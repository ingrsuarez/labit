<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <nav class="text-sm text-gray-500 mb-1">
                    <a href="{{ route('accounting.section') }}" class="hover:text-gray-700">Contabilidad</a>
                    <span class="mx-1">›</span>
                    <span class="text-gray-700 font-medium">Declaraciones juradas</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-800">Declaraciones juradas de impuestos</h1>
            </div>
            @can('tax-returns.manage')
            <a href="{{ route('tax-returns.create') }}" class="mt-3 md:mt-0 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Nueva DDJJ
            </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 mb-6 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Impuesto</label>
                <select name="tax_id" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todos</option>
                    @foreach($taxes as $t)
                        <option value="{{ $t->id }}" @selected(request('tax_id') == $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Año</label>
                <input type="number" name="year" value="{{ request('year') }}" class="w-28 rounded-lg border-gray-300 text-sm" min="2000" max="2100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Estado</label>
                <select name="status" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todos</option>
                    <option value="draft" @selected(request('status') === 'draft')>Borrador</option>
                    <option value="confirmed" @selected(request('status') === 'confirmed')>Confirmada</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Anulada</option>
                </select>
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-800 rounded-lg text-sm hover:bg-gray-200">Filtrar</button>
        </form>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Impuesto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Declarado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aplicado</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($returns as $r)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $r->tax->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $r->period_label }}</td>
                                <td class="px-6 py-4 text-sm text-right">${{ number_format($r->declared_amount, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-sm text-right">${{ number_format($r->applied_total, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($r->status === 'draft')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Borrador</span>
                                    @elseif($r->status === 'confirmed')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Confirmada</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Anulada</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('tax-returns.show', $r) }}" class="text-indigo-600 hover:underline text-sm">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No hay declaraciones.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($returns->hasPages())
                <div class="px-6 py-4 border-t">{{ $returns->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
