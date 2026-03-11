<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $supply->name }}@if($supply->brand) <span class="text-lg font-normal text-gray-400">· {{ $supply->brand }}</span>@endif</h1>
                <p class="text-gray-500 text-sm mt-1">
                    {{ $supply->code }} {{ $supply->category ? '- ' . $supply->category->name : '' }}
                    @if($supply->tracks_lot)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-600">Controla Lote/Vencimiento</span>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('supplies.edit', $supply) }}"
                   class="inline-flex items-center px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">
                    Editar
                </a>
                <a href="{{ route('supplies.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                    &larr; Volver
                </a>
            </div>
        </div>

        <!-- Resumen de Stock -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                <p class="text-3xl font-bold {{ $supply->isLowStock() ? 'text-amber-600' : 'text-gray-800' }}">
                    {{ number_format($supply->stock, 2, ',', '.') }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Stock Actual ({{ $supply->unit }})</p>
                @if($supply->isLowStock())
                    <p class="text-xs text-amber-600 font-medium mt-1">Stock bajo</p>
                @endif
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                <p class="text-3xl font-bold text-gray-600">{{ number_format($supply->min_stock, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-500 mt-1">Stock Mínimo</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                <p class="text-3xl font-bold text-gray-600">${{ number_format($supply->last_price, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-500 mt-1">Último Precio</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                <p class="text-sm text-gray-600 font-medium">{{ $supply->defaultSupplier->name ?? 'Sin proveedor' }}</p>
                <p class="text-xs text-gray-500 mt-1">Proveedor por Defecto</p>
            </div>
        </div>

        @if($supply->description)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <p class="text-sm text-gray-600">{{ $supply->description }}</p>
            </div>
        @endif

        <!-- Historial de Movimientos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Historial de Movimientos</h2>
                <a href="{{ route('stock-movements.create') }}"
                   class="text-sm text-zinc-600 hover:text-zinc-800 font-medium">
                    + Registrar movimiento
                </a>
            </div>

            @if($movements->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Cantidad</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Stock Anterior</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Stock Nuevo</th>
                                @if($supply->tracks_lot)
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lote</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vencimiento</th>
                                @endif
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Motivo</th>
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
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$mov->type] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $mov->type_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-right {{ $mov->type === 'entrada' ? 'text-green-600' : ($mov->type === 'salida' ? 'text-red-600' : 'text-amber-600') }}">
                                        {{ $mov->type === 'entrada' ? '+' : ($mov->type === 'salida' ? '-' : '') }}{{ number_format($mov->quantity, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 text-right">{{ number_format($mov->previous_stock, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800 text-right">{{ number_format($mov->new_stock, 2, ',', '.') }}</td>
                                    @if($supply->tracks_lot)
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $mov->lot_number ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if($mov->expiration_date)
                                            <span class="{{ $mov->expiration_date->isPast() ? 'text-red-600 font-medium' : ($mov->expiration_date->diffInDays(now()) <= 30 ? 'text-amber-600' : '') }}">
                                                {{ $mov->expiration_date->format('d/m/Y') }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    @endif
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $mov->reason_label }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $mov->user->name ?? '-' }}</td>
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
                <div class="p-8 text-center text-gray-400 text-sm">
                    No hay movimientos de stock registrados para este insumo.
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
