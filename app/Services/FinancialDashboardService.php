<?php

namespace App\Services;

use App\Models\CollectionReceipt;
use App\Models\CreditNote;
use App\Models\PaymentOrder;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialDashboardService
{
    /**
     * Ventas netas (FV - NC) del mes corriente, evolución 12 meses, variación %.
     */
    public function ventas(?int $companyId): array
    {
        [$start, $end] = $this->range();
        $expr = $this->yearMonthExpression('issue_date');

        $invoices = SalesInvoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('issue_date', [$start, $end])
            ->where(function ($q) {
                $q->where('is_electronic', false)
                    ->orWhereNotNull('cae');
            })
            ->select(
                DB::raw("{$expr} as ym"),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        $credits = CreditNote::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('issue_date', [$start, $end])
            ->select(
                DB::raw("{$expr} as ym"),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        return $this->buildSeries(
            key: 'ventas',
            label: 'Ventas',
            positives: $invoices,
            negatives: $credits
        );
    }

    /**
     * Compras netas (FC - NC proveedor) del mes corriente, evolución 12 meses.
     */
    public function compras(?int $companyId): array
    {
        [$start, $end] = $this->range();
        $expr = $this->yearMonthExpression('issue_date');

        $invoices = PurchaseInvoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('issue_date', [$start, $end])
            ->select(
                DB::raw("{$expr} as ym"),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        $credits = PurchaseCreditNote::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('issue_date', [$start, $end])
            ->select(
                DB::raw("{$expr} as ym"),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        return $this->buildSeries(
            key: 'compras',
            label: 'Compras',
            positives: $invoices,
            negatives: $credits
        );
    }

    /**
     * Ingresos del mes (recibos de cobro confirmados), evolución 12 meses.
     */
    public function ingresos(?int $companyId): array
    {
        [$start, $end] = $this->range();
        $expr = $this->yearMonthExpression('date');

        $rows = CollectionReceipt::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('date', [$start, $end])
            ->where('status', '!=', 'anulado')
            ->select(
                DB::raw("{$expr} as ym"),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        return $this->buildSeries(
            key: 'ingresos',
            label: 'Ingresos',
            positives: $rows,
            negatives: collect()
        );
    }

    /**
     * Egresos del mes (órdenes de pago no anuladas), evolución 12 meses.
     */
    public function egresos(?int $companyId): array
    {
        [$start, $end] = $this->range();
        $expr = $this->yearMonthExpression('date');

        $rows = PaymentOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('date', [$start, $end])
            ->where('status', '!=', 'anulada')
            ->select(
                DB::raw("{$expr} as ym"),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        return $this->buildSeries(
            key: 'egresos',
            label: 'Egresos',
            positives: $rows,
            negatives: collect()
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(): array
    {
        return [
            now()->subMonths(11)->startOfMonth(),
            now()->endOfMonth(),
        ];
    }

    /**
     * Devuelve la expresión SQL para extraer el año-mes ('YYYY-MM') de una columna de fecha,
     * compatible con MySQL/MariaDB y SQLite (driver usado por la suite de tests).
     */
    private function yearMonthExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "strftime('%Y-%m', {$column})";
        }

        return "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /**
     * Construye la serie de 12 meses + KPI corriente + variación %.
     */
    private function buildSeries(string $key, string $label, $positives, $negatives): array
    {
        $monthly = [];
        $currentKey = now()->format('Y-m');
        $prevKey = now()->subMonth()->format('Y-m');

        $currentTotal = 0.0;
        $previousTotal = 0.0;

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $mKey = $date->format('Y-m');

            $positiveRow = $positives->get($mKey);
            $negativeRow = $negatives->get($mKey);
            $positive = $positiveRow ? (float) $positiveRow->total : 0.0;
            $negative = $negativeRow ? (float) $negativeRow->total : 0.0;
            $value = $positive - $negative;

            $monthly[] = [
                'label' => $date->locale('es')->isoFormat('MMM YY'),
                'value' => $value,
                'is_current' => $mKey === $currentKey,
            ];

            if ($mKey === $currentKey) {
                $currentTotal = $value;
            }
            if ($mKey === $prevKey) {
                $previousTotal = $value;
            }
        }

        return [
            'key' => $key,
            'label' => $label,
            'monthly' => $monthly,
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'variation_percent' => $this->calculateVariation($currentTotal, $previousTotal),
        ];
    }

    /**
     * Variación % entre el mes corriente y el anterior.
     *
     * Devuelve null cuando no hay base de comparación (mes anterior 0 con corriente > 0).
     * Devuelve 0.0 cuando ambos son 0 (sin actividad).
     * Cap a +999 / -999 para evitar números absurdos.
     */
    public function calculateVariation(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.01) {
            if (abs($current) < 0.01) {
                return 0.0;
            }

            return null;
        }

        $variation = round((($current - $previous) / $previous) * 100, 1);

        if ($variation > 999) {
            return 999.0;
        }
        if ($variation < -999) {
            return -999.0;
        }

        return $variation;
    }
}
