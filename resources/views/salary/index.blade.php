<x-manage>
    <div class="max-w-7xl mx-auto p-6">
        {{-- Encabezado --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Conceptos de Sueldo</h1>
                <p class="text-sm text-gray-600 mt-1">Administre los haberes y deducciones según CCT 108/75 FATSA</p>
            </div>
            <a href="{{ route('salary.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nuevo Concepto
            </a>
        </div>

        {{-- Mensajes --}}
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Resumen --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-2xl font-bold text-gray-800">{{ $summary['total'] }}</div>
                <div class="text-sm text-gray-600">Total conceptos</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-2xl font-bold text-green-600">{{ $summary['haberes'] }}</div>
                <div class="text-sm text-gray-600">Haberes</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-2xl font-bold text-red-600">{{ $summary['deducciones'] }}</div>
                <div class="text-sm text-gray-600">Deducciones</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-2xl font-bold text-blue-600">{{ $summary['activos'] }}</div>
                <div class="text-sm text-gray-600">Activos</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Haberes --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Haberes
                    </h2>
                    <p class="text-green-100 text-sm">Conceptos que suman al sueldo</p>
                </div>
                
                <div class="divide-y divide-gray-100">
                    @forelse($haberes as $haber)
                        <div class="p-4 hover:bg-gray-50 transition-colors {{ !$haber->is_active ? 'opacity-50' : '' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-800">{{ $haber->name }}</span>
                                        @if($haber->code)
                                            <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded">{{ $haber->code }}</span>
                                        @endif
                                        @if(!$haber->is_active)
                                            <span class="px-2 py-0.5 text-xs bg-gray-200 text-gray-500 rounded">Inactivo</span>
                                        @endif
                                        @if($haber->requires_assignment)
                                            <span class="px-2 py-0.5 text-xs bg-amber-100 text-amber-700 rounded" title="Requiere asignación individual">Asignable</span>
                                        @endif
                                        @if($haber->includes_in_antiguedad_base)
                                            <span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded" title="Se suma a la base de antigüedad y zona">Base Zona</span>
                                        @endif
                                    </div>
                                    <div class="mt-1 text-sm text-gray-600 flex flex-wrap gap-x-4 gap-y-1">
                                        <span>
                                            @if($haber->calculation_type === 'percentage')
                                                <span class="font-semibold text-green-600">{{ number_format($haber->value, 2) }}%</span>
                                            @elseif($haber->calculation_type === 'fixed')
                                                <span class="font-semibold text-green-600">${{ number_format($haber->value, 2) }}</span>
                                            @else
                                                {{ $haber->calculation_type_name }}: {{ $haber->value }}
                                            @endif
                                        </span>
                                        <span class="text-gray-400">
                                            Base: 
                                            @switch($haber->calculation_base)
                                                @case('basic')
                                                    Básico
                                                    @break
                                                @case('basic_antiguedad')
                                                    Básico + Antigüedad
                                                    @break
                                                @case('basic_antiguedad_titulo')
                                                    Básico + Antigüedad + Título
                                                    @break
                                                @case('basic_hours')
                                                    Básico + Hs. Extras
                                                    @break
                                                @case('basic_hours_antiguedad')
                                                    Básico + Hs. Extras + Antigüedad
                                                    @break
                                                @default
                                                    {{ $haber->calculation_base ?? 'N/A' }}
                                            @endswitch
                                        </span>
                                        <span class="text-gray-400">Orden: {{ $haber->order }}</span>
                                        @if(!$haber->is_remunerative)
                                            <span class="text-orange-500">No remunerativo</span>
                                        @endif
                                    </div>
                                    @if($haber->description)
                                        <p class="mt-1 text-xs text-gray-500">{{ $haber->description }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-gray-400">{{ $haber->period_description }}</p>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <a href="{{ route('salary.edit', $haber) }}" 
                                       class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                       title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    @if($haber->requires_assignment)
                                        <a href="{{ route('salary.assignments', $haber) }}" 
                                           class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                           title="Asignar empleados">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    <form action="{{ route('salary.toggle', $haber) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="p-2 text-gray-400 hover:text-{{ $haber->is_active ? 'red' : 'green' }}-600 hover:bg-{{ $haber->is_active ? 'red' : 'green' }}-50 rounded-lg transition-colors"
                                                title="{{ $haber->is_active ? 'Desactivar' : 'Activar' }}">
                                            @if($haber->is_active)
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p>No hay haberes configurados</p>
                            <a href="{{ route('salary.create') }}" class="text-blue-600 hover:underline mt-2 inline-block">Crear el primero</a>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Deducciones --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                        Deducciones
                    </h2>
                    <p class="text-red-100 text-sm">Conceptos que se descuentan del sueldo</p>
                </div>
                
                <div class="divide-y divide-gray-100">
                    @forelse($deducciones as $deduccion)
                        <div class="p-4 hover:bg-gray-50 transition-colors {{ !$deduccion->is_active ? 'opacity-50' : '' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-800">{{ $deduccion->name }}</span>
                                        @if($deduccion->code)
                                            <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded">{{ $deduccion->code }}</span>
                                        @endif
                                        @if(!$deduccion->is_active)
                                            <span class="px-2 py-0.5 text-xs bg-gray-200 text-gray-500 rounded">Inactivo</span>
                                        @endif
                                        @if($deduccion->requires_assignment)
                                            <span class="px-2 py-0.5 text-xs bg-amber-100 text-amber-700 rounded" title="Requiere asignación individual">Asignable</span>
                                        @endif
                                    </div>
                                    <div class="mt-1 text-sm text-gray-600 flex flex-wrap gap-x-4 gap-y-1">
                                        <span>
                                            @if($deduccion->calculation_type === 'percentage')
                                                <span class="font-semibold text-red-600">{{ number_format($deduccion->value, 2) }}%</span>
                                            @elseif($deduccion->calculation_type === 'fixed')
                                                <span class="font-semibold text-red-600">${{ number_format($deduccion->value, 2) }}</span>
                                            @else
                                                {{ $deduccion->calculation_type_name }}: {{ $deduccion->value }}
                                            @endif
                                        </span>
                                        <span class="text-gray-400">Base: Bruto Total</span>
                                        <span class="text-gray-400">Orden: {{ $deduccion->order }}</span>
                                    </div>
                                    @if($deduccion->description)
                                        <p class="mt-1 text-xs text-gray-500">{{ $deduccion->description }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-gray-400">{{ $deduccion->period_description }}</p>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <a href="{{ route('salary.edit', $deduccion) }}" 
                                       class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                       title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('salary.toggle', $deduccion) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="p-2 text-gray-400 hover:text-{{ $deduccion->is_active ? 'red' : 'green' }}-600 hover:bg-{{ $deduccion->is_active ? 'red' : 'green' }}-50 rounded-lg transition-colors"
                                                title="{{ $deduccion->is_active ? 'Desactivar' : 'Activar' }}">
                                            @if($deduccion->is_active)
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p>No hay deducciones configuradas</p>
                            <a href="{{ route('salary.create') }}" class="text-blue-600 hover:underline mt-2 inline-block">Crear la primera</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Información CCT 108/75 --}}
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Información CCT 108/75 FATSA
            </h3>
            <div class="text-sm text-blue-700 space-y-2">
                <p><strong>Base del 30% Zona Desfavorable:</strong> Según el convenio, el adicional de zona se calcula sobre el <em>sueldo básico + antigüedad + adicional título</em> (conceptos fijos convencionales).</p>
                <p><strong>No incluye:</strong> Presentismo, comisiones, horas extras, ni adicionales no remunerativos.</p>
                <p class="text-xs text-blue-500 mt-2">Los conceptos marcados con <span class="px-1 py-0.5 bg-purple-100 text-purple-700 rounded text-xs">Base Zona</span> se suman a la base de cálculo del 30% de zona.</p>
            </div>
        </div>
    </div>
</x-manage>

