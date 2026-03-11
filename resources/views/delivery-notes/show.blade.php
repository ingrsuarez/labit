<x-admin-layout>
    <div class="p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Remito {{ $deliveryNote->remito_number }}</h1>
                    <p class="text-gray-500 text-sm mt-1">Detalle del remito de recepción</p>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $deliveryNote->status_color }}-100 text-{{ $deliveryNote->status_color }}-700">
                    {{ $deliveryNote->status_label }}
                </span>
            </div>
            <div class="flex items-center gap-3">
                @if($deliveryNote->status === 'pendiente')
                    <a href="{{ route('delivery-notes.edit', $deliveryNote) }}"
                       class="inline-flex items-center px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">
                        Editar
                    </a>
                @endif
                <a href="{{ route('delivery-notes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        <!-- Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">N° Remito</dt>
                        <dd class="text-sm font-semibold text-gray-800">{{ $deliveryNote->remito_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Fecha</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $deliveryNote->date->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Estado</dt>
                        <dd>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $deliveryNote->status_color }}-100 text-{{ $deliveryNote->status_color }}-700">
                                {{ $deliveryNote->status_label }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Proveedor</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $deliveryNote->supplier->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Orden de Compra</dt>
                        <dd class="text-sm font-medium text-gray-800">
                            @if($deliveryNote->purchaseOrder)
                                <a href="{{ route('purchase-orders.show', $deliveryNote->purchaseOrder) }}"
                                   class="text-zinc-600 hover:text-zinc-800 underline decoration-dotted">
                                    {{ $deliveryNote->purchaseOrder->number }}
                                </a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Recibido por</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $deliveryNote->receiver->name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            @if($deliveryNote->notes)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500 mb-1">Notas</p>
                    <p class="text-sm text-gray-700">{{ $deliveryNote->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Status: Aceptado -->
        @if($deliveryNote->status === 'aceptado')
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
                <svg class="w-6 h-6 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-green-800 font-medium">Remito aceptado — Stock actualizado</p>
            </div>
        @endif

        <!-- Items Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Ítems Recibidos</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Insumo</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cantidad Recibida</th>
                            @if($deliveryNote->purchase_order_id)
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Pedido en OC</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Pendiente OC</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Notas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($deliveryNote->items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-800">
                                    {{ $item->supply->name ?? 'Insumo eliminado' }}
                                    @if($item->supply)
                                        <span class="text-gray-400 text-xs ml-1">({{ $item->supply->unit }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-800 text-right font-semibold">
                                    {{ number_format($item->quantity_received, 2, ',', '.') }}
                                </td>
                                @if($deliveryNote->purchase_order_id)
                                    <td class="px-4 py-3 text-sm text-gray-500 text-right">
                                        @if($item->purchaseOrderItem)
                                            {{ number_format($item->purchaseOrderItem->quantity, 2, ',', '.') }}
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 text-right">
                                        @if($item->purchaseOrderItem)
                                            {{ number_format($item->purchaseOrderItem->pending_quantity, 2, ',', '.') }}
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-4 py-3 text-sm text-gray-400">
                                    {{ $item->notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Accept Section (only for pendiente) -->
        @if($deliveryNote->status === 'pendiente')
            @php
                $hasLotItems = $deliveryNote->items->contains(fn($item) => $item->supply && $item->supply->tracks_lot);
            @endphp

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5" x-data="{ confirming: false }">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Aceptar Remito</h2>

                <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <p class="text-sm text-amber-800">Al aceptar el remito se actualizará el stock de los insumos.</p>
                </div>

                <form method="POST" action="{{ route('delivery-notes.accept', $deliveryNote) }}">
                    @csrf

                    @if($hasLotItems)
                        <div class="mb-5">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Datos de Lote</h3>
                            <p class="text-xs text-gray-500 mb-3">Los siguientes insumos requieren número de lote y fecha de vencimiento.</p>
                            <div class="space-y-3">
                                @foreach($deliveryNote->items as $item)
                                    @if($item->supply && $item->supply->tracks_lot)
                                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <p class="text-sm font-medium text-gray-800 mb-2">
                                                {{ $item->supply->name }}
                                                <span class="text-gray-400 font-normal">({{ number_format($item->quantity_received, 2, ',', '.') }} {{ $item->supply->unit }})</span>
                                            </p>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">N° de Lote <span class="text-red-500">*</span></label>
                                                    <input type="text" name="items[{{ $item->id }}][lot_number]"
                                                           value="{{ old("items.{$item->id}.lot_number") }}"
                                                           required
                                                           placeholder="Ej: LOT-2026-001"
                                                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                                    @error("items.{$item->id}.lot_number")
                                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de Vencimiento <span class="text-red-500">*</span></label>
                                                    <input type="date" name="items[{{ $item->id }}][expiration_date]"
                                                           value="{{ old("items.{$item->id}.expiration_date") }}"
                                                           required
                                                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                                    @error("items.{$item->id}.expiration_date")
                                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div x-show="!confirming">
                        <button type="button" @click="confirming = true"
                                class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors text-sm shadow-sm">
                            Aceptar Remito
                        </button>
                    </div>
                    <div x-show="confirming" x-cloak class="flex items-center gap-3">
                        <p class="text-sm text-gray-600">¿Confirmar la aceptación y actualización de stock?</p>
                        <button type="submit"
                                class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors text-sm shadow-sm">
                            Sí, Aceptar
                        </button>
                        <button type="button" @click="confirming = false"
                                class="px-4 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-admin-layout>
