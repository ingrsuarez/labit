<x-lab-layout title="Equivalencias Biosystems A25">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <div class="text-sm text-gray-500 mb-1">
                    <a href="{{ route('lab.section.configuracion') }}" class="hover:text-teal-600">Configuración</a>
                    <span class="mx-1">/</span>
                    <span>Equivalencias A25</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Equivalencias Biosystems A25 ↔ Labit</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Cada fila define cómo se llama un analito <strong>en el archivo del equipo A25</strong>
                    y a qué determinación corresponde en Labit.
                </p>
            </div>
            @can('a25.mappings.manage')
                <a href="{{ route('a25.mappings.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 transition text-sm font-medium">
                    + Nueva equivalencia
                </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        <!-- Filtro -->
        <form method="GET" class="mb-4 flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Buscar por nombre equipo..."
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-72 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
            <button type="submit" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">Buscar</button>
            @if(request('search'))
                <a href="{{ route('a25.mappings.index') }}" class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700">Limpiar</a>
            @endif
        </form>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre en equipo A25</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Determinación en Labit</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Material</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($mappings as $mapping)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-mono text-xs">{{ $mapping->equipment_analyte_name }}</td>
                            <td class="px-4 py-2">
                                <span class="text-xs text-gray-400 mr-1">{{ $mapping->test->code ?? '' }}</span>
                                {{ $mapping->test->name ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded text-xs font-mono">{{ $mapping->material_type }}</span>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-500">
                                {{ $mapping->labBranch?->name ?? 'Global (todas las sedes)' }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                @can('a25.mappings.manage')
                                    <a href="{{ route('a25.mappings.edit', $mapping) }}"
                                       class="text-indigo-600 hover:text-indigo-800 mr-2 text-xs">Editar</a>
                                    <form action="{{ route('a25.mappings.destroy', $mapping) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Eliminar esta equivalencia?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Eliminar</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">
                                No hay equivalencias configuradas.
                                @can('a25.mappings.manage')
                                    <a href="{{ route('a25.mappings.create') }}" class="text-teal-600 hover:underline ml-1">Crear la primera</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $mappings->links() }}</div>
    </div>
</x-lab-layout>
