<x-lab-layout title="Perfiles de determinaciones">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <div class="text-sm text-gray-500 mb-1">
                    <a href="{{ route('lab.section.configuracion') }}" class="hover:text-teal-600">Configuración</a>
                    <span class="mx-1">/</span>
                    <span>Perfiles</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Perfiles de determinaciones</h1>
                <p class="text-sm text-gray-600 mt-1">Catálogo global del sistema (sin empresa).</p>
            </div>
            @can('determination-profiles.manage')
                <a href="{{ route('determination-profiles.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo perfil
                </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
            <form method="GET" class="flex flex-col md:flex-row gap-3 md:items-end">
                <div class="flex flex-wrap gap-2">
                    @foreach(['todos' => 'Todos', 'clinico' => 'Clínico', 'veterinario' => 'Veterinario', 'aguas_alimentos' => 'Aguas / alimentos'] as $val => $label)
                        <a href="{{ route('determination-profiles.index', array_merge(request()->except('page'), ['tipo' => $val])) }}"
                           class="px-3 py-1.5 rounded-lg text-sm font-medium {{ ($tab ?? 'todos') === $val ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Buscar por nombre</label>
                    <input type="search" name="q" value="{{ $search ?? '' }}" placeholder="Nombre del perfil..."
                           class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-900">Filtrar</button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ítems</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($profiles as $profile)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $profile->name }}</td>
                                <td class="px-4 py-3">
                                    @php $lt = $profile->lab_type; @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $lt->value === 'clinico' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $lt->value === 'veterinario' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $lt->value === 'aguas_alimentos' ? 'bg-teal-100 text-teal-800' : '' }}">
                                        {{ $lt->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-700">{{ $profile->tests_count }}</td>
                                <td class="px-4 py-3">
                                    @if($profile->is_active)
                                        <span class="text-xs font-medium text-green-700">Activo</span>
                                    @else
                                        <span class="text-xs font-medium text-gray-500">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    @can('determination-profiles.manage')
                                        <a href="{{ route('determination-profiles.edit', $profile) }}" class="text-teal-600 hover:text-teal-800 text-sm">Editar</a>
                                        <form action="{{ route('determination-profiles.destroy', $profile) }}" method="POST" class="inline"
                                              onsubmit="return confirm('¿Desactivar este perfil? Las admisiones ya cargadas no cambian.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Desactivar</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-gray-500">
                                    <i class="bi bi-folder2-open text-3xl block mb-2 opacity-50"></i>
                                    No hay perfiles para este filtro.
                                    @can('determination-profiles.manage')
                                        <a href="{{ route('determination-profiles.create') }}" class="text-teal-600 hover:underline">Crear perfil</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200">{{ $profiles->withQueryString()->links() }}</div>
        </div>
    </div>
</x-lab-layout>
