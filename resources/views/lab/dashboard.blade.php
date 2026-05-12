<x-lab-layout>
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Dashboard del Laboratorio</h1>

        @if($branches->count() > 1)
        <div class="flex items-center gap-2">
            <label for="branch-selector" class="text-sm text-gray-500">Sede:</label>
            <select id="branch-selector"
                    onchange="window.location.href = this.value === 'all' ? '{{ route('lab.dashboard') }}?branch=all' : '{{ route('lab.dashboard') }}?branch=' + this.value"
                    class="text-sm border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                <option value="all" {{ $currentBranch === null ? 'selected' : '' }}>Todas las sedes</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ (int) $currentBranch === $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Creados (30d) --}}
        <div class="bg-white rounded-xl border shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="w-9 h-9 rounded-lg flex items-center justify-center bg-gray-100">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Creados (30d)</span>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($kpis['created']) }}</p>
        </div>

        {{-- Pendientes de validar (vivo) --}}
        <div class="rounded-xl border shadow-sm p-5 {{ $kpis['pending_validation'] > 0 ? 'bg-amber-50 border-amber-200' : 'bg-white' }}">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="w-9 h-9 rounded-lg flex items-center justify-center {{ $kpis['pending_validation'] > 0 ? 'bg-amber-100' : 'bg-green-100' }}">
                    <svg class="w-5 h-5 {{ $kpis['pending_validation'] > 0 ? 'text-amber-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pend. Validar</span>
            </div>
            <p class="text-3xl font-bold {{ $kpis['pending_validation'] > 0 ? 'text-amber-700' : 'text-green-700' }}">
                {{ number_format($kpis['pending_validation']) }}
            </p>
        </div>

        {{-- Validados (30d) --}}
        <div class="bg-white rounded-xl border shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="w-9 h-9 rounded-lg flex items-center justify-center bg-purple-100">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Validados (30d)</span>
            </div>
            <p class="text-3xl font-bold text-purple-700">{{ number_format($kpis['validated']) }}</p>
        </div>

        {{-- Enviados (30d) --}}
        <div class="bg-white rounded-xl border shadow-sm p-5">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="w-9 h-9 rounded-lg flex items-center justify-center bg-sky-100">
                    <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Enviados (30d)</span>
            </div>
            <p class="text-3xl font-bold text-sky-700">{{ number_format($kpis['sent']) }}</p>
        </div>
    </div>

    {{-- Alerta de atrasados --}}
    @if($overdue['count'] > 0)
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <p class="text-sm text-red-800">
                <span class="font-semibold">{{ $overdue['count'] }} {{ $overdue['count'] === 1 ? 'protocolo pendiente' : 'protocolos pendientes' }}</span>
                de validar con más de 3 días de antigüedad
                @if($overdue['oldest_date'])
                    (desde {{ \Carbon\Carbon::parse($overdue['oldest_date'])->format('d/m/Y') }})
                @endif
            </p>
        </div>
        <a href="{{ route('lab.admissions.index', ['status' => 'pending']) }}"
           class="text-sm font-medium text-red-700 hover:text-red-900 whitespace-nowrap ml-4">
            Ver pendientes →
        </a>
    </div>
    @endif

    {{-- Gráficos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Estado de protocolos --}}
        <div class="bg-white rounded-xl border shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Estado de protocolos</h3>
            @php
                $statusData = [
                    ['key' => 'pending', 'label' => 'Pendiente', 'color' => 'bg-yellow-400', 'value' => $byStatus['pending'], 'link' => route('lab.admissions.index', ['status' => 'pending'])],
                    ['key' => 'in_progress', 'label' => 'En Proceso', 'color' => 'bg-blue-400', 'value' => $byStatus['in_progress'], 'link' => route('lab.admissions.index', ['status' => 'in_progress'])],
                    ['key' => 'completed', 'label' => 'Completado', 'color' => 'bg-green-400', 'value' => $byStatus['completed'], 'link' => route('lab.admissions.index', ['status' => 'completed'])],
                    ['key' => 'validated', 'label' => 'Validado', 'color' => 'bg-purple-400', 'value' => $byStatus['validated'], 'link' => route('lab.admissions.index', ['status' => 'validated'])],
                    ['key' => 'sent', 'label' => 'Enviado', 'color' => 'bg-sky-400', 'value' => $byStatus['sent'], 'link' => route('lab.admissions.index', ['status' => 'validated'])],
                ];
                $statusMax = max(1, max(array_column($statusData, 'value')));
            @endphp

            @if(array_sum(array_column($statusData, 'value')) === 0)
                <div class="flex items-center justify-center h-48 text-gray-400 text-sm">Sin protocolos registrados</div>
            @else
                <div class="flex items-end justify-between h-48 gap-3" x-data="{ hovered: null }">
                    @foreach($statusData as $idx => $bar)
                        @php
                            $height = $statusMax > 0 ? ($bar['value'] / $statusMax) * 100 : 0;
                            if ($bar['value'] > 0 && $height < 4) $height = 4;
                        @endphp
                        <a href="{{ $bar['link'] }}"
                           class="flex-1 flex flex-col items-center group"
                           @mouseenter="hovered = {{ $idx }}"
                           @mouseleave="hovered = null">
                            <div class="relative w-full flex flex-col items-center justify-end h-36">
                                <div x-show="hovered === {{ $idx }}"
                                     x-transition.opacity
                                     class="absolute -top-7 left-1/2 -translate-x-1/2 px-2 py-0.5 bg-gray-900 text-white text-[10px] rounded whitespace-nowrap z-10"
                                     style="display: none;">
                                    {{ $bar['label'] }}: {{ number_format($bar['value']) }}
                                </div>
                                <span class="text-xs font-semibold text-gray-700 mb-1">{{ number_format($bar['value']) }}</span>
                                <div class="w-full {{ $bar['color'] }} rounded-t transition-all duration-300 group-hover:opacity-80"
                                     style="height: {{ $height }}%"></div>
                            </div>
                            <span class="text-[10px] mt-1.5 text-gray-500 text-center leading-tight">{{ $bar['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Pendientes por tipo de laboratorio --}}
        <div class="bg-white rounded-xl border shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Pendientes por tipo</h3>
            @php
                $typeData = [
                    ['label' => 'Clínico', 'color' => 'bg-teal-400', 'value' => $byLabType['clinico'], 'link' => route('lab.admissions.index', ['status' => 'pending'])],
                    ['label' => 'Veterinario', 'color' => 'bg-amber-400', 'value' => $byLabType['veterinario'], 'link' => route('vet.admissions.index', ['status' => 'pending'])],
                    ['label' => 'Aguas', 'color' => 'bg-cyan-400', 'value' => $byLabType['aguas'], 'link' => route('sample.index')],
                ];
                $typeMax = max(1, max(array_column($typeData, 'value')));
            @endphp

            @if(array_sum(array_column($typeData, 'value')) === 0)
                <div class="flex items-center justify-center h-48 text-gray-400 text-sm">Sin protocolos pendientes</div>
            @else
                <div class="flex items-end justify-around h-48 gap-6" x-data="{ hovered: null }">
                    @foreach($typeData as $idx => $bar)
                        @php
                            $height = $typeMax > 0 ? ($bar['value'] / $typeMax) * 100 : 0;
                            if ($bar['value'] > 0 && $height < 4) $height = 4;
                        @endphp
                        <a href="{{ $bar['link'] }}"
                           class="flex-1 flex flex-col items-center group max-w-[120px]"
                           @mouseenter="hovered = {{ $idx }}"
                           @mouseleave="hovered = null">
                            <div class="relative w-full flex flex-col items-center justify-end h-36">
                                <div x-show="hovered === {{ $idx }}"
                                     x-transition.opacity
                                     class="absolute -top-7 left-1/2 -translate-x-1/2 px-2 py-0.5 bg-gray-900 text-white text-[10px] rounded whitespace-nowrap z-10"
                                     style="display: none;">
                                    {{ $bar['label'] }}: {{ number_format($bar['value']) }}
                                </div>
                                <span class="text-xs font-semibold text-gray-700 mb-1">{{ number_format($bar['value']) }}</span>
                                <div class="w-full {{ $bar['color'] }} rounded-t transition-all duration-300 group-hover:opacity-80"
                                     style="height: {{ $height }}%"></div>
                            </div>
                            <span class="text-xs mt-1.5 text-gray-500 font-medium">{{ $bar['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Pendientes por sede --}}
    @if(count($byBranch) > 1)
    <div class="bg-white rounded-xl border shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Pendientes por sede</h3>
        @php
            $branchMax = max(1, max(array_column($byBranch, 'pending')));
        @endphp

        @if(array_sum(array_column($byBranch, 'pending')) === 0)
            <div class="flex items-center justify-center h-20 text-gray-400 text-sm">Sin protocolos pendientes</div>
        @else
            <div class="space-y-3">
                @foreach($byBranch as $branchItem)
                    @php
                        $width = $branchMax > 0 ? ($branchItem['pending'] / $branchMax) * 100 : 0;
                        if ($branchItem['pending'] > 0 && $width < 3) $width = 3;
                        $barColor = 'bg-teal-400';
                        if ($branchItem['pending'] > 20) $barColor = 'bg-red-400';
                        elseif ($branchItem['pending'] > 10) $barColor = 'bg-amber-400';
                    @endphp
                    <a href="{{ route('lab.admissions.index', ['branch' => $branchItem['id']]) }}"
                       class="flex items-center gap-4 group hover:bg-gray-50 rounded-lg px-2 py-1 -mx-2 transition-colors">
                        <span class="text-sm text-gray-700 w-36 truncate flex-shrink-0">{{ $branchItem['name'] }}</span>
                        <div class="flex-1 h-6 bg-gray-100 rounded-full overflow-hidden">
                            <div class="{{ $barColor }} h-full rounded-full transition-all duration-300 group-hover:opacity-80"
                                 style="width: {{ $width }}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 w-10 text-right">{{ $branchItem['pending'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
    @endif

    {{-- Tarjetas de navegación rápida --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        @foreach($navItems as $item)
            <a href="{{ $item['route'] }}"
               class="flex flex-col items-center gap-3 px-4 py-5 bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-teal-300 transition-all group">
                <div class="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center group-hover:bg-teal-100 transition-colors">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                    </svg>
                </div>
                <div class="text-center">
                    <span class="block text-sm font-semibold text-gray-800 group-hover:text-teal-700 transition-colors">{{ $item['name'] }}</span>
                    <span class="block text-xs text-gray-400 mt-0.5">{{ $item['description'] }}</span>
                </div>
            </a>
        @endforeach
    </div>

</div>
</x-lab-layout>
