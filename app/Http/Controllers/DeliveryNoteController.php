<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\DeliveryNoteStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryNoteController extends Controller
{
    public function bySupplier(Request $request)
    {
        $request->validate(['supplier_id' => 'required|integer']);

        $notes = DeliveryNote::where('company_id', active_company_id())
            ->where('supplier_id', $request->supplier_id)
            ->orderByDesc('date')
            ->limit(50)
            ->get(['id', 'remito_number', 'date', 'supplier_id']);

        return response()->json($notes);
    }

    public function getItems(DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->company_id !== active_company_id(), 403);

        $items = $deliveryNote->items()->with('supply:id,code,name,brand,tracks_lot')->get();

        return response()->json([
            'delivery_note' => [
                'id' => $deliveryNote->id,
                'number' => $deliveryNote->remito_number,
                'date' => $deliveryNote->date?->format('Y-m-d'),
            ],
            'items' => $items->map(fn ($item) => [
                'supply_id' => $item->supply_id,
                '_supply_name' => $item->supply?->name ?? '',
                '_supply_code' => $item->supply?->code ?? '',
                '_supply_brand' => $item->supply?->brand ?? '',
                '_supply_label' => $item->supply ? ($item->supply->brand ? "{$item->supply->name} — {$item->supply->brand}" : $item->supply->name) : '',
                '_tracks_lot' => $item->supply?->tracks_lot ?? false,
                'quantity' => (int) $item->quantity_received,
                'lot_number' => $item->lot_number ?? '',
                'expiration_date' => $item->expiration_date?->format('Y-m-d') ?? '',
                'unit_price' => 0,
                'iva_rate' => '21',
                'updates_stock' => false,
                'description' => $item->supply ? ($item->supply->brand ? "{$item->supply->name} — {$item->supply->brand}" : $item->supply->name) : '',
            ]),
        ]);
    }

    public function index(Request $request)
    {
        $this->authorize('delivery-notes.index');

        $query = DeliveryNote::with(['supplier', 'purchaseOrder', 'receiver', 'purchaseInvoices:id,delivery_note_id'])
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
            'items.*.quantity_received' => 'required|integer|min:1',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.notes' => 'nullable|string|max:255',
            'items.*.lot_number' => 'nullable|string|max:100',
            'items.*.expiration_date' => 'nullable|date',
        ], [
            'remito_number.required' => 'El número de remito es obligatorio.',
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'date.required' => 'La fecha es obligatoria.',
            'items.required' => 'Debe agregar al menos un ítem.',
            'items.min' => 'Debe agregar al menos un ítem.',
            'items.*.supply_id.required' => 'Debe seleccionar un insumo.',
            'items.*.quantity_received.required' => 'La cantidad recibida es obligatoria.',
            'items.*.quantity_received.min' => 'La cantidad recibida debe ser al menos 1.',
        ]);

        $duplicate = DeliveryNote::where('company_id', active_company_id())
            ->where('remito_number', $validated['remito_number'])
            ->where('supplier_id', $validated['supplier_id'])
            ->exists();

        if ($duplicate) {
            return back()->withInput()->withErrors([
                'remito_number' => 'Ya existe un remito con este número para el mismo proveedor.',
            ]);
        }

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
                'lot_number' => $itemData['lot_number'] ?? null,
                'expiration_date' => $itemData['expiration_date'] ?? null,
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

        if ($deliveryNote->hasPurchaseInvoice()) {
            return redirect()->route('delivery-notes.show', $deliveryNote)
                ->with('warning', 'Este remito tiene una factura de compra asociada y no puede editarse.');
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

        if ($deliveryNote->hasPurchaseInvoice()) {
            abort(403, 'Remito con factura asociada no puede editarse.');
        }

        $validated = $request->validate([
            'remito_number' => 'required|string|max:100',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity_received' => 'required|integer|min:1',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.notes' => 'nullable|string|max:255',
            'items.*.lot_number' => 'nullable|string|max:100',
            'items.*.expiration_date' => 'nullable|date',
        ], [
            'remito_number.required' => 'El número de remito es obligatorio.',
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'date.required' => 'La fecha es obligatoria.',
            'items.required' => 'Debe agregar al menos un ítem.',
            'items.min' => 'Debe agregar al menos un ítem.',
            'items.*.supply_id.required' => 'Debe seleccionar un insumo.',
            'items.*.quantity_received.required' => 'La cantidad recibida es obligatoria.',
            'items.*.quantity_received.min' => 'La cantidad recibida debe ser al menos 1.',
        ]);

        $wasAccepted = $deliveryNote->status === 'aceptado';

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
                'lot_number' => $itemData['lot_number'] ?? null,
                'expiration_date' => $itemData['expiration_date'] ?? null,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        if ($wasAccepted) {
            app(DeliveryNoteStockService::class)->syncStockAfterUpdate($deliveryNote);
        }

        return redirect()->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'Remito actualizado y stock sincronizado correctamente.');
    }

    public function destroy(DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->company_id !== active_company_id(), 403);

        $this->authorize('delivery-notes.delete');

        if ($deliveryNote->hasPurchaseInvoice()) {
            return back()->with('error',
                'No se puede eliminar el remito porque tiene una factura de compra asociada.');
        }

        $number = $deliveryNote->remito_number;
        $wasAccepted = $deliveryNote->status === 'aceptado';

        DB::transaction(function () use ($deliveryNote, $wasAccepted) {
            if ($wasAccepted) {
                app(DeliveryNoteStockService::class)->reverseStockForDeletion($deliveryNote);
            }

            $deliveryNote->items()->delete();
            $deliveryNote->delete();
        });

        return redirect()->route('delivery-notes.index')
            ->with('success', "Remito {$number} eliminado".($wasAccepted ? ' y stock revertido' : '').' correctamente.');
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
                    $lotNumber = $request->input("items.{$item->id}.lot_number", $item->lot_number);
                    $expirationDate = $request->input("items.{$item->id}.expiration_date", $item->expiration_date?->format('Y-m-d'));
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
