<x-admin-layout>
    <div class="p-4 md:p-6" x-data="deliveryEditForm()" @add-remito-item="addFreeItemAndFocusNext()">
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

        <form method="POST" action="{{ route('delivery-notes.update', $deliveryNote) }}" @keydown.enter="remitoHeaderEnter($event)" @submit="if (duplicateRemitoWarning) { $event.preventDefault(); }">
            @csrf
            @method('PUT')

            <!-- Datos Generales -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Remito</h2>
                <div x-show="duplicateRemitoWarning" x-cloak class="mb-4 p-3 rounded-lg border border-amber-300 bg-amber-50 text-sm text-amber-900 flex gap-2 items-start">
                    <svg class="w-5 h-5 shrink-0 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Ya existe otro remito con este número para el mismo proveedor. Corregí antes de guardar.</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 [&>div]:min-w-0">
                    <input type="hidden" name="remito_number" :value="remitoNumberForDup">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Punto de Venta</label>
                        <input type="text" x-model="remitoPdv"
                               @blur="padRemitoPdv(); checkDuplicateRemito()"
                               maxlength="5" placeholder="00000"
                               :class="duplicateRemitoWarning ? 'w-full rounded-lg border-red-400 text-sm focus:border-red-500 focus:ring-red-500' : 'w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500'">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N° Remito <span class="text-red-500">*</span></label>
                        <input type="text" x-model="remitoNum"
                               @blur="padRemitoNum(); checkDuplicateRemito()" required
                               maxlength="8" placeholder="00000000"
                               :class="duplicateRemitoWarning ? 'w-full rounded-lg border-red-400 text-sm focus:border-red-500 focus:ring-red-500' : 'w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500'">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-2">
                            <select name="supplier_id" x-model="supplierIdForDup" @change="checkDuplicateRemito()" required
                                    :class="duplicateRemitoWarning
                                        ? 'flex-1 min-w-0 rounded-lg border-red-400 text-sm focus:border-red-500 focus:ring-red-500'
                                        : 'flex-1 min-w-0 rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500'">
                                <option value="">Seleccionar...</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                                <template x-for="sup in inlineSuppliers" :key="sup.id">
                                    <option :value="sup.id" x-text="sup.name"></option>
                                </template>
                            </select>
                            @can('suppliers.create')
                            <button type="button" @click="openNewSupplierModal()"
                                    class="flex-shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-zinc-700 transition-colors"
                                    title="Nuevo proveedor">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                            @endcan
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="date"
                               value="{{ old('date', $deliveryNote->date->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    @if($deliveryNote->status === 'aceptado')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha recepción en stock</label>
                        <input type="date" name="stock_received_at"
                               value="{{ old('stock_received_at', $deliveryNote->stock_received_at?->format('Y-m-d') ?? $deliveryNote->date->format('Y-m-d')) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        <p class="text-xs text-gray-500 mt-1">Usada al sincronizar movimientos. Si queda vacío, se usa la fecha del remito.</p>
                    </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sede / depósito <span class="text-red-500">*</span></label>
                        <select name="lab_branch_id" x-model="lab_branch_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($branches as $br)
                                <option value="{{ $br->id }}">{{ $br->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 lg:col-span-2">
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
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Observaciones opcionales">{{ old('notes', $deliveryNote->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div id="remito-items-section" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
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

                <div x-show="items.length > 0" class="overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" style="max-width: 260px;">Insumo</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28" x-show="hasPOItems">Pedido</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28" x-show="hasPOItems">Pendiente</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Cant.</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Lote</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Vencimiento</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Notas</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm relative" style="max-width: 260px;" x-data="item.from_po ? {} : supplySearch(item, index)">
                                        <input type="hidden" :name="'items[' + index + '][supply_id]'" :value="item.supply_id">
                                        <input type="hidden" :name="'items[' + index + '][purchase_order_item_id]'" :value="item.purchase_order_item_id || ''">
                                        <template x-if="item.from_po">
                                            <span>
                                                <span class="font-medium text-gray-800" x-text="item.supply_name"></span>
                                                <span class="text-gray-400 text-xs ml-1" x-text="'(' + item.unit + ')'"></span>
                                            </span>
                                        </template>
                                        <template x-if="!item.from_po">
                                            <div>
                                                <div class="flex items-center gap-1">
                                                    <input type="text"
                                                           autocomplete="off"
                                                           x-show="!item.supply_id"
                                                           x-model="searchText"
                                                           @input.debounce.300ms="doSearch()"
                                                           @focus="if (searchText.length >= 2) showResults = true"
                                                           @keydown.arrow-down.prevent="highlightNext()"
                                                           @keydown.arrow-up.prevent="highlightPrev()"
                                                           @keydown.enter.prevent="onSupplySearchEnter()"
                                                           @keydown.escape="showResults = false"
                                                           @keydown.tab="onTab($event)"
                                                           placeholder="Buscar insumo..."
                                                           :required="!item.supply_id"
                                                           :id="'item-supply-' + index"
                                                           class="flex-1 min-w-0 rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">

                                                    <span x-show="item.supply_id" x-cloak
                                                          class="shrink-0 inline-flex items-center gap-2 mt-0.5">
                                                        <span class="text-xs text-teal-700 font-mono" x-text="item._supply_code"></span>
                                                        <span class="text-xs text-gray-500" x-text="item._supply_label || item._supply_name"></span>
                                                        <button type="button" @click="unlinkSupply()" class="text-xs text-gray-400 hover:text-red-500">&times;</button>
                                                    </span>
                                                </div>

                                                <div x-show="showResults && (results.length > 0 || (searchText.length >= 2 && !loading))" x-cloak
                                                     @click.outside="showResults = false"
                                                     class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                                    <template x-for="(supply, i) in results" :key="supply.id">
                                                        <button type="button"
                                                                @click="selectSupply(supply)"
                                                                @mouseenter="highlightedIndex = i"
                                                                :class="highlightedIndex === i ? 'bg-teal-50 text-teal-800' : 'text-gray-700'"
                                                                class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center gap-2 border-b border-gray-100 last:border-0">
                                                            <span class="font-mono text-xs text-gray-400" x-text="supply.code"></span>
                                                            <span x-text="supply.name"></span>
                                                            <template x-if="supply.brand">
                                                                <span class="text-xs text-gray-400" x-text="'— ' + supply.brand"></span>
                                                            </template>
                                                        </button>
                                                    </template>

                                                    <div x-show="results.length === 0 && searchText.length >= 2 && !loading"
                                                         class="px-3 py-2 text-sm text-gray-400">
                                                        No se encontraron insumos.
                                                    </div>

                                                    <button type="button"
                                                            x-show="searchText.length >= 2 && !loading"
                                                            @click="openCreateModal()"
                                                            class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2 border-t border-gray-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                        </svg>
                                                        <span>Crear insumo "<span x-text="searchText" class="font-medium"></span>"</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-center text-sm text-gray-500" x-show="hasPOItems">
                                        <span x-text="item.ordered_qty ? parseInt(item.ordered_qty).toLocaleString('es-AR') : '-'"></span>
                                    </td>
                                    <td class="px-3 py-2 text-center text-sm text-gray-500" x-show="hasPOItems">
                                        <span x-text="item.pending_qty ? parseInt(item.pending_qty).toLocaleString('es-AR') : '-'"></span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" :name="'items[' + index + '][quantity_received]'"
                                               x-model.number="item.quantity_received"
                                               min="1" step="1" required
                                               :id="'item-qty-' + index"
                                               @keydown.enter.prevent="$dispatch('add-remito-item')"
                                               class="w-24 rounded border-gray-300 text-sm text-center focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <template x-if="item._tracks_lot">
                                            <input type="text" :name="'items[' + index + '][lot_number]'"
                                                   x-model="item.lot_number"
                                                   @keydown.enter.prevent="$dispatch('add-remito-item')"
                                                   placeholder="Lote"
                                                   class="w-24 rounded border-gray-300 text-xs focus:border-zinc-500 focus:ring-zinc-500">
                                        </template>
                                        <template x-if="!item._tracks_lot">
                                            <span class="text-gray-300 text-xs">—</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2">
                                        <template x-if="item._tracks_lot">
                                            <input type="date" :name="'items[' + index + '][expiration_date]'"
                                                   x-model="item.expiration_date"
                                                   @keydown.enter.prevent="$dispatch('add-remito-item')"
                                                   class="w-32 rounded border-gray-300 text-xs focus:border-zinc-500 focus:ring-zinc-500">
                                        </template>
                                        <template x-if="!item._tracks_lot">
                                            <span class="text-gray-300 text-xs">—</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" :name="'items[' + index + '][notes]'" x-model="item.notes"
                                               @keydown.enter.prevent="$dispatch('add-remito-item')"
                                               placeholder="—"
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
                <button type="submit" :disabled="items.length === 0 || duplicateRemitoWarning"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>

        {{-- Modal crear insumo --}}
        <div x-show="showNewSupplyModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             @keydown.escape.window="showNewSupplyModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6" @click.outside="showNewSupplyModal = false">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Crear Insumo Nuevo</h3>
                <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-4">El insumo se creará con stock 0. El stock se actualizará al aceptar el remito.</p>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" x-model="newSupply.name" required
                               class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500"
                               placeholder="Nombre del insumo">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select x-model="newSupply.supply_category_id"
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Sin categoría</option>
                            @foreach(\App\Models\SupplyCategory::active()->orderBy('name')->get() as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unidad *</label>
                        <select x-model="newSupply.unit" required
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                            <option value="unidad">Unidad</option>
                            <option value="litro">Litro</option>
                            <option value="kg">Kilogramo</option>
                            <option value="caja">Caja</option>
                            <option value="pack">Pack</option>
                            <option value="metro">Metro</option>
                            <option value="rollo">Rollo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                        <input type="text" x-model="newSupply.brand"
                               class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500"
                               placeholder="Opcional">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" x-model="newSupply.tracks_lot" id="editNewSupplyTracksLot"
                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        <label for="editNewSupplyTracksLot" class="text-sm text-gray-700">¿Controla lote/vencimiento?</label>
                    </div>
                </div>
                <div x-show="newSupplyError" class="mt-3 text-sm text-red-600" x-text="newSupplyError"></div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" @click="showNewSupplyModal = false"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                    <button type="button" @click="createSupply()" :disabled="newSupplySaving"
                            class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50">
                        <span x-show="!newSupplySaving">Crear Insumo</span>
                        <span x-show="newSupplySaving">Creando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
        $existingItemsJson = $deliveryNote->items->map(function ($item) {
            $brand = $item->supply?->brand ?? '';
            $name = $item->supply?->name ?? '';
            return [
                'supply_id' => $item->supply_id,
                'supply_name' => $item->supply?->name ?? 'Insumo eliminado',
                '_supply_name' => $name,
                '_supply_brand' => $brand,
                '_supply_label' => $brand ? "{$name} — {$brand}" : $name,
                '_supply_code' => $item->supply?->code ?? '',
                '_tracks_lot' => $item->supply?->tracks_lot === true,
                'unit' => $item->supply?->unit ?? '',
                'ordered_qty' => $item->purchaseOrderItem?->quantity,
                'pending_qty' => $item->purchaseOrderItem?->pending_quantity,
                'purchase_order_item_id' => $item->purchase_order_item_id,
                'quantity_received' => $item->quantity_received,
                'lot_number' => $item->lot_number ?? '',
                'expiration_date' => $item->expiration_date ? $item->expiration_date->format('Y-m-d') : '',
                'notes' => $item->notes ?? '',
                'from_po' => $item->purchase_order_item_id !== null,
            ];
        })->values();
        $poItemsMap = $purchaseOrders->mapWithKeys(function ($po) {
            return [
                $po->id => $po->items
                    ->filter(function ($item) { return $item->pending_quantity > 0; })
                    ->map(function ($item) {
                        return [
                            'supply_id' => $item->supply_id,
                            'supply_name' => $item->supply->name,
                            'unit' => $item->supply->unit,
                            'ordered_qty' => $item->quantity,
                            'pending_qty' => $item->pending_quantity,
                            'purchase_order_item_id' => $item->id,
                            'quantity_received' => $item->pending_quantity,
                            'lot_number' => '',
                            'expiration_date' => '',
                            '_tracks_lot' => $item->supply->tracks_lot === true,
                            'notes' => '',
                            'from_po' => true,
                        ];
                    })->values()
            ];
        });
        $poBranchByIdJson = $purchaseOrders->mapWithKeys(fn ($po) => [$po->id => $po->lab_branch_id]);
        if ($purchaseOrder) {
            $poBranchByIdJson[$purchaseOrder->id] = $purchaseOrder->lab_branch_id;
        }
    @endphp
    <script>
        function supplySearch(item, index) {
            return {
                searchText: item._supply_label || item._supply_name || '',
                results: [],
                showResults: false,
                loading: false,
                highlightedIndex: -1,

                async doSearch() {
                    if (this.searchText.length < 2) {
                        this.results = [];
                        this.showResults = false;
                        return;
                    }
                    this.loading = true;
                    try {
                        const resp = await fetch(`{{ route('supplies.search') }}?q=${encodeURIComponent(this.searchText)}`);
                        this.results = await resp.json();
                        this.showResults = true;
                        this.highlightedIndex = -1;
                    } catch (e) {
                        this.results = [];
                    } finally {
                        this.loading = false;
                    }
                },

                selectSupply(supply) {
                    const label = supply.brand
                        ? `${supply.name} — ${supply.brand}`
                        : supply.name;

                    item.supply_id = supply.id;
                    item._supply_name = supply.name;
                    item._supply_brand = supply.brand || '';
                    item._supply_label = label;
                    item._supply_code = supply.code;
                    item._tracks_lot = !!supply.tracks_lot;
                    if (!supply.tracks_lot) {
                        item.lot_number = '';
                        item.expiration_date = '';
                    }
                    this.searchText = '';
                    this.showResults = false;
                    this.$nextTick(() => {
                        const qtyInput = document.querySelector(`#item-qty-${index}`);
                        if (qtyInput) qtyInput.focus();
                    });
                },

                unlinkSupply() {
                    item.supply_id = '';
                    item._supply_code = '';
                    item._supply_name = '';
                    item._supply_label = '';
                    this.searchText = '';
                    this.$nextTick(() => {
                        const el = document.querySelector(`#item-supply-${index}`);
                        if (el) el.focus();
                    });
                },

                highlightNext() {
                    if (this.highlightedIndex < this.results.length - 1) this.highlightedIndex++;
                },

                highlightPrev() {
                    if (this.highlightedIndex > 0) this.highlightedIndex--;
                },

                selectHighlighted() {
                    if (this.highlightedIndex >= 0 && this.highlightedIndex < this.results.length) {
                        this.selectSupply(this.results[this.highlightedIndex]);
                    }
                },

                onSupplySearchEnter() {
                    if (this.showResults && this.highlightedIndex >= 0 && this.highlightedIndex < this.results.length) {
                        this.selectHighlighted();

                        return;
                    }
                    this.$dispatch('add-remito-item');
                },

                onTab(event) {
                    if (this.showResults && this.highlightedIndex >= 0) {
                        event.preventDefault();
                        this.selectHighlighted();
                    } else {
                        this.showResults = false;
                    }
                },

                openCreateModal() {
                    this.showResults = false;
                    const formEl = this.$root.closest('[x-data="deliveryForm()"]') || this.$root.closest('[x-data="deliveryEditForm()"]');
                    if (formEl && formEl._x_dataStack) {
                        const parentData = formEl._x_dataStack[0];
                        parentData.newSupply.name = this.searchText;
                        parentData.newSupplyForRow = index;
                        parentData.showNewSupplyModal = true;
                    }
                }
            }
        }

        function deliveryEditForm() {
            const existingItems = @json($existingItemsJson);

            const purchaseOrdersItemsMap = @json($poItemsMap);
            const poBranchById = @json($poBranchByIdJson);

            return {
                remitoPdv: @json(explode('-', old('remito_number', $deliveryNote->remito_number ?? ''), 2)[0] ?? ''),
                remitoNum: @json(explode('-', old('remito_number', $deliveryNote->remito_number ?? ''), 2)[1] ?? ''),

                get remitoNumberForDup() {
                    if (!this.remitoPdv || !this.remitoNum) return '';
                    return this.remitoPdv + '-' + this.remitoNum;
                },

                supplierIdForDup: '{{ old('supplier_id', $deliveryNote->supplier_id) }}',
                duplicateRemitoWarning: false,
                excludeRemitoId: {{ $deliveryNote->id }},

                showNewSupplierModal: false,
                inlineSuppliers: [],
                newSupplierErrors: {},
                newSupplierLoading: false,
                newSupplierForm: {
                    name: '', business_name: '', tax_id: '', tax_condition: '',
                    email: '', phone: '', address: '', city: '', state: '',
                    country: 'Argentina', postal: '', cbu: '', bank_alias: '',
                    bank_name: '', contact_name: '', contact_phone: '', notes: '',
                },

                selectedPO: '{{ old("purchase_order_id", $deliveryNote->purchase_order_id ?? "") }}',
                lab_branch_id: '{{ old("lab_branch_id", $deliveryNote->lab_branch_id ?? "") }}',
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
                    if (this.selectedPO && poBranchById[this.selectedPO]) {
                        this.lab_branch_id = String(poBranchById[this.selectedPO]);
                    }
                },

                addFreeItem() {
                    this.items.push({
                        supply_id: '',
                        supply_name: '',
                        _supply_name: '',
                        _supply_code: '',
                        _tracks_lot: false,
                        unit: '',
                        ordered_qty: null,
                        pending_qty: null,
                        purchase_order_item_id: null,
                        quantity_received: 1,
                        lot_number: '',
                        expiration_date: '',
                        notes: '',
                        from_po: false,
                    });
                },

                addFreeItemAndFocusNext() {
                    this.addFreeItem();
                    this.$nextTick(() => {
                        const idx = this.items.length - 1;
                        const row = this.items[idx];
                        let el = document.getElementById('item-supply-' + idx);
                        if (! el || row?.from_po) {
                            el = document.getElementById('item-qty-' + idx);
                        }
                        el?.focus();
                    });
                },

                openNewSupplierModal() {
                    this.newSupplierErrors = {};
                    this.newSupplierForm = {
                        name: '', business_name: '', tax_id: '', tax_condition: '',
                        email: '', phone: '', address: '', city: '', state: '',
                        country: 'Argentina', postal: '', cbu: '', bank_alias: '',
                        bank_name: '', contact_name: '', contact_phone: '', notes: '',
                    };
                    this.showNewSupplierModal = true;
                },

                async submitNewSupplier() {
                    this.newSupplierLoading = true;
                    this.newSupplierErrors = {};
                    try {
                        const resp = await fetch('{{ route('suppliers.store-inline') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(this.newSupplierForm),
                        });
                        const data = await resp.json();
                        if (!resp.ok) {
                            this.newSupplierErrors = data.errors ?? { name: [data.message ?? 'Error al guardar.'] };
                            return;
                        }
                        this.inlineSuppliers.push({ id: data.id, name: data.name });
                        this.supplierIdForDup = String(data.id);
                        this.showNewSupplierModal = false;
                        await this.checkDuplicateRemito();
                    } catch (e) {
                        this.newSupplierErrors = { name: ['Error de red. Intentá nuevamente.'] };
                    } finally {
                        this.newSupplierLoading = false;
                    }
                },

                remitoHeaderEnter($event) {
                    const t = $event.target;
                    if (t.tagName === 'TEXTAREA') {
                        return;
                    }
                    if (t.tagName === 'INPUT' && ! t.closest('#remito-items-section')) {
                        $event.preventDefault();
                    }
                },

                padRemitoPdv() {
                    if (this.remitoPdv) {
                        this.remitoPdv = String(parseInt(this.remitoPdv) || 0).padStart(5, '0');
                    }
                },

                padRemitoNum() {
                    if (this.remitoNum) {
                        this.remitoNum = String(parseInt(this.remitoNum) || 0).padStart(8, '0');
                    }
                },

                async checkDuplicateRemito() {
                    const num = (this.remitoNumberForDup || '').trim();
                    const sup = this.supplierIdForDup;
                    if (! num || ! sup) {
                        this.duplicateRemitoWarning = false;

                        return;
                    }
                    try {
                        const params = new URLSearchParams({
                            remito_number: num,
                            supplier_id: String(sup),
                        });
                        if (this.excludeRemitoId) {
                            params.set('exclude_id', String(this.excludeRemitoId));
                        }
                        const resp = await fetch(`{{ route('delivery-notes.check-duplicate') }}?${params}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                        });
                        const data = await resp.json();
                        this.duplicateRemitoWarning = !! data.duplicate;
                    } catch (e) {
                        this.duplicateRemitoWarning = false;
                    }
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                init() {
                    this.$nextTick(() => this.checkDuplicateRemito());
                },

                showNewSupplyModal: false,
                newSupplyForRow: null,
                newSupplySaving: false,
                newSupplyError: '',
                newSupply: { name: '', supply_category_id: '', unit: 'unidad', brand: '', min_stock: 0, tracks_lot: false },

                async createSupply() {
                    if (!this.newSupply.name.trim()) {
                        this.newSupplyError = 'El nombre es obligatorio.';
                        return;
                    }
                    this.newSupplySaving = true;
                    this.newSupplyError = '';
                    try {
                        const resp = await fetch('{{ route("supplies.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(this.newSupply),
                        });
                        if (!resp.ok) {
                            const err = await resp.json();
                            this.newSupplyError = err.message || 'Error al crear el insumo.';
                            return;
                        }
                        const data = await resp.json();
                        if (this.newSupplyForRow !== null && this.items[this.newSupplyForRow]) {
                            this.items[this.newSupplyForRow].supply_id = data.id;
                            this.items[this.newSupplyForRow]._supply_name = data.name;
                            this.items[this.newSupplyForRow]._supply_code = data.code;
                            this.items[this.newSupplyForRow]._tracks_lot = !!data.tracks_lot;
                            if (!data.tracks_lot) {
                                this.items[this.newSupplyForRow].lot_number = '';
                                this.items[this.newSupplyForRow].expiration_date = '';
                            }
                            this.$nextTick(() => {
                                const input = document.querySelector(`#item-supply-${this.newSupplyForRow}`);
                                if (input) {
                                    input.value = data.name;
                                    input.dispatchEvent(new Event('input', { bubbles: true }));
                                }
                            });
                        }
                        this.showNewSupplyModal = false;
                        this.newSupply = { name: '', supply_category_id: '', unit: 'unidad', brand: '', min_stock: 0, tracks_lot: false };
                    } catch (e) {
                        this.newSupplyError = 'Error de red al crear el insumo.';
                    } finally {
                        this.newSupplySaving = false;
                    }
                }
            }
        }
    </script>
{{-- Modal Nuevo Proveedor --}}
<div x-show="showNewSupplierModal" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @keydown.escape.window="showNewSupplierModal = false">
    <div class="absolute inset-0 bg-black/50" @click="showNewSupplierModal = false"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
            <h2 class="text-lg font-semibold text-gray-800">Nuevo Proveedor</h2>
            <button type="button" @click="showNewSupplierModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <template x-if="Object.keys(newSupplierErrors).length > 0">
            <div class="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                    <template x-for="(msgs, field) in newSupplierErrors" :key="field">
                        <template x-for="msg in msgs" :key="msg"><li x-text="msg"></li></template>
                    </template>
                </ul>
            </div>
        </template>
        <div class="p-6 space-y-5">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Datos Principales</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newSupplierForm.name" placeholder="Nombre comercial" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" :class="newSupplierErrors.name ? 'border-red-400' : ''">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social</label>
                        <input type="text" x-model="newSupplierForm.business_name" placeholder="Razón social" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CUIT</label>
                        <input type="text" x-model="newSupplierForm.tax_id" placeholder="XX-XXXXXXXX-X" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" :class="newSupplierErrors.tax_id ? 'border-red-400' : ''">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condición IVA</label>
                        <select x-model="newSupplierForm.tax_condition" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            <option value="responsable_inscripto">Responsable Inscripto</option>
                            <option value="monotributo">Monotributo</option>
                            <option value="exento">Exento</option>
                            <option value="consumidor_final">Consumidor Final</option>
                        </select>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Contacto</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" x-model="newSupplierForm.email" placeholder="correo@proveedor.com" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" :class="newSupplierErrors.email ? 'border-red-400' : ''">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" x-model="newSupplierForm.phone" placeholder="Teléfono principal" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contacto (persona)</label>
                        <input type="text" x-model="newSupplierForm.contact_name" placeholder="Nombre del contacto" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono de contacto</label>
                        <input type="text" x-model="newSupplierForm.contact_phone" placeholder="Teléfono del contacto" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Dirección</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" x-model="newSupplierForm.address" placeholder="Calle y número" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                        <input type="text" x-model="newSupplierForm.city" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                        <input type="text" x-model="newSupplierForm.state" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <input type="text" x-model="newSupplierForm.country" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código Postal</label>
                        <input type="text" x-model="newSupplierForm.postal" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Datos Bancarios</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CBU</label>
                        <input type="text" x-model="newSupplierForm.cbu" placeholder="CBU (22 dígitos)" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alias</label>
                        <input type="text" x-model="newSupplierForm.bank_alias" placeholder="Alias de transferencia" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                        <input type="text" x-model="newSupplierForm.bank_name" placeholder="Nombre del banco" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notas / Observaciones</label>
                <textarea x-model="newSupplierForm.notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" placeholder="Observaciones sobre el proveedor..."></textarea>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 bg-white">
            <button type="button" @click="showNewSupplierModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</button>
            <button type="button" @click="submitNewSupplier()" :disabled="newSupplierLoading" class="px-5 py-2 text-sm font-semibold text-white bg-zinc-700 rounded-lg hover:bg-zinc-800 disabled:opacity-60 transition-colors">
                <span x-show="!newSupplierLoading">Guardar Proveedor</span>
                <span x-show="newSupplierLoading">Guardando...</span>
            </button>
        </div>
    </div>
</div>
</x-admin-layout>
