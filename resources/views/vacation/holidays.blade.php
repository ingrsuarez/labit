<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-4xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('vacation.index') }}" 
                       class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Feriados Argentina {{ $year }}</h1>
                        <p class="text-sm text-gray-500">Días no laborables excluidos del cálculo de vacaciones</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('vacation.holidays', ['year' => $year - 1]) }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        ← {{ $year - 1 }}
                    </a>
                    <a href="{{ route('vacation.holidays', ['year' => $year + 1]) }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        {{ $year + 1 }} →
                    </a>
                </div>
            </div>

            {{-- Listado de Feriados --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ $holidays->count() }} Feriados Registrados
                    </h2>
                </div>
                
                <div class="p-6">
                    @if($holidays->count())
                        <div class="space-y-3">
                            @foreach($holidays as $holiday)
                                @php
                                    $isPast = \Carbon\Carbon::parse($holiday->date)->lt(now()->startOfDay());
                                @endphp
                                <div class="flex items-center justify-between p-4 rounded-lg {{ $isPast ? 'bg-gray-50' : 'bg-red-50 border border-red-100' }}">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 flex flex-col items-center justify-center rounded-lg {{ $isPast ? 'bg-gray-200' : 'bg-red-100' }} mr-4">
                                            <span class="text-xs font-medium {{ $isPast ? 'text-gray-500' : 'text-red-600' }}">
                                                {{ \Carbon\Carbon::parse($holiday->date)->locale('es')->format('M') }}
                                            </span>
                                            <span class="text-lg font-bold {{ $isPast ? 'text-gray-700' : 'text-red-700' }}">
                                                {{ \Carbon\Carbon::parse($holiday->date)->format('d') }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-medium {{ $isPast ? 'text-gray-600' : 'text-gray-900' }}">
                                                {{ $holiday->name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ \Carbon\Carbon::parse($holiday->date)->locale('es')->isoFormat('dddd, D [de] MMMM') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 rounded text-xs font-medium
                                            {{ $holiday->type === 'fijo' ? 'bg-blue-100 text-blue-700' : 
                                               ($holiday->type === 'movil' ? 'bg-amber-100 text-amber-700' : 'bg-purple-100 text-purple-700') }}">
                                            {{ ucfirst($holiday->type) }}
                                        </span>
                                        @if(!$holiday->is_active)
                                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs">Inactivo</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-gray-500">No hay feriados registrados para {{ $year }}</p>
                            <p class="text-sm text-gray-400 mt-1">Ejecutá el seeder para cargar los feriados</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Leyenda --}}
            <div class="mt-6 bg-white rounded-xl shadow p-4">
                <h3 class="font-medium text-gray-700 mb-3">Tipos de Feriados</h3>
                <div class="flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center">
                        <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                        <span class="text-gray-600">Fijo - Fecha inamovible</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-3 h-3 bg-amber-500 rounded-full mr-2"></span>
                        <span class="text-gray-600">Móvil - Trasladable</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-3 h-3 bg-purple-500 rounded-full mr-2"></span>
                        <span class="text-gray-600">Puente - Día no laborable</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-manage>






