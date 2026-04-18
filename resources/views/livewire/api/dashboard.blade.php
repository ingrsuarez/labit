<div class="p-6">
    {{-- Header con selector de periodo --}}
    <div class="flex flex-wrap items-center justify-between mb-6 gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">Monitoreo de ingesta API</h1>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Período:</label>
            <select wire:model.live="periodDays" class="text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="1">Últimas 24 hs</option>
                <option value="7">Últimos 7 días</option>
                <option value="30">Últimos 30 días</option>
            </select>

            @can('api-clients.manage')
                    <a href="{{ route('api-clients.index') }}"
                   class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Gestionar API keys
                </a>
            @endcan
        </div>
    </div>

    @php $c = $this->counters @endphp

    {{-- Counter cards con auto-refresh --}}
    <div wire:poll.10s="$refresh" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-api-monitor.counter-card
            label="Batches recibidos"
            :value="$c['batches_total']"
            color="blue" />

        <x-api-monitor.counter-card
            label="Mensajes ingestados"
            :value="$c['messages_ingested']"
            :sublabel="'ítems: ' . ($c['items_ingested'] + $c['items_overwritten'])"
            color="green" />

        <x-api-monitor.counter-card
            label="Mensajes parciales"
            :value="$c['messages_partial']"
            color="orange" />

        <x-api-monitor.counter-card
            label="Mensajes rechazados"
            :value="$c['messages_rejected']"
            :sublabel="$c['rejected_already_validated'] . ' por validación previa'"
            color="red"
            :highlight="$c['messages_rejected'] > 0" />
    </div>

    {{-- Alerta ALREADY_VALIDATED — importante para bioquímicos --}}
    @if ($c['rejected_already_validated'] > 0)
        <div class="bg-violet-50 border border-violet-200 rounded-lg p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-violet-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1">
                    <h3 class="font-semibold text-violet-900">
                        {{ $c['rejected_already_validated'] }} resultado(s) rechazado(s) porque ya estaban validados
                    </h3>
                    <p class="text-sm text-violet-700 mt-1">
                        LISCOM intentó pisar valores que ya fueron validados por un bioquímico.
                        Estos resultados no se cargaron automáticamente.
                        Si necesitás el valor del equipo, primero invalidá el resultado y pedile al técnico que reenvíe desde LISCOM.
                    </p>
                    <a href="{{ route('admin.api-monitor.ingestions', ['razon' => 'ALREADY_VALIDATED']) }}"
                       class="text-sm font-medium text-violet-800 underline mt-2 inline-block">
                        Ver mensajes rechazados →
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Estado de las sedes --}}
    <div class="bg-white border border-gray-200 rounded-lg mb-6 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 font-semibold text-gray-800">
            Estado de las sedes (LISCOM)
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Sede / Cliente</th>
                        <th class="px-4 py-2 text-left">Estado</th>
                        <th class="px-4 py-2 text-left">Última actividad</th>
                        <th class="px-4 py-2 text-right">Batches (7 días)</th>
                        <th class="px-4 py-2 text-right">Total requests</th>
                        @can('api-clients.manage')
                            <th class="px-4 py-2 text-left">Acciones</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->clientsStatus as $row)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $row['lab_branch_name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $row['client']->name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <x-api-monitor.health-badge :health="$row['health']" />
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if ($row['client']->last_used_at)
                                    {{ $row['client']->last_used_at->diffForHumans() }}
                                @else
                                    <span class="text-gray-400">nunca</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                {{ number_format($row['batches_recent']) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                {{ number_format($row['client']->requests_count) }}
                            </td>
                            @can('api-clients.manage')
                                <td class="px-4 py-3">
                                        <a href="{{ route('api-clients.show', $row['client']) }}"
                                       class="text-blue-600 hover:underline text-xs">
                                        Detalle key
                                    </a>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No hay clientes API configurados todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Acceso rápido a listados --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('admin.api-monitor.batches') }}"
           class="block bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
            <div class="font-semibold text-gray-800">Ver batches recibidos →</div>
            <div class="text-sm text-gray-500 mt-1">
                Listado completo de POST batch desde LISCOM con filtros por fecha, sede y rechazos.
            </div>
        </a>
        <a href="{{ route('admin.api-monitor.ingestions') }}"
           class="block bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
            <div class="font-semibold text-gray-800">Ver mensajes individuales →</div>
            <div class="text-sm text-gray-500 mt-1">
                Drill-down por mensaje HL7 con estado, ítems procesados y razón de rechazo.
            </div>
        </a>
    </div>
</div>
