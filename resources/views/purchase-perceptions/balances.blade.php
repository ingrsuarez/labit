<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="mb-6">
            <nav class="text-sm text-gray-500 mb-1">
                <a href="{{ route('purchase-invoices.index') }}" class="hover:text-gray-700">Compras</a>
                <span class="mx-1">›</span>
                <a href="{{ route('purchase-perceptions.index') }}" class="hover:text-gray-700">Percepciones</a>
                <span class="mx-1">›</span>
                <span class="text-gray-700 font-medium">Saldos</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-800">Saldos de percepciones sufridas</h1>
            <p class="text-gray-500 text-sm mt-1">Anticipos cargados en facturas vs. saldo en cuenta contable (Libro Mayor)</p>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('purchase-perceptions.balances') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Desde</label>
                    <input type="date" name="from" value="{{ $from }}" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Hasta</label>
                    <input type="date" name="to" value="{{ $to }}" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="bi bi-search me-1"></i> Consultar
                </button>
            </div>
        </form>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($balances->isEmpty())
                <div class="p-12 text-center">
                    <i class="bi bi-percent text-4xl text-gray-300"></i>
                    <p class="mt-3 text-gray-500">No hay percepciones configuradas para esta empresa.</p>
                    @can('purchase-perceptions.create')
                    <a href="{{ route('purchase-perceptions.index') }}" class="mt-4 inline-flex items-center text-indigo-600 hover:underline text-sm">
                        Configurar percepciones →
                    </a>
                    @endcan
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percepción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jurisdicción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuenta contable</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Anticipos cargados</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Imputado (DDJJ)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Disponible</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Saldo en cuenta (Libro Mayor)
                                    <span class="ml-1 text-gray-400" title="Calculado desde asientos contables del período seleccionado.">
                                        <i class="bi bi-info-circle"></i>
                                    </span>
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php $totalAnticipos = 0; $totalImputado = 0; $totalDisponible = 0; $totalSaldo = 0; @endphp
                            @foreach($balances as $row)
                                @php
                                    $totalAnticipos += $row['anticipos_cargados'];
                                    $totalImputado += $row['imputado'];
                                    $totalDisponible += $row['disponible'];
                                    $totalSaldo += $row['saldo_cuenta'];
                                    $esCero = abs($row['diferencia']) < 0.01;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['perception']->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['perception']->jurisdiction ?: '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        @if($row['perception']->accountingAccount)
                                            <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded mr-1">{{ $row['perception']->accountingAccount->code }}</span>
                                            {{ $row['perception']->accountingAccount->name }}
                                        @else
                                            <span class="text-red-500 text-xs">Sin cuenta</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right font-medium text-gray-800">
                                        ${{ number_format($row['anticipos_cargados'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right font-medium text-gray-800">
                                        ${{ number_format($row['imputado'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right font-medium text-gray-800">
                                        ${{ number_format($row['disponible'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right font-medium text-gray-800">
                                        ${{ number_format($row['saldo_cuenta'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($esCero)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">OK</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                                                title="Diferencia: ${{ number_format(abs($row['diferencia']), 2, ',', '.') }}">
                                                Revisar
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-sm font-semibold text-gray-700">Totales</td>
                                <td class="px-6 py-3 text-sm text-right font-bold text-gray-900">
                                    ${{ number_format($totalAnticipos, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-bold text-gray-900">
                                    ${{ number_format($totalImputado, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-bold text-gray-900">
                                    ${{ number_format($totalDisponible, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-bold text-gray-900">
                                    ${{ number_format($totalSaldo, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-3 text-center text-gray-400">—</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
