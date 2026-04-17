<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CollectionReceiptPayment;
use App\Models\JournalEntry;
use App\Models\PaymentOrder;
use App\Models\PaymentOrderPaymentLine;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Services\AccountingEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentOrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('payment-orders.index');

        $query = PaymentOrder::with(['supplier', 'creator', 'approver', 'portfolioEcheqPayments', 'paymentLines'])
            ->where('company_id', active_company_id())
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paymentOrders = $query->paginate(15)->withQueryString();

        return view('payment-orders.index', compact('paymentOrders'));
    }

    public function create(Request $request)
    {
        $this->authorize('payment-orders.create');

        $suppliers = Supplier::active()->orderBy('name')->get();
        $selectedSupplier = null;
        $pendingInvoices = collect();

        if ($request->filled('supplier_id')) {
            $selectedSupplier = Supplier::find($request->supplier_id);
            if ($selectedSupplier) {
                $pendingInvoices = PurchaseInvoice::where('company_id', active_company_id())
                    ->where('supplier_id', $selectedSupplier->id)
                    ->whereIn('status', ['pendiente', 'parcialmente_pagada'])
                    ->orderByDesc('issue_date')
                    ->get();
            }
        }

        $portfolioEcheqsJson = $this->portfolioEcheqsPayloadForUi(
            CollectionReceiptPayment::availableInPortfolio(active_company_id())
                ->with(['collectionReceipt:id,number'])
                ->orderBy('due_date')
                ->orderBy('id')
                ->get()
        );

        $bankAccounts = BankAccount::active()
            ->where('company_id', active_company_id())
            ->orderBy('bank_name')
            ->orderBy('account_number')
            ->get();

        return view('payment-orders.create', compact('suppliers', 'selectedSupplier', 'pendingInvoices', 'portfolioEcheqsJson', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $this->authorize('payment-orders.create');

        $validated = $this->validatePaymentOrderRequest($request, null);

        DB::beginTransaction();
        try {
            $paymentOrder = PaymentOrder::create([
                'number' => PaymentOrder::generateNumber(),
                'company_id' => active_company_id(),
                'supplier_id' => $validated['supplier_id'],
                'date' => $validated['date'],
                'payment_method' => null,
                'payment_reference' => null,
                'cheque_due_date' => null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'borrador',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['invoices'] as $itemData) {
                $paymentOrder->items()->create([
                    'purchase_invoice_id' => $itemData['purchase_invoice_id'],
                    'amount' => $itemData['amount'],
                ]);
            }

            $paymentOrder->recalculate();

            $this->syncPaymentLines($paymentOrder, $validated['payments']);

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('payment-orders.show', $paymentOrder)
            ->with('success', 'Orden de pago '.$paymentOrder->number.' creada correctamente.');
    }

    public function show(PaymentOrder $paymentOrder)
    {
        abort_if($paymentOrder->company_id !== active_company_id(), 403);

        $this->authorize('payment-orders.index');

        $paymentOrder->load([
            'supplier', 'creator', 'approver', 'items.invoice.supplier',
            'portfolioEcheqPayments.collectionReceipt',
            'paymentLines.bankAccount',
            'paymentLines.collectionReceiptPayment.collectionReceipt',
        ]);

        return view('payment-orders.show', compact('paymentOrder'));
    }

    public function edit(PaymentOrder $paymentOrder)
    {
        abort_if($paymentOrder->company_id !== active_company_id(), 403);

        $this->authorize('payment-orders.edit');

        if ($paymentOrder->status !== 'borrador') {
            return redirect()->route('payment-orders.show', $paymentOrder)
                ->with('error', 'Solo se pueden editar órdenes de pago en estado borrador.');
        }

        $paymentOrder->load(['items.invoice', 'portfolioEcheqPayments', 'paymentLines']);
        $suppliers = Supplier::active()->orderBy('name')->get();

        $existingInvoiceIds = $paymentOrder->items->pluck('purchase_invoice_id')->toArray();

        $pendingInvoices = PurchaseInvoice::where('company_id', active_company_id())
            ->where('supplier_id', $paymentOrder->supplier_id)
            ->where(function ($q) use ($existingInvoiceIds) {
                $q->whereIn('status', ['pendiente', 'parcialmente_pagada'])
                    ->orWhereIn('id', $existingInvoiceIds);
            })
            ->orderByDesc('issue_date')
            ->get();

        $companyId = active_company_id();
        $linked = $paymentOrder->portfolioEcheqPayments;
        $available = CollectionReceiptPayment::availableInPortfolio($companyId)
            ->with(['collectionReceipt:id,number'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
        $merged = $linked->merge($available)->unique('id')->sortBy(fn ($p) => [$p->due_date?->timestamp ?? 0, $p->id])->values();
        $portfolioEcheqsJson = $this->portfolioEcheqsPayloadForUi($merged);

        $bankAccounts = BankAccount::active()
            ->where('company_id', $companyId)
            ->orderBy('bank_name')
            ->orderBy('account_number')
            ->get();

        return view('payment-orders.edit', compact(
            'paymentOrder',
            'suppliers',
            'pendingInvoices',
            'portfolioEcheqsJson',
            'bankAccounts'
        ));
    }

    public function update(Request $request, PaymentOrder $paymentOrder)
    {
        abort_if($paymentOrder->company_id !== active_company_id(), 403);

        $this->authorize('payment-orders.edit');

        if ($paymentOrder->status !== 'borrador') {
            return redirect()->route('payment-orders.show', $paymentOrder)
                ->with('error', 'Solo se pueden editar órdenes de pago en estado borrador.');
        }

        $validated = $this->validatePaymentOrderRequest($request, $paymentOrder->id);

        DB::beginTransaction();
        try {
            $paymentOrder->update([
                'supplier_id' => $validated['supplier_id'],
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $paymentOrder->items()->delete();

            foreach ($validated['invoices'] as $itemData) {
                $paymentOrder->items()->create([
                    'purchase_invoice_id' => $itemData['purchase_invoice_id'],
                    'amount' => $itemData['amount'],
                ]);
            }

            $paymentOrder->recalculate();

            $this->syncPaymentLines($paymentOrder, $validated['payments']);

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('payment-orders.show', $paymentOrder)
            ->with('success', 'Orden de pago actualizada correctamente.');
    }

    public function destroy(PaymentOrder $paymentOrder)
    {
        abort_if($paymentOrder->company_id !== active_company_id(), 403);

        $this->authorize('payment-orders.delete');

        if ($paymentOrder->status !== 'borrador') {
            return redirect()->route('payment-orders.show', $paymentOrder)
                ->with('error', 'Solo se pueden eliminar órdenes de pago en estado borrador.');
        }

        $number = $paymentOrder->number;
        $this->releasePortfolioEcheqs($paymentOrder);
        $paymentOrder->delete();

        return redirect()->route('payment-orders.index')
            ->with('success', "Orden de pago {$number} eliminada.");
    }

    public function confirm(PaymentOrder $paymentOrder)
    {
        abort_if($paymentOrder->company_id !== active_company_id(), 403);

        $this->authorize('payment-orders.edit');

        if (! in_array($paymentOrder->status, ['borrador', 'aprobada'])) {
            return back()->with('error', 'Solo se pueden confirmar órdenes en estado borrador o aprobada.');
        }

        $paymentOrder->load(['items.invoice', 'portfolioEcheqPayments', 'paymentLines']);

        $total = round((float) $paymentOrder->total, 2);
        $sumLines = round((float) $paymentOrder->paymentLines->sum('amount'), 2);
        if ($paymentOrder->paymentLines->isEmpty()) {
            return back()->with('error', 'La orden no tiene medios de pago definidos. Editá el borrador.');
        }
        if (abs($sumLines - $total) > 0.01) {
            return back()->with('error', 'La suma de los medios de pago no coincide con el total de la orden.');
        }

        foreach ($paymentOrder->paymentLines->where('kind', 'portfolio_echeq') as $line) {
            $crp = CollectionReceiptPayment::find($line->collection_receipt_payment_id);
            if (! $crp || (int) $crp->payment_order_id !== (int) $paymentOrder->id) {
                return back()->with('error', 'Hay líneas de e-cheq inconsistentes con esta orden.');
            }
        }

        DB::beginTransaction();
        try {
            foreach ($paymentOrder->items as $item) {
                $invoice = $item->invoice;
                $invoice->amount_paid += $item->amount;
                $invoice->balance = $invoice->total - $invoice->amount_paid;
                $invoice->updatePaymentStatus();
            }

            $paymentOrder->status = 'pagada';
            $paymentOrder->approved_by = auth()->id();
            $paymentOrder->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al confirmar la orden de pago: '.$e->getMessage());
        }

        try {
            $paymentOrder->refresh();
            if (! JournalEntry::where('source_type', PaymentOrder::class)->where('source_id', $paymentOrder->id)->exists()) {
                (new AccountingEntryService)->fromPaymentOrder(
                    $paymentOrder->load(['supplier', 'portfolioEcheqPayments', 'paymentLines.bankAccount'])
                );
            }
        } catch (\Throwable $e) {
            Log::error('Error generando asiento para OP #'.$paymentOrder->id.': '.$e->getMessage());
        }

        return back()->with('success', 'Orden de pago '.$paymentOrder->number.' confirmada y pagada.');
    }

    private function validatePaymentOrderRequest(Request $request, ?int $editingPaymentOrderId): array
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'invoices' => 'required|array|min:1',
            'invoices.*.purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'invoices.*.amount' => 'required|numeric|min:0.01',
            'payments' => 'required|array|min:1',
            'payments.*.kind' => 'required|in:transferencia,cheque,efectivo,portfolio_echeq',
            'payments.*.amount' => 'nullable|numeric|min:0.01',
            'payments.*.bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
            'payments.*.portfolio_echeq_id' => 'nullable|integer|exists:collection_receipt_payments,id',
            'payments.*.payment_reference' => 'nullable|string|max:255',
            'payments.*.cheque_due_date' => 'nullable|date',
        ], [
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'date.required' => 'La fecha es obligatoria.',
            'invoices.required' => 'Debe agregar al menos una factura.',
            'invoices.min' => 'Debe agregar al menos una factura.',
            'invoices.*.purchase_invoice_id.required' => 'Debe seleccionar una factura.',
            'invoices.*.amount.required' => 'El monto es obligatorio.',
            'invoices.*.amount.min' => 'El monto debe ser mayor a 0.',
            'payments.required' => 'Debe indicar al menos un medio de pago.',
            'payments.min' => 'Debe indicar al menos un medio de pago.',
        ]);

        $orderTotal = round((float) collect($validated['invoices'])->sum('amount'), 2);
        $validated['payments'] = $this->normalizePaymentLinesPayload(
            $validated['payments'],
            $orderTotal,
            (int) active_company_id(),
            $editingPaymentOrderId
        );

        return $validated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{kind: string, amount: float, bank_account_id: ?int, collection_receipt_payment_id: ?int, payment_reference: ?string, cheque_due_date: ?string}>
     */
    private function normalizePaymentLinesPayload(array $rows, float $orderTotal, int $companyId, ?int $editingPaymentOrderId): array
    {
        $orderTotal = round($orderTotal, 2);
        $normalized = [];
        $sort = 0;
        $usedPortfolioIds = [];

        foreach ($rows as $i => $row) {
            $prefix = "payments.{$i}";
            $kind = $row['kind'];

            if ($kind === 'portfolio_echeq') {
                $pid = isset($row['portfolio_echeq_id']) ? (int) $row['portfolio_echeq_id'] : 0;
                if ($pid <= 0) {
                    throw ValidationException::withMessages([
                        $prefix.'.portfolio_echeq_id' => 'Seleccioná un e-cheq de cartera para esta línea.',
                    ]);
                }

                $crp = CollectionReceiptPayment::query()
                    ->where('id', $pid)
                    ->where('line_type', 'echeq')
                    ->with('collectionReceipt')
                    ->first();

                if (! $crp) {
                    throw ValidationException::withMessages([
                        $prefix.'.portfolio_echeq_id' => 'El e-cheq indicado no es válido.',
                    ]);
                }

                $rc = $crp->collectionReceipt;
                if (! $rc || (int) $rc->company_id !== $companyId || $rc->status !== 'confirmado') {
                    throw ValidationException::withMessages([
                        $prefix.'.portfolio_echeq_id' => 'Solo se pueden usar e-cheq de recibos confirmados de la empresa activa.',
                    ]);
                }

                $poId = $crp->payment_order_id;
                if ($poId !== null && (int) $poId !== (int) ($editingPaymentOrderId ?? 0)) {
                    throw ValidationException::withMessages([
                        $prefix.'.portfolio_echeq_id' => 'Uno de los e-cheqs ya está reservado en otra orden de pago.',
                    ]);
                }

                if (isset($usedPortfolioIds[$pid])) {
                    throw ValidationException::withMessages([
                        $prefix.'.portfolio_echeq_id' => 'No podés repetir el mismo e-cheq en dos líneas.',
                    ]);
                }
                $usedPortfolioIds[$pid] = true;

                $amt = round((float) $crp->amount, 2);
                $normalized[] = [
                    'kind' => 'portfolio_echeq',
                    'amount' => $amt,
                    'bank_account_id' => null,
                    'collection_receipt_payment_id' => $crp->id,
                    'payment_reference' => null,
                    'cheque_due_date' => null,
                    'sort_order' => $sort++,
                ];

                continue;
            }

            $amt = round((float) ($row['amount'] ?? 0), 2);
            if ($amt <= 0) {
                throw ValidationException::withMessages([
                    $prefix.'.amount' => 'El monto debe ser mayor a 0.',
                ]);
            }

            $bankAccountId = isset($row['bank_account_id']) && $row['bank_account_id'] !== '' && $row['bank_account_id'] !== null
                ? (int) $row['bank_account_id']
                : null;

            if ($kind === 'transferencia') {
                if (! $bankAccountId) {
                    throw ValidationException::withMessages([
                        $prefix.'.bank_account_id' => 'Seleccioná la cuenta bancaria de origen para la transferencia.',
                    ]);
                }
            }

            if ($bankAccountId) {
                $exists = BankAccount::query()
                    ->where('id', $bankAccountId)
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->exists();
                if (! $exists) {
                    throw ValidationException::withMessages([
                        $prefix.'.bank_account_id' => 'La cuenta bancaria no pertenece a la empresa activa o está inactiva.',
                    ]);
                }
            }

            $normalized[] = [
                'kind' => $kind,
                'amount' => $amt,
                'bank_account_id' => $bankAccountId,
                'collection_receipt_payment_id' => null,
                'payment_reference' => $row['payment_reference'] ?? null,
                'cheque_due_date' => $row['cheque_due_date'] ?? null,
                'sort_order' => $sort++,
            ];
        }

        $sum = round((float) collect($normalized)->sum('amount'), 2);
        if (abs($sum - $orderTotal) > 0.01) {
            throw ValidationException::withMessages([
                'payments' => 'La suma de los medios de pago ($'.number_format($sum, 2, ',', '.').') debe igualar el total de facturas ($'.number_format($orderTotal, 2, ',', '.').').',
            ]);
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncPaymentLines(PaymentOrder $paymentOrder, array $lines): void
    {
        $this->releasePortfolioEcheqs($paymentOrder);
        $paymentOrder->paymentLines()->delete();

        foreach ($lines as $line) {
            PaymentOrderPaymentLine::query()->create([
                'payment_order_id' => $paymentOrder->id,
                'sort_order' => (int) ($line['sort_order'] ?? 0),
                'kind' => $line['kind'],
                'amount' => $line['amount'],
                'bank_account_id' => $line['bank_account_id'] ?? null,
                'collection_receipt_payment_id' => $line['collection_receipt_payment_id'] ?? null,
                'payment_reference' => $line['payment_reference'] ?? null,
                'cheque_due_date' => $line['cheque_due_date'] ?? null,
            ]);
        }

        $portfolioIds = collect($lines)
            ->filter(fn ($l) => ($l['kind'] ?? '') === 'portfolio_echeq' && ! empty($l['collection_receipt_payment_id']))
            ->pluck('collection_receipt_payment_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($portfolioIds !== []) {
            $this->attachPortfolioEcheqs($paymentOrder, $portfolioIds);
        }

        $this->refreshPaymentOrderSummaryFields($paymentOrder);
    }

    private function refreshPaymentOrderSummaryFields(PaymentOrder $paymentOrder): void
    {
        $paymentOrder->load('paymentLines');
        $lines = $paymentOrder->paymentLines;

        if ($lines->isEmpty()) {
            return;
        }

        $onlyPortfolio = $lines->every(fn ($l) => $l->kind === 'portfolio_echeq');
        if ($onlyPortfolio) {
            $paymentOrder->forceFill([
                'payment_method' => 'cheque',
                'payment_reference' => 'E-cheq cartera',
                'cheque_due_date' => null,
            ])->save();

            return;
        }

        $nonPortfolio = $lines->filter(fn ($l) => $l->kind !== 'portfolio_echeq')->values();
        if ($nonPortfolio->count() === 1 && $lines->count() === 1) {
            $l = $nonPortfolio->first();
            $paymentOrder->forceFill([
                'payment_method' => $l->kind,
                'payment_reference' => $l->payment_reference,
                'cheque_due_date' => $l->cheque_due_date,
            ])->save();

            return;
        }

        $paymentOrder->forceFill([
            'payment_method' => null,
            'payment_reference' => null,
            'cheque_due_date' => null,
        ])->save();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CollectionReceiptPayment>  $payments
     * @return list<array<string, mixed>>
     */
    private function portfolioEcheqsPayloadForUi($payments): array
    {
        return $payments->map(fn (CollectionReceiptPayment $p) => [
            'id' => $p->id,
            'amount' => (float) $p->amount,
            'cheque_number' => $p->cheque_number ?? '',
            'bank_name' => $p->bank_name ?? '',
            'due_date' => $p->due_date?->format('d/m/Y') ?? '',
            'rc_number' => $p->collectionReceipt?->number ?? '—',
        ])->values()->all();
    }

    private function releasePortfolioEcheqs(PaymentOrder $paymentOrder): void
    {
        CollectionReceiptPayment::where('payment_order_id', $paymentOrder->id)->update(['payment_order_id' => null]);
    }

    /**
     * @param  list<int>  $ids
     */
    private function attachPortfolioEcheqs(PaymentOrder $paymentOrder, array $ids): void
    {
        $companyId = (int) $paymentOrder->company_id;

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $lines = CollectionReceiptPayment::query()
            ->whereIn('id', $ids)
            ->where('line_type', 'echeq')
            ->with('collectionReceipt')
            ->get();

        if ($lines->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'payments' => 'Uno o más e-cheqs no son válidos.',
            ]);
        }

        foreach ($lines as $line) {
            $rc = $line->collectionReceipt;
            if (! $rc || (int) $rc->company_id !== $companyId || $rc->status !== 'confirmado') {
                throw ValidationException::withMessages([
                    'payments' => 'Solo se pueden usar e-cheq de recibos confirmados de la empresa activa.',
                ]);
            }
            if ($line->payment_order_id !== null && (int) $line->payment_order_id !== (int) $paymentOrder->id) {
                throw ValidationException::withMessages([
                    'payments' => 'Uno de los e-cheqs ya está reservado en otra orden de pago.',
                ]);
            }
        }

        $sum = round((float) $lines->sum('amount'), 2);
        $expectedPortfolio = round((float) PaymentOrderPaymentLine::query()
            ->where('payment_order_id', $paymentOrder->id)
            ->where('kind', 'portfolio_echeq')
            ->sum('amount'), 2);
        if (abs($sum - $expectedPortfolio) > 0.01) {
            throw ValidationException::withMessages([
                'payments' => 'La suma de los e-cheqs seleccionados no coincide con las líneas de cartera de la orden.',
            ]);
        }

        foreach ($lines as $line) {
            $line->update(['payment_order_id' => $paymentOrder->id]);
        }
    }
}
