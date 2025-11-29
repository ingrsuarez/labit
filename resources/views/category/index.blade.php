<x-manage>
    <div class="w-full px-4 py-6">
        {{-- Encabezado con título y botón --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Categorías</h1>
                <p class="text-sm text-gray-600 mt-1">Gestión de categorías salariales y convenios</p>
            </div>
            <a href="{{ route('category.new') }}" 
               class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nueva Categoría
            </a>
        </div>

        {{-- Tarjetas resumen --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Total Categorías</div>
                <div class="text-2xl font-semibold text-blue-600">{{ $categories->count() }}</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Puestos Asociados</div>
                <div class="text-2xl font-semibold text-green-600">{{ $categories->sum('jobs_count') }}</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Total Salarios Básicos</div>
                <div class="text-2xl font-semibold text-amber-600">
                    ${{ number_format($categories->sum('wage') ?? 0, 2, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Tabla de categorías --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            @if($categories->isEmpty())
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay categorías</h3>
                    <p class="mt-1 text-sm text-gray-500">Comienza creando una nueva categoría.</p>
                    <div class="mt-6">
                        <a href="{{ route('category.new') }}" 
                           class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Nueva Categoría
                        </a>
                    </div>
                </div>
            @else
                <table class="w-full divide-y divide-gray-200 table-fixed">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-1/4 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nombre
                            </th>
                            <th class="w-1/4 px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Convenio
                            </th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sindicato
                            </th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Salario Básico
                            </th>
                            <th class="w-16 px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Puestos
                            </th>
                            <th class="w-24 px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($categories as $category)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <span class="text-blue-600 font-semibold text-xs">
                                                {{ mb_strtoupper(mb_substr($category->name, 0, 2, 'UTF-8'), 'UTF-8') }}
                                            </span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 capitalize truncate" title="{{ $category->name }}">
                                            {{ $category->name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="text-sm text-gray-900 capitalize truncate" title="{{ $category->agreement }}">{{ $category->agreement ?? '—' }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="text-sm text-gray-900 capitalize truncate">{{ $category->union_name ?? '—' }}</div>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <div class="text-sm font-semibold text-gray-900">
                                        ${{ number_format($category->wage ?? 0, 2, ',', '.') }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                        {{ $category->jobs_count > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $category->jobs_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('category.edit', ['category' => $category->id]) }}" 
                                       class="inline-flex items-center px-2 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors text-xs"
                                       title="Editar categoría">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-manage>

