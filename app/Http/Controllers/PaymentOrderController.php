<?php

namespace App\Http\Controllers;

use App\Models\CollectionReceiptPayment;
use App\Models\JournalEntry;
use App\Models\PaymentOrder;
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

        $query = PaymentOrder::with(['supplier', 'creator', 'approver', 'portfolioEcheqPayments'])
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

        return view('payment-orders.create', compact('suppliers', 'selectedSupplier', 'pendingInvoices', 'portfolioEcheqsJson'));
    }

    public function store(Request $request)
    {
        $this->authorize('payment-orders.create');

        $validated = $this->validatePaymentOrderRequest($request);

        DB::beginTransaction();
        try {
            $paymentOrder = PaymentOrder::create([
                'number' => PaymentOrder::generateNumber(),
                'company_id' => active_company_id(),
                'supplier_id' => $validated['supplier_id'],
                'date' => $validated['date'],
                'payment_method' => $validated['payment_mode'] === 'portfolio_echeq' ? null : ($validated['payment_method'] ?? null),
                'payment_reference' => $validated['payment_mode'] === 'portfolio_echeq' ? null : ($validated['payment_reference'] ?? null),
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

            if ($validated['payment_mode'] === 'portfolio_echeq') {
                $this->attachPortfolioEcheqs($paymentOrder, $validated['portfolio_echeq_ids']);
                $paymentOrder->update([
                    'payment_method' => 'cheque',
                    'payment_reference' => 'E-cheq cartera',
                ]);
            }

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

        $paymentOrder->load(['supplier', 'creator', 'approver', 'items.invoice.supplier', 'portfolioEcheqPayments.collectionReceipt']);

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

        $paymentOrder->load(['items.invoice', 'portfolioEcheqPayments']);
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
        $initialPortfolioIds = $linked->pluck('id')->all();

        return view('payment-orders.edit', compact(
            'paymentOrder',
            'suppliers',
            'pendingInvoices',
            'portfolioEcheqsJson',
            'initialPortfolioIds'
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

        $validated = $this->validatePaymentOrderRequest($request);

        DB::beginTransaction();
        try {
            $paymentOrder->update([
                'supplier_id' => $validated['supplier_id'],
                'date' => $validated['date'],
                'payment_method' => $validated['payment_mode'] === 'portfolio_echeq' ? null : ($validated['payment_method'] ?? null),
                'payment_reference' => $validated['payment_mode'] === 'portfolio_echeq' ? null : ($validated['payment_reference'] ?? null),
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

            if ($validated['payment_mode'] === 'portfolio_echeq') {
                $this->attachPortfolioEcheqs($paymentOrder, $validated['portfolio_echeq_ids']);
                $paymentOrder->update([
                    'payment_method' => 'cheque',
                    'payment_reference' => 'E-cheq cartera',
                ]);
            } else {
                $this->releasePortfolioEcheqs($paymentOrder);
            }

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

        $paymentOrder->load(['items.invoice', 'portfolioEcheqPayments']);
        $usePortfolio = $paymentOrder->portfolioEcheqPayments->isNotEmpty();

        if ($usePortfolio) {
            $sum = round((float) $paymentOrder->portfolioEcheqPayments->sum('amount'), 2);
            $total = round((float) $paymentOrder->total, 2);
            if (abs($sum - $total) > 0.01) {
                return back()->with('error', 'Los e-cheqs seleccionados no coinciden con el total de la orden. Editá el borrador.');
            }
            foreach ($paymentOrder->portfolioEcheqPayments as $line) {
                if ((int) $line->payment_order_id !== (int) $paymentOrder->id) {
                    return back()->with('error', 'Hay líneas de e-cheq inconsistentes con esta orden.');
                }
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
                    $paymentOrder->load(['supplier', 'portfolioEcheqPayments'])
                );
            }
        } catch (\Throwable $e) {
            Log::error('Error generando asiento para OP #'.$paymentOrder->id.': '.$e->getMessage());
        }

        return back()->with('success', 'Orden de pago '.$paymentOrder->number.' confirmada y pagada.');
    }

    private function validatePaymentOrderRequest(Request $request): array
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'payment_mode' => 'required|in:legacy,portfolio_echeq',
            'notes' => 'nullable|string',
            'invoices' => 'required|array|min:1',
            'invoices.*.purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'invoices.*.amount' => 'required|numeric|min:0.01',
        ], [
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'date.required' => 'La fecha es obligatoria.',
            'invoices.required' => 'Debe agregar al menos una factura.',
            'invoices.min' => 'Debe agregar al menos una factura.',
            'invoices.*.purchase_invoice_id.required' => 'Debe seleccionar una factura.',
            'invoices.*.amount.required' => 'El monto es obligatorio.',
            'invoices.*.amount.min' => 'El monto debe ser mayor a 0.',
        ]);

        if ($validated['payment_mode'] === 'legacy') {
            $extra = $request->validate([
                'payment_method' => 'nullable|in:transferencia,cheque,efectivo',
                'payment_reference' => 'nullable|string|max:255',
            ]);
            $validated['portfolio_echeq_ids'] = [];
        } else {
            $extra = $request->validate([
                'portfolio_echeq_ids' => 'required|array|min:1',
                'portfolio_echeq_ids.*' => 'integer|exists:collection_receipt_payments,id',
            ], [
                'portfolio_echeq_ids.required' => 'Seleccioná al menos un e-cheq en cartera.',
                'portfolio_echeq_ids.min' => 'Seleccioná al menos un e-cheq en cartera.',
            ]);
            $validated['portfolio_echeq_ids'] = $extra['portfolio_echeq_ids'];
            $validated['payment_method'] = null;
            $validated['payment_reference'] = null;
        }

        if ($validated['payment_mode'] === 'legacy') {
            $validated['payment_method'] = $extra['payment_method'] ?? null;
            $validated['payment_reference'] = $extra['payment_reference'] ?? null;
        }

        return $validated;
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
        $this->releasePortfolioEcheqs($paymentOrder);

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $lines = CollectionReceiptPayment::query()
            ->whereIn('id', $ids)
            ->where('line_type', 'echeq')
            ->with('collectionReceipt')
            ->get();

        if ($lines->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'portfolio_echeq_ids' => 'Uno o más e-cheqs no son válidos.',
            ]);
        }

        foreach ($lines as $line) {
            $rc = $line->collectionReceipt;
            if (! $rc || (int) $rc->company_id !== $companyId || $rc->status !== 'confirmado') {
                throw ValidationException::withMessages([
                    'portfolio_echeq_ids' => 'Solo se pueden usar e-cheq de recibos confirmados de la empresa activa.',
                ]);
            }
            if ($line->payment_order_id !== null) {
                throw ValidationException::withMessages([
                    'portfolio_echeq_ids' => 'Uno de los e-cheqs ya está reservado en otra orden de pago.',
                ]);
            }
        }

        $sum = round((float) $lines->sum('amount'), 2);
        $total = round((float) $paymentOrder->total, 2);
        if (abs($sum - $total) > 0.01) {
            throw ValidationException::withMessages([
                'portfolio_echeq_ids' => 'La suma de los e-cheqs ($'.number_format($sum, 2, ',', '.').') debe igualar el total de la orden ($'.number_format($total, 2, ',', '.').').',
            ]);
        }

        foreach ($lines as $line) {
            $line->update(['payment_order_id' => $paymentOrder->id]);
        }
    }
}
