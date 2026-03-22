<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Customer;
use App\Models\Test;
use App\Models\Service;
use App\Mail\QuoteMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use PDF;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $query = Quote::with(['customer', 'creator', 'items'])
            ->where('company_id', active_company_id())
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $quotes = $query->paginate(15)->withQueryString();

        return view('quote.index', compact('quotes'));
    }

    public function create()
    {
        return view('quote.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'valid_until' => 'nullable|date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.test_id' => 'nullable|exists:tests,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'customer_name.required' => 'El nombre del cliente es obligatorio.',
            'customer_email.email' => 'El email no tiene un formato válido.',
            'items.required' => 'Debe agregar al menos una determinación.',
            'items.min' => 'Debe agregar al menos una determinación.',
            'items.*.description.required' => 'La descripción es obligatoria.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad mínima es 1.',
            'items.*.unit_price.required' => 'El precio es obligatorio.',
            'items.*.unit_price.min' => 'El precio no puede ser negativo.',
        ]);

        $quote = Quote::create([
            'quote_number' => Quote::generateQuoteNumber(),
            'company_id' => active_company_id(),
            'customer_id' => $validated['customer_id'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'tax_rate' => $validated['tax_rate'] ?? 0,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['items'] as $index => $itemData) {
            $total = $itemData['quantity'] * $itemData['unit_price'];
            $quote->items()->create([
                'test_id' => $itemData['test_id'] ?? null,
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total' => $total,
                'sort_order' => $index,
            ]);
        }

        $quote->recalculate();

        return redirect()->route('quotes.show', $quote)
            ->with('success', 'Presupuesto ' . $quote->quote_number . ' creado correctamente.');
    }

    public function show(Quote $quote)
    {
        abort_if($quote->company_id !== active_company_id(), 403);
        $quote->load(['customer', 'creator', 'items.test']);
        return view('quote.show', compact('quote'));
    }

    public function edit(Quote $quote)
    {
        abort_if($quote->company_id !== active_company_id(), 403);
        $quote->load(['items.test']);
        return view('quote.edit', compact('quote'));
    }

    public function update(Request $request, Quote $quote)
    {
        abort_if($quote->company_id !== active_company_id(), 403);
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'valid_until' => 'nullable|date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.test_id' => 'nullable|exists:tests,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'customer_name.required' => 'El nombre del cliente es obligatorio.',
            'customer_email.email' => 'El email no tiene un formato válido.',
            'items.required' => 'Debe agregar al menos una determinación.',
            'items.min' => 'Debe agregar al menos una determinación.',
            'items.*.description.required' => 'La descripción es obligatoria.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad mínima es 1.',
            'items.*.unit_price.required' => 'El precio es obligatorio.',
            'items.*.unit_price.min' => 'El precio no puede ser negativo.',
        ]);

        $quote->update([
            'customer_id' => $validated['customer_id'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'tax_rate' => $validated['tax_rate'] ?? 0,
        ]);

        $quote->items()->delete();

        foreach ($validated['items'] as $index => $itemData) {
            $total = $itemData['quantity'] * $itemData['unit_price'];
            $quote->items()->create([
                'test_id' => $itemData['test_id'] ?? null,
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total' => $total,
                'sort_order' => $index,
            ]);
        }

        $quote->recalculate();

        return redirect()->route('quotes.show', $quote)
            ->with('success', 'Presupuesto actualizado correctamente.');
    }

    public function destroy(Quote $quote)
    {
        abort_if($quote->company_id !== active_company_id(), 403);
        $number = $quote->quote_number;
        $quote->delete();

        return redirect()->route('quotes.index')
            ->with('success', "Presupuesto {$number} eliminado.");
    }

    public function searchCustomers(Request $request)
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $customers = Customer::where('status', 'activo')
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('taxId', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'taxId', 'email', 'phone', 'address']);

        return response()->json($customers);
    }

    public function searchTests(Request $request)
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $tests = Test::where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            })
            ->whereNull('parent')
            ->limit(15)
            ->get(['id', 'code', 'name', 'price', 'unit']);

        return response()->json($tests);
    }

    public function searchServices(Request $request)
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $services = Service::where('active', true)
            ->where('name', 'like', "%{$search}%")
            ->limit(15)
            ->get(['id', 'name', 'price']);

        return response()->json($services);
    }

    public function downloadPdf(Quote $quote)
    {
        abort_if($quote->company_id !== active_company_id(), 403);
        $quote->load(['customer', 'creator', 'items.test']);

        $pdf = PDF::loadView('quote.pdf', compact('quote'), [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        return $pdf->download('Presupuesto_' . $quote->quote_number . '.pdf');
    }

    public function sendEmail(Quote $quote)
    {
        abort_if($quote->company_id !== active_company_id(), 403);
        if (!$quote->customer_email) {
            return back()->with('error', 'El presupuesto no tiene un email de cliente configurado.');
        }

        $quote->load(['customer', 'creator', 'items.test']);

        Mail::to($quote->customer_email)->send(new QuoteMail($quote));

        if ($quote->status === 'draft') {
            $quote->update(['status' => 'sent']);
        }

        return back()->with('success', 'Presupuesto enviado por correo a ' . $quote->customer_email);
    }

    public function updateStatus(Request $request, Quote $quote)
    {
        abort_if($quote->company_id !== active_company_id(), 403);
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected',
        ]);

        $quote->update(['status' => $validated['status']]);

        return back()->with('success', 'Estado actualizado a: ' . $quote->status_label);
    }

    public function duplicate(Quote $quote)
    {
        abort_if($quote->company_id !== active_company_id(), 403);
        $quote->load('items');

        $newQuote = $quote->replicate();
        $newQuote->quote_number = Quote::generateQuoteNumber();
        $newQuote->status = 'draft';
        $newQuote->created_by = auth()->id();
        $newQuote->company_id = active_company_id();
        $newQuote->save();

        foreach ($quote->items as $item) {
            $newItem = $item->replicate();
            $newItem->quote_id = $newQuote->id;
            $newItem->save();
        }

        return redirect()->route('quotes.show', $newQuote)
            ->with('success', 'Presupuesto duplicado como ' . $newQuote->quote_number);
    }
}
