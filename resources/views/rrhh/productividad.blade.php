<x-admin-layout title="Productividad diaria">
    <div class="p-6 space-y-6">
        <a href="{{ route('rrhh.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            ← Volver a Recursos Humanos
        </a>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Productividad diaria</h1>
                <p class="text-gray-500 text-sm mt-1">Métricas por empleado según puesto — laboratorio (clínico, vet, aguas)</p>
            </div>
            @can('rrhh.productivity.view')
                <a href="{{ route('rrhh.productividad.export', request()->query()) }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Exportar CSV
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('rrhh.productividad') }}" class="bg-white rounded-xl shadow-sm border p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Fecha</label>
                    <input type="date" name="date" value="{{ $date }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sede</label>
                    <select name="branch_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Todas</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branchId === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Puesto</label>
                    <select name="job_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Todos</option>
                        @foreach ($jobs as $job)
                            <option value="{{ $job->id }}" @selected($jobId === $job->id)>{{ $job->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Empleado</label>
                    <select name="employee_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Todos</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" @selected($employeeId === $emp->id)>{{ $emp->name }} {{ $emp->lastName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        Aplicar
                    </button>
                    <a href="{{ route('rrhh.productividad') }}" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">
                        Hoy
                    </a>
                </div>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <p class="text-sm text-gray-500">Protocolos del día</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $report['branch_summary']['protocols_created'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <p class="text-sm text-gray-500">Pacientes nuevos</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $report['branch_summary']['patients_created'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <p class="text-sm text-gray-500 flex items-center gap-1">
                    Resultados entregados
                    <i class="bi bi-info-circle text-gray-400" title="Protocolos distintos entregados (email o descarga PDF), por quien envió, cualquier rol."></i>
                </p>
                <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $report['branch_summary']['results_delivered'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <p class="text-sm text-gray-500">Protocolos validados</p>
                <p class="text-2xl font-bold text-purple-600 mt-1">{{ $report['branch_summary']['protocols_validated'] }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            @if (count($report['rows']) === 0)
                <div class="p-12 text-center text-gray-500">
                    No hay actividad registrada para esta fecha y filtros.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puesto</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-teal-600 uppercase">Creados</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-teal-600 uppercase" title="Email o PDF; cualquier rol">
                                    Entregados <i class="bi bi-info-circle"></i>
                                </th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-teal-600 uppercase">% ent.</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase">Det. carg.</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase">% carga</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-purple-600 uppercase">Val. práct.</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-purple-600 uppercase">Val. prot.</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-purple-600 uppercase">% val.</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-rose-600 uppercase">Extracciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($report['rows'] as $row)
                                @php
                                    $reception = $row['metrics']['reception'] ?? null;
                                    $delivery = $row['metrics']['delivery'] ?? null;
                                    $loading = $row['metrics']['loading'] ?? null;
                                    $technician = $row['metrics']['technician'] ?? null;
                                    $biochemist = $row['metrics']['biochemist'] ?? null;
                                @endphp
                                <tr class="hover:bg-indigo-50 cursor-pointer transition-colors"
                                    role="link"
                                    tabindex="0"
                                    onclick="window.location='{{ route('rrhh.productividad.empleado', $row['employee_id']) }}'"
                                    onkeydown="if (event.key === 'Enter') window.location='{{ route('rrhh.productividad.empleado', $row['employee_id']) }}'">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        <span class="text-indigo-700 hover:text-indigo-900">{{ $row['employee_name'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-gray-600">{{ $row['job_name'] }}</td>
                                    <td class="px-3 py-3 text-gray-600">{{ $row['inferred_branch_name'] }}</td>
                                    <td class="px-3 py-3">
                                        @foreach ($row['roles'] as $role)
                                            @php
                                                $badge = match ($role) {
                                                    'recepcion-lab' => 'bg-teal-100 text-teal-800',
                                                    'tecnico-lab' => 'bg-blue-100 text-blue-800',
                                                    'bioquimico', 'director-tecnico' => 'bg-purple-100 text-purple-800',
                                                    default => 'bg-gray-100 text-gray-800',
                                                };
                                                $label = match ($role) {
                                                    'recepcion-lab' => 'Recepción',
                                                    'tecnico-lab' => 'Técnico',
                                                    'bioquimico' => 'Bioquímico',
                                                    'director-tecnico' => 'Director técnico',
                                                    default => $role,
                                                };
                                            @endphp
                                            <span class="inline-block rounded-full px-2 py-0.5 text-xs {{ $badge }} mr-1">{{ $label }}</span>
                                        @endforeach
                                    </td>
                                    <td class="px-3 py-3 text-right">{{ $reception['protocols_created'] ?? '—' }}</td>
                                    <td class="px-3 py-3 text-right font-medium">{{ $delivery['results_delivered'] ?? 0 }}</td>
                                    <td class="px-3 py-3 text-right">{{ isset($delivery['delivery_rate']) ? number_format($delivery['delivery_rate'], 1, ',', '.').'%' : '0,0%' }}</td>
                                    <td class="px-3 py-3 text-right">{{ $loading['results_entered'] ?? 0 }}</td>
                                    <td class="px-3 py-3 text-right">{{ isset($loading['load_rate']) ? number_format($loading['load_rate'], 1, ',', '.').'%' : '0,0%' }}</td>
                                    <td class="px-3 py-3 text-right">{{ $biochemist['tests_validated'] ?? '—' }}</td>
                                    <td class="px-3 py-3 text-right">{{ $biochemist['protocols_validated'] ?? '—' }}</td>
                                    <td class="px-3 py-3 text-right">{{ isset($biochemist['validation_rate']) ? number_format($biochemist['validation_rate'], 1, ',', '.').'%' : '—' }}</td>
                                    <td class="px-3 py-3 text-right font-medium text-rose-700">
                                        {{ $technician['samples_drawn'] ?? $biochemist['samples_drawn'] ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
