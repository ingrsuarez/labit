<?php

namespace App\Services;

use App\Models\CollectionReceiptWithholding;

/**
 * Agrega retenciones de IVA sufridas en cobranzas (RC confirmados).
 *
 * Criterio de período: fecha del recibo (`collection_receipts.date`), no fecha de emisión de FC.
 * Esto alinea el crédito fiscal retenido con el mes en que se registró el cobro.
 */
class WithheldIvaSummaryService
{
    public function totalForPeriod(int $companyId, int $year, int $month): float
    {
        return round((float) CollectionReceiptWithholding::query()
            ->where('withholding_type', CollectionReceiptWithholding::TYPE_IVA)
            ->whereHas('collectionReceipt', function ($q) use ($companyId, $year, $month) {
                $q->where('company_id', $companyId)
                    ->where('status', 'confirmado')
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month);
            })
            ->sum('amount'), 2);
    }

    public function countForPeriod(int $companyId, int $year, int $month): int
    {
        return (int) CollectionReceiptWithholding::query()
            ->where('withholding_type', CollectionReceiptWithholding::TYPE_IVA)
            ->whereHas('collectionReceipt', function ($q) use ($companyId, $year, $month) {
                $q->where('company_id', $companyId)
                    ->where('status', 'confirmado')
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month);
            })
            ->count();
    }
}
