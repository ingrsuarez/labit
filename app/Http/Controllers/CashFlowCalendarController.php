<?php

namespace App\Http\Controllers;

use App\Models\CashFlowSetting;
use App\Models\Company;
use App\Services\CashFlowCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $user = $request->user();
        $companies = $user->accessibleCompanies();

        if ($companies->isEmpty()) {
            abort(403, 'No tiene empresas asignadas para consultar el calendario de flujo de caja.');
        }

        $activeCompanyIds = $this->resolveActiveCompanyIds($request, $companies);
        $activeCompanies = $companies->whereIn('id', $activeCompanyIds)->values();
        $showCompanyLabels = $companies->count() > 1;

        $events = $service->eventsForCompanies($activeCompanies, $from, $to);
        $categories = CashFlowCalendarService::categoryMeta();

        $filtersActive = $request->boolean('filters');
        $activeCategories = $this->resolveActiveCategories($request, $categories);

        if ($filtersActive) {
            $events = $events->whereIn('category', $activeCategories)->values();
        }

        $eventsByDate = $events->groupBy('date');
        $totalPeriod = round($events->sum('amount'), 2);
        $totalsByCategory = $events->groupBy('category')->map(fn ($group) => round($group->sum('amount'), 2));
        $totalsByCompany = $events
            ->groupBy('company_id')
            ->map(fn ($group) => [
                'name' => $group->first()['company_short_name'],
                'total' => round($group->sum('amount'), 2),
            ]);

        $settings = CashFlowSetting::forCompany($activeCompanies->first()->id);
        $settingsLegend = $this->settingsLegend($activeCompanies);

        $prevDate = $view === 'week'
            ? $anchor->copy()->subWeek()->toDateString()
            : $anchor->copy()->subMonth()->toDateString();
        $nextDate = $view === 'week'
            ? $anchor->copy()->addWeek()->toDateString()
            : $anchor->copy()->addMonth()->toDateString();

        $routeParams = $this->calendarRouteParams($request, $companies, $activeCompanyIds, $activeCategories, $filtersActive);

        return view('cash-flow.calendar', [
            'view' => $view,
            'anchor' => $anchor,
            'from' => $from,
            'to' => $to,
            'events' => $events,
            'eventsByDate' => $eventsByDate,
            'categories' => $categories,
            'settings' => $settings,
            'settingsLegend' => $settingsLegend,
            'companies' => $companies,
            'activeCompanyIds' => $activeCompanyIds,
            'showCompanyLabels' => $showCompanyLabels,
            'filtersActive' => $filtersActive,
            'totalPeriod' => $totalPeriod,
            'totalsByCategory' => $totalsByCategory,
            'totalsByCompany' => $totalsByCompany,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
            'activeCategories' => $activeCategories,
            'routeParams' => $routeParams,
        ]);
    }

    /**
     * @param  Collection<int, Company>  $companies
     * @return array<int, int>
     */
    protected function resolveActiveCompanyIds(Request $request, Collection $companies): array
    {
        $accessibleIds = $companies->pluck('id')->all();

        if (! $request->boolean('filters')) {
            return $accessibleIds;
        }

        if ($companies->count() <= 1) {
            return $accessibleIds;
        }

        return collect($request->query('companies', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $accessibleIds, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array{label: string, badge: string, color: string}>  $categories
     * @return array<int, string>
     */
    protected function resolveActiveCategories(Request $request, array $categories): array
    {
        $all = array_keys($categories);

        if (! $request->boolean('filters')) {
            return $all;
        }

        return collect($request->query('categories', []))
            ->filter(fn ($c) => isset($categories[$c]))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Company>  $companies
     */
    protected function settingsLegend(Collection $companies): string
    {
        $settings = $companies->map(fn (Company $company) => CashFlowSetting::forCompany($company->id));

        $ivaDays = $settings->pluck('iva_due_day')->unique()->values();
        $form931Days = $settings->pluck('form931_due_day')->unique()->values();

        if ($ivaDays->count() === 1 && $form931Days->count() === 1) {
            return 'IVA vence día '.$ivaDays->first().' · 931 día '.$form931Days->first();
        }

        return 'IVA/931: según configuración por empresa';
    }

    /**
     * @param  Collection<int, Company>  $companies
     * @param  array<int, int>  $activeCompanyIds
     * @param  array<int, string>  $activeCategories
     * @return array<string, mixed>
     */
    protected function calendarRouteParams(
        Request $request,
        Collection $companies,
        array $activeCompanyIds,
        array $activeCategories,
        bool $filtersActive,
    ): array {
        if (! $filtersActive) {
            return [];
        }

        $params = [
            'filters' => 1,
            'categories' => $activeCategories,
        ];

        if ($companies->count() > 1) {
            $params['companies'] = $activeCompanyIds;
        }

        return $params;
    }
}
