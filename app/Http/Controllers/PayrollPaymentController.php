<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\JournalEntry;
use App\Models\Payroll;
use App\Models\PayrollPayment;
use App\Services\AccountingEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollPaymentController extends Controller
{
    public function index()
    {
        $payrollPayments = PayrollPayment::with(['bankAccount', 'creator'])
            ->where('company_id', active_company_id())
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->paginate(15);

        return view('payroll-payments.index', compact('payrollPayments'));
    }

    public function create(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $bankAccounts = BankAccount::active()
            ->where('company_id', active_company_id())
            ->get();

        $availablePayrolls = Payroll::with('employee')
            ->whereHas('employee', fn ($q) => $q->where('company_id', active_company_id()))
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'liquidado')
            ->whereNull('payroll_payment_id')
            ->orderBy('employee_name')
            ->get();

        $existingPayments = PayrollPayment::where('company_id', active_company_id())
            ->where('year', $year)
            ->where('month', $month)
            ->withCount('payrolls')
            ->get();

        return view('payroll-payments.create', compact(
            'year', 'month', 'bankAccounts', 'availablePayrolls', 'existingPayments'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'payroll_ids' => 'required|array|min:1',
            'payroll_ids.*' => 'integer|exists:payrolls,id',
        ]);

        $companyId = active_company_id();

        // Validar cada liquidación individualmente
        foreach ($validated['payroll_ids'] as $payrollId) {
            $payroll = Payroll::with('employee')->find($payrollId);

            if (! $payroll || $payroll->employee?->company_id !== $companyId) {
                return back()->withErrors(['payroll_ids' => 'Una o más liquidaciones no pertenecen a la empresa activa.']);
            }

            if ($payroll->status !== 'liquidado') {
                return back()->withErrors(['payroll_ids' => "La liquidación de {$payroll->employee_name} no está en estado liquidado."]);
            }

            if ($payroll->payroll_payment_id !== null) {
                return back()->withErrors(['payroll_ids' => "La liquidación de {$payroll->employee_name} ya está asignada a un pago."]);
            }
        }

        $periodLabel = Carbon::createFromDate($validated['year'], $validated['month'], 1)
            ->translatedFormat('F Y');

        $payment = PayrollPayment::create([
            'company_id' => $companyId,
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'year' => $validated['year'],
            'month' => $validated['month'],
            'period_label' => $periodLabel,
            'payment_date' => $validated['payment_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'borrador',
            'created_by' => auth()->id(),
        ]);

        Payroll::whereIn('id', $validated['payroll_ids'])
            ->update(['payroll_payment_id' => $payment->id]);

        $payment->recalculate();

        return redirect()->route('payroll-payments.show', $payment)
            ->with('success', 'Pago de haberes creado correctamente.');
    }

    public function show(PayrollPayment $payrollPayment)
    {
        if ($payrollPayment->company_id !== active_company_id()) {
            abort(403);
        }

        $payrollPayment->loadMissing([
            'payrolls',
            'bankAccount',
            'creator',
            'confirmer',
        ]);

        $journalEntry = JournalEntry::with('lines.account')
            ->where('source_type', PayrollPayment::class)
            ->where('source_id', $payrollPayment->id)
            ->first();

        $reconciledMovements = $payrollPayment->reconciledMovements()->with('statement.bankAccount')->get();

        return view('payroll-payments.show', compact('payrollPayment', 'journalEntry', 'reconciledMovements'));
    }

    public function confirm(PayrollPayment $payrollPayment)
    {
        if ($payrollPayment->company_id !== active_company_id()) {
            abort(403);
        }

        if ($payrollPayment->isConfirmado()) {
            return back()->with('error', 'Este pago ya está confirmado.');
        }

        if ($payrollPayment->payrolls()->count() === 0) {
            return back()->with('error', 'El pago no tiene liquidaciones vinculadas.');
        }

        DB::transaction(function () use ($payrollPayment) {
            $payrollPayment->update([
                'status' => 'confirmado',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);

            Payroll::where('payroll_payment_id', $payrollPayment->id)
                ->update([
                    'status' => 'pagado',
                    'paid_at' => now(),
                ]);

            $service = app(AccountingEntryService::class);
            $service->fromPayrollPayment($payrollPayment->fresh());
        });

        return redirect()->route('payroll-payments.show', $payrollPayment)
            ->with('success', 'Pago de haberes confirmado. Liquidaciones marcadas como pagadas y asiento contable generado.');
    }

    public function destroy(PayrollPayment $payrollPayment)
    {
        if ($payrollPayment->company_id !== active_company_id()) {
            abort(403);
        }

        if ($payrollPayment->isConfirmado()) {
            return back()->with('error', 'No se puede eliminar un pago confirmado.');
        }

        Payroll::where('payroll_payment_id', $payrollPayment->id)
            ->update(['payroll_payment_id' => null]);

        $payrollPayment->delete();

        return redirect()->route('payroll-payments.index')
            ->with('success', 'Pago de haberes eliminado. Las liquidaciones quedaron disponibles.');
    }
}
