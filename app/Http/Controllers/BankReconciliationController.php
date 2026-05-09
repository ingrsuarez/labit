<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankStatement;
use App\Models\CollectionReceipt;
use App\Models\PaymentOrder;
use App\Models\PayrollPayment;
use App\Services\BankReconciliationService;
use Illuminate\Http\Request;

class BankReconciliationController extends Controller
{
    public function index(BankAccount $bankAccount, BankStatement $statement)
    {
        $this->authorize('contabilidad.bank_statements.index');

        $companyId = $bankAccount->company_id;

        $movements = $statement->movements()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->with('reconciledRecord')
            ->get();

        $paymentOrders = PaymentOrder::where('company_id', $companyId)
            ->whereIn('status', ['pagada', 'aprobada'])
            ->whereDoesntHave('reconciledMovements')
            ->with(['supplier', 'paymentLines', 'portfolioEcheqPayments'])
            ->orderByDesc('date')
            ->get();

        $collectionReceipts = CollectionReceipt::where('company_id', $companyId)
            ->where('status', 'confirmado')
            ->whereDoesntHave('reconciledMovements')
            ->with('customer')
            ->orderByDesc('date')
            ->get();

        $service = new BankReconciliationService;
        $payrollPayments = $service->getUnreconciledPayrollPayments($companyId);

        $payrollPaymentSuggestions = [];
        foreach ($movements->where('reconciliation_status', 'pending')->where('debit', '>', 0) as $mov) {
            $match = $service->findMatch($mov, $companyId);
            if (in_array($match['confidence'] ?? '', ['exact', 'probable'], true)
                && $match['record'] instanceof PayrollPayment) {
                $payrollPaymentSuggestions[$mov->id] = $match['record']->id;
            }
        }

        $progress = $statement->reconciliation_progress;

        return view('accounting.bank-statements.reconcile', compact(
            'bankAccount', 'statement', 'movements',
            'paymentOrders', 'collectionReceipts', 'payrollPayments',
            'payrollPaymentSuggestions', 'progress'
        ));
    }

    public function autoReconcile(BankAccount $bankAccount, BankStatement $statement)
    {
        $this->authorize('contabilidad.reconciliation.execute');

        $service = new BankReconciliationService;
        $result = $service->autoReconcile($statement);

        $msg = "Conciliación automática: {$result['matched']} vinculados.";
        if (count($result['suggestions']) > 0) {
            $msg .= ' '.count($result['suggestions']).' sugerencias pendientes.';
        }
        if ($result['pending'] > 0) {
            $msg .= " {$result['pending']} sin match.";
        }

        return redirect()->route('accounting.reconciliation.index', [$bankAccount, $statement])
            ->with('success', $msg);
    }

    public function link(Request $request, BankMovement $movement)
    {
        $this->authorize('contabilidad.reconciliation.manual');

        $validated = $request->validate([
            'reconciled_type' => 'required|in:PaymentOrder,CollectionReceipt,PayrollPayment',
            'reconciled_id' => 'required|integer',
        ]);

        $modelClass = match ($validated['reconciled_type']) {
            'PaymentOrder' => PaymentOrder::class,
            'CollectionReceipt' => CollectionReceipt::class,
            'PayrollPayment' => PayrollPayment::class,
        };

        $record = $modelClass::findOrFail($validated['reconciled_id']);

        $companyId = $movement->statement->bankAccount->company_id;
        if ((int) $record->getAttribute('company_id') !== (int) $companyId) {
            return back()->with('error', 'El registro no pertenece a la misma empresa que la cuenta bancaria.');
        }

        $service = new BankReconciliationService;
        $service->linkMovement($movement, $record, auth()->user());

        return back()->with('success', 'Movimiento vinculado correctamente.');
    }

    public function unlink(BankMovement $movement)
    {
        $this->authorize('contabilidad.reconciliation.manual');

        $service = new BankReconciliationService;
        $service->unlinkMovement($movement);

        return back()->with('success', 'Vinculación eliminada.');
    }

    public function ignore(Request $request, BankMovement $movement)
    {
        $this->authorize('contabilidad.reconciliation.manual');

        $request->validate(['notes' => 'nullable|string|max:500']);

        $service = new BankReconciliationService;
        $service->ignoreMovement($movement, auth()->user(), $request->notes);

        return back()->with('success', 'Movimiento marcado como ignorado.');
    }

    public function bulkIgnore(Request $request, BankStatement $statement)
    {
        $this->authorize('contabilidad.reconciliation.manual');

        $validated = $request->validate([
            'category' => 'required|string|max:30',
        ]);

        $movements = $statement->movements()
            ->where('reconciliation_status', 'pending')
            ->where('category', $validated['category'])
            ->get();

        $count = 0;
        $service = new BankReconciliationService;
        foreach ($movements as $movement) {
            $service->ignoreMovement($movement, auth()->user(), "Bulk ignore: {$validated['category']}");
            $count++;
        }

        return back()->with('success', "{$count} movimientos de categoría \"{$validated['category']}\" marcados como ignorados.");
    }
}
