@php
    $colorClasses = [
        'violet' => 'bg-violet-100 text-violet-800 border-violet-200',
        'indigo' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'purple' => 'bg-purple-100 text-purple-800 border-purple-200',
        'orange' => 'bg-orange-100 text-orange-800 border-orange-200',
        'amber' => 'bg-amber-100 text-amber-800 border-amber-200',
        'blue' => 'bg-blue-100 text-blue-800 border-blue-200',
        'teal' => 'bg-teal-100 text-teal-800 border-teal-200',
        'slate' => 'bg-slate-100 text-slate-800 border-slate-200',
        'red' => 'bg-red-100 text-red-800 border-red-200',
        'gray' => 'bg-gray-100 text-gray-800 border-gray-200',
    ];
    $confidenceLabels = [
        'confirmed' => 'Confirmado',
        'estimated' => 'Estimado',
        'manual' => 'Manual',
    ];
    $navParams = fn (array $extra = []) => array_merge($routeParams, $extra);
@endphp

<x-admin-layout>
    <div class="p-4 md:p-6" x-data="{ selected: null, showCompanyLabels: @js($showCompanyLabels), filtersOpen: false }">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
            <div>
                <nav class="text-sm text-gray-500 mb-1">
                    <a href="{{ route('purchases.section') }}" class="hover:text-gray-700">Compras</a>
                    <span class="mx-1">›</span>
                    <span class="text-gray-700 font-medium">Calendario de vencimientos</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">Flujo de caja</h1>
                <div class="mt-3 flex flex-wrap items-end gap-x-4 gap-y-1">
                    <p class="text-2xl md:text-3xl font-bold text-gray-900 capitalize leading-none tracking-tight">
                        @if($view === 'week')
                            {{ $from->locale('es')->isoFormat('D MMM') }} – {{ $to->locale('es')->isoFormat('D MMM YYYY') }}
                        @else
                            {{ $anchor->locale('es')->isoFormat('MMMM YYYY') }}
                        @endif
                    </p>
                    <p class="text-sm md:text-base text-gray-600 pb-0.5">
                        Total del período:
                        <span class="text-lg font-bold text-indigo-700">${{ number_format($totalPeriod, 2, ',', '.') }}</span>
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 items-center justify-end">
                <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-sm">
                    <a href="{{ route('cash-flow.index', $navParams(['view' => 'month', 'date' => $anchor->toDateString()])) }}"
                       class="px-3 py-2 {{ $view === 'month' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Mes</a>
                    <a href="{{ route('cash-flow.index', $navParams(['view' => 'week', 'date' => $anchor->toDateString()])) }}"
                       class="px-3 py-2 {{ $view === 'week' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Semana</a>
                </div>
                <a href="{{ route('cash-flow.index', $navParams(['view' => $view, 'date' => $prevDate])) }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">←</a>
                <a href="{{ route('cash-flow.index', $navParams(['view' => $view, 'date' => now()->toDateString()])) }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">Hoy</a>
                <a href="{{ route('cash-flow.index', $navParams(['view' => $view, 'date' => $nextDate])) }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">→</a>
                @can('cash-flow.manage')
                    <a href="{{ route('cash-flow.obligations.create') }}" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">+ Obligación</a>
                    <a href="{{ route('cash-flow.settings.edit') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Configuración</a>
                @endcan

                <div class="relative" @click.outside="filtersOpen = false">
                    <button type="button" @click="filtersOpen = !filtersOpen"
                        class="inline-flex items-center gap-1.5 px-3 py-2 border rounded-lg text-sm hover:bg-gray-50 {{ $filtersActive ? 'border-indigo-300 bg-indigo-50 text-indigo-800' : 'border-gray-300 text-gray-700' }}">
                        Filtros
                        @if($filtersActive)
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                        @endif
                        <svg class="w-4 h-4 transition-transform" :class="filtersOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="filtersOpen" x-cloak
                         class="absolute right-0 top-full mt-1 z-40 w-72 max-h-[min(70vh,28rem)] overflow-y-auto bg-white rounded-xl border border-gray-200 shadow-lg p-3">
                        <form method="GET" action="{{ route('cash-flow.index') }}" id="cash-flow-filters">
                            <input type="hidden" name="view" value="{{ $view }}">
                            <input type="hidden" name="date" value="{{ $anchor->toDateString() }}">
                            <input type="hidden" name="filters" value="1">

                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="text-xs font-semibold text-gray-900 uppercase tracking-wide">Filtros</span>
                                @if($filtersActive)
                                    <a href="{{ route('cash-flow.index', ['view' => $view, 'date' => $anchor->toDateString()]) }}"
                                       class="text-xs text-indigo-600 hover:text-indigo-800">Limpiar</a>
                                @endif
                            </div>

                            <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-1.5">Tipo</p>
                            <div class="space-y-1.5 mb-3">
                                @foreach($categories as $slug => $meta)
                                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer select-none py-0.5">
                                        <input type="checkbox" name="categories[]" value="{{ $slug }}"
                                            {{ in_array($slug, $activeCategories, true) ? 'checked' : '' }}
                                            onchange="document.getElementById('cash-flow-filters').submit()"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="w-2.5 h-2.5 rounded shrink-0 {{ str_replace(['text-', 'border-'], ['bg-', ''], explode(' ', $colorClasses[$meta['color']] ?? $colorClasses['gray'])[0]) }}"></span>
                                        <span class="truncate">{{ $meta['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>

                            @if($companies->count() > 1)
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-1.5 pt-2 border-t border-gray-100">Empresa</p>
                                <div class="space-y-1.5">
                                    @foreach($companies as $company)
                                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer select-none py-0.5">
                                            <input type="checkbox" name="companies[]" value="{{ $company->id }}"
                                                {{ in_array($company->id, $activeCompanyIds, true) ? 'checked' : '' }}
                                                onchange="document.getElementById('cash-flow-filters').submit()"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="truncate" title="{{ $company->name }}">{{ $company->displayName() }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
            <div class="xl:col-span-3 space-y-4">
                @if($view === 'month')
                    @php
                        $startGrid = $from->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                        $endGrid = $to->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
                        $weeks = [];
                        $day = $startGrid->copy();
                        while ($day->lte($endGrid)) {
                            $week = [];
                            for ($i = 0; $i < 7; $i++) {
                                $week[] = $day->copy();
                                $day->addDay();
                            }
                            $weeks[] = $week;
                        }
                    @endphp
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-indigo-50/60 flex items-center justify-between gap-3">
                            <h2 class="text-lg md:text-xl font-bold text-gray-900 capitalize">
                                {{ $anchor->locale('es')->isoFormat('MMMM YYYY') }}
                            </h2>
                            <span class="text-sm font-medium text-indigo-800 bg-white/80 border border-indigo-100 rounded-lg px-3 py-1">
                                {{ $events->count() }} {{ $events->count() === 1 ? 'vencimiento' : 'vencimientos' }}
                            </span>
                        </div>
                        <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase">
                            @foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d)
                                <div class="px-2 py-2.5 text-center">{{ $d }}</div>
                            @endforeach
                        </div>
                        @foreach($weeks as $week)
                            <div class="grid grid-cols-7 divide-x divide-gray-100 border-b border-gray-100 last:border-b-0">
                                @foreach($week as $cellDate)
                                    @php
                                        $key = $cellDate->toDateString();
                                        $dayEvents = $eventsByDate->get($key, collect());
                                        $inMonth = $cellDate->month === $anchor->month;
                                    @endphp
                                    <div class="p-2 min-h-[165px] md:min-h-[180px] flex flex-col {{ $inMonth ? 'bg-white' : 'bg-gray-50/80' }}">
                                        <div class="text-sm font-semibold mb-2 shrink-0 {{ $inMonth ? 'text-gray-800' : 'text-gray-400' }} {{ $cellDate->isToday() ? 'inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-600 text-white' : '' }}">
                                            {{ $cellDate->day }}
                                        </div>
                                        <div class="space-y-1.5 flex-1 overflow-hidden">
                                            @foreach($dayEvents->take(4) as $ev)
                                                <button type="button" @click="selected = @js($ev)"
                                                    title="{{ ($showCompanyLabels ? $ev['company_short_name'].' · ' : '').$ev['badge_label'].' · '.$ev['title'].' · $'.number_format($ev['amount'], 0, ',', '.') }}"
                                                    class="w-full text-left text-[11px] leading-snug px-1.5 py-1 rounded border {{ $colorClasses[$ev['category_color']] ?? $colorClasses['gray'] }}">
                                                    @if($showCompanyLabels)
                                                        <div class="font-semibold truncate" title="{{ $ev['company_name'] }}">{{ $ev['company_short_name'] }}</div>
                                                    @endif
                                                    <div class="truncate opacity-90">{{ $ev['badge_label'] }}</div>
                                                    <div class="font-medium truncate">${{ number_format($ev['amount'], 0, ',', '.') }}</div>
                                                </button>
                                            @endforeach
                                            @if($dayEvents->count() > 4)
                                                <div class="text-[11px] text-gray-500 px-1">+{{ $dayEvents->count() - 4 }} más</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
                        @for($i = 0; $i < 7; $i++)
                            @php
                                $cellDate = $from->copy()->addDays($i);
                                $key = $cellDate->toDateString();
                                $dayEvents = $eventsByDate->get($key, collect());
                            @endphp
                            <div class="bg-white rounded-xl border border-gray-200 p-3 min-h-[240px]">
                                <div class="text-sm font-semibold text-gray-800 mb-2 {{ $cellDate->isToday() ? 'text-indigo-600' : '' }}">
                                    {{ $cellDate->locale('es')->isoFormat('ddd D/M') }}
                                </div>
                                <div class="space-y-2">
                                    @forelse($dayEvents as $ev)
                                        <button type="button" @click="selected = @js($ev)"
                                            class="w-full text-left p-2 rounded-lg border text-xs {{ $colorClasses[$ev['category_color']] ?? $colorClasses['gray'] }}">
                                            @if($showCompanyLabels)
                                                <div class="font-semibold truncate text-[10px] uppercase tracking-wide opacity-80">{{ $ev['company_short_name'] }}</div>
                                            @endif
                                            <div class="font-semibold truncate">{{ $ev['badge_label'] }}</div>
                                            <div class="truncate opacity-80">{{ $ev['title'] }}</div>
                                            <div>${{ number_format($ev['amount'], 2, ',', '.') }}</div>
                                        </button>
                                    @empty
                                        <p class="text-xs text-gray-400">Sin vencimientos</p>
                                    @endforelse
                                </div>
                            </div>
                        @endfor
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Leyenda</h2>
                    <div class="space-y-2">
                        @foreach($categories as $slug => $meta)
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-3 h-3 rounded {{ str_replace(['text-', 'border-'], ['bg-', ''], explode(' ', $colorClasses[$meta['color']] ?? $colorClasses['gray'])[0]) }}"></span>
                                <span>{{ $meta['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-3">{{ $settingsLegend }}</p>
                </div>

                @if($showCompanyLabels && $totalsByCompany->isNotEmpty())
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <h2 class="text-sm font-semibold text-gray-900 mb-3">Totales por empresa</h2>
                        <dl class="space-y-2 text-sm">
                            @foreach($totalsByCompany as $companyTotal)
                                <div class="flex justify-between gap-2">
                                    <dt class="text-gray-600 truncate">{{ $companyTotal['name'] }}</dt>
                                    <dd class="font-medium whitespace-nowrap">${{ number_format($companyTotal['total'], 2, ',', '.') }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Totales por categoría</h2>
                    <dl class="space-y-2 text-sm">
                        @forelse($totalsByCategory as $cat => $total)
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-600 truncate">{{ $categories[$cat]['label'] ?? $cat }}</dt>
                                <dd class="font-medium whitespace-nowrap">${{ number_format($total, 2, ',', '.') }}</dd>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500">Sin pagos en el período.</p>
                        @endforelse
                    </dl>
                </div>
            </div>
        </div>

        <div x-show="selected" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @keydown.escape.window="selected = null">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-5" @click.outside="selected = null">
                <template x-if="selected">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900" x-text="selected.title"></h3>
                        <p class="text-sm text-gray-500 mt-1" x-text="selected.category_label"></p>
                        <p class="text-sm text-gray-600 mt-2" x-show="showCompanyLabels && selected.company_short_name">
                            Empresa: <span class="font-medium" x-text="selected.company_short_name" :title="selected.company_name"></span>
                        </p>
                        <p class="text-2xl font-bold text-gray-900 mt-3" x-text="'$' + Number(selected.amount).toLocaleString('es-AR', {minimumFractionDigits: 2})"></p>
                        <p class="text-sm text-gray-600 mt-2">
                            Vencimiento: <span x-text="selected.date"></span>
                            · <span x-text="({confirmed:'Confirmado',estimated:'Estimado',manual:'Manual'})[selected.confidence]"></span>
                        </p>
                        <div class="flex gap-2 mt-4">
                            <template x-if="selected.url">
                                <a :href="selected.url" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">Ver origen</a>
                            </template>
                            <button type="button" @click="selected = null" class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">Cerrar</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-admin-layout>
