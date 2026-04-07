<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Estadísticas — servicios de compra</h1>
                <p class="text-gray-500 text-sm mt-1">Totales por categoría y por servicio (facturas no anuladas, empresa activa)</p>
            </div>
            <a href="{{ route('purchase-services.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Servicios</a>
        </div>

        <form method="GET" action="{{ route('purchase-services.statistics') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800">Aplicar</button>
        </form>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-800">Por categoría</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Categoría</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Líneas</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Neto</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">IVA</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($byCategory as $row)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $row['category_name'] }}</td>
                                    <td class="px-4 py-2 text-right text-gray-600">{{ $row['lines'] }}</td>
                                    <td class="px-4 py-2 text-right text-gray-700">${{ number_format($row['neto'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right text-gray-700">${{ number_format($row['iva'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-gray-900">${{ number_format($row['total'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">Sin datos en el período</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-800">Por servicio</h2>
                </div>
                <div class="overflow-x-auto max-h-[480px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Servicio</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Categoría</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Líneas</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($byService as $row)
                                <tr>
                                    <td class="px-4 py-2 text-gray-800">
                                        @if($row['code'])
                                            <span class="font-mono text-xs text-gray-400">{{ $row['code'] }}</span>
                                        @endif
                                        {{ $row['name'] }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-600">{{ $row['category'] }}</td>
                                    <td class="px-4 py-2 text-right text-gray-600">{{ $row['lines'] }}</td>
                                    <td class="px-4 py-2 text-right font-medium text-gray-900">${{ number_format($row['total'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-400">Sin datos en el período</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
