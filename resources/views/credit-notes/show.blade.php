<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $creditNote->full_number }}</h1>
                    <p class="text-gray-500 text-sm mt-1">Nota de Crédito</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @if($creditNote->status === 'pendiente') bg-yellow-100 text-yellow-700
                    @elseif($creditNote->status === 'confirmada') bg-green-100 text-green-700
                    @else bg-red-100 text-red-700
                    @endif">
                    {{ $creditNote->status_label }}
                </span>
            </div>
            <div class="flex items-center gap-3 mt-3 md:mt-0">
                <a href="{{ route('credit-notes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
                @if($creditNote->status === 'pendiente' && !$creditNote->cae)
                    <form method="POST" action="{{ route('credit-notes.destroy', $creditNote) }}" class="inline" onsubmit="return confirm('¿Eliminar esta nota de crédito?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            Eliminar
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        @if($creditNote->salesInvoice)
            <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-blue-800">
                        Factura asociada:
                        <a href="{{ route('sales-invoices.show', $creditNote->salesInvoice) }}" class="font-semibold underline hover:text-blue-900">{{ $creditNote->salesInvoice->full_number }}</a>
                    </p>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Datos del Comprobante</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Tipo</dt>
                        <dd class="text-sm font-medium text-gray-800">NC {{ $creditNote->voucher_type }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Punto de Venta</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $creditNote->pointOfSale ? $creditNote->pointOfSale->code . ' - ' . $creditNote->pointOfSale->name : '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Número</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $creditNote->afip_voucher_number ?? $creditNote->credit_note_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Motivo</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $creditNote->reason }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Fechas y Estado</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Fecha Emisión</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $creditNote->issue_date->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Estado</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($creditNote->status === 'pendiente') bg-yellow-100 text-yellow-700
                                @elseif($creditNote->status === 'confirmada') bg-green-100 text-green-700
                                @else bg-red-100 text-red-700
                                @endif">
                                {{ $creditNote->status_label }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Creado por</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $creditNote->creator?->name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Cliente</h3>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Nombre</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $creditNote->customer->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">CUIT</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $creditNote->customer->taxId ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Condición IVA</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $creditNote->customer->tax ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        @if($creditNote->is_electronic)
            @if($creditNote->cae)
                <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-200 p-5 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-indigo-600 uppercase tracking-wider">Datos AFIP</h3>
                    </div>
                    <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <dt class="text-xs text-gray-500 mb-1">CAE</dt>
                            <dd class="text-sm font-mono font-semibold text-gray-800">{{ $creditNote->cae }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 mb-1">Vencimiento CAE</dt>
                            <dd class="text-sm font-medium text-gray-800">{{ $creditNote->cae_expiration?->format('d/m/Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 mb-1">N° Comprobante AFIP</dt>
                            <dd class="text-sm font-mono font-medium text-gray-800">{{ $creditNote->afip_voucher_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 mb-1">Resultado</dt>
                            <dd>
                                @if($creditNote->afip_result === 'A')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aprobado</span>
                                @elseif($creditNote->afip_result === 'O')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Con Observaciones</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Rechazado</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border-2 border-red-200 p-5 mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <h3 class="text-sm font-semibold text-red-600 uppercase tracking-wider">Pendiente de autorización</h3>
                        </div>
                        <form method="POST" action="{{ route('credit-notes.retry-afip', $creditNote) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Reintentar
                            </button>
                        </form>
                    </div>
                    @if($creditNote->afip_response && isset($creditNote->afip_response['error']))
                        <p class="text-sm text-red-700 mt-2">Error: {{ $creditNote->afip_response['error'] }}</p>
                    @endif
                </div>
            @endif
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Ítems de la Nota de Crédito</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cantidad</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">P. Unitario</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">IVA %</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">IVA</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($creditNote->items as $i => $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800 font-medium">{{ $item->description }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800 text-right">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 text-right">${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 text-center">{{ number_format($item->iva_rate, 1, ',', '.') }}%</td>
                                <td class="px-4 py-3 text-sm text-gray-600 text-right">${{ number_format($item->iva_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-800 text-right">${{ number_format($item->total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                <div class="flex justify-end">
                    <dl class="w-72 space-y-1">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-600">Subtotal</dt>
                            <dd class="font-medium text-gray-800">${{ number_format($creditNote->subtotal, 2, ',', '.') }}</dd>
                        </div>
                        @if($creditNote->iva_21 > 0)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600">IVA 21%</dt>
                                <dd class="font-medium text-gray-800">${{ number_format($creditNote->iva_21, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                        @if($creditNote->iva_10_5 > 0)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600">IVA 10,5%</dt>
                                <dd class="font-medium text-gray-800">${{ number_format($creditNote->iva_10_5, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                        @if($creditNote->iva_27 > 0)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600">IVA 27%</dt>
                                <dd class="font-medium text-gray-800">${{ number_format($creditNote->iva_27, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                        @if($creditNote->percepciones > 0)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600">Percepciones</dt>
                                <dd class="font-medium text-gray-800">${{ number_format($creditNote->percepciones, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                        @if($creditNote->otros_impuestos > 0)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600">Otros Impuestos</dt>
                                <dd class="font-medium text-gray-800">${{ number_format($creditNote->otros_impuestos, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between text-base font-bold border-t border-gray-300 pt-2 mt-2">
                            <dt class="text-gray-800">TOTAL</dt>
                            <dd class="text-gray-900">${{ number_format($creditNote->total, 2, ',', '.') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
