<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\PurchaseCreditNotePerception;
use App\Models\PurchaseInvoicePerception;
use App\Models\Tax;
use App\Models\TaxReturn;
use App\Models\TaxReturnApplication;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaxReturnService
{
    public function availableAdvances(Tax $tax, int $year, ?int $month): Collection
    {
        [$dateFrom, $dateTo] = $this->periodRange($tax->frequency, $year, $month);

        $perceptionIds = $tax->perceptions()->pluck('id');
        if ($perceptionIds->isEmpty()) {
            return collect();
        }

        $alreadyImputedPip = TaxReturnApplication::query()
            ->whereHas('taxReturn', fn ($q) => $q->where('status', 'confirmed'))
            ->whereNotNull('purchase_invoice_perception_id')
            ->pluck('purchase_invoice_perception_id');

        $alreadyImputedPcnp = TaxReturnApplication::query()
            ->whereHas('taxReturn', fn ($q) => $q->where('status', 'confirmed'))
            ->whereNotNull('purchase_credit_note_perception_id')
            ->pluck('purchase_credit_note_perception_id');

        $companyId = active_company_id();

        $fc = PurchaseInvoicePerception::query()
            ->with(['purchaseInvoice.supplier', 'accountingAccount'])
            ->whereIn('purchase_perception_id', $perceptionIds)
            ->whereHas('purchaseInvoice', fn ($q) => $q
                ->where('company_id', $companyId)
                ->whereBetween('issue_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            )
            ->whereNotIn('id', $alreadyImputedPip)
            ->orderBy('id')
            ->get()
            ->map(fn ($pip) => [
                'kind' => 'fc',
                'id' => $pip->id,
                'purchase_invoice_perception_id' => $pip->id,
                'amount' => (float) $pip->amount,
                'label' => $pip->purchaseInvoice->full_number.' — '.$pip->name_snapshot,
                'issue_date' => $pip->purchaseInvoice->issue_date?->toDateString(),
                'account_code' => $pip->accountingAccount?->code,
            ]);

        $nc = PurchaseCreditNotePerception::query()
            ->with(['purchaseCreditNote.supplier', 'accountingAccount'])
            ->whereIn('purchase_perception_id', $perceptionIds)
            ->whereHas('purchaseCreditNote', fn ($q) => $q
                ->where('company_id', $companyId)
                ->whereBetween('issue_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            )
            ->whereNotIn('id', $alreadyImputedPcnp)
            ->orderBy('id')
            ->get()
            ->map(fn ($p) => [
                'kind' => 'nc',
                'id' => $p->id,
                'purchase_credit_note_perception_id' => $p->id,
                'amount' => (float) $p->amount,
                'label' => $p->purchaseCreditNote->full_number.' — '.$p->name_snapshot,
                'issue_date' => $p->purchaseCreditNote->issue_date?->toDateString(),
                'account_code' => $p->accountingAccount?->code,
            ]);

        return $fc->concat($nc)->values();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodRange(string $frequency, int $year, ?int $month): array
    {
        return match ($frequency) {
            'monthly' => [
                Carbon::create($year, $month ?? 1, 1)->startOfMonth(),
                Carbon::create($year, $month ?? 1, 1)->endOfMonth(),
            ],
            'quarterly' => [
                Carbon::create($year, $month ?? 1, 1)->startOfMonth(),
                Carbon::create($year, $month ?? 1, 1)->addMonths(2)->endOfMonth(),
            ],
            'annual' => [
                Carbon::create($year, 1, 1)->startOfYear(),
                Carbon::create($year, 12, 31)->endOfYear(),
            ],
            default => [
                Carbon::create($year, $month ?? 1, 1)->startOfMonth(),
                Carbon::create($year, $month ?? 1, 1)->endOfMonth(),
            ],
        };
    }

    /**
     * @throws \DomainException
     */
    public function confirm(TaxReturn $taxReturn): ?JournalEntry
    {
        if (! $taxReturn->isDraft()) {
            throw new \DomainException('Solo se pueden confirmar declaraciones en estado borrador.');
        }

        $taxReturn->loadMissing([
            'tax.liabilityAccount',
            'applications.purchaseInvoicePerception.purchaseInvoice',
            'applications.purchaseInvoicePerception.accountingAccount',
            'applications.purchaseCreditNotePerception.purchaseCreditNote',
            'applications.purchaseCreditNotePerception.accountingAccount',
        ]);

        foreach ($taxReturn->applications as $app) {
            $this->assertAdvanceFreeForConfirm($app, $taxReturn->id);
        }

        $creditAdvances = 0.0;
        $debitAdvances = 0.0;

        $lines = [];

        $pipGroups = [];
        foreach ($taxReturn->applications as $app) {
            if ($app->purchase_invoice_perception_id) {
                $pip = $app->purchaseInvoicePerception;
                if (! $pip?->accountingAccount) {
                    continue;
                }
                $code = $pip->accountingAccount->code;
                $amt = round((float) $app->amount_applied, 2);
                if ($amt <= 0) {
                    continue;
                }
                $pipGroups[$code] = ($pipGroups[$code] ?? 0) + $amt;
                $creditAdvances += $amt;
            }
        }

        foreach ($pipGroups as $code => $total) {
            $lines[] = [
                'account_code' => $code,
                'debit' => 0,
                'credit' => round($total, 2),
                'description' => 'Imputación anticipos — '.$taxReturn->tax->name.' '.$taxReturn->period_label,
            ];
        }

        $pcnpGroups = [];
        foreach ($taxReturn->applications as $app) {
            if ($app->purchase_credit_note_perception_id) {
                $pcnp = $app->purchaseCreditNotePerception;
                if (! $pcnp?->accountingAccount) {
                    continue;
                }
                $code = $pcnp->accountingAccount->code;
                $amt = round((float) $app->amount_applied, 2);
                if ($amt <= 0) {
                    continue;
                }
                $pcnpGroups[$code] = ($pcnpGroups[$code] ?? 0) + $amt;
                $debitAdvances += $amt;
            }
        }

        foreach ($pcnpGroups as $code => $total) {
            $lines[] = [
                'account_code' => $code,
                'debit' => round($total, 2),
                'credit' => 0,
                'description' => 'Imputación NC — '.$taxReturn->tax->name.' '.$taxReturn->period_label,
            ];
        }

        $declared = round((float) $taxReturn->declared_amount, 2);
        $liabilityCode = $taxReturn->tax->liabilityAccount->code;

        if ($declared > 0) {
            $lines[] = [
                'account_code' => $liabilityCode,
                'debit' => $declared,
                'credit' => 0,
                'description' => 'DDJJ '.$taxReturn->tax->name.' '.$taxReturn->period_label,
            ];
        }

        $totalDr = $debitAdvances + ($declared > 0 ? $declared : 0);
        $totalCr = $creditAdvances;
        $net = round($totalDr - $totalCr, 2);

        if ($net > 0.004) {
            $lines[] = [
                'account_code' => $liabilityCode,
                'debit' => 0,
                'credit' => $net,
                'description' => 'Saldo a pagar — '.$taxReturn->tax->name.' '.$taxReturn->period_label,
            ];
        } elseif ($net < -0.004) {
            $lines[] = [
                'account_code' => $liabilityCode,
                'debit' => round(abs($net), 2),
                'credit' => 0,
                'description' => 'Saldo a favor — '.$taxReturn->tax->name.' '.$taxReturn->period_label,
            ];
        }

        $entryDate = $taxReturn->period_month
            ? Carbon::create((int) $taxReturn->period_year, (int) $taxReturn->period_month, 1)->endOfMonth()
            : Carbon::create((int) $taxReturn->period_year, 12, 31)->endOfDay();

        $entry = app(AccountingEntryService::class)->createEntryForSource(
            (int) $taxReturn->company_id,
            $entryDate,
            'DDJJ '.$taxReturn->tax->name.' '.$taxReturn->period_label,
            $taxReturn,
            $lines
        );

        if (! $entry) {
            throw new \RuntimeException('No se pudo generar el asiento contable (cuentas inexistentes o asiento desbalanceado).');
        }

        $taxReturn->update([
            'journal_entry_id' => $entry->id,
            'status' => 'confirmed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        return $entry;
    }

    /**
     * @throws \DomainException
     */
    public function cancel(TaxReturn $taxReturn): ?JournalEntry
    {
        if (! $taxReturn->isConfirmed()) {
            throw new \DomainException('Solo se pueden anular declaraciones confirmadas.');
        }

        $original = $taxReturn->journalEntry;
        if (! $original) {
            throw new \RuntimeException('La declaración no tiene asiento original.');
        }

        $original->loadMissing('lines.account');

        $reverseLines = $original->lines->map(fn ($line) => [
            'account_code' => $line->account->code,
            'debit' => (float) $line->credit,
            'credit' => (float) $line->debit,
            'description' => 'ANULACIÓN — '.($line->description ?? ''),
        ])->toArray();

        $entry = app(AccountingEntryService::class)->createEntryForSource(
            (int) $taxReturn->company_id,
            Carbon::now(),
            'ANULACIÓN DDJJ '.$taxReturn->tax->name.' '.$taxReturn->period_label,
            $taxReturn,
            $reverseLines
        );

        if (! $entry) {
            throw new \RuntimeException('No se pudo generar el asiento de anulación.');
        }

        $taxReturn->update([
            'cancellation_journal_entry_id' => $entry->id,
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
        ]);

        return $entry;
    }

    /**
     * @throws \DomainException
     */
    protected function assertAdvanceFreeForConfirm(TaxReturnApplication $application, int $currentTaxReturnId): void
    {
        if ($application->purchase_invoice_perception_id) {
            $exists = TaxReturnApplication::query()
                ->where('purchase_invoice_perception_id', $application->purchase_invoice_perception_id)
                ->where('tax_return_id', '!=', $currentTaxReturnId)
                ->whereHas('taxReturn', fn ($q) => $q->where('status', 'confirmed'))
                ->exists();
            if ($exists) {
                throw new \DomainException('Un anticipo de factura seleccionado ya fue imputado en otra declaración confirmada.');
            }
        }

        if ($application->purchase_credit_note_perception_id) {
            $exists = TaxReturnApplication::query()
                ->where('purchase_credit_note_perception_id', $application->purchase_credit_note_perception_id)
                ->where('tax_return_id', '!=', $currentTaxReturnId)
                ->whereHas('taxReturn', fn ($q) => $q->where('status', 'confirmed'))
                ->exists();
            if ($exists) {
                throw new \DomainException('Un anticipo de nota de crédito seleccionado ya fue imputado en otra declaración confirmada.');
            }
        }
    }
}
