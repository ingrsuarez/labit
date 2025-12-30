<x-admin-layout title="Resumen de Licencias">
    <div class="max-w-7xl mx-auto p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Resumen de licencias</h1>

        {{-- Filtros --}}
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 bg-white p-4 rounded-xl shadow mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Año</label>
                <input type="number" name="year" value="{{ $filters['year'] ?? '' }}" min="2000" max="2100"
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Mes</label>
                <select name="month"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Todos —</option>
                    @for($m=1; $m<=12; $m++)
                        <option value="{{ $m }}" @selected(($filters['month'] ?? '') == $m)>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>
            <div class="">
                <label class="block text-sm font-medium text-gray-700">Empleado</label>
                <select name="employee_id"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Todos —</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(($filters['employee_id'] ?? '') == $e->id)>
                            {{ ucfirst($e->lastName) }}, {{ ucfirst($e->name) }} — #{{ $e->employeeId }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('leave.resume') }}"
                class="px-4 py-2 my-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Limpiar</a>
                <button class="px-4 my-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Filtrar</button>
                
            {{-- Botón que abre la vista de 4 meses centrada en este mes --}}
                @php
                    $year = request('year') ?? now()->year;
                    $month = request('month') ?? now()->month;
                    $ym = sprintf('%04d-%02d', $year, $month);
                @endphp
                <a href="{{ route('leave.resume.compact', array_merge(request()->only(['employee_id','year','month']), ['anchor' => $ym])) }}"
                class="px-4 py-2 my-3 border border-green-500 rounded-lg bg-green-200 text-gray-800 hover:bg-green-300">
                    Últimos 
                </a>
            </div>
        </form>

        {{-- Tabla agrupada por Año-Mes y luego por Empleado --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            @php
                // Agrupar por período
                $grouped = $resumes->groupBy(fn($r) => sprintf('%04d-%02d', $r->year, $r->month));
            @endphp

            @forelse($grouped as $ym => $rows)
                @php
                    // Agrupar por empleado dentro del período
                    $byEmployee = $rows->groupBy('employee_id');
                @endphp

                <div class="bg-gray-50 px-4 py-2 border-t border-gray-200 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-600">Periodo</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $ym }}</div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CUIL</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Horas Sem.</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Vacaciones</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Enfermedad</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Horas 50%</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Horas 100%</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Certificados</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($byEmployee as $employeeId => $employeeRows)
                                @php
                                    $first = $employeeRows->first();
                                    
                                    // Sumar valores por tipo
                                    $vacaciones = 0;
                                    $enfermedad = 0;
                                    $horas50 = 0;
                                    $horas100 = 0;
                                    $allFiles = collect();
                                    
                                    foreach ($employeeRows as $r) {
                                        $type = strtolower($r->type ?? '');
                                        
                                        if ($type === 'vacaciones') {
                                            $vacaciones += (int)($r->total_dias ?? 0);
                                        } elseif ($type === 'enfermedad') {
                                            $enfermedad += (int)($r->total_dias ?? 0);
                                        }
                                        
                                        $horas50 += (int)($r->horas_50 ?? 0);
                                        $horas100 += (int)($r->horas_100 ?? 0);
                                        
                                        // Recolectar archivos
                                        if (!empty($r->files)) {
                                            $files = collect(explode('|', (string)$r->files))->filter(fn($f) => !empty($f));
                                            $allFiles = $allFiles->merge($files);
                                        }
                                    }
                                    
                                    $allFiles = $allFiles->unique();
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $first->employee }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $first->cuil ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ (int)($first->weekly_hours ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">
                                        @if($vacaciones > 0)
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">{{ $vacaciones }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700">
                                        @if($enfermedad > 0)
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded">{{ $enfermedad }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700">
                                        @if($horas50 > 0)
                                            <span class="px-2 py-1 bg-amber-100 text-amber-800 rounded">{{ $horas50 }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700">
                                        @if($horas100 > 0)
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded">{{ $horas100 }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($allFiles->isEmpty())
                                            <span class="text-gray-400 text-sm">—</span>
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($allFiles as $idx => $file)
                                                    <a href="{{ asset('storage/'.$file) }}" target="_blank"
                                                    class="inline-flex items-center px-2 py-1 rounded-md bg-blue-50 text-blue-700 text-xs hover:bg-blue-100">
                                                        Cert. {{ $loop->iteration }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="p-6 text-center text-gray-500">No hay novedades para el criterio seleccionado.</div>
            @endforelse
        </div>
    </div>
</x-admin-layout>
