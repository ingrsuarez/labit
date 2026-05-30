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
@endphp

<x-admin-layout>
    <div class="p-4 md:p-6" x-data="{ selected: null }">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
            <div>
                <nav class="text-sm text-gray-500 mb-1">
                    <a href="{{ route('purchases.section') }}" class="hover:text-gray-700">Compras</a>
                    <span class="mx-1">›</span>
                    <span class="text-gray-700 font-medium">Calendario de vencimientos</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">Flujo de caja</h1>
                <p class="text-sm text-gray-500 mt-1">
                    @if($view === 'week')
                        Semana del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}
                    @else
                        {{ $anchor->locale('es')->isoFormat('MMMM YYYY') }}
                    @endif
                    · Total: <span class="font-semibold text-gray-800">${{ number_format($totalPeriod, 2, ',', '.') }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
                <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-sm">
                    <a href="{{ route('cash-flow.index', ['view' => 'month', 'date' => $anchor->toDateString()]) }}"
                       class="px-3 py-2 {{ $view === 'month' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Mes</a>
                    <a href="{{ route('cash-flow.index', ['view' => 'week', 'date' => $anchor->toDateString()]) }}"
                       class="px-3 py-2 {{ $view === 'week' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Semana</a>
                </div>
                <a href="{{ route('cash-flow.index', ['view' => $view, 'date' => $prevDate]) }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">←</a>
                <a href="{{ route('cash-flow.index', ['view' => $view, 'date' => now()->toDateString()]) }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">Hoy</a>
                <a href="{{ route('cash-flow.index', ['view' => $view, 'date' => $nextDate]) }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">→</a>
                @can('cash-flow.manage')
                    <a href="{{ route('cash-flow.obligations.create') }}" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">+ Obligación</a>
                    <a href="{{ route('cash-flow.settings.edit') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Configuración</a>
                @endcan
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
                        <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase">
                            @foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d)
                                <div class="px-2 py-2 text-center">{{ $d }}</div>
                            @endforeach
                        </div>
                        @foreach($weeks as $week)
                            <div class="grid grid-cols-7 divide-x divide-gray-100 border-b border-gray-100 last:border-b-0 min-h-[110px]">
                                @foreach($week as $cellDate)
                                    @php
                                        $key = $cellDate->toDateString();
                                        $dayEvents = $eventsByDate->get($key, collect());
                                        $inMonth = $cellDate->month === $anchor->month;
                                    @endphp
                                    <div class="p-1.5 {{ $inMonth ? 'bg-white' : 'bg-gray-50/80' }}">
                                        <div class="text-xs font-medium mb-1 {{ $inMonth ? 'text-gray-700' : 'text-gray-400' }} {{ $cellDate->isToday() ? 'text-indigo-600' : '' }}">
                                            {{ $cellDate->day }}
                                        </div>
                                        <div class="space-y-1">
                                            @foreach($dayEvents->take(3) as $ev)
                                                <button type="button" @click="selected = @js($ev)"
                                                    class="w-full text-left text-[10px] leading-tight px-1 py-0.5 rounded border truncate {{ $colorClasses[$ev['category_color']] ?? $colorClasses['gray'] }}">
                                                    ${{ number_format($ev['amount'], 0, ',', '.') }}
                                                </button>
                                            @endforeach
                                            @if($dayEvents->count() > 3)
                                                <div class="text-[10px] text-gray-500 px-1">+{{ $dayEvents->count() - 3 }} más</div>
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
                            <div class="bg-white rounded-xl border border-gray-200 p-3 min-h-[200px]">
                                <div class="text-sm font-semibold text-gray-800 mb-2 {{ $cellDate->isToday() ? 'text-indigo-600' : '' }}">
                                    {{ $cellDate->locale('es')->isoFormat('ddd D/M') }}
                                </div>
                                <div class="space-y-2">
                                    @forelse($dayEvents as $ev)
                                        <button type="button" @click="selected = @js($ev)"
                                            class="w-full text-left p-2 rounded-lg border text-xs {{ $colorClasses[$ev['category_color']] ?? $colorClasses['gray'] }}">
                                            <div class="font-medium truncate">{{ $ev['title'] }}</div>
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
                    <p class="text-xs text-gray-500 mt-3">IVA vence día {{ $settings->iva_due_day }} · 931 día {{ $settings->form931_due_day }}</p>
                </div>

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
