<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $invoice->full_number }}</h1>
                    <p class="text-gray-500 text-sm mt-1">Factura de Compra</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $invoice->status_color }}-100 text-{{ $invoice->status_color }}-700">
                    {{ $invoice->status_label }}
                </span>
            </div>
            <div class="flex items-center gap-3 mt-3 md:mt-0">
                @if($invoice->status === 'pendiente')
                    <a href="{{ route('purchase-invoices.edit', $invoice) }}"
                       class="inline-flex items-center px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">Editar</a>
                @endif
                <a href="{{ route('purchase-invoices.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        @if(!$invoice->delivery_note_id && $invoice->items->whereNotNull('supply_id')->count() > 0)
            <div class="flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-2 mb-6">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span>Esta factura generó entrada de stock para {{ $invoice->items->whereNotNull('supply_id')->count() }} insumo(s).</span>
            </div>
        @elseif($invoice->delivery_note_id)
            <div class="flex items-center gap-2 text-sm text-blue-700 bg-blue-50 border border-blue-200 rounded-lg px-4 py-2 mb-6">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                </svg>
                <span>El stock fue actualizado por el remito asociado.</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Datos del Comprobante</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Proveedor</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $invoice->supplier->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Tipo Comprobante</dt>
                        <dd class="text-sm font-medium text-gray-800">Factura {{ $invoice->voucher_type }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Punto de Venta</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $invoice->point_of_sale ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">N° Factura</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $invoice->invoice_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Remito</dt>
                        <dd class="text-sm font-medium text-gray-800">
                            @if($invoice->deliveryNote)
                                <a href="{{ route('delivery-notes.show', $invoice->deliveryNote) }}" class="text-zinc-700 hover:text-zinc-900 underline">
                                    {{ $invoice->deliveryNote->number }}
                                </a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Orden de Compra</dt>
                        <dd class="text-sm font-medium text-gray-800">
                            @if($invoice->purchaseOrder)
                                <a href="{{ route('purchase-orders.show', $invoice->purchaseOrder) }}" class="text-zinc-700 hover:text-zinc-900 underline">
                                    {{ $invoice->purchaseOrder->number }}
                                </a>
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
                        <dd class="text-sm font-medium {{ $invoice->due_date && $invoice->due_date->isPast() && !in_array($invoice->status, ['pagada', 'anulada']) ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}
                            @if($invoice->due_date && $invoice->due_date->isPast() && !in_array($invoice->status, ['pagada', 'anulada']))
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

        @if($invoice->cae)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <h4 class="text-sm font-semibold text-indigo-800 mb-2 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    Datos fiscales (QR)
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div>
                        <span class="text-gray-500">CAE:</span>
                        <span class="font-medium">{{ $invoice->cae }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">CUIT Emisor:</span>
                        <span class="font-medium">{{ $invoice->cuit_emisor }}</span>
                    </div>
                    @if($invoice->qr_data)
                        <div>
                            <span class="text-gray-500">Fecha QR:</span>
                            <span class="font-medium">{{ $invoice->qr_data['fecha'] ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Importe QR:</span>
                            <span class="font-medium">${{ number_format($invoice->qr_data['importe'] ?? 0, 2, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
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
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Insumo</th>
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
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @if($item->supply)
                                        <span class="font-mono text-gray-400 text-xs">{{ $item->supply->code }}</span>
                                        <span class="ml-1">{{ $item->supply->name }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
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
                            <dt class="text-gray-600">Pagado</dt>
                            <dd class="font-medium text-green-600">${{ number_format($invoice->amount_paid, 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between text-base font-bold border-t border-gray-300 pt-2 mt-2">
                            <dt class="{{ $invoice->balance > 0 ? 'text-amber-700' : 'text-green-700' }}">SALDO</dt>
                            <dd class="{{ $invoice->balance > 0 ? 'text-amber-700' : 'text-green-700' }}">${{ number_format($invoice->balance, 2, ',', '.') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        @if($invoice->paymentOrderItems->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Historial de Pagos</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Orden de Pago</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($invoice->paymentOrderItems as $paymentItem)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('payment-orders.show', $paymentItem->paymentOrder) }}" class="text-zinc-700 font-semibold hover:text-zinc-900 underline">
                                            {{ $paymentItem->paymentOrder->number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $paymentItem->paymentOrder->date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-800 text-right">${{ number_format($paymentItem->amount, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($invoice->balance > 0 && !in_array($invoice->status, ['anulada']))
            <div class="flex justify-end mb-6">
                <a href="{{ route('payment-orders.create', ['supplier_id' => $invoice->supplier_id]) }}"
                   class="inline-flex items-center px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Generar Orden de Pago
                </a>
            </div>
        @endif
    </div>
</x-admin-layout>
