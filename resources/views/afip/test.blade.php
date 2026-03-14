<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Test de Conexión AFIP</h1>
                <p class="text-gray-500 text-sm mt-1">Verificá la configuración y conexión con los servidores de AFIP</p>
            </div>
            <a href="{{ route('sales.section') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                &larr; Volver a Ventas
            </a>
        </div>

        <div class="space-y-4">
            {{-- Configuración --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-lg font-semibold text-gray-700 mb-3">Configuración</h2>
                @if(isset($results['config']))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-500 w-32">CUIT:</span>
                            <span class="text-sm font-mono {{ $results['config']['cuit'] ? 'text-gray-800' : 'text-red-500 font-semibold' }}">
                                {{ $results['config']['cuit'] ?: 'NO CONFIGURADO' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-500 w-32">Entorno:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $results['config']['production'] ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $results['config']['production'] ? 'PRODUCCIÓN' : 'HOMOLOGACIÓN' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-500 w-32">Certificado:</span>
                            @if($results['config']['cert_exists'])
                                <span class="inline-flex items-center gap-1 text-sm text-green-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Presente
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-sm text-red-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    No encontrado
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-500 w-32">Clave privada:</span>
                            @if($results['config']['key_exists'])
                                <span class="inline-flex items-center gap-1 text-sm text-green-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Presente
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-sm text-red-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    No encontrada
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Estado del Servidor --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-lg font-semibold text-gray-700 mb-3">Estado del Servidor AFIP</h2>
                @if(isset($results['server_status']))
                    @if($results['server_status']['success'])
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @foreach(['AppServer', 'DbServer', 'AuthServer'] as $server)
                                <div class="flex items-center gap-2 p-3 rounded-lg {{ $results['server_status'][$server] === 'OK' ? 'bg-green-50' : 'bg-red-50' }}">
                                    @if($results['server_status'][$server] === 'OK')
                                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">{{ $server }}</p>
                                        <p class="text-xs {{ $results['server_status'][$server] === 'OK' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $results['server_status'][$server] }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-700 font-medium">Error de conexión</p>
                            <p class="text-xs text-red-600 mt-1 font-mono">{{ $results['server_status']['error'] ?? 'Error desconocido' }}</p>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Error general --}}
            @if(isset($results['error']))
                <div class="bg-white rounded-xl shadow-sm border border-red-200 p-5">
                    <h2 class="text-lg font-semibold text-red-600 mb-2">Error</h2>
                    <pre class="text-xs text-red-700 bg-red-50 p-3 rounded-lg overflow-x-auto font-mono">{{ $results['error'] }}</pre>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
