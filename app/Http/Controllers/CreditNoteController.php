<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\PointOfSale;
use App\Models\SalesInvoice;
use App\Services\AccountingEntryService;
use App\Services\AfipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;

class CreditNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = CreditNote::with(['customer', 'salesInvoice', 'pointOfSale'])
            ->where('company_id', active_company_id())
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
            ->where('company_id', active_company_id())
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

        $invoice = SalesInvoice::with(['pointOfSale', 'customer', 'company'])
            ->where('company_id', active_company_id())
            ->findOrFail($request->sales_invoice_id);
        $pointOfSale = $invoice->pointOfSale;
        $isElectronic = $pointOfSale && $pointOfSale->is_electronic;

        try {
            $creditNote = DB::transaction(function () use ($request, $invoice, $isElectronic) {
                $creditNote = CreditNote::create([
                    'company_id' => active_company_id(),
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

                return $creditNote->fresh([
                    'items',
                    'company',
                    'pointOfSale',
                    'customer',
                    'salesInvoice' => fn ($q) => $q->with('pointOfSale'),
                ]);
            });

            if (! $isElectronic) {
                $this->recordCreditNoteJournalEntry($creditNote);

                return redirect()->route('credit-notes.show', $creditNote)
                    ->with('success', 'Nota de crédito creada exitosamente.');
            }

            $creditNote->load(['items', 'pointOfSale', 'customer', 'company', 'salesInvoice.pointOfSale']);

            $afipResponse = $this->afipServiceForCreditNote($creditNote)->createCreditNote($creditNote);

            if (in_array($afipResponse['result'], ['A', 'O'], true)) {
                DB::transaction(function () use ($creditNote, $afipResponse) {
                    $creditNote->update([
                        'cae' => $afipResponse['cae'],
                        'cae_expiration' => $afipResponse['cae_expiration'],
                        'afip_voucher_number' => $afipResponse['voucher_number'],
                        'afip_result' => $afipResponse['result'],
                        'afip_response' => $afipResponse['full_response'],
                    ]);
                    $number = str_pad((string) $afipResponse['voucher_number'], 8, '0', STR_PAD_LEFT);
                    $creditNote->update([
                        'credit_note_number' => $number,
                        'status' => 'confirmada',
                    ]);
                });

                $creditNote->refresh();
                $this->recordCreditNoteJournalEntry($creditNote);

                return redirect()->route('credit-notes.show', $creditNote)
                    ->with('success', 'Nota de crédito creada exitosamente.');
            }

            DB::transaction(function () use ($creditNote, $afipResponse) {
                $creditNote->update([
                    'cae' => $afipResponse['cae'],
                    'cae_expiration' => $afipResponse['cae_expiration'],
                    'afip_voucher_number' => $afipResponse['voucher_number'],
                    'afip_result' => $afipResponse['result'],
                    'afip_response' => $afipResponse['full_response'],
                ]);
            });

            $creditNote->refresh();

            return redirect()->route('credit-notes.show', $creditNote)
                ->with('error', 'AFIP rechazó la nota de crédito. '.$this->afipRejectionObservationsText($afipResponse));
        } catch (\Exception $e) {
            Log::error('Credit note creation failed', ['error' => $e->getMessage()]);

            return back()->withInput()
                ->with('error', 'Error al crear la nota de crédito: '.$e->getMessage());
        }
    }

    public function show(CreditNote $creditNote)
    {
        abort_if($creditNote->company_id !== active_company_id(), 403);
        $creditNote->load(['items', 'customer', 'pointOfSale', 'salesInvoice', 'creator']);

        return view('credit-notes.show', compact('creditNote'));
    }

    public function pdf(CreditNote $creditNote)
    {
        $this->authorize('sales-invoices.index');
        abort_if($creditNote->company_id !== active_company_id(), 403);

        if ($creditNote->status !== 'confirmada') {
            return redirect()->route('credit-notes.show', $creditNote)
                ->with('error', 'Solo se puede descargar el PDF de notas de crédito confirmadas.');
        }

        $creditNote->load(['customer', 'pointOfSale', 'items', 'creator', 'company', 'salesInvoice.pointOfSale']);

        $qrDataUri = null;
        $barcodeComplete = null;

        if ($creditNote->cae) {
            $company = $creditNote->company;
            $cuit = $company ? str_replace('-', '', $company->cuit) : config('afip.cuit');
            $pos = $creditNote->pointOfSale;
            $afipCodes = ['A' => '03', 'B' => '08', 'C' => '13'];

            $barcode = $cuit
                .str_pad($afipCodes[$creditNote->voucher_type] ?? '00', 3, '0', STR_PAD_LEFT)
                .str_pad($pos ? $pos->afip_pos_number : '1', 5, '0', STR_PAD_LEFT)
                .$creditNote->cae
                .($creditNote->cae_expiration ? $creditNote->cae_expiration->format('Ymd') : '');
            $sumOdd = 0;
            $sumEven = 0;
            for ($i = 0; $i < strlen($barcode); $i++) {
                if (($i + 1) % 2 === 0) {
                    $sumEven += intval($barcode[$i]);
                } else {
                    $sumOdd += intval($barcode[$i]);
                }
            }
            $barcodeComplete = $barcode.((10 - (($sumOdd + $sumEven * 3) % 10)) % 10);

            $customer = $creditNote->customer;
            $netAmount = $creditNote->items->sum(fn ($i) => $i->quantity * $i->unit_price);
            $totalIva = $creditNote->items->sum('iva_amount');
            $voucherNumber = (int) ($creditNote->afip_voucher_number ?? preg_replace('/\D/', '', $creditNote->credit_note_number));

            $qrJson = json_encode([
                'ver' => 1,
                'fecha' => $creditNote->issue_date->format('Y-m-d'),
                'cuit' => (int) $cuit,
                'ptoVta' => $pos ? $pos->afip_pos_number : 1,
                'tipoCmp' => (int) ($afipCodes[$creditNote->voucher_type] ?? 0),
                'nroCmp' => $voucherNumber,
                'importe' => round($netAmount + $totalIva + $creditNote->percepciones + $creditNote->otros_impuestos, 2),
                'moneda' => 'PES',
                'ctz' => 1,
                'tipoDocRec' => $customer->tax && strtolower($customer->tax) === 'consumidor final' ? 99 : 80,
                'nroDocRec' => (int) str_replace('-', '', $customer->taxId ?? '0'),
                'tipoCodAut' => 'E',
                'codAut' => (int) $creditNote->cae,
            ]);
            $qrUrl = 'https://www.afip.gob.ar/fe/qr/?p='.base64_encode($qrJson);

            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd
            );
            $qrSvg = (new \BaconQrCode\Writer($renderer))->writeString($qrUrl);
            $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);
        }

        $hasFooter = $creditNote->cae && $qrDataUri;

        $pdf = PDF::loadView('credit-notes.pdf', [
            'creditNote' => $creditNote,
            'qrDataUri' => $qrDataUri,
            'barcodeComplete' => $barcodeComplete,
        ], [], [
            'margin_top' => 10,
            'margin_bottom' => $hasFooter ? 52 : 15,
            'margin_footer' => $hasFooter ? 5 : 5,
            'margin_left' => 12,
            'margin_right' => 12,
            'format' => 'A4',
        ]);

        $posCode = $creditNote->pointOfSale ? $creditNote->pointOfSale->code : '00000';
        $number = str_pad(
            (string) ($creditNote->afip_voucher_number ?? preg_replace('/\D/', '', $creditNote->credit_note_number)),
            8,
            '0',
            STR_PAD_LEFT
        );
        $filename = 'NotaCredito_'.$creditNote->voucher_type.'_'.$posCode.'-'.$number.'.pdf';

        return $pdf->download($filename);
    }

    public function destroy(CreditNote $creditNote)
    {
        abort_if($creditNote->company_id !== active_company_id(), 403);
        if ($creditNote->cae || $creditNote->status !== 'pendiente') {
            return back()->with('error', 'No se puede eliminar una nota de crédito autorizada o confirmada.');
        }

        JournalEntry::deleteForSource($creditNote);
        $creditNote->delete();

        return redirect()->route('credit-notes.index')
            ->with('success', 'Nota de crédito eliminada.');
    }

    public function retryAfip(CreditNote $creditNote)
    {
        abort_if($creditNote->company_id !== active_company_id(), 403);
        if (! $creditNote->is_electronic || $creditNote->cae) {
            return back()->with('error', 'Esta nota de crédito no requiere autorización AFIP.');
        }

        $creditNote->load(['pointOfSale', 'customer', 'items', 'salesInvoice.pointOfSale', 'company']);

        try {
            $afipResponse = $this->afipServiceForCreditNote($creditNote)->createCreditNote($creditNote);

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
                $this->recordCreditNoteJournalEntry($creditNote->fresh(['customer', 'pointOfSale', 'items']));

                return back()->with('success', 'Nota de crédito autorizada por AFIP. CAE: '.$afipResponse['cae']);
            }

            $errorMsg = 'AFIP rechazó la nota de crédito.';
            if (! empty($afipResponse['observations'])) {
                $obs = collect($afipResponse['observations'])->flatten()->implode(' | ');
                $errorMsg .= ' Observaciones: '.$obs;
            }

            return back()->with('error', $errorMsg);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al conectar con AFIP: '.$e->getMessage());
        }
    }

    public function createManual()
    {
        $customers = Customer::where('status', 'activo')
            ->orderBy('name')
            ->get(['id', 'name', 'tax', 'taxId']);

        $pointsOfSale = PointOfSale::where('company_id', active_company_id())
            ->where('is_electronic', false)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('credit-notes.create-manual', compact('customers', 'pointsOfSale'));
    }

    public function storeManual(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'voucher_type' => 'required|in:A,B,C',
            'point_of_sale_id' => 'required|exists:points_of_sale,id',
            'credit_note_number' => 'required|string|max:20',
            'issue_date' => 'required|date',
            'reason' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.iva_rate' => 'required|numeric|in:0,10.5,21,27',
        ]);

        $pos = PointOfSale::where('company_id', active_company_id())->findOrFail($request->point_of_sale_id);
        abort_if($pos->is_electronic, 422, 'Para NC electrónicas, generarlas desde la factura de venta.');

        DB::beginTransaction();

        try {
            $creditNote = CreditNote::create([
                'company_id' => active_company_id(),
                'credit_note_number' => $request->credit_note_number,
                'voucher_type' => $request->voucher_type,
                'point_of_sale_id' => $pos->id,
                'customer_id' => $request->customer_id,
                'sales_invoice_id' => null,
                'issue_date' => $request->issue_date,
                'reason' => $request->reason,
                'percepciones' => $request->percepciones ?? 0,
                'otros_impuestos' => $request->otros_impuestos ?? 0,
                'status' => 'confirmada',
                'is_electronic' => false,
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

            DB::commit();

            $this->recordCreditNoteJournalEntry($creditNote);

            return redirect()->route('credit-notes.show', $creditNote)
                ->with('success', 'Nota de crédito registrada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manual credit note creation failed', ['error' => $e->getMessage()]);

            return back()->withInput()
                ->with('error', 'Error al registrar la nota de crédito: '.$e->getMessage());
        }
    }

    protected function afipServiceForCreditNote(CreditNote $creditNote): AfipService
    {
        $creditNote->loadMissing('company');

        if (! $creditNote->company) {
            throw new \RuntimeException('La nota de crédito no tiene empresa asociada.');
        }

        if (app()->runningUnitTests() && app()->bound(AfipService::class)) {
            return app(AfipService::class);
        }

        return new AfipService($creditNote->company);
    }

    protected function afipRejectionObservationsText(array $afipResponse): string
    {
        $obs = '';
        if (isset($afipResponse['full_response']['FeDetResp']['FECAEDetResponse']['Observaciones'])) {
            $observations = $afipResponse['full_response']['FeDetResp']['FECAEDetResponse']['Observaciones'];
            if (isset($observations['Obs'])) {
                $obsList = is_array($observations['Obs']) && isset($observations['Obs'][0])
                    ? $observations['Obs']
                    : [$observations['Obs']];
                foreach ($obsList as $ob) {
                    $obs .= ($ob['Msg'] ?? '').' ';
                }
            }
        }

        return trim($obs);
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

    protected function recordCreditNoteJournalEntry(CreditNote $creditNote): void
    {
        try {
            if ($creditNote->is_electronic && $creditNote->status !== 'confirmada') {
                return;
            }
            if (JournalEntry::where('source_type', CreditNote::class)->where('source_id', $creditNote->id)->exists()) {
                return;
            }
            (new AccountingEntryService)->fromCreditNote($creditNote);
        } catch (\Throwable $e) {
            Log::error('Error generando asiento para NC #'.$creditNote->id.': '.$e->getMessage());
        }
    }
}
