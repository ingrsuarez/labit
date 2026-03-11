<x-admin-layout>
    <div class="p-4 md:p-6" x-data="deliveryEditForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar Remito {{ $deliveryNote->remito_number }}</h1>
                <p class="text-gray-500 text-sm mt-1">Modificar datos del remito</p>
            </div>
            <a href="{{ route('delivery-notes.show', $deliveryNote) }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
        </div>

        @if($purchaseOrder)
            <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-blue-800">
                        Vinculado a la Orden de Compra
                        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="font-semibold underline hover:text-blue-900">{{ $purchaseOrder->number }}</a>
                    </p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('delivery-notes.update', $deliveryNote) }}">
            @csrf
            @method('PUT')

            <!-- Datos Generales -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Remito</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N° Remito <span class="text-red-500">*</span></label>
                        <input type="text" name="remito_number"
                               value="{{ old('remito_number', $deliveryNote->remito_number) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <select name="supplier_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}"
                                    {{ old('supplier_id', $deliveryNote->supplier_id) == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="date"
                               value="{{ old('date', $deliveryNote->date->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Orden de Compra</label>
                        <select name="purchase_order_id" x-model="selectedPO" @change="loadPOItems()"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Sin OC vinculada</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}"
                                    {{ old('purchase_order_id', $deliveryNote->purchase_order_id) == $po->id ? 'selected' : '' }}>
                                    {{ $po->number }} — {{ $po->supplier->name }}
                                </option>
                            @endforeach
                            @if($purchaseOrder && !$purchaseOrders->contains('id', $purchaseOrder->id))
                                <option value="{{ $purchaseOrder->id }}" selected>
                                    {{ $purchaseOrder->number }} — {{ $purchaseOrder->supplier->name }}
                                </option>
                            @endif
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Observaciones opcionales">{{ old('notes', $deliveryNote->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Ítems del Remito</h2>
                    <button type="button" @click="addFreeItem()"
                            class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar ítem libre
                    </button>
                </div>

                <div x-show="items.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Insumo</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28" x-show="hasPOItems">Pedido</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28" x-show="hasPOItems">Pendiente</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Cant. Recibida</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-48">Notas</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm">
                                        <input type="hidden" :name="'items[' + index + '][supply_id]'" :value="item.supply_id">
                                        <input type="hidden" :name="'items[' + index + '][purchase_order_item_id]'" :value="item.purchase_order_item_id || ''">
                                        <template x-if="item.from_po">
                                            <span>
                                                <span class="font-medium text-gray-800" x-text="item.supply_name"></span>
                                                <span class="text-gray-400 text-xs ml-1" x-text="'(' + item.unit + ')'"></span>
                                            </span>
                                        </template>
                                        <template x-if="!item.from_po">
                                            <select :name="'items[' + index + '][supply_id_select]'"
                                                    x-model="item.supply_id"
                                                    required
                                                    class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                                <option value="">Seleccionar insumo...</option>
                                                @foreach(\App\Models\Supply::active()->orderBy('name')->get() as $sup)
                                                    <option value="{{ $sup->id }}">
                                                        {{ $sup->code }} - {{ $sup->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-center text-sm text-gray-500" x-show="hasPOItems">
                                        <span x-text="item.ordered_qty ? parseFloat(item.ordered_qty).toLocaleString('es-AR', {minimumFractionDigits: 2}) : '-'"></span>
                                    </td>
                                    <td class="px-3 py-2 text-center text-sm text-gray-500" x-show="hasPOItems">
                                        <span x-text="item.pending_qty ? parseFloat(item.pending_qty).toLocaleString('es-AR', {minimumFractionDigits: 2}) : '-'"></span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" :name="'items[' + index + '][quantity_received]'"
                                               x-model.number="item.quantity_received"
                                               min="0.01" step="0.01" required
                                               class="w-28 rounded border-gray-300 text-sm text-center focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" :name="'items[' + index + '][notes]'" x-model="item.notes"
                                               placeholder="Opcional"
                                               class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div x-show="items.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Agregá ítems al remito
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('delivery-notes.show', $deliveryNote) }}"
                   class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 text-sm">
                    Cancelar
                </a>
                <button type="submit" :disabled="items.length === 0"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <script>
        function deliveryEditForm() {
            const existingItems = @json($deliveryNote->items->map(fn($item) => [
                'supply_id' => $item->supply_id,
                'supply_name' => $item->supply->name ?? 'Insumo eliminado',
                'unit' => $item->supply->unit ?? '',
                'ordered_qty' => $item->purchaseOrderItem?->quantity,
                'pending_qty' => $item->purchaseOrderItem?->pending_quantity,
                'purchase_order_item_id' => $item->purchase_order_item_id,
                'quantity_received' => $item->quantity_received,
                'notes' => $item->notes ?? '',
                'from_po' => $item->purchase_order_item_id !== null,
            ]));

            const purchaseOrdersItemsMap = @json(
                $purchaseOrders->mapWithKeys(fn($po) => [
                    $po->id => $po->items
                        ->filter(fn($item) => $item->pending_quantity > 0)
                        ->map(fn($item) => [
                            'supply_id' => $item->supply_id,
                            'supply_name' => $item->supply->name,
                            'unit' => $item->supply->unit,
                            'ordered_qty' => $item->quantity,
                            'pending_qty' => $item->pending_quantity,
                            'purchase_order_item_id' => $item->id,
                            'quantity_received' => $item->pending_quantity,
                            'notes' => '',
                            'from_po' => true,
                        ])->values()
                ])
            );

            return {
                selectedPO: '{{ old("purchase_order_id", $deliveryNote->purchase_order_id ?? "") }}',
                items: [...existingItems],

                get hasPOItems() {
                    return this.items.some(i => i.from_po);
                },

                loadPOItems() {
                    const freeItems = this.items.filter(i => !i.from_po);

                    if (this.selectedPO && purchaseOrdersItemsMap[this.selectedPO]) {
                        const poItems = purchaseOrdersItemsMap[this.selectedPO].map(i => ({...i}));
                        this.items = [...poItems, ...freeItems];
                    } else {
                        this.items = [...freeItems];
                    }
                },

                addFreeItem() {
                    this.items.push({
                        supply_id: '',
                        supply_name: '',
                        unit: '',
                        ordered_qty: null,
                        pending_qty: null,
                        purchase_order_item_id: null,
                        quantity_received: 1,
                        notes: '',
                        from_po: false,
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                }
            }
        }
    </script>
</x-admin-layout>
