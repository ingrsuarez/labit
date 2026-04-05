<?php

namespace App\Services;

use App\Models\AccountingAccount;
use App\Models\BankAccount;
use App\Models\CollectionReceipt;
use App\Models\CreditNote;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PaymentOrder;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingEntryService
{
    private function account(string $code): ?AccountingAccount
    {
        return AccountingAccount::where('code', $code)->where('is_active', true)->first();
    }

    private function createEntry(
        int $companyId,
        Carbon $date,
        string $description,
        Model $source,
        array $lines
    ): ?JournalEntry {
        $resolvedLines = [];
        foreach ($lines as $line) {
            $account = $this->account($line['account_code']);
            if (! $account) {
                Log::warning("AccountingEntryService: cuenta '{$line['account_code']}' no encontrada. Asiento no generado para ".get_class($source)." #{$source->id}");

                return null;
            }
            $resolvedLines[] = array_merge($line, ['account_id' => $account->id]);
        }

        $totalDebit = collect($resolvedLines)->sum('debit');
        $totalCredit = collect($resolvedLines)->sum('credit');
        if (abs($totalDebit - $totalCredit) > 0.01) {
            Log::warning('AccountingEntryService: asiento no balanceado (DB:'.$totalDebit.' CR:'.$totalCredit.') para '.get_class($source)." #{$source->id}");

            return null;
        }

        return DB::transaction(function () use ($companyId, $date, $description, $source, $resolvedLines) {
            $entry = JournalEntry::create([
                'company_id' => $companyId,
                'date' => $date,
                'number' => JournalEntry::nextNumber($companyId, (int) $date->format('Y')),
                'description' => $description,
                'source_type' => get_class($source),
                'source_id' => $source->id,
                'is_automatic' => true,
                'created_by' => Auth::id(),
            ]);

            foreach ($resolvedLines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'accounting_account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry;
        });
    }

    public function fromSalesInvoice(SalesInvoice $invoice): ?JournalEntry
    {
        $invoice->loadMissing('customer', 'pointOfSale');

        $ivaTotal = round(
            (float) $invoice->iva_21 + (float) $invoice->iva_10_5 + (float) $invoice->iva_27,
            2
        );
        $netPlusOther = round(
            (float) $invoice->subtotal + (float) $invoice->percepciones + (float) $invoice->otros_impuestos,
            2
        );
        $total = round((float) $invoice->total, 2);

        $lines = [
            [
                'account_code' => '1.1.04',
                'debit' => $total,
                'credit' => 0,
                'description' => 'FC '.$invoice->full_number.' — '.($invoice->customer->name ?? ''),
            ],
            [
                'account_code' => '4.1.01',
                'debit' => 0,
                'credit' => $netPlusOther,
                'description' => 'Venta neta y otros',
            ],
        ];

        if ($ivaTotal > 0) {
            $lines[] = [
                'account_code' => '2.1.03',
                'debit' => 0,
                'credit' => $ivaTotal,
                'description' => 'IVA débito fiscal',
            ];
        }

        return $this->createEntry(
            (int) $invoice->company_id,
            Carbon::parse($invoice->issue_date),
            'Venta — FC '.$invoice->full_number,
            $invoice,
            $lines
        );
    }

    public function fromCreditNote(CreditNote $creditNote): ?JournalEntry
    {
        $creditNote->loadMissing('customer', 'pointOfSale');

        $ivaTotal = round(
            (float) $creditNote->iva_21 + (float) $creditNote->iva_10_5 + (float) $creditNote->iva_27,
            2
        );
        $netPlusOther = round(
            (float) $creditNote->subtotal + (float) $creditNote->percepciones + (float) $creditNote->otros_impuestos,
            2
        );
        $total = round((float) $creditNote->total, 2);

        $lines = [
            [
                'account_code' => '4.1.01',
                'debit' => $netPlusOther,
                'credit' => 0,
                'description' => 'NC '.$creditNote->full_number.' — '.($creditNote->customer->name ?? ''),
            ],
            [
                'account_code' => '1.1.04',
                'debit' => 0,
                'credit' => $total,
                'description' => 'NC '.$creditNote->full_number,
            ],
        ];

        if ($ivaTotal > 0) {
            $lines[] = [
                'account_code' => '2.1.03',
                'debit' => $ivaTotal,
                'credit' => 0,
                'description' => 'IVA débito fiscal NC',
            ];
        }

        return $this->createEntry(
            (int) $creditNote->company_id,
            Carbon::parse($creditNote->issue_date),
            'Nota de crédito — '.$creditNote->full_number,
            $creditNote,
            $lines
        );
    }

    public function fromCollectionReceipt(CollectionReceipt $receipt): ?JournalEntry
    {
        $receipt->loadMissing('customer');
        $companyId = (int) ($receipt->company_id ?? active_company_id());
        $method = $receipt->payment_method ?? 'efectivo';
        $bankAccountCode = $this->resolveBankAccountCode($method, null);

        return $this->createEntry(
            $companyId,
            Carbon::parse($receipt->date),
            'Cobro — RC '.$receipt->number,
            $receipt,
            [
                [
                    'account_code' => $bankAccountCode,
                    'debit' => round((float) $receipt->total, 2),
                    'credit' => 0,
                    'description' => 'Cobro RC '.$receipt->number.' — '.($receipt->customer->name ?? ''),
                ],
                [
                    'account_code' => '1.1.04',
                    'debit' => 0,
                    'credit' => round((float) $receipt->total, 2),
                    'description' => 'Cancelación deuda RC '.$receipt->number,
                ],
            ]
        );
    }

    public function fromPurchaseInvoice(PurchaseInvoice $invoice): ?JournalEntry
    {
        $invoice->loadMissing(['items', 'supplier']);

        $itemsWithSupply = $invoice->items->whereNotNull('supply_id');
        $itemsWithoutSupply = $invoice->items->whereNull('supply_id');

        $netoInsumos = round($itemsWithSupply->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_price), 2);
        $netoServicios = round($itemsWithoutSupply->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_price), 2);

        $totalIva = round(
            (float) $invoice->iva_21 + (float) $invoice->iva_10_5 + (float) $invoice->iva_27,
            2
        );
        $totalBruto = round((float) $invoice->total, 2);

        $percOtros = round((float) $invoice->percepciones + (float) $invoice->otros_impuestos, 2);

        $lines = [];

        if ($netoInsumos > 0) {
            $lines[] = [
                'account_code' => '5.1.01',
                'debit' => $netoInsumos,
                'credit' => 0,
                'description' => 'Insumos — FC compra '.$invoice->full_number,
            ];
        }

        $netoServiciosPlus = $netoServicios + $percOtros;
        if ($netoServiciosPlus > 0) {
            $lines[] = [
                'account_code' => '5.1.02',
                'debit' => $netoServiciosPlus,
                'credit' => 0,
                'description' => 'Servicios / otros — FC compra '.$invoice->full_number,
            ];
        }

        if ($totalIva > 0) {
            $lines[] = [
                'account_code' => '1.1.06',
                'debit' => $totalIva,
                'credit' => 0,
                'description' => 'IVA crédito fiscal',
            ];
        }

        $lines[] = [
            'account_code' => '2.1.01',
            'debit' => 0,
            'credit' => $totalBruto,
            'description' => 'Deuda proveedor '.($invoice->supplier->name ?? '').' — FC '.$invoice->full_number,
        ];

        return $this->createEntry(
            (int) $invoice->company_id,
            Carbon::parse($invoice->issue_date),
            'Compra — FC '.$invoice->full_number,
            $invoice,
            $lines
        );
    }

    public function fromPaymentOrder(PaymentOrder $paymentOrder): ?JournalEntry
    {
        $paymentOrder->loadMissing('supplier');
        $bankAccountCode = $this->resolveBankAccountCode(
            $paymentOrder->payment_method ?? 'efectivo',
            null
        );

        return $this->createEntry(
            (int) $paymentOrder->company_id,
            Carbon::parse($paymentOrder->date),
            'Pago OP '.$paymentOrder->number,
            $paymentOrder,
            [
                [
                    'account_code' => '2.1.01',
                    'debit' => round((float) $paymentOrder->total, 2),
                    'credit' => 0,
                    'description' => 'Cancelación deuda '.($paymentOrder->supplier->name ?? ''),
                ],
                [
                    'account_code' => $bankAccountCode,
                    'debit' => 0,
                    'credit' => round((float) $paymentOrder->total, 2),
                    'description' => 'OP '.$paymentOrder->number.' — '.($paymentOrder->supplier->name ?? ''),
                ],
            ]
        );
    }

    private function resolveBankAccountCode(?string $paymentMethod, ?int $bankAccountId): string
    {
        if ($paymentMethod === 'efectivo') {
            return '1.1.01';
        }

        if ($bankAccountId) {
            $bankAccount = BankAccount::with('accountingAccount')->find($bankAccountId);
            if ($bankAccount?->accountingAccount) {
                return $bankAccount->accountingAccount->code;
            }
        }

        return '1.1.02';
    }
}
