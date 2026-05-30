<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <nav class="text-sm text-gray-500 mb-1">
                    <a href="{{ route('purchases.section') }}" class="hover:text-gray-700">Compras</a>
                    <span class="mx-1">›</span>
                    <span class="text-gray-700 font-medium">Formulario 931</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-800">Declaraciones Form 931</h1>
                <p class="text-sm text-gray-500 mt-1">Aportes y contribuciones patronales SUSS/AFIP</p>
            </div>
            @can('form931.manage')
            <a href="{{ route('form931-declarations.create') }}" class="mt-3 md:mt-0 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Nueva DDJJ 931
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
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Año</label>
                <input type="number" name="year" value="{{ request('year') }}" class="w-28 rounded-lg border-gray-300 text-sm" min="2000" max="2100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Mes</label>
                <select name="month" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Todos</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected((int) request('month') === $m)>{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aportes</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Contribuciones</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($declarations as $d)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $d->period_label }}</td>
                                <td class="px-6 py-4 text-sm text-right text-gray-600">${{ number_format($d->amount_aportes_patronales, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-sm text-right text-gray-600">${{ number_format($d->amount_contribuciones_patronales, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-sm text-right font-medium">${{ number_format($d->total, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($d->status === 'draft')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Borrador</span>
                                    @elseif($d->status === 'confirmed')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Confirmada</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Anulada</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('form931-declarations.show', $d) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-gray-500 text-sm">No hay declaraciones Form 931.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($declarations->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">{{ $declarations->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
