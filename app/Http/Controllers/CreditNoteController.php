<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\SalesInvoice;
use App\Services\AfipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = CreditNote::with(['customer', 'salesInvoice', 'pointOfSale'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('credit_note_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $creditNotes = $query->paginate(15)->withQueryString();

        return view('credit-notes.index', compact('creditNotes'));
    }

    public function create(Request $request)
    {
        $request->validate(['sales_invoice_id' => 'required|exists:sales_invoices,id']);

        $invoice = SalesInvoice::with(['items', 'customer', 'pointOfSale'])
            ->findOrFail($request->sales_invoice_id);

        return view('credit-notes.create', compact('invoice'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,id',
            'reason' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.iva_rate' => 'required|numeric|in:0,10.5,21,27',
        ]);

        $invoice = SalesInvoice::with(['pointOfSale', 'customer'])->findOrFail($request->sales_invoice_id);
        $pointOfSale = $invoice->pointOfSale;
        $isElectronic = $pointOfSale && $pointOfSale->is_electronic;

        DB::beginTransaction();

        try {
            $creditNote = CreditNote::create([
                'credit_note_number' => $isElectronic ? 'PENDIENTE-AFIP' : $this->getNextLocalNumber($invoice),
                'voucher_type' => $invoice->voucher_type,
                'point_of_sale_id' => $invoice->point_of_sale_id,
                'customer_id' => $invoice->customer_id,
                'sales_invoice_id' => $invoice->id,
                'issue_date' => now()->toDateString(),
                'reason' => $request->reason,
                'percepciones' => $request->percepciones ?? 0,
                'otros_impuestos' => $request->otros_impuestos ?? 0,
                'status' => 'pendiente',
                'is_electronic' => $isElectronic,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $i => $itemData) {
                $qty = floatval($itemData['quantity']);
                $price = floatval($itemData['unit_price']);
                $ivaRate = floatval($itemData['iva_rate']);
                $ivaAmount = round($qty * $price * $ivaRate / 100, 2);
                $total = round($qty * $price + $ivaAmount, 2);

                CreditNoteItem::create([
                    'credit_note_id' => $creditNote->id,
                    'description' => $itemData['description'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'iva_rate' => $ivaRate,
                    'iva_amount' => $ivaAmount,
                    'total' => $total,
                    'sort_order' => $i,
                ]);
            }

            $creditNote->recalculate();

            if ($isElectronic) {
                $afip = new AfipService();
                $afipResponse = $afip->createCreditNote($creditNote);

                $creditNote->update([
                    'cae' => $afipResponse['cae'],
                    'cae_expiration' => $afipResponse['cae_expiration'],
                    'afip_voucher_number' => $afipResponse['voucher_number'],
                    'afip_result' => $afipResponse['result'],
                    'afip_response' => $afipResponse['full_response'],
                ]);

                if (in_array($afipResponse['result'], ['A', 'O'])) {
                    $number = str_pad($afipResponse['voucher_number'], 8, '0', STR_PAD_LEFT);
                    $creditNote->update([
                        'credit_note_number' => $number,
                        'status' => 'confirmada',
                    ]);
                } else {
                    DB::commit();
                    $obs = '';
                    if (isset($afipResponse['full_response']['FeDetResp']['FECAEDetResponse']['Observaciones'])) {
                        $observations = $afipResponse['full_response']['FeDetResp']['FECAEDetResponse']['Observaciones'];
                        if (isset($observations['Obs'])) {
                            $obsList = is_array($observations['Obs']) && isset($observations['Obs'][0])
                                ? $observations['Obs']
                                : [$observations['Obs']];
                            foreach ($obsList as $ob) {
                                $obs .= ($ob['Msg'] ?? '') . ' ';
                            }
                        }
                    }
                    return redirect()->route('credit-notes.show', $creditNote)
                        ->with('error', 'AFIP rechazó la nota de crédito. ' . trim($obs));
                }
            }

            DB::commit();

            return redirect()->route('credit-notes.show', $creditNote)
                ->with('success', 'Nota de crédito creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Credit note creation failed', ['error' => $e->getMessage()]);
            return back()->withInput()
                ->with('error', 'Error al crear la nota de crédito: ' . $e->getMessage());
        }
    }

    public function show(CreditNote $creditNote)
    {
        $creditNote->load(['items', 'customer', 'pointOfSale', 'salesInvoice', 'creator']);
        return view('credit-notes.show', compact('creditNote'));
    }

    public function destroy(CreditNote $creditNote)
    {
        if ($creditNote->cae || $creditNote->status !== 'pendiente') {
            return back()->with('error', 'No se puede eliminar una nota de crédito autorizada o confirmada.');
        }

        $creditNote->delete();
        return redirect()->route('credit-notes.index')
            ->with('success', 'Nota de crédito eliminada.');
    }

    public function retryAfip(CreditNote $creditNote)
    {
        if (! $creditNote->is_electronic || $creditNote->cae) {
            return back()->with('error', 'Esta nota de crédito no requiere autorización AFIP.');
        }

        $creditNote->load(['pointOfSale', 'customer', 'items', 'salesInvoice.pointOfSale']);

        try {
            $afip = new AfipService();
            $afipResponse = $afip->createCreditNote($creditNote);

            $creditNote->update([
                'cae' => $afipResponse['cae'],
                'cae_expiration' => $afipResponse['cae_expiration'],
                'afip_voucher_number' => $afipResponse['voucher_number'],
                'afip_result' => $afipResponse['result'],
                'afip_response' => $afipResponse['full_response'],
            ]);

            if (in_array($afipResponse['result'], ['A', 'O'])) {
                $number = str_pad($afipResponse['voucher_number'], 8, '0', STR_PAD_LEFT);
                $creditNote->update([
                    'credit_note_number' => $number,
                    'status' => 'confirmada',
                ]);
                return back()->with('success', 'Nota de crédito autorizada por AFIP. CAE: ' . $afipResponse['cae']);
            }

            return back()->with('error', 'AFIP rechazó la nota de crédito.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al conectar con AFIP: ' . $e->getMessage());
        }
    }

    protected function getNextLocalNumber(SalesInvoice $invoice): string
    {
        $lastNumber = CreditNote::where('point_of_sale_id', $invoice->point_of_sale_id)
            ->where('voucher_type', $invoice->voucher_type)
            ->where('is_electronic', false)
            ->max('credit_note_number');

        $next = $lastNumber ? intval($lastNumber) + 1 : 1;
        return str_pad($next, 8, '0', STR_PAD_LEFT);
    }
}
