<x-admin-layout>
    <div class="max-w-7xl mx-auto p-6">
        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Liquidación Masiva</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Período: {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}
                </p>
            </div>
            <a href="{{ route('payroll.index') }}" 
               class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver
            </a>
        </div>

        {{-- Selector de período --}}
        <form method="GET" class="bg-white p-4 rounded-xl shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Año</label>
                    <input type="number" name="year" value="{{ $year }}" min="2020" max="2100"
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mes</label>
                    <select name="month"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromDate(2000, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Recalcular
                    </button>
                </div>
            </div>
        </form>

        {{-- Resumen Total --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Empleados</div>
                <div class="text-2xl font-semibold text-gray-900">{{ count($payrolls) }}</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Total Bruto</div>
                <div class="text-2xl font-semibold text-green-600">
                    ${{ number_format($totals['total_bruto'], 2, ',', '.') }}
                </div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Total Deducciones</div>
                <div class="text-2xl font-semibold text-red-600">
                    ${{ number_format($totals['total_deducciones'], 2, ',', '.') }}
                </div>
            </div>
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl shadow p-4 text-white">
                <div class="text-sm text-blue-200">Total Neto a Pagar</div>
                <div class="text-2xl font-bold">
                    ${{ number_format($totals['total_neto'], 2, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Tabla de liquidaciones --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Básico</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Haberes</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Deducciones</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Neto</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payrolls as $payroll)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 capitalize">{{ $payroll['empleado']['nombre'] }}</div>
                                <div class="text-xs text-gray-500">{{ $payroll['empleado']['cuil'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 capitalize">{{ $payroll['empleado']['categoria'] }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">
                                ${{ number_format($payroll['salario_basico'], 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-green-600">
                                ${{ number_format($payroll['subtotal'], 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-red-600">
                                ${{ number_format($payroll['total_deducciones'], 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-blue-700">
                                ${{ number_format($payroll['neto_a_cobrar'], 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('payroll.index', ['employee_id' => $payroll['empleado']['cuil'], 'year' => $year, 'month' => $month]) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">
                                    Ver Recibo
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                No hay empleados para liquidar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-100">
                    <tr class="font-bold">
                        <td colspan="3" class="px-4 py-3 text-right">TOTALES:</td>
                        <td class="px-4 py-3 text-right text-green-700">
                            ${{ number_format($totals['total_bruto'], 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-red-700">
                            ${{ number_format($totals['total_deducciones'], 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-blue-800">
                            ${{ number_format($totals['total_neto'], 2, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-admin-layout>






