<x-admin-layout>
    <div class="p-4 md:p-6" x-data="orderForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nueva Orden de Compra</h1>
                <p class="text-gray-500 text-sm mt-1">Crear una nueva orden de compra a proveedor</p>
            </div>
            <a href="{{ route('purchase-orders.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
        </div>

        @if($quotation)
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-700 text-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Generada desde Solicitud de Cotización <strong class="ml-1">{{ $quotation->number }}</strong>
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

        <form method="POST" action="{{ route('purchase-orders.store') }}">
            @csrf

            @if($quotation)
                <input type="hidden" name="quotation_request_id" value="{{ $quotation->id }}">
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos Generales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <select name="supplier_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ old('supplier_id', $quotation?->supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Entrega Esperada</label>
                        <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">IVA (%) <span class="text-red-500">*</span></label>
                        <input type="number" name="tax_rate" x-model.number="taxRate" value="{{ old('tax_rate', 21) }}" min="0" step="0.01" required
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
                    <h2 class="text-lg font-semibold text-gray-800">Ítems</h2>
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
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Insumo</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Cantidad</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Precio Unit.</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Subtotal</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2">
                                        <input type="hidden" :name="'items[' + index + '][supply_id]'" :value="item.supply_id">
                                        <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                                        <input type="hidden" :name="'items[' + index + '][unit_price]'" :value="item.unit_price">
                                        <input type="hidden" :name="'items[' + index + '][notes]'" :value="item.notes">
                                        <select x-model="item.supply_id" required
                                                class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                            <option value="">Seleccionar insumo...</option>
                                            <template x-for="supply in supplies" :key="supply.id">
                                                <option :value="supply.id" x-text="supply.name" :selected="supply.id == item.supply_id"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="item.quantity" min="0.01" step="0.01" required
                                               class="w-24 rounded border-gray-300 text-sm text-center focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="item.unit_price" min="0" step="0.01" required
                                               class="w-32 rounded border-gray-300 text-sm text-right focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-medium text-gray-800">
                                        <span x-text="'$' + formatMoney(item.quantity * item.unit_price)"></span>
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
                                <td colspan="3" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Subtotal</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(subtotal)"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right text-sm font-medium text-gray-600">
                                    IVA (<span x-text="taxRate"></span>%)
                                </td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(taxAmount)"></td>
                                <td></td>
                            </tr>
                            <tr class="border-t-2 border-gray-300">
                                <td colspan="3" class="px-3 py-3 text-right text-sm font-bold text-gray-800">Total</td>
                                <td class="px-3 py-3 text-right text-base font-bold text-gray-900" x-text="'$' + formatMoney(total)"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div x-show="items.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Agregá ítems a la orden de compra usando el botón de arriba
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="items.length === 0"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Guardar Orden
                </button>
            </div>
        </form>
    </div>

    @php
        $quotationItems = $quotation
            ? $quotation->items->map(function ($i) {
                return ['supply_id' => $i->supply_id, 'quantity' => $i->quantity, 'unit_price' => $i->unit_price ?? 0, 'notes' => $i->notes ?? ''];
            })->values()
            : [];
        $suppliesJson = $supplies->map(function ($s) {
            return ['id' => $s->id, 'name' => $s->code . ' - ' . $s->name, 'last_price' => $s->last_price];
        })->values();
    @endphp
    <script>
        function orderForm() {
            return {
                items: @json($quotationItems),
                taxRate: {{ old('tax_rate', 21) }},
                supplies: @json($suppliesJson),
                addItem() {
                    this.items.push({ supply_id: '', quantity: 1, unit_price: 0, notes: '' });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                get subtotal() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0)), 0);
                },
                get taxAmount() {
                    return Math.round(this.subtotal * this.taxRate / 100 * 100) / 100;
                },
                get total() {
                    return this.subtotal + this.taxAmount;
                },
                formatMoney(val) {
                    return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2 }).format(val);
                }
            }
        }
    </script>
</x-admin-layout>
