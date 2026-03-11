<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Categorías de Insumos</h1>
                <p class="text-gray-500 text-sm mt-1">Clasificación de insumos del sistema</p>
            </div>
            <a href="{{ route('supply-categories.create') }}"
               class="mt-3 md:mt-0 inline-flex items-center px-4 py-2.5 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Categoría
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($categories->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Prefijo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descripción</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Insumos</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($categories as $category)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-800">
                                        {{ $category->name }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-zinc-100 text-zinc-700">
                                            {{ $category->code_prefix ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $category->description ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-700">
                                            {{ $category->supplies_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm space-x-2">
                                        <a href="{{ route('supply-categories.edit', $category) }}" class="text-gray-500 hover:text-zinc-700">Editar</a>
                                        @if($category->supplies_count === 0)
                                            <form method="POST" action="{{ route('supply-categories.destroy', $category) }}" class="inline" onsubmit="return confirm('¿Eliminar esta categoría?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-400 hover:text-red-600">Eliminar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay categorías</h3>
                    <p class="mt-1 text-sm text-gray-500">Creá la primera categoría de insumos.</p>
                    <div class="mt-4">
                        <a href="{{ route('supply-categories.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">
                            Nueva Categoría
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
