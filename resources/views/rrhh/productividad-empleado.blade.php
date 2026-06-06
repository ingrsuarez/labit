@php
    $groups = $report['applicable_groups'];
    $showReception = in_array('reception', $groups, true);
    $showDelivery = in_array('delivery', $groups, true);
    $showLoading = in_array('loading', $groups, true);
    $showBiochemist = in_array('biochemist', $groups, true);
    $showTechnician = in_array('technician', $groups, true);

    $formatPct = fn (?float $value) => isset($value) ? number_format($value, 1, ',', '.').'%' : '—';
    $formatInt = fn ($value) => $value ?? '—';
@endphp

<x-admin-layout title="Productividad — {{ $report['employee_name'] }}">
    <div class="p-6 space-y-6">
        <div class="flex flex-col gap-2">
            <a href="{{ route('rrhh.productividad', request()->only(['date', 'branch_id', 'job_id'])) }}"
               class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                ← Volver a Productividad diaria
            </a>
            <a href="{{ route('rrhh.index') }}" class="inline-flex items-center text-sm text-gray-400 hover:text-gray-600">
                Recursos Humanos
            </a>
        </div>

        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $report['employee_name'] }}</h1>
                <p class="text-gray-500 text-sm mt-1">
                    {{ $report['job_name'] }}
                    @if ($report['inferred_branch_name'] !== '—')
                        · {{ $report['inferred_branch_name'] }}
                    @endif
                </p>
                <div class="mt-2 flex flex-wrap gap-1">
                    @foreach ($report['roles'] as $role)
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
                        <span class="inline-block rounded-full px-2.5 py-0.5 text-xs {{ $badge }}">{{ $label }}</span>
                    @endforeach
                </div>
            </div>
            <p class="text-sm text-gray-500 md:text-right">
                Período: {{ \Carbon\Carbon::parse($dateFrom)->locale('es')->isoFormat('D MMM YYYY') }}
                — {{ \Carbon\Carbon::parse($dateTo)->locale('es')->isoFormat('D MMM YYYY') }}
            </p>
        </div>

        <form method="GET" action="{{ route('rrhh.productividad.empleado', $employee) }}" class="bg-white rounded-xl shadow-sm border p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
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
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        Aplicar
                    </button>
                    <a href="{{ route('rrhh.productividad.empleado', $employee) }}"
                       class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">
                        Últimos 30 días
                    </a>
                </div>
            </div>
        </form>

        @php
            $summary = $report['period_summary'];
            $rrhh = $summary['rrhh'] ?? [];
            $overtimeTotal = ($rrhh['hours_50'] ?? 0) + ($rrhh['hours_100'] ?? 0);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <p class="text-sm text-gray-500">Protocolos del período</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['protocols_created'] }}</p>
            </div>

            @if ($showReception)
                <div class="bg-white rounded-xl shadow-sm border p-5">
                    <p class="text-sm text-teal-600">Protocolos creados</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $formatInt($summary['metrics']['reception']['protocols_created'] ?? 0) }}</p>
                </div>
            @endif

            @if ($showDelivery)
                <div class="bg-white rounded-xl shadow-sm border p-5">
                    <p class="text-sm text-teal-600 flex items-center gap-1">
                        Resultados entregados
                        <i class="bi bi-info-circle text-gray-400" title="Protocolos distintos entregados (email o PDF), por quien envió."></i>
                    </p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $summary['metrics']['delivery']['results_delivered'] ?? 0 }}</p>
                </div>
            @endif

            @if ($showLoading)
                <div class="bg-white rounded-xl shadow-sm border p-5">
                    <p class="text-sm text-blue-600">Determinaciones cargadas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['metrics']['loading']['results_entered'] ?? 0 }}</p>
                </div>
            @endif

            @if ($showBiochemist)
                <div class="bg-white rounded-xl shadow-sm border p-5">
                    <p class="text-sm text-purple-600">Protocolos validados</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1">{{ $summary['metrics']['biochemist']['protocols_validated'] ?? 0 }}</p>
                </div>
            @endif

            @if ($showTechnician)
                <div class="bg-white rounded-xl shadow-sm border p-5">
                    <p class="text-sm text-rose-600">Extracciones</p>
                    <p class="text-2xl font-bold text-rose-700 mt-1">{{ $summary['metrics']['technician']['samples_drawn'] ?? 0 }}</p>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <p class="text-sm text-amber-600">Vacaciones tomadas</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $rrhh['vacation_days'] ?? 0 }} <span class="text-sm font-normal text-gray-500">días</span></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <p class="text-sm text-orange-600">Licencias</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $rrhh['license_days'] ?? 0 }} <span class="text-sm font-normal text-gray-500">días</span></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <p class="text-sm text-red-600">No conformidades</p>
                <p class="text-2xl font-bold text-red-700 mt-1">{{ $rrhh['non_conformities'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <p class="text-sm text-slate-600">Horas extras</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $overtimeTotal }}</p>
                <p class="text-xs text-gray-400 mt-1">
                    50%: {{ $rrhh['hours_50'] ?? 0 }} · 100%: {{ $rrhh['hours_100'] ?? 0 }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-700">Desglose mensual</h2>
            </div>

            @if (count($report['monthly_rows']) === 0)
                <div class="p-12 text-center text-gray-500">
                    No hay actividad registrada en el período seleccionado.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mes</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prot. sede</th>
                                @if ($showReception)
                                    <th class="px-3 py-3 text-right text-xs font-medium text-teal-600 uppercase">Creados</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-teal-600 uppercase">Pac. nuevos</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-teal-600 uppercase">Editados</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-teal-600 uppercase">Cobros</th>
                                @endif
                                @if ($showDelivery)
                                    <th class="px-3 py-3 text-right text-xs font-medium text-teal-600 uppercase">Entregados</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-teal-600 uppercase">% ent.</th>
                                @endif
                                @if ($showLoading)
                                    <th class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase">Det. carg.</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase">Prot. carga</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase">% carga</th>
                                @endif
                                @if ($showBiochemist)
                                    <th class="px-3 py-3 text-right text-xs font-medium text-purple-600 uppercase">Val. práct.</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-purple-600 uppercase">Val. prot.</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-purple-600 uppercase">% val.</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-purple-600 uppercase">Desvalid.</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-purple-600 uppercase">Emails</th>
                                @endif
                                @if ($showTechnician)
                                    <th class="px-3 py-3 text-right text-xs font-medium text-rose-600 uppercase">Extracciones</th>
                                @endif
                                <th class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase">Vacaciones</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-orange-600 uppercase">Licencias</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-red-600 uppercase">No conf.</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-slate-600 uppercase">Hs 50%</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-slate-600 uppercase">Hs 100%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($report['monthly_rows'] as $monthRow)
                                @php
                                    $reception = $monthRow['metrics']['reception'] ?? null;
                                    $delivery = $monthRow['metrics']['delivery'] ?? null;
                                    $loading = $monthRow['metrics']['loading'] ?? null;
                                    $biochemist = $monthRow['metrics']['biochemist'] ?? null;
                                    $technician = $monthRow['metrics']['technician'] ?? null;
                                    $monthRrhh = $monthRow['rrhh'] ?? [];
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900 capitalize">{{ $monthRow['month_label'] }}</td>
                                    <td class="px-3 py-3 text-right text-gray-600">{{ $monthRow['protocols_created'] }}</td>
                                    @if ($showReception)
                                        <td class="px-3 py-3 text-right">{{ $formatInt($reception['protocols_created'] ?? 0) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $formatInt($reception['patients_created'] ?? 0) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $formatInt($reception['protocols_updated'] ?? 0) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $formatInt($reception['payments_recorded'] ?? 0) }}</td>
                                    @endif
                                    @if ($showDelivery)
                                        <td class="px-3 py-3 text-right font-medium">{{ $delivery['results_delivered'] ?? 0 }}</td>
                                        <td class="px-3 py-3 text-right">{{ $formatPct($delivery['delivery_rate'] ?? 0) }}</td>
                                    @endif
                                    @if ($showLoading)
                                        <td class="px-3 py-3 text-right">{{ $loading['results_entered'] ?? 0 }}</td>
                                        <td class="px-3 py-3 text-right">{{ $loading['protocols_with_results'] ?? 0 }}</td>
                                        <td class="px-3 py-3 text-right">{{ $formatPct($loading['load_rate'] ?? 0) }}</td>
                                    @endif
                                    @if ($showBiochemist)
                                        <td class="px-3 py-3 text-right">{{ $formatInt($biochemist['tests_validated'] ?? 0) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $formatInt($biochemist['protocols_validated'] ?? 0) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $formatPct($biochemist['validation_rate'] ?? null) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $formatInt($biochemist['unvalidations'] ?? 0) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $formatInt($biochemist['emails_sent'] ?? 0) }}</td>
                                    @endif
                                    @if ($showTechnician)
                                        <td class="px-3 py-3 text-right font-medium text-rose-700">{{ $technician['samples_drawn'] ?? 0 }}</td>
                                    @endif
                                    <td class="px-3 py-3 text-right">{{ $monthRrhh['vacation_days'] ?? 0 }}</td>
                                    <td class="px-3 py-3 text-right">{{ $monthRrhh['license_days'] ?? 0 }}</td>
                                    <td class="px-3 py-3 text-right font-medium text-red-700">{{ $monthRrhh['non_conformities'] ?? 0 }}</td>
                                    <td class="px-3 py-3 text-right">{{ $monthRrhh['hours_50'] ?? 0 }}</td>
                                    <td class="px-3 py-3 text-right">{{ $monthRrhh['hours_100'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
