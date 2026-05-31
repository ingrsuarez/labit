<?php

namespace App\Services;

use App\Models\CashFlowObligation;
use App\Models\CashFlowSetting;
use App\Models\CreditNote;
use App\Models\PaymentOrder;
use App\Models\PaymentOrderPaymentLine;
use App\Models\Payroll;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\TaxReturn;
use App\Support\BusinessDayCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CashFlowCalendarService
{
    public function __construct(
        protected Form931DeclarationService $form931Service
    ) {}

    public function eventsForRange(int $companyId, Carbon $from, Carbon $to): Collection
    {
        $settings = CashFlowSetting::forCompany($companyId);

        return collect()
            ->merge($this->purchaseInvoiceEvents($companyId, $from, $to))
            ->merge($this->ivaEvents($companyId, $from, $to, $settings))
            ->merge($this->form931Events($companyId, $from, $to, $settings))
            ->merge($this->payrollEvents($companyId, $from, $to))
            ->merge($this->fixedExpenseEvents($companyId, $from, $to))
            ->merge($this->issuedChequeEvents($companyId, $from, $to))
            ->merge($this->manualEvents($companyId, $from, $to))
            ->sortBy([['date', 'asc'], ['title', 'asc']])
            ->values();
    }

    public static function categoryMeta(): array
    {
        return [
            'impuesto_iva' => ['label' => 'IVA', 'color' => 'violet'],
            'impuesto_931' => ['label' => 'Form 931', 'color' => 'indigo'],
            'impuesto_ganancias' => ['label' => 'Ganancias', 'color' => 'purple'],
            'arca_plan' => ['label' => 'Plan ARCA', 'color' => 'orange'],
            'echeq_emitido' => ['label' => 'E-cheq emitido', 'color' => 'amber'],
            'sueldos' => ['label' => 'Sueldos', 'color' => 'blue'],
            'gasto_fijo' => ['label' => 'Gasto fijo', 'color' => 'teal'],
            'cuota_equipo' => ['label' => 'Cuota equipo', 'color' => 'slate'],
            'factura_compra' => ['label' => 'FC compra', 'color' => 'red'],
        ];
    }

    protected function purchaseInvoiceEvents(int $companyId, Carbon $from, Carbon $to): Collection
    {
        return PurchaseInvoice::query()
            ->where('company_id', $companyId)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->where('balance', '>', 0)
            ->whereNotIn('status', ['pagada', 'anulada'])
            ->with('supplier')
            ->get()
            ->map(fn (PurchaseInvoice $inv) => $this->event(
                id: 'fc:'.$inv->id,
                date: $inv->due_date->toDateString(),
                category: 'factura_compra',
                title: 'FC '.$inv->full_number.' — '.($inv->supplier?->name ?? 'Proveedor'),
                amount: (float) $inv->balance,
                confidence: 'confirmed',
                sourceType: PurchaseInvoice::class,
                sourceId: $inv->id,
                url: route('purchase-invoices.show', $inv),
                meta: ['supplier' => $inv->supplier?->name],
            ));
    }

    protected function ivaEvents(int $companyId, Carbon $from, Carbon $to, CashFlowSetting $settings): Collection
    {
        $events = collect();
        $cursor = $from->copy()->startOfMonth();
        $end = $to->copy()->endOfMonth();
        $ivaTax = $this->findIvaTax($companyId);

        while ($cursor->lte($end)) {
            $year = $cursor->year;
            $month = $cursor->month;
            $date = BusinessDayCalculator::dueDateOnDayOfMonth($year, $month, $settings->iva_due_day);

            if ($date->between($from, $to)) {
                [$amount, $confidence] = $this->ivaAmountForPeriod($companyId, $year, $month, $ivaTax);

                if ($amount > 0) {
                    $events->push($this->event(
                        id: 'iva:'.$year.'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                        date: $date->toDateString(),
                        category: 'impuesto_iva',
                        title: 'IVA '.sprintf('%02d/%d', $month, $year),
                        amount: $amount,
                        confidence: $confidence,
                        sourceType: $ivaTax && $confidence === 'confirmed' ? TaxReturn::class : null,
                        sourceId: null,
                        url: $ivaTax ? route('tax-returns.index', ['year' => $year]) : null,
                        meta: ['period' => sprintf('%02d/%d', $month, $year)],
                    ));
                }
            }

            $cursor->addMonth();
        }

        return $events;
    }

    protected function form931Events(int $companyId, Carbon $from, Carbon $to, CashFlowSetting $settings): Collection
    {
        $total = $this->form931Service->latestConfirmedTotal($companyId);
        if ($total === null || $total <= 0) {
            return collect();
        }

        $events = collect();
        $cursor = $from->copy()->startOfMonth();
        $end = $to->copy()->endOfMonth();

        while ($cursor->lte($end)) {
            $date = BusinessDayCalculator::dueDateOnDayOfMonth(
                $cursor->year,
                $cursor->month,
                $settings->form931_due_day
            );

            if ($date->between($from, $to)) {
                $events->push($this->event(
                    id: '931:'.$cursor->format('Y-m'),
                    date: $date->toDateString(),
                    category: 'impuesto_931',
                    title: 'Form 931 '.sprintf('%02d/%d', $cursor->month, $cursor->year),
                    amount: $total,
                    confidence: 'estimated',
                    sourceType: null,
                    sourceId: null,
                    url: route('form931-declarations.index'),
                    meta: ['projected_from' => 'latest_confirmed'],
                ));
            }

            $cursor->addMonth();
        }

        return $events;
    }

    protected function payrollEvents(int $companyId, Carbon $from, Carbon $to): Collection
    {
        $amount = $this->latestClosedPayrollTotal($companyId);
        if ($amount <= 0) {
            return collect();
        }

        $events = collect();
        $cursor = $from->copy()->startOfMonth();
        $end = $to->copy()->endOfMonth();

        while ($cursor->lte($end)) {
            $date = BusinessDayCalculator::nthBusinessDayOfMonth($cursor->year, $cursor->month, 5);

            if ($date->between($from, $to)) {
                $events->push($this->event(
                    id: 'sueldos:'.$cursor->format('Y-m'),
                    date: $date->toDateString(),
                    category: 'sueldos',
                    title: 'Sueldos '.sprintf('%02d/%d', $cursor->month, $cursor->year),
                    amount: $amount,
                    confidence: 'estimated',
                    sourceType: null,
                    sourceId: null,
                    url: route('payroll.index'),
                    meta: ['note' => 'Basado en última liquidación cerrada'],
                ));
            }

            $cursor->addMonth();
        }

        return $events;
    }

    protected function fixedExpenseEvents(int $companyId, Carbon $from, Carbon $to): Collection
    {
        $events = collect();

        $suppliers = Supplier::query()
            ->where('is_fixed_expense', true)
            ->where('status', 'activo')
            ->get();

        foreach ($suppliers as $supplier) {
            $lastInvoice = PurchaseInvoice::query()
                ->where('company_id', $companyId)
                ->where('supplier_id', $supplier->id)
                ->whereNotIn('status', ['anulada'])
                ->orderByDesc('issue_date')
                ->first();

            if (! $lastInvoice) {
                continue;
            }

            $referenceDate = $lastInvoice->due_date ?? $lastInvoice->issue_date;
            $dayOfMonth = $referenceDate->day;

            $cursor = $from->copy()->startOfMonth();
            $end = $to->copy()->endOfMonth();

            while ($cursor->lte($end)) {
                $date = BusinessDayCalculator::dueDateOnDayOfMonth($cursor->year, $cursor->month, $dayOfMonth);

                if ($date->between($from, $to)) {
                    $events->push($this->event(
                        id: 'fijo:'.$supplier->id.':'.$cursor->format('Y-m'),
                        date: $date->toDateString(),
                        category: 'gasto_fijo',
                        title: $supplier->name.' (estimado)',
                        amount: (float) $lastInvoice->total,
                        confidence: 'estimated',
                        sourceType: PurchaseInvoice::class,
                        sourceId: $lastInvoice->id,
                        url: route('purchase-invoices.show', $lastInvoice),
                        meta: [
                            'supplier_id' => $supplier->id,
                            'based_on_invoice' => $lastInvoice->full_number,
                        ],
                    ));
                }

                $cursor->addMonth();
            }
        }

        return $events;
    }

    protected function issuedChequeEvents(int $companyId, Carbon $from, Carbon $to): Collection
    {
        $events = collect();

        PaymentOrderPaymentLine::query()
            ->where('kind', 'cheque')
            ->whereNotNull('cheque_due_date')
            ->whereBetween('cheque_due_date', [$from->toDateString(), $to->toDateString()])
            ->whereHas('paymentOrder', fn ($q) => $q
                ->where('company_id', $companyId)
                ->whereNotIn('status', ['anulada', 'borrador']))
            ->with(['paymentOrder.supplier'])
            ->get()
            ->each(function (PaymentOrderPaymentLine $line) use ($events) {
                $order = $line->paymentOrder;
                $reference = $line->payment_reference ?: $order->number;

                $events->push($this->event(
                    id: 'op-cheque:'.$line->id,
                    date: $line->cheque_due_date->toDateString(),
                    category: 'echeq_emitido',
                    title: 'Cheque '.$reference.' — '.($order->supplier?->name ?? 'Proveedor').' ('.$order->number.')',
                    amount: (float) $line->amount,
                    confidence: 'confirmed',
                    sourceType: PaymentOrder::class,
                    sourceId: $order->id,
                    url: route('payment-orders.show', $order),
                    meta: [
                        'payment_order' => $order->number,
                        'cheque_reference' => $line->payment_reference,
                    ],
                ));
            });

        PaymentOrder::query()
            ->where('company_id', $companyId)
            ->where('payment_method', 'cheque')
            ->whereNotNull('cheque_due_date')
            ->whereBetween('cheque_due_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotIn('status', ['anulada', 'borrador'])
            ->whereDoesntHave('paymentLines')
            ->with('supplier')
            ->get()
            ->each(function (PaymentOrder $order) use ($events) {
                $reference = $order->payment_reference ?: $order->number;

                $events->push($this->event(
                    id: 'op-cheque-legacy:'.$order->id,
                    date: $order->cheque_due_date->toDateString(),
                    category: 'echeq_emitido',
                    title: 'Cheque '.$reference.' — '.($order->supplier?->name ?? 'Proveedor').' ('.$order->number.')',
                    amount: (float) $order->total,
                    confidence: 'confirmed',
                    sourceType: PaymentOrder::class,
                    sourceId: $order->id,
                    url: route('payment-orders.show', $order),
                    meta: [
                        'payment_order' => $order->number,
                        'cheque_reference' => $order->payment_reference,
                    ],
                ));
            });

        return $events;
    }

    protected function manualEvents(int $companyId, Carbon $from, Carbon $to): Collection
    {
        return CashFlowObligation::query()
            ->where('company_id', $companyId)
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('due_date')
            ->get()
            ->map(fn (CashFlowObligation $ob) => $this->event(
                id: 'manual:'.$ob->id,
                date: $ob->due_date->toDateString(),
                category: $ob->calendarCategory(),
                title: $ob->title,
                amount: (float) $ob->amount,
                confidence: 'manual',
                sourceType: CashFlowObligation::class,
                sourceId: $ob->id,
                url: route('cash-flow.obligations.edit', $ob),
                meta: array_filter(['notes' => $ob->notes, 'metadata' => $ob->metadata]),
            ));
    }

    /**
     * @return array{0: float, 1: string}
     */
    protected function ivaAmountForPeriod(int $companyId, int $year, int $month, ?Tax $ivaTax): array
    {
        if ($ivaTax) {
            $return = TaxReturn::query()
                ->where('company_id', $companyId)
                ->where('tax_id', $ivaTax->id)
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->where('status', 'confirmed')
                ->first();

            if ($return) {
                $balance = max((float) $return->balance, 0);

                return [$balance, 'confirmed'];
            }
        }

        $estimated = max($this->estimatedIvaNet($companyId, $year, $month), 0);

        return [$estimated, 'estimated'];
    }

    protected function estimatedIvaNet(int $companyId, int $year, int $month): float
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $salesIva = $this->sumIva(SalesInvoice::query()
            ->where('company_id', $companyId)
            ->whereBetween('issue_date', [$start, $end])
            ->where(fn ($q) => $q->where('is_electronic', false)->orWhereNotNull('cae'))
            ->get());

        $salesNcIva = $this->sumIva(CreditNote::query()
            ->where('company_id', $companyId)
            ->whereBetween('issue_date', [$start, $end])
            ->get());

        $purchaseIva = $this->sumIva(PurchaseInvoice::query()
            ->where('company_id', $companyId)
            ->whereBetween('issue_date', [$start, $end])
            ->whereNotIn('status', ['anulada'])
            ->get());

        $purchaseNcIva = $this->sumIva(PurchaseCreditNote::query()
            ->where('company_id', $companyId)
            ->whereBetween('issue_date', [$start, $end])
            ->get());

        return round(($salesIva - $salesNcIva) - ($purchaseIva - $purchaseNcIva), 2);
    }

    protected function sumIva(Collection $documents): float
    {
        return round($documents->sum(fn ($doc) => (float) ($doc->iva_21 ?? 0)
            + (float) ($doc->iva_10_5 ?? 0)
            + (float) ($doc->iva_27 ?? 0)), 2);
    }

    protected function findIvaTax(int $companyId): ?Tax
    {
        return Tax::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('frequency', 'monthly')
            ->whereRaw('LOWER(name) LIKE ?', ['%iva%'])
            ->orderBy('sort_order')
            ->first();
    }

    protected function latestClosedPayrollTotal(int $companyId): float
    {
        $latest = Payroll::query()
            ->whereIn('status', ['liquidado', 'pagado'])
            ->whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        if (! $latest) {
            return 0;
        }

        return round((float) Payroll::query()
            ->where('year', $latest->year)
            ->where('month', $latest->month)
            ->whereIn('status', ['liquidado', 'pagado'])
            ->whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
            ->sum('neto_a_cobrar'), 2);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function event(
        string $id,
        string $date,
        string $category,
        string $title,
        float $amount,
        string $confidence,
        ?string $sourceType,
        ?int $sourceId,
        ?string $url,
        array $meta = [],
    ): array {
        $labels = self::categoryMeta();

        return [
            'id' => $id,
            'date' => $date,
            'category' => $category,
            'category_label' => $labels[$category]['label'] ?? $category,
            'category_color' => $labels[$category]['color'] ?? 'gray',
            'title' => $title,
            'amount' => $amount,
            'confidence' => $confidence,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'url' => $url,
            'meta' => $meta,
        ];
    }
}
