<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Customer;
use App\Models\Insurance;
use App\Models\InvoiceProtocol;
use App\Models\PointOfSale;
use App\Models\SalesInvoice;
use App\Models\Sample;
use App\Models\VetAdmission;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function uninvoiced(Request $request)
    {
        $this->authorize('sales-invoices.index');

        $module = $request->get('module', 'all');
        $billingStatus = $request->get('billing_status', 'uninvoiced');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $insuranceId = $request->get('insurance_id');
        $customerId = $request->get('customer_id');

        $admissions = collect();
        $samples = collect();
        $vetAdmissions = collect();

        if (in_array($module, ['all', 'clinico'])) {
            $q = Admission::query()
                ->with(['patient', 'insuranceRelation', 'admissionTests.test', 'labBranch']);
            if ($billingStatus === 'uninvoiced') {
                $q->uninvoiced();
            } elseif ($billingStatus === 'invoiced') {
                $q->invoiced();
            }
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
            $q = Sample::query()
                ->with(['customer', 'determinations', 'labBranch']);
            if ($billingStatus === 'uninvoiced') {
                $q->uninvoiced();
            } elseif ($billingStatus === 'invoiced') {
                $q->invoiced();
            }
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
            $q = VetAdmission::query()
                ->with(['customer', 'species', 'labBranch']);
            if ($billingStatus === 'uninvoiced') {
                $q->uninvoiced();
            } elseif ($billingStatus === 'invoiced') {
                $q->invoiced();
            }
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
            'insurances', 'customers', 'module', 'billingStatus', 'totals'
        ));
    }

    public function batchPreview(Request $request)
    {
        $this->authorize('sales-invoices.create');

        $request->validate([
            'protocol_type' => 'required|in:admission,sample,vet_admission',
            'protocol_ids' => 'required|array|min:1',
        ]);

        $protocolClass = match ($request->protocol_type) {
            'admission' => Admission::class,
            'sample' => Sample::class,
            'vet_admission' => VetAdmission::class,
        };

        $protocols = $protocolClass::whereIn('id', $request->protocol_ids)
            ->uninvoiced()
            ->get();

        $total = 0;
        $itemCount = 0;
        foreach ($protocols as $p) {
            if ($request->protocol_type === 'admission') {
                $p->load('admissionTests.test');
                $total += $p->admissionTests->sum('price');
                $itemCount += $p->admissionTests->count();
            } elseif ($request->protocol_type === 'sample') {
                $p->load('determinations.test');
                $total += $p->determinations->sum('price');
                $itemCount += $p->determinations->count();
            } else {
                $p->load('vetTests.test');
                $total += $p->vetTests->sum('price');
                $itemCount += $p->vetTests->count();
            }
        }

        $customerId = null;
        $customerName = '';
        $voucherType = 'B';

        if ($request->protocol_type === 'admission' && $protocols->isNotEmpty()) {
            $insurance = $protocols->first()->insuranceRelation;
            $customerName = $insurance?->name ?? 'Sin obra social';
        } else {
            $customer = $protocols->first()?->customer;
            $customerId = $customer?->id;
            $customerName = $customer?->name ?? '';
            if ($customer && in_array($customer->iva_condition, ['IVA Responsable Inscripto', 'Responsable Inscripto'])) {
                $voucherType = 'A';
            }
        }

        $pointsOfSale = PointOfSale::where('is_active', true)
            ->where('company_id', active_company_id())
            ->orderBy('code')->get();

        $customers = Customer::where('status', 'activo')->orderBy('name')->get();

        return view('billing.batch-preview', compact(
            'protocols', 'total', 'itemCount', 'customerId', 'customerName',
            'voucherType', 'pointsOfSale', 'customers'
        ))->with([
            'protocolType' => $request->protocol_type,
            'protocolIds' => $request->protocol_ids,
        ]);
    }

    public function batchInvoice(Request $request)
    {
        $this->authorize('sales-invoices.create');

        $request->validate([
            'protocol_type' => 'required|in:admission,sample,vet_admission',
            'protocol_ids' => 'required|array|min:1',
            'protocol_ids.*' => 'integer',
            'customer_id' => 'required|exists:customers,id',
            'point_of_sale_id' => 'required|exists:points_of_sale,id',
            'voucher_type' => 'required|in:A,B,C',
            'issue_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $protocolClass = match ($request->protocol_type) {
            'admission' => Admission::class,
            'sample' => Sample::class,
            'vet_admission' => VetAdmission::class,
        };

        $protocols = $protocolClass::whereIn('id', $request->protocol_ids)
            ->uninvoiced()
            ->get();

        if ($protocols->isEmpty()) {
            return back()->with('error', 'No se encontraron protocolos válidos para facturar.');
        }

        $items = [];
        foreach ($protocols as $protocol) {
            if ($request->protocol_type === 'admission') {
                $protocol->load('admissionTests.test');
                foreach ($protocol->admissionTests as $at) {
                    $items[] = [
                        'description' => $protocol->protocol_number.' — '.($at->test?->name ?? 'Determinación'),
                        'test_id' => $at->test_id,
                        'quantity' => 1,
                        'unit_price' => $at->price ?? 0,
                        'iva_rate' => 21,
                    ];
                }
            } elseif ($request->protocol_type === 'sample') {
                $protocol->load('determinations.test');
                foreach ($protocol->determinations as $det) {
                    $items[] = [
                        'description' => $protocol->protocol_number.' — '.($det->test?->name ?? 'Determinación'),
                        'test_id' => $det->test_id ?? null,
                        'quantity' => 1,
                        'unit_price' => $det->price ?? 0,
                        'iva_rate' => 21,
                    ];
                }
            } elseif ($request->protocol_type === 'vet_admission') {
                $protocol->load('vetTests.test');
                foreach ($protocol->vetTests as $vt) {
                    $items[] = [
                        'description' => $protocol->protocol_number.' — '.($vt->test?->name ?? 'Determinación'),
                        'test_id' => $vt->test_id,
                        'quantity' => 1,
                        'unit_price' => $vt->price ?? 0,
                        'iva_rate' => 21,
                    ];
                }
            }
        }

        if (empty($items)) {
            return back()->with('error', 'Los protocolos seleccionados no tienen determinaciones.');
        }

        $pointOfSale = PointOfSale::findOrFail($request->point_of_sale_id);
        $isElectronic = $pointOfSale->is_electronic;

        $invoice = SalesInvoice::create([
            'company_id' => $pointOfSale->company_id,
            'invoice_number' => $isElectronic ? 'PENDIENTE-AFIP' : null,
            'voucher_type' => $request->voucher_type,
            'point_of_sale_id' => $request->point_of_sale_id,
            'customer_id' => $request->customer_id,
            'issue_date' => $request->issue_date,
            'notes' => $request->notes,
            'status' => 'pendiente',
            'amount_collected' => 0,
            'is_electronic' => $isElectronic,
            'created_by' => auth()->id(),
        ]);

        $sortOrder = 0;
        foreach ($items as $itemData) {
            $ivaAmount = round($itemData['quantity'] * $itemData['unit_price'] * $itemData['iva_rate'] / 100, 2);
            $total = $itemData['quantity'] * $itemData['unit_price'] + $ivaAmount;

            $invoice->items()->create([
                'description' => $itemData['description'],
                'test_id' => $itemData['test_id'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'iva_rate' => $itemData['iva_rate'],
                'iva_amount' => $ivaAmount,
                'total' => $total,
                'sort_order' => $sortOrder++,
            ]);
        }

        $invoice->recalculate();

        foreach ($protocols as $protocol) {
            InvoiceProtocol::create([
                'sales_invoice_id' => $invoice->id,
                'protocol_type' => $protocolClass,
                'protocol_id' => $protocol->id,
                'amount' => 0,
            ]);
        }

        $msg = "Borrador creado con {$protocols->count()} protocolos. Revisá los datos y agregá líneas extras si es necesario antes de "
            .($isElectronic ? 'enviar a AFIP.' : 'confirmar.');

        return redirect()->route('sales-invoices.edit', $invoice)->with('success', $msg);
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
