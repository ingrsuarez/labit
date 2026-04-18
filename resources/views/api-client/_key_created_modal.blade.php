@php
    /** @var string $plainKey */
@endphp

<div x-data="{ open: true, copied: false, acknowledged: false }"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="api-key-modal-title">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full overflow-hidden border-4 border-amber-400">
        <div class="bg-amber-50 border-b border-amber-200 px-6 py-4 flex items-center">
            <svg class="w-6 h-6 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h2 id="api-key-modal-title" class="text-lg font-bold text-amber-900">
                Tu API key — guardala ahora
            </h2>
        </div>

        <div class="px-6 py-5 space-y-4">
            <p class="text-sm text-gray-700">
                Esta es la <strong>única vez</strong> que vas a ver la key completa.
                Copiala y guardala en un lugar seguro (gestor de contraseñas, vault de secretos).
                Si la perdés, vas a tener que <strong>regenerarla</strong> y reconfigurar el cliente.
            </p>

            <div class="bg-gray-900 rounded-lg px-4 py-3 flex items-center justify-between gap-3">
                <code class="text-green-400 text-sm font-mono break-all select-all"
                      x-ref="apiKey">{{ $plainKey }}</code>
                <button type="button"
                        @click="navigator.clipboard.writeText($refs.apiKey.textContent.trim()); copied = true; setTimeout(() => copied = false, 2000)"
                        class="flex-shrink-0 inline-flex items-center px-3 py-1.5 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors text-xs font-medium">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span x-text="copied ? '¡Copiada!' : 'Copiar'"></span>
                </button>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-900 space-y-1">
                <p class="font-semibold">Cómo usarla:</p>
                <code class="block bg-blue-100 px-2 py-1 rounded font-mono">
                    curl -H "X-API-Key: {{ $plainKey }}" {{ url('/api/v1/ping') }}
                </code>
            </div>

            <label class="flex items-start gap-2 cursor-pointer pt-2 border-t border-gray-100">
                <input type="checkbox" x-model="acknowledged"
                       class="mt-1 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                <span class="text-sm text-gray-700">
                    Confirmo que <strong>guardé la key</strong> en un lugar seguro.
                </span>
            </label>
        </div>

        <div class="bg-gray-50 px-6 py-4 flex justify-end">
            <button type="button"
                    @click="open = false"
                    :disabled="!acknowledged"
                    :class="acknowledged ? 'bg-amber-600 hover:bg-amber-700 cursor-pointer' : 'bg-gray-300 cursor-not-allowed'"
                    class="inline-flex items-center px-4 py-2 text-white rounded-lg transition-colors font-medium">
                Cerrar
            </button>
        </div>
    </div>
</div>
