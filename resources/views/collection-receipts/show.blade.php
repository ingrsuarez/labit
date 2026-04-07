<x-admin-layout>
    <div class="p-4 md:p-6">
        @php
            $statusColors = [
                'borrador' => 'bg-gray-100 text-gray-700',
                'confirmado' => 'bg-green-100 text-green-700',
                'anulado' => 'bg-red-100 text-red-700',
            ];
            $paymentMethodLabels = [
                'transferencia' => 'Transferencia',
                'cheque' => 'Cheque / e-cheq (legado)',
                'efectivo' => 'Efectivo',
                'tarjeta' => 'Tarjeta',
                'deposito' => 'Depósito',
            ];
        @endphp

        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $collectionReceipt->number }}</h1>
                    <p class="text-gray-500 text-sm mt-1">Recibo de Cobro</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$collectionReceipt->status] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ $collectionReceipt->status_label }}
                </span>
            </div>
            <div class="flex flex-wrap items-center gap-3 mt-3 md:mt-0">
                <a href="{{ route('collection-receipts.pdf', $collectionReceipt) }}"
                   title="Descargar recibo de cobro en PDF"
                   class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    PDF
                </a>
                @if($collectionReceipt->status === 'borrador')
                    <a href="{{ route('collection-receipts.edit', $collectionReceipt) }}"
                       class="inline-flex items-center px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">Editar</a>
                @endif
                <a href="{{ route('collection-receipts.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        @if($collectionReceipt->status === 'confirmado')
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium text-green-700">Cobro confirmado</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Información General</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Cliente</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $collectionReceipt->customer->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Fecha</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $collectionReceipt->date->format('d/m/Y') }}</dd>
                    </div>
                    @if($collectionReceipt->payments->isEmpty())
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Método de Pago (legado)</dt>
                            <dd class="text-sm font-medium text-gray-800">{{ $paymentMethodLabels[$collectionReceipt->payment_method] ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Referencia de Pago</dt>
                            <dd class="text-sm font-medium text-gray-800">{{ $collectionReceipt->payment_reference ?? '-' }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Estado y Seguimiento</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Creado por</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $collectionReceipt->creator->name }}</dd>
                    </div>
                    @if($collectionReceipt->confirmer)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Confirmado por</dt>
                            <dd class="text-sm font-medium text-gray-800">{{ $collectionReceipt->confirmer->name }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Estado</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$collectionReceipt->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $collectionReceipt->status_label }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        @if($collectionReceipt->payments->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Medios de pago</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Importe</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($collectionReceipt->payments as $pay)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $pay->line_type_label }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-800 text-right">${{ number_format($pay->amount, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if($pay->line_type === 'transferencia')
                                            {{ $pay->bankAccount?->display_name ?? '—' }}
                                        @elseif($pay->line_type === 'echeq')
                                            N° {{ $pay->cheque_number }} — {{ $pay->bank_name }} — vto. {{ $pay->due_date?->format('d/m/Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($collectionReceipt->withholdings->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Retenciones sufridas</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nº doc.</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Régimen</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jurisdicción</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nº certif.</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($collectionReceipt->withholdings as $wh)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ \App\Models\CollectionReceiptWithholding::typeLabel($wh->withholding_type) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $wh->document_number ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $wh->regime }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $wh->jurisdiction ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $wh->certificate_number }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-800 text-right">${{ number_format($wh->amount, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-right text-sm font-bold text-gray-800">Total retenciones</td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">${{ number_format($collectionReceipt->withholdings->sum('amount'), 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Facturas Incluidas</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">N° Factura Venta</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($collectionReceipt->items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('sales-invoices.show', $item->invoice) }}" class="text-zinc-700 font-semibold hover:text-zinc-900 underline">
                                        {{ $item->invoice->full_number }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-800 text-right">${{ number_format($item->amount, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                <div class="flex justify-end">
                    <div class="flex justify-between w-64">
                        <span class="text-sm font-bold text-gray-800">Total</span>
                        <span class="text-base font-bold text-gray-900">${{ number_format($collectionReceipt->total, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <x-journal-entry-widget :source="$collectionReceipt" />

        @if($collectionReceipt->notes)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Notas</h3>
                <p class="text-sm text-gray-600">{{ $collectionReceipt->notes }}</p>
            </div>
        @endif

        @if($collectionReceipt->status === 'borrador')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm text-amber-700 font-medium">Al confirmar se actualizarán los saldos de las facturas de venta.</p>
                        <p class="text-xs text-amber-600 mt-1">Esta acción no se puede deshacer.</p>
                    </div>
                    <form method="POST" action="{{ route('collection-receipts.confirm', $collectionReceipt) }}">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('¿Está seguro de confirmar este cobro? Se actualizarán los saldos de las facturas de venta.')"
                                class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 transition-colors text-sm shadow-sm">
                            Confirmar Cobro
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
