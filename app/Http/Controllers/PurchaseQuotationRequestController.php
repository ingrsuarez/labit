<?php

namespace App\Http\Controllers;

use App\Models\PurchaseQuotationRequest;
use App\Models\Supplier;
use App\Models\Supply;
use Illuminate\Http\Request;

class PurchaseQuotationRequestController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('purchase-quotation-requests.index');

        $query = PurchaseQuotationRequest::with(['supplier', 'creator', 'items'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$search}%")
                      ->orWhere('business_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('purchase-quotation-requests.index', compact('requests'));
    }

    public function create()
    {
        $this->authorize('purchase-quotation-requests.create');

        $suppliers = Supplier::active()->orderBy('name')->get();
        $supplies = Supply::active()->orderBy('name')->get();
        return view('purchase-quotation-requests.create', compact('suppliers', 'supplies'));
    }

    public function store(Request $request)
    {
        $this->authorize('purchase-quotation-requests.create');

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'date.required' => 'La fecha es obligatoria.',
            'items.required' => 'Debe agregar al menos un insumo.',
            'items.min' => 'Debe agregar al menos un insumo.',
            'items.*.supply_id.required' => 'Debe seleccionar un insumo.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
        ]);

        $quotation = PurchaseQuotationRequest::create([
            'number' => PurchaseQuotationRequest::generateNumber(),
            'supplier_id' => $validated['supplier_id'],
            'date' => $validated['date'],
            'valid_until' => $validated['valid_until'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'borrador',
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['items'] as $index => $itemData) {
            $quotation->items()->create([
                'supply_id' => $itemData['supply_id'],
                'quantity' => $itemData['quantity'],
                'notes' => $itemData['notes'] ?? null,
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('purchase-quotation-requests.show', $quotation)
            ->with('success', 'Solicitud de cotización ' . $quotation->number . ' creada correctamente.');
    }

    public function show(PurchaseQuotationRequest $purchaseQuotationRequest)
    {
        $this->authorize('purchase-quotation-requests.index');

        $purchaseQuotationRequest->load(['supplier', 'creator', 'items.supply']);
        return view('purchase-quotation-requests.show', ['quotation' => $purchaseQuotationRequest]);
    }

    public function edit(PurchaseQuotationRequest $purchaseQuotationRequest)
    {
        $this->authorize('purchase-quotation-requests.edit');

        $purchaseQuotationRequest->load(['items.supply']);
        $suppliers = Supplier::active()->orderBy('name')->get();
        $supplies = Supply::active()->orderBy('name')->get();
        return view('purchase-quotation-requests.edit', [
            'quotation' => $purchaseQuotationRequest,
            'suppliers' => $suppliers,
            'supplies' => $supplies,
        ]);
    }

    public function update(Request $request, PurchaseQuotationRequest $purchaseQuotationRequest)
    {
        $this->authorize('purchase-quotation-requests.edit');

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'items.required' => 'Debe agregar al menos un insumo.',
        ]);

        $purchaseQuotationRequest->update([
            'supplier_id' => $validated['supplier_id'],
            'date' => $validated['date'],
            'valid_until' => $validated['valid_until'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $purchaseQuotationRequest->items()->delete();

        foreach ($validated['items'] as $index => $itemData) {
            $purchaseQuotationRequest->items()->create([
                'supply_id' => $itemData['supply_id'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'] ?? null,
                'notes' => $itemData['notes'] ?? null,
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('purchase-quotation-requests.show', $purchaseQuotationRequest)
            ->with('success', 'Solicitud de cotización actualizada correctamente.');
    }

    public function destroy(PurchaseQuotationRequest $purchaseQuotationRequest)
    {
        $this->authorize('purchase-quotation-requests.delete');

        $number = $purchaseQuotationRequest->number;
        $purchaseQuotationRequest->delete();

        return redirect()->route('purchase-quotation-requests.index')
            ->with('success', "Solicitud {$number} eliminada.");
    }

    public function updateStatus(Request $request, PurchaseQuotationRequest $purchaseQuotationRequest)
    {
        $this->authorize('purchase-quotation-requests.edit');

        $validated = $request->validate([
            'status' => 'required|in:borrador,enviada,recibida,cancelada',
        ]);

        $purchaseQuotationRequest->update(['status' => $validated['status']]);

        return back()->with('success', 'Estado actualizado a: ' . $purchaseQuotationRequest->status_label);
    }
}
