<x-admin-layout>
    <div class="p-4 md:p-6" x-data="deliveryForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nuevo Remito</h1>
                <p class="text-gray-500 text-sm mt-1">Registrar recepción de mercadería</p>
            </div>
            <a href="{{ route('delivery-notes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
        </div>

        @if($purchaseOrder)
            <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-blue-800">
                        Creando remito a partir de la Orden de Compra
                        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="font-semibold underline hover:text-blue-900">{{ $purchaseOrder->number }}</a>
                        — Proveedor: {{ $purchaseOrder->supplier->name }}
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

        <form method="POST" action="{{ route('delivery-notes.store') }}" @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()">
            @csrf

            <!-- Datos Generales -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Remito</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N° Remito <span class="text-red-500">*</span></label>
                        <input type="text" name="remito_number" value="{{ old('remito_number') }}" required
                               placeholder="Ej: 0001-00012345"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <select name="supplier_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}"
                                    {{ old('supplier_id', $purchaseOrder?->supplier_id) == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    @if(!$purchaseOrder)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Orden de Compra</label>
                            <select name="purchase_order_id" x-model="selectedPO" @change="loadPOItems()"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                <option value="">Sin OC vinculada</option>
                                @foreach($purchaseOrders as $po)
                                    <option value="{{ $po->id }}" {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>
                                        {{ $po->number }} — {{ $po->supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
                    @endif
                    <div class="{{ $purchaseOrder ? 'md:col-span-3' : 'md:col-span-2' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Observaciones opcionales">{{ old('notes') }}</textarea>
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
                                            {{-- Mismo criterio que admisión lab (prácticas): buscador a ancho completo; chip solo tras elegir, sin flex junto al input --}}
                                            <div class="relative min-w-0 w-full max-w-[280px]">
                                                <div x-show="!item.supply_id" class="relative w-full" x-cloak>
                                                    <input type="text"
                                                           x-model="searchText"
                                                           @input.debounce.300ms="doSearch()"
                                                           @focus="if (searchText.length >= 2) showResults = true"
                                                           @keydown.arrow-down.prevent="highlightNext()"
                                                           @keydown.arrow-up.prevent="highlightPrev()"
                                                           @keydown.enter.prevent="selectHighlighted()"
                                                           @keydown.escape="showResults = false"
                                                           @keydown.tab="onTab($event)"
                                                           placeholder="Buscar insumo… (Enter para elegir)"
                                                           required
                                                           :id="'item-supply-' + index"
                                                           autocomplete="off"
                                                           class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                                    <div x-show="showResults && (results.length > 0 || (searchText.length >= 2 && !loading))" x-cloak
                                                         @click.outside="showResults = false"
                                                         class="absolute z-[60] left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
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
                                                <div x-show="item.supply_id" x-cloak class="flex items-center min-h-[2.25rem]">
                                                    <span class="inline-flex items-center gap-2 rounded-md bg-teal-50 border border-teal-100 px-2.5 py-1.5 text-sm max-w-full">
                                                        <span class="font-mono text-xs text-teal-800 shrink-0" x-text="item._supply_code"></span>
                                                        <span class="text-gray-800 truncate" x-text="item._supply_label || item._supply_name"></span>
                                                        <button type="button" @click="unlinkSupply()" class="shrink-0 text-gray-400 hover:text-red-600 text-lg leading-none px-0.5" title="Cambiar insumo">&times;</button>
                                                    </span>
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
                                               @keydown.enter.prevent="addFreeItemFromRowEnter()"
                                               class="w-24 rounded border-gray-300 text-sm text-center focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" :name="'items[' + index + '][lot_number]'"
                                               x-model="item.lot_number"
                                               @keydown.enter.prevent
                                               placeholder="—"
                                               :disabled="!item._tracks_lot"
                                               :class="item._tracks_lot ? '' : 'bg-gray-50 text-gray-300'"
                                               class="w-24 rounded border-gray-300 text-xs focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="date" :name="'items[' + index + '][expiration_date]'"
                                               x-model="item.expiration_date"
                                               :disabled="!item._tracks_lot"
                                               :class="item._tracks_lot ? '' : 'bg-gray-50 text-gray-300'"
                                               class="w-32 rounded border-gray-300 text-xs focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" :name="'items[' + index + '][notes]'" x-model="item.notes"
                                               @keydown.enter.prevent="addFreeItemFromRowEnter()"
                                               placeholder="—"
                                               class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600"
                                                :class="{ 'opacity-30 pointer-events-none': item.from_po && !allowRemovePO }">
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
                    @if($purchaseOrder)
                        No hay ítems pendientes en la orden de compra seleccionada
                    @else
                        Seleccioná una orden de compra o agregá ítems libres
                    @endif
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="items.length === 0"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Guardar Remito
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
                        <input type="checkbox" x-model="newSupply.tracks_lot" id="newSupplyTracksLot"
                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        <label for="newSupplyTracksLot" class="text-sm text-gray-700">¿Controla lote/vencimiento?</label>
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
        $purchaseOrderDataJson = $purchaseOrder
            ? $purchaseOrder->items->map(function ($item) {
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
            : collect();
        $poItemsMapJson = $purchaseOrders->mapWithKeys(function ($po) {
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
                    this.searchText = label;
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
                    this.showResults = false;
                    this.results = [];
                    this.highlightedIndex = -1;
                },

                highlightNext() {
                    if (this.highlightedIndex < this.results.length - 1) this.highlightedIndex++;
                },

                highlightPrev() {
                    if (this.highlightedIndex > 0) this.highlightedIndex--;
                },

                selectHighlighted() {
                    if (!this.showResults || this.results.length === 0) {
                        return;
                    }
                    let idx = this.highlightedIndex;
                    if (idx < 0 || idx >= this.results.length) {
                        idx = 0;
                    }
                    this.selectSupply(this.results[idx]);
                },

                onTab(event) {
                    if (this.showResults && this.results.length > 0) {
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

        function deliveryForm() {
            const purchaseOrderData = @json($purchaseOrderDataJson);

            const purchaseOrdersItemsMap = @json($poItemsMapJson);

            return {
                selectedPO: '{{ old("purchase_order_id", $purchaseOrder?->id ?? "") }}',
                items: purchaseOrderData.length > 0 ? [...purchaseOrderData] : [],
                allowRemovePO: false,

                get hasPOItems() {
                    return this.items.some(i => i.from_po);
                },

                loadPOItems() {
                    this.items = this.items.filter(i => !i.from_po);

                    if (this.selectedPO && purchaseOrdersItemsMap[this.selectedPO]) {
                        const poItems = purchaseOrdersItemsMap[this.selectedPO].map(i => ({...i}));
                        this.items = [...poItems, ...this.items];
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

                addFreeItemFromRowEnter() {
                    this.addFreeItem();
                    this.$nextTick(() => {
                        const newIndex = this.items.length - 1;
                        const el = document.getElementById(`item-supply-${newIndex}`);
                        if (el) {
                            el.focus();
                        }
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
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
</x-admin-layout>
