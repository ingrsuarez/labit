<?php

namespace App\Http\Controllers;

use App\Models\CollectionReceipt;
use App\Models\CollectionReceiptItem;
use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionReceiptController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('collection-receipts.index');

        $query = CollectionReceipt::with(['customer', 'creator', 'confirmer'])
            ->where('company_id', active_company_id())
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $collectionReceipts = $query->paginate(15)->withQueryString();

        return view('collection-receipts.index', compact('collectionReceipts'));
    }

    public function create(Request $request)
    {
        $this->authorize('collection-receipts.create');

        $customers = Customer::where('status', 'activo')->orderBy('name')->get();
        $selectedCustomer = null;
        $pendingInvoices = collect();

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::find($request->customer_id);
            if ($selectedCustomer) {
                $pendingInvoices = SalesInvoice::where('customer_id', $selectedCustomer->id)
                    ->where('company_id', active_company_id())
                    ->whereIn('status', ['pendiente', 'parcialmente_cobrada'])
                    ->orderByDesc('issue_date')
                    ->get();
            }
        }

        return view('collection-receipts.create', compact('customers', 'selectedCustomer', 'pendingInvoices'));
    }

    public function store(Request $request)
    {
        $this->authorize('collection-receipts.create');

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'date' => 'required|date',
            'payment_method' => 'nullable|in:transferencia,cheque,efectivo,tarjeta,deposito',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'invoices' => 'required|array|min:1',
            'invoices.*.sales_invoice_id' => 'required|exists:sales_invoices,id',
            'invoices.*.amount' => 'required|numeric|min:0.01',
        ], [
            'customer_id.required' => 'Debe seleccionar un cliente.',
            'date.required' => 'La fecha es obligatoria.',
            'invoices.required' => 'Debe agregar al menos una factura.',
            'invoices.min' => 'Debe agregar al menos una factura.',
            'invoices.*.sales_invoice_id.required' => 'Debe seleccionar una factura.',
            'invoices.*.amount.required' => 'El monto es obligatorio.',
            'invoices.*.amount.min' => 'El monto debe ser mayor a 0.',
        ]);

        $collectionReceipt = CollectionReceipt::create([
            'number' => CollectionReceipt::generateNumber(),
            'company_id' => active_company_id(),
            'customer_id' => $validated['customer_id'],
            'date' => $validated['date'],
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_reference' => $validated['payment_reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'borrador',
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['invoices'] as $itemData) {
            $collectionReceipt->items()->create([
                'sales_invoice_id' => $itemData['sales_invoice_id'],
                'amount' => $itemData['amount'],
            ]);
        }

        $collectionReceipt->recalculate();

        return redirect()->route('collection-receipts.show', $collectionReceipt)
            ->with('success', 'Recibo de cobro ' . $collectionReceipt->number . ' creado correctamente.');
    }

    public function show(CollectionReceipt $collectionReceipt)
    {
        $this->authorize('collection-receipts.index');

        abort_if($collectionReceipt->company_id !== active_company_id(), 403);

        $collectionReceipt->load(['customer', 'creator', 'confirmer', 'items.invoice.customer']);
        return view('collection-receipts.show', compact('collectionReceipt'));
    }

    public function edit(CollectionReceipt $collectionReceipt)
    {
        $this->authorize('collection-receipts.edit');

        abort_if($collectionReceipt->company_id !== active_company_id(), 403);

        if ($collectionReceipt->status !== 'borrador') {
            return redirect()->route('collection-receipts.show', $collectionReceipt)
                ->with('error', 'Solo se pueden editar recibos de cobro en estado borrador.');
        }

        $collectionReceipt->load(['items.invoice']);
        $customers = Customer::where('status', 'activo')->orderBy('name')->get();

        $existingInvoiceIds = $collectionReceipt->items->pluck('sales_invoice_id')->toArray();

        $pendingInvoices = SalesInvoice::where('customer_id', $collectionReceipt->customer_id)
            ->where('company_id', active_company_id())
            ->where(function ($q) use ($existingInvoiceIds) {
                $q->whereIn('status', ['pendiente', 'parcialmente_cobrada'])
                  ->orWhereIn('id', $existingInvoiceIds);
            })
            ->orderByDesc('issue_date')
            ->get();

        return view('collection-receipts.edit', compact('collectionReceipt', 'customers', 'pendingInvoices'));
    }

    public function update(Request $request, CollectionReceipt $collectionReceipt)
    {
        $this->authorize('collection-receipts.edit');

        abort_if($collectionReceipt->company_id !== active_company_id(), 403);

        if ($collectionReceipt->status !== 'borrador') {
            return redirect()->route('collection-receipts.show', $collectionReceipt)
                ->with('error', 'Solo se pueden editar recibos de cobro en estado borrador.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'date' => 'required|date',
            'payment_method' => 'nullable|in:transferencia,cheque,efectivo,tarjeta,deposito',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'invoices' => 'required|array|min:1',
            'invoices.*.sales_invoice_id' => 'required|exists:sales_invoices,id',
            'invoices.*.amount' => 'required|numeric|min:0.01',
        ], [
            'customer_id.required' => 'Debe seleccionar un cliente.',
            'date.required' => 'La fecha es obligatoria.',
            'invoices.required' => 'Debe agregar al menos una factura.',
            'invoices.min' => 'Debe agregar al menos una factura.',
            'invoices.*.sales_invoice_id.required' => 'Debe seleccionar una factura.',
            'invoices.*.amount.required' => 'El monto es obligatorio.',
            'invoices.*.amount.min' => 'El monto debe ser mayor a 0.',
        ]);

        $collectionReceipt->update([
            'customer_id' => $validated['customer_id'],
            'date' => $validated['date'],
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_reference' => $validated['payment_reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $collectionReceipt->items()->delete();

        foreach ($validated['invoices'] as $itemData) {
            $collectionReceipt->items()->create([
                'sales_invoice_id' => $itemData['sales_invoice_id'],
                'amount' => $itemData['amount'],
            ]);
        }

        $collectionReceipt->recalculate();

        return redirect()->route('collection-receipts.show', $collectionReceipt)
            ->with('success', 'Recibo de cobro actualizado correctamente.');
    }

    public function destroy(CollectionReceipt $collectionReceipt)
    {
        $this->authorize('collection-receipts.delete');

        abort_if($collectionReceipt->company_id !== active_company_id(), 403);

        if ($collectionReceipt->status !== 'borrador') {
            return redirect()->route('collection-receipts.show', $collectionReceipt)
                ->with('error', 'Solo se pueden eliminar recibos de cobro en estado borrador.');
        }

        $number = $collectionReceipt->number;
        $collectionReceipt->delete();

        return redirect()->route('collection-receipts.index')
            ->with('success', "Recibo de cobro {$number} eliminado.");
    }

    public function confirm(CollectionReceipt $collectionReceipt)
    {
        $this->authorize('collection-receipts.edit');

        abort_if($collectionReceipt->company_id !== active_company_id(), 403);

        if ($collectionReceipt->status !== 'borrador') {
            return back()->with('error', 'Solo se pueden confirmar recibos en estado borrador.');
        }

        DB::beginTransaction();

        try {
            foreach ($collectionReceipt->items as $item) {
                $invoice = $item->invoice;
                $invoice->amount_collected += $item->amount;
                $invoice->balance = $invoice->total - $invoice->amount_collected;
                $invoice->updateCollectionStatus();
            }

            $collectionReceipt->status = 'confirmado';
            $collectionReceipt->confirmed_by = auth()->id();
            $collectionReceipt->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al confirmar el recibo de cobro: ' . $e->getMessage());
        }

        return back()->with('success', 'Recibo de cobro ' . $collectionReceipt->number . ' confirmado.');
    }
}
