<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Movimientos de Stock</h1>
                <p class="text-gray-500 text-sm mt-1">Historial de entradas, salidas y ajustes</p>
            </div>
            <a href="{{ route('stock-movements.create') }}"
               class="mt-3 md:mt-0 inline-flex items-center px-4 py-2.5 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Movimiento
            </a>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <form method="GET" action="{{ route('stock-movements.index') }}" class="flex flex-col md:flex-row gap-3 flex-wrap">
                <div class="w-full md:w-56">
                    <select name="supply_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        <option value="">Todos los insumos</option>
                        @foreach($supplies as $sup)
                            <option value="{{ $sup->id }}" {{ request('supply_id') == $sup->id ? 'selected' : '' }}>{{ $sup->code }} - {{ $sup->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-36">
                    <select name="type" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        <option value="">Todos los tipos</option>
                        <option value="entrada" {{ request('type') === 'entrada' ? 'selected' : '' }}>Entrada</option>
                        <option value="salida" {{ request('type') === 'salida' ? 'selected' : '' }}>Salida</option>
                        <option value="ajuste" {{ request('type') === 'ajuste' ? 'selected' : '' }}>Ajuste</option>
                    </select>
                </div>
                <div class="w-full md:w-40">
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                           placeholder="Desde">
                </div>
                <div class="w-full md:w-40">
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                           placeholder="Hasta">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors">
                    Filtrar
                </button>
                @if(request()->hasAny(['supply_id', 'type', 'date_from', 'date_to', 'reason']))
                    <a href="{{ route('stock-movements.index') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($movements->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Insumo</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Cantidad</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Anterior</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Nuevo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lote</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vencimiento</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Motivo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Notas</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Usuario</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($movements as $mov)
                                @php
                                    $colors = [
                                        'entrada' => 'bg-green-100 text-green-700',
                                        'salida' => 'bg-red-100 text-red-700',
                                        'ajuste' => 'bg-amber-100 text-amber-700',
                                    ];
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                        {{ $mov->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        <a href="{{ route('supplies.show', $mov->supply_id) }}" class="text-zinc-700 hover:text-zinc-900 font-medium">
                                            {{ $mov->supply->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$mov->type] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $mov->type_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-right {{ $mov->type === 'entrada' ? 'text-green-600' : ($mov->type === 'salida' ? 'text-red-600' : 'text-amber-600') }}">
                                        {{ $mov->type === 'entrada' ? '+' : ($mov->type === 'salida' ? '-' : '') }}{{ number_format($mov->quantity, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-right">
                                        {{ number_format($mov->previous_stock, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-800 text-right">
                                        {{ number_format($mov->new_stock, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                        {{ $mov->lot_number ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                        @if($mov->expiration_date)
                                            <span class="{{ $mov->expiration_date->isPast() ? 'text-red-600 font-medium' : ($mov->expiration_date->diffInDays(now()) <= 30 ? 'text-amber-600' : '') }}">
                                                {{ $mov->expiration_date->format('d/m/Y') }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $mov->reason_label }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-400 max-w-[150px] truncate">
                                        {{ $mov->notes ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $mov->user->name ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($movements->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200">
                        {{ $movements->links() }}
                    </div>
                @endif
            @else
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay movimientos</h3>
                    <p class="mt-1 text-sm text-gray-500">No se encontraron movimientos de stock con los filtros seleccionados.</p>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
