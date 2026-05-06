<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePerception;
use App\Models\PurchaseService;
use App\Models\Supplier;
use App\Services\AccountingEntryService;
use App\Services\LabBranchResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseCreditNoteController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('purchase-credit-notes.index');

        $query = PurchaseCreditNote::with(['supplier', 'purchaseInvoice', 'labBranch'])
            ->where('company_id', active_company_id())
            ->orderByDesc('issue_date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('credit_note_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $creditNotes = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name']);

        return view('purchase-credit-notes.index', compact('creditNotes', 'suppliers'));
    }

    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|integer',
            'point_of_sale' => 'nullable|string',
            'credit_note_number' => 'required|string',
            'exclude_id' => 'nullable|integer',
        ]);

        $companyId = active_company_id();
        if ($request->filled('exclude_id')) {
            $cn = PurchaseCreditNote::query()->find($request->exclude_id);
            if (! $cn || ! auth()->user()->companies->contains('id', (int) $cn->company_id)) {
                abort(403);
            }
            $companyId = (int) $cn->company_id;
        }

        $pv = $request->point_of_sale ?? '';

        $exists = PurchaseCreditNote::where('company_id', $companyId)
            ->where('supplier_id', $request->supplier_id)
            ->where('point_of_sale', $pv)
            ->where('credit_note_number', $request->credit_note_number)
            ->when($request->exclude_id, fn ($q) => $q->where('id', '!=', $request->exclude_id))
            ->exists();

        return response()->json(['duplicate' => $exists]);
    }

    /**
     * Facturas de compra del proveedor con saldo pendiente (para aplicar la NC).
     */
    public function availablePurchaseInvoices(Request $request)
    {
        if (auth()->user()->cannot('purchase-credit-notes.create')) {
            abort(403);
        }

        $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
        ]);

        $invoices = PurchaseInvoice::query()
            ->where('company_id', active_company_id())
            ->where('supplier_id', (int) $request->supplier_id)
            ->whereIn('status', ['pendiente', 'parcialmente_pagada'])
            ->where('balance', '>', 0)
            ->orderByDesc('issue_date')
            ->get(['id', 'invoice_number', 'point_of_sale', 'voucher_type', 'issue_date', 'balance', 'total']);

        return response()->json($invoices->map(fn ($inv) => [
            'id' => $inv->id,
            'label' => $inv->full_number.' — Saldo: $'.number_format((float) $inv->balance, 2, ',', '.'),
            'balance' => (float) $inv->balance,
            'issue_date' => $inv->issue_date?->format('Y-m-d'),
        ]));
    }

    public function create(Request $request)
    {
        $this->authorize('purchase-credit-notes.create');

        $suppliers = Supplier::active()->orderBy('name')->get();
        $branches = LabBranchResolver::activeBranchesForForms();
        $selectedSupplierId = $request->get('supplier_id');
        $selectedPurchaseInvoiceId = $request->get('purchase_invoice_id');

        $perceptionTypes = PurchasePerception::query()
            ->where('company_id', active_company_id())
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('purchase-credit-notes.create', [
            'suppliers' => $suppliers,
            'branches' => $branches,
            'selectedSupplierId' => $selectedSupplierId,
            'selectedPurchaseInvoiceId' => $selectedPurchaseInvoiceId,
            'purchaseServiceCatalog' => PurchaseService::catalogGroupsForCompany(active_company_id()),
            'perceptionTypes' => $perceptionTypes,
            'perceptionLines' => [],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('purchase-credit-notes.create');

        $request->merge([
            'purchase_invoice_id' => $request->filled('purchase_invoice_id') ? $request->purchase_invoice_id : null,
        ]);

        $validated = $request->validate([
            'credit_note_number' => 'required|string|max:100',
            'voucher_type' => 'required|in:A,B,C',
            'point_of_sale' => 'nullable|string|max:20',
            'supplier_id' => 'required|exists:suppliers,id',
            'lab_branch_id' => [
                'required',
                'integer',
                Rule::exists('lab_branches', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'issue_date' => 'required|date',
            'otros_impuestos' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'perceptions' => 'nullable|array',
            'perceptions.*.accounting_account_id' => 'nullable|exists:accounting_accounts,id',
            'perceptions.*.amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.supply_id' => 'nullable|exists:supplies,id',
            'items.*.purchase_service_id' => 'nullable|integer|exists:purchase_services,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.iva_rate' => 'required|in:0,10.5,21,27',
        ], [
            'credit_note_number.required' => 'El número de nota de crédito es obligatorio.',
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'lab_branch_id.required' => 'Seleccioná la sede / depósito.',
            'issue_date.required' => 'La fecha es obligatoria.',
            'items.required' => 'Debe agregar al menos un ítem.',
        ]);

        $this->assertItemsPurchaseServicesBelongToCompany($validated['items'], active_company_id());

        $pv = trim((string) ($validated['point_of_sale'] ?? ''));
        $duplicate = PurchaseCreditNote::where('company_id', active_company_id())
            ->where('supplier_id', $validated['supplier_id'])
            ->where('point_of_sale', $pv)
            ->where('credit_note_number', $validated['credit_note_number'])
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages([
                'credit_note_number' => 'Ya existe una nota de crédito con el mismo proveedor, punto de venta y número.',
            ]);
        }

        $purchaseInvoice = null;
        if (! empty($validated['purchase_invoice_id'])) {
            $purchaseInvoice = PurchaseInvoice::where('company_id', active_company_id())
                ->where('supplier_id', $validated['supplier_id'])
                ->find($validated['purchase_invoice_id']);
            if (! $purchaseInvoice) {
                throw ValidationException::withMessages([
                    'purchase_invoice_id' => 'La factura no corresponde al proveedor o a la empresa activa.',
                ]);
            }
            if (! in_array($purchaseInvoice->status, ['pendiente', 'parcialmente_pagada'], true)) {
                throw ValidationException::withMessages([
                    'purchase_invoice_id' => 'Solo se pueden aplicar notas de crédito a facturas con saldo pendiente.',
                ]);
            }
        }

        $creditNote = DB::transaction(function () use ($request, $validated, $pv, $purchaseInvoice) {
            $cn = PurchaseCreditNote::create([
                'company_id' => active_company_id(),
                'lab_branch_id' => (int) $validated['lab_branch_id'],
                'supplier_id' => (int) $validated['supplier_id'],
                'purchase_invoice_id' => $purchaseInvoice?->id,
                'credit_note_number' => $validated['credit_note_number'],
                'voucher_type' => $validated['voucher_type'],
                'point_of_sale' => $pv,
                'issue_date' => $validated['issue_date'],
                'percepciones' => 0,
                'otros_impuestos' => $validated['otros_impuestos'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                $ivaAmount = round($itemData['quantity'] * $itemData['unit_price'] * $itemData['iva_rate'] / 100, 2);
                $total = $itemData['quantity'] * $itemData['unit_price'] + $ivaAmount;

                $purchaseServiceId = ! empty($itemData['purchase_service_id']) ? (int) $itemData['purchase_service_id'] : null;
                $supplyId = $purchaseServiceId ? null : ($itemData['supply_id'] ?? null);

                $cn->items()->create([
                    'description' => $itemData['description'],
                    'supply_id' => $supplyId,
                    'purchase_service_id' => $purchaseServiceId,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'iva_rate' => $itemData['iva_rate'],
                    'iva_amount' => $ivaAmount,
                    'total' => $total,
                ]);
            }

            $cn->perceptions()->delete();
            foreach ($request->input('perceptions', []) as $idx => $line) {
                if (! empty($line['accounting_account_id']) && (float) ($line['amount'] ?? 0) > 0) {
                    $cn->perceptions()->create([
                        'purchase_perception_id' => $line['purchase_perception_id'] ?: null,
                        'accounting_account_id' => $line['accounting_account_id'],
                        'name_snapshot' => $line['name_snapshot'] ?? '',
                        'jurisdiction_snapshot' => $line['jurisdiction_snapshot'] ?? null,
                        'rate_snapshot' => $line['rate_snapshot'] ?? 0,
                        'amount' => $line['amount'],
                        'sort_order' => $idx,
                    ]);
                }
            }

            $cn->recalculate();

            if ($purchaseInvoice) {
                if ((float) $cn->total > (float) $purchaseInvoice->balance + 0.01) {
                    throw ValidationException::withMessages([
                        'items' => 'El total de la nota de crédito no puede superar el saldo pendiente de la factura seleccionada ($'
                            .number_format((float) $purchaseInvoice->balance, 2, ',', '.').').',
                    ]);
                }
                $purchaseInvoice->balance = round((float) $purchaseInvoice->balance - (float) $cn->total, 2);
                $purchaseInvoice->updatePaymentStatus();
            }

            return $cn->fresh(['items', 'supplier']);
        });

        try {
            if (! JournalEntry::where('source_type', PurchaseCreditNote::class)->where('source_id', $creditNote->id)->exists()) {
                (new AccountingEntryService)->fromPurchaseCreditNote($creditNote);
            }
        } catch (\Throwable $e) {
            Log::error('Error generando asiento para NC proveedor #'.$creditNote->id.': '.$e->getMessage());
        }

        return redirect()->route('purchase-credit-notes.show', $creditNote)
            ->with('success', 'Nota de crédito '.$creditNote->full_number.' registrada correctamente.');
    }

    public function show(PurchaseCreditNote $purchaseCreditNote)
    {
        $this->ensureCompany($purchaseCreditNote);

        $this->authorize('purchase-credit-notes.index');

        $purchaseCreditNote->load(['supplier', 'purchaseInvoice', 'labBranch', 'items.supply', 'items.purchaseService', 'creator', 'perceptions.accountingAccount']);

        return view('purchase-credit-notes.show', ['creditNote' => $purchaseCreditNote]);
    }

    public function destroy(PurchaseCreditNote $purchaseCreditNote)
    {
        $this->ensureCompany($purchaseCreditNote);

        $this->authorize('purchase-credit-notes.delete');

        DB::transaction(function () use ($purchaseCreditNote) {
            if ($purchaseCreditNote->purchase_invoice_id) {
                $inv = PurchaseInvoice::find($purchaseCreditNote->purchase_invoice_id);
                if ($inv && (int) $inv->company_id === (int) $purchaseCreditNote->company_id) {
                    $inv->balance = round((float) $inv->balance + (float) $purchaseCreditNote->total, 2);
                    $inv->updatePaymentStatus();
                }
            }

            JournalEntry::deleteForSource($purchaseCreditNote);
            $purchaseCreditNote->delete();
        });

        return redirect()->route('purchase-credit-notes.index')
            ->with('success', 'Nota de crédito eliminada.');
    }

    protected function ensureCompany(PurchaseCreditNote $purchaseCreditNote): void
    {
        abort_if((int) $purchaseCreditNote->company_id !== (int) active_company_id(), 403);
    }

    protected function assertItemsPurchaseServicesBelongToCompany(array $items, int $companyId): void
    {
        foreach ($items as $idx => $itemData) {
            $sid = $itemData['purchase_service_id'] ?? null;
            if ($sid === null || $sid === '') {
                continue;
            }
            $ok = PurchaseService::where('id', (int) $sid)->where('company_id', $companyId)->exists();
            if (! $ok) {
                throw ValidationException::withMessages([
                    "items.$idx.purchase_service_id" => 'El servicio de compra no pertenece a la empresa del comprobante o no existe.',
                ]);
            }
        }
    }
}
