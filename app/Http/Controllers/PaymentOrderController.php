<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PaymentOrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('payment-orders.index');

        $query = PaymentOrder::with(['supplier', 'creator', 'approver'])
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

        return view('payment-orders.create', compact('suppliers', 'selectedSupplier', 'pendingInvoices'));
    }

    public function store(Request $request)
    {
        $this->authorize('payment-orders.create');

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'payment_method' => 'nullable|in:transferencia,cheque,efectivo',
            'payment_reference' => 'nullable|string|max:255',
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

        $paymentOrder = PaymentOrder::create([
            'number' => PaymentOrder::generateNumber(),
            'company_id' => active_company_id(),
            'supplier_id' => $validated['supplier_id'],
            'date' => $validated['date'],
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_reference' => $validated['payment_reference'] ?? null,
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

        return redirect()->route('payment-orders.show', $paymentOrder)
            ->with('success', 'Orden de pago '.$paymentOrder->number.' creada correctamente.');
    }

    public function show(PaymentOrder $paymentOrder)
    {
        abort_if($paymentOrder->company_id !== active_company_id(), 403);

        $this->authorize('payment-orders.index');

        $paymentOrder->load(['supplier', 'creator', 'approver', 'items.invoice.supplier']);

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

        $paymentOrder->load(['items.invoice']);
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

        return view('payment-orders.edit', compact('paymentOrder', 'suppliers', 'pendingInvoices'));
    }

    public function update(Request $request, PaymentOrder $paymentOrder)
    {
        abort_if($paymentOrder->company_id !== active_company_id(), 403);

        $this->authorize('payment-orders.edit');

        if ($paymentOrder->status !== 'borrador') {
            return redirect()->route('payment-orders.show', $paymentOrder)
                ->with('error', 'Solo se pueden editar órdenes de pago en estado borrador.');
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'payment_method' => 'nullable|in:transferencia,cheque,efectivo',
            'payment_reference' => 'nullable|string|max:255',
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

        $paymentOrder->update([
            'supplier_id' => $validated['supplier_id'],
            'date' => $validated['date'],
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_reference' => $validated['payment_reference'] ?? null,
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

        foreach ($paymentOrder->items as $item) {
            $invoice = $item->invoice;
            $invoice->amount_paid += $item->amount;
            $invoice->balance = $invoice->total - $invoice->amount_paid;
            $invoice->updatePaymentStatus();
        }

        $paymentOrder->status = 'pagada';
        $paymentOrder->approved_by = auth()->id();
        $paymentOrder->save();

        return back()->with('success', 'Orden de pago '.$paymentOrder->number.' confirmada y pagada.');
    }
}
