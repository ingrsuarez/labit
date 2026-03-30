<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Customer;
use App\Models\Insurance;
use App\Models\Sample;
use App\Models\VetAdmission;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function uninvoiced(Request $request)
    {
        $this->authorize('sales-invoices.index');

        $module = $request->get('module', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $insuranceId = $request->get('insurance_id');
        $customerId = $request->get('customer_id');

        $admissions = collect();
        $samples = collect();
        $vetAdmissions = collect();

        if (in_array($module, ['all', 'clinico'])) {
            $q = Admission::uninvoiced()
                ->with(['patient', 'insuranceRelation', 'admissionTests.test', 'labBranch']);
            if ($dateFrom) {
                $q->whereDate('date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $q->whereDate('date', '<=', $dateTo);
            }
            if ($insuranceId) {
                $q->where('insurance', $insuranceId);
            }
            $admissions = $q->orderBy('date', 'desc')->get();
        }

        if (in_array($module, ['all', 'aguas'])) {
            $q = Sample::uninvoiced()
                ->with(['customer', 'determinations', 'labBranch']);
            if ($dateFrom) {
                $q->whereDate('entry_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $q->whereDate('entry_date', '<=', $dateTo);
            }
            if ($customerId) {
                $q->where('customer_id', $customerId);
            }
            $samples = $q->orderBy('entry_date', 'desc')->get();
        }

        if (in_array($module, ['all', 'veterinario'])) {
            $q = VetAdmission::uninvoiced()
                ->with(['customer', 'species', 'labBranch']);
            if ($dateFrom) {
                $q->whereDate('date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $q->whereDate('date', '<=', $dateTo);
            }
            if ($customerId) {
                $q->where('customer_id', $customerId);
            }
            $vetAdmissions = $q->orderBy('date', 'desc')->get();
        }

        $insurances = Insurance::where('type', '!=', 'nomenclador')
            ->orderByRaw("CASE WHEN type = 'particular' THEN 0 ELSE 1 END")
            ->orderBy('name')->get();
        $customers = Customer::where('status', 'activo')->orderBy('name')->get();

        $totals = [
            'clinico' => $admissions->sum(fn ($a) => $a->total_patient ?: $a->patient_price ?: 0),
            'aguas' => $samples->sum(fn ($s) => $s->determinations->sum('price')),
            'veterinario' => $vetAdmissions->sum('total_price'),
        ];

        return view('billing.uninvoiced', compact(
            'admissions', 'samples', 'vetAdmissions',
            'insurances', 'customers', 'module', 'totals'
        ));
    }

    public function summary()
    {
        return response()->json([
            'clinico' => Admission::uninvoiced()->count(),
            'aguas' => Sample::uninvoiced()->count(),
            'veterinario' => VetAdmission::uninvoiced()->count(),
        ]);
    }
}
