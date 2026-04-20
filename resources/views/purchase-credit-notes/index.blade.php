<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Notas de crédito de proveedores</h1>
                <p class="text-gray-500 text-sm mt-1">Comprobantes recibidos que reducen deuda y generan asiento contable</p>
            </div>
            @can('purchase-credit-notes.create')
                <a href="{{ route('purchase-credit-notes.create') }}"
                   class="mt-3 md:mt-0 inline-flex items-center px-4 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva nota de crédito
                </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        <form method="GET" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3 md:items-end">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Número o proveedor..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div class="w-full md:w-64">
                <label class="block text-xs font-medium text-gray-500 mb-1">Proveedor</label>
                <select name="supplier_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    <option value="">Todos</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-zinc-800 text-white rounded-lg text-sm font-medium hover:bg-zinc-700">Filtrar</button>
        </form>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Comprobante</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Proveedor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aplicación</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($creditNotes as $cn)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $cn->issue_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $cn->full_number }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $cn->supplier->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @if($cn->purchase_invoice_id)
                                        <a href="{{ route('purchase-invoices.show', $cn->purchase_invoice_id) }}" class="text-zinc-700 underline hover:text-zinc-900">{{ $cn->purchaseInvoice?->full_number ?? 'FC #'.$cn->purchase_invoice_id }}</a>
                                    @else
                                        <span class="text-amber-700 font-medium">Sin aplicar a factura</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">${{ number_format($cn->total, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('purchase-credit-notes.show', $cn) }}" class="text-sm text-zinc-700 hover:text-zinc-900 font-medium">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500 text-sm">No hay notas de crédito registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($creditNotes->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">{{ $creditNotes->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
