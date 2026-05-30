<?php

namespace App\Http\Controllers;

use App\Models\CashFlowSetting;
use App\Services\CashFlowCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashFlowCalendarController extends Controller
{
    public function index(Request $request, CashFlowCalendarService $service): View
    {
        $this->authorize('cash-flow.view');

        $view = $request->query('view', 'month') === 'week' ? 'week' : 'month';
        $anchor = $request->filled('date')
            ? Carbon::parse($request->query('date'))
            : now();

        if ($view === 'week') {
            $from = $anchor->copy()->startOfWeek(Carbon::MONDAY);
            $to = $anchor->copy()->endOfWeek(Carbon::SUNDAY);
        } else {
            $from = $anchor->copy()->startOfMonth();
            $to = $anchor->copy()->endOfMonth();
        }

        $companyId = (int) active_company_id();
        $events = $service->eventsForRange($companyId, $from, $to);
        $categories = CashFlowCalendarService::categoryMeta();
        $settings = CashFlowSetting::forCompany($companyId);

        $activeCategories = collect($request->query('categories', array_keys($categories)))
            ->filter(fn ($c) => isset($categories[$c]))
            ->values()
            ->all();

        if ($activeCategories !== [] && count($activeCategories) < count($categories)) {
            $events = $events->whereIn('category', $activeCategories)->values();
        }

        $eventsByDate = $events->groupBy('date');
        $totalPeriod = round($events->sum('amount'), 2);
        $totalsByCategory = $events->groupBy('category')->map(fn ($group) => round($group->sum('amount'), 2));

        $prevDate = $view === 'week'
            ? $anchor->copy()->subWeek()->toDateString()
            : $anchor->copy()->subMonth()->toDateString();
        $nextDate = $view === 'week'
            ? $anchor->copy()->addWeek()->toDateString()
            : $anchor->copy()->addMonth()->toDateString();

        return view('cash-flow.calendar', [
            'view' => $view,
            'anchor' => $anchor,
            'from' => $from,
            'to' => $to,
            'events' => $events,
            'eventsByDate' => $eventsByDate,
            'categories' => $categories,
            'settings' => $settings,
            'totalPeriod' => $totalPeriod,
            'totalsByCategory' => $totalsByCategory,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
            'activeCategories' => $activeCategories,
        ]);
    }
}
