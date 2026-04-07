<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Categorías de servicios de compra</h1>
                <p class="text-gray-500 text-sm mt-1">Agrupan servicios para reportes (ej. derivación veterinaria, alquileres)</p>
            </div>
            @can('purchase-service-categories.create')
            <a href="{{ route('purchase-service-categories.create') }}"
               class="mt-3 md:mt-0 inline-flex items-center px-4 py-2.5 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva categoría
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
            @if($categories->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Slug</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Servicios</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Orden</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($categories as $category)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $category->name }}</td>
                                    <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $category->slug }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $category->services_count }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $category->sort_order }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm space-x-2">
                                        @can('purchase-service-categories.edit')
                                        <a href="{{ route('purchase-service-categories.edit', $category) }}" class="text-gray-500 hover:text-zinc-700">Editar</a>
                                        @endcan
                                        @can('purchase-service-categories.delete')
                                        @if($category->services_count === 0)
                                            <form method="POST" action="{{ route('purchase-service-categories.destroy', $category) }}" class="inline" onsubmit="return confirm('¿Eliminar esta categoría?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-400 hover:text-red-600">Eliminar</button>
                                            </form>
                                        @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center text-gray-500 text-sm">No hay categorías. Creá una para clasificar servicios de compra.</div>
            @endif
        </div>
    </div>
</x-admin-layout>
