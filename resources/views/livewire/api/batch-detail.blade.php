<div class="p-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Detalle de batch</h1>
            <p class="text-sm text-gray-500 mt-1">
                <a href="{{ route('admin.api-monitor.batches') }}" class="text-blue-600 hover:underline">← Volver a batches</a>
            </p>
        </div>
    </div>

    {{-- Info del batch --}}
    <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6">
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wide">Sede</dt>
                <dd class="font-medium mt-1">{{ $batch->apiClient?->labBranch?->name ?? '-' }}</dd>
                <dd class="text-xs text-gray-500">{{ $batch->apiClient?->name }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wide">Batch ID externo</dt>
                <dd class="font-mono text-xs mt-1 break-all">{{ $batch->external_batch_id }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wide">Recibido</dt>
                <dd class="mt-1">{{ $batch->created_at->format('d/m/Y H:i:s') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wide">Origen</dt>
                <dd class="mt-1">{{ $batch->source_app }}</dd>
            </div>
        </dl>

        {{-- Contadores --}}
        <div class="grid grid-cols-4 md:grid-cols-5 gap-3 mt-5 pt-4 border-t border-gray-100">
            <x-api-monitor.counter-card label="Total" :value="$batch->items_total" color="gray" />
            <x-api-monitor.counter-card label="Ingestados" :value="$batch->items_ingested" color="green" />
            <x-api-monitor.counter-card label="Sobrescritos" :value="$batch->items_overwritten" color="orange" />
            <x-api-monitor.counter-card label="Rechazados" :value="$batch->items_rejected" color="red" :highlight="$batch->items_rejected > 0" />
            <x-api-monitor.counter-card label="Duplicados" :value="$batch->items_duplicate" color="gray" />
        </div>
    </div>

    {{-- Lista de mensajes del batch --}}
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-gray-200 font-semibold text-gray-800">
            Mensajes procesados ({{ $batch->ingestions->count() }})
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">HL7 Control ID</th>
                        <th class="px-4 py-2 text-left">Protocolo</th>
                        <th class="px-4 py-2 text-left">Equipo</th>
                        <th class="px-4 py-2 text-left">Estado</th>
                        <th class="px-4 py-2 text-left">Razón rechazo</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batch->ingestions as $ingestion)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $ingestion->hl7_control_id }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $ingestion->protocol_number }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ $ingestion->equipment_name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <x-api-monitor.status-badge :status="$ingestion->status" />
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ $ingestion->rejection_reason ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.api-monitor.ingestions.show', $ingestion) }}"
                                   class="text-blue-600 hover:underline text-xs">
                                    Ver →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                Sin mensajes registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Payload raw — solo admins --}}
    @can('api-clients.manage')
        @if ($batch->raw_request)
            <div x-data="{ open: false }" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <button @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <span>Payload raw del request</span>
                    <span x-text="open ? '▲' : '▼'" class="text-xs text-gray-400"></span>
                </button>
                <div x-show="open" class="border-t border-gray-200 p-4">
                    @if (isset($batch->raw_request['_truncated']))
                        <div class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2 mb-3">
                            ⚠ Payload truncado — solo se almacenaron los primeros datos. El payload original excedía 64 KB.
                        </div>
                    @endif
                    <pre class="text-xs bg-gray-50 rounded p-3 overflow-x-auto max-h-96">{{ json_encode($batch->raw_request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif
    @endcan
</div>
