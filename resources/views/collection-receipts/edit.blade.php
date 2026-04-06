<x-admin-layout>
    <div class="p-4 md:p-6" x-data="collectionEditForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar Recibo {{ $collectionReceipt->number }}</h1>
                <p class="text-gray-500 text-sm mt-1">Modificar recibo de cobro</p>
            </div>
            <a href="{{ route('collection-receipts.show', $collectionReceipt) }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
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

        <form method="POST" action="{{ route('collection-receipts.update', $collectionReceipt) }}">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos Generales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
                        <select name="customer_id" x-model="customerId" @change="onCustomerChange()" required
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" {{ old('customer_id', $collectionReceipt->customer_id) == $cust->id ? 'selected' : '' }}>{{ $cust->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', $collectionReceipt->date->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Observaciones opcionales">{{ old('notes', $collectionReceipt->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Facturas Pendientes</h2>

                <div x-show="invoices.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Este cliente no tiene facturas pendientes de cobro
                </div>

                <div x-show="invoices.length > 0" x-cloak class="overflow-x-auto">
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
                <p class="text-sm text-gray-500 mb-4">Medios líquidos. La suma de medios <strong>más</strong> las retenciones debe igualar el total a cobrar.</p>

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

                <div class="mt-4 flex flex-col lg:flex-row lg:flex-wrap lg:justify-end lg:items-center gap-2 text-sm">
                    <span class="text-gray-600">Total medios: <strong x-text="'$' + formatMoney(totalPayments)"></strong></span>
                    <span class="text-gray-400 hidden lg:inline">|</span>
                    <span class="text-gray-600">Total retenciones: <strong x-text="'$' + formatMoney(totalWithholdings)"></strong></span>
                    <span class="text-gray-400 hidden lg:inline">|</span>
                    <span class="text-gray-600">Suma medios + retenciones: <strong x-text="'$' + formatMoney(totalLiquidAndWithholdings)"></strong></span>
                    <span class="text-gray-400 hidden lg:inline">|</span>
                    <span class="text-gray-600">Total facturas: <strong x-text="'$' + formatMoney(totalCollection)"></strong></span>
                </div>
                <p x-show="!receiptTotalsMatch && selectedInvoices.length > 0" x-cloak class="mt-2 text-sm text-red-600 font-medium">
                    Los importes no coinciden. Ajustá medios, retenciones o montos a cobrar.
                </p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Retenciones sufridas</h2>
                        <p class="text-sm text-gray-500 mt-1">Importes retenidos por el cliente según certificado. Se suman al total del recibo junto con los medios de pago.</p>
                    </div>
                    <button type="button" @click="addWithholdingLine()"
                            class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-800 text-sm font-medium rounded-lg hover:bg-gray-200">
                        + Agregar retención
                    </button>
                </div>
                <p x-show="withholdingLines.length === 0" class="text-sm text-gray-400 mb-3">No hay retenciones cargadas. Usá + Agregar retención si el cliente te practicó retenciones.</p>
                <div class="space-y-4">
                    <template x-for="(wh, wi) in withholdingLines" :key="'wh-' + wi">
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50">
                            <div class="flex justify-between items-start gap-2">
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo <span class="text-red-500">*</span></label>
                                        <select x-model="wh.withholding_type" @change="syncDefaultPaymentAmount()"
                                                class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                            <option value="ganancias">Ganancias</option>
                                            <option value="iva">IVA</option>
                                            <option value="suss_931">SUSS (Ley 19.640 / 931)</option>
                                            <option value="iibb">IIBB</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Nº doc.</label>
                                        <input type="text" x-model="wh.document_number" placeholder="8669249"
                                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Régimen de retención <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="wh.regime" placeholder="Locación de obras y servicios"
                                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                    </div>
                                    <div class="md:col-span-2 lg:col-span-1">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Jurisdicción</label>
                                        <p x-show="wh.withholding_type === 'iibb'" class="text-xs text-amber-700 mb-1"><i class="bi bi-info-circle"></i> Requerida para IIBB</p>
                                        <input type="text" x-model="wh.jurisdiction"
                                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Nº certificado <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="wh.certificate_number"
                                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Monto retenido <span class="text-red-500">*</span></label>
                                        <input type="number" x-model.number="wh.amount" min="0.01" step="0.01" @input="syncDefaultPaymentAmount()"
                                               class="w-full rounded-lg border-gray-300 text-sm text-right focus:border-zinc-500 focus:ring-zinc-500">
                                    </div>
                                </div>
                                <button type="button" @click="removeWithholdingLine(wi)"
                                        class="text-sm text-red-600 hover:text-red-800 whitespace-nowrap mt-6">Quitar</button>
                            </div>
                        </div>
                    </template>
                </div>
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

            <template x-for="(wh, index) in withholdingLines" :key="'hidden-wh-' + index">
                <div>
                    <input type="hidden" :name="'withholdings[' + index + '][withholding_type]'" :value="wh.withholding_type">
                    <input type="hidden" :name="'withholdings[' + index + '][document_number]'" :value="wh.document_number">
                    <input type="hidden" :name="'withholdings[' + index + '][regime]'" :value="wh.regime">
                    <input type="hidden" :name="'withholdings[' + index + '][jurisdiction]'" :value="wh.jurisdiction">
                    <input type="hidden" :name="'withholdings[' + index + '][certificate_number]'" :value="wh.certificate_number">
                    <input type="hidden" :name="'withholdings[' + index + '][amount]'" :value="wh.amount">
                </div>
            </template>

            <div class="flex justify-end gap-3">
                <a href="{{ route('collection-receipts.show', $collectionReceipt) }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 text-sm">Cancelar</a>
                <button type="submit"
                        :disabled="selectedInvoices.length === 0 || !receiptTotalsMatch"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <script>
        function collectionEditForm() {
            const existingItems = @json($collectionReceipt->items->map(fn($item) => [
                'invoice_id' => $item->sales_invoice_id,
                'amount' => (float) $item->amount,
            ]));

            const existingItemMap = {};
            existingItems.forEach(item => {
                existingItemMap[item.invoice_id] = item.amount;
            });

            const allInvoices = @json($pendingInvoices->map(fn($inv) => [
                'id' => $inv->id,
                'full_number' => $inv->full_number,
                'issue_date' => $inv->issue_date->format('d/m/Y'),
                'total' => (float) $inv->total,
                'amount_collected' => (float) $inv->amount_collected,
                'balance' => (float) $inv->balance,
            ]));

            const invoices = allInvoices.map(inv => ({
                ...inv,
                selected: inv.id in existingItemMap,
                amount: existingItemMap[inv.id] ?? inv.balance,
            }));

            const initialPayments = @json($initialPaymentLines);
            const initialWithholdingLines = @json($initialWithholdingLines);
            const emptyPaymentLine = () => ({
                line_type: 'efectivo',
                amount: 0,
                bank_account_id: '',
                cheque_number: '',
                bank_name: '',
                due_date: '',
            });
            const emptyWithholdingLine = () => ({
                withholding_type: 'ganancias',
                document_number: '',
                regime: '',
                jurisdiction: '',
                certificate_number: '',
                amount: 0,
            });

            return {
                customerId: '{{ old('customer_id', $collectionReceipt->customer_id) }}',
                invoices: invoices,
                paymentLines: initialPayments.length ? initialPayments.map(p => ({
                    ...p,
                    bank_account_id: p.bank_account_id ? String(p.bank_account_id) : '',
                })) : [emptyPaymentLine()],
                withholdingLines: initialWithholdingLines.length
                    ? initialWithholdingLines.map(w => ({ ...w, document_number: w.document_number || '', jurisdiction: w.jurisdiction || '' }))
                    : [],
                init() {
                    this.$watch('totalCollection', () => this.syncDefaultPaymentAmount());
                    this.$watch('withholdingLines', () => this.syncDefaultPaymentAmount(), { deep: true });
                },
                onCustomerChange() {
                    if (this.customerId != '{{ $collectionReceipt->customer_id }}') {
                        window.location.href = '{{ route('collection-receipts.create') }}?customer_id=' + this.customerId;
                    }
                },
                onToggleInvoice(index) {
                    if (!this.invoices[index].selected) {
                        this.invoices[index].amount = this.invoices[index].balance;
                    }
                    this.syncDefaultPaymentAmount();
                },
                addPaymentLine() {
                    const line = emptyPaymentLine();
                    const currentPay = this.totalPayments;
                    const rest = Math.max(0, this.totalCollection - this.totalWithholdings - currentPay);
                    line.amount = rest > 0 ? rest : 0;
                    this.paymentLines.push(line);
                },
                addWithholdingLine() {
                    this.withholdingLines.push(emptyWithholdingLine());
                    this.syncDefaultPaymentAmount();
                },
                removeWithholdingLine(index) {
                    this.withholdingLines.splice(index, 1);
                    this.syncDefaultPaymentAmount();
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
                        this.paymentLines[0].amount = Math.max(0, this.totalCollection - this.totalWithholdings);
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
                get totalWithholdings() {
                    return this.withholdingLines.reduce((sum, wh) => sum + parseFloat(wh.amount || 0), 0);
                },
                get totalLiquidAndWithholdings() {
                    return this.totalPayments + this.totalWithholdings;
                },
                get receiptTotalsMatch() {
                    if (this.selectedInvoices.length === 0) return false;
                    return Math.abs(this.totalCollection - this.totalLiquidAndWithholdings) < 0.02;
                },
                formatMoney(val) {
                    return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2 }).format(val);
                }
            };
        }
    </script>
</x-admin-layout>
