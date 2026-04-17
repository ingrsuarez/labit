<x-admin-layout>
    @php
        $initialPaymentRowsForJs = $paymentOrder->paymentLines->isNotEmpty()
            ? $paymentOrder->paymentLines->map(function ($l) {
                return [
                    'uid' => 'pl-'.$l->id,
                    'kind' => $l->kind,
                    'amount' => $l->kind === 'portfolio_echeq' ? '' : (float) $l->amount,
                    'bank_account_id' => $l->bank_account_id ? (string) $l->bank_account_id : '',
                    'portfolio_echeq_id' => $l->collection_receipt_payment_id ? (string) $l->collection_receipt_payment_id : '',
                    'payment_reference' => $l->payment_reference ?? '',
                    'cheque_due_date' => $l->cheque_due_date?->format('Y-m-d') ?? '',
                ];
            })->values()
            : collect([[
                'uid' => 'r0',
                'kind' => old('payment_method', $paymentOrder->payment_method ?: 'transferencia'),
                'amount' => (float) $paymentOrder->total,
                'bank_account_id' => '',
                'portfolio_echeq_id' => '',
                'payment_reference' => old('payment_reference', $paymentOrder->payment_reference ?? ''),
                'cheque_due_date' => old('cheque_due_date', $paymentOrder->cheque_due_date?->format('Y-m-d') ?? ''),
            ]]);
    @endphp
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
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Observaciones opcionales">{{ old('notes', $paymentOrder->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Medios de pago</h2>
                        <p class="text-sm text-gray-500 mt-1">Combiná medios; la suma debe igualar el total de facturas.</p>
                    </div>
                    <button type="button" @click="addPaymentRow()"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-zinc-300 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                        + Agregar medio
                    </button>
                </div>

                @if($bankAccounts->isEmpty())
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">No hay cuentas bancarias activas para esta empresa.</p>
                @endif

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Medio</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Cuenta bancaria</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">E-cheq cartera</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-32">Importe</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Referencia</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Venc. cheque</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(row, idx) in paymentRows" :key="row.uid">
                                <tr class="align-top">
                                    <td class="px-3 py-2">
                                        <select x-model="row.kind" @change="onPaymentKindChange(row)"
                                                class="w-full min-w-[10rem] rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                            <option value="transferencia">Transferencia</option>
                                            <option value="cheque">Cheque propio</option>
                                            <option value="efectivo">Efectivo</option>
                                            <option value="portfolio_echeq">E-cheq terceros (cartera)</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <template x-if="row.kind === 'transferencia' || row.kind === 'cheque'">
                                            <select x-model="row.bank_account_id"
                                                    class="w-full min-w-[12rem] rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                                <option value="">—</option>
                                                @foreach($bankAccounts as $ba)
                                                    <option value="{{ $ba->id }}">{{ $ba->display_name }}</option>
                                                @endforeach
                                            </select>
                                        </template>
                                        <template x-if="row.kind === 'efectivo' || row.kind === 'portfolio_echeq'">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2">
                                        <template x-if="row.kind === 'portfolio_echeq'">
                                            <select x-model="row.portfolio_echeq_id" @change="onPortfolioPick(row)"
                                                    class="w-full min-w-[12rem] rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                                <option value="">Seleccionar e-cheq…</option>
                                                <template x-for="opt in portfolioOptionsForRow(row)" :key="opt.id">
                                                    <option :value="String(opt.id)" x-text="opt.rc_number + ' · ' + (opt.cheque_number || 's/n') + ' ($' + formatMoney(opt.amount) + ')'"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <template x-if="row.kind !== 'portfolio_echeq'">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <template x-if="row.kind === 'portfolio_echeq'">
                                            <span class="text-sm font-medium text-gray-800" x-text="'$' + formatMoney(portfolioAmount(row.portfolio_echeq_id))"></span>
                                        </template>
                                        <template x-if="row.kind !== 'portfolio_echeq'">
                                            <input type="number" x-model.number="row.amount" min="0.01" step="0.01"
                                                   class="w-full rounded border-gray-300 text-sm text-right focus:border-zinc-500 focus:ring-zinc-500">
                                        </template>
                                    </td>
                                    <td class="px-3 py-2">
                                        <template x-if="row.kind !== 'portfolio_echeq'">
                                            <input type="text" x-model="row.payment_reference" placeholder="N° operación, cheque…"
                                                   class="w-full min-w-[8rem] rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                        </template>
                                        <template x-if="row.kind === 'portfolio_echeq'">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2">
                                        <template x-if="row.kind === 'cheque'">
                                            <input type="date" x-model="row.cheque_due_date"
                                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                        </template>
                                        <template x-if="row.kind !== 'cheque'">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" @click="removePaymentRow(idx)" :disabled="paymentRows.length <= 1"
                                                class="text-red-600 hover:text-red-800 text-sm disabled:opacity-30" title="Quitar">✕</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 flex flex-wrap justify-end gap-3 text-sm text-gray-600">
                    <span>Total medios: <strong class="text-gray-900" x-text="'$' + formatMoney(totalPaymentLines)"></strong></span>
                    <span class="hidden sm:inline">|</span>
                    <span>Total facturas: <strong class="text-gray-900" x-text="'$' + formatMoney(totalPayment)"></strong></span>
                </div>
                <p x-show="selectedInvoices.length > 0 && !paymentTotalsMatch" class="mt-2 text-sm text-red-600 font-medium">
                    Los importes no coinciden: ajustá montos o e-cheqs para igualar el total a pagar.
                </p>
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

            <template x-for="(row, idx) in paymentRows" :key="'ph-' + row.uid">
                <div>
                    <input type="hidden" :name="'payments[' + idx + '][kind]'" :value="row.kind">
                    <input type="hidden" :name="'payments[' + idx + '][amount]'" :value="row.kind === 'portfolio_echeq' ? portfolioAmount(row.portfolio_echeq_id) : (row.amount || 0)">
                    <input type="hidden" :name="'payments[' + idx + '][bank_account_id]'" :value="(row.kind === 'transferencia' || row.kind === 'cheque') ? row.bank_account_id : ''">
                    <input type="hidden" :name="'payments[' + idx + '][portfolio_echeq_id]'" :value="row.kind === 'portfolio_echeq' ? row.portfolio_echeq_id : ''">
                    <input type="hidden" :name="'payments[' + idx + '][payment_reference]'" :value="row.kind !== 'portfolio_echeq' ? row.payment_reference : ''">
                    <input type="hidden" :name="'payments[' + idx + '][cheque_due_date]'" :value="row.kind === 'cheque' ? row.cheque_due_date : ''">
                </div>
            </template>

            <div class="flex justify-end gap-3">
                <a href="{{ route('payment-orders.show', $paymentOrder) }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 text-sm">Cancelar</a>
                <button type="submit" :disabled="selectedInvoices.length === 0 || !formReady"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    @php
        $existingItemsJson = $paymentOrder->items->map(function ($item) {
            return ['invoice_id' => $item->purchase_invoice_id, 'amount' => (float) $item->amount];
        })->values();
        $allInvoicesJson = $pendingInvoices->map(function ($inv) {
            return [
                'id' => $inv->id,
                'full_number' => $inv->full_number,
                'issue_date' => $inv->issue_date->format('d/m/Y'),
                'total' => (float) $inv->total,
                'amount_paid' => (float) $inv->amount_paid,
                'balance' => (float) $inv->balance,
            ];
        })->values();
    @endphp
    <script>
        function paymentEditForm() {
            const existingItems = @json($existingItemsJson);
            const existingItemMap = {};
            existingItems.forEach(item => {
                existingItemMap[item.invoice_id] = item.amount;
            });
            const allInvoices = @json($allInvoicesJson);
            const invoices = allInvoices.map(inv => ({
                ...inv,
                selected: inv.id in existingItemMap,
                amount: existingItemMap[inv.id] ?? inv.balance,
            }));
            const portfolioRows = @json($portfolioEcheqsJson);
            const initialPaymentRows = @json($initialPaymentRowsForJs);
            const nextUid = () => 'n-' + Date.now() + '-' + Math.floor(Math.random() * 1000);

            return {
                supplierId: '{{ old('supplier_id', $paymentOrder->supplier_id) }}',
                portfolioRows,
                paymentRows: initialPaymentRows.map(r => ({ ...r, uid: r.uid || nextUid() })),
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
                addPaymentRow() {
                    this.paymentRows.push({ uid: nextUid(), kind: 'transferencia', amount: '', bank_account_id: '', portfolio_echeq_id: '', payment_reference: '', cheque_due_date: '' });
                },
                removePaymentRow(index) {
                    if (this.paymentRows.length <= 1) return;
                    this.paymentRows.splice(index, 1);
                },
                onPaymentKindChange(row) {
                    if (row.kind !== 'transferencia' && row.kind !== 'cheque') row.bank_account_id = '';
                    if (row.kind !== 'portfolio_echeq') row.portfolio_echeq_id = '';
                    if (row.kind !== 'cheque') row.cheque_due_date = '';
                    if (row.kind === 'portfolio_echeq') row.amount = '';
                },
                onPortfolioPick(row) {},
                portfolioAmount(pid) {
                    if (!pid) return 0;
                    const r = this.portfolioRows.find(x => String(x.id) === String(pid));
                    return r ? parseFloat(r.amount) : 0;
                },
                portfolioOptionsForRow(row) {
                    const taken = new Set(
                        this.paymentRows.filter(r => r !== row && r.kind === 'portfolio_echeq' && r.portfolio_echeq_id)
                            .map(r => String(r.portfolio_echeq_id))
                    );
                    return this.portfolioRows.filter(r => !taken.has(String(r.id)));
                },
                rowValid(row) {
                    if (row.kind === 'portfolio_echeq') {
                        return !!row.portfolio_echeq_id && this.portfolioAmount(row.portfolio_echeq_id) > 0;
                    }
                    const amt = parseFloat(row.amount || 0);
                    if (!(amt > 0)) return false;
                    if (row.kind === 'transferencia' && !row.bank_account_id) return false;
                    return true;
                },
                get selectedInvoices() {
                    return this.invoices.filter(inv => inv.selected && inv.amount > 0);
                },
                get totalPayment() {
                    return this.selectedInvoices.reduce((sum, inv) => sum + parseFloat(inv.amount || 0), 0);
                },
                get totalPaymentLines() {
                    return this.paymentRows.reduce((sum, row) => {
                        if (row.kind === 'portfolio_echeq') {
                            return sum + this.portfolioAmount(row.portfolio_echeq_id);
                        }
                        return sum + parseFloat(row.amount || 0);
                    }, 0);
                },
                get paymentTotalsMatch() {
                    return Math.abs(this.totalPaymentLines - this.totalPayment) < 0.02;
                },
                get formReady() {
                    if (this.selectedInvoices.length === 0) return false;
                    if (!this.paymentTotalsMatch) return false;
                    return this.paymentRows.every(r => this.rowValid(r));
                },
                formatMoney(val) {
                    return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2 }).format(val);
                }
            }
        }
    </script>
</x-admin-layout>
