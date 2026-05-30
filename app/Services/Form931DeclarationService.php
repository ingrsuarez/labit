<?php

namespace App\Services;

use App\Models\Form931Declaration;
use App\Models\JournalEntry;
use Carbon\Carbon;

class Form931DeclarationService
{
    /**
     * @throws \DomainException
     */
    public function confirm(Form931Declaration $declaration): ?JournalEntry
    {
        if (! $declaration->isDraft()) {
            throw new \DomainException('Solo se pueden confirmar declaraciones en estado borrador.');
        }

        $declaration->recalculateTotal();

        if ((float) $declaration->total <= 0) {
            throw new \DomainException('El total debe ser mayor a cero para confirmar.');
        }

        $this->assertUniqueConfirmedPeriod($declaration);

        $lines = [];
        $aportes = round((float) $declaration->amount_aportes_patronales, 2);
        $contribuciones = round((float) $declaration->amount_contribuciones_patronales, 2);
        $label = 'DDJJ Form 931 '.$declaration->period_label;

        if ($aportes > 0) {
            $lines[] = [
                'account_code' => '5.2.11',
                'debit' => $aportes,
                'credit' => 0,
                'description' => $label.' — Aportes patronales',
            ];
            $lines[] = [
                'account_code' => '2.1.11',
                'debit' => 0,
                'credit' => $aportes,
                'description' => $label.' — Aportes patronales',
            ];
        }

        if ($contribuciones > 0) {
            $lines[] = [
                'account_code' => '5.2.12',
                'debit' => $contribuciones,
                'credit' => 0,
                'description' => $label.' — Contribuciones patronales',
            ];
            $lines[] = [
                'account_code' => '2.1.12',
                'debit' => 0,
                'credit' => $contribuciones,
                'description' => $label.' — Contribuciones patronales',
            ];
        }

        $entryDate = Carbon::create(
            (int) $declaration->period_year,
            (int) $declaration->period_month,
            1
        )->endOfMonth();

        $entry = app(AccountingEntryService::class)->createEntryForSource(
            (int) $declaration->company_id,
            $entryDate,
            $label,
            $declaration,
            $lines
        );

        if (! $entry) {
            throw new \RuntimeException('No se pudo generar el asiento contable (cuentas inexistentes o asiento desbalanceado).');
        }

        $declaration->update([
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
    public function cancel(Form931Declaration $declaration): ?JournalEntry
    {
        if (! $declaration->isConfirmed()) {
            throw new \DomainException('Solo se pueden anular declaraciones confirmadas.');
        }

        $original = $declaration->journalEntry;
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
            (int) $declaration->company_id,
            Carbon::now(),
            'ANULACIÓN DDJJ Form 931 '.$declaration->period_label,
            $declaration,
            $reverseLines
        );

        if (! $entry) {
            throw new \RuntimeException('No se pudo generar el asiento de anulación.');
        }

        $declaration->update([
            'cancellation_journal_entry_id' => $entry->id,
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
        ]);

        return $entry;
    }

    public function latestConfirmed(?int $companyId): ?Form931Declaration
    {
        if (! $companyId) {
            return null;
        }

        return Form931Declaration::query()
            ->forCompany($companyId)
            ->confirmed()
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('confirmed_at')
            ->first();
    }

    public function latestConfirmedTotal(?int $companyId): ?float
    {
        $latest = $this->latestConfirmed($companyId);

        return $latest ? (float) $latest->total : null;
    }

    /**
     * @throws \DomainException
     */
    protected function assertUniqueConfirmedPeriod(Form931Declaration $declaration): void
    {
        $exists = Form931Declaration::query()
            ->where('company_id', $declaration->company_id)
            ->where('period_year', $declaration->period_year)
            ->where('period_month', $declaration->period_month)
            ->where('status', 'confirmed')
            ->where('id', '!=', $declaration->id)
            ->exists();

        if ($exists) {
            throw new \DomainException('Ya existe una DDJJ Form 931 confirmada para este período.');
        }
    }
}
