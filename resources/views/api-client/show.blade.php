<x-admin-layout>
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('api-clients.index') }}"
               class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver al listado
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ route('api-clients.edit', $client) }}"
                   class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                    Editar
                </a>
                <form method="POST" action="{{ route('api-clients.regenerate', $client) }}"
                      onsubmit="return confirm('Regenerar la key invalida la actual. Cualquier sistema que la use va a empezar a recibir 401 hasta que lo reconfigures. ¿Continuar?');">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-3 py-1.5 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 transition-colors text-sm font-medium">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Regenerar key
                    </button>
                </form>
                <form method="POST" action="{{ route('api-clients.destroy', $client) }}"
                      onsubmit="return confirm('Eliminar la key {{ $client->name }}? Esta acción es irreversible.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium">
                        Eliminar
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $client->name }}</h1>
                        <p class="text-sm text-gray-600 mt-1">
                            Cliente #{{ $client->id }}
                            @if($client->createdBy)
                                · creado por {{ $client->createdBy->name }}
                            @endif
                            · {{ $client->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    @if($client->active)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            Activa
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-200 text-gray-700">
                            Inactiva
                        </span>
                    @endif
                </div>
            </div>

            <dl class="divide-y divide-gray-100">
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-gray-500">Sede</dt>
                    <dd class="col-span-2 text-sm text-gray-900">{{ $client->labBranch?->name ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-gray-500">Empresa</dt>
                    <dd class="col-span-2 text-sm text-gray-900">{{ $client->company?->displayName() ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-gray-500">Preview de key</dt>
                    <dd class="col-span-2">
                        <code class="text-sm font-mono bg-gray-100 text-gray-700 px-2 py-1 rounded">
                            {{ $client->key_preview }}
                        </code>
                        <span class="text-xs text-gray-500 ml-2">(la key completa solo se ve al crear/regenerar)</span>
                    </dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-gray-500">Total de requests</dt>
                    <dd class="col-span-2 text-sm text-gray-900 font-mono">{{ number_format($client->requests_count) }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-gray-500">Último uso</dt>
                    <dd class="col-span-2 text-sm text-gray-900">
                        @if($client->last_used_at)
                            {{ $client->last_used_at->format('d/m/Y H:i:s') }}
                            <span class="text-xs text-gray-500">({{ $client->last_used_at->diffForHumans() }})</span>
                        @else
                            <span class="text-gray-400 italic">Nunca</span>
                        @endif
                    </dd>
                </div>
                @if($client->notes)
                    <div class="grid grid-cols-3 gap-4 px-6 py-4">
                        <dt class="text-sm font-medium text-gray-500">Notas</dt>
                        <dd class="col-span-2 text-sm text-gray-900 whitespace-pre-wrap">{{ $client->notes }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        @if($client->auditLogs && $client->auditLogs->count() > 0)
            <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Auditoría</h2>
                </div>
                <ul class="divide-y divide-gray-100">
                    @foreach($client->auditLogs->take(20) as $log)
                        <li class="px-6 py-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900">
                                    <span class="inline-block px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800 uppercase mr-2">{{ $log->action }}</span>
                                    {{ $log->description }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $log->user_name }} · {{ $log->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @if(session('api_key_just_created'))
        @include('api-client._key_created_modal', ['plainKey' => session('api_key_just_created')])
    @endif
</x-admin-layout>
