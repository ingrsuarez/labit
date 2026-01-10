<x-portal-layout>
    <div class="max-w-5xl mx-auto py-8 px-4" 
         x-data="{
            tab: '{{ $tab }}',
            search: '',
            pendingCirculars: {{ Js::from($pendingCirculars->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'title' => $c->title,
                'description' => $c->description,
                'date' => $c->date->format('d/m/Y'),
                'sector' => $c->sector,
                'sector_label' => \App\Models\Circular::sectors()[$c->sector] ?? $c->sector,
                'url' => route('portal.circulars.show', $c),
            ])) }},
            signedCirculars: {{ Js::from($signedCirculars->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'title' => $c->title,
                'description' => $c->description,
                'date' => $c->date->format('d/m/Y'),
                'sector' => $c->sector,
                'sector_label' => \App\Models\Circular::sectors()[$c->sector] ?? $c->sector,
                'signed_at' => $c->signatures->where('employee_id', $employee->id)->first()?->signed_at?->format('d/m/Y H:i'),
                'url' => route('portal.circulars.show', $c),
            ])) }},
            get filteredPending() {
                if (!this.search.trim()) return this.pendingCirculars;
                const term = this.search.toLowerCase();
                return this.pendingCirculars.filter(c => 
                    c.title.toLowerCase().includes(term) ||
                    c.description.toLowerCase().includes(term) ||
                    c.code.toLowerCase().includes(term) ||
                    c.sector_label.toLowerCase().includes(term)
                );
            },
            get filteredSigned() {
                if (!this.search.trim()) return this.signedCirculars;
                const term = this.search.toLowerCase();
                return this.signedCirculars.filter(c => 
                    c.title.toLowerCase().includes(term) ||
                    c.description.toLowerCase().includes(term) ||
                    c.code.toLowerCase().includes(term) ||
                    c.sector_label.toLowerCase().includes(term)
                );
            }
         }">
        
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Circulares</h1>
            <p class="text-sm text-gray-600 mt-1">Comunicados oficiales de la empresa que requieren tu firma</p>
        </div>

        {{-- Buscador --}}
        <div class="mb-6">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" 
                       x-model="search"
                       placeholder="Buscar por título, código, contenido o sector..."
                       class="w-full pl-11 pr-10 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow text-sm">
                <button x-show="search.length > 0" 
                        @click="search = ''"
                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <p x-show="search.length > 0" class="mt-2 text-sm text-gray-500">
                <span x-text="tab === 'pending' ? filteredPending.length : filteredSigned.length"></span> 
                resultado(s) para "<span x-text="search" class="font-medium"></span>"
            </p>
        </div>

        {{-- Tabs --}}
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button @click="tab = 'pending'"
                            :class="tab === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        Pendientes
                        <span class="ml-2 text-xs font-semibold px-2.5 py-0.5 rounded-full"
                              :class="tab === 'pending' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-600'"
                              x-text="filteredPending.length"></span>
                    </button>
                    <button @click="tab = 'signed'"
                            :class="tab === 'signed' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        Firmadas
                        <span class="ml-2 text-xs font-semibold px-2.5 py-0.5 rounded-full"
                              :class="tab === 'signed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                              x-text="filteredSigned.length"></span>
                    </button>
                </nav>
            </div>
        </div>

        {{-- Mensaje de éxito --}}
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-green-800">{{ session('success') }}</span>
            </div>
        @endif

        {{-- Circulares Pendientes --}}
        <div x-show="tab === 'pending'" x-cloak>
            <template x-if="filteredPending.length === 0">
                <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                    <template x-if="search.length > 0">
                        <div>
                            <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">Sin resultados</h3>
                            <p class="mt-2 text-gray-500">No se encontraron circulares pendientes con ese criterio.</p>
                            <button @click="search = ''" class="mt-4 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                Limpiar búsqueda
                            </button>
                        </div>
                    </template>
                    <template x-if="search.length === 0">
                        <div>
                            <svg class="mx-auto h-16 w-16 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">¡Todo al día!</h3>
                            <p class="mt-2 text-gray-500">No tienes circulares pendientes de firma.</p>
                        </div>
                    </template>
                </div>
            </template>
            
            <div class="space-y-4">
                <template x-for="circular in filteredPending" :key="circular.id">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-xs font-mono text-gray-500" x-text="circular.code"></span>
                                        <span class="text-xs text-gray-400">•</span>
                                        <span class="text-xs text-gray-500" x-text="circular.date"></span>
                                        <template x-if="circular.sector !== 'general'">
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800" x-text="circular.sector_label"></span>
                                        </template>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900" x-text="circular.title"></h3>
                                    <p class="mt-2 text-gray-600 text-sm line-clamp-2" x-text="circular.description.substring(0, 200) + (circular.description.length > 200 ? '...' : '')"></p>
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Pendiente
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <a :href="circular.url" 
                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Leer y Firmar
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Circulares Firmadas --}}
        <div x-show="tab === 'signed'" x-cloak>
            <template x-if="filteredSigned.length === 0">
                <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                    <template x-if="search.length > 0">
                        <div>
                            <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">Sin resultados</h3>
                            <p class="mt-2 text-gray-500">No se encontraron circulares firmadas con ese criterio.</p>
                            <button @click="search = ''" class="mt-4 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                Limpiar búsqueda
                            </button>
                        </div>
                    </template>
                    <template x-if="search.length === 0">
                        <div>
                            <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">Sin circulares firmadas</h3>
                            <p class="mt-2 text-gray-500">Aún no has firmado ninguna circular.</p>
                        </div>
                    </template>
                </div>
            </template>
            
            <div class="space-y-4">
                <template x-for="circular in filteredSigned" :key="circular.id">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-xs font-mono text-gray-500" x-text="circular.code"></span>
                                        <span class="text-xs text-gray-400">•</span>
                                        <span class="text-xs text-gray-500" x-text="circular.date"></span>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900" x-text="circular.title"></h3>
                                    <p class="mt-2 text-xs text-gray-500" x-show="circular.signed_at">
                                        Firmada el <span x-text="circular.signed_at"></span>
                                    </p>
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Firmada
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <a :href="circular.url" 
                                   class="inline-flex items-center px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                    Ver detalles
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-portal-layout>
