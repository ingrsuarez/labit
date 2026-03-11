<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Solicitud {{ $quotation->number }}</h1>
                <p class="text-gray-500 text-sm mt-1">Proveedor: {{ $quotation->supplier->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if($quotation->status === 'borrador')
                    <a href="{{ route('purchase-quotation-requests.edit', $quotation) }}"
                       class="inline-flex items-center px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">Editar</a>
                @endif
                <a href="{{ route('purchase-quotation-requests.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Número</dt>
                        <dd class="text-sm font-semibold text-gray-800">{{ $quotation->number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Fecha</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $quotation->date->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Válido hasta</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $quotation->valid_until?->format('d/m/Y') ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Proveedor</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $quotation->supplier->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Creado por</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $quotation->creator->name }}</dd>
                    </div>
                </dl>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                @php
                    $statusColors = [
                        'borrador' => 'bg-gray-100 text-gray-700',
                        'enviada' => 'bg-blue-100 text-blue-700',
                        'recibida' => 'bg-green-100 text-green-700',
                        'cancelada' => 'bg-red-100 text-red-700',
                    ];
                @endphp
                <div class="text-center mb-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$quotation->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $quotation->status_label }}
                    </span>
                </div>
                <form method="POST" action="{{ route('purchase-quotation-requests.updateStatus', $quotation) }}" class="flex gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="flex-1 rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        <option value="borrador" {{ $quotation->status === 'borrador' ? 'selected' : '' }}>Borrador</option>
                        <option value="enviada" {{ $quotation->status === 'enviada' ? 'selected' : '' }}>Enviada</option>
                        <option value="recibida" {{ $quotation->status === 'recibida' ? 'selected' : '' }}>Recibida</option>
                        <option value="cancelada" {{ $quotation->status === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                    <button type="submit" class="px-3 py-2 bg-zinc-700 text-white text-sm rounded-lg hover:bg-zinc-800 transition-colors">
                        Cambiar
                    </button>
                </form>
            </div>
        </div>

        @if($quotation->notes)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <p class="text-sm text-gray-600"><span class="font-medium">Notas:</span> {{ $quotation->notes }}</p>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Insumos Solicitados</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Código</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Insumo</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Cantidad</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Precio Unit.</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Notas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($quotation->items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ $item->supply->code }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800 font-medium">{{ $item->supply->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800 text-right">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->supply->unit }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 text-right">
                                    {{ $item->unit_price !== null ? '$' . number_format($item->unit_price, 2, ',', '.') : 'Pendiente' }}
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-800 text-right">
                                    {{ $item->unit_price !== null ? '$' . number_format($item->total, 2, ',', '.') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-400">{{ $item->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($quotation->status === 'recibida')
            <div class="mt-6 flex justify-end">
                <a href="{{ route('purchase-orders.create', ['quotation' => $quotation->id]) }}"
                   class="inline-flex items-center px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Generar Orden de Compra
                </a>
            </div>
        @endif
    </div>
</x-admin-layout>
