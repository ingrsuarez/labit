<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $creditNote->full_number }}</h1>
                    <p class="text-gray-500 text-sm mt-1">Nota de crédito de proveedor</p>
                </div>
            </div>
            <div class="flex items-center gap-3 mt-3 md:mt-0">
                @can('purchase-credit-notes.delete')
                    <form method="POST" action="{{ route('purchase-credit-notes.destroy', $creditNote) }}" onsubmit="return confirm('¿Eliminar esta nota de crédito? Se revertirá el saldo de la factura (si aplica) y el asiento contable.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-300 text-red-700 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors">Eliminar</button>
                    </form>
                @endcan
                <a href="{{ route('purchase-credit-notes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Datos</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500">Proveedor</dt>
                        <dd class="text-sm font-medium text-gray-800 text-right">{{ $creditNote->supplier->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500">Sede</dt>
                        <dd class="text-sm font-medium text-gray-800 text-right">{{ $creditNote->labBranch?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500">Factura aplicada</dt>
                        <dd class="text-sm font-medium text-gray-800 text-right">
                            @if($creditNote->purchase_invoice_id)
                                <a href="{{ route('purchase-invoices.show', $creditNote->purchase_invoice_id) }}" class="text-zinc-700 underline">{{ $creditNote->purchaseInvoice?->full_number }}</a>
                            @else
                                <span class="text-amber-700">Sin aplicar (solo cuenta corriente)</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-gray-500">Registró</dt>
                        <dd class="text-sm text-gray-800 text-right">{{ $creditNote->creator?->name ?? '—' }}</dd>
                    </div>
                    @if($creditNote->notes)
                        <div class="pt-2 border-t border-gray-100">
                            <dt class="text-sm text-gray-500 mb-1">Notas</dt>
                            <dd class="text-sm text-gray-800">{{ $creditNote->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Totales</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-600">Subtotal</dt><dd class="font-medium">${{ number_format($creditNote->subtotal, 2, ',', '.') }}</dd></div>
                    @if($creditNote->iva_10_5 > 0)
                        <div class="flex justify-between"><dt class="text-gray-600">IVA 10,5%</dt><dd>${{ number_format($creditNote->iva_10_5, 2, ',', '.') }}</dd></div>
                    @endif
                    @if($creditNote->iva_21 > 0)
                        <div class="flex justify-between"><dt class="text-gray-600">IVA 21%</dt><dd>${{ number_format($creditNote->iva_21, 2, ',', '.') }}</dd></div>
                    @endif
                    @if($creditNote->iva_27 > 0)
                        <div class="flex justify-between"><dt class="text-gray-600">IVA 27%</dt><dd>${{ number_format($creditNote->iva_27, 2, ',', '.') }}</dd></div>
                    @endif
                    @if($creditNote->percepciones > 0)
                        <div class="flex justify-between"><dt class="text-gray-600">Percepciones</dt><dd>${{ number_format($creditNote->percepciones, 2, ',', '.') }}</dd></div>
                    @endif
                    @if($creditNote->otros_impuestos > 0)
                        <div class="flex justify-between"><dt class="text-gray-600">Otros</dt><dd>${{ number_format($creditNote->otros_impuestos, 2, ',', '.') }}</dd></div>
                    @endif
                    <div class="flex justify-between text-base font-bold border-t border-gray-200 pt-2 mt-2">
                        <dt>Total</dt>
                        <dd>${{ number_format($creditNote->total, 2, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Ítems</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cant.</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">P. unit.</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">IVA %</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($creditNote->items as $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-800">{{ $item->description }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700">${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-600">{{ number_format($item->iva_rate, 1, ',', '.') }}%</td>
                                <td class="px-4 py-3 text-sm font-semibold text-right">${{ number_format($item->total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <x-journal-entry-widget :source="$creditNote" />
    </div>
</x-admin-layout>
