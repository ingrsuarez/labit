<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoiceItem;
use Carbon\Carbon;

class PurchaseServiceStatisticsController extends Controller
{
    public function index()
    {
        $this->authorize('purchase-invoices.index');

        $request = request();
        $companyId = active_company_id();

        $from = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfMonth();

        $items = PurchaseInvoiceItem::query()
            ->whereNotNull('purchase_service_id')
            ->whereHas('purchaseService', fn ($q) => $q->where('company_id', $companyId))
            ->with(['purchaseService.category', 'invoice'])
            ->whereHas('invoice', function ($q) use ($companyId, $from, $to) {
                $q->where('company_id', $companyId)
                    ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
                    ->where('status', '!=', 'anulada');
            })
            ->get();

        $byCategory = $items->groupBy(function (PurchaseInvoiceItem $item) {
            return $item->purchaseService?->category?->name ?? 'Sin categoría';
        })->map(function ($group, $categoryName) {
            $neto = $group->sum(fn (PurchaseInvoiceItem $i) => (float) $i->quantity * (float) $i->unit_price);
            $iva = $group->sum(fn (PurchaseInvoiceItem $i) => (float) $i->iva_amount);
            $total = $group->sum(fn (PurchaseInvoiceItem $i) => (float) $i->total);

            return [
                'category_name' => $categoryName,
                'lines' => $group->count(),
                'neto' => $neto,
                'iva' => $iva,
                'total' => $total,
            ];
        })->sortKeys()->values();

        $byService = $items->groupBy('purchase_service_id')->map(function ($group) {
            $first = $group->first();
            $svc = $first->purchaseService;
            $neto = $group->sum(fn (PurchaseInvoiceItem $i) => (float) $i->quantity * (float) $i->unit_price);
            $iva = $group->sum(fn (PurchaseInvoiceItem $i) => (float) $i->iva_amount);
            $total = $group->sum(fn (PurchaseInvoiceItem $i) => (float) $i->total);

            return [
                'code' => $svc?->code,
                'name' => $svc?->name ?? '—',
                'category' => $svc?->category?->name ?? 'Sin categoría',
                'lines' => $group->count(),
                'neto' => $neto,
                'iva' => $iva,
                'total' => $total,
            ];
        })->sortBy('category')->values();

        return view('purchase-services.statistics', [
            'byCategory' => $byCategory,
            'byService' => $byService,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
        ]);
    }
}
