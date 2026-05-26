<x-lab-layout title="Resumen por Obra Social">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Resumen por Obra Social</h1>
            <p class="mt-1 text-sm text-gray-600">
                @if(($format ?? 'summary') === 'detailed')
                    Detallado — una fila por práctica (como planilla de facturación)
                @else
                    Consolidado — un protocolo por fila con códigos unidos
                @endif
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <form action="{{ route('lab.reports.monthly') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="w-full sm:w-64">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Obra Social *</label>
                    <select name="insurance_id" required
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Seleccionar...</option>
                        @foreach($insurances as $ins)
                            <option value="{{ $ins->id }}" {{ (string) $insuranceId === (string) $ins->id ? 'selected' : '' }}>
                                {{ $ins->displayName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Formato</label>
                    <select name="format" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="summary" {{ ($format ?? 'summary') === 'summary' ? 'selected' : '' }}>Consolidado</option>
                        <option value="detailed" {{ ($format ?? '') === 'detailed' ? 'selected' : '' }}>Detallado</option>
                    </select>
                </div>
                <div class="w-40">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div class="w-40">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                    Generar resumen
                </button>
            </form>
        </div>

        @if($rows !== null)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Protocolos</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totals['protocol_count'] }}</p>
                </div>
                @if(($format ?? 'summary') === 'detailed')
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <p class="text-sm text-gray-500">Prácticas</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totals['line_count'] ?? 0 }}</p>
                    </div>
                @endif
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Total a facturar</p>
                    <p class="text-2xl font-bold text-teal-600">${{ number_format($totals['total_amount'], 2, ',', '.') }}</p>
                </div>
            </div>

            @php
                $exportQuery = http_build_query([
                    'insurance_id' => $insuranceId,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'format' => $format ?? 'summary',
                ]);
            @endphp
            <div class="mb-4 flex flex-wrap justify-end gap-2">
                <a href="{{ route('lab.reports.exportPdf') }}?{{ $exportQuery }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800 transition-colors text-sm">
                    <i class="bi bi-file-earmark-pdf mr-2"></i> Exportar PDF
                </a>
                <a href="{{ route('lab.reports.exportExcel') }}?{{ $exportQuery }}"
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                    <i class="bi bi-file-earmark-excel mr-2"></i> Exportar Excel
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    @include('partials.billing-summary-lab-header', [
                        'reportTitle' => 'Facturación — '.$periodLabel,
                        'counterpartyLabel' => 'Obra social: '.$selectedInsurance->billingDisplayName(),
                    ])
                </div>

                @if($rows->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    @if(($format ?? 'summary') === 'detailed')
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">DNI</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Práctica</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                    @else
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">DNI</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Afiliado</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Determinaciones</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($rows as $row)
                                    <tr class="hover:bg-gray-50 {{ ($format ?? '') === 'detailed' && empty($row['formatted_date']) ? 'border-t border-gray-100' : '' }}">
                                        @if(($format ?? 'summary') === 'detailed')
                                            <td class="px-4 py-2 whitespace-nowrap text-gray-600 align-top">{{ $row['formatted_date'] }}</td>
                                            <td class="px-4 py-2 text-gray-900 align-top whitespace-pre-line">{{ $row['patient_label'] }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-gray-600 align-top">{{ $row['dni'] }}</td>
                                            <td class="px-4 py-2 font-mono text-xs align-top">{{ $row['code'] }}</td>
                                            <td class="px-4 py-2 text-gray-800 align-top">{{ $row['practice'] }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-right font-medium align-top">${{ number_format($row['amount'], 2, ',', '.') }}</td>
                                        @else
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['formatted_date'] }}</td>
                                            <td class="px-4 py-3 text-gray-900">{{ $row['name'] }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['dni'] }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['affiliate'] }}</td>
                                            <td class="px-4 py-3 font-mono text-xs text-gray-800 max-w-md truncate" title="{{ $row['codes'] }}">{{ $row['codes'] }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-right font-medium">${{ number_format($row['price'], 2, ',', '.') }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-teal-50">
                                <tr>
                                    @if(($format ?? 'summary') === 'detailed')
                                        <td colspan="4" class="px-4 py-4 text-sm font-bold text-gray-900 text-right">TOTAL A FACTURAR</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">
                                            {{ $totals['line_count'] ?? 0 }} práctica(s) · {{ $totals['protocol_count'] }} protocolo(s)
                                        </td>
                                        <td class="px-4 py-4 text-right text-lg font-bold text-teal-600">
                                            ${{ number_format($totals['total_amount'], 2, ',', '.') }}
                                        </td>
                                    @else
                                        <td colspan="4" class="px-4 py-4 text-sm font-bold text-gray-900 text-right">TOTAL</td>
                                        <td class="px-4 py-4 text-sm font-semibold text-gray-700">{{ $totals['protocol_count'] }} protocolo(s)</td>
                                        <td class="px-4 py-4 text-right text-lg font-bold text-teal-600">
                                            ${{ number_format($totals['total_amount'], 2, ',', '.') }}
                                        </td>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <h3 class="text-sm font-medium text-gray-900">Sin datos</h3>
                        <p class="mt-1 text-sm text-gray-500">No hay protocolos en el período seleccionado.</p>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <h3 class="text-lg font-medium text-gray-900">Generar resumen</h3>
                <p class="mt-2 text-sm text-gray-500">Seleccioná obra social, formato y rango de fechas.</p>
            </div>
        @endif
    </div>
</x-lab-layout>
