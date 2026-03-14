<x-admin-layout>
    <div class="p-4 md:p-6" x-data="invoiceForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nueva Factura de Venta</h1>
                <p class="text-gray-500 text-sm mt-1">Registrar factura de venta</p>
            </div>
            <a href="{{ route('sales-invoices.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
        </div>

        @if($quote)
            <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-blue-800">
                        Generando factura a partir del Presupuesto
                        <a href="{{ route('quotes.show', $quote) }}" class="font-semibold underline hover:text-blue-900">{{ $quote->quote_number }}</a>
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

        <form method="POST" action="{{ route('sales-invoices.store') }}">
            @csrf

            @if($quote)
                <input type="hidden" name="quote_id" value="{{ $quote->id }}">
            @endif
            @if(request('admission_id'))
                <input type="hidden" name="admission_id" value="{{ request('admission_id') }}">
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Comprobante</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
                     x-data="{
                         voucherType: '{{ old('voucher_type', '') }}',
                         pointOfSaleId: '{{ old('point_of_sale_id', '') }}',
                         invoiceNumber: '{{ old('invoice_number', '') }}',
                         loading: false,
                         isElectronic: false,
                         posData: @js($pointsOfSale->mapWithKeys(fn($p) => [$p->id => ['is_electronic' => $p->is_electronic, 'afip_pos' => $p->afip_pos_number]])),
                         fetchNextNumber() {
                             if (!this.voucherType || !this.pointOfSaleId) return;
                             this.isElectronic = this.posData[this.pointOfSaleId]?.is_electronic || false;
                             this.loading = true;
                             fetch(`{{ route('sales-invoices.next-number') }}?voucher_type=${this.voucherType}&point_of_sale_id=${this.pointOfSaleId}`)
                                 .then(r => r.json())
                                 .then(data => {
                                     if (data.next_number) this.invoiceNumber = data.next_number;
                                     this.loading = false;
                                 })
                                 .catch(() => this.loading = false);
                         }
                     }">

                    <template x-if="isElectronic">
                        <div class="lg:col-span-4 p-3 bg-indigo-50 border border-indigo-200 rounded-lg flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <p class="text-sm text-indigo-800 font-medium">Factura electrónica — El número de comprobante será asignado por AFIP al emitir</p>
                        </div>
                    </template>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Comprobante <span class="text-red-500">*</span></label>
                        <select name="voucher_type" x-model="voucherType" @change="fetchNextNumber()" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            <option value="A">Factura A</option>
                            <option value="B">Factura B</option>
                            <option value="C">Factura C</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Punto de Venta <span class="text-red-500">*</span></label>
                        <select name="point_of_sale_id" x-model="pointOfSaleId" @change="fetchNextNumber()" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($pointsOfSale as $pos)
                                <option value="{{ $pos->id }}">{{ $pos->code }} - {{ $pos->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N° Factura <span x-show="!isElectronic" class="text-red-500">*</span></label>
                        <input type="text" name="invoice_number" x-model="invoiceNumber" :required="!isElectronic"
                               :disabled="isElectronic" :placeholder="isElectronic ? 'Asignado por AFIP' : 'Ej: 00012345'"
                               :class="isElectronic ? 'bg-gray-100 cursor-not-allowed' : ''"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        <p x-show="loading" class="text-xs text-zinc-500 mt-1">Cargando número...</p>
                        <p x-show="isElectronic && !loading" class="text-xs text-indigo-500 mt-1">AFIP asigna el número al autorizar</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
                        <select name="customer_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" {{ old('customer_id', $selectedCustomerId ?? $quote?->customer_id) == $cust->id ? 'selected' : '' }}>{{ $cust->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Emisión <span class="text-red-500">*</span></label>
                        <input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
                        <input type="date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Percepciones</label>
                        <input type="number" name="percepciones" x-model.number="percepciones" value="{{ old('percepciones', 0) }}" min="0" step="0.01"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Otros Impuestos</label>
                        <input type="number" name="otros_impuestos" x-model.number="otrosImpuestos" value="{{ old('otros_impuestos', 0) }}" min="0" step="0.01"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Observaciones opcionales">{{ old('notes') }}</textarea>
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
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descripción</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Cantidad</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Precio Unit.</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Tasa IVA</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">IVA</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Total</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2">
                                        <input type="hidden" :name="'items[' + index + '][description]'" :value="item.description">
                                        <input type="hidden" :name="'items[' + index + '][test_id]'" :value="item.test_id || ''">
                                        <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                                        <input type="hidden" :name="'items[' + index + '][unit_price]'" :value="item.unit_price">
                                        <input type="hidden" :name="'items[' + index + '][iva_rate]'" :value="item.iva_rate">
                                        <input type="text" x-model="item.description" required placeholder="Descripción del ítem"
                                               class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="item.quantity" min="1" step="1" required
                                               class="w-24 rounded border-gray-300 text-sm text-center focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="item.unit_price" min="0" step="0.01" required
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
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Subtotal (Neto Gravado)</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(subtotal)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva105 > 0">
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 10,5%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva105)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva21 > 0">
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 21%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva21)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva27 > 0">
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 27%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva27)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="percepciones > 0">
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Percepciones</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(percepciones)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="otrosImpuestos > 0">
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Otros Impuestos</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(otrosImpuestos)"></td>
                                <td></td>
                            </tr>
                            <tr class="border-t-2 border-gray-300">
                                <td colspan="5" class="px-3 py-3 text-right text-sm font-bold text-gray-800">TOTAL</td>
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
                <button type="submit" :disabled="items.length === 0 || submitting"
                        @click="submitting = isElectronic"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm"
                        :class="isElectronic ? 'bg-indigo-600 hover:bg-indigo-700' : ''">
                    <span x-show="!submitting" x-text="isElectronic ? 'Emitir Factura Electrónica' : 'Guardar Factura'"></span>
                    <span x-show="submitting" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Autorizando en AFIP...
                    </span>
                </button>
            </div>
        </form>
    </div>

    @php
        $quoteItemsJson = $quote
            ? $quote->items->map(fn($i) => [
                'description' => $i->description,
                'test_id' => $i->test_id,
                'quantity' => floatval($i->quantity),
                'unit_price' => floatval($i->unit_price),
                'iva_rate' => '21',
            ])->values()
            : [];
    @endphp

    <script>
        function invoiceForm() {
            const quoteItems = @json($quoteItemsJson);

            return {
                items: quoteItems.length > 0 ? [...quoteItems] : [],
                percepciones: {{ old('percepciones', 0) }},
                otrosImpuestos: {{ old('otros_impuestos', 0) }},
                submitting: false,
                isElectronic: false,

                addItem() {
                    this.items.push({ description: '', test_id: '', quantity: 1, unit_price: 0, iva_rate: '21' });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
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
</x-admin-layout>
