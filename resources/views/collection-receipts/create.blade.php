<x-admin-layout>
    <div class="p-4 md:p-6" x-data="collectionForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nuevo Recibo de Cobro</h1>
                <p class="text-gray-500 text-sm mt-1">Crear un nuevo recibo de cobro a cliente</p>
            </div>
            <a href="{{ route('collection-receipts.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
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

        <form method="POST" action="{{ route('collection-receipts.store') }}">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos Generales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
                        <select name="customer_id" x-model="customerId" @change="onCustomerChange()" required
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" {{ old('customer_id', $selectedCustomer?->id) == $cust->id ? 'selected' : '' }}>{{ $cust->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Observaciones opcionales">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Facturas Pendientes</h2>

                <div x-show="!customerId" class="text-center py-8 text-gray-400 text-sm">
                    Seleccioná un cliente para ver sus facturas pendientes
                </div>

                <div x-show="customerId && invoices.length === 0" x-cloak class="text-center py-8 text-gray-400 text-sm">
                    Este cliente no tiene facturas pendientes de cobro
                </div>

                <div x-show="customerId && invoices.length > 0" x-cloak class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-10"></th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">N° Factura Venta</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cobrado</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-40">Monto a Cobrar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(inv, index) in invoices" :key="inv.id">
                                <tr class="hover:bg-gray-50" :class="{ 'bg-zinc-50': inv.selected }">
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox" x-model="inv.selected" @change="onToggleInvoice(index)"
                                               class="rounded border-gray-300 text-zinc-600 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2 text-sm font-medium text-gray-800" x-text="inv.full_number"></td>
                                    <td class="px-3 py-2 text-sm text-gray-500" x-text="inv.issue_date"></td>
                                    <td class="px-3 py-2 text-sm text-gray-700 text-right" x-text="'$' + formatMoney(inv.total)"></td>
                                    <td class="px-3 py-2 text-sm text-gray-500 text-right" x-text="'$' + formatMoney(inv.amount_collected)"></td>
                                    <td class="px-3 py-2 text-sm font-medium text-amber-600 text-right" x-text="'$' + formatMoney(inv.balance)"></td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="inv.amount" :max="inv.balance" min="0.01" step="0.01"
                                               :disabled="!inv.selected"
                                               class="w-full rounded border-gray-300 text-sm text-right focus:border-zinc-500 focus:ring-zinc-500 disabled:bg-gray-100 disabled:text-gray-400">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr class="border-t-2 border-gray-300">
                                <td colspan="6" class="px-3 py-3 text-right text-sm font-bold text-gray-800">Total a Cobrar</td>
                                <td class="px-3 py-3 text-right text-base font-bold text-gray-900" x-text="'$' + formatMoney(totalCollection)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Valores recibidos</h2>
                    <button type="button" @click="addPaymentLine()"
                            class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-800 text-sm font-medium rounded-lg hover:bg-gray-200">
                        + Agregar medio
                    </button>
                </div>
                <p class="text-sm text-gray-500 mb-4">La suma de los medios debe igualar el total a cobrar. El e-cheq ingresa solo a cartera (sin endoso en esta pantalla).</p>

                <div class="space-y-4">
                    <template x-for="(line, pi) in paymentLines" :key="pi">
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50">
                            <div class="flex justify-between items-start gap-2 mb-3">
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                                        <select x-model="line.line_type" @change="onPaymentTypeChange(pi)"
                                                class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                            <option value="efectivo">Efectivo</option>
                                            <option value="transferencia">Transferencia</option>
                                            <option value="echeq">E-cheq</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Importe <span class="text-red-500">*</span></label>
                                        <input type="number" x-model.number="line.amount" min="0.01" step="0.01"
                                               class="w-full rounded-lg border-gray-300 text-sm text-right focus:border-zinc-500 focus:ring-zinc-500">
                                    </div>
                                    <div x-show="line.line_type === 'transferencia'" x-cloak class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta bancaria <span class="text-red-500">*</span></label>
                                        <select x-model="line.bank_account_id"
                                                class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                            <option value="">Seleccionar...</option>
                                            @foreach($bankAccounts as $ba)
                                                <option value="{{ $ba->id }}">{{ $ba->display_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <template x-if="line.line_type === 'echeq'">
                                        <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Número <span class="text-red-500">*</span></label>
                                                <input type="text" x-model="line.cheque_number" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Banco <span class="text-red-500">*</span></label>
                                                <input type="text" x-model="line.bank_name" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Vencimiento <span class="text-red-500">*</span></label>
                                                <input type="date" x-model="line.due_date" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <button type="button" @click="removePaymentLine(pi)" x-show="paymentLines.length > 1"
                                        class="text-sm text-red-600 hover:text-red-800 whitespace-nowrap mt-6">Quitar</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row sm:justify-end sm:items-center gap-2 text-sm">
                    <span class="text-gray-600">Total medios: <strong x-text="'$' + formatMoney(totalPayments)"></strong></span>
                    <span class="text-gray-400 hidden sm:inline">|</span>
                    <span class="text-gray-600">Total facturas: <strong x-text="'$' + formatMoney(totalCollection)"></strong></span>
                </div>
                <p x-show="!paymentsMatchTotal && selectedInvoices.length > 0" x-cloak class="mt-2 text-sm text-red-600 font-medium">
                    Los importes no coinciden. Ajustá las líneas o los montos a cobrar.
                </p>
            </div>

            <template x-for="(inv, index) in selectedInvoices" :key="'hidden-inv-' + inv.id">
                <div>
                    <input type="hidden" :name="'invoices[' + index + '][sales_invoice_id]'" :value="inv.id">
                    <input type="hidden" :name="'invoices[' + index + '][amount]'" :value="inv.amount">
                </div>
            </template>

            <template x-for="(line, index) in paymentLines" :key="'hidden-pay-' + index">
                <div>
                    <input type="hidden" :name="'payments[' + index + '][line_type]'" :value="line.line_type">
                    <input type="hidden" :name="'payments[' + index + '][amount]'" :value="line.amount">
                    <input type="hidden" :name="'payments[' + index + '][bank_account_id]'" :value="line.line_type === 'transferencia' ? line.bank_account_id : ''">
                    <input type="hidden" :name="'payments[' + index + '][cheque_number]'" :value="line.line_type === 'echeq' ? line.cheque_number : ''">
                    <input type="hidden" :name="'payments[' + index + '][bank_name]'" :value="line.line_type === 'echeq' ? line.bank_name : ''">
                    <input type="hidden" :name="'payments[' + index + '][due_date]'" :value="line.line_type === 'echeq' ? line.due_date : ''">
                </div>
            </template>

            <div class="flex justify-end">
                <button type="submit"
                        :disabled="selectedInvoices.length === 0 || !paymentsMatchTotal"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Guardar Recibo de Cobro
                </button>
            </div>
        </form>
    </div>

    <script>
        function collectionForm() {
            const invoicesByCustomer = @json($invoicesByCustomer);

            @if($selectedCustomer && $preloadedInvoiceRows)
                const preloaded = @json($preloadedInvoiceRows);
                if ('{{ $selectedCustomer->id }}' in invoicesByCustomer) {
                    invoicesByCustomer['{{ $selectedCustomer->id }}'] = preloaded;
                }
            @endif

            const emptyPaymentLine = () => ({
                line_type: 'efectivo',
                amount: 0,
                bank_account_id: '',
                cheque_number: '',
                bank_name: '',
                due_date: '',
            });

            return {
                customerId: '{{ old('customer_id', $selectedCustomer?->id ?? '') }}',
                invoices: [],
                paymentLines: [emptyPaymentLine()],
                init() {
                    if (this.customerId) {
                        this.invoices = JSON.parse(JSON.stringify(invoicesByCustomer[this.customerId] || []));
                    }
                    this.$watch('totalCollection', () => this.syncDefaultPaymentAmount());
                },
                onCustomerChange() {
                    if (this.customerId && invoicesByCustomer[this.customerId]) {
                        this.invoices = JSON.parse(JSON.stringify(invoicesByCustomer[this.customerId]));
                    } else {
                        this.invoices = [];
                    }
                    this.syncDefaultPaymentAmount();
                },
                onToggleInvoice(index) {
                    if (!this.invoices[index].selected) {
                        this.invoices[index].amount = this.invoices[index].balance;
                    }
                    this.syncDefaultPaymentAmount();
                },
                addPaymentLine() {
                    const line = emptyPaymentLine();
                    const rest = Math.max(0, this.totalCollection - this.totalPayments);
                    line.amount = rest > 0 ? rest : 0;
                    this.paymentLines.push(line);
                },
                removePaymentLine(index) {
                    if (this.paymentLines.length <= 1) return;
                    this.paymentLines.splice(index, 1);
                },
                onPaymentTypeChange(index) {
                    const line = this.paymentLines[index];
                    if (line.line_type !== 'transferencia') line.bank_account_id = '';
                    if (line.line_type !== 'echeq') {
                        line.cheque_number = '';
                        line.bank_name = '';
                        line.due_date = '';
                    }
                },
                syncDefaultPaymentAmount() {
                    if (this.paymentLines.length === 1 && this.paymentLines[0].line_type === 'efectivo') {
                        this.paymentLines[0].amount = this.totalCollection;
                    }
                },
                get selectedInvoices() {
                    return this.invoices.filter(inv => inv.selected && inv.amount > 0);
                },
                get totalCollection() {
                    return this.selectedInvoices.reduce((sum, inv) => sum + parseFloat(inv.amount || 0), 0);
                },
                get totalPayments() {
                    return this.paymentLines.reduce((sum, line) => sum + parseFloat(line.amount || 0), 0);
                },
                get paymentsMatchTotal() {
                    if (this.selectedInvoices.length === 0) return false;
                    return Math.abs(this.totalCollection - this.totalPayments) < 0.02;
                },
                formatMoney(val) {
                    return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2 }).format(val);
                }
            };
        }
    </script>
</x-admin-layout>
