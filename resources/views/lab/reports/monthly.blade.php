<x-lab-layout title="Reportes Mensuales">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Reporte Mensual por Obra Social</h1>
            <p class="mt-1 text-sm text-gray-600">Resumen de prácticas facturables a obras sociales</p>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <form action="{{ route('lab.reports.monthly') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="w-64">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Obra Social *</label>
                    <select name="insurance_id" required
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Seleccionar...</option>
                        @foreach($insurances as $ins)
                            <option value="{{ $ins->id }}" {{ $insuranceId == $ins->id ? 'selected' : '' }}>
                                {{ strtoupper($ins->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-32">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                    <select name="month" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        @php
                            $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                        @endphp
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ $meses[$m - 1] }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="w-28">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                    <select name="year" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                    Generar Reporte
                </button>
            </form>
        </div>

        @if($report !== null)
            <!-- Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Total Facturado</p>
                    <p class="text-2xl font-bold text-teal-600">${{ number_format($totals['total_amount'], 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Total Copagos</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($totals['total_copago'], 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Prácticas</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totals['total_practices'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Admisiones</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totals['total_admissions'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Pacientes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totals['total_patients'] }}</p>
                </div>
            </div>

            <!-- Botón de exportar -->
            <div class="mb-4 flex justify-end">
                <a href="{{ route('lab.reports.exportExcel', ['insurance_id' => $insuranceId, 'month' => $month, 'year' => $year]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Exportar a Excel
                </a>
            </div>

            <!-- Tabla de detalle -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-800">
                        {{ strtoupper($selectedInsurance->name) }} - 
                        {{ $meses[$month - 1] }} {{ $year }}
                    </h2>
                </div>

                @if($report->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paciente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DNI</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Afiliado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Práctica</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($report as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                            {{ $row['formatted_date'] }}
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-900">
                                            {{ $row['patient_name'] }}
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                            {{ $row['patient_id'] }}
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                                            {{ $row['affiliate_number'] }}
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $row['test_code'] }}
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-900">
                                            {{ $row['test_name'] }}
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                            ${{ number_format($row['price'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-green-50">
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-sm font-bold text-gray-900 text-right">
                                        TOTAL
                                    </td>
                                    <td class="px-6 py-4 text-right text-lg font-bold text-teal-600">
                                        ${{ number_format($totals['total_amount'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Sin datos</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            No hay prácticas registradas para {{ strtoupper($selectedInsurance->name) }} en {{ $meses[$month - 1] }} {{ $year }}.
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Generar Reporte</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Seleccione una obra social, mes y año para generar el reporte mensual.
                </p>
            </div>
        @endif
    </div>
</x-lab-layout>

