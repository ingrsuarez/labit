@props(['data'])

@php
    $palette = config('dashboard.financial.palettes.'.($data['key'] ?? 'ventas'), [
        'icon_bg' => 'bg-gray-100',
        'icon_text' => 'text-gray-600',
        'bar' => 'bg-gray-400',
        'bar_current' => 'bg-gray-600',
    ]);

    $values = array_column($data['monthly'] ?? [], 'value');
    $maxValue = ! empty($values) ? max(max($values), 1) : 1;
    $variation = $data['variation_percent'] ?? null;
    $current = (float) ($data['current_total'] ?? 0);

    $iconPaths = [
        'ventas' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
        'compras' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z',
        'ingresos' => 'M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4',
        'egresos' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
    ];
    $iconPath = $iconPaths[$data['key'] ?? 'ventas'] ?? $iconPaths['ventas'];

    $monthCurrent = now()->locale('es')->isoFormat('MMMM YYYY');
    $monthPrev = now()->subMonth()->locale('es')->isoFormat('MMMM');
@endphp

<div class="bg-white rounded-xl shadow-sm border p-6"
     role="region"
     aria-label="{{ $data['label'] }} del mes: ${{ number_format($current, 0, ',', '.') }}">

    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-lg flex items-center justify-center {{ $palette['icon_bg'] }}">
                <svg class="w-5 h-5 {{ $palette['icon_text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                </svg>
            </span>
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ $data['label'] }} del mes
            </h3>
        </div>
        <span class="text-xs text-gray-400">{{ $monthCurrent }}</span>
    </div>

    <p class="text-3xl font-bold text-gray-900 mb-1">
        ${{ number_format($current, 0, ',', '.') }}
    </p>

    <div class="text-sm mb-4 h-5">
        @if (is_null($variation))
            <span class="text-gray-400">— sin datos previos</span>
        @elseif ($variation > 0)
            <span class="text-emerald-600 font-medium">
                ▲ +{{ number_format($variation, 1, ',', '.') }}% vs {{ $monthPrev }}
            </span>
        @elseif ($variation < 0)
            <span class="text-rose-600 font-medium">
                ▼ {{ number_format($variation, 1, ',', '.') }}% vs {{ $monthPrev }}
            </span>
        @else
            <span class="text-gray-500">Sin cambios vs {{ $monthPrev }}</span>
        @endif
    </div>

    <div class="flex items-end justify-between h-36 gap-1.5"
         x-data="{ hovered: null }">
        @foreach ($data['monthly'] ?? [] as $idx => $month)
            @php
                $altura = $maxValue > 0 ? (max($month['value'], 0) / $maxValue) * 100 : 0;
                $barColor = $month['is_current'] ? $palette['bar_current'] : $palette['bar'];
                $tooltipMonto = '$'.number_format($month['value'], 0, ',', '.');
            @endphp
            <div class="flex-1 flex flex-col items-center group cursor-default"
                 @mouseenter="hovered = {{ $idx }}"
                 @mouseleave="hovered = null"
                 title="{{ $month['label'] }}: {{ $tooltipMonto }}">
                <div class="relative w-full flex flex-col items-end justify-end h-28">
                    <div x-show="hovered === {{ $idx }}"
                         x-transition.opacity
                         class="absolute -top-7 left-1/2 -translate-x-1/2 px-2 py-0.5 bg-gray-900 text-white text-[10px] rounded whitespace-nowrap z-10"
                         style="display: none;">
                        {{ $tooltipMonto }}
                    </div>
                    <div class="w-full {{ $barColor }} rounded-t transition-all duration-300"
                         style="height: {{ max($altura, 2) }}%"></div>
                </div>
                <span class="text-[10px] mt-1.5 transform -rotate-45 origin-top-left whitespace-nowrap {{ $month['is_current'] ? 'font-semibold text-gray-700' : 'text-gray-500' }}">
                    {{ $month['label'] }}
                </span>
            </div>
        @endforeach
    </div>
</div>
