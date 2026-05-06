<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <nav class="text-sm text-gray-500 mb-1">
                    <a href="{{ route('purchases.section') }}" class="hover:text-gray-700">Compras</a>
                    <span class="mx-1">›</span>
                    <span class="text-gray-700 font-medium">Impuestos</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-800">Catálogo de impuestos</h1>
                <p class="text-gray-500 text-sm mt-1">Definición de impuestos para DDJJ y cuenta de pasivo asociada</p>
            </div>
            @can('taxes.manage')
            <a href="{{ route('taxes.create') }}" class="mt-3 md:mt-0 inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Nuevo impuesto
            </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jurisdicción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periodicidad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cuenta a pagar</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($taxes as $tax)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $tax->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $tax->jurisdiction ?: '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ match($tax->frequency) { 'monthly' => 'Mensual', 'quarterly' => 'Trimestral', 'annual' => 'Anual', default => $tax->frequency } }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    @if($tax->liabilityAccount)
                                        <span class="font-mono text-xs">{{ $tax->liabilityAccount->code }}</span>
                                        {{ $tax->liabilityAccount->name }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                    @can('taxes.manage')
                                    <a href="{{ route('taxes.edit', $tax) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">Editar</a>
                                    <form method="POST" action="{{ route('taxes.destroy', $tax) }}" class="inline" onsubmit="return confirm('¿Eliminar este impuesto?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Eliminar</button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">No hay impuestos configurados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($taxes->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">{{ $taxes->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
