<x-admin-layout>
    <div class="p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Cuenta Corriente de Proveedores</h1>
                <p class="text-gray-500 text-sm mt-1">Historial de facturas y pagos por proveedor</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        <!-- Filtros -->
        <form method="GET" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Proveedor <span class="text-red-500">*</span></label>
                    <select name="supplier_id" required
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        <option value="">Seleccionar proveedor...</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>
                                {{ $s->name }}@if($s->tax_id) — {{ $s->tax_id }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit"
                        class="px-4 py-2 bg-zinc-800 text-white rounded-lg text-sm font-medium hover:bg-zinc-700 transition-colors">
                    Consultar
                </button>
                @if($supplier)
                    <a href="{{ route('suppliers.statement.pdf', request()->query()) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Descargar PDF
                    </a>
                @endif
            </div>
        </form>

        @if($supplier)
            <!-- Encabezado del proveedor -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">{{ $supplier->name }}</h2>
                        @if($supplier->tax_id)
                            <p class="text-sm text-gray-500 mt-1">CUIT: {{ $supplier->tax_id }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400">Período consultado</p>
                        <p class="text-sm font-medium text-gray-700">
                            {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') : 'Inicio del año' }}
                            al
                            {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : now()->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tabla de movimientos -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Comprobante</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Detalle</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Debe</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Haber</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- Saldo inicial -->
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 text-gray-400 text-xs">—</td>
                                <td class="px-4 py-2 text-gray-500 text-xs font-medium">Saldo anterior</td>
                                <td class="px-4 py-2 text-gray-400 text-xs">Movimientos previos al período</td>
                                <td class="px-4 py-2"></td>
                                <td class="px-4 py-2"></td>
                                <td class="px-4 py-2 text-right font-mono text-gray-700 text-xs">
                                    $ {{ number_format(abs($openBalance), 2, ',', '.') }}
                                    @if($openBalance != 0)
                                        <span class="ml-1 text-xs font-semibold {{ $openBalance > 0 ? 'text-green-600' : 'text-orange-500' }}">
                                            {{ $openBalance > 0 ? 'AC' : 'AD' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>

                            <!-- Movimientos del período -->
                            @forelse($movements as $mov)
                                <tr class="hover:bg-gray-50 transition-colors {{ $mov['type'] === 'payment' ? 'bg-green-50/30' : '' }}">
                                    <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $mov['date']->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2.5 font-medium text-gray-800 whitespace-nowrap">
                                        @if($mov['type'] === 'invoice')
                                            <span class="inline-flex items-center gap-1.5">
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                {{ $mov['reference'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5">
                                                <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                                {{ $mov['reference'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ $mov['detail'] }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono text-gray-700">
                                        @if($mov['debe'] > 0)
                                            $ {{ number_format($mov['debe'], 2, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-mono text-gray-700">
                                        @if($mov['haber'] > 0)
                                            $ {{ number_format($mov['haber'], 2, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-mono font-medium whitespace-nowrap">
                                        <span class="{{ $mov['saldo'] >= 0 ? 'text-gray-800' : 'text-orange-600' }}">
                                            $ {{ number_format(abs($mov['saldo']), 2, ',', '.') }}
                                        </span>
                                        <span class="ml-1 text-xs font-semibold {{ $mov['saldo'] >= 0 ? 'text-green-600' : 'text-orange-500' }}">
                                            {{ $mov['saldo'] >= 0 ? 'AC' : 'AD' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-gray-400 text-sm">
                                        Sin movimientos en el período seleccionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t-2 border-gray-200 bg-gray-50">
                            <tr class="font-semibold">
                                <td colspan="3" class="px-4 py-3 text-sm text-gray-600 text-right">Totales del período</td>
                                <td class="px-4 py-3 text-right font-mono text-gray-800">$ {{ number_format($totalDebe, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-mono text-gray-800">$ {{ number_format($totalHaber, 2, ',', '.') }}</td>
                                <td class="px-4 py-3"></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-sm font-semibold text-right text-gray-700">
                                    Saldo final al {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') : now()->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-lg font-bold {{ $closeBalance >= 0 ? 'text-gray-900' : 'text-orange-600' }}">
                                        $ {{ number_format(abs($closeBalance), 2, ',', '.') }}
                                    </span>
                                    <span class="ml-2 px-2 py-1 rounded text-xs font-semibold
                                        {{ $closeBalance >= 0 ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                        {{ $closeBalance >= 0 ? 'Acreedor' : 'Deudor' }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Leyenda -->
                <div class="px-4 py-2 border-t border-gray-100 bg-gray-50">
                    <p class="text-xs text-gray-400">
                        <strong>AC</strong> = Saldo Acreedor (le debemos al proveedor) &nbsp;·&nbsp;
                        <strong>AD</strong> = Saldo Deudor (anticipo o pago en exceso)
                    </p>
                </div>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-3 text-sm font-medium text-gray-800">Seleccioná un proveedor para ver su cuenta corriente</h3>
                <p class="mt-1 text-sm text-gray-500">Podés filtrar por rango de fechas para acotar el período consultado.</p>
            </div>
        @endif
    </div>
</x-admin-layout>
