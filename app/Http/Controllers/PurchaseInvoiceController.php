<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DeliveryNote;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\AccountingEntryService;
use App\Services\LabBranchResolver;
use App\Services\SupplyStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('purchase-invoices.index');

        $query = PurchaseInvoice::with(['supplier', 'creator', 'deliveryNotes', 'labBranch'])
            ->where('company_id', active_company_id())
            ->orderByDesc('created_at');

        if ($request->filled('lab_branch_id')) {
            $query->where('lab_branch_id', $request->lab_branch_id);
        }

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

        $branches = LabBranchResolver::activeBranchesForForms();

        return view('purchase-invoices.index', compact('invoices', 'total_balance', 'branches'));
    }

    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|integer',
            'point_of_sale' => 'required|string',
            'invoice_number' => 'required|string',
            'exclude_id' => 'nullable|integer',
        ]);

        $companyId = active_company_id();
        if ($request->filled('exclude_id')) {
            $inv = PurchaseInvoice::query()->find($request->exclude_id);
            if (! $inv || ! auth()->user()->companies->contains('id', (int) $inv->company_id)) {
                abort(403);
            }
            $companyId = (int) $inv->company_id;
        }

        $exists = PurchaseInvoice::where('company_id', $companyId)
            ->where('supplier_id', $request->supplier_id)
            ->where('point_of_sale', $request->point_of_sale)
            ->where('invoice_number', $request->invoice_number)
            ->when($request->exclude_id, fn ($q) => $q->where('id', '!=', $request->exclude_id))
            ->exists();

        return response()->json(['duplicate' => $exists]);
    }

    /**
     * Remitos aceptados del proveedor que se pueden asociar a una FC (libres o ya vinculados a la misma factura en edición).
     */
    public function availableDeliveryNotes(Request $request)
    {
        if (auth()->user()->cannot('purchase-invoices.create') && auth()->user()->cannot('purchase-invoices.edit')) {
            abort(403);
        }

        $request->validate([
            'supplier_id' => 'required|integer',
            'purchase_invoice_id' => 'nullable|integer|exists:purchase_invoices,id',
        ]);

        $supplierId = (int) $request->supplier_id;
        $excludePi = $request->filled('purchase_invoice_id') ? (int) $request->purchase_invoice_id : null;

        $companyId = active_company_id();
        if ($excludePi) {
            $pi = PurchaseInvoice::query()->find($excludePi);
            if (! $pi || ! auth()->user()->companies->contains('id', (int) $pi->company_id)) {
                abort(404);
            }
            $companyId = (int) $pi->company_id;
        }

        $notes = DeliveryNote::query()
            ->where('company_id', $companyId)
            ->where('supplier_id', $supplierId)
            ->where('status', 'aceptado')
            ->orderByDesc('date')
            ->get(['id', 'remito_number', 'date', 'lab_branch_id']);

        $eligible = $notes->filter(function (DeliveryNote $dn) use ($excludePi) {
            return $this->deliveryNoteIsFreeForPurchaseInvoice($dn->id, $excludePi)
                || $this->deliveryNoteIsLinkedToPurchaseInvoice($dn->id, $excludePi);
        });

        return response()->json($eligible->values());
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

        $branches = LabBranchResolver::activeBranchesForForms();

        return view('purchase-invoices.create', [
            'suppliers' => $suppliers,
            'deliveryNote' => $deliveryNote,
            'purchaseOrder' => $purchaseOrder,
            'selectedSupplierId' => $request->supplier_id,
            'branches' => $branches,
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
            'lab_branch_id' => [
                'required',
                'integer',
                Rule::exists('lab_branches', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'delivery_note_id' => 'nullable|exists:delivery_notes,id',
            'delivery_note_ids' => 'nullable|array',
            'delivery_note_ids.*' => 'integer|exists:delivery_notes,id',
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
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.iva_rate' => 'required|in:0,10.5,21,27',
            'items.*.lot_number' => 'nullable|string|max:50',
            'items.*.expiration_date' => 'nullable|date',
            'items.*.updates_stock' => 'nullable|boolean',
        ], [
            'invoice_number.required' => 'El número de factura es obligatorio.',
            'voucher_type.required' => 'El tipo de comprobante es obligatorio.',
            'voucher_type.in' => 'El tipo de comprobante debe ser A, B o C.',
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'supplier_id.exists' => 'El proveedor seleccionado no es válido.',
            'lab_branch_id.required' => 'Seleccioná la sede / depósito.',
            'lab_branch_id.exists' => 'La sede no es válida o está inactiva.',
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
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
            'items.*.unit_price.required' => 'El precio unitario es obligatorio.',
            'items.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
            'items.*.iva_rate.required' => 'La alícuota de IVA es obligatoria.',
            'items.*.iva_rate.in' => 'La alícuota de IVA no es válida.',
        ]);

        $deliveryNoteIds = $this->resolveDeliveryNoteIds($request);
        $this->validateDeliveryNotesForInvoice($deliveryNoteIds, (int) $validated['supplier_id'], null);
        $this->validateLinkedDeliveryNotesBranchConsistency($deliveryNoteIds, (int) $validated['lab_branch_id']);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => $validated['invoice_number'],
            'company_id' => active_company_id(),
            'lab_branch_id' => (int) $validated['lab_branch_id'],
            'voucher_type' => $validated['voucher_type'],
            'point_of_sale' => $validated['point_of_sale'] ?? null,
            'supplier_id' => $validated['supplier_id'],
            'delivery_note_id' => $deliveryNoteIds[0] ?? null,
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

        $invoice->deliveryNotes()->sync($deliveryNoteIds);
        $invoice->refresh();
        $invoice->forceFill(['delivery_note_id' => $deliveryNoteIds[0] ?? null])->saveQuietly();

        $hasLinkedDeliveryNotes = count($deliveryNoteIds) > 0;

        foreach ($validated['items'] as $itemData) {
            $ivaAmount = round($itemData['quantity'] * $itemData['unit_price'] * $itemData['iva_rate'] / 100, 2);
            $total = $itemData['quantity'] * $itemData['unit_price'] + $ivaAmount;

            $updatesStock = $hasLinkedDeliveryNotes
                ? false
                : filter_var($itemData['updates_stock'] ?? true, FILTER_VALIDATE_BOOLEAN);

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
                'updates_stock' => $updatesStock,
            ]);
        }

        $invoice->recalculate();

        $stockItems = $invoice->items()->whereNotNull('supply_id')->where('updates_stock', true)->get();
        if ($stockItems->isNotEmpty()) {
            $branch = LabBranchResolver::requireActiveBranchForStock($invoice->lab_branch_id);
            $stockSvc = app(SupplyStockService::class);
            foreach ($stockItems as $item) {
                $supply = $item->supply;
                if (! $supply) {
                    continue;
                }

                $stockSvc->recordEntrada($supply, $branch->id, (float) $item->quantity, [
                    'reason' => 'compra',
                    'reference_type' => PurchaseInvoice::class,
                    'reference_id' => $invoice->id,
                    'lot_number' => $item->lot_number,
                    'expiration_date' => $item->expiration_date,
                    'notes' => "Factura de compra {$invoice->full_number}",
                    'user_id' => auth()->id(),
                ]);

                $supply->refresh()->update([
                    'last_price' => $item->unit_price,
                ]);
            }
        }

        try {
            $invoice->refresh()->load('items.supply', 'supplier');
            if (! JournalEntry::where('source_type', PurchaseInvoice::class)->where('source_id', $invoice->id)->exists()) {
                (new AccountingEntryService)->fromPurchaseInvoice($invoice);
            }
        } catch (\Throwable $e) {
            Log::error('Error generando asiento para factura compra #'.$invoice->id.': '.$e->getMessage());
        }

        return redirect()->route('purchase-invoices.show', $invoice)
            ->with('success', 'Factura '.$invoice->full_number.' creada correctamente.');
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $this->ensureUserCanAccessPurchaseInvoiceCompany($purchaseInvoice);

        $this->authorize('purchase-invoices.index');

        $purchaseInvoice->load([
            'supplier', 'deliveryNotes', 'purchaseOrder', 'creator',
            'items.supply', 'paymentOrderItems.paymentOrder',
        ]);

        return view('purchase-invoices.show', ['invoice' => $purchaseInvoice]);
    }

    public function edit(PurchaseInvoice $purchaseInvoice)
    {
        $this->ensureUserCanAccessPurchaseInvoiceCompany($purchaseInvoice);

        $this->authorize('purchase-invoices.edit');

        if ($purchaseInvoice->status !== 'pendiente') {
            return redirect()->route('purchase-invoices.show', $purchaseInvoice)
                ->with('error', 'Solo se pueden editar facturas en estado pendiente.');
        }

        $purchaseInvoice->load(['items.supply', 'deliveryNotes']);
        $suppliers = Supplier::active()->orderBy('name')->get();
        $branches = LabBranchResolver::activeBranchesForForms();
        $companies = auth()->user()->companies()->orderBy('name')->get();

        return view('purchase-invoices.edit', [
            'invoice' => $purchaseInvoice,
            'suppliers' => $suppliers,
            'branches' => $branches,
            'companies' => $companies,
        ]);
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $this->ensureUserCanAccessPurchaseInvoiceCompany($purchaseInvoice);

        $this->authorize('purchase-invoices.edit');

        if ($purchaseInvoice->status !== 'pendiente') {
            return redirect()->route('purchase-invoices.show', $purchaseInvoice)
                ->with('error', 'Solo se pueden editar facturas en estado pendiente.');
        }

        $remitosCompanyId = (int) $purchaseInvoice->company_id;
        $previousCompanyId = $remitosCompanyId;

        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! auth()->user()->companies->contains('id', (int) $value)) {
                        $fail('No tenés acceso a la empresa seleccionada.');
                    }
                },
            ],
            'invoice_number' => 'required|string',
            'voucher_type' => 'required|in:A,B,C',
            'point_of_sale' => 'nullable|string',
            'supplier_id' => 'required|exists:suppliers,id',
            'lab_branch_id' => [
                'required',
                'integer',
                Rule::exists('lab_branches', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'delivery_note_id' => 'nullable|exists:delivery_notes,id',
            'delivery_note_ids' => 'nullable|array',
            'delivery_note_ids.*' => 'integer|exists:delivery_notes,id',
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
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.iva_rate' => 'required|in:0,10.5,21,27',
            'items.*.lot_number' => 'nullable|string|max:50',
            'items.*.expiration_date' => 'nullable|date',
            'items.*.updates_stock' => 'nullable|boolean',
        ], [
            'invoice_number.required' => 'El número de factura es obligatorio.',
            'voucher_type.required' => 'El tipo de comprobante es obligatorio.',
            'voucher_type.in' => 'El tipo de comprobante debe ser A, B o C.',
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'supplier_id.exists' => 'El proveedor seleccionado no es válido.',
            'lab_branch_id.required' => 'Seleccioná la sede / depósito.',
            'lab_branch_id.exists' => 'La sede no es válida o está inactiva.',
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
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
            'items.*.unit_price.required' => 'El precio unitario es obligatorio.',
            'items.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
            'items.*.iva_rate.required' => 'La alícuota de IVA es obligatoria.',
            'items.*.iva_rate.in' => 'La alícuota de IVA no es válida.',
            'company_id.required' => 'Debés indicar la empresa del comprobante.',
        ]);

        $deliveryNoteIds = $this->resolveDeliveryNoteIds($request);
        $this->validateDeliveryNotesForInvoice($deliveryNoteIds, (int) $validated['supplier_id'], $purchaseInvoice->id, $remitosCompanyId);
        $this->validateLinkedDeliveryNotesBranchConsistency($deliveryNoteIds, (int) $validated['lab_branch_id'], $remitosCompanyId);

        $purchaseInvoice->update([
            'company_id' => (int) $validated['company_id'],
            'invoice_number' => $validated['invoice_number'],
            'voucher_type' => $validated['voucher_type'],
            'point_of_sale' => $validated['point_of_sale'] ?? null,
            'supplier_id' => $validated['supplier_id'],
            'lab_branch_id' => (int) $validated['lab_branch_id'],
            'delivery_note_id' => $deliveryNoteIds[0] ?? null,
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

        $purchaseInvoice->deliveryNotes()->sync($deliveryNoteIds);
        $purchaseInvoice->refresh();
        $purchaseInvoice->forceFill(['delivery_note_id' => $deliveryNoteIds[0] ?? null])->saveQuietly();

        $hasLinkedDeliveryNotes = count($deliveryNoteIds) > 0;

        $purchaseInvoice->items()->delete();

        foreach ($validated['items'] as $itemData) {
            $ivaAmount = round($itemData['quantity'] * $itemData['unit_price'] * $itemData['iva_rate'] / 100, 2);
            $total = $itemData['quantity'] * $itemData['unit_price'] + $ivaAmount;

            $updatesStock = $hasLinkedDeliveryNotes
                ? false
                : filter_var($itemData['updates_stock'] ?? true, FILTER_VALIDATE_BOOLEAN);

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
                'updates_stock' => $updatesStock,
            ]);
        }

        $purchaseInvoice->recalculate();

        $newCompanyId = (int) $validated['company_id'];
        if ($previousCompanyId !== $newCompanyId
            && JournalEntry::where('source_type', PurchaseInvoice::class)->where('source_id', $purchaseInvoice->id)->exists()) {
            JournalEntry::deleteForSource($purchaseInvoice);
            try {
                (new AccountingEntryService)->fromPurchaseInvoice($purchaseInvoice->fresh(['items.supply', 'supplier']));
            } catch (\Throwable $e) {
                Log::error('Error regenerando asiento FC compra #'.$purchaseInvoice->id.' tras cambio de empresa: '.$e->getMessage());
            }
        }

        $success = 'Factura actualizada correctamente.';
        if ($previousCompanyId !== $newCompanyId) {
            $name = Company::query()->whereKey($newCompanyId)->value('name') ?? 'la nueva empresa';
            $success .= ' Factura asignada a '.$name.'. Cambiá la empresa activa para verla en el listado.';
        }

        return redirect()->route('purchase-invoices.show', $purchaseInvoice)
            ->with('success', $success);
    }

    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        $this->ensureUserCanAccessPurchaseInvoiceCompany($purchaseInvoice);

        $this->authorize('purchase-invoices.delete');

        if ($purchaseInvoice->status !== 'pendiente' || $purchaseInvoice->amount_paid > 0) {
            return redirect()->route('purchase-invoices.show', $purchaseInvoice)
                ->with('error', 'Solo se pueden eliminar facturas pendientes sin pagos registrados.');
        }

        $fullNumber = $purchaseInvoice->full_number;

        app(SupplyStockService::class)->deleteMovementsForReference(PurchaseInvoice::class, $purchaseInvoice->id);

        JournalEntry::deleteForSource($purchaseInvoice);
        $purchaseInvoice->delete();

        return redirect()->route('purchase-invoices.index')
            ->with('success', "Factura {$fullNumber} eliminada.");
    }

    /**
     * Remitos vinculados: misma sede y coincide con la sede elegida en la factura.
     *
     * @param  list<int>  $deliveryNoteIds
     */
    protected function validateLinkedDeliveryNotesBranchConsistency(array $deliveryNoteIds, int $invoiceLabBranchId, ?int $companyId = null): void
    {
        if ($deliveryNoteIds === []) {
            return;
        }

        $companyId ??= active_company_id();
        $notes = DeliveryNote::where('company_id', $companyId)->whereIn('id', $deliveryNoteIds)->get();

        foreach ($notes as $dn) {
            if (! $dn->lab_branch_id) {
                throw ValidationException::withMessages([
                    'delivery_note_ids' => 'El remito '.$dn->remito_number.' no tiene sede asignada. Corregí el remito antes de asociar la factura.',
                ]);
            }
        }

        $distinct = $notes->pluck('lab_branch_id')->map(fn ($id) => (int) $id)->unique()->values();
        if ($distinct->count() > 1) {
            throw ValidationException::withMessages([
                'delivery_note_ids' => 'Los remitos asociados deben ser todos de la misma sede.',
            ]);
        }

        if ((int) $distinct->first() !== $invoiceLabBranchId) {
            throw ValidationException::withMessages([
                'lab_branch_id' => 'La sede de la factura debe coincidir con la de los remitos seleccionados.',
            ]);
        }
    }

    /** @return list<int> */
    protected function resolveDeliveryNoteIds(Request $request): array
    {
        $raw = $request->input('delivery_note_ids', []);
        if (! is_array($raw)) {
            $raw = [];
        }
        $ids = collect($raw)->map(fn ($v) => (int) $v)->filter(fn ($id) => $id > 0)->unique()->values()->all();
        if ($request->filled('delivery_note_id')) {
            $single = (int) $request->delivery_note_id;
            if ($single > 0) {
                $ids = collect([$single])->merge($ids)->unique()->values()->all();
            }
        }

        return $ids;
    }

    /**
     * @param  list<int>  $ids
     */
    protected function validateDeliveryNotesForInvoice(array $ids, int $supplierId, ?int $excludePurchaseInvoiceId, ?int $companyId = null): void
    {
        $companyId ??= active_company_id();

        foreach ($ids as $dnId) {
            $dn = DeliveryNote::where('company_id', $companyId)->find($dnId);
            if (! $dn) {
                throw ValidationException::withMessages([
                    'delivery_note_ids' => 'Uno de los remitos no existe o no pertenece a la empresa activa.',
                ]);
            }
            if ((int) $dn->supplier_id !== (int) $supplierId) {
                throw ValidationException::withMessages([
                    'delivery_note_ids' => 'El remito '.$dn->remito_number.' no pertenece al proveedor seleccionado.',
                ]);
            }
            if (! $this->deliveryNoteIsFreeForPurchaseInvoice($dnId, $excludePurchaseInvoiceId)) {
                throw ValidationException::withMessages([
                    'delivery_note_ids' => 'El remito '.$dn->remito_number.' ya está asociado a otra factura de compra.',
                ]);
            }
        }
    }

    protected function deliveryNoteIsFreeForPurchaseInvoice(int $deliveryNoteId, ?int $excludePurchaseInvoiceId): bool
    {
        $inPivot = DB::table('delivery_note_purchase_invoice')
            ->where('delivery_note_id', $deliveryNoteId)
            ->when($excludePurchaseInvoiceId, fn ($q) => $q->where('purchase_invoice_id', '!=', $excludePurchaseInvoiceId))
            ->exists();

        $legacy = PurchaseInvoice::query()
            ->where('delivery_note_id', $deliveryNoteId)
            ->when($excludePurchaseInvoiceId, fn ($q) => $q->where('id', '!=', $excludePurchaseInvoiceId))
            ->exists();

        return ! $inPivot && ! $legacy;
    }

    protected function deliveryNoteIsLinkedToPurchaseInvoice(int $deliveryNoteId, ?int $purchaseInvoiceId): bool
    {
        if (! $purchaseInvoiceId) {
            return false;
        }

        return DB::table('delivery_note_purchase_invoice')
            ->where('delivery_note_id', $deliveryNoteId)
            ->where('purchase_invoice_id', $purchaseInvoiceId)
            ->exists()
            || PurchaseInvoice::where('id', $purchaseInvoiceId)->where('delivery_note_id', $deliveryNoteId)->exists();
    }

    protected function ensureUserCanAccessPurchaseInvoiceCompany(PurchaseInvoice $purchaseInvoice): void
    {
        abort_unless(
            auth()->user()->companies->contains('id', (int) $purchaseInvoice->company_id),
            403
        );
    }
}
