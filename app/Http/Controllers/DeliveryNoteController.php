<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryNoteController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('delivery-notes.index');

        $query = DeliveryNote::with(['supplier', 'purchaseOrder', 'receiver'])
            ->where('company_id', active_company_id())
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('remito_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $deliveryNotes = $query->paginate(15)->withQueryString();

        return view('delivery-notes.index', compact('deliveryNotes'));
    }

    public function create(Request $request)
    {
        $this->authorize('delivery-notes.create');

        $suppliers = Supplier::active()->orderBy('name')->get();

        $purchaseOrder = null;
        if ($request->filled('purchase_order_id')) {
            $purchaseOrder = PurchaseOrder::with(['items.supply', 'supplier'])
                ->where('company_id', active_company_id())
                ->find($request->purchase_order_id);

            if ($purchaseOrder) {
                $purchaseOrder->setRelation(
                    'items',
                    $purchaseOrder->items->filter(fn ($item) => $item->pending_quantity > 0)->values()
                );
            }
        }

        $purchaseOrders = PurchaseOrder::whereIn('status', ['aprobada', 'parcial'])
            ->where('company_id', active_company_id())
            ->whereHas('items', fn ($q) => $q->whereRaw('quantity > received_quantity'))
            ->with(['supplier', 'items.supply'])
            ->orderByDesc('date')
            ->get();

        return view('delivery-notes.create', compact('suppliers', 'purchaseOrder', 'purchaseOrders'));
    }

    public function store(Request $request)
    {
        $this->authorize('delivery-notes.create');

        $validated = $request->validate([
            'remito_number' => 'required|string|max:100',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity_received' => 'required|numeric|min:0.01',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'remito_number.required' => 'El número de remito es obligatorio.',
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'date.required' => 'La fecha es obligatoria.',
            'items.required' => 'Debe agregar al menos un ítem.',
            'items.min' => 'Debe agregar al menos un ítem.',
            'items.*.supply_id.required' => 'Debe seleccionar un insumo.',
            'items.*.quantity_received.required' => 'La cantidad recibida es obligatoria.',
            'items.*.quantity_received.min' => 'La cantidad recibida debe ser mayor a 0.',
        ]);

        $deliveryNote = DeliveryNote::create([
            'remito_number' => $validated['remito_number'],
            'company_id' => active_company_id(),
            'supplier_id' => $validated['supplier_id'],
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pendiente',
            'received_by' => auth()->id(),
        ]);

        foreach ($validated['items'] as $itemData) {
            $deliveryNote->items()->create([
                'supply_id' => $itemData['supply_id'],
                'quantity_received' => $itemData['quantity_received'],
                'purchase_order_item_id' => $itemData['purchase_order_item_id'] ?? null,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        return redirect()->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'Remito '.$deliveryNote->remito_number.' creado correctamente.');
    }

    public function show(DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->company_id !== active_company_id(), 403);

        $this->authorize('delivery-notes.index');

        $deliveryNote->load(['supplier', 'purchaseOrder', 'receiver', 'items.supply', 'items.purchaseOrderItem']);

        return view('delivery-notes.show', ['deliveryNote' => $deliveryNote]);
    }

    public function edit(DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->company_id !== active_company_id(), 403);

        $this->authorize('delivery-notes.edit');

        if ($deliveryNote->status !== 'pendiente') {
            return redirect()->route('delivery-notes.show', $deliveryNote)
                ->with('error', 'Solo se pueden editar remitos en estado pendiente.');
        }

        $deliveryNote->load(['items.supply', 'items.purchaseOrderItem']);
        $suppliers = Supplier::active()->orderBy('name')->get();

        $purchaseOrder = null;
        if ($deliveryNote->purchase_order_id) {
            $purchaseOrder = PurchaseOrder::with(['items.supply', 'supplier'])
                ->where('company_id', active_company_id())
                ->find($deliveryNote->purchase_order_id);
        }

        $purchaseOrders = PurchaseOrder::whereIn('status', ['aprobada', 'parcial'])
            ->where('company_id', active_company_id())
            ->whereHas('items', fn ($q) => $q->whereRaw('quantity > received_quantity'))
            ->with(['supplier', 'items.supply'])
            ->orderByDesc('date')
            ->get();

        return view('delivery-notes.edit', [
            'deliveryNote' => $deliveryNote,
            'suppliers' => $suppliers,
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function update(Request $request, DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->company_id !== active_company_id(), 403);

        $this->authorize('delivery-notes.edit');

        if ($deliveryNote->status !== 'pendiente') {
            return redirect()->route('delivery-notes.show', $deliveryNote)
                ->with('error', 'Solo se pueden editar remitos en estado pendiente.');
        }

        $validated = $request->validate([
            'remito_number' => 'required|string|max:100',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity_received' => 'required|numeric|min:0.01',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'remito_number.required' => 'El número de remito es obligatorio.',
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'date.required' => 'La fecha es obligatoria.',
            'items.required' => 'Debe agregar al menos un ítem.',
            'items.min' => 'Debe agregar al menos un ítem.',
            'items.*.supply_id.required' => 'Debe seleccionar un insumo.',
            'items.*.quantity_received.required' => 'La cantidad recibida es obligatoria.',
            'items.*.quantity_received.min' => 'La cantidad recibida debe ser mayor a 0.',
        ]);

        $deliveryNote->update([
            'remito_number' => $validated['remito_number'],
            'supplier_id' => $validated['supplier_id'],
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $deliveryNote->items()->delete();

        foreach ($validated['items'] as $itemData) {
            $deliveryNote->items()->create([
                'supply_id' => $itemData['supply_id'],
                'quantity_received' => $itemData['quantity_received'],
                'purchase_order_item_id' => $itemData['purchase_order_item_id'] ?? null,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        return redirect()->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'Remito actualizado correctamente.');
    }

    public function destroy(DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->company_id !== active_company_id(), 403);

        $this->authorize('delivery-notes.delete');

        if ($deliveryNote->status !== 'pendiente') {
            return redirect()->route('delivery-notes.show', $deliveryNote)
                ->with('error', 'Solo se pueden eliminar remitos en estado pendiente.');
        }

        $number = $deliveryNote->remito_number;
        $deliveryNote->delete();

        return redirect()->route('delivery-notes.index')
            ->with('success', "Remito {$number} eliminado.");
    }

    public function accept(Request $request, DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->company_id !== active_company_id(), 403);

        $this->authorize('delivery-notes.edit');

        if ($deliveryNote->status !== 'pendiente') {
            return back()->with('error', 'Solo se pueden aceptar remitos en estado pendiente.');
        }

        $deliveryNote->load(['items.supply', 'items.purchaseOrderItem']);

        $rules = [];
        $messages = [];
        foreach ($deliveryNote->items as $item) {
            if ($item->supply->tracks_lot) {
                $rules["items.{$item->id}.lot_number"] = 'required|string|max:100';
                $rules["items.{$item->id}.expiration_date"] = 'required|date';
                $messages["items.{$item->id}.lot_number.required"] = "El número de lote es obligatorio para {$item->supply->name}.";
                $messages["items.{$item->id}.expiration_date.required"] = "La fecha de vencimiento es obligatoria para {$item->supply->name}.";
            }
        }

        if (! empty($rules)) {
            $request->validate($rules, $messages);
        }

        DB::beginTransaction();

        try {
            foreach ($deliveryNote->items as $item) {
                $supply = $item->supply;
                $previousStock = (float) $supply->stock;
                $newStock = $previousStock + (float) $item->quantity_received;

                $lotNumber = null;
                $expirationDate = null;
                if ($supply->tracks_lot) {
                    $lotNumber = $request->input("items.{$item->id}.lot_number");
                    $expirationDate = $request->input("items.{$item->id}.expiration_date");
                }

                StockMovement::create([
                    'supply_id' => $supply->id,
                    'type' => 'entrada',
                    'quantity' => $item->quantity_received,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reason' => 'compra',
                    'lot_number' => $lotNumber,
                    'expiration_date' => $expirationDate,
                    'reference_type' => DeliveryNote::class,
                    'reference_id' => $deliveryNote->id,
                    'user_id' => auth()->id(),
                ]);

                $updateData = ['stock' => $newStock];
                if ($item->purchaseOrderItem && $item->purchaseOrderItem->unit_price) {
                    $updateData['last_price'] = $item->purchaseOrderItem->unit_price;
                }
                $supply->update($updateData);

                if ($item->purchase_order_item_id && $item->purchaseOrderItem) {
                    $item->purchaseOrderItem->increment('received_quantity', $item->quantity_received);
                }
            }

            if ($deliveryNote->purchase_order_id) {
                $purchaseOrder = PurchaseOrder::with('items')
                    ->where('company_id', active_company_id())
                    ->find($deliveryNote->purchase_order_id);
                if ($purchaseOrder) {
                    $allReceived = $purchaseOrder->items->every(
                        fn ($poItem) => $poItem->received_quantity >= $poItem->quantity
                    );
                    $purchaseOrder->update([
                        'status' => $allReceived ? 'recibida' : 'parcial',
                    ]);
                }
            }

            $deliveryNote->update(['status' => 'aceptado']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al aceptar el remito: '.$e->getMessage());
        }

        return redirect()->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'Remito aceptado y stock actualizado correctamente.');
    }
}
