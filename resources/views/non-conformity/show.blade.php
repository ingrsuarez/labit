<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <a href="{{ route('non-conformity.index') }}" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ $nonConformity->code }}
                    </h2>
                    <p class="text-sm text-gray-500">No Conformidad</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 text-sm rounded-full {{ $nonConformity->severity_color }}">
                    {{ ucfirst($nonConformity->severity) }}
                </span>
                <span class="px-3 py-1 text-sm rounded-full {{ $nonConformity->status_color }}">
                    {{ ucfirst(str_replace('_', ' ', $nonConformity->status)) }}
                </span>
                <a href="{{ route('non-conformity.edit', $nonConformity) }}" 
                   class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 text-sm">
                    Editar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Mensajes Flash -->
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Información Principal -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Detalles -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Detalles de la No Conformidad</h3>
                        
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <p class="text-sm text-gray-500">Fecha del Incidente</p>
                                <p class="font-medium">{{ $nonConformity->date->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Tipo</p>
                                <p class="font-medium">{{ \App\Models\NonConformity::types()[$nonConformity->type] ?? $nonConformity->type }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Empleado Involucrado</p>
                                <p class="font-medium">{{ $nonConformity->employee->lastname }}, {{ $nonConformity->employee->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Reportado por</p>
                                <p class="font-medium">{{ $nonConformity->reporter->name }}</p>
                            </div>
                            @if($nonConformity->procedure_name)
                            <div>
                                <p class="text-sm text-gray-500">Procedimiento Incumplido</p>
                                <p class="font-medium">{{ $nonConformity->procedure_name }}</p>
                            </div>
                            @endif
                            @if($nonConformity->training_name)
                            <div>
                                <p class="text-sm text-gray-500">Capacitación Relacionada</p>
                                <p class="font-medium">{{ $nonConformity->training_name }}</p>
                            </div>
                            @endif
                        </div>

                        <div class="border-t pt-4">
                            <p class="text-sm text-gray-500 mb-2">Descripción</p>
                            <p class="text-gray-700 whitespace-pre-line">{{ $nonConformity->description }}</p>
                        </div>

                        @if($nonConformity->corrective_action)
                        <div class="border-t pt-4 mt-4">
                            <p class="text-sm text-gray-500 mb-2">Acción Correctiva</p>
                            <p class="text-gray-700 whitespace-pre-line">{{ $nonConformity->corrective_action }}</p>
                        </div>
                        @endif

                        @if($nonConformity->preventive_action)
                        <div class="border-t pt-4 mt-4">
                            <p class="text-sm text-gray-500 mb-2">Acción Preventiva</p>
                            <p class="text-gray-700 whitespace-pre-line">{{ $nonConformity->preventive_action }}</p>
                        </div>
                        @endif

                        @if($nonConformity->status === 'cerrada')
                        <div class="border-t pt-4 mt-4 bg-green-50 -mx-6 -mb-6 px-6 py-4 rounded-b-xl">
                            <p class="text-sm text-green-600">
                                <strong>Cerrada el {{ $nonConformity->closed_at->format('d/m/Y H:i') }}</strong>
                                por {{ $nonConformity->closer->name ?? 'Usuario desconocido' }}
                            </p>
                        </div>
                        @endif
                    </div>

                    <!-- Seguimientos -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Seguimientos</h3>
                        
                        @if($nonConformity->followUps->count())
                            <div class="space-y-4">
                                @foreach($nonConformity->followUps as $followUp)
                                    <div class="border-l-4 border-gray-300 pl-4 py-2">
                                        <div class="flex justify-between items-start">
                                            <p class="font-medium text-gray-900">{{ $followUp->user->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $followUp->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                        <p class="text-gray-700 mt-1 whitespace-pre-line">{{ $followUp->notes }}</p>
                                        @if($followUp->status_change)
                                            <p class="text-sm text-blue-600 mt-1 font-medium">{{ $followUp->status_change }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-4">No hay seguimientos registrados.</p>
                        @endif
                    </div>
                </div>

                <!-- Panel Lateral -->
                <div class="space-y-6">
                    <!-- Agregar Seguimiento -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Agregar Seguimiento</h3>
                        
                        <form action="{{ route('non-conformity.follow-up', $nonConformity) }}" method="POST">
                            @csrf
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notas *</label>
                                <textarea name="notes" rows="4" required
                                          placeholder="Escriba las notas del seguimiento..."
                                          class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 text-sm"></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cambiar Estado</label>
                                <select name="status_change" class="w-full rounded-lg border-gray-300 text-sm">
                                    <option value="">Sin cambio</option>
                                    @foreach(\App\Models\NonConformity::statuses() as $key => $label)
                                        @if($key !== $nonConformity->status)
                                            <option value="{{ $key }}">Cambiar a: {{ $label }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                                Agregar Seguimiento
                            </button>
                        </form>
                    </div>

                    <!-- Información Adicional -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Información</h3>
                        
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-gray-500">Creada</p>
                                <p class="font-medium">{{ $nonConformity->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Última actualización</p>
                                <p class="font-medium">{{ $nonConformity->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Total de seguimientos</p>
                                <p class="font-medium">{{ $nonConformity->followUps->count() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Acciones</h3>
                        
                        <div class="space-y-2">
                            <a href="{{ route('non-conformity.edit', $nonConformity) }}" 
                               class="block w-full text-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                                Editar NC
                            </a>
                            
                            <form action="{{ route('non-conformity.destroy', $nonConformity) }}" method="POST"
                                  onsubmit="return confirm('¿Está seguro de eliminar esta No Conformidad?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm">
                                    Eliminar NC
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
