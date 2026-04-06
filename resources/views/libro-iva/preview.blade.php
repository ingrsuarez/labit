<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="max-w-4xl mx-auto">
            <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <a href="{{ route('libro-iva.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm flex items-center mb-2">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Cambiar período
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Libro IVA Digital</h1>
                    @php
                        $periodo = \Carbon\Carbon::create($year, $month)->locale('es')->isoFormat('MMMM YYYY');
                    @endphp
                    <p class="text-gray-500 mt-1">Período: <span class="font-semibold text-gray-700 capitalize">{{ $periodo }}</span></p>
                </div>
                @if($company)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        {{ $company->name }}
                    </span>
                @endif
            </div>

            {{-- Tarjetas de resumen --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Ventas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $ventasCount }}</p>
                    <p class="text-sm text-gray-500">comprobantes</p>
                    <p class="text-sm font-semibold text-green-600 mt-1">${{ number_format($ventasTotal, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Notas de Crédito</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $ncCount }}</p>
                    <p class="text-sm text-gray-500">comprobantes</p>
                    <p class="text-sm font-semibold text-red-600 mt-1">-${{ number_format($ncTotal, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Compras</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $comprasCount }}</p>
                    <p class="text-sm text-gray-500">comprobantes</p>
                    <p class="text-sm font-semibold text-blue-600 mt-1">${{ number_format($comprasTotal, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Débito Fiscal</p>
                    <p class="text-2xl font-bold text-green-700 mt-1">${{ number_format($debitoFiscal, 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-500 uppercase tracking-wider mt-3">Crédito Fiscal</p>
                    <p class="text-2xl font-bold text-blue-700">${{ number_format($creditoFiscal, 2, ',', '.') }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-amber-200 p-5 mb-6">
                <h3 class="text-sm font-semibold text-amber-900 uppercase tracking-wider mb-2">Retenciones IVA sufridas en cobranzas</h3>
                <p class="text-xs text-amber-800/80 mb-3">Suma de certificados de retención IVA registrados en recibos de cobro <strong>confirmados</strong> con fecha en este período (no incluido en los TXT de Libro IVA Digital).</p>
                <div class="flex flex-wrap items-baseline gap-4">
                    <p class="text-2xl font-bold text-amber-900">${{ number_format($retencionesIvaCobranzas, 2, ',', '.') }}</p>
                    <span class="text-sm text-amber-800">{{ $retencionesIvaCobranzasCount }} {{ $retencionesIvaCobranzasCount === 1 ? 'línea' : 'líneas' }}</span>
                </div>
            </div>

            {{-- Saldo técnico --}}
            @php
                $saldo = $debitoFiscal - $creditoFiscal;
                $aFavor = $saldo >= 0 ? 'AFIP' : 'contribuyente';
                $saldoColor = $saldo >= 0 ? 'red' : 'green';
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="text-center">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Saldo Técnico IVA</p>
                    <p class="text-3xl font-bold text-{{ $saldoColor }}-700 mt-2">
                        ${{ number_format(abs($saldo), 2, ',', '.') }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        A favor de <span class="font-semibold">{{ $aFavor }}</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Débito (${{ number_format($debitoFiscal, 2, ',', '.') }}) − Crédito (${{ number_format($creditoFiscal, 2, ',', '.') }})</p>
                </div>
            </div>

            @if($ventasCount === 0 && $comprasCount === 0 && $ncCount === 0)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-6">
                    No se encontraron comprobantes para este período. Los archivos TXT estarán vacíos.
                </div>
            @endif

            {{-- Botón de descarga --}}
            <form action="{{ route('libro-iva.download') }}" method="POST">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <button type="submit"
                        class="w-full px-6 py-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium text-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Descargar archivos TXT (.zip)
                </button>
            </form>
        </div>
    </div>
</x-admin-layout>
