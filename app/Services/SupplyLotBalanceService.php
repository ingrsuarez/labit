<?php

namespace App\Services;

use App\Models\StockMovement;
use Illuminate\Support\Collection;

/**
 * Saldo por lote a partir del historial de {@see StockMovement}.
 * Los ajustes no distribuyen cantidad por lote (pueden desalinear suma de buckets vs stock global/sede).
 */
class SupplyLotBalanceService
{
    /**
     * @return Collection<int, object{lot_number: string, expiration_date: ?string, quantity: float}>
     */
    public function availableLots(int $supplyId, ?int $labBranchId = null): Collection
    {
        $buckets = [];

        $query = StockMovement::query()
            ->where('supply_id', $supplyId)
            ->orderBy('id');

        if ($labBranchId !== null) {
            $query->where('lab_branch_id', $labBranchId);
        }

        foreach ($query->cursor() as $movement) {
            if ($movement->type === 'ajuste') {
                continue;
            }

            $lot = $movement->lot_number !== null ? trim((string) $movement->lot_number) : '';
            if ($lot === '') {
                continue;
            }

            $expKey = $movement->expiration_date
                ? $movement->expiration_date->format('Y-m-d')
                : '';
            $key = $lot."\0".$expKey;

            $qty = (float) $movement->quantity;
            if (! isset($buckets[$key])) {
                $buckets[$key] = 0.0;
            }

            if ($movement->type === 'entrada') {
                $buckets[$key] += $qty;
            } elseif ($movement->type === 'salida') {
                $buckets[$key] -= $qty;
            }
        }

        return collect($buckets)
            ->map(function (float $quantity, string $key) {
                [$lotNumber, $expirationKey] = explode("\0", $key, 2);
                $expirationDate = $expirationKey !== '' ? $expirationKey : null;

                return (object) [
                    'lot_number' => $lotNumber,
                    'expiration_date' => $expirationDate,
                    'quantity' => $quantity,
                ];
            })
            ->filter(fn (object $row) => $row->quantity > 0.0001)
            ->values();
    }

    public function quantityAvailableForLot(
        int $supplyId,
        ?int $labBranchId,
        string $lotNumber,
        ?string $expirationDateYmd
    ): float {
        $lot = trim($lotNumber);
        if ($lot === '') {
            return 0.0;
        }

        $expKey = $expirationDateYmd !== null && $expirationDateYmd !== ''
            ? $expirationDateYmd
            : '';

        return (float) $this->availableLots($supplyId, $labBranchId)
            ->first(function (object $row) use ($lot, $expKey) {
                $rowExp = $row->expiration_date ?? '';

                return $row->lot_number === $lot && (string) $rowExp === $expKey;
            })?->quantity ?? 0.0;
    }
}
