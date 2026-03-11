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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                        <select name="payment_method" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            <option value="transferencia" {{ old('payment_method', $collectionReceipt->payment_method) === 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                            <option value="cheque" {{ old('payment_method', $collectionReceipt->payment_method) === 'cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="efectivo" {{ old('payment_method', $collectionReceipt->payment_method) === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                            <option value="tarjeta" {{ old('payment_method', $collectionReceipt->payment_method) === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                            <option value="deposito" {{ old('payment_method', $collectionReceipt->payment_method) === 'deposito' ? 'selected' : '' }}>Depósito</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Referencia de Pago</label>
                        <input type="text" name="payment_reference" value="{{ old('payment_reference', $collectionReceipt->payment_reference) }}"
                               placeholder="N° transferencia, cheque, etc."
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
                                        <input type="checkbox" x-model="inv.selected" @change="onToggle(index)"
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

            <template x-for="(inv, index) in selectedInvoices" :key="'hidden-' + inv.id">
                <div>
                    <input type="hidden" :name="'invoices[' + index + '][sales_invoice_id]'" :value="inv.id">
                    <input type="hidden" :name="'invoices[' + index + '][amount]'" :value="inv.amount">
                </div>
            </template>

            <div class="flex justify-end gap-3">
                <a href="{{ route('collection-receipts.show', $collectionReceipt) }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 text-sm">Cancelar</a>
                <button type="submit" :disabled="selectedInvoices.length === 0"
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

            return {
                customerId: '{{ old('customer_id', $collectionReceipt->customer_id) }}',
                invoices: invoices,
                onCustomerChange() {
                    if (this.customerId != '{{ $collectionReceipt->customer_id }}') {
                        window.location.href = '{{ route('collection-receipts.create') }}?customer_id=' + this.customerId;
                    }
                },
                onToggle(index) {
                    if (!this.invoices[index].selected) {
                        this.invoices[index].amount = this.invoices[index].balance;
                    }
                },
                get selectedInvoices() {
                    return this.invoices.filter(inv => inv.selected && inv.amount > 0);
                },
                get totalCollection() {
                    return this.selectedInvoices.reduce((sum, inv) => sum + parseFloat(inv.amount || 0), 0);
                },
                formatMoney(val) {
                    return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2 }).format(val);
                }
            }
        }
    </script>
</x-admin-layout>
