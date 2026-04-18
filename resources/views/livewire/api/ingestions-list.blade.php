<div class="p-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Mensajes individuales</h1>
            <p class="text-sm text-gray-500 mt-1">
                <a href="{{ route('admin.api-monitor.dashboard') }}" class="text-blue-600 hover:underline">← Dashboard</a>
            </p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                <select wire:model.live="status" class="w-full text-sm border border-gray-300 rounded-md px-2 py-1.5">
                    <option value="">Todos</option>
                    <option value="ingested">Ingestado</option>
                    <option value="partial">Parcial</option>
                    <option value="rejected">Rechazado</option>
                    <option value="duplicate">Duplicado</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Razón rechazo</label>
                <select wire:model.live="rejectionReason" class="w-full text-sm border border-gray-300 rounded-md px-2 py-1.5">
                    <option value="">Todas</option>
                    <option value="ALREADY_VALIDATED">Ya validado</option>
                    <option value="PROTOCOL_NOT_FOUND">Protocolo no encontrado</option>
                    <option value="DETERMINATION_NOT_FOUND">Determinación no encontrada</option>
                    <option value="PROTOCOL_OUT_OF_BRANCH">Sede incorrecta</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nro. protocolo</label>
                <input wire:model.live.debounce.400ms="protocolNumber" type="text" placeholder="C2604..." class="w-full text-sm border border-gray-300 rounded-md px-2 py-1.5">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sede</label>
                <select wire:model.live="clientId" class="w-full text-sm border border-gray-300 rounded-md px-2 py-1.5">
                    <option value="">Todos</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                <input wire:model.live="dateFrom" type="date" class="w-full text-sm border border-gray-300 rounded-md px-2 py-1.5">
            </div>
            <div class="flex items-end">
                <button wire:click="clearFilters" class="text-xs text-gray-500 hover:text-gray-700 underline mb-1">
                    Limpiar filtros
                </button>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Protocolo</th>
                        <th class="px-4 py-2 text-left">HL7 Control ID</th>
                        <th class="px-4 py-2 text-left">Equipo</th>
                        <th class="px-4 py-2 text-left">Estado</th>
                        <th class="px-4 py-2 text-left">Razón rechazo</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ingestions as $ingestion)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ $ingestion->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $ingestion->protocol_number }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $ingestion->hl7_control_id }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ $ingestion->equipment_name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <x-api-monitor.status-badge :status="$ingestion->status" />
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                @if ($ingestion->rejection_reason)
                                    <span class="{{ $ingestion->rejection_reason === 'ALREADY_VALIDATED' ? 'text-violet-700 font-medium' : '' }}">
                                        {{ $ingestion->rejection_reason }}
                                    </span>
                                @else
                                    -
                                @endif
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
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                No hay mensajes para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($ingestions->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $ingestions->links() }}
            </div>
        @endif
    </div>
</div>
