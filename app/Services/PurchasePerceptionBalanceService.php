<?php

namespace App\Services;

use App\Models\JournalEntryLine;
use App\Models\PurchaseInvoicePerception;
use App\Models\PurchasePerception;
use Illuminate\Support\Collection;

class PurchasePerceptionBalanceService
{
    public function getBalances(int $companyId, string $from, string $to): Collection
    {
        $perceptions = PurchasePerception::where('company_id', $companyId)
            ->with('accountingAccount')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $perceptions->map(function ($perception) use ($companyId, $from, $to) {
            $anticiposFc = PurchaseInvoicePerception::query()
                ->where('purchase_perception_id', $perception->id)
                ->whereHas('purchaseInvoice', fn ($q) => $q->whereBetween('issue_date', [$from, $to])
                    ->where('company_id', $companyId)
                )
                ->sum('amount');

            $saldoCuenta = JournalEntryLine::query()
                ->where('accounting_account_id', $perception->accounting_account_id)
                ->whereHas('journalEntry', fn ($q) => $q->where('company_id', $companyId)
                    ->whereBetween('date', [$from, $to])
                )
                ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
                ->value('balance') ?? 0;

            return [
                'perception' => $perception,
                'anticipos_cargados' => round((float) $anticiposFc, 2),
                'saldo_cuenta' => round((float) $saldoCuenta, 2),
                'diferencia' => round((float) $anticiposFc - (float) $saldoCuenta, 2),
            ];
        });
    }
}
