<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\PointOfSale;
use Illuminate\Http\Request;

class SalesInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('sales-invoices.index');

        $query = SalesInvoice::with(['customer', 'creator', 'pointOfSale'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $invoices = $query->paginate(15)->withQueryString();

        $total_balance = SalesInvoice::whereIn('status', ['pendiente', 'parcialmente_cobrada'])->sum('balance');

        return view('sales-invoices.index', compact('invoices', 'total_balance'));
    }

    public function create(Request $request)
    {
        $this->authorize('sales-invoices.create');

        $customers = Customer::where('status', 'activo')->orderBy('name')->get();
        $pointsOfSale = PointOfSale::where('is_active', true)->orderBy('code')->get();

        $quote = null;

        if ($request->filled('quote_id')) {
            $quote = Quote::with('items')->findOrFail($request->quote_id);
        }

        return view('sales-invoices.create', [
            'customers' => $customers,
            'pointsOfSale' => $pointsOfSale,
            'quote' => $quote,
            'selectedCustomerId' => $request->customer_id,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('sales-invoices.create');

        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:sales_invoices,invoice_number,NULL,id,voucher_type,' . $request->voucher_type . ',point_of_sale_id,' . $request->point_of_sale_id,
            'voucher_type' => 'required|in:A,B,C',
            'point_of_sale_id' => 'required|exists:points_of_sale,id',
            'customer_id' => 'required|exists:customers,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'admission_id' => 'nullable|integer',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'percepciones' => 'nullable|numeric|min:0',
            'otros_impuestos' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.test_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.iva_rate' => 'required|in:0,10.5,21,27',
        ], [
            'invoice_number.required' => 'El número de factura es obligatorio.',
            'invoice_number.unique' => 'Ya existe una factura con ese número para el mismo tipo y punto de venta.',
            'voucher_type.required' => 'El tipo de comprobante es obligatorio.',
            'voucher_type.in' => 'El tipo de comprobante debe ser A, B o C.',
            'customer_id.required' => 'Debe seleccionar un cliente.',
            'customer_id.exists' => 'El cliente seleccionado no es válido.',
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

        $invoice = SalesInvoice::create([
            'invoice_number' => $validated['invoice_number'],
            'voucher_type' => $validated['voucher_type'],
            'point_of_sale_id' => $validated['point_of_sale_id'],
            'customer_id' => $validated['customer_id'],
            'quote_id' => $validated['quote_id'] ?? null,
            'admission_id' => $validated['admission_id'] ?? null,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'] ?? null,
            'percepciones' => $validated['percepciones'] ?? 0,
            'otros_impuestos' => $validated['otros_impuestos'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pendiente',
            'amount_collected' => 0,
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['items'] as $itemData) {
            $ivaAmount = round($itemData['quantity'] * $itemData['unit_price'] * $itemData['iva_rate'] / 100, 2);
            $total = $itemData['quantity'] * $itemData['unit_price'] + $ivaAmount;

            $invoice->items()->create([
                'description' => $itemData['description'],
                'test_id' => $itemData['test_id'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'iva_rate' => $itemData['iva_rate'],
                'iva_amount' => $ivaAmount,
                'total' => $total,
            ]);
        }

        $invoice->recalculate();

        return redirect()->route('sales-invoices.show', $invoice)
            ->with('success', 'Factura ' . $invoice->full_number . ' creada correctamente.');
    }

    public function show(SalesInvoice $salesInvoice)
    {
        $this->authorize('sales-invoices.index');

        $salesInvoice->load([
            'customer', 'quote', 'creator', 'pointOfSale',
            'items.test', 'collectionReceiptItems.collectionReceipt',
        ]);

        return view('sales-invoices.show', ['invoice' => $salesInvoice]);
    }

    public function edit(SalesInvoice $salesInvoice)
    {
        $this->authorize('sales-invoices.edit');

        if ($salesInvoice->status !== 'pendiente') {
            return redirect()->route('sales-invoices.show', $salesInvoice)
                ->with('error', 'Solo se pueden editar facturas en estado pendiente.');
        }

        $salesInvoice->load('items');
        $customers = Customer::where('status', 'activo')->orderBy('name')->get();
        $pointsOfSale = PointOfSale::where('is_active', true)->orderBy('code')->get();

        return view('sales-invoices.edit', [
            'invoice' => $salesInvoice,
            'customers' => $customers,
            'pointsOfSale' => $pointsOfSale,
        ]);
    }

    public function update(Request $request, SalesInvoice $salesInvoice)
    {
        $this->authorize('sales-invoices.edit');

        if ($salesInvoice->status !== 'pendiente') {
            return redirect()->route('sales-invoices.show', $salesInvoice)
                ->with('error', 'Solo se pueden editar facturas en estado pendiente.');
        }

        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:sales_invoices,invoice_number,' . $salesInvoice->id . ',id,voucher_type,' . $request->voucher_type . ',point_of_sale_id,' . $request->point_of_sale_id,
            'voucher_type' => 'required|in:A,B,C',
            'point_of_sale_id' => 'required|exists:points_of_sale,id',
            'customer_id' => 'required|exists:customers,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'admission_id' => 'nullable|integer',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'percepciones' => 'nullable|numeric|min:0',
            'otros_impuestos' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.test_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.iva_rate' => 'required|in:0,10.5,21,27',
        ], [
            'invoice_number.required' => 'El número de factura es obligatorio.',
            'invoice_number.unique' => 'Ya existe una factura con ese número para el mismo tipo y punto de venta.',
            'voucher_type.required' => 'El tipo de comprobante es obligatorio.',
            'voucher_type.in' => 'El tipo de comprobante debe ser A, B o C.',
            'customer_id.required' => 'Debe seleccionar un cliente.',
            'customer_id.exists' => 'El cliente seleccionado no es válido.',
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

        $salesInvoice->update([
            'invoice_number' => $validated['invoice_number'],
            'voucher_type' => $validated['voucher_type'],
            'point_of_sale_id' => $validated['point_of_sale_id'],
            'customer_id' => $validated['customer_id'],
            'quote_id' => $validated['quote_id'] ?? null,
            'admission_id' => $validated['admission_id'] ?? null,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'] ?? null,
            'percepciones' => $validated['percepciones'] ?? 0,
            'otros_impuestos' => $validated['otros_impuestos'] ?? 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        $salesInvoice->items()->delete();

        foreach ($validated['items'] as $itemData) {
            $ivaAmount = round($itemData['quantity'] * $itemData['unit_price'] * $itemData['iva_rate'] / 100, 2);
            $total = $itemData['quantity'] * $itemData['unit_price'] + $ivaAmount;

            $salesInvoice->items()->create([
                'description' => $itemData['description'],
                'test_id' => $itemData['test_id'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'iva_rate' => $itemData['iva_rate'],
                'iva_amount' => $ivaAmount,
                'total' => $total,
            ]);
        }

        $salesInvoice->recalculate();

        return redirect()->route('sales-invoices.show', $salesInvoice)
            ->with('success', 'Factura actualizada correctamente.');
    }

    public function destroy(SalesInvoice $salesInvoice)
    {
        $this->authorize('sales-invoices.delete');

        if ($salesInvoice->status !== 'pendiente' || $salesInvoice->amount_collected > 0) {
            return redirect()->route('sales-invoices.show', $salesInvoice)
                ->with('error', 'Solo se pueden eliminar facturas pendientes sin cobros registrados.');
        }

        $fullNumber = $salesInvoice->full_number;
        $salesInvoice->delete();

        return redirect()->route('sales-invoices.index')
            ->with('success', "Factura {$fullNumber} eliminada.");
    }

    public function nextNumber(Request $request)
    {
        $this->authorize('sales-invoices.index');

        $request->validate([
            'voucher_type' => 'required|in:A,B,C',
            'point_of_sale_id' => 'required|exists:points_of_sale,id',
        ]);

        $last = SalesInvoice::where('voucher_type', $request->voucher_type)
            ->where('point_of_sale_id', $request->point_of_sale_id)
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        if ($last) {
            $nextNumber = str_pad((int) $last + 1, strlen($last), '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '';
        }

        return response()->json(['next_number' => $nextNumber]);
    }
}
