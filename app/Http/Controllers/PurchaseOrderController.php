<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseQuotationRequest;
use App\Models\Supplier;
use App\Models\Supply;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('purchase-orders.index');

        $query = PurchaseOrder::with(['supplier', 'creator', 'items'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('purchase-orders.index', compact('orders'));
    }

    public function create(Request $request)
    {
        $this->authorize('purchase-orders.create');

        $suppliers = Supplier::active()->orderBy('name')->get();
        $supplies = Supply::active()->orderBy('name')->get();
        $quotation = null;

        if ($request->filled('from_quotation')) {
            $quotation = PurchaseQuotationRequest::with('items.supply')
                ->find($request->from_quotation);
        }

        return view('purchase-orders.create', compact('suppliers', 'supplies', 'quotation'));
    }

    public function store(Request $request)
    {
        $this->authorize('purchase-orders.create');

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'tax_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'quotation_request_id' => 'nullable|exists:purchase_quotation_requests,id',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'date.required' => 'La fecha es obligatoria.',
            'tax_rate.required' => 'La alícuota de IVA es obligatoria.',
            'tax_rate.numeric' => 'La alícuota de IVA debe ser un valor numérico.',
            'tax_rate.min' => 'La alícuota de IVA no puede ser negativa.',
            'items.required' => 'Debe agregar al menos un ítem.',
            'items.min' => 'Debe agregar al menos un ítem.',
            'items.*.supply_id.required' => 'Debe seleccionar un insumo.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.unit_price.required' => 'El precio unitario es obligatorio.',
            'items.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
        ]);

        $po = PurchaseOrder::create([
            'number' => PurchaseOrder::generateNumber(),
            'supplier_id' => $validated['supplier_id'],
            'quotation_request_id' => $validated['quotation_request_id'] ?? null,
            'date' => $validated['date'],
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'tax_rate' => $validated['tax_rate'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'borrador',
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['items'] as $index => $itemData) {
            $po->items()->create([
                'supply_id' => $itemData['supply_id'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total' => $itemData['quantity'] * $itemData['unit_price'],
                'notes' => $itemData['notes'] ?? null,
                'sort_order' => $index,
            ]);
        }

        $po->recalculate();

        return redirect()->route('purchase-orders.show', $po)
            ->with('success', 'Orden de compra ' . $po->number . ' creada correctamente.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('purchase-orders.index');

        $purchaseOrder->load(['supplier', 'creator', 'approver', 'items.supply', 'quotationRequest', 'deliveryNotes']);
        return view('purchase-orders.show', ['order' => $purchaseOrder]);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('purchase-orders.edit');

        if ($purchaseOrder->status !== 'borrador') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Solo se pueden editar órdenes en estado Borrador.');
        }

        $purchaseOrder->load(['items.supply']);
        $suppliers = Supplier::active()->orderBy('name')->get();
        $supplies = Supply::active()->orderBy('name')->get();

        return view('purchase-orders.edit', [
            'order' => $purchaseOrder,
            'suppliers' => $suppliers,
            'supplies' => $supplies,
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('purchase-orders.edit');

        if ($purchaseOrder->status !== 'borrador') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Solo se pueden editar órdenes en estado Borrador.');
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'tax_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'quotation_request_id' => 'nullable|exists:purchase_quotation_requests,id',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'date.required' => 'La fecha es obligatoria.',
            'tax_rate.required' => 'La alícuota de IVA es obligatoria.',
            'tax_rate.numeric' => 'La alícuota de IVA debe ser un valor numérico.',
            'tax_rate.min' => 'La alícuota de IVA no puede ser negativa.',
            'items.required' => 'Debe agregar al menos un ítem.',
            'items.min' => 'Debe agregar al menos un ítem.',
            'items.*.supply_id.required' => 'Debe seleccionar un insumo.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.unit_price.required' => 'El precio unitario es obligatorio.',
            'items.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
        ]);

        $purchaseOrder->update([
            'supplier_id' => $validated['supplier_id'],
            'date' => $validated['date'],
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'tax_rate' => $validated['tax_rate'],
            'notes' => $validated['notes'] ?? null,
            'quotation_request_id' => $validated['quotation_request_id'] ?? null,
        ]);

        $purchaseOrder->items()->delete();

        foreach ($validated['items'] as $index => $itemData) {
            $purchaseOrder->items()->create([
                'supply_id' => $itemData['supply_id'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total' => $itemData['quantity'] * $itemData['unit_price'],
                'notes' => $itemData['notes'] ?? null,
                'sort_order' => $index,
            ]);
        }

        $purchaseOrder->recalculate();

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Orden de compra actualizada correctamente.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('purchase-orders.delete');

        if ($purchaseOrder->status !== 'borrador') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Solo se pueden eliminar órdenes en estado Borrador.');
        }

        $number = $purchaseOrder->number;
        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')
            ->with('success', "Orden de compra {$number} eliminada.");
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('purchase-orders.edit');

        $validated = $request->validate([
            'status' => 'required|in:borrador,aprobada,parcial,recibida,cancelada',
        ]);

        if ($validated['status'] === 'aprobada') {
            $purchaseOrder->approved_by = auth()->id();
            $purchaseOrder->approved_at = now();
        }

        $purchaseOrder->status = $validated['status'];
        $purchaseOrder->save();

        return back()->with('success', 'Estado actualizado a: ' . $purchaseOrder->status_label);
    }
}
