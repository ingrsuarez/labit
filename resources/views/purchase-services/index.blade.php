<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Servicios de compra</h1>
                <p class="text-gray-500 text-sm mt-1">Se eligen al cargar facturas de compra para estadísticas por tipo</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('purchase-invoices.index')
                <a href="{{ route('purchase-services.statistics') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                    Estadísticas
                </a>
                @endcan
                @can('purchase-services.create')
                <a href="{{ route('purchase-services.create') }}"
                   class="inline-flex items-center px-4 py-2.5 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 shadow-sm">
                    Nuevo servicio
                </a>
                @endcan
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($services->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Categoría</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Orden</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($services as $service)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $service->code ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $service->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $service->category?->name ?? 'Sin categoría' }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $service->sort_order }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $service->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm space-x-2">
                                        @can('purchase-services.edit')
                                        <a href="{{ route('purchase-services.edit', $service) }}" class="text-gray-500 hover:text-zinc-700">Editar</a>
                                        @endcan
                                        @can('purchase-services.delete')
                                        <form method="POST" action="{{ route('purchase-services.destroy', $service) }}" class="inline" onsubmit="return confirm('¿Eliminar este servicio?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-600">Eliminar</button>
                                        </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center text-gray-500 text-sm">No hay servicios. Creá uno para usarlo en facturas de compra.</div>
            @endif
        </div>
    </div>
</x-admin-layout>
