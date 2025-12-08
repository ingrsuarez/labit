<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-start md:justify-between mb-6">
            <div>
                <a href="{{ route('sample.index') }}" class="text-teal-600 hover:text-teal-800 text-sm flex items-center mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver a Protocolos
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Protocolo {{ $sample->protocol_number }}</h1>
                <div class="flex flex-wrap items-center gap-3 mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $sample->sample_type == 'agua' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                        {{ ucfirst($sample->sample_type) }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @switch($sample->status)
                            @case('pending') bg-yellow-100 text-yellow-800 @break
                            @case('in_progress') bg-blue-100 text-blue-800 @break
                            @case('completed') bg-green-100 text-green-800 @break
                            @case('cancelled') bg-red-100 text-red-800 @break
                        @endswitch">
                        {{ $sample->status_label }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @switch($sample->validation_status ?? 'pending')
                            @case('pending') bg-gray-100 text-gray-800 @break
                            @case('validated') bg-green-100 text-green-800 @break
                            @case('rejected') bg-red-100 text-red-800 @break
                        @endswitch">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($sample->validation_status === 'validated')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @elseif($sample->validation_status === 'rejected')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @endif
                        </svg>
                        {{ $sample->validation_status_label ?? 'Pendiente de validación' }}
                    </span>
                </div>
            </div>
            <div class="mt-4 md:mt-0 flex flex-wrap gap-2">
                <!-- Botón cargar resultados -->
                <a href="{{ route('sample.loadResults', $sample) }}" 
                   class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    Cargar Resultados
                </a>
                
                <!-- Botón validar (solo para validadores) -->
                @can('samples.validate')
                    <a href="{{ route('sample.validate.show', $sample) }}" 
                       class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Validar
                    </a>
                @endcan
                
                <!-- Botones PDF (si hay determinaciones validadas) -->
                @if($sample->determinations->where('is_validated', true)->count() > 0)
                    <a href="{{ route('sample.pdf.view', $sample) }}" target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Ver PDF ({{ $sample->determinations->where('is_validated', true)->count() }})
                    </a>
                    <a href="{{ route('sample.pdf.download', $sample) }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Descargar
                    </a>
                @endif
                
                <!-- Botón editar -->
                <a href="{{ route('sample.edit', $sample) }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                </a>
            </div>
        </div>

        <!-- Alertas -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Información Principal -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Información de la Muestra</h2>
                    
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500">Cliente</dt>
                            <dd class="text-gray-900 font-medium">{{ $sample->customer->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Fecha de Ingreso</dt>
                            <dd class="text-gray-900">{{ $sample->entry_date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Fecha de Toma</dt>
                            <dd class="text-gray-900">{{ $sample->sampling_date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Lugar</dt>
                            <dd class="text-gray-900">{{ $sample->location }}</dd>
                        </div>
                        @if($sample->address)
                        <div>
                            <dt class="text-sm text-gray-500">Dirección</dt>
                            <dd class="text-gray-900">{{ $sample->address }}</dd>
                        </div>
                        @endif
                        @if($sample->isFood())
                            @if($sample->product_name)
                            <div>
                                <dt class="text-sm text-gray-500">Producto</dt>
                                <dd class="text-gray-900">{{ $sample->product_name }}</dd>
                            </div>
                            @endif
                            @if($sample->batch)
                            <div>
                                <dt class="text-sm text-gray-500">Lote</dt>
                                <dd class="text-gray-900">{{ $sample->batch }}</dd>
                            </div>
                            @endif
                        @endif
                        @if($sample->observations)
                        <div>
                            <dt class="text-sm text-gray-500">Observaciones</dt>
                            <dd class="text-gray-900">{{ $sample->observations }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm text-gray-500">Registrado por</dt>
                            <dd class="text-gray-900">{{ $sample->creator->name ?? 'N/A' }}</dd>
                        </div>
                        @if($sample->isValidated())
                        <div class="pt-3 mt-3 border-t border-gray-200">
                            <dt class="text-sm text-gray-500">Validado por</dt>
                            <dd class="text-gray-900 font-medium">{{ $sample->validator->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Fecha de validación</dt>
                            <dd class="text-gray-900">{{ $sample->validated_at?->format('d/m/Y H:i') }}</dd>
                        </div>
                        @if($sample->validator_notes)
                        <div>
                            <dt class="text-sm text-gray-500">Notas del validador</dt>
                            <dd class="text-gray-900">{{ $sample->validator_notes }}</dd>
                        </div>
                        @endif
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Determinaciones -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Determinaciones</h2>
                        
                        <!-- Agregar Determinación -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" type="button"
                                    class="inline-flex items-center px-3 py-1.5 bg-teal-100 text-teal-700 rounded-lg hover:bg-teal-200 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Agregar
                            </button>
                            
                            <div x-show="open" @click.outside="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border z-10 p-4">
                                <form action="{{ route('sample.addDetermination', $sample) }}" method="POST">
                                    @csrf
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Determinación</label>
                                    <select name="test_id" required class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 mb-3">
                                        <option value="">Seleccionar...</option>
                                        @foreach(\App\Models\Test::orderBy('name')->get() as $test)
                                            @if(!$sample->determinations->contains('test_id', $test->id))
                                                <option value="{{ $test->id }}">{{ $test->code }} - {{ $test->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <button type="submit" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                                        Agregar Determinación
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    @php
                        // Ordenar determinaciones: padres primero, luego hijos
                        // Soporta hijos con múltiples padres (tabla pivote test_parents)
                        $orderedDeterminations = collect();
                        $processedAsParent = [];
                        $processedAsChild = [];
                        
                        // Función para verificar si un test es padre de otro
                        $isParentOf = function($parentDet, $childDet) {
                            $childTest = $childDet->test;
                            $parentTestId = $parentDet->test_id;
                            
                            // Verificar relación legacy
                            if ($childTest->parent == $parentTestId) return true;
                            
                            // Verificar relación tabla pivote
                            if ($childTest->parentTests && $childTest->parentTests->contains('id', $parentTestId)) return true;
                            
                            return false;
                        };
                        
                        // Identificar padres (tests que tienen hijos en esta muestra)
                        $parentTestIds = [];
                        foreach ($sample->determinations as $det) {
                            $allChildren = $det->test->getAllChildren();
                            $childIdsInSample = $allChildren->pluck('id')->intersect($sample->determinations->pluck('test_id'));
                            if ($childIdsInSample->count() > 0) {
                                $parentTestIds[] = $det->test_id;
                            }
                        }
                        
                        // Procesar padres y sus hijos
                        foreach ($sample->determinations as $det) {
                            if (in_array($det->id, $processedAsParent)) continue;
                            
                            if (in_array($det->test_id, $parentTestIds)) {
                                $orderedDeterminations->push(['det' => $det, 'isChild' => false, 'isParent' => true]);
                                $processedAsParent[] = $det->id;
                                
                                // Agregar hijos de este padre
                                foreach ($sample->determinations as $child) {
                                    if ($isParentOf($det, $child) && !in_array($child->id, $processedAsParent)) {
                                        $orderedDeterminations->push(['det' => $child, 'isChild' => true, 'isParent' => false]);
                                        $processedAsChild[] = $child->id;
                                    }
                                }
                            }
                        }
                        
                        // Agregar determinaciones huérfanas (sin padre en esta muestra)
                        foreach ($sample->determinations as $det) {
                            if (!in_array($det->id, $processedAsParent) && !in_array($det->id, $processedAsChild)) {
                                $hasParents = $det->test->parent || ($det->test->parentTests && $det->test->parentTests->count() > 0);
                                $orderedDeterminations->push(['det' => $det, 'isChild' => $hasParents, 'isParent' => false]);
                            }
                        }
                    @endphp

                    @if($sample->determinations->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Determinación</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Resultado</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unidad</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($orderedDeterminations as $item)
                                        @php 
                                            $det = $item['det'];
                                            $isChild = $item['isChild'];
                                            $isParent = $item['isParent'];
                                        @endphp
                                        <tr class="hover:bg-gray-50 {{ $isChild ? 'bg-gray-50' : '' }} {{ $isParent ? 'bg-teal-50' : '' }}" x-data="{ editing: false }">
                                            <td class="px-4 py-3 text-sm text-gray-900 {{ $isChild ? 'pl-8' : '' }}">
                                                @if($isChild)
                                                    <span class="text-teal-400 mr-1">↳</span>
                                                @endif
                                                {{ $det->test->code ?? 'N/A' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm {{ $isChild ? 'text-gray-700' : 'text-gray-900 font-medium' }}">
                                                {{ $det->test->name ?? 'N/A' }}
                                                @if($isParent)
                                                    <span class="ml-2 text-xs bg-teal-100 text-teal-600 px-1.5 py-0.5 rounded">Grupo</span>
                                                @endif
                                            </td>
                                            
                                            <!-- Modo Ver -->
                                            <template x-if="!editing">
                                                <td class="px-4 py-3 text-sm {{ $isParent ? 'text-gray-400 italic' : 'text-gray-900' }}">
                                                    {{ $isParent ? '-' : ($det->result ?? '-') }}
                                                </td>
                                            </template>
                                            <template x-if="!editing">
                                                <td class="px-4 py-3 text-sm text-gray-500">{{ $isParent ? '-' : ($det->unit ?? '-') }}</td>
                                            </template>
                                            <template x-if="!editing">
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                        @switch($det->status)
                                                            @case('pending') bg-yellow-100 text-yellow-800 @break
                                                            @case('in_progress') bg-blue-100 text-blue-800 @break
                                                            @case('completed') bg-green-100 text-green-800 @break
                                                        @endswitch">
                                                        {{ $det->status_label }}
                                                    </span>
                                                </td>
                                            </template>
                                            <template x-if="!editing">
                                                <td class="px-4 py-3 text-right text-sm">
                                                    <button @click="editing = true" class="text-indigo-600 hover:text-indigo-900 mr-2">
                                                        Cargar
                                                    </button>
                                                    <form action="{{ route('sample.removeDetermination', [$sample, $det]) }}" method="POST" class="inline"
                                                          onsubmit="return confirm('¿Eliminar esta determinación?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>
                                                    </form>
                                                </td>
                                            </template>

                                            <!-- Modo Editar -->
                                            <template x-if="editing">
                                                <td colspan="4" class="px-4 py-3">
                                                    <form action="{{ route('sample.updateDetermination', $det) }}" method="POST" class="flex items-center gap-2">
                                                        @csrf
                                                        @method('PUT')
                                                        @if(!$isParent)
                                                            <input type="text" name="result" value="{{ $det->result }}" placeholder="Resultado"
                                                                   class="w-24 text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                                                            <input type="text" name="reference_value" value="{{ $det->reference_value }}" placeholder="Ref."
                                                                   class="w-20 text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                                                        @else
                                                            <input type="hidden" name="result" value="">
                                                            <input type="hidden" name="reference_value" value="">
                                                            <span class="text-sm text-gray-500 italic">Grupo sin resultado</span>
                                                        @endif
                                                        <select name="status" class="text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                                                            <option value="pending" {{ $det->status == 'pending' ? 'selected' : '' }}>Pendiente</option>
                                                            <option value="in_progress" {{ $det->status == 'in_progress' ? 'selected' : '' }}>En Proceso</option>
                                                            <option value="completed" {{ $det->status == 'completed' ? 'selected' : '' }}>Completado</option>
                                                        </select>
                                                        <button type="submit" class="px-3 py-1 bg-teal-600 text-white rounded text-sm hover:bg-teal-700">
                                                            Guardar
                                                        </button>
                                                        <button type="button" @click="editing = false" class="px-3 py-1 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">
                                                            Cancelar
                                                        </button>
                                                    </form>
                                                </td>
                                            </template>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="mt-2">No hay determinaciones asignadas</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-lab-layout>
