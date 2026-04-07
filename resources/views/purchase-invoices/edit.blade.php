<x-admin-layout>
    <div class="p-4 md:p-6" x-data="invoiceEditForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar {{ $invoice->full_number }}</h1>
                <p class="text-gray-500 text-sm mt-1">Modificar factura de compra</p>
            </div>
            <a href="{{ route('purchase-invoices.show', $invoice) }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
        </div>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('purchase-invoices.update', $invoice) }}" @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()">
            @csrf
            @method('PUT')

            @if($invoice->purchase_order_id)
                <input type="hidden" name="purchase_order_id" value="{{ $invoice->purchase_order_id }}">
            @endif
            <template x-for="dnId in deliveryNoteIds" :key="dnId">
                <input type="hidden" name="delivery_note_ids[]" :value="dnId">
            </template>

            {{-- Remitos asociados (varios) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Remitos asociados <span class="text-gray-400 font-normal">(opcional)</span></h3>
                <p class="text-xs text-gray-500 mb-3">Varios remitos suman líneas nuevas sin fusionar cantidades. Quitar un remito no borra las líneas ya cargadas. Los remitos asociados ya actualizaron el stock.</p>
                <div class="flex flex-wrap gap-2 mb-3" x-show="deliveryNoteIds.length > 0" x-cloak>
                    <template x-for="dnId in deliveryNoteIds" :key="dnId">
                        <span class="inline-flex items-center gap-2 pl-3 pr-2 py-1.5 rounded-lg bg-blue-50 border border-blue-200 text-sm text-blue-900">
                            <span x-text="deliveryNoteLabels[dnId] || ('#' + dnId)"></span>
                            <button type="button" @click="removeDeliveryNoteById(dnId)" class="text-blue-500 hover:text-blue-800 p-0.5" title="Quitar remito (las líneas no se eliminan solas)">&times;</button>
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
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Comprobante</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Empresa <span class="text-red-500">*</span></label>
                        <select name="company_id" required class="w-full max-w-md rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            @foreach($companies as $co)
                                <option value="{{ $co->id }}" {{ (string) old('company_id', $invoice->company_id) === (string) $co->id ? 'selected' : '' }}>{{ $co->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Define en qué empresa queda registrada la compra a efectos contables. No modifica stock ni remitos.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Comprobante <span class="text-red-500">*</span></label>
                        <select name="voucher_type" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="A" {{ old('voucher_type', $invoice->voucher_type) === 'A' ? 'selected' : '' }}>Factura A</option>
                            <option value="B" {{ old('voucher_type', $invoice->voucher_type) === 'B' ? 'selected' : '' }}>Factura B</option>
                            <option value="C" {{ old('voucher_type', $invoice->voucher_type) === 'C' ? 'selected' : '' }}>Factura C</option>
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
                        <select name="supplier_id" x-model="supplier_id" @change="checkDuplicate(); onSupplierChange()" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ old('supplier_id', $invoice->supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        </select>
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
                        <input type="number" name="percepciones" x-model.number="percepciones" value="{{ old('percepciones', $invoice->percepciones) }}" min="0" step="0.01"
                               :tabindex="items.length ? (100 + items.length * 2) : undefined"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Otros Impuestos</label>
                        <input type="number" name="otros_impuestos" x-model.number="otrosImpuestos" value="{{ old('otros_impuestos', $invoice->otros_impuestos) }}" min="0" step="0.01"
                               :tabindex="items.length ? (101 + items.length * 2) : undefined"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">{{ old('notes', $invoice->notes) }}</textarea>
                    </div>
                    <div x-show="duplicateWarning" x-cloak
                         class="lg:col-span-4 md:col-span-2 flex items-center gap-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Ya existe una factura de este proveedor con el mismo Punto de Venta y N° de Factura.</span>
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
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-52">Servicio</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descripción / Insumo</th>
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
                                    <td class="px-3 py-2 align-top w-52">
                                        <input type="hidden" :name="'items[' + index + '][purchase_service_id]'" :value="item.purchase_service_id || ''">
                                        <select @change="selectPurchaseService(item, index, $event.target.value)"
                                                class="w-full rounded-lg border-gray-300 text-xs focus:border-zinc-500 focus:ring-zinc-500">
                                            <option value="">— Ninguno —</option>
                                            <template x-for="g in purchaseServiceGroups" :key="g.id === null ? 'uncat' : g.id">
                                                <optgroup :label="g.name">
                                                    <template x-for="s in g.services" :key="s.id">
                                                        <option :value="s.id"
                                                                :selected="String(item.purchase_service_id || '') === String(s.id)"
                                                                x-text="(s.code ? s.code + ' · ' : '') + s.name"></option>
                                                    </template>
                                                </optgroup>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2" x-data="supplySearch(item, index)">
                                        <input type="hidden" :name="'items[' + index + '][description]'" :value="item.description">
                                        <input type="hidden" :name="'items[' + index + '][supply_id]'" :value="item.supply_id || ''">
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
                                                           x-model="searchText"
                                                           @input.debounce.300ms="doSearch()"
                                                           @focus="if (searchText.length >= 2) showResults = true"
                                                           @keydown.arrow-down.prevent="highlightNext()"
                                                           @keydown.arrow-up.prevent="highlightPrev()"
                                                           @keydown.enter.prevent="selectHighlighted()"
                                                           @keydown.escape="showResults = false"
                                                           @keydown.tab="onTab($event)"
                                                           placeholder="Buscar insumo o escribir descripción..."
                                                           required
                                                           :id="'item-desc-' + index"
                                                           class="flex-1 min-w-0 rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">

                                                    <span x-show="item.supply_id" x-cloak
                                                          class="shrink-0 inline-flex items-center gap-2 mt-0.5">
                                                        <span class="text-xs text-teal-700 font-mono" x-text="item._supply_code"></span>
                                                        <span class="text-xs text-gray-500" x-text="item._supply_label || item.description"></span>
                                                        <button type="button" @click="unlinkSupply()" class="text-xs text-gray-400 hover:text-red-500">&times;</button>
                                                    </span>
                                                </div>

                                                <div x-show="showResults && (results.length > 0 || searchText.length >= 2)" x-cloak
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
                                                        Sin resultados. Podés escribir una descripción libre.
                                                    </div>
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
                                <td colspan="7" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Subtotal (Neto Gravado)</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(subtotal)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva105 > 0">
                                <td colspan="7" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 10,5%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva105)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva21 > 0">
                                <td colspan="7" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 21%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva21)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva27 > 0">
                                <td colspan="7" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 27%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva27)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="percepciones > 0">
                                <td colspan="7" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Percepciones</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(percepciones)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="otrosImpuestos > 0">
                                <td colspan="7" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Otros Impuestos</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(otrosImpuestos)"></td>
                                <td></td>
                            </tr>
                            <tr class="border-t-2 border-gray-300">
                                <td colspan="7" class="px-3 py-3 text-right text-sm font-bold text-gray-800">TOTAL</td>
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

            <div class="flex justify-end gap-3">
                <a href="{{ route('purchase-invoices.show', $invoice) }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 text-sm">Cancelar</a>
                <button type="submit" :disabled="items.length === 0"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    @php
        $editDnIds = $invoice->deliveryNotes->pluck('id')->values()->all();
        $editDnLabels = $invoice->deliveryNotes->mapWithKeys(fn ($dn) => [(string) $dn->id => $dn->remito_number])->all();
        $invoiceItemsJson = $invoice->items->map(function ($i) {
            $brand = $i->supply?->brand ?? '';
            $name = $i->supply?->name ?? '';
            return [
                'description' => $i->description,
                'supply_id' => $i->supply_id,
                'purchase_service_id' => $i->purchase_service_id ? (string) $i->purchase_service_id : '',
                '_supply_code' => $i->supply ? $i->supply->code : '',
                '_supply_label' => $i->supply_id ? ($brand ? "{$name} — {$brand}" : $name) : '',
                'quantity' => floatval($i->quantity),
                'unit_price' => floatval($i->unit_price),
                'iva_rate' => strval(floatval($i->iva_rate)),
                'lot_number' => $i->lot_number ?? '',
                'expiration_date' => $i->expiration_date ?? '',
                'updates_stock' => (bool) $i->updates_stock,
            ];
        })->values();
        $suppliesEditJson = \App\Models\Supply::active()->orderBy('name')->get()->map(function ($s) {
            return ['id' => $s->id, 'name' => $s->code . ' - ' . $s->name, 'tracks_lot' => $s->tracks_lot];
        })->values();
    @endphp
    <script>
        function supplySearch(item, index) {
            return {
                searchText: item.description || '',
                results: [],
                showResults: false,
                loading: false,
                highlightedIndex: -1,

                async doSearch() {
                    item.description = this.searchText;
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

                    item.purchase_service_id = '';
                    item.supply_id = supply.id;
                    item.description = label;
                    item._supply_code = supply.code;
                    item._supply_label = label;
                    this.searchText = label;
                    this.showResults = false;

                    const formEl = this.$root.closest('[x-data="invoiceEditForm()"]');
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

                unlinkSupply() {
                    item.supply_id = '';
                    item._supply_code = '';
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

                init() {
                    if (item.supply_id && (item._supply_label || item.description)) {
                        this.searchText = item._supply_label || item.description;
                    }
                }
            }
        }

        function invoiceEditForm() {
            const initialDnIds = @json($editDnIds);
            const initialDnLabels = @json($editDnLabels);

            return {
                items: @json($invoiceItemsJson),
                percepciones: {{ old('percepciones', $invoice->percepciones) }},
                otrosImpuestos: {{ old('otros_impuestos', $invoice->otros_impuestos) }},
                purchaseServiceGroups: @json($purchaseServiceCatalog ?? []),
                supplies: @json($suppliesEditJson),

                point_of_sale: '{{ old('point_of_sale', $invoice->point_of_sale) }}',
                invoice_number: '{{ old('invoice_number', $invoice->invoice_number) }}',
                supplier_id: '{{ old('supplier_id', $invoice->supplier_id) }}',
                lab_branch_id: '{{ old('lab_branch_id', $invoice->lab_branch_id) }}',
                issue_date: '{{ old('issue_date', $invoice->issue_date->format('Y-m-d')) }}',
                due_date: '{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}',
                editId: {{ $invoice->id }},
                duplicateWarning: false,

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
                        const params = new URLSearchParams({
                            supplier_id: this.supplier_id,
                            purchase_invoice_id: this.editId || '',
                        });
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

                addItem() {
                    this.items.push({ description: '', supply_id: '', purchase_service_id: '', quantity: 1, unit_price: 0, iva_rate: '21', lot_number: '', expiration_date: '', _supply_code: '', updates_stock: !this.hasLinkedDeliveryNotes() });
                },

                selectPurchaseService(item, index, value) {
                    const id = value ? String(value) : '';
                    item.purchase_service_id = id;
                    if (! id) {
                        return;
                    }
                    let svc = null;
                    for (const g of this.purchaseServiceGroups) {
                        const found = g.services.find(s => String(s.id) === id);
                        if (found) {
                            svc = found;
                            break;
                        }
                    }
                    if (! svc) {
                        return;
                    }
                    item.supply_id = '';
                    item._supply_code = '';
                    item._supply_label = '';
                    item.lot_number = '';
                    item.expiration_date = '';
                    item.updates_stock = false;
                    const label = svc.code ? `${svc.code} — ${svc.name}` : svc.name;
                    item.description = label;
                    this.$nextTick(() => {
                        const input = document.querySelector(`#item-desc-${index}`);
                        if (input) {
                            input.value = label;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                getSupplyTracksLot(supplyId) {
                    if (!supplyId) return false;
                    const s = this.supplies.find(s => s.id == supplyId);
                    return s ? s.tracks_lot : false;
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
                },

                init() {
                    this.fetchAvailableDeliveryNotes();
                }
            }
        }
    </script>
</x-admin-layout>
