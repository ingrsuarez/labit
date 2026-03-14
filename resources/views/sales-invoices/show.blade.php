<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $invoice->full_number }}</h1>
                    <p class="text-gray-500 text-sm mt-1">Factura de Venta</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $invoice->status_color }}-100 text-{{ $invoice->status_color }}-700">
                    {{ $invoice->status_label }}
                </span>
            </div>
            <div class="flex items-center gap-3 mt-3 md:mt-0">
                @if($invoice->status === 'pendiente' && !($invoice->is_electronic && $invoice->cae))
                    <a href="{{ route('sales-invoices.edit', $invoice) }}"
                       class="inline-flex items-center px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">Editar</a>
                @endif
                <a href="{{ route('sales-invoices.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Datos del Comprobante</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Cliente</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $invoice->customer->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Tipo Comprobante</dt>
                        <dd class="text-sm font-medium text-gray-800">Factura {{ $invoice->voucher_type }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Punto de Venta</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $invoice->pointOfSale ? $invoice->pointOfSale->code . ' - ' . $invoice->pointOfSale->name : ($invoice->point_of_sale ?? '-') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">N° Factura</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $invoice->invoice_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Presupuesto</dt>
                        <dd class="text-sm font-medium text-gray-800">
                            @if($invoice->quote)
                                <a href="{{ route('quotes.show', $invoice->quote) }}" class="text-zinc-700 hover:text-zinc-900 underline">
                                    {{ $invoice->quote->number }}
                                </a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Admisión</dt>
                        <dd class="text-sm font-medium text-gray-800">
                            @if($invoice->admission_id)
                                #{{ $invoice->admission_id }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Fechas y Estado</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Fecha Emisión</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $invoice->issue_date->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Fecha Vencimiento</dt>
                        <dd class="text-sm font-medium {{ $invoice->due_date && $invoice->due_date->isPast() && !in_array($invoice->status, ['cobrada', 'anulada']) ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}
                            @if($invoice->due_date && $invoice->due_date->isPast() && !in_array($invoice->status, ['cobrada', 'anulada']))
                                <span class="text-xs ml-1">(vencida)</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Creado por</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $invoice->creator?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Estado</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $invoice->status_color }}-100 text-{{ $invoice->status_color }}-700">
                                {{ $invoice->status_label }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        @if($invoice->notes)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <p class="text-sm text-gray-600"><span class="font-medium">Notas:</span> {{ $invoice->notes }}</p>
            </div>
        @endif

        @if($invoice->is_electronic)
            @if($invoice->cae)
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
                            <dd class="text-sm font-mono font-semibold text-gray-800">{{ $invoice->cae }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 mb-1">Vencimiento CAE</dt>
                            <dd class="text-sm font-medium text-gray-800">{{ $invoice->cae_expiration?->format('d/m/Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 mb-1">N° Comprobante AFIP</dt>
                            <dd class="text-sm font-mono font-medium text-gray-800">{{ $invoice->afip_voucher_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 mb-1">Resultado</dt>
                            <dd>
                                @if($invoice->afip_result === 'A')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aprobado</span>
                                @elseif($invoice->afip_result === 'O')
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
                            <h3 class="text-sm font-semibold text-red-600 uppercase tracking-wider">Factura electrónica pendiente de autorización</h3>
                        </div>
                        <form method="POST" action="{{ route('sales-invoices.retry-afip', $invoice) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Reintentar
                            </button>
                        </form>
                    </div>
                    @if($invoice->afip_response && isset($invoice->afip_response['error']))
                        <p class="text-sm text-red-700 mt-2">Error: {{ $invoice->afip_response['error'] }}</p>
                    @endif
                </div>
            @endif
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Ítems de la Factura</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cantidad</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Precio Unit.</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Tasa IVA</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">IVA</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($invoice->items as $item)
                            <tr class="hover:bg-gray-50">
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
                            <dd class="font-medium text-gray-800">${{ number_format($invoice->subtotal, 2, ',', '.') }}</dd>
                        </div>
                        @if($invoice->iva_10_5 > 0)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600">IVA 10,5%</dt>
                                <dd class="font-medium text-gray-800">${{ number_format($invoice->iva_10_5, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                        @if($invoice->iva_21 > 0)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600">IVA 21%</dt>
                                <dd class="font-medium text-gray-800">${{ number_format($invoice->iva_21, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                        @if($invoice->iva_27 > 0)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600">IVA 27%</dt>
                                <dd class="font-medium text-gray-800">${{ number_format($invoice->iva_27, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                        @if($invoice->percepciones > 0)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600">Percepciones</dt>
                                <dd class="font-medium text-gray-800">${{ number_format($invoice->percepciones, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                        @if($invoice->otros_impuestos > 0)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600">Otros Impuestos</dt>
                                <dd class="font-medium text-gray-800">${{ number_format($invoice->otros_impuestos, 2, ',', '.') }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between text-base font-bold border-t border-gray-300 pt-2 mt-2">
                            <dt class="text-gray-800">TOTAL</dt>
                            <dd class="text-gray-900">${{ number_format($invoice->total, 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-600">Cobrado</dt>
                            <dd class="font-medium text-green-600">${{ number_format($invoice->amount_collected, 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between text-base font-bold border-t border-gray-300 pt-2 mt-2">
                            <dt class="{{ $invoice->balance > 0 ? 'text-amber-700' : 'text-green-700' }}">SALDO</dt>
                            <dd class="{{ $invoice->balance > 0 ? 'text-amber-700' : 'text-green-700' }}">${{ number_format($invoice->balance, 2, ',', '.') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        @if($invoice->collectionReceiptItems->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Historial de Cobros</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Recibo de Cobro</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($invoice->collectionReceiptItems as $collectionItem)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('collection-receipts.show', $collectionItem->collectionReceipt) }}" class="text-zinc-700 font-semibold hover:text-zinc-900 underline">
                                            {{ $collectionItem->collectionReceipt->number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $collectionItem->collectionReceipt->date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-800 text-right">${{ number_format($collectionItem->amount, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($invoice->balance > 0 && !in_array($invoice->status, ['anulada']))
            <div class="flex justify-end mb-6">
                <a href="{{ route('collection-receipts.create', ['customer_id' => $invoice->customer_id]) }}"
                   class="inline-flex items-center px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Generar Recibo de Cobro
                </a>
            </div>
        @endif
    </div>
</x-admin-layout>
