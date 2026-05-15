<x-admin-layout>
    <div class="p-4 md:p-6" x-data="creditNoteManualForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nueva Nota de Crédito Manual</h1>
                <p class="text-gray-500 text-sm mt-1">Registrar nota de crédito emitida fuera del sistema</p>
            </div>
            <a href="{{ route('credit-notes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
        </div>

        <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-blue-800">
                    Esta nota de crédito no se enviará a AFIP. Para NC electrónicas, generarlas desde la factura de venta.
                </p>
            </div>
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

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('credit-notes.store-manual') }}">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Comprobante</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
                        <input type="hidden" name="customer_id" :value="customerSelectedId">
                        <div class="relative">
                            <input type="text"
                                   x-model="customerSearch"
                                   @focus="customerOpen = true"
                                   @click="customerOpen = true"
                                   @input="customerOpen = true; customerSelectedId = ''"
                                   :placeholder="customerSelectedName || 'Buscar cliente...'"
                                   :class="customerSelectedId ? 'bg-gray-50 text-gray-700 font-medium' : ''"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <button x-show="customerSelectedId" @click.prevent="customerSelectedId = ''; customerSelectedName = ''; customerSearch = ''; customerOpen = true" type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <div x-show="customerOpen && !customerSelectedId" @click.outside="customerOpen = false" x-cloak
                                 class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                <template x-for="c in filteredCustomers" :key="c.id">
                                    <button type="button" @click="customerSelectedId = c.id; customerSelectedName = c.name; customerSearch = c.name; customerOpen = false"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 flex justify-between items-center">
                                        <span x-text="c.name"></span>
                                        <span class="text-xs text-gray-400" x-text="c.taxId || ''"></span>
                                    </button>
                                </template>
                                <div x-show="filteredCustomers.length === 0" class="px-3 py-2 text-sm text-gray-400">Sin resultados</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                        <select name="voucher_type" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="A" {{ old('voucher_type') === 'A' ? 'selected' : '' }}>NC A</option>
                            <option value="B" {{ old('voucher_type', 'B') === 'B' ? 'selected' : '' }}>NC B</option>
                            <option value="C" {{ old('voucher_type') === 'C' ? 'selected' : '' }}>NC C</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Punto de Venta <span class="text-red-500">*</span></label>
                        <select name="point_of_sale_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            @foreach($pointsOfSale as $pos)
                                <option value="{{ $pos->id }}" {{ old('point_of_sale_id') == $pos->id ? 'selected' : '' }}>{{ $pos->code }} - {{ $pos->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número de NC <span class="text-red-500">*</span></label>
                        <input type="text" name="credit_note_number" value="{{ old('credit_note_number') }}" required placeholder="Ej: 00000012"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Emisión <span class="text-red-500">*</span></label>
                        <input type="date" name="issue_date" value="{{ old('issue_date', now()->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Motivo <span class="text-red-500">*</span></label>
                        <textarea name="reason" rows="2" required placeholder="Motivo de la nota de crédito..."
                                  class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">{{ old('reason') }}</textarea>
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
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Ítems de la Nota de Crédito</h2>
                    <button type="button" @click="addItem()"
                            class="inline-flex items-center px-3 py-1.5 bg-zinc-100 text-zinc-700 text-sm font-medium rounded-lg hover:bg-zinc-200 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar ítem
                    </button>
                </div>

                <div x-show="items.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descripción</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Cantidad</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">P. Unitario</th>
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
                                        <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                                        <input type="hidden" :name="'items[' + index + '][unit_price]'" :value="item.unit_price">
                                        <input type="hidden" :name="'items[' + index + '][iva_rate]'" :value="item.iva_rate">
                                        <input type="text" x-model="item.description" required placeholder="Descripción"
                                               class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="item.quantity" min="0.01" step="0.01" required
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
                    Agregá al menos un ítem para la nota de crédito
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="items.length === 0 || submitting"
                        class="px-6 py-3 bg-zinc-700 hover:bg-zinc-800 text-white font-semibold rounded-lg transition-colors text-sm shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!submitting">Registrar Nota de Crédito</span>
                    <span x-show="submitting" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Registrando...
                    </span>
                </button>
            </div>
        </form>
    </div>

    <script>
        function creditNoteManualForm() {
            return {
                customers: @json($customers),
                customerSearch: '',
                customerOpen: false,
                customerSelectedId: '{{ old('customer_id', '') }}',
                customerSelectedName: '',
                items: [],
                percepciones: {{ old('percepciones', 0) }},
                otrosImpuestos: {{ old('otros_impuestos', 0) }},
                submitting: false,

                init() {
                    if (this.customerSelectedId) {
                        const c = this.customers.find(c => c.id == this.customerSelectedId);
                        if (c) {
                            this.customerSelectedName = c.name;
                            this.customerSearch = c.name;
                        }
                    }
                },

                get filteredCustomers() {
                    if (!this.customerSearch || this.customerSearch.length < 1) return this.customers.slice(0, 50);
                    const term = this.customerSearch.toLowerCase();
                    return this.customers.filter(c =>
                        c.name.toLowerCase().includes(term) ||
                        (c.taxId && String(c.taxId).toLowerCase().includes(term))
                    ).slice(0, 50);
                },

                addItem() {
                    this.items.push({ description: '', quantity: 1, unit_price: 0, iva_rate: 21 });
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

                get grandTotal() {
                    return this.subtotal + this.iva105 + this.iva21 + this.iva27 + parseFloat(this.percepciones || 0) + parseFloat(this.otrosImpuestos || 0);
                },

                formatMoney(val) {
                    return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
                }
            }
        }
    </script>
</x-admin-layout>
