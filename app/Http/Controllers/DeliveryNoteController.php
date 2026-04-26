<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\DeliveryNoteStockService;
use App\Services\LabBranchResolver;
use App\Services\SupplyStockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeliveryNoteController extends Controller
{
    protected function assertRemitoBranchMatchesPurchaseOrder(?int $purchaseOrderId, int $labBranchId): void
    {
        if (! $purchaseOrderId) {
            return;
        }

        $po = PurchaseOrder::where('company_id', active_company_id())->find($purchaseOrderId);
        if ($po && $po->lab_branch_id && (int) $po->lab_branch_id !== (int) $labBranchId) {
            throw ValidationException::withMessages([
                'lab_branch_id' => 'La sede debe coincidir con la de la orden de compra vinculada.',
            ]);
        }
    }

    public function checkDuplicateRemito(Request $request)
    {
        abort_unless(
            auth()->user()->can('delivery-notes.create') || auth()->user()->can('delivery-notes.edit'),
            403
        );

        $validated = $request->validate([
            'remito_number' => 'required|string|max:100',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'exclude_id' => 'nullable|integer',
        ]);

        $remito = trim($validated['remito_number']);
        if ($remito === '') {
            return response()->json(['duplicate' => false]);
        }

        $query = DeliveryNote::query()
            ->where('company_id', active_company_id())
            ->where('remito_number', $remito)
            ->where('supplier_id', $validated['supplier_id']);

        if (! empty($validated['exclude_id'])) {
            $query->where('id', '!=', (int) $validated['exclude_id']);
        }

        return response()->json(['duplicate' => $query->exists()]);
    }

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
        $userCompanyIds = auth()->user()->load('companies')->companies->pluck('id');
        abort_if(! $userCompanyIds->contains($deliveryNote->company_id), 403);

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

        $query = DeliveryNote::with(['supplier', 'purchaseOrder', 'receiver', 'labBranch'])
            ->withExists('purchaseInvoices')
            ->withExists('legacyLinkedPurchaseInvoices')
            ->where('company_id', active_company_id())
            ->orderByDesc('created_at');

        if ($request->filled('lab_branch_id')) {
            $query->where('lab_branch_id', $request->lab_branch_id);
        }

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
        $branches = LabBranchResolver::activeBranchesForForms();

        return view('delivery-notes.index', compact('deliveryNotes', 'branches'));
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

        $branches = LabBranchResolver::activeBranchesForForms();

        return view('delivery-notes.create', compact('suppliers', 'purchaseOrder', 'purchaseOrders', 'branches'));
    }

    public function store(Request $request)
    {
        $this->authorize('delivery-notes.create');

        $validated = $request->validate([
            'remito_number' => 'required|string|max:100',
            'supplier_id' => 'required|exists:suppliers,id',
            'lab_branch_id' => [
                'required',
                'integer',
                Rule::exists('lab_branches', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
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
            'lab_branch_id.required' => 'Seleccioná la sede / depósito.',
            'lab_branch_id.exists' => 'La sede no es válida o está inactiva.',
            'date.required' => 'La fecha es obligatoria.',
            'items.required' => 'Debe agregar al menos un ítem.',
            'items.min' => 'Debe agregar al menos un ítem.',
            'items.*.supply_id.required' => 'Debe seleccionar un insumo.',
            'items.*.quantity_received.required' => 'La cantidad recibida es obligatoria.',
            'items.*.quantity_received.min' => 'La cantidad recibida debe ser al menos 1.',
        ]);

        $this->assertRemitoBranchMatchesPurchaseOrder(
            isset($validated['purchase_order_id']) ? (int) $validated['purchase_order_id'] : null,
            (int) $validated['lab_branch_id']
        );

        $remitoNumber = trim($validated['remito_number']);

        $duplicate = DeliveryNote::where('company_id', active_company_id())
            ->where('remito_number', $remitoNumber)
            ->where('supplier_id', $validated['supplier_id'])
            ->exists();

        if ($duplicate) {
            return back()->withInput()->withErrors([
                'remito_number' => 'Ya existe un remito con este número para el mismo proveedor.',
            ]);
        }

        $deliveryNote = DeliveryNote::create([
            'remito_number' => $remitoNumber,
            'company_id' => active_company_id(),
            'lab_branch_id' => $validated['lab_branch_id'],
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
            ->with(
                'success',
                'Remito '.$deliveryNote->remito_number.' guardado en estado pendiente. Para actualizar el stock y generar movimientos en el historial, abrí esta pantalla y presioná «Aceptar remito» (abajo).'
            );
    }

    public function show(DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->company_id !== active_company_id(), 403);

        $this->authorize('delivery-notes.index');

        $deliveryNote->load(['supplier', 'purchaseOrder', 'receiver', 'labBranch', 'items.supply', 'items.purchaseOrderItem']);

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

        $branches = LabBranchResolver::activeBranchesForForms();

        return view('delivery-notes.edit', [
            'deliveryNote' => $deliveryNote,
            'suppliers' => $suppliers,
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrders' => $purchaseOrders,
            'branches' => $branches,
        ]);
    }

    public function update(Request $request, DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->company_id !== active_company_id(), 403);

        $this->authorize('delivery-notes.edit');

        if ($deliveryNote->hasPurchaseInvoice()) {
            abort(403, 'Remito con factura asociada no puede editarse.');
        }

        if ($request->input('stock_received_at') === '') {
            $request->merge(['stock_received_at' => null]);
        }

        $validated = $request->validate([
            'remito_number' => 'required|string|max:100',
            'supplier_id' => 'required|exists:suppliers,id',
            'lab_branch_id' => [
                'required',
                'integer',
                Rule::exists('lab_branches', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'date' => 'required|date',
            'stock_received_at' => 'nullable|date',
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
            'lab_branch_id.required' => 'Seleccioná la sede / depósito.',
            'lab_branch_id.exists' => 'La sede no es válida o está inactiva.',
            'date.required' => 'La fecha es obligatoria.',
            'items.required' => 'Debe agregar al menos un ítem.',
            'items.min' => 'Debe agregar al menos un ítem.',
            'items.*.supply_id.required' => 'Debe seleccionar un insumo.',
            'items.*.quantity_received.required' => 'La cantidad recibida es obligatoria.',
            'items.*.quantity_received.min' => 'La cantidad recibida debe ser al menos 1.',
        ]);

        $this->assertRemitoBranchMatchesPurchaseOrder(
            isset($validated['purchase_order_id']) ? (int) $validated['purchase_order_id'] : null,
            (int) $validated['lab_branch_id']
        );

        $remitoNumber = trim($validated['remito_number']);

        $duplicate = DeliveryNote::where('company_id', active_company_id())
            ->where('remito_number', $remitoNumber)
            ->where('supplier_id', $validated['supplier_id'])
            ->where('id', '!=', $deliveryNote->id)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->withErrors([
                'remito_number' => 'Ya existe un remito con este número para el mismo proveedor.',
            ]);
        }

        $wasAccepted = $deliveryNote->status === 'aceptado';

        $updatePayload = [
            'remito_number' => $remitoNumber,
            'supplier_id' => $validated['supplier_id'],
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'lab_branch_id' => $validated['lab_branch_id'],
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
        ];
        if ($wasAccepted) {
            $updatePayload['stock_received_at'] = $validated['stock_received_at'] ?? null;
        }
        $deliveryNote->update($updatePayload);

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

        foreach ($deliveryNote->items as $item) {
            if (! $item->supply) {
                return back()->with('error',
                    'El remito tiene ítems sin insumo asociado. Corregí el remito antes de aceptar.');
            }
        }

        $rules = [];
        $messages = [];
        foreach ($deliveryNote->items as $item) {
            $supply = $item->supply;
            if ($supply->tracks_lot === true) {
                $rules["items.{$item->id}.lot_number"] = 'required|string|max:100';
                $rules["items.{$item->id}.expiration_date"] = 'required|date';
                $messages["items.{$item->id}.lot_number.required"] = "El número de lote es obligatorio para {$supply->name}.";
                $messages["items.{$item->id}.expiration_date.required"] = "La fecha de vencimiento es obligatoria para {$supply->name}.";
            }
        }

        if (! empty($rules)) {
            $request->validate($rules, $messages);
        }

        if ($request->input('stock_received_at') === '') {
            $request->merge(['stock_received_at' => null]);
        }

        $request->validate([
            'stock_received_at' => 'nullable|date',
        ]);

        $receivedYmd = $request->filled('stock_received_at')
            ? $request->input('stock_received_at')
            : $deliveryNote->date->format('Y-m-d');
        $occurredAt = Carbon::parse($receivedYmd, config('app.timezone'))->setTime(12, 0, 0);

        try {
            $branch = LabBranchResolver::requireDocumentBranch($deliveryNote->lab_branch_id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        DB::beginTransaction();

        try {
            $stockSvc = app(SupplyStockService::class);

            foreach ($deliveryNote->items as $item) {
                $supply = $item->supply;

                $lotNumber = null;
                $expirationDate = null;
                if ($supply->tracks_lot === true) {
                    $lotNumber = $request->input("items.{$item->id}.lot_number", $item->lot_number);
                    $expirationDate = $request->input("items.{$item->id}.expiration_date", $item->expiration_date?->format('Y-m-d'));
                }

                $stockSvc->recordEntrada($supply, $branch->id, (float) $item->quantity_received, [
                    'reason' => 'compra',
                    'lot_number' => $lotNumber,
                    'expiration_date' => $expirationDate,
                    'reference_type' => DeliveryNote::class,
                    'reference_id' => $deliveryNote->id,
                    'user_id' => auth()->id(),
                    'occurred_at' => $occurredAt,
                ]);

                $updateData = [];
                if ($item->purchaseOrderItem && $item->purchaseOrderItem->unit_price) {
                    $updateData['last_price'] = $item->purchaseOrderItem->unit_price;
                }
                if ($updateData !== []) {
                    $supply->refresh()->update($updateData);
                }

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

            $deliveryNote->update([
                'status' => 'aceptado',
                'stock_received_at' => $receivedYmd,
            ]);

            DB::commit();
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al aceptar el remito: '.$e->getMessage());
        }

        return redirect()->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'Remito aceptado y stock actualizado correctamente.');
    }
}
