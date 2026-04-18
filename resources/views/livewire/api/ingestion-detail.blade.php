<div class="p-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Detalle de mensaje</h1>
            <p class="text-sm text-gray-500 mt-1">
                <a href="{{ route('admin.api-monitor.ingestions') }}" class="text-blue-600 hover:underline">← Volver a mensajes</a>
                &nbsp;·&nbsp;
                <a href="{{ route('admin.api-monitor.batches.show', $ingestion->batch) }}" class="text-blue-600 hover:underline">
                    Batch {{ Str::limit($ingestion->batch?->external_batch_id, 16) }}
                </a>
            </p>
        </div>
    </div>

    {{-- Info del mensaje --}}
    <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6">
        <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wide">Protocolo</dt>
                <dd class="font-mono font-medium mt-1">{{ $ingestion->protocol_number }}</dd>
                @if ($protocolUrl)
                    <dd class="mt-1">
                        <a href="{{ $protocolUrl }}" class="text-xs text-blue-600 hover:underline" target="_blank">
                            Ver en labit →
                        </a>
                    </dd>
                @endif
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wide">Estado</dt>
                <dd class="mt-1"><x-api-monitor.status-badge :status="$ingestion->status" /></dd>
                @if ($ingestion->rejection_reason)
                    <dd class="text-xs mt-1 {{ $ingestion->rejection_reason === 'ALREADY_VALIDATED' ? 'text-violet-700 font-medium' : 'text-red-600' }}">
                        {{ $ingestion->rejection_reason }}
                    </dd>
                @endif
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wide">Equipo</dt>
                <dd class="mt-1">{{ $ingestion->equipment_name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wide">HL7 Control ID</dt>
                <dd class="font-mono text-xs mt-1">{{ $ingestion->hl7_control_id }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wide">Tipo de protocolo</dt>
                <dd class="mt-1">{{ $ingestion->protocol_type ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wide">Recibido</dt>
                <dd class="mt-1">{{ $ingestion->created_at->format('d/m/Y H:i:s') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Alerta ALREADY_VALIDATED --}}
    @if ($ingestion->rejection_reason === 'ALREADY_VALIDATED')
        <div class="bg-violet-50 border border-violet-200 rounded-lg p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-violet-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-violet-900">Este mensaje fue rechazado porque algunos resultados ya estaban validados</h3>
                    <p class="text-sm text-violet-700 mt-1">
                        Ver la tabla de ítems para identificar cuáles estaban validados, quién los validó y cuándo.
                        Si necesitás actualizar el resultado con el valor del equipo, primero invalidá la determinación correspondiente en labit.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabla de ítems (items_summary) --}}
    @php $items = $ingestion->items_summary ?? [] @endphp
    @if (count($items))
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
            <div class="px-4 py-3 border-b border-gray-200 font-semibold text-gray-800">
                Ítems procesados ({{ count($items) }})
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-2 text-left">OBX</th>
                            <th class="px-4 py-2 text-left">Test ID</th>
                            <th class="px-4 py-2 text-left">Estado</th>
                            <th class="px-4 py-2 text-left">Determinación</th>
                            <th class="px-4 py-2 text-left">Razón / Info</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr class="border-t border-gray-100 {{ ($item['status'] ?? '') === 'rejected' ? 'bg-red-50' : '' }}">
                                <td class="px-4 py-3 font-mono text-xs">{{ $item['obx_index'] ?? '-' }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $item['labit_test_id'] ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <x-api-monitor.status-badge :status="$item['status'] ?? 'unknown'" />
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    {{ $item['determination_id'] ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if (($item['reason'] ?? null) === 'ALREADY_VALIDATED')
                                        <span class="text-violet-700 font-medium">Ya validado</span>
                                        @if (!empty($item['validated_by_name']))
                                            <span class="text-gray-500"> por {{ $item['validated_by_name'] }}</span>
                                        @endif
                                        @if (!empty($item['validated_at']))
                                            <span class="text-gray-400"> ({{ \Carbon\Carbon::parse($item['validated_at'])->format('d/m/Y H:i') }})</span>
                                        @endif
                                    @elseif (!empty($item['reason']))
                                        <span class="text-red-600">{{ $item['reason'] }}</span>
                                    @elseif (!empty($item['previous_value']))
                                        <span class="text-yellow-700">Sobrescribió: {{ $item['previous_value'] }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Info técnica — solo admins --}}
    @can('api-clients.manage')
        <div x-data="{ open: false }" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <span>Info técnica (admin)</span>
                <span x-text="open ? '▲' : '▼'" class="text-xs text-gray-400"></span>
            </button>
            <div x-show="open" class="border-t border-gray-200 p-4 space-y-2 text-sm">
                <div>
                    <span class="text-gray-500">External message ID:</span>
                    <span class="font-mono text-xs ml-2">{{ $ingestion->external_message_id ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Sede:</span>
                    <span class="ml-2">{{ $ingestion->batch?->apiClient?->labBranch?->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Timestamp exacto:</span>
                    <span class="ml-2">{{ $ingestion->created_at->toIso8601String() }}</span>
                </div>
            </div>
        </div>
    @endcan
</div>
