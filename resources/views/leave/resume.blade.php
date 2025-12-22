<x-admin-layout title="Resumen de Novedades">
    <div class="p-6 space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Resumen de Novedades</h1>
                <p class="text-gray-500">Licencias, vacaciones y horas extras para liquidación</p>
            </div>
            <div class="mt-4 md:mt-0 flex items-center gap-3">
                <a href="{{ route('leave.resume.compact', request()->only(['year', 'month', 'employee_id'])) }}"
                   class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm">
                    📊 Vista Compacta
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Año -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                        <select name="year" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— Todos —</option>
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" @selected(($filters['year'] ?? '') == $y)>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    
                    <!-- Mes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                        <select name="month" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— Todos —</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected(($filters['month'] ?? '') == $m)>
                                    {{ \Carbon\Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    
                    <!-- Empleado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Empleado</label>
                        <select name="employee_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— Todos —</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}" @selected(($filters['employee_id'] ?? '') == $e->id)>
                                    {{ ucfirst($e->lastName) }}, {{ ucfirst($e->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Filtrar
                        </button>
                        <a href="{{ route('leave.resume') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                            Limpiar
                        </a>
                    </div>
                </div>

                <!-- Botones de exportación -->
                <div class="flex items-center justify-between pt-4 border-t">
                    <p class="text-sm text-gray-500">
                        @if($filters['year'] && $filters['month'])
                            Período: {{ \Carbon\Carbon::createFromDate($filters['year'], $filters['month'], 1)->translatedFormat('F Y') }}
                        @elseif($filters['year'])
                            Año: {{ $filters['year'] }}
                        @else
                            Todos los períodos
                        @endif
                    </p>
                    <div class="flex gap-3">
                        <a href="{{ route('leave.export.excel', request()->only(['year', 'month', 'employee_id'])) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 text-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"/>
                            </svg>
                            Excel
                        </a>
                        <a href="{{ route('leave.export.pdf', request()->only(['year', 'month', 'employee_id'])) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 text-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"/>
                            </svg>
                            PDF
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de resultados -->
        @php
            $grouped = $resumes->groupBy(fn($r) => sprintf('%04d-%02d', $r->year, $r->month));
        @endphp

        @forelse($grouped as $ym => $rows)
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <!-- Header del período -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-white">
                            <p class="text-sm text-blue-100">Período</p>
                            <p class="text-xl font-bold">{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->translatedFormat('F Y') }}</p>
                        </div>
                        <div class="text-right text-white">
                            <p class="text-sm text-blue-100">Total registros</p>
                            <p class="text-xl font-bold">{{ $rows->count() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CUIL</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hs/Sem</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cant.</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Días</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hs 50%</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hs 100%</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cert.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($rows as $r)
                                @php
                                    $files = collect(explode('|', (string)$r->files))->filter(fn($f) => !empty($f));
                                    $badgeColors = [
                                        'vacaciones' => 'bg-blue-100 text-blue-800',
                                        'enfermedad' => 'bg-red-100 text-red-800',
                                        'embarazo' => 'bg-pink-100 text-pink-800',
                                        'capacitacion' => 'bg-green-100 text-green-800',
                                        'horas extra' => 'bg-amber-100 text-amber-800',
                                    ];
                                    $badgeColor = $badgeColors[$r->type] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $r->employee }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $r->cuil ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">{{ (int)($r->weekly_hours ?? 0) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                                            {{ ucfirst($r->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $r->cantidad }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-semibold text-gray-900">{{ (int)$r->total_dias }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm {{ (int)$r->horas_50 > 0 ? 'text-amber-600 font-medium' : 'text-gray-400' }}">
                                        {{ (int)$r->horas_50 > 0 ? (int)$r->horas_50 : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm {{ (int)$r->horas_100 > 0 ? 'text-orange-600 font-medium' : 'text-gray-400' }}">
                                        {{ (int)$r->horas_100 > 0 ? (int)$r->horas_100 : '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($files->isEmpty())
                                            <span class="text-gray-400 text-sm">—</span>
                                        @else
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($files as $idx => $file)
                                                    <a href="{{ asset('storage/'.$file) }}" target="_blank"
                                                       class="inline-flex items-center px-2 py-1 rounded bg-blue-50 text-blue-700 text-xs hover:bg-blue-100">
                                                        📄 {{ $idx + 1 }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('leave.edit', $r->id) }}"
                                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Resumen del período -->
                <div class="bg-gray-50 px-6 py-4 border-t">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
                        @php
                            $vacDays = $rows->where('type', 'vacaciones')->sum('total_dias');
                            $enfDays = $rows->where('type', 'enfermedad')->sum('total_dias');
                            $embDays = $rows->where('type', 'embarazo')->sum('total_dias');
                            $h50 = $rows->sum('horas_50');
                            $h100 = $rows->sum('horas_100');
                        @endphp
                        <div>
                            <p class="text-xs text-gray-500">Vacaciones</p>
                            <p class="text-lg font-bold text-blue-600">{{ (int)$vacDays }} días</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Enfermedad</p>
                            <p class="text-lg font-bold text-red-600">{{ (int)$enfDays }} días</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Embarazo</p>
                            <p class="text-lg font-bold text-pink-600">{{ (int)$embDays }} días</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Horas 50%</p>
                            <p class="text-lg font-bold text-amber-600">{{ (int)$h50 }} hs</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Horas 100%</p>
                            <p class="text-lg font-bold text-orange-600">{{ (int)$h100 }} hs</p>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay novedades</h3>
                <p class="text-gray-500 mb-4">No se encontraron licencias para el período seleccionado.</p>
                <a href="{{ route('leave.new') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    + Nueva Licencia
                </a>
            </div>
        @endforelse

        <!-- Acciones rápidas -->
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('leave.new') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                + Nueva Licencia
            </a>
            <span class="text-gray-300">|</span>
            <a href="{{ route('leave.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                Ver todas las licencias
            </a>
            <span class="text-gray-300">|</span>
            <a href="{{ route('payroll.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                Ir a Liquidaciones
            </a>
        </div>

    </div>
</x-admin-layout>
