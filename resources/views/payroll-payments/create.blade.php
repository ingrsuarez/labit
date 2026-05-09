<x-admin-layout title="Nuevo Pago de Haberes">
    <div class="max-w-5xl mx-auto p-6" x-data="payrollPaymentForm()">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('payroll-payments.index') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Nuevo Pago de Haberes</h1>
        </div>

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('payroll-payments.store') }}">
            @csrf

            {{-- Sección 1: Datos del pago --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del pago</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Año <span class="text-red-500">*</span></label>
                        <input type="number" name="year" value="{{ old('year', $year) }}"
                               min="2000" max="2100" required
                               @change="loadAvailable()"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mes <span class="text-red-500">*</span></label>
                        <select name="month" required @change="loadAvailable()"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500">
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}" {{ old('month', $month) == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Bancaria</label>
                        <select name="bank_account_id"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500">
                            <option value="">— Sin asignar —</option>
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago</label>
                        <input type="date" name="payment_date" value="{{ old('payment_date') }}"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <input type="text" name="notes" value="{{ old('notes') }}"
                               placeholder='Ej: "Banco Nación", "Transferencias externas"'
                               maxlength="500"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                </div>
            </div>

            {{-- Alerta pagos existentes en el período --}}
            @if($existingPayments->isNotEmpty())
                <div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm">
                    <strong>Atención:</strong> Ya existe{{ $existingPayments->count() > 1 ? 'n' : '' }}
                    {{ $existingPayments->count() }} pago{{ $existingPayments->count() > 1 ? 's' : '' }}
                    para este período:
                    @foreach($existingPayments as $ep)
                        <span class="inline-flex items-center ml-1 px-2 py-0.5 rounded-full text-xs
                            {{ $ep->status === 'confirmado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $ep->payrolls_count }} emp. — {{ ucfirst($ep->status) }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Sección 2: Liquidaciones a incluir --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">Liquidaciones a incluir</h2>
                    <span class="text-sm text-gray-500">
                        Solo liquidaciones en estado <em>liquidado</em> sin pago asignado
                    </span>
                </div>

                @if($availablePayrolls->isEmpty())
                    <div class="p-8 text-center text-gray-400">
                        No hay liquidaciones disponibles para el período seleccionado.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left w-10">
                                        <input type="checkbox" id="selectAll"
                                               @change="toggleAll($event)"
                                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CUIL</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Neto a Cobrar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($availablePayrolls as $payroll)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <input type="checkbox"
                                               name="payroll_ids[]"
                                               value="{{ $payroll->id }}"
                                               :checked="selected.includes({{ $payroll->id }})"
                                               @change="toggle({{ $payroll->id }}, {{ $payroll->neto_a_cobrar }})"
                                               {{ in_array($payroll->id, old('payroll_ids', [])) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $payroll->employee_name }}</td>
                                    <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $payroll->employee_cuil }}</td>
                                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $payroll->category_name }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">
                                        ${{ number_format($payroll->neto_a_cobrar, 2, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                        <span class="text-sm text-gray-600">
                            <span x-text="selected.length"></span> liquidación(es) seleccionada(s)
                        </span>
                        <span class="text-sm font-semibold text-gray-900">
                            Total: $<span x-text="total.toLocaleString('es-AR', {minimumFractionDigits:2})"></span>
                        </span>
                    </div>
                @endif
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="inline-flex items-center px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium">
                    Crear Pago de Haberes
                </button>
                <a href="{{ route('payroll-payments.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <script>
    function payrollPaymentForm() {
        const payrollData = @json($availablePayrolls->map(fn($p) => ['id' => $p->id, 'neto' => (float)$p->neto_a_cobrar]));

        return {
            selected: @json(array_map('intval', old('payroll_ids', []))),
            total: 0,

            init() {
                this.recalculate();
            },

            toggle(id, neto) {
                const idx = this.selected.indexOf(id);
                if (idx === -1) {
                    this.selected.push(id);
                } else {
                    this.selected.splice(idx, 1);
                }
                this.recalculate();
            },

            toggleAll(event) {
                if (event.target.checked) {
                    this.selected = payrollData.map(p => p.id);
                } else {
                    this.selected = [];
                }
                this.recalculate();
                // Sincronizar checkboxes individuales
                document.querySelectorAll('input[name="payroll_ids[]"]').forEach(cb => {
                    cb.checked = event.target.checked;
                });
            },

            recalculate() {
                this.total = payrollData
                    .filter(p => this.selected.includes(p.id))
                    .reduce((sum, p) => sum + p.neto, 0);
            },

            loadAvailable() {
                // Recargar la página con los nuevos parámetros de período
                const year = document.querySelector('input[name="year"]').value;
                const month = document.querySelector('select[name="month"]').value;
                window.location.href = `{{ route('payroll-payments.create') }}?year=${year}&month=${month}`;
            }
        }
    }
    </script>
</x-admin-layout>
