<x-admin-layout>
    <div class="p-4 md:p-6" x-data="paymentEditForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar Orden {{ $paymentOrder->number }}</h1>
                <p class="text-gray-500 text-sm mt-1">Modificar orden de pago</p>
            </div>
            <a href="{{ route('payment-orders.show', $paymentOrder) }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
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

        <form method="POST" action="{{ route('payment-orders.update', $paymentOrder) }}">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos Generales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <select name="supplier_id" x-model="supplierId" @change="onSupplierChange()" required
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ old('supplier_id', $paymentOrder->supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', $paymentOrder->date->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                        <select name="payment_method" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            <option value="transferencia" {{ old('payment_method', $paymentOrder->payment_method) === 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                            <option value="cheque" {{ old('payment_method', $paymentOrder->payment_method) === 'cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="efectivo" {{ old('payment_method', $paymentOrder->payment_method) === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Referencia de Pago</label>
                        <input type="text" name="payment_reference" value="{{ old('payment_reference', $paymentOrder->payment_reference) }}"
                               placeholder="N° transferencia, cheque, etc."
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Observaciones opcionales">{{ old('notes', $paymentOrder->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Facturas Pendientes</h2>

                <div x-show="invoices.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Este proveedor no tiene facturas pendientes de pago
                </div>

                <div x-show="invoices.length > 0" x-cloak class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-10"></th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">N° Factura</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Pagado</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-40">Monto a Pagar</th>
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
                                    <td class="px-3 py-2 text-sm text-gray-500 text-right" x-text="'$' + formatMoney(inv.amount_paid)"></td>
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
                                <td colspan="6" class="px-3 py-3 text-right text-sm font-bold text-gray-800">Total a Pagar</td>
                                <td class="px-3 py-3 text-right text-base font-bold text-gray-900" x-text="'$' + formatMoney(totalPayment)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <template x-for="(inv, index) in selectedInvoices" :key="'hidden-' + inv.id">
                <div>
                    <input type="hidden" :name="'invoices[' + index + '][purchase_invoice_id]'" :value="inv.id">
                    <input type="hidden" :name="'invoices[' + index + '][amount]'" :value="inv.amount">
                </div>
            </template>

            <div class="flex justify-end gap-3">
                <a href="{{ route('payment-orders.show', $paymentOrder) }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 text-sm">Cancelar</a>
                <button type="submit" :disabled="selectedInvoices.length === 0"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <script>
        function paymentEditForm() {
            const existingItems = @json($paymentOrder->items->map(fn($item) => [
                'invoice_id' => $item->purchase_invoice_id,
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
                'amount_paid' => (float) $inv->amount_paid,
                'balance' => (float) $inv->balance,
            ]));

            const invoices = allInvoices.map(inv => ({
                ...inv,
                selected: inv.id in existingItemMap,
                amount: existingItemMap[inv.id] ?? inv.balance,
            }));

            return {
                supplierId: '{{ old('supplier_id', $paymentOrder->supplier_id) }}',
                invoices: invoices,
                onSupplierChange() {
                    if (this.supplierId != '{{ $paymentOrder->supplier_id }}') {
                        window.location.href = '{{ route('payment-orders.create') }}?supplier_id=' + this.supplierId;
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
                get totalPayment() {
                    return this.selectedInvoices.reduce((sum, inv) => sum + parseFloat(inv.amount || 0), 0);
                },
                formatMoney(val) {
                    return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2 }).format(val);
                }
            }
        }
    </script>
</x-admin-layout>
