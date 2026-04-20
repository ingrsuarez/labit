<x-admin-layout>
    <div class="p-4 md:p-6" x-data="ncForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nueva nota de crédito de proveedor</h1>
                <p class="text-gray-500 text-sm mt-1">Registrá el NC recibido; podés aplicarlo a una factura con saldo o dejarlo sin aplicar.</p>
            </div>
            <a href="{{ route('purchase-credit-notes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
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

        <form method="POST" action="{{ route('purchase-credit-notes.store') }}" @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Comprobante</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                        <select name="voucher_type" x-model="voucher_type" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Punto de venta</label>
                        <input type="text" name="point_of_sale" x-model="point_of_sale" @blur="padPointOfSale(); checkDuplicate()"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" placeholder="00001">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número NC <span class="text-red-500">*</span></label>
                        <input type="text" name="credit_note_number" x-model="credit_note_number" required @blur="padCnNumber(); checkDuplicate()"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="issue_date" x-model="issue_date" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <select name="supplier_id" x-model="supplier_id" @change="onSupplierChange()" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sede / depósito <span class="text-red-500">*</span></label>
                        <select name="lab_branch_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Aplicar a factura de compra</label>
                        <select name="purchase_invoice_id" x-model="purchase_invoice_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">— Sin aplicar (solo cuenta corriente) —</option>
                            <template x-for="inv in purchaseInvoices" :key="inv.id">
                                <option :value="inv.id" x-text="inv.label"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Si elegís una factura, el saldo pendiente de esa FC se reduce hasta el importe de esta NC.</p>
                    </div>
                </div>
                <div x-show="duplicateWarning" x-cloak class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                    Ya existe una nota de crédito con el mismo proveedor, punto de venta y número.
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Ítems</h2>
                    <button type="button" @click="addItem()" class="text-sm text-zinc-700 font-medium hover:text-zinc-900">+ Agregar línea</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-semibold text-gray-500">Descripción</th>
                                <th class="px-2 py-2 text-right text-xs font-semibold text-gray-500 w-20">Cant.</th>
                                <th class="px-2 py-2 text-right text-xs font-semibold text-gray-500 w-28">P. unit.</th>
                                <th class="px-2 py-2 text-center text-xs font-semibold text-gray-500 w-24">IVA</th>
                                <th class="px-2 py-2 text-right text-xs font-semibold text-gray-500 w-28">Total</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-2 py-2">
                                        <input type="hidden" :name="'items['+index+'][supply_id]'" value="">
                                        <input type="hidden" :name="'items['+index+'][purchase_service_id]'" value="">
                                        <input type="text" :name="'items['+index+'][description]'" x-model="item.description" required
                                               class="w-full rounded border-gray-300 text-sm" placeholder="Descripción">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" min="1" :name="'items['+index+'][quantity]'" x-model.number="item.quantity"
                                               class="w-full rounded border-gray-300 text-sm text-right">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" min="0" :name="'items['+index+'][unit_price]'" x-model.number="item.unit_price"
                                               class="w-full rounded border-gray-300 text-sm text-right">
                                    </td>
                                    <td class="px-2 py-2">
                                        <select :name="'items['+index+'][iva_rate]'" x-model="item.iva_rate" class="w-full rounded border-gray-300 text-sm">
                                            <option value="0">0</option>
                                            <option value="10.5">10.5</option>
                                            <option value="21">21</option>
                                            <option value="27">27</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-2 text-right text-sm font-medium text-gray-800" x-text="'$' + formatMoney(itemTotal(item))"></td>
                                    <td class="px-1 py-2">
                                        <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700 text-sm" x-show="items.length > 1">&times;</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Otros importes</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-xl">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Percepciones</label>
                        <input type="number" step="0.01" min="0" name="percepciones" x-model.number="percepciones"
                               class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Otros impuestos</label>
                        <input type="number" step="0.01" min="0" name="otros_impuestos" x-model.number="otros_impuestos"
                               class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas internas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex justify-end border-t border-gray-100 pt-4">
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Total estimado</p>
                        <p class="text-xl font-bold text-gray-900" x-text="'$ ' + formatMoney(grandTotal)"></p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('purchase-credit-notes.index') }}" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-5 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 shadow-sm">Guardar nota de crédito</button>
            </div>
        </form>
    </div>

    <script>
        function ncForm() {
            return {
                voucher_type: 'A',
                point_of_sale: '',
                credit_note_number: '',
                issue_date: new Date().toISOString().split('T')[0],
                supplier_id: @json($selectedSupplierId ? (string) $selectedSupplierId : ''),
                purchase_invoice_id: @json($selectedPurchaseInvoiceId ? (string) $selectedPurchaseInvoiceId : ''),
                selectedPiPreset: @json($selectedPurchaseInvoiceId ? (string) $selectedPurchaseInvoiceId : ''),
                purchaseInvoices: [],
                duplicateWarning: false,
                percepciones: 0,
                otros_impuestos: 0,
                items: [{ description: '', quantity: 1, unit_price: 0, iva_rate: '21' }],

                init() {
                    if (this.supplier_id) {
                        this.onSupplierChange();
                    }
                },

                async onSupplierChange() {
                    const keepPi = this.selectedPiPreset;
                    this.purchaseInvoices = [];
                    if (!this.supplier_id) {
                        this.purchase_invoice_id = '';
                        return;
                    }
                    try {
                        const r = await fetch(`{{ route('purchase-credit-notes.available-purchase-invoices') }}?supplier_id=${this.supplier_id}`);
                        const data = await r.json();
                        this.purchaseInvoices = data;
                        if (keepPi && data.some(inv => String(inv.id) === String(keepPi))) {
                            this.purchase_invoice_id = String(keepPi);
                        } else {
                            this.purchase_invoice_id = '';
                        }
                        this.selectedPiPreset = '';
                    } catch (e) {
                        this.purchaseInvoices = [];
                    }
                },

                padPointOfSale() {
                    if (this.point_of_sale) {
                        this.point_of_sale = String(parseInt(this.point_of_sale) || 0).padStart(5, '0');
                    }
                },

                padCnNumber() {
                    if (this.credit_note_number) {
                        this.credit_note_number = String(parseInt(this.credit_note_number) || 0).padStart(8, '0');
                    }
                },

                async checkDuplicate() {
                    if (!this.supplier_id || !this.credit_note_number) {
                        this.duplicateWarning = false;
                        return;
                    }
                    try {
                        const params = new URLSearchParams({
                            supplier_id: this.supplier_id,
                            point_of_sale: this.point_of_sale || '',
                            credit_note_number: this.credit_note_number,
                        });
                        const resp = await fetch(`{{ route('purchase-credit-notes.check-duplicate') }}?${params}`);
                        const data = await resp.json();
                        this.duplicateWarning = data.duplicate;
                    } catch (e) {
                        this.duplicateWarning = false;
                    }
                },

                addItem() {
                    this.items.push({ description: '', quantity: 1, unit_price: 0, iva_rate: '21' });
                },

                removeItem(i) {
                    this.items.splice(i, 1);
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

                get totalIva() {
                    return this.items.reduce((sum, item) => sum + this.itemIva(item), 0);
                },

                get grandTotal() {
                    return this.subtotal + this.totalIva + parseFloat(this.percepciones || 0) + parseFloat(this.otros_impuestos || 0);
                },

                formatMoney(val) {
                    return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
                }
            };
        }
    </script>
</x-admin-layout>
