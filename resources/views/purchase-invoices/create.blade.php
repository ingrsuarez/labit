<x-admin-layout>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <div class="p-4 md:p-6" x-data="invoiceForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nueva Factura de Compra</h1>
                <p class="text-gray-500 text-sm mt-1">Registrar factura de proveedor</p>
            </div>
            <a href="{{ route('purchase-invoices.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
        </div>

        @if($deliveryNote)
            <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-blue-800">
                        Generando factura a partir del Remito
                        <a href="{{ route('delivery-notes.show', $deliveryNote) }}" class="font-semibold underline hover:text-blue-900">{{ $deliveryNote->remito_number }}</a>
                        — Proveedor: {{ $deliveryNote->supplier->name }}
                    </p>
                </div>
            </div>
        @endif

        @if($purchaseOrder)
            <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-blue-800">
                        Vinculada a la Orden de Compra
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

        <form method="POST" action="{{ route('purchase-invoices.store') }}" @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()">
            @csrf

            @if($purchaseOrder)
                <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
            @endif
            <template x-for="dnId in deliveryNoteIds" :key="dnId">
                <input type="hidden" name="delivery_note_ids[]" :value="dnId">
            </template>

            <input type="hidden" name="cae" :value="qrData?.codAut || ''">
            <input type="hidden" name="cuit_emisor" :value="qrData?.cuit || ''">
            <input type="hidden" name="qr_data" :value="qrData ? JSON.stringify(qrData) : ''">

            {{-- Escáner QR AFIP --}}
            @unless($deliveryNote)
            <div class="mb-5 bg-gray-50 border border-gray-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-gray-700 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        Escanear QR de factura recibida
                    </h3>
                    <span class="text-xs text-gray-400">(opcional)</span>
                </div>

                <div class="flex gap-3 mb-3">
                    <label class="inline-flex items-center px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Escanear QR
                        <input type="file" accept="image/*" capture="environment" @change="scanFromFile($event)" class="hidden">
                    </label>
                </div>

                <div id="qr-reader-file" style="display:none"></div>

                <div x-show="scanResult" x-cloak class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm">
                    <div class="flex items-center gap-2 text-green-700 font-medium mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        QR leído correctamente
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-gray-600">
                        <div>CUIT: <span class="font-medium" x-text="scanResult?.cuit"></span></div>
                        <div>Tipo: <span class="font-medium" x-text="scanResult?.tipoCmpLabel"></span></div>
                        <div>PV-Nro: <span class="font-medium" x-text="scanResult?.ptoVta + '-' + scanResult?.nroCmp"></span></div>
                        <div>Total: <span class="font-medium" x-text="'$' + scanResult?.importe"></span></div>
                        <div>Fecha: <span class="font-medium" x-text="scanResult?.fecha"></span></div>
                        <div>CAE: <span class="font-medium" x-text="scanResult?.codAut"></span></div>
                    </div>
                    <button type="button" @click="applyToForm()"
                            class="mt-3 inline-flex items-center px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        Aplicar al formulario
                    </button>
                </div>

                <div x-show="scanError" x-cloak class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    <span x-text="scanError"></span>
                </div>
            </div>
            @endunless

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Comprobante</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Comprobante <span class="text-red-500">*</span></label>
                        <select name="voucher_type" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            <option value="A" {{ old('voucher_type') === 'A' ? 'selected' : '' }}>Factura A</option>
                            <option value="B" {{ old('voucher_type') === 'B' ? 'selected' : '' }}>Factura B</option>
                            <option value="C" {{ old('voucher_type') === 'C' ? 'selected' : '' }}>Factura C</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Punto de Venta</label>
                        <input type="text" name="point_of_sale" x-model="point_of_sale"
                               @blur="padPointOfSale(); checkDuplicate()"
                               maxlength="5"
                               placeholder="00000"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N° Factura <span class="text-red-500">*</span></label>
                        <input type="text" name="invoice_number" x-model="invoice_number"
                               @blur="padInvoiceNumber(); checkDuplicate()"
                               maxlength="8" required
                               placeholder="00000000"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-2">
                            <select name="supplier_id" x-model="supplier_id" @change="checkDuplicate(); onSupplierChange()" required class="flex-1 rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                <option value="">Seleccionar...</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" {{ old('supplier_id', $selectedSupplierId ?? $deliveryNote?->supplier_id ?? $purchaseOrder?->supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sede / depósito <span class="text-red-500">*</span></label>
                        <select name="lab_branch_id" x-model="lab_branch_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($branches as $br)
                                <option value="{{ $br->id }}">{{ $br->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Emisión <span class="text-red-500">*</span></label>
                        <input type="date" name="issue_date" x-model="issue_date"
                               @change="autoCalcDueDate()" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
                        <input type="date" name="due_date" x-model="due_date"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Percepciones</label>
                        <input type="number" name="percepciones" x-model.number="percepciones" value="{{ old('percepciones', 0) }}" min="0" step="0.01"
                               :tabindex="items.length ? (100 + items.length * 2) : undefined"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Otros Impuestos</label>
                        <input type="number" name="otros_impuestos" x-model.number="otrosImpuestos" value="{{ old('otros_impuestos', 0) }}" min="0" step="0.01"
                               :tabindex="items.length ? (101 + items.length * 2) : undefined"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Observaciones opcionales">{{ old('notes') }}</textarea>
                    </div>
                    <div x-show="duplicateWarning" x-cloak
                         class="lg:col-span-4 md:col-span-2 flex items-center gap-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Ya existe una factura de este proveedor con el mismo Punto de Venta y N° de Factura.</span>
                    </div>
                </div>
            </div>

            {{-- Remitos asociados (varios); también si se abrió desde un remito se pueden agregar más --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Remitos asociados <span class="text-gray-400 font-normal">(opcional)</span></h3>
                <p class="text-xs text-gray-500 mb-3">Podés vincular varios remitos. Cada uno agrega líneas nuevas a la grilla (sin fusionar cantidades). Los remitos asociados ya actualizaron el stock: los ítems no vuelven a impactar depósito.</p>
                <div class="flex flex-wrap gap-2 mb-3" x-show="deliveryNoteIds.length > 0" x-cloak>
                    <template x-for="dnId in deliveryNoteIds" :key="dnId">
                        <span class="inline-flex items-center gap-2 pl-3 pr-2 py-1.5 rounded-lg bg-blue-50 border border-blue-200 text-sm text-blue-900">
                            <span x-text="deliveryNoteLabels[dnId] || ('#' + dnId)"></span>
                            <button type="button" @click="removeDeliveryNoteById(dnId)" class="text-blue-500 hover:text-blue-800 p-0.5" title="Quitar remito (las líneas importadas no se borran solas)">&times;</button>
                        </span>
                    </template>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Agregar remito</label>
                        <select x-model="selectedDeliveryNoteId"
                                @change="onSelectDeliveryNoteToAdd()"
                                :disabled="!supplier_id"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="" x-text="supplier_id ? 'Elegir remito…' : 'Seleccioná un proveedor primero'"></option>
                            <template x-for="note in notesAvailableToAdd()" :key="note.id">
                                <option :value="note.id" x-text="`${note.remito_number} — ${note.date}`"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Ítems de la Factura</h2>
                    <button type="button" @click="addItem()"
                            class="inline-flex items-center px-3 py-1.5 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar Ítem
                    </button>
                </div>

                <div x-show="items.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descripción / Insumo / Servicio</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Cantidad</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Precio Unit.</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Tasa IVA</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">IVA</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Total</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Stock</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2" x-data="itemSearch(item, index, purchaseServiceGroups)">
                                        <input type="hidden" :name="'items[' + index + '][description]'" :value="item.description">
                                        <input type="hidden" :name="'items[' + index + '][supply_id]'" :value="item.supply_id || ''">
                                        <input type="hidden" :name="'items[' + index + '][purchase_service_id]'" :value="item.purchase_service_id || ''">
                                        <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                                        <input type="hidden" :name="'items[' + index + '][unit_price]'" :value="item.unit_price">
                                        <input type="hidden" :name="'items[' + index + '][iva_rate]'" :value="item.iva_rate">
                                        <input type="hidden" :name="'items[' + index + '][lot_number]'" :value="item.lot_number || ''">
                                        <input type="hidden" :name="'items[' + index + '][expiration_date]'" :value="item.expiration_date || ''">
                                        <input type="hidden" :name="'items[' + index + '][updates_stock]'" :value="item.updates_stock ? '1' : '0'">

                                        <div class="flex items-start gap-2">
                                            <div class="flex-1 min-w-0 relative">
                                                <div class="flex items-center gap-1">
                                                    <input type="text"
                                                           x-show="!item.supply_id && !item.purchase_service_id"
                                                           x-model="searchText"
                                                           @input.debounce.300ms="doSearch()"
                                                           @focus="if (searchText.length >= 2) showResults = true"
                                                           @keydown.arrow-down.prevent="highlightNext()"
                                                           @keydown.arrow-up.prevent="highlightPrev()"
                                                           @keydown.enter.prevent="selectHighlighted()"
                                                           @keydown.escape="showResults = false"
                                                           @keydown.tab="onTab($event)"
                                                           placeholder="Buscar insumo o servicio..."
                                                           :required="!item.supply_id && !item.purchase_service_id"
                                                           :id="'item-desc-' + index"
                                                           class="flex-1 min-w-0 rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">

                                                    {{-- Badge insumo --}}
                                                    <span x-show="item.supply_id" x-cloak
                                                          class="shrink-0 inline-flex items-center gap-2 mt-0.5">
                                                        <span class="text-xs text-teal-700 font-mono" x-text="item._supply_code"></span>
                                                        <span class="text-xs text-gray-500" x-text="item._supply_label || item.description"></span>
                                                        <button type="button" @click="unlinkItem()" class="text-xs text-gray-400 hover:text-red-500">&times;</button>
                                                    </span>

                                                    {{-- Badge servicio --}}
                                                    <span x-show="item.purchase_service_id && !item.supply_id" x-cloak
                                                          class="shrink-0 inline-flex items-center gap-2 px-2 py-0.5 rounded bg-indigo-50 border border-indigo-200">
                                                        <span class="text-xs font-mono text-indigo-700" x-text="item._service_label || item.description"></span>
                                                        <button type="button" @click="unlinkItem()" class="text-xs text-indigo-300 hover:text-red-500">&times;</button>
                                                    </span>
                                                </div>

                                                <div x-show="showResults && (results.length > 0 || serviceResults.length > 0 || (searchText.length >= 2 && !loading))"
                                                     x-cloak
                                                     @click.outside="showResults = false"
                                                     class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-72 overflow-y-auto">

                                                    {{-- Sección INSUMOS --}}
                                                    <template x-if="results.length > 0">
                                                        <div>
                                                            <div class="px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400 bg-gray-50 border-b border-gray-100">
                                                                Insumos
                                                            </div>
                                                            <template x-for="(supply, i) in results.slice(0, 6)" :key="'s-' + supply.id">
                                                                <button type="button"
                                                                        @click="selectSupply(supply)"
                                                                        @mouseenter="highlightedIndex = i"
                                                                        :class="highlightedIndex === i ? 'bg-teal-50 text-teal-800' : 'text-gray-700'"
                                                                        class="w-full text-left px-3 py-2 text-sm hover:bg-teal-50 flex items-center gap-2 border-b border-gray-50 last:border-0">
                                                                    <span class="font-mono text-xs text-teal-700" x-text="supply.code"></span>
                                                                    <span x-text="supply.name"></span>
                                                                    <template x-if="supply.brand">
                                                                        <span class="text-xs text-gray-400" x-text="'— ' + supply.brand"></span>
                                                                    </template>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </template>

                                                    {{-- Sección SERVICIOS --}}
                                                    <template x-if="serviceResults.length > 0">
                                                        <div>
                                                            <div class="px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400 bg-gray-50 border-b border-gray-100"
                                                                 :class="results.length > 0 ? 'border-t border-gray-200' : ''">
                                                                Servicios
                                                            </div>
                                                            <template x-for="(svc, i) in serviceResults.slice(0, 6)" :key="'svc-' + svc.id">
                                                                <button type="button"
                                                                        @click="selectService(svc)"
                                                                        class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 flex items-center gap-2 border-b border-gray-50 last:border-0">
                                                                    <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-200 rounded px-1">SRV</span>
                                                                    <span class="text-gray-800" x-text="svc.name"></span>
                                                                    <span class="text-xs text-gray-400" x-text="svc._group_name"></span>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </template>

                                                    {{-- Sin resultados --}}
                                                    <div x-show="results.length === 0 && serviceResults.length === 0 && searchText.length >= 2 && !loading"
                                                         class="px-3 py-2 text-sm text-gray-400">
                                                        Sin resultados. Podés escribir una descripción libre o crear un insumo nuevo.
                                                    </div>

                                                    {{-- Crear insumo --}}
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

                                            <template x-if="item.supply_id && getSupplyTracksLot(item.supply_id)">
                                                <div class="flex gap-2 shrink-0">
                                                    <input type="text" x-model="item.lot_number" placeholder="Lote"
                                                           @keydown.enter.prevent
                                                           class="w-28 rounded border-gray-300 text-xs focus:border-zinc-500 focus:ring-zinc-500">
                                                    <input type="date" x-model="item.expiration_date"
                                                           class="w-36 rounded border-gray-300 text-xs focus:border-zinc-500 focus:ring-zinc-500">
                                                </div>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="item.quantity" min="1" step="1" required
                                               :id="'item-qty-' + index"
                                               :tabindex="100 + index * 2"
                                               class="w-24 rounded border-gray-300 text-sm text-center focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="item.unit_price" min="0" step="0.01" required
                                               :id="'item-unit-' + index"
                                               :tabindex="101 + index * 2"
                                               class="w-32 rounded border-gray-300 text-sm text-right focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <select x-model="item.iva_rate" required
                                                class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                            <option value="0">0%</option>
                                            <option value="10.5">10,5%</option>
                                            <option value="21">21%</option>
                                            <option value="27">27%</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-700">
                                        <span x-text="'$' + formatMoney(itemIva(item))"></span>
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-medium text-gray-800">
                                        <span x-text="'$' + formatMoney(itemTotal(item))"></span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <template x-if="!hasLinkedDeliveryNotes()">
                                            <input type="checkbox"
                                                   x-model="item.updates_stock"
                                                   class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                   title="Marcar si este ítem actualiza el stock al guardar">
                                        </template>
                                        <template x-if="hasLinkedDeliveryNotes()">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-600 border border-blue-200"
                                                  title="Los remitos asociados ya actualizaron el stock">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                                </svg>
                                            </span>
                                        </template>
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
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Subtotal (Neto Gravado)</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(subtotal)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva105 > 0">
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 10,5%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva105)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva21 > 0">
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 21%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva21)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva27 > 0">
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 27%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva27)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="percepciones > 0">
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Percepciones</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(percepciones)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="otrosImpuestos > 0">
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Otros Impuestos</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(otrosImpuestos)"></td>
                                <td></td>
                            </tr>
                            <tr class="border-t-2 border-gray-300">
                                <td colspan="6" class="px-3 py-3 text-right text-sm font-bold text-gray-800">TOTAL</td>
                                <td class="px-3 py-3 text-right text-base font-bold text-gray-900" x-text="'$' + formatMoney(grandTotal)"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div x-show="items.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Agregá ítems a la factura usando el botón de arriba
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="items.length === 0"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Guardar Factura
                </button>
            </div>
        </form>

        {{-- Modal crear insumo --}}
        <div x-show="showNewSupplyModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             @keydown.escape.window="showNewSupplyModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6" @click.outside="showNewSupplyModal = false">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Crear Insumo Nuevo</h3>
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock mínimo</label>
                        <input type="number" x-model.number="newSupply.min_stock" min="0" step="1"
                               class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
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
        $deliveryNoteItemsJson = $deliveryNote
            ? $deliveryNote->items->map(function ($i) {
                return [
                    'description' => $i->supply ? $i->supply->name : ('Ítem remito - Cant: ' . $i->quantity_received),
                    'supply_id' => $i->supply_id,
                    'purchase_service_id' => '',
                    'quantity' => floatval($i->quantity_received),
                    'unit_price' => $i->supply ? floatval($i->supply->last_price ?? 0) : 0,
                    'iva_rate' => '21',
                ];
            })->values()
            : collect();
    @endphp
    <script>
        function itemSearch(item, index, serviceGroups) {
            return {
                searchText: '',
                results: [],
                serviceResults: [],
                showResults: false,
                loading: false,
                highlightedIndex: -1,

                async doSearch() {
                    item.description = this.searchText;
                    const q = this.searchText.trim().toLowerCase();
                    if (q.length < 2) {
                        this.results = [];
                        this.serviceResults = [];
                        this.showResults = false;
                        return;
                    }
                    this.serviceResults = [];
                    for (const g of serviceGroups) {
                        for (const s of g.services) {
                            const haystack = ((s.code || '') + ' ' + s.name + ' ' + g.name).toLowerCase();
                            if (haystack.includes(q)) {
                                this.serviceResults.push({ ...s, _group_name: g.name });
                            }
                        }
                        if (this.serviceResults.length >= 6) break;
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

                    item.purchase_service_id = '';
                    item.supply_id = supply.id;
                    item.description = label;
                    item._supply_code = supply.code;
                    item._supply_label = label;
                    item._service_label = '';
                    this.searchText = '';
                    this.showResults = false;

                    const formEl = this.$root.closest('[x-data="invoiceForm()"]');
                    if (formEl && formEl._x_dataStack) {
                        const parentData = formEl._x_dataStack[0];
                        if (parentData && !parentData.supplies.find(s => s.id === supply.id)) {
                            parentData.supplies.push({ id: supply.id, name: supply.code + ' - ' + supply.name, tracks_lot: supply.tracks_lot });
                        }
                    }

                    if (supply.tracks_lot && !item.expiration_date) {
                        const issueDateEl = document.querySelector('input[name="issue_date"]');
                        if (issueDateEl && issueDateEl.value) {
                            const d = new Date(issueDateEl.value);
                            d.setDate(d.getDate() + 30);
                            item.expiration_date = d.toISOString().split('T')[0];
                        }
                    }

                    this.$nextTick(() => {
                        const qtyInput = document.querySelector(`#item-qty-${index}`);
                        if (qtyInput) qtyInput.focus();
                    });
                },

                selectService(svc) {
                    item.purchase_service_id = String(svc.id);
                    item.supply_id = '';
                    item._supply_code = '';
                    item._supply_label = '';
                    item._service_label = (svc.code ? svc.code + ' — ' : '') + svc.name;
                    item.description = item._service_label;
                    item.lot_number = '';
                    item.expiration_date = '';
                    item.updates_stock = false;
                    this.searchText = '';
                    this.showResults = false;
                    this.$nextTick(() => {
                        const qtyInput = document.querySelector(`#item-qty-${index}`);
                        if (qtyInput) qtyInput.focus();
                    });
                },

                unlinkItem() {
                    item.supply_id = '';
                    item.purchase_service_id = '';
                    item._supply_code = '';
                    item._supply_label = '';
                    item._service_label = '';
                    item.description = '';
                    item.lot_number = '';
                    item.expiration_date = '';
                    this.searchText = '';
                    this.$nextTick(() => {
                        const el = document.querySelector(`#item-desc-${index}`);
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

                onTab(event) {
                    if (this.showResults && this.highlightedIndex >= 0) {
                        event.preventDefault();
                        this.selectHighlighted();
                    } else {
                        this.showResults = false;
                        item.description = this.searchText;
                    }
                },

                openCreateModal() {
                    this.showResults = false;
                    const formEl = this.$root.closest('[x-data="invoiceForm()"]');
                    if (formEl && formEl._x_dataStack) {
                        const parentData = formEl._x_dataStack[0];
                        parentData.newSupply.name = this.searchText;
                        parentData.newSupplyForRow = index;
                        parentData.showNewSupplyModal = true;
                    }
                },

                init() {
                    if (item.supply_id && (item._supply_label || item.description)) {
                        this.searchText = '';
                    } else if (item.purchase_service_id && item.description) {
                        item._service_label = item.description;
                        this.searchText = '';
                    }
                }
            }
        }

        function invoiceForm() {
            const deliveryNoteItems = @json($deliveryNoteItemsJson);
            const initialDnIds = @json($deliveryNote ? [$deliveryNote->id] : []);
            const initialDnLabels = @json($deliveryNote ? [ (string) $deliveryNote->id => $deliveryNote->remito_number ] : []);

            return {
                items: deliveryNoteItems.length > 0 ? [...deliveryNoteItems.map(i => ({...i, lot_number: '', expiration_date: '', _supply_code: '', updates_stock: false}))] : [],
                percepciones: {{ old('percepciones', 0) }},
                otrosImpuestos: {{ old('otros_impuestos', 0) }},
                purchaseServiceGroups: @json($purchaseServiceCatalog ?? []),
                supplies: @json(\App\Models\Supply::active()->orderBy('name')->get()->map(function ($s) { return ['id' => $s->id, 'name' => $s->code . ' - ' . $s->name, 'tracks_lot' => $s->tracks_lot]; })),

                point_of_sale: '{{ old('point_of_sale') }}',
                invoice_number: '{{ old('invoice_number') }}',
                supplier_id: '{{ old('supplier_id', $selectedSupplierId ?? $deliveryNote?->supplier_id ?? $purchaseOrder?->supplier_id ?? '') }}',
                lab_branch_id: '{{ old('lab_branch_id', $deliveryNote?->lab_branch_id ?? $purchaseOrder?->lab_branch_id ?? '') }}',
                issue_date: '{{ old('issue_date', date('Y-m-d')) }}',
                due_date: '{{ old('due_date') }}',
                editId: null,
                duplicateWarning: false,

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

                deliveryNoteIds: initialDnIds,
                deliveryNoteLabels: initialDnLabels,
                selectedDeliveryNoteId: '',
                availableDeliveryNotes: [],
                loadingDeliveryNotes: false,

                hasLinkedDeliveryNotes() {
                    return this.deliveryNoteIds.length > 0;
                },

                notesAvailableToAdd() {
                    return this.availableDeliveryNotes.filter(n => !this.deliveryNoteIds.includes(n.id));
                },

                syncBranchFromLinkedRemitos() {
                    const ids = this.deliveryNoteIds;
                    if (!ids.length) return;
                    const branches = ids.map(id => {
                        const n = this.availableDeliveryNotes.find(x => Number(x.id) === Number(id));
                        return n && n.lab_branch_id != null ? Number(n.lab_branch_id) : null;
                    }).filter(b => b != null);
                    const uniq = [...new Set(branches)];
                    if (uniq.length === 1) {
                        this.lab_branch_id = String(uniq[0]);
                    }
                },

                async fetchAvailableDeliveryNotes() {
                    if (!this.supplier_id) {
                        this.availableDeliveryNotes = [];
                        return;
                    }
                    this.loadingDeliveryNotes = true;
                    try {
                        const params = new URLSearchParams({ supplier_id: this.supplier_id });
                        const resp = await fetch(`{{ route('purchase-invoices.available-delivery-notes') }}?${params}`);
                        this.availableDeliveryNotes = await resp.json();
                    } catch (e) {
                        this.availableDeliveryNotes = [];
                    } finally {
                        this.loadingDeliveryNotes = false;
                        this.syncBranchFromLinkedRemitos();
                    }
                },

                async onSupplierChange() {
                    this.deliveryNoteIds = [];
                    this.deliveryNoteLabels = {};
                    this.selectedDeliveryNoteId = '';
                    this.availableDeliveryNotes = [];
                    if (!this.supplier_id) return;
                    await this.fetchAvailableDeliveryNotes();
                },

                init() {
                    if (this.supplier_id) {
                        this.fetchAvailableDeliveryNotes();
                    }
                },

                async onSelectDeliveryNoteToAdd() {
                    const id = this.selectedDeliveryNoteId ? parseInt(this.selectedDeliveryNoteId, 10) : null;
                    this.selectedDeliveryNoteId = '';
                    if (!id) return;
                    if (this.deliveryNoteIds.includes(id)) return;

                    try {
                        const resp = await fetch(`/delivery-notes/${id}/items`);
                        const data = await resp.json();

                        this.deliveryNoteIds.push(id);
                        this.deliveryNoteLabels[id] = data.delivery_note.number || data.delivery_note.remito_number || ('#' + id);

                        const newRows = data.items.map(item => ({
                            ...item,
                            purchase_service_id: item.purchase_service_id || '',
                            iva_rate: '21',
                            updates_stock: false,
                        }));
                        this.items = [...this.items, ...newRows];
                        this.syncBranchFromLinkedRemitos();
                    } catch (e) {
                        alert('Error al cargar los ítems del remito. Intentá de nuevo.');
                    }
                },

                removeDeliveryNoteById(dnId) {
                    this.deliveryNoteIds = this.deliveryNoteIds.filter(i => i !== dnId);
                    delete this.deliveryNoteLabels[dnId];
                    this.syncBranchFromLinkedRemitos();
                },

                padPointOfSale() {
                    if (this.point_of_sale) {
                        this.point_of_sale = String(parseInt(this.point_of_sale) || 0).padStart(5, '0');
                    }
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
                        this.supplier_id = String(data.id);
                        this.showNewSupplierModal = false;
                        await this.onSupplierChange();
                    } catch (e) {
                        this.newSupplierErrors = { name: ['Error de red. Intentá nuevamente.'] };
                    } finally {
                        this.newSupplierLoading = false;
                    }
                },

                padInvoiceNumber() {
                    if (this.invoice_number) {
                        this.invoice_number = String(parseInt(this.invoice_number) || 0).padStart(8, '0');
                    }
                },

                async checkDuplicate() {
                    if (!this.supplier_id || !this.point_of_sale || !this.invoice_number) {
                        this.duplicateWarning = false;
                        return;
                    }
                    try {
                        const params = new URLSearchParams({
                            supplier_id: this.supplier_id,
                            point_of_sale: this.point_of_sale,
                            invoice_number: this.invoice_number,
                            exclude_id: this.editId || '',
                        });
                        const resp = await fetch(`{{ route('purchase-invoices.check-duplicate') }}?${params}`);
                        const data = await resp.json();
                        this.duplicateWarning = data.duplicate;
                    } catch (e) {
                        this.duplicateWarning = false;
                    }
                },

                autoCalcDueDate() {
                    if (this.issue_date && !this.due_date) {
                        const issue = new Date(this.issue_date + 'T12:00:00');
                        issue.setDate(issue.getDate() + 30);
                        this.due_date = issue.toISOString().split('T')[0];
                    }
                },

                // QR Scanner
                scanResult: null,
                scanError: null,
                qrData: null,

                decodeAfipQr(decodedText) {
                    this.scanError = null;
                    try {
                        let base64Data;
                        if (decodedText.includes('afip.gob.ar/fe/qr')) {
                            const url = new URL(decodedText);
                            base64Data = url.searchParams.get('p');
                        } else {
                            base64Data = decodedText;
                        }
                        if (!base64Data) throw new Error('Sin datos');
                        const json = JSON.parse(atob(base64Data));
                        const tipoMap = {1:'A',2:'A',3:'A',6:'B',7:'B',8:'B',11:'C',12:'C',13:'C'};
                        const tipoCmpLabels = {1:'Factura A',2:'ND A',3:'NC A',6:'Factura B',7:'ND B',8:'NC B',11:'Factura C',12:'ND C',13:'NC C'};
                        this.scanResult = {
                            ...json,
                            tipoCmpLabel: tipoCmpLabels[json.tipoCmp] || ('Tipo ' + json.tipoCmp),
                            voucherType: tipoMap[json.tipoCmp] || 'A',
                            ptoVta: String(json.ptoVta).padStart(5, '0'),
                            nroCmp: String(json.nroCmp).padStart(8, '0'),
                        };
                        this.qrData = this.scanResult;
                    } catch (e) {
                        this.scanError = 'No se pudo leer el QR. Asegurate de que sea el QR de una factura electrónica AFIP.';
                        this.scanResult = null;
                    }
                },

                async scanFromFile(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    try {
                        const html5Qr = new Html5Qrcode('qr-reader-file');
                        const result = await html5Qr.scanFile(file, true);
                        this.decodeAfipQr(result);
                        html5Qr.clear();
                    } catch (e) {
                        this.scanError = 'No se encontró un QR válido en la imagen.';
                    }
                    event.target.value = '';
                },

                applyToForm() {
                    if (!this.scanResult) return;
                    const r = this.scanResult;
                    const setVal = (name, val) => {
                        const el = document.querySelector('[name="' + name + '"]');
                        if (el) { el.value = val; el.dispatchEvent(new Event('input', {bubbles:true})); el.dispatchEvent(new Event('change', {bubbles:true})); }
                    };
                    setVal('voucher_type', r.voucherType);
                    this.point_of_sale = r.ptoVta;
                    this.invoice_number = r.nroCmp;
                    if (r.fecha) { this.issue_date = r.fecha; this.autoCalcDueDate(); }
                    this.lookupSupplierByCuit(r.cuit);
                },

                async lookupSupplierByCuit(cuit) {
                    try {
                        const resp = await fetch('{{ url("suppliers/by-cuit") }}/' + cuit);
                        if (resp.ok) {
                            const supplier = await resp.json();
                            const el = document.querySelector('[name="supplier_id"]');
                            if (el && supplier.id) { el.value = supplier.id; el.dispatchEvent(new Event('change', {bubbles:true})); }
                        }
                    } catch (e) {}
                },

                showNewSupplyModal: false,
                newSupplyForRow: null,
                newSupplySaving: false,
                newSupplyError: '',
                newSupply: { name: '', supply_category_id: '', unit: 'unidad', brand: '', min_stock: 0, tracks_lot: false },

                addItem() {
                    this.items.push({ description: '', supply_id: '', purchase_service_id: '', quantity: 1, unit_price: 0, iva_rate: '21', lot_number: '', expiration_date: '', updates_stock: !this.hasLinkedDeliveryNotes() });
                },




                removeItem(index) {
                    this.items.splice(index, 1);
                },

                onSupplyChange(item) {
                    if (!this.getSupplyTracksLot(item.supply_id)) {
                        item.lot_number = '';
                        item.expiration_date = '';
                    }
                },

                getSupplyTracksLot(supplyId) {
                    if (!supplyId) return false;
                    const s = this.supplies.find(s => s.id == supplyId);
                    return s ? s.tracks_lot : false;
                },

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
                        this.supplies.push({ id: data.id, name: data.code + ' - ' + data.name, tracks_lot: data.tracks_lot });
                        if (this.newSupplyForRow !== null && this.items[this.newSupplyForRow]) {
                            this.items[this.newSupplyForRow].supply_id = data.id;
                            this.items[this.newSupplyForRow].description = data.name;
                            this.items[this.newSupplyForRow]._supply_code = data.code;
                            this.$nextTick(() => {
                                const descInput = document.querySelector(`#item-desc-${this.newSupplyForRow}`);
                                if (descInput) {
                                    descInput.value = data.name;
                                    descInput.dispatchEvent(new Event('input', { bubbles: true }));
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
                },

                itemIva(item) {
                    return Math.round(parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0) * parseFloat(item.iva_rate || 0) / 100 * 100) / 100;
                },

                itemTotal(item) {
                    const net = parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0);
                    return net + this.itemIva(item);
                },

                get subtotal() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0)), 0);
                },

                get iva105() {
                    return this.items.filter(i => parseFloat(i.iva_rate) === 10.5)
                        .reduce((sum, i) => sum + this.itemIva(i), 0);
                },

                get iva21() {
                    return this.items.filter(i => parseFloat(i.iva_rate) === 21)
                        .reduce((sum, i) => sum + this.itemIva(i), 0);
                },

                get iva27() {
                    return this.items.filter(i => parseFloat(i.iva_rate) === 27)
                        .reduce((sum, i) => sum + this.itemIva(i), 0);
                },

                get totalIva() {
                    return this.iva105 + this.iva21 + this.iva27;
                },

                get grandTotal() {
                    return this.subtotal + this.totalIva + parseFloat(this.percepciones || 0) + parseFloat(this.otrosImpuestos || 0);
                },

                formatMoney(val) {
                    return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
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
