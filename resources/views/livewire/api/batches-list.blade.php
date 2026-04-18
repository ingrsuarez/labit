<div class="p-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Batches recibidos</h1>
            <p class="text-sm text-gray-500 mt-1">
                <a href="{{ route('admin.api-monitor.dashboard') }}" class="text-blue-600 hover:underline">← Volver al dashboard</a>
            </p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sede / Cliente</label>
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
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                <input wire:model.live="dateTo" type="date" class="w-full text-sm border border-gray-300 rounded-md px-2 py-1.5">
            </div>
            <div class="flex items-end gap-2">
                <label class="flex items-center gap-2 text-sm cursor-pointer mb-1">
                    <input wire:model.live="hasRejections" type="checkbox" class="rounded">
                    Solo con rechazos
                </label>
                <button wire:click="clearFilters" class="text-xs text-gray-500 hover:text-gray-700 underline mb-1">
                    Limpiar
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
                        <th class="px-4 py-2 text-left">Sede</th>
                        <th class="px-4 py-2 text-left">Batch ID externo</th>
                        <th class="px-4 py-2 text-right">Total</th>
                        <th class="px-4 py-2 text-right">Ingestados</th>
                        <th class="px-4 py-2 text-right">Sobrescritos</th>
                        <th class="px-4 py-2 text-right">Rechazados</th>
                        <th class="px-4 py-2 text-right">Duplicados</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                {{ $batch->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $batch->apiClient?->labBranch?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $batch->apiClient?->name }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">
                                {{ Str::limit($batch->external_batch_id, 20) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">{{ $batch->items_total }}</td>
                            <td class="px-4 py-3 text-right font-mono text-green-700">{{ $batch->items_ingested }}</td>
                            <td class="px-4 py-3 text-right font-mono text-yellow-700">{{ $batch->items_overwritten }}</td>
                            <td class="px-4 py-3 text-right font-mono {{ $batch->items_rejected > 0 ? 'text-red-700 font-semibold' : '' }}">
                                {{ $batch->items_rejected }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-gray-500">{{ $batch->items_duplicate }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.api-monitor.batches.show', $batch) }}"
                                   class="text-blue-600 hover:underline text-xs">
                                    Ver →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                No hay batches para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($batches->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
</div>
