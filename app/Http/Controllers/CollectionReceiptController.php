<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CollectionReceipt;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Services\AccountingEntryService;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CollectionReceiptController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('collection-receipts.index');

        $query = CollectionReceipt::with(['customer', 'creator', 'confirmer', 'payments'])
            ->where('company_id', active_company_id())
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
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

        $companyId = active_company_id();
        $customers = Customer::where('status', 'activo')->orderBy('name')->get();
        $selectedCustomer = null;

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::find($request->customer_id);
        }

        [$invoicesByCustomer, $preloadedInvoiceRows] = $this->buildInvoicesByCustomerPayload($companyId, $customers, $selectedCustomer);

        $bankAccounts = BankAccount::query()
            ->where('company_id', $companyId)
            ->active()
            ->with('accountingAccount')
            ->orderBy('bank_name')
            ->orderBy('account_number')
            ->get();

        return view('collection-receipts.create', compact(
            'customers',
            'selectedCustomer',
            'invoicesByCustomer',
            'preloadedInvoiceRows',
            'bankAccounts'
        ));
    }

    public function store(Request $request)
    {
        $this->authorize('collection-receipts.create');

        $validated = $this->validateCollectionReceiptRequest($request);

        DB::beginTransaction();
        try {
            $collectionReceipt = CollectionReceipt::create([
                'number' => CollectionReceipt::generateNumber(),
                'company_id' => active_company_id(),
                'customer_id' => $validated['customer_id'],
                'date' => $validated['date'],
                'payment_method' => null,
                'payment_reference' => null,
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
            $this->assertReceiptTotalsMatch(
                $validated['payments'],
                $validated['withholdings'],
                (float) $collectionReceipt->total
            );
            $this->replacePayments($collectionReceipt, $validated['payments']);
            $this->replaceWithholdings($collectionReceipt, $validated['withholdings']);
            $this->syncLegacyPaymentHeader($collectionReceipt);

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('collection-receipts.show', $collectionReceipt)
            ->with('success', 'Recibo de cobro '.$collectionReceipt->number.' creado correctamente.');
    }

    public function show(CollectionReceipt $collectionReceipt)
    {
        $this->authorize('collection-receipts.index');

        abort_if($collectionReceipt->company_id !== active_company_id(), 403);

        $collectionReceipt->load(['customer', 'creator', 'confirmer', 'items.invoice.customer', 'payments.bankAccount', 'withholdings']);

        return view('collection-receipts.show', compact('collectionReceipt'));
    }

    public function pdf(CollectionReceipt $collectionReceipt)
    {
        $this->authorize('collection-receipts.index');

        abort_if($collectionReceipt->company_id !== active_company_id(), 403);

        $collectionReceipt->load([
            'customer',
            'company',
            'creator',
            'confirmer',
            'items.invoice',
            'payments.bankAccount',
            'withholdings',
        ]);

        $pdf = DomPdf::loadView('collection-receipts.pdf', compact('collectionReceipt'));
        $pdf->setPaper('A4', 'portrait');

        $safeNumber = preg_replace('/[^\w\-.]+/u', '_', $collectionReceipt->number) ?: 'recibo';

        return $pdf->download('ReciboCobro_'.$safeNumber.'.pdf');
    }

    public function edit(CollectionReceipt $collectionReceipt)
    {
        $this->authorize('collection-receipts.edit');

        abort_if($collectionReceipt->company_id !== active_company_id(), 403);

        if ($collectionReceipt->status !== 'borrador') {
            return redirect()->route('collection-receipts.show', $collectionReceipt)
                ->with('error', 'Solo se pueden editar recibos de cobro en estado borrador.');
        }

        $collectionReceipt->load(['items.invoice', 'payments', 'withholdings']);
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

        $initialPaymentLines = $collectionReceipt->payments->map(fn ($p) => [
            'line_type' => $p->line_type,
            'amount' => (float) $p->amount,
            'bank_account_id' => $p->bank_account_id,
            'cheque_number' => $p->cheque_number ?? '',
            'bank_name' => $p->bank_name ?? '',
            'due_date' => $p->due_date?->format('Y-m-d') ?? '',
        ])->values()->all();

        $initialWithholdingLines = $collectionReceipt->withholdings->map(fn ($w) => [
            'withholding_type' => $w->withholding_type,
            'document_number' => $w->document_number ?? '',
            'regime' => $w->regime,
            'jurisdiction' => $w->jurisdiction ?? '',
            'certificate_number' => $w->certificate_number,
            'amount' => (float) $w->amount,
        ])->values()->all();

        $bankAccounts = BankAccount::query()
            ->where('company_id', active_company_id())
            ->active()
            ->with('accountingAccount')
            ->orderBy('bank_name')
            ->orderBy('account_number')
            ->get();

        return view('collection-receipts.edit', compact(
            'collectionReceipt',
            'customers',
            'pendingInvoices',
            'initialPaymentLines',
            'initialWithholdingLines',
            'bankAccounts'
        ));
    }

    public function update(Request $request, CollectionReceipt $collectionReceipt)
    {
        $this->authorize('collection-receipts.edit');

        abort_if($collectionReceipt->company_id !== active_company_id(), 403);

        if ($collectionReceipt->status !== 'borrador') {
            return redirect()->route('collection-receipts.show', $collectionReceipt)
                ->with('error', 'Solo se pueden editar recibos de cobro en estado borrador.');
        }

        $validated = $this->validateCollectionReceiptRequest($request);

        DB::beginTransaction();
        try {
            $collectionReceipt->update([
                'customer_id' => $validated['customer_id'],
                'date' => $validated['date'],
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
            $this->assertReceiptTotalsMatch(
                $validated['payments'],
                $validated['withholdings'],
                (float) $collectionReceipt->total
            );
            $this->replacePayments($collectionReceipt, $validated['payments']);
            $this->replaceWithholdings($collectionReceipt, $validated['withholdings']);
            $this->syncLegacyPaymentHeader($collectionReceipt);

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

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
        JournalEntry::deleteForSource($collectionReceipt);
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

        $collectionReceipt->load(['payments', 'withholdings']);
        $sumPay = round((float) $collectionReceipt->payments->sum('amount'), 2);
        $sumWh = round((float) $collectionReceipt->withholdings->sum('amount'), 2);
        $total = round((float) $collectionReceipt->total, 2);
        if (abs($sumPay + $sumWh - $total) > 0.01) {
            return back()->with('error', 'La suma de medios de pago y retenciones no coincide con el total del recibo. Editá el borrador antes de confirmar.');
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

            return back()->with('error', 'Error al confirmar el recibo de cobro: '.$e->getMessage());
        }

        try {
            $collectionReceipt->refresh();
            if (! JournalEntry::where('source_type', CollectionReceipt::class)->where('source_id', $collectionReceipt->id)->exists()) {
                (new AccountingEntryService)->fromCollectionReceipt(
                    $collectionReceipt->load(['customer', 'payments.bankAccount.accountingAccount', 'withholdings'])
                );
            }
        } catch (\Throwable $e) {
            Log::error('Error generando asiento para RC #'.$collectionReceipt->id.': '.$e->getMessage());
        }

        return back()->with('success', 'Recibo de cobro '.$collectionReceipt->number.' confirmado.');
    }

    /**
     * @return array{0: array<int, array<int, array<string, mixed>>>, 1: array<int, array<string, mixed>>|null}
     */
    private function buildInvoicesByCustomerPayload(int $companyId, $customers, ?Customer $selectedCustomer): array
    {
        $invoicesByCustomer = [];
        foreach ($customers as $cust) {
            $invoicesByCustomer[$cust->id] = [];
        }

        $rows = SalesInvoice::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['pendiente', 'parcialmente_cobrada'])
            ->orderByDesc('issue_date')
            ->get();

        foreach ($rows as $inv) {
            if (! isset($invoicesByCustomer[$inv->customer_id])) {
                $invoicesByCustomer[$inv->customer_id] = [];
            }
            $invoicesByCustomer[$inv->customer_id][] = $this->invoiceRowForAlpine($inv);
        }

        $preloadedInvoiceRows = null;
        if ($selectedCustomer && ! empty($invoicesByCustomer[$selectedCustomer->id] ?? [])) {
            $preloadedInvoiceRows = $invoicesByCustomer[$selectedCustomer->id];
        }

        return [$invoicesByCustomer, $preloadedInvoiceRows];
    }

    private function invoiceRowForAlpine(SalesInvoice $inv): array
    {
        return [
            'id' => $inv->id,
            'full_number' => $inv->full_number,
            'issue_date' => $inv->issue_date->format('d/m/Y'),
            'total' => (float) $inv->total,
            'amount_collected' => (float) $inv->amount_collected,
            'balance' => (float) $inv->balance,
            'selected' => false,
            'amount' => (float) $inv->balance,
        ];
    }

    private function validateCollectionReceiptRequest(Request $request): array
    {
        $companyId = active_company_id();

        $payments = $request->input('payments', []);
        foreach ($payments as $i => $p) {
            $type = $p['line_type'] ?? '';
            if ($type !== 'transferencia') {
                $payments[$i]['bank_account_id'] = null;
            }
            if ($type !== 'echeq') {
                $payments[$i]['cheque_number'] = null;
                $payments[$i]['bank_name'] = null;
                $payments[$i]['due_date'] = null;
            }
        }
        $request->merge(['payments' => $payments]);

        $withholdings = $request->input('withholdings', []);
        if (! is_array($withholdings)) {
            $withholdings = [];
        }
        $request->merge(['withholdings' => array_values($withholdings)]);

        $base = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'invoices' => 'required|array|min:1',
            'invoices.*.sales_invoice_id' => 'required|exists:sales_invoices,id',
            'invoices.*.amount' => 'required|numeric|min:0.01',
            'payments' => 'required|array|min:1',
            'payments.*.line_type' => 'required|in:efectivo,transferencia,echeq',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payments.*.cheque_number' => 'nullable|string|max:191',
            'payments.*.bank_name' => 'nullable|string|max:191',
            'payments.*.due_date' => 'nullable|date',
            'withholdings' => 'nullable|array',
            'withholdings.*.withholding_type' => 'required|in:ganancias,iva,suss_931,iibb',
            'withholdings.*.document_number' => 'nullable|string|max:191',
            'withholdings.*.regime' => 'required|string|max:255',
            'withholdings.*.jurisdiction' => 'nullable|string|max:191',
            'withholdings.*.certificate_number' => 'required|string|max:191',
            'withholdings.*.amount' => 'required|numeric|min:0.01',
        ], [
            'customer_id.required' => 'Debe seleccionar un cliente.',
            'date.required' => 'La fecha es obligatoria.',
            'invoices.required' => 'Debe agregar al menos una factura.',
            'invoices.min' => 'Debe agregar al menos una factura.',
            'invoices.*.sales_invoice_id.required' => 'Debe seleccionar una factura.',
            'invoices.*.amount.required' => 'El monto es obligatorio.',
            'invoices.*.amount.min' => 'El monto debe ser mayor a 0.',
            'payments.required' => 'Debe indicar al menos un medio de pago.',
            'payments.min' => 'Debe indicar al menos un medio de pago.',
            'withholdings.*.withholding_type.required' => 'Indicá el tipo de retención.',
            'withholdings.*.regime.required' => 'Indicá el régimen de retención.',
            'withholdings.*.certificate_number.required' => 'Indicá el número de certificado.',
            'withholdings.*.amount.required' => 'Indicá el monto retenido.',
            'withholdings.*.amount.min' => 'El monto retenido debe ser mayor a 0.',
        ]);

        $base['withholdings'] = $base['withholdings'] ?? [];

        foreach ($base['withholdings'] as $i => $w) {
            if (($w['withholding_type'] ?? '') === 'iibb' && empty(trim((string) ($w['jurisdiction'] ?? '')))) {
                throw ValidationException::withMessages([
                    "withholdings.{$i}.jurisdiction" => 'La jurisdicción es obligatoria para retenciones IIBB.',
                ]);
            }
        }

        foreach ($base['payments'] as $i => $p) {
            if ($p['line_type'] === 'transferencia') {
                if (empty($p['bank_account_id'])) {
                    throw ValidationException::withMessages([
                        "payments.{$i}.bank_account_id" => 'Seleccioná la cuenta bancaria donde se acreditó el cobro.',
                    ]);
                }
                $ba = BankAccount::query()->where('id', $p['bank_account_id'])->where('company_id', $companyId)->first();
                if (! $ba) {
                    throw ValidationException::withMessages([
                        "payments.{$i}.bank_account_id" => 'La cuenta bancaria no pertenece a la empresa activa.',
                    ]);
                }
            }
            if ($p['line_type'] === 'echeq') {
                if (empty($p['cheque_number']) || empty($p['bank_name']) || empty($p['due_date'])) {
                    throw ValidationException::withMessages([
                        "payments.{$i}.cheque_number" => 'Completá número, banco y vencimiento del e-cheq.',
                    ]);
                }
            }
        }

        return $base;
    }

    /**
     * @param  array<int, array<string, mixed>>  $payments
     * @param  array<int, array<string, mixed>>  $withholdings
     */
    private function assertReceiptTotalsMatch(array $payments, array $withholdings, float $total): void
    {
        $sumPay = round(array_sum(array_map(fn ($p) => (float) $p['amount'], $payments)), 2);
        $sumWh = round(array_sum(array_map(fn ($w) => (float) $w['amount'], $withholdings)), 2);
        $sum = round($sumPay + $sumWh, 2);
        $total = round($total, 2);
        if (abs($sum - $total) > 0.01) {
            throw ValidationException::withMessages([
                'payments' => 'Medios ($'.number_format($sumPay, 2, ',', '.').') + retenciones ($'.number_format($sumWh, 2, ',', '.').') = $'.number_format($sum, 2, ',', '.').' debe igualar el total del recibo ($'.number_format($total, 2, ',', '.').').',
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $payments
     */
    private function replacePayments(CollectionReceipt $collectionReceipt, array $payments): void
    {
        $collectionReceipt->payments()->delete();
        foreach ($payments as $idx => $p) {
            $collectionReceipt->payments()->create([
                'line_type' => $p['line_type'],
                'amount' => $p['amount'],
                'bank_account_id' => $p['line_type'] === 'transferencia' ? $p['bank_account_id'] : null,
                'cheque_number' => $p['line_type'] === 'echeq' ? $p['cheque_number'] : null,
                'bank_name' => $p['line_type'] === 'echeq' ? $p['bank_name'] : null,
                'due_date' => $p['line_type'] === 'echeq' ? $p['due_date'] : null,
                'sort_order' => $idx,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $withholdings
     */
    private function replaceWithholdings(CollectionReceipt $collectionReceipt, array $withholdings): void
    {
        $collectionReceipt->withholdings()->delete();
        foreach ($withholdings as $idx => $w) {
            $collectionReceipt->withholdings()->create([
                'withholding_type' => $w['withholding_type'],
                'document_number' => $w['document_number'] ?? null,
                'regime' => $w['regime'],
                'jurisdiction' => ! empty(trim((string) ($w['jurisdiction'] ?? ''))) ? $w['jurisdiction'] : null,
                'certificate_number' => $w['certificate_number'],
                'amount' => $w['amount'],
                'sort_order' => $idx,
            ]);
        }
    }

    private function syncLegacyPaymentHeader(CollectionReceipt $collectionReceipt): void
    {
        $collectionReceipt->load('payments.bankAccount');
        $first = $collectionReceipt->payments->sortBy(fn ($p) => [$p->sort_order, $p->id])->first();
        if (! $first) {
            $collectionReceipt->forceFill(['payment_method' => null, 'payment_reference' => null])->saveQuietly();

            return;
        }
        $method = match ($first->line_type) {
            'efectivo' => 'efectivo',
            'transferencia' => 'transferencia',
            'echeq' => 'cheque',
        };
        $ref = match ($first->line_type) {
            'echeq' => $first->cheque_number,
            'transferencia' => $first->bank_account_id ? (string) $first->bank_account_id : null,
            default => null,
        };
        $collectionReceipt->forceFill([
            'payment_method' => $method,
            'payment_reference' => $ref ? mb_substr((string) $ref, 0, 255) : null,
        ])->saveQuietly();
    }
}
