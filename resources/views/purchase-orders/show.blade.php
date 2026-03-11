<x-admin-layout>
    <div class="p-4 md:p-6">
        @php
            $statusColors = [
                'borrador' => 'bg-gray-100 text-gray-700',
                'aprobada' => 'bg-blue-100 text-blue-700',
                'parcial' => 'bg-amber-100 text-amber-700',
                'recibida' => 'bg-green-100 text-green-700',
                'cancelada' => 'bg-red-100 text-red-700',
            ];
        @endphp

        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $order->number }}</h1>
                    <p class="text-gray-500 text-sm mt-1">Orden de Compra</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ $order->status_label }}
                </span>
            </div>
            <div class="flex items-center gap-3 mt-3 md:mt-0">
                @if($order->status === 'borrador')
                    <a href="{{ route('purchase-orders.edit', $order) }}"
                       class="inline-flex items-center px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">Editar</a>
                @endif
                <a href="{{ route('purchase-orders.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Información General</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Proveedor</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $order->supplier->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Fecha</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $order->date->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Entrega Esperada</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $order->expected_delivery_date?->format('d/m/Y') ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Origen</dt>
                        <dd class="text-sm font-medium text-gray-800">
                            @if($order->quotationRequest)
                                <a href="{{ route('purchase-quotation-requests.show', $order->quotationRequest) }}" class="text-zinc-700 hover:text-zinc-900 underline">
                                    {{ $order->quotationRequest->number }}
                                </a>
                            @else
                                Directa
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Estado y Seguimiento</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Creado por</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $order->creator->name }}</dd>
                    </div>
                    @if($order->approver)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Aprobado por</dt>
                            <dd class="text-sm font-medium text-gray-800">{{ $order->approver->name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Fecha aprobación</dt>
                            <dd class="text-sm font-medium text-gray-800">{{ $order->approved_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Estado</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $order->status_label }}
                            </span>
                        </dd>
                    </div>
                </dl>

                @if(!in_array($order->status, ['cancelada', 'recibida']))
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <form method="POST" action="{{ route('purchase-orders.updateStatus', $order) }}" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="flex-1 rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                @if($order->status === 'borrador')
                                    <option value="aprobada">Aprobada</option>
                                    <option value="cancelada">Cancelada</option>
                                @elseif($order->status === 'aprobada')
                                    <option value="parcial">Parcialmente Recibida</option>
                                    <option value="recibida">Recibida</option>
                                    <option value="cancelada">Cancelada</option>
                                @elseif($order->status === 'parcial')
                                    <option value="recibida">Recibida</option>
                                    <option value="cancelada">Cancelada</option>
                                @endif
                            </select>
                            <button type="submit" class="px-3 py-2 bg-zinc-700 text-white text-sm rounded-lg hover:bg-zinc-800 transition-colors">
                                Cambiar
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        @if($order->notes)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <p class="text-sm text-gray-600"><span class="font-medium">Notas:</span> {{ $order->notes }}</p>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Ítems de la Orden</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Insumo</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cantidad</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Precio Unit.</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Recibido</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Pendiente</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($order->items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-mono text-gray-400 text-xs">{{ $item->supply->code }}</span>
                                    <span class="ml-1 text-gray-800 font-medium">{{ $item->supply->name }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-800 text-right">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 text-right">${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <span class="{{ $item->received_quantity > 0 ? 'text-green-600 font-medium' : 'text-gray-400' }}">
                                        {{ number_format($item->received_quantity, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <span class="{{ $item->pending_quantity > 0 ? 'text-amber-600 font-medium' : 'text-green-600' }}">
                                        {{ number_format($item->pending_quantity, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-800 text-right">${{ number_format($item->total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                <div class="flex justify-end">
                    <dl class="w-64 space-y-1">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-600">Subtotal</dt>
                            <dd class="font-medium text-gray-800">${{ number_format($order->subtotal, 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-600">IVA ({{ number_format($order->tax_rate, 0) }}%)</dt>
                            <dd class="font-medium text-gray-800">${{ number_format($order->tax_amount, 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between text-base font-bold border-t border-gray-300 pt-2 mt-2">
                            <dt class="text-gray-800">Total</dt>
                            <dd class="text-gray-900">${{ number_format($order->total, 2, ',', '.') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        @if($order->status === 'aprobada')
            <div class="flex justify-end mb-6">
                <a href="{{ route('delivery-notes.create', ['purchase_order_id' => $order->id]) }}"
                   class="inline-flex items-center px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    Generar Remito
                </a>
            </div>
        @endif

        @if($order->deliveryNotes->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Remitos Asociados</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Número</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($order->deliveryNotes as $note)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('delivery-notes.show', $note) }}" class="text-zinc-700 font-semibold hover:text-zinc-900">
                                            {{ $note->number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $note->date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$note->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $note->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <a href="{{ route('delivery-notes.show', $note) }}" class="text-gray-500 hover:text-zinc-700">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
