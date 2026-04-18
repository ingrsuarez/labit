<x-admin-layout>
    <div class="max-w-7xl mx-auto py-8 px-4">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">API Keys</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Gestión de claves de la API pública v1 (integraciones máquina-a-máquina como LISCOM).
                </p>
            </div>
            <a href="{{ route('api-clients.create') }}"
               class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nueva API Key
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <p class="text-sm text-gray-500">Total de keys</p>
                <p class="text-2xl font-bold text-gray-900">{{ $clients->total() }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <p class="text-sm text-gray-500">Activas</p>
                <p class="text-2xl font-bold text-green-700">
                    {{ $clients->getCollection()->where('active', true)->count() }} / {{ $clients->total() }}
                </p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <p class="text-sm text-gray-500">Sedes con clientes</p>
                <p class="text-2xl font-bold text-gray-900">
                    {{ $clients->getCollection()->pluck('lab_branch_id')->unique()->count() }}
                </p>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('api-clients.index') }}"
              class="mb-6 bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sede</label>
                <select name="lab_branch_id" class="border-gray-300 rounded-md text-sm">
                    <option value="">Todas</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(request('lab_branch_id') == $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                <select name="active" class="border-gray-300 rounded-md text-sm">
                    <option value="">Todos</option>
                    <option value="1" @selected(request('active') === '1')>Activas</option>
                    <option value="0" @selected(request('active') === '0')>Inactivas</option>
                </select>
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-gray-700 text-white rounded-md text-sm hover:bg-gray-800">
                Filtrar
            </button>
            <a href="{{ route('api-clients.index') }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">
                Limpiar
            </a>
        </form>

        {{-- Tabla --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
            @if($clients->isEmpty())
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Todavía no hay API keys</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Creá la primera key para empezar a integrar con LISCOM u otro sistema externo.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Sede</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Empresa</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Key</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Último uso</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Requests</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($clients as $client)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-gray-900">{{ $client->name }}</p>
                                        @if($client->notes)
                                            <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $client->notes }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $client->labBranch?->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $client->company?->displayName() ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        <code class="text-xs font-mono bg-gray-100 text-gray-700 px-2 py-1 rounded">
                                            {{ $client->key_preview }}
                                        </code>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $client->last_used_at ? $client->last_used_at->diffForHumans() : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-700 font-mono">
                                        {{ number_format($client->requests_count) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($client->active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Activa
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                                Inactiva
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('api-clients.show', $client) }}"
                                           class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors text-sm font-medium">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($clients->hasPages())
                    <div class="px-6 py-3 border-t border-gray-100 bg-gray-50">
                        {{ $clients->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>
