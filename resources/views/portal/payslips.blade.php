<x-portal-layout title="Mis Recibos">
    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Mis Recibos de Sueldo</h1>
                    <p class="text-sm text-gray-500">Descarga tus recibos de sueldo cerrados</p>
                </div>
                <div>
                    <a href="{{ route('portal.dashboard') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver
                    </a>
                </div>
            </div>

            {{-- Lista de recibos --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                        Últimos Recibos
                    </h2>
                </div>
                
                <div class="divide-y divide-gray-200">
                    @forelse($payslips as $payslip)
                        @php
                            $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                      'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                            $mesNombre = $meses[$payslip->month] ?? $payslip->month;
                        @endphp
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                                        <span class="text-indigo-600 font-bold text-lg">{{ str_pad($payslip->month, 2, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $mesNombre }} {{ $payslip->year }}</h3>
                                        <p class="text-sm text-gray-500">{{ $payslip->period_label ?? 'Liquidación mensual' }}</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">Neto a cobrar</p>
                                        <p class="text-xl font-bold text-green-600">${{ number_format($payslip->neto_a_cobrar, 2, ',', '.') }}</p>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        @if($payslip->status === 'pagado')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                                Pagado
                                            </span>
                                        @else
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                                                Liquidado
                                            </span>
                                        @endif
                                        
                                        <a href="{{ route('portal.payslips.download', $payslip) }}" 
                                           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Descargar PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Detalles adicionales --}}
                            <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Total Haberes</p>
                                    <p class="font-medium text-gray-900">${{ number_format($payslip->total_haberes, 2, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Deducciones</p>
                                    <p class="font-medium text-red-600">-${{ number_format($payslip->total_deducciones, 2, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Categoría</p>
                                    <p class="font-medium text-gray-900">{{ $payslip->category_name ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Antigüedad</p>
                                    <p class="font-medium text-gray-900">{{ $payslip->antiguedad_years ?? 0 }} años</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No hay recibos disponibles</h3>
                            <p class="text-gray-500">Aún no tienes recibos de sueldo cerrados.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Información adicional --}}
            @if($payslips->count() > 0)
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-800">Información</h4>
                            <p class="text-sm text-blue-700 mt-1">
                                Los recibos están disponibles para descarga una vez que han sido liquidados. 
                                Si tienes alguna consulta sobre tu recibo, contacta al área de Recursos Humanos.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-portal-layout>
