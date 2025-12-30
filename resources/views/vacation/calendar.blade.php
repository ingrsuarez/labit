<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-6xl mx-auto">
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('vacation.index') }}" 
                       class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Calendario de Vacaciones {{ $year }}</h1>
                        <p class="text-sm text-gray-500 mt-1">Vista anual de vacaciones</p>
                    </div>
                </div>
                <div class="flex gap-2 mt-4 sm:mt-0">
                    <a href="{{ route('vacation.calendar', ['year' => $year - 1]) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        ← {{ $year - 1 }}
                    </a>
                    <a href="{{ route('vacation.calendar', ['year' => $year + 1]) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        {{ $year + 1 }} →
                    </a>
                </div>
            </div>

            @php
                $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                
                // Detectar qué meses tienen vacaciones
                $activeMonths = [];
                foreach($vacations as $vac) {
                    $start = \Carbon\Carbon::parse($vac->start);
                    $end = \Carbon\Carbon::parse($vac->end);
                    for ($date = $start->copy()->startOfMonth(); $date <= $end; $date->addMonth()) {
                        if ($date->year == $year) {
                            $activeMonths[$date->month] = true;
                        }
                    }
                }
                $activeMonths = collect($activeMonths)->keys()->sort()->values();
                
                // Si no hay meses activos, mostrar el mes actual
                if ($activeMonths->isEmpty()) {
                    $activeMonths = collect([now()->month]);
                }
                
                // Detectar superposiciones (solo si tienen misma categoría o puesto)
                $overlapsPerDay = [];
                foreach($vacations as $vac) {
                    $start = \Carbon\Carbon::parse($vac->start);
                    $end = \Carbon\Carbon::parse($vac->end);
                    
                    // Obtener categorías y puestos del empleado
                    $empJobs = $vac->employee->jobs ?? collect();
                    $empCategories = $empJobs->pluck('category_id')->filter()->toArray();
                    $empJobIds = $empJobs->pluck('id')->toArray();
                    
                    for ($date = $start->copy(); $date <= $end; $date->addDay()) {
                        $key = $date->format('Y-m-d');
                        if (!isset($overlapsPerDay[$key])) {
                            $overlapsPerDay[$key] = [];
                        }
                        $overlapsPerDay[$key][] = [
                            'vacation' => $vac,
                            'categories' => $empCategories,
                            'jobs' => $empJobIds,
                        ];
                    }
                }
                
                // Filtrar solo días con superposición real (misma categoría o puesto)
                $conflictDays = collect($overlapsPerDay)->filter(function($entries) {
                    if (count($entries) < 2) return false;
                    
                    // Verificar si hay empleados con misma categoría o puesto
                    for ($i = 0; $i < count($entries); $i++) {
                        for ($j = $i + 1; $j < count($entries); $j++) {
                            // Misma categoría?
                            $commonCategories = array_intersect($entries[$i]['categories'], $entries[$j]['categories']);
                            if (!empty($commonCategories)) return true;
                            
                            // Mismo puesto?
                            $commonJobs = array_intersect($entries[$i]['jobs'], $entries[$j]['jobs']);
                            if (!empty($commonJobs)) return true;
                        }
                    }
                    return false;
                });
                
                // Vacaciones con conflicto
                $vacationsWithConflict = [];
                foreach ($vacations as $vac) {
                    $start = \Carbon\Carbon::parse($vac->start);
                    $end = \Carbon\Carbon::parse($vac->end);
                    
                    $empJobs = $vac->employee->jobs ?? collect();
                    $empCategories = $empJobs->pluck('category_id')->filter()->toArray();
                    $empJobIds = $empJobs->pluck('id')->toArray();
                    
                    for ($date = $start->copy(); $date <= $end; $date->addDay()) {
                        $key = $date->format('Y-m-d');
                        if ($conflictDays->has($key)) {
                            // Verificar si esta vacación específica tiene conflicto
                            $dayEntries = $overlapsPerDay[$key];
                            foreach ($dayEntries as $entry) {
                                if ($entry['vacation']->id === $vac->id) continue;
                                
                                $commonCategories = array_intersect($empCategories, $entry['categories']);
                                $commonJobs = array_intersect($empJobIds, $entry['jobs']);
                                
                                if (!empty($commonCategories) || !empty($commonJobs)) {
                                    $vacationsWithConflict[$vac->id] = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                
                // Calcular rango total de días a mostrar
                $firstMonth = $activeMonths->first();
                $lastMonth = $activeMonths->last();
                $rangeStart = \Carbon\Carbon::create($year, $firstMonth, 1)->startOfMonth();
                $rangeEnd = \Carbon\Carbon::create($year, $lastMonth, 1)->endOfMonth();
                $totalDays = $rangeStart->diffInDays($rangeEnd) + 1;
            @endphp

            {{-- Leyenda --}}
            <div class="bg-white rounded-xl shadow p-4 mb-6">
                <div class="flex flex-wrap gap-6 justify-center">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-green-500 rounded mr-2"></div>
                        <span class="text-sm">Aprobadas</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-amber-400 rounded mr-2"></div>
                        <span class="text-sm">Pendientes</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-red-500 rounded mr-2"></div>
                        <span class="text-sm">Superposición</span>
                    </div>
                </div>
            </div>

            {{-- Alerta de Superposiciones --}}
            @if($conflictDays->count() > 0)
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-red-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <h3 class="font-semibold text-red-800">{{ $conflictDays->count() }} días con superposiciones</h3>
                            <p class="text-sm text-red-600">Empleados con vacaciones coincidentes</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Timeline por Meses Activos --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white">Timeline de Vacaciones</h2>
                </div>
                <div class="p-6">
                    {{-- Cabecera de meses --}}
                    <div class="flex items-center mb-4">
                        <div class="w-36 flex-shrink-0"></div>
                        <div class="flex-1 flex">
                            @foreach($activeMonths as $m)
                                @php
                                    $daysInMonth = \Carbon\Carbon::create($year, $m, 1)->daysInMonth;
                                    $monthStart = \Carbon\Carbon::create($year, $m, 1);
                                    $widthPercent = ($daysInMonth / $totalDays) * 100;
                                @endphp
                                <div class="text-center text-sm font-semibold text-indigo-700 border-l border-indigo-100 first:border-l-0"
                                     style="width: {{ $widthPercent }}%;">
                                    {{ $monthNames[$m - 1] }}
                                    <div class="text-xs font-normal text-gray-400">{{ $daysInMonth }} días</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Filas de empleados --}}
                    @foreach($employees as $emp)
                        @php
                            $empVacations = $vacations->where('employee_id', $emp->id);
                        @endphp
                        @if($empVacations->count() > 0)
                            <div class="flex items-center mb-3 group">
                                <div class="w-36 flex-shrink-0 pr-3">
                                    <div class="font-medium text-gray-900 text-sm truncate">{{ $emp->lastName }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ $emp->name }}</div>
                                </div>
                                <div class="flex-1 h-10 bg-gray-100 rounded-lg relative overflow-hidden">
                                    {{-- Líneas de separación de meses --}}
                                    @php $cumDays = 0; @endphp
                                    @foreach($activeMonths as $idx => $m)
                                        @if($idx > 0)
                                            @php
                                                $leftPercent = ($cumDays / $totalDays) * 100;
                                            @endphp
                                            <div class="absolute top-0 bottom-0 w-px bg-gray-300" style="left: {{ $leftPercent }}%;"></div>
                                        @endif
                                        @php
                                            $cumDays += \Carbon\Carbon::create($year, $m, 1)->daysInMonth;
                                        @endphp
                                    @endforeach
                                    
                                    {{-- Barras de vacaciones --}}
                                    @foreach($empVacations as $vac)
                                        @php
                                            $vStart = \Carbon\Carbon::parse($vac->start);
                                            $vEnd = \Carbon\Carbon::parse($vac->end);
                                            
                                            // Ajustar al rango visible
                                            $startClamped = $vStart < $rangeStart ? $rangeStart : $vStart;
                                            $endClamped = $vEnd > $rangeEnd ? $rangeEnd : $vEnd;
                                            
                                            $startDay = $rangeStart->diffInDays($startClamped);
                                            $duration = $startClamped->diffInDays($endClamped) + 1;
                                            
                                            $leftPercent = ($startDay / $totalDays) * 100;
                                            $widthPercent = ($duration / $totalDays) * 100;
                                            
                                            $hasConflict = isset($vacationsWithConflict[$vac->id]);
                                            
                                            if ($hasConflict) {
                                                $bgColor = 'bg-gradient-to-r from-red-500 to-red-600';
                                                $borderClass = 'ring-2 ring-red-300';
                                            } elseif ($vac->status === 'aprobado') {
                                                $bgColor = 'bg-gradient-to-r from-green-500 to-green-600';
                                                $borderClass = '';
                                            } else {
                                                $bgColor = 'bg-gradient-to-r from-amber-400 to-amber-500';
                                                $borderClass = '';
                                            }
                                        @endphp
                                        <div class="absolute top-1 bottom-1 rounded-md {{ $bgColor }} {{ $borderClass }} flex items-center justify-center text-white text-xs font-medium shadow-md hover:scale-105 hover:z-10 transition-transform cursor-pointer"
                                             style="left: {{ $leftPercent }}%; width: {{ max($widthPercent, 2) }}%;"
                                             title="{{ $emp->lastName }} {{ $emp->name }}
{{ $vStart->format('d/m') }} - {{ $vEnd->format('d/m') }}
{{ $vac->working_days }} días - {{ ucfirst($vac->status) }}{{ $hasConflict ? '
⚠️ SUPERPOSICIÓN' : '' }}">
                                            @if($widthPercent > 8)
                                                <span class="truncate px-1">{{ $vStart->format('d') }}-{{ $vEnd->format('d') }}</span>
                                            @endif
                                            @if($hasConflict)
                                                <span class="absolute -top-1 -right-1 w-4 h-4 bg-white text-red-600 rounded-full flex items-center justify-center text-[10px] font-bold shadow">!</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                    
                    @if($vacations->count() == 0)
                        <p class="text-center text-gray-500 py-8">No hay vacaciones registradas</p>
                    @endif
                </div>
            </div>

            {{-- Detalle de Superposiciones --}}
            @if($conflictDays->count() > 0)
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Superposiciones (misma categoría o puesto)
                        </h2>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-4">Solo se muestran conflictos entre empleados con la misma categoría o puesto.</p>
                        <div class="space-y-3">
                            @foreach($conflictDays->sortKeys()->take(15) as $day => $entries)
                                @php
                                    $dayDate = \Carbon\Carbon::parse($day);
                                    // Obtener nombres y sus categorías/puestos
                                    $employeeInfo = collect($entries)->map(function($e) {
                                        $jobs = $e['vacation']->employee->jobs ?? collect();
                                        $category = $jobs->first()?->category?->name ?? null;
                                        $jobName = $jobs->first()?->name ?? null;
                                        return [
                                            'name' => $e['vacation']->employee->lastName . ' ' . $e['vacation']->employee->name,
                                            'category' => $category,
                                            'job' => $jobName,
                                        ];
                                    });
                                @endphp
                                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-semibold text-red-800">{{ $dayDate->format('d/m/Y') }}</span>
                                        <span class="px-2 py-1 bg-red-600 text-white rounded text-xs font-bold">
                                            {{ $employeeInfo->count() }} empleados
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($employeeInfo as $info)
                                            <div class="px-2 py-1 bg-white border border-red-200 rounded text-sm">
                                                <span class="font-medium text-gray-900">{{ $info['name'] }}</span>
                                                @if($info['category'] || $info['job'])
                                                    <span class="text-xs text-red-600 ml-1">
                                                        ({{ $info['job'] ?? $info['category'] }})
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($conflictDays->count() > 15)
                            <p class="text-center text-gray-500 mt-4 text-sm">Y {{ $conflictDays->count() - 15 }} días más...</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Listado por empleado --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white">Todas las Vacaciones {{ $year }}</h2>
                </div>
                <div class="p-6">
                    @if($vacations->count())
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($vacations->sortBy('start') as $vac)
                                @php $hasConflict = isset($vacationsWithConflict[$vac->id]); @endphp
                                <div class="p-4 rounded-xl border-2 transition-all hover:shadow-md
                                    {{ $hasConflict 
                                        ? 'border-red-400 bg-red-50' 
                                        : ($vac->status === 'aprobado' ? 'border-green-300 bg-green-50' : 'border-amber-300 bg-amber-50') 
                                    }}">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="font-medium text-gray-900 flex items-center gap-2">
                                                {{ $vac->employee->lastName }}, {{ $vac->employee->name }}
                                                @if($hasConflict)
                                                    <span class="px-1.5 py-0.5 bg-red-600 text-white rounded text-xs">⚠️</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                {{ \Carbon\Carbon::parse($vac->start)->format('d/m/Y') }} → 
                                                {{ \Carbon\Carbon::parse($vac->end)->format('d/m/Y') }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="px-2 py-1 rounded text-xs font-bold
                                                {{ $vac->status === 'aprobado' ? 'bg-green-500 text-white' : 'bg-amber-400 text-white' }}">
                                                {{ $vac->working_days }} días
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 py-8">No hay vacaciones registradas para {{ $year }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-manage>
