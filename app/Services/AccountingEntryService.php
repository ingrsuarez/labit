<?php

namespace App\Services;

use App\Models\AccountingAccount;
use App\Models\BankAccount;
use App\Models\CollectionReceipt;
use App\Models\CollectionReceiptWithholding;
use App\Models\CreditNote;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PaymentOrder;
use App\Models\PayrollPayment;
use App\Models\PurchaseCreditNote;
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

    /**
     * Crea un asiento manual/automático desde cualquier modelo fuente (p.ej. DDJJ de impuestos).
     *
     * @param  array<int, array{account_code: string, debit: float|int, credit: float|int, description?: string|null}>  $lines
     */
    public function createEntryForSource(
        int $companyId,
        Carbon $date,
        string $description,
        Model $source,
        array $lines
    ): ?JournalEntry {
        return $this->createEntry($companyId, $date, $description, $source, $lines);
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

    /**
     * Plan de cuentas sugerido para retenciones sufridas en cobro (activo / crédito a recuperar).
     * Ajustar códigos según plan real del laboratorio; centralizado para un solo punto de edición.
     */
    public static function withholdingAccountCode(string $withholdingType): string
    {
        return match ($withholdingType) {
            CollectionReceiptWithholding::TYPE_GANANCIAS => '1.1.05',
            CollectionReceiptWithholding::TYPE_IVA => '1.1.06',
            CollectionReceiptWithholding::TYPE_SUSS_931 => '1.1.07',
            CollectionReceiptWithholding::TYPE_IIBB => '1.1.08',
            default => '1.1.05',
        };
    }

    public function fromCollectionReceipt(CollectionReceipt $receipt): ?JournalEntry
    {
        // Medios líquidos + retenciones = total RC; crédito único a clientes por el total.
        $receipt->loadMissing(['customer', 'payments.bankAccount.accountingAccount', 'withholdings']);
        $companyId = (int) ($receipt->company_id ?? active_company_id());
        $total = round((float) $receipt->total, 2);

        $debitLines = [];

        if ($receipt->payments->isEmpty()) {
            $method = $receipt->payment_method ?? 'efectivo';
            $bankAccountCode = $this->resolveBankAccountCode($method, null);
            $debitLines[] = [
                'account_code' => $bankAccountCode,
                'debit' => $total,
                'credit' => 0,
                'description' => 'Cobro RC '.$receipt->number.' — '.($receipt->customer->name ?? ''),
            ];
        } else {
            foreach ($receipt->payments as $payment) {
                $amt = round((float) $payment->amount, 2);
                if ($amt <= 0) {
                    continue;
                }
                $code = match ($payment->line_type) {
                    'efectivo' => '1.1.01',
                    'transferencia' => $this->resolveBankAccountCode('transferencia', $payment->bank_account_id ? (int) $payment->bank_account_id : null),
                    'echeq' => $this->resolveBankAccountCode('cheque', null),
                    default => '1.1.01',
                };
                $desc = match ($payment->line_type) {
                    'efectivo' => 'Efectivo — RC '.$receipt->number,
                    'transferencia' => 'Transferencia — RC '.$receipt->number,
                    'echeq' => 'E-cheq '.($payment->cheque_number ?? '').' — RC '.$receipt->number,
                    default => 'Cobro RC '.$receipt->number,
                };
                $debitLines[] = [
                    'account_code' => $code,
                    'debit' => $amt,
                    'credit' => 0,
                    'description' => $desc,
                ];
            }
        }

        foreach ($receipt->withholdings as $wh) {
            $amt = round((float) $wh->amount, 2);
            if ($amt <= 0) {
                continue;
            }
            $acc = self::withholdingAccountCode($wh->withholding_type);
            $debitLines[] = [
                'account_code' => $acc,
                'debit' => $amt,
                'credit' => 0,
                'description' => 'Ret. '.CollectionReceiptWithholding::typeLabel($wh->withholding_type).' cert. '.($wh->certificate_number ?? '').' — RC '.$receipt->number,
            ];
        }

        $debitLines[] = [
            'account_code' => '1.1.04',
            'debit' => 0,
            'credit' => $total,
            'description' => 'Cancelación deuda RC '.$receipt->number,
        ];

        return $this->createEntry(
            $companyId,
            Carbon::parse($receipt->date),
            'Cobro — RC '.$receipt->number,
            $receipt,
            $debitLines
        );
    }

    public function fromPurchaseInvoice(PurchaseInvoice $invoice): ?JournalEntry
    {
        $invoice->loadMissing(['items', 'supplier', 'perceptions.accountingAccount']);

        $itemsWithSupply = $invoice->items->whereNotNull('supply_id');
        $itemsWithoutSupply = $invoice->items->whereNull('supply_id');

        $netoInsumos = round($itemsWithSupply->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_price), 2);
        $netoServicios = round($itemsWithoutSupply->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_price), 2);

        $totalIva = round(
            (float) $invoice->iva_21 + (float) $invoice->iva_10_5 + (float) $invoice->iva_27,
            2
        );
        $totalBruto = round((float) $invoice->total, 2);
        $otrosImp = round((float) $invoice->otros_impuestos, 2);

        $lines = [];

        if ($netoInsumos > 0) {
            $lines[] = [
                'account_code' => '5.1.01',
                'debit' => $netoInsumos,
                'credit' => 0,
                'description' => 'Insumos — FC compra '.$invoice->full_number,
            ];
        }

        // Servicios + otros_impuestos (percepciones van a sus cuentas individuales)
        $netoServiciosPlus = $netoServicios + $otrosImp;
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

        // Una línea DB por cada percepción (anticipo sufrido → activo corriente)
        foreach ($invoice->perceptions as $perception) {
            $amt = round((float) $perception->amount, 2);
            if ($amt <= 0 || ! $perception->accountingAccount) {
                continue;
            }
            $lines[] = [
                'account_code' => $perception->accountingAccount->code,
                'debit' => $amt,
                'credit' => 0,
                'description' => 'Anticipo '.$perception->name_snapshot
                    .($perception->jurisdiction_snapshot ? ' ('.$perception->jurisdiction_snapshot.')' : '')
                    .' — FC '.$invoice->full_number,
            ];
        }

        // FC legado: percepciones > 0 sin pivote → usar cuenta genérica para no romper balance
        if ($invoice->perceptions->isEmpty() && (float) $invoice->percepciones > 0) {
            $lines[] = [
                'account_code' => '5.1.02',
                'debit' => round((float) $invoice->percepciones, 2),
                'credit' => 0,
                'description' => 'Percepciones (legacy) — FC '.$invoice->full_number,
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

    /**
     * Nota de crédito recibida de proveedor: revierte el efecto de la compra (reduce deuda y gastos/IVA crédito).
     */
    public function fromPurchaseCreditNote(PurchaseCreditNote $creditNote): ?JournalEntry
    {
        $creditNote->loadMissing(['items', 'supplier', 'perceptions.accountingAccount']);

        $itemsWithSupply = $creditNote->items->whereNotNull('supply_id');
        $itemsWithoutSupply = $creditNote->items->whereNull('supply_id');

        $netoInsumos = round($itemsWithSupply->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_price), 2);
        $netoServicios = round($itemsWithoutSupply->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_price), 2);

        $totalIva = round(
            (float) $creditNote->iva_21 + (float) $creditNote->iva_10_5 + (float) $creditNote->iva_27,
            2
        );
        $totalBruto = round((float) $creditNote->total, 2);

        $otrosImp = round((float) $creditNote->otros_impuestos, 2);

        $lines = [];

        $lines[] = [
            'account_code' => '2.1.01',
            'debit' => $totalBruto,
            'credit' => 0,
            'description' => 'Reduce deuda '.($creditNote->supplier->name ?? '').' — NC '.$creditNote->full_number,
        ];

        $netoServiciosPlus = $netoServicios + $otrosImp;

        if ($netoInsumos > 0) {
            $lines[] = [
                'account_code' => '5.1.01',
                'debit' => 0,
                'credit' => $netoInsumos,
                'description' => 'Reversión insumos — NC compra '.$creditNote->full_number,
            ];
        }

        if ($netoServiciosPlus > 0) {
            $lines[] = [
                'account_code' => '5.1.02',
                'debit' => 0,
                'credit' => $netoServiciosPlus,
                'description' => 'Reversión servicios / otros — NC compra '.$creditNote->full_number,
            ];
        }

        if ($totalIva > 0) {
            $lines[] = [
                'account_code' => '1.1.06',
                'debit' => 0,
                'credit' => $totalIva,
                'description' => 'IVA crédito fiscal NC proveedor',
            ];
        }

        foreach ($creditNote->perceptions as $perception) {
            $amt = round((float) $perception->amount, 2);
            if ($amt <= 0 || ! $perception->accountingAccount) {
                continue;
            }
            $lines[] = [
                'account_code' => $perception->accountingAccount->code,
                'debit' => 0,
                'credit' => $amt,
                'description' => 'Reversión percepción '.$perception->name_snapshot
                    .($perception->jurisdiction_snapshot ? ' ('.$perception->jurisdiction_snapshot.')' : '')
                    .' — NC '.$creditNote->full_number,
            ];
        }

        return $this->createEntry(
            (int) $creditNote->company_id,
            Carbon::parse($creditNote->issue_date),
            'NC proveedor — '.$creditNote->full_number,
            $creditNote,
            $lines
        );
    }

    public function fromPaymentOrder(PaymentOrder $paymentOrder): ?JournalEntry
    {
        $paymentOrder->loadMissing(['supplier', 'portfolioEcheqPayments', 'paymentLines.bankAccount']);
        $companyId = (int) $paymentOrder->company_id;
        $total = round((float) $paymentOrder->total, 2);

        $debitLine = [
            'account_code' => '2.1.01',
            'debit' => $total,
            'credit' => 0,
            'description' => 'Cancelación deuda '.($paymentOrder->supplier->name ?? ''),
        ];

        $creditLines = [];
        if ($paymentOrder->paymentLines->isNotEmpty()) {
            foreach ($paymentOrder->paymentLines as $payLine) {
                $amt = round((float) $payLine->amount, 2);
                if ($amt <= 0) {
                    continue;
                }
                if ($payLine->kind === 'portfolio_echeq') {
                    $portfolioAccountCode = $this->resolveBankAccountCode('cheque', null);
                    $creditLines[] = [
                        'account_code' => $portfolioAccountCode,
                        'debit' => 0,
                        'credit' => $amt,
                        'description' => 'Endoso e-cheq cartera — OP '.$paymentOrder->number,
                    ];
                } else {
                    $bankId = $payLine->bank_account_id ? (int) $payLine->bank_account_id : null;
                    $bankAccountCode = $this->resolveBankAccountCode($payLine->kind, $bankId);
                    $ref = $payLine->payment_reference ? ' — '.$payLine->payment_reference : '';
                    $creditLines[] = [
                        'account_code' => $bankAccountCode,
                        'debit' => 0,
                        'credit' => $amt,
                        'description' => 'OP '.$paymentOrder->number.$ref.' — '.($paymentOrder->supplier->name ?? ''),
                    ];
                }
            }
        } elseif ($paymentOrder->portfolioEcheqPayments->isNotEmpty()) {
            $portfolioAccountCode = $this->resolveBankAccountCode('cheque', null);
            foreach ($paymentOrder->portfolioEcheqPayments as $pay) {
                $amt = round((float) $pay->amount, 2);
                if ($amt <= 0) {
                    continue;
                }
                $creditLines[] = [
                    'account_code' => $portfolioAccountCode,
                    'debit' => 0,
                    'credit' => $amt,
                    'description' => 'Endoso e-cheq '.($pay->cheque_number ?? '').' — OP '.$paymentOrder->number,
                ];
            }
        } else {
            $bankAccountCode = $this->resolveBankAccountCode(
                $paymentOrder->payment_method ?? 'efectivo',
                null
            );
            $creditLines[] = [
                'account_code' => $bankAccountCode,
                'debit' => 0,
                'credit' => $total,
                'description' => 'OP '.$paymentOrder->number.' — '.($paymentOrder->supplier->name ?? ''),
            ];
        }

        $lines = array_merge([$debitLine], $creditLines);

        return $this->createEntry(
            $companyId,
            Carbon::parse($paymentOrder->date),
            'Pago OP '.$paymentOrder->number,
            $paymentOrder,
            $lines
        );
    }

    public function fromPayrollPayment(PayrollPayment $payment): ?JournalEntry
    {
        // Idempotencia: no generar si ya existe un asiento para este pago
        $existing = JournalEntry::where('source_type', PayrollPayment::class)
            ->where('source_id', $payment->id)
            ->first();
        if ($existing) {
            return $existing;
        }

        $payment->loadMissing('bankAccount.accountingAccount');

        if (! $payment->bank_account_id || ! $payment->bankAccount?->accountingAccount) {
            Log::warning("AccountingEntryService::fromPayrollPayment: cuenta bancaria sin cuenta contable para PayrollPayment #{$payment->id}. Asiento no generado.");

            return null;
        }

        $total = round((float) $payment->total, 2);
        $label = "Pago haberes {$payment->period_label}";

        $lines = [
            [
                'account_code' => '2.1.07',
                'debit' => $total,
                'credit' => 0,
                'description' => $label,
            ],
            [
                'account_code' => $payment->bankAccount->accountingAccount->code,
                'debit' => 0,
                'credit' => $total,
                'description' => $label,
            ],
        ];

        $date = $payment->payment_date
            ? Carbon::parse($payment->payment_date)
            : Carbon::now();

        return $this->createEntry(
            (int) $payment->company_id,
            $date,
            $label,
            $payment,
            $lines
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
