<x-admin-layout title="Pago de Haberes">
    <div class="max-w-5xl mx-auto p-6">

        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between mb-6 gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('payroll-payments.index') }}" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Pago de Haberes — {{ ucfirst($payrollPayment->period_label) }}
                    </h1>
                    @if($payrollPayment->notes)
                        <p class="text-sm text-gray-500 mt-0.5">{{ $payrollPayment->notes }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($payrollPayment->status === 'confirmado')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        ✓ Confirmado
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        Borrador
                    </span>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif

        {{-- Datos generales --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Datos del pago</h2>
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Período</dt>
                    <dd class="font-medium text-gray-900 mt-0.5">{{ ucfirst($payrollPayment->period_label) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Empleados</dt>
                    <dd class="font-medium text-gray-900 mt-0.5">{{ $payrollPayment->employee_count }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Total Neto</dt>
                    <dd class="font-bold text-gray-900 mt-0.5 text-base">
                        ${{ number_format($payrollPayment->total, 2, ',', '.') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Fecha de Pago</dt>
                    <dd class="font-medium text-gray-900 mt-0.5">
                        {{ $payrollPayment->payment_date ? $payrollPayment->payment_date->format('d/m/Y') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Banco destino</dt>
                    <dd class="font-medium text-gray-900 mt-0.5">
                        {{ $payrollPayment->bankAccount?->display_name ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Creado por</dt>
                    <dd class="font-medium text-gray-900 mt-0.5">{{ $payrollPayment->creator?->name ?? '—' }}</dd>
                </div>
                @if($payrollPayment->isConfirmado())
                    <div>
                        <dt class="text-gray-500">Confirmado por</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $payrollPayment->confirmer?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Confirmado el</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">
                            {{ $payrollPayment->confirmed_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Liquidaciones incluidas --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-base font-semibold text-gray-800">Liquidaciones incluidas</h2>
            </div>
            @if($payrollPayment->payrolls->isEmpty())
                <div class="p-6 text-center text-gray-400 text-sm">Sin liquidaciones vinculadas.</div>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CUIL</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Neto a Cobrar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($payrollPayment->payrolls as $payroll)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <a href="{{ route('payroll.show', $payroll) }}" class="hover:text-teal-700">
                                    {{ $payroll->employee_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $payroll->employee_cuil }}</td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ $payroll->category_name }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($payroll->status === 'pagado')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Pagado</span>
                                @elseif($payroll->status === 'liquidado')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Liquidado</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $payroll->status }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">
                                ${{ number_format($payroll->neto_a_cobrar, 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-700 text-sm">Total</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">
                                ${{ number_format($payrollPayment->total, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>

        {{-- Asiento contable --}}
        @if($journalEntry)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Asiento Contable</h2>
                <span class="text-sm text-gray-500">N° {{ $journalEntry->number }} — {{ $journalEntry->date->format('d/m/Y') }}</span>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cuenta</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Débito</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Crédito</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($journalEntry->lines as $line)
                    <tr>
                        <td class="px-4 py-2 text-gray-700">
                            <span class="font-mono text-xs text-gray-400 mr-2">{{ $line->account->code }}</span>
                            {{ $line->account->name }}
                        </td>
                        <td class="px-4 py-2 text-right text-gray-900">
                            {{ $line->debit > 0 ? '$'.number_format($line->debit, 2, ',', '.') : '—' }}
                        </td>
                        <td class="px-4 py-2 text-right text-gray-900">
                            {{ $line->credit > 0 ? '$'.number_format($line->credit, 2, ',', '.') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Conciliación bancaria --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-base font-semibold text-gray-800">Conciliación bancaria</h2>
            </div>
            <div class="p-5 text-sm">
                @if($reconciledMovements->isNotEmpty())
                    @php $bm = $reconciledMovements->first(); @endphp
                    <div class="flex items-start gap-3">
                        <span class="text-green-600 text-xl leading-none">✓</span>
                        <div class="space-y-2 flex-1">
                            <p class="font-medium text-gray-900">Conciliado con extracto bancario</p>
                            <p class="text-gray-600">
                                <span class="text-gray-500">Banco:</span>
                                {{ $bm->statement?->bankAccount?->display_name ?? '—' }}
                                <span class="mx-2 text-gray-300">|</span>
                                <span class="text-gray-500">Fecha:</span>
                                {{ $bm->date?->format('d/m/Y') ?? '—' }}
                            </p>
                            <p class="text-gray-600"><span class="text-gray-500">Concepto:</span> {{ $bm->concept }}</p>
                            <p class="text-gray-600"><span class="text-gray-500">Monto:</span> <span class="font-mono font-medium">$ {{ number_format($bm->debit, 2, ',', '.') }}</span></p>
                            @if($bm->statement && $bm->statement->bankAccount)
                                <a href="{{ route('accounting.bank-statements.show', [$bm->statement->bankAccount, $bm->statement]) }}"
                                   class="inline-flex items-center text-violet-700 hover:text-violet-900 font-medium">
                                    Ver extracto →
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="flex items-start gap-3">
                        <span class="text-amber-500 text-xl leading-none">⚠</span>
                        <div>
                            <p class="font-medium text-gray-900">Aún no conciliado con el banco.</p>
                            <p class="text-gray-600 mt-1">Cuando el débito aparezca en el extracto, podrás vincularlo desde la pantalla de Conciliación Bancaria.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex gap-3">
            @if(!$payrollPayment->isConfirmado())
                <form method="POST" action="{{ route('payroll-payments.confirm', $payrollPayment) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
                            onclick="return confirm('¿Confirmar el pago? Las liquidaciones quedarán marcadas como pagadas y se generará el asiento contable.')">
                        ✓ Confirmar Pago
                    </button>
                </form>
                <form method="POST" action="{{ route('payroll-payments.destroy', $payrollPayment) }}">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-white border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                            onclick="return confirm('¿Eliminar este pago? Las liquidaciones quedarán desvinculadas.')">
                        Eliminar
                    </button>
                </form>
            @endif
            @if($journalEntry)
                <a href="{{ route('accounting.journal.show', $journalEntry) }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Ver Asiento
                </a>
            @endif
        </div>
    </div>
</x-admin-layout>
