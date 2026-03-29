<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('purchase-invoices.index');

        $query = PurchaseInvoice::with(['supplier', 'creator'])
            ->where('company_id', active_company_id())
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $invoices = $query->paginate(15)->withQueryString();

        $total_balance = PurchaseInvoice::where('company_id', active_company_id())
            ->whereIn('status', ['pendiente', 'parcialmente_pagada'])
            ->sum('balance');

        return view('purchase-invoices.index', compact('invoices', 'total_balance'));
    }

    public function create(Request $request)
    {
        $this->authorize('purchase-invoices.create');

        $suppliers = Supplier::active()->orderBy('name')->get();

        $deliveryNote = null;
        $purchaseOrder = null;

        if ($request->filled('delivery_note_id')) {
            $deliveryNote = DeliveryNote::where('company_id', active_company_id())
                ->with('items.supply')
                ->findOrFail($request->delivery_note_id);
        }

        if ($request->filled('purchase_order_id')) {
            $purchaseOrder = PurchaseOrder::where('company_id', active_company_id())
                ->findOrFail($request->purchase_order_id);
        }

        return view('purchase-invoices.create', [
            'suppliers' => $suppliers,
            'deliveryNote' => $deliveryNote,
            'purchaseOrder' => $purchaseOrder,
            'selectedSupplierId' => $request->supplier_id,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('purchase-invoices.create');

        $validated = $request->validate([
            'invoice_number' => 'required|string',
            'voucher_type' => 'required|in:A,B,C',
            'point_of_sale' => 'nullable|string',
            'supplier_id' => 'required|exists:suppliers,id',
            'delivery_note_id' => 'nullable|exists:delivery_notes,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'percepciones' => 'nullable|numeric|min:0',
            'otros_impuestos' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'cae' => 'nullable|string|max:20',
            'cuit_emisor' => 'nullable|string|max:13',
            'qr_data' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.supply_id' => 'nullable|exists:supplies,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.iva_rate' => 'required|in:0,10.5,21,27',
            'items.*.lot_number' => 'nullable|string|max:50',
            'items.*.expiration_date' => 'nullable|date',
        ], [
            'invoice_number.required' => 'El número de factura es obligatorio.',
            'voucher_type.required' => 'El tipo de comprobante es obligatorio.',
            'voucher_type.in' => 'El tipo de comprobante debe ser A, B o C.',
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'supplier_id.exists' => 'El proveedor seleccionado no es válido.',
            'issue_date.required' => 'La fecha de emisión es obligatoria.',
            'issue_date.date' => 'La fecha de emisión no es válida.',
            'due_date.date' => 'La fecha de vencimiento no es válida.',
            'percepciones.numeric' => 'Las percepciones deben ser un valor numérico.',
            'percepciones.min' => 'Las percepciones no pueden ser negativas.',
            'otros_impuestos.numeric' => 'Otros impuestos debe ser un valor numérico.',
            'otros_impuestos.min' => 'Otros impuestos no puede ser negativo.',
            'items.required' => 'Debe agregar al menos un ítem.',
            'items.min' => 'Debe agregar al menos un ítem.',
            'items.*.description.required' => 'La descripción del ítem es obligatoria.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.unit_price.required' => 'El precio unitario es obligatorio.',
            'items.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
            'items.*.iva_rate.required' => 'La alícuota de IVA es obligatoria.',
            'items.*.iva_rate.in' => 'La alícuota de IVA no es válida.',
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => $validated['invoice_number'],
            'company_id' => active_company_id(),
            'voucher_type' => $validated['voucher_type'],
            'point_of_sale' => $validated['point_of_sale'] ?? null,
            'supplier_id' => $validated['supplier_id'],
            'delivery_note_id' => $validated['delivery_note_id'] ?? null,
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'] ?? null,
            'percepciones' => $validated['percepciones'] ?? 0,
            'otros_impuestos' => $validated['otros_impuestos'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'cae' => $validated['cae'] ?? null,
            'cuit_emisor' => $validated['cuit_emisor'] ?? null,
            'qr_data' => isset($validated['qr_data']) ? json_decode($validated['qr_data'], true) : null,
            'status' => 'pendiente',
            'amount_paid' => 0,
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['items'] as $itemData) {
            $ivaAmount = round($itemData['quantity'] * $itemData['unit_price'] * $itemData['iva_rate'] / 100, 2);
            $total = $itemData['quantity'] * $itemData['unit_price'] + $ivaAmount;

            $invoice->items()->create([
                'description' => $itemData['description'],
                'supply_id' => $itemData['supply_id'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'iva_rate' => $itemData['iva_rate'],
                'iva_amount' => $ivaAmount,
                'total' => $total,
                'lot_number' => $itemData['lot_number'] ?? null,
                'expiration_date' => $itemData['expiration_date'] ?? null,
            ]);
        }

        $invoice->recalculate();

        if (! $invoice->delivery_note_id) {
            foreach ($invoice->items()->whereNotNull('supply_id')->get() as $item) {
                $supply = $item->supply;
                if (! $supply) {
                    continue;
                }

                $previousStock = $supply->stock;
                $newStock = $previousStock + $item->quantity;

                StockMovement::create([
                    'supply_id' => $supply->id,
                    'type' => 'entrada',
                    'quantity' => $item->quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reason' => 'compra',
                    'reference_type' => PurchaseInvoice::class,
                    'reference_id' => $invoice->id,
                    'lot_number' => $item->lot_number,
                    'expiration_date' => $item->expiration_date,
                    'notes' => "Factura de compra {$invoice->full_number}",
                    'user_id' => auth()->id(),
                ]);

                $supply->update([
                    'stock' => $newStock,
                    'last_price' => $item->unit_price,
                ]);
            }
        }

        return redirect()->route('purchase-invoices.show', $invoice)
            ->with('success', 'Factura '.$invoice->full_number.' creada correctamente.');
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        abort_if($purchaseInvoice->company_id !== active_company_id(), 403);

        $this->authorize('purchase-invoices.index');

        $purchaseInvoice->load([
            'supplier', 'deliveryNote', 'purchaseOrder', 'creator',
            'items.supply', 'paymentOrderItems.paymentOrder',
        ]);

        return view('purchase-invoices.show', ['invoice' => $purchaseInvoice]);
    }

    public function edit(PurchaseInvoice $purchaseInvoice)
    {
        abort_if($purchaseInvoice->company_id !== active_company_id(), 403);

        $this->authorize('purchase-invoices.edit');

        if ($purchaseInvoice->status !== 'pendiente') {
            return redirect()->route('purchase-invoices.show', $purchaseInvoice)
                ->with('error', 'Solo se pueden editar facturas en estado pendiente.');
        }

        $purchaseInvoice->load('items.supply');
        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('purchase-invoices.edit', [
            'invoice' => $purchaseInvoice,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        abort_if($purchaseInvoice->company_id !== active_company_id(), 403);

        $this->authorize('purchase-invoices.edit');

        if ($purchaseInvoice->status !== 'pendiente') {
            return redirect()->route('purchase-invoices.show', $purchaseInvoice)
                ->with('error', 'Solo se pueden editar facturas en estado pendiente.');
        }

        $validated = $request->validate([
            'invoice_number' => 'required|string',
            'voucher_type' => 'required|in:A,B,C',
            'point_of_sale' => 'nullable|string',
            'supplier_id' => 'required|exists:suppliers,id',
            'delivery_note_id' => 'nullable|exists:delivery_notes,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'percepciones' => 'nullable|numeric|min:0',
            'otros_impuestos' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'cae' => 'nullable|string|max:20',
            'cuit_emisor' => 'nullable|string|max:13',
            'qr_data' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.supply_id' => 'nullable|exists:supplies,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.iva_rate' => 'required|in:0,10.5,21,27',
            'items.*.lot_number' => 'nullable|string|max:50',
            'items.*.expiration_date' => 'nullable|date',
        ], [
            'invoice_number.required' => 'El número de factura es obligatorio.',
            'voucher_type.required' => 'El tipo de comprobante es obligatorio.',
            'voucher_type.in' => 'El tipo de comprobante debe ser A, B o C.',
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'supplier_id.exists' => 'El proveedor seleccionado no es válido.',
            'issue_date.required' => 'La fecha de emisión es obligatoria.',
            'issue_date.date' => 'La fecha de emisión no es válida.',
            'due_date.date' => 'La fecha de vencimiento no es válida.',
            'percepciones.numeric' => 'Las percepciones deben ser un valor numérico.',
            'percepciones.min' => 'Las percepciones no pueden ser negativas.',
            'otros_impuestos.numeric' => 'Otros impuestos debe ser un valor numérico.',
            'otros_impuestos.min' => 'Otros impuestos no puede ser negativo.',
            'items.required' => 'Debe agregar al menos un ítem.',
            'items.min' => 'Debe agregar al menos un ítem.',
            'items.*.description.required' => 'La descripción del ítem es obligatoria.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.unit_price.required' => 'El precio unitario es obligatorio.',
            'items.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
            'items.*.iva_rate.required' => 'La alícuota de IVA es obligatoria.',
            'items.*.iva_rate.in' => 'La alícuota de IVA no es válida.',
        ]);

        $purchaseInvoice->update([
            'invoice_number' => $validated['invoice_number'],
            'voucher_type' => $validated['voucher_type'],
            'point_of_sale' => $validated['point_of_sale'] ?? null,
            'supplier_id' => $validated['supplier_id'],
            'delivery_note_id' => $validated['delivery_note_id'] ?? null,
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'] ?? null,
            'percepciones' => $validated['percepciones'] ?? 0,
            'otros_impuestos' => $validated['otros_impuestos'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'cae' => $validated['cae'] ?? null,
            'cuit_emisor' => $validated['cuit_emisor'] ?? null,
            'qr_data' => isset($validated['qr_data']) ? json_decode($validated['qr_data'], true) : null,
        ]);

        $purchaseInvoice->items()->delete();

        foreach ($validated['items'] as $itemData) {
            $ivaAmount = round($itemData['quantity'] * $itemData['unit_price'] * $itemData['iva_rate'] / 100, 2);
            $total = $itemData['quantity'] * $itemData['unit_price'] + $ivaAmount;

            $purchaseInvoice->items()->create([
                'description' => $itemData['description'],
                'supply_id' => $itemData['supply_id'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'iva_rate' => $itemData['iva_rate'],
                'iva_amount' => $ivaAmount,
                'total' => $total,
                'lot_number' => $itemData['lot_number'] ?? null,
                'expiration_date' => $itemData['expiration_date'] ?? null,
            ]);
        }

        $purchaseInvoice->recalculate();

        return redirect()->route('purchase-invoices.show', $purchaseInvoice)
            ->with('success', 'Factura actualizada correctamente.');
    }

    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        abort_if($purchaseInvoice->company_id !== active_company_id(), 403);

        $this->authorize('purchase-invoices.delete');

        if ($purchaseInvoice->status !== 'pendiente' || $purchaseInvoice->amount_paid > 0) {
            return redirect()->route('purchase-invoices.show', $purchaseInvoice)
                ->with('error', 'Solo se pueden eliminar facturas pendientes sin pagos registrados.');
        }

        $fullNumber = $purchaseInvoice->full_number;

        if (! $purchaseInvoice->delivery_note_id) {
            $movements = StockMovement::where('reference_type', PurchaseInvoice::class)
                ->where('reference_id', $purchaseInvoice->id)
                ->get();

            foreach ($movements as $movement) {
                $supply = $movement->supply;
                if ($supply) {
                    $supply->decrement('stock', $movement->quantity);
                }
                $movement->delete();
            }
        }

        $purchaseInvoice->delete();

        return redirect()->route('purchase-invoices.index')
            ->with('success', "Factura {$fullNumber} eliminada.");
    }
}
