<x-lab-layout title="Nomencladores">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Gestión de Nomencladores</h1>
            <p class="mt-1 text-sm text-gray-600">Configure los precios de cada práctica por obra social usando nomencladores base</p>
        </div>

        <!-- Nomencladores Base Disponibles -->
        @if(isset($baseNomenclators) && $baseNomenclators->count() > 0)
        <div class="bg-gradient-to-r from-teal-50 to-cyan-50 rounded-xl shadow-sm border border-teal-200 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-teal-200 bg-teal-100/50">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h2 class="text-lg font-semibold text-teal-800">Nomencladores Base</h2>
                </div>
                <p class="text-sm text-teal-600 mt-1">Listas de prácticas con sus valores NBU. Use estos para copiar prácticas a las obras sociales.</p>
            </div>
            
            <div class="divide-y divide-teal-200">
                @foreach($baseNomenclators as $nomenclator)
                    <a href="{{ route('nomenclator.show', $nomenclator) }}" 
                       class="block px-6 py-4 hover:bg-teal-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 text-xs font-medium bg-teal-600 text-white rounded">BASE</span>
                                    <h3 class="text-base font-medium text-gray-900">
                                        {{ strtoupper($nomenclator->name) }}
                                    </h3>
                                </div>
                                <div class="mt-1 flex items-center gap-4 text-sm text-gray-500">
                                    <span class="flex items-center text-teal-600 font-medium">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        {{ $nomenclator->nomenclator_count }} prácticas
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Listado de Obras Sociales -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h2 class="text-lg font-semibold text-gray-800">Nomencladores por Obra Social</h2>
                </div>
                <p class="text-sm text-gray-500 mt-1">Configure qué prácticas y precios tiene cada obra social/cliente.</p>
            </div>
            
            <div class="divide-y divide-gray-200">
                @forelse($insurances as $insurance)
                    <a href="{{ route('nomenclator.show', $insurance) }}" 
                       class="block px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    @if($insurance->type === 'particular')
                                        <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded">PARTICULAR</span>
                                    @elseif($insurance->type === 'prepaga')
                                        <span class="px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 rounded">PREPAGA</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded">O. SOCIAL</span>
                                    @endif
                                    <h3 class="text-base font-medium text-gray-900">
                                        {{ strtoupper($insurance->name) }}
                                    </h3>
                                </div>
                                <div class="mt-1 flex items-center gap-4 text-sm text-gray-500">
                                    @if($insurance->nbu_value)
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Valor NBU: ${{ number_format($insurance->nbu_value, 2, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-yellow-600">Sin valor NBU configurado</span>
                                    @endif
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        {{ $insurance->nomenclator_count }} prácticas
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay obras sociales</h3>
                        <p class="mt-1 text-sm text-gray-500">Primero debe crear obras sociales para configurar sus nomencladores.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-lab-layout>

