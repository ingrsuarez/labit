<x-lab-layout title="Resumen por período — Muestras">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Resumen por período</h1>
            <p class="mt-1 text-sm text-gray-600">Aguas y alimentos — un protocolo por fila</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <form action="{{ route('sample.billing-summary') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="w-full sm:w-64">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <select name="customer_id" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">Seleccionar...</option>
                        @foreach($customers as $cust)
                            <option value="{{ $cust->id }}" {{ (string) $customerId === (string) $cust->id ? 'selected' : '' }}>
                                {{ $cust->displayName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                <div class="w-40">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition-colors">
                    Generar resumen
                </button>
            </form>
        </div>

        @if($rows !== null)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border p-4">
                    <p class="text-sm text-gray-500">Protocolos</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totals['protocol_count'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border p-4">
                    <p class="text-sm text-gray-500">Total período</p>
                    <p class="text-2xl font-bold text-cyan-600">${{ number_format($totals['total_amount'], 2, ',', '.') }}</p>
                </div>
            </div>

            @php $exportQuery = http_build_query(['customer_id' => $customerId, 'date_from' => $dateFrom, 'date_to' => $dateTo]); @endphp
            <div class="mb-4 flex flex-wrap justify-end gap-2">
                <a href="{{ route('sample.billing-summary.exportPdf') }}?{{ $exportQuery }}" class="inline-flex items-center px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800 text-sm">
                    <i class="bi bi-file-earmark-pdf mr-2"></i> Exportar PDF
                </a>
                <a href="{{ route('sample.billing-summary.exportExcel') }}?{{ $exportQuery }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                    <i class="bi bi-file-earmark-excel mr-2"></i> Exportar Excel
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b bg-cyan-50">
                    <h2 class="text-lg font-semibold text-cyan-900">
                        {{ $selectedCustomer->displayName() }}
                        <span class="text-sm font-normal text-gray-500">— {{ $periodLabel }}</span>
                    </h2>
                </div>
                @if($rows->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Muestra</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Determinaciones</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($rows as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-600">{{ $row['formatted_date'] }}</td>
                                        <td class="px-4 py-3 text-gray-900">{{ $row['name'] }}</td>
                                        <td class="px-4 py-3 font-mono text-xs max-w-md truncate" title="{{ $row['codes'] }}">{{ $row['codes'] }}</td>
                                        <td class="px-4 py-3 text-right font-medium">${{ number_format($row['price'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-cyan-50">
                                <tr>
                                    <td colspan="2" class="px-4 py-4 text-sm font-bold text-right">TOTAL</td>
                                    <td class="px-4 py-4 text-sm font-semibold">{{ $totals['protocol_count'] }} protocolo(s)</td>
                                    <td class="px-4 py-4 text-right text-lg font-bold text-cyan-600">${{ number_format($totals['total_amount'], 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="p-12 text-center text-sm text-gray-500">No hay protocolos en el período.</div>
                @endif
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border p-12 text-center text-sm text-gray-500">
                Seleccioná cliente y fechas para generar el resumen.
            </div>
        @endif
    </div>
</x-lab-layout>
