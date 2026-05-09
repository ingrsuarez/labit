<x-admin-layout title="Pagos de Haberes">
    <div class="max-w-7xl mx-auto p-6">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pagos de Haberes</h1>
                <p class="text-sm text-gray-600 mt-1">Pagos agrupados de liquidaciones de sueldos</p>
            </div>
            <a href="{{ route('payroll-payments.create') }}"
               class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 rounded-lg bg-teal-600 text-white hover:bg-teal-700 transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Pago de Haberes
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($payrollPayments->isEmpty())
                <div class="p-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>No hay pagos de haberes registrados.</p>
                    <a href="{{ route('payroll-payments.create') }}" class="mt-3 inline-block text-teal-600 hover:underline">Crear el primero</a>
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Empleados</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Neto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Banco</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Pago</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($payrollPayments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <a href="{{ route('payroll-payments.show', $payment) }}" class="hover:text-teal-700">
                                    {{ ucfirst($payment->period_label) }}
                                </a>
                                @if($payment->notes)
                                    <span class="block text-xs text-gray-400 font-normal">{{ $payment->notes }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $payment->employee_count }}</td>
                            <td class="px-4 py-3 text-right text-gray-900 font-medium">
                                ${{ number_format($payment->total, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                {{ $payment->bankAccount?->bank_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($payment->status === 'confirmado')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Confirmado</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Borrador</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('payroll-payments.show', $payment) }}"
                                   class="text-teal-600 hover:text-teal-800 text-xs font-medium">Ver</a>
                                @if($payment->status === 'borrador')
                                    <form method="POST" action="{{ route('payroll-payments.confirm', $payment) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium"
                                                onclick="return confirm('¿Confirmar el pago? Las liquidaciones quedarán marcadas como pagadas.')">
                                            Confirmar
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('payroll-payments.destroy', $payment) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium"
                                                onclick="return confirm('¿Eliminar este pago? Las liquidaciones quedarán desvinculadas.')">
                                            Eliminar
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $payrollPayments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
