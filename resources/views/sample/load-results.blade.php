<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-start md:justify-between mb-6">
            <div>
                <a href="{{ route('sample.show', $sample) }}" class="text-teal-600 hover:text-teal-800 text-sm flex items-center mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver al Protocolo
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Cargar Resultados</h1>
                <p class="text-gray-600 mt-1">Protocolo {{ $sample->protocol_number }} - {{ $sample->customer->name ?? 'N/A' }}</p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-2 items-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $sample->sample_type == 'agua' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                    {{ ucfirst($sample->sample_type) }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    {{ $sample->determinations->where('status', 'completed')->count() }}/{{ $sample->determinations->count() }} completadas
                </span>
            </div>
        </div>

        <!-- Instrucciones -->
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm text-blue-800 font-medium">Navegación rápida con teclado</p>
                    <p class="text-sm text-blue-700">
                        Use <kbd class="px-1.5 py-0.5 bg-blue-100 rounded text-xs font-mono">Tab</kbd> o 
                        <kbd class="px-1.5 py-0.5 bg-blue-100 rounded text-xs font-mono">Enter</kbd> para moverse al siguiente campo.
                        <kbd class="px-1.5 py-0.5 bg-blue-100 rounded text-xs font-mono">Shift+Tab</kbd> para retroceder.
                    </p>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Formulario de carga - Formato vertical tipo protocolo -->
        <form action="{{ route('sample.saveResults', $sample) }}" method="POST" id="resultsForm">
            @csrf
            
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <!-- Cabecera tipo protocolo -->
                <div class="bg-teal-600 text-white px-6 py-3">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold uppercase text-sm">
                            {{ strtoupper($sample->sample_type) }} - {{ strtoupper($sample->location) }}
                        </span>
                        <span class="text-teal-100 text-sm">
                            {{ $sample->customer->name ?? '' }}
                        </span>
                    </div>
                </div>

                <!-- Lista de determinaciones en formato vertical con jerarquía -->
                @php
                    // Ordenar determinaciones: padres primero, luego hijos
                    // Soporta múltiples padres (tabla pivote test_parents)
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
                    
                    // Identificar tests que son padres en esta muestra
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

                <div class="divide-y divide-gray-200">
                    @foreach($orderedDeterminations as $index => $item)
                        @php 
                            $det = $item['det'];
                            $isChild = $item['isChild'];
                            $isParentWithChildren = $item['isParent'] ?? false;
                        @endphp
                        <div class="p-4 hover:bg-gray-50 transition-colors {{ $det->status === 'completed' ? 'bg-green-50' : '' }} {{ $isChild ? 'pl-10 border-l-4 border-teal-200' : '' }}" 
                             id="det-block-{{ $index }}">
                            @if(!$isParentWithChildren)
                                <input type="hidden" name="determinations[{{ $index }}][id]" value="{{ $det->id }}">
                            @endif
                            
                            <!-- Nombre de la determinación y valor de referencia -->
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-start">
                                    @if($isChild)
                                        <svg class="w-4 h-4 mr-2 mt-0.5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                                        </svg>
                                    @endif
                                    <div>
                                        <h3 class="font-bold {{ $isChild ? 'text-teal-600 text-sm' : 'text-teal-700' }} uppercase {{ $isParentWithChildren ? 'text-base' : 'text-sm' }}">
                                            {{ $det->test->name ?? 'N/A' }}
                                        </h3>
                                        <span class="text-xs text-gray-500">{{ $det->test->code ?? '' }}</span>
                                        @if($isParentWithChildren)
                                            <span class="ml-2 text-xs bg-teal-100 text-teal-600 px-2 py-0.5 rounded">Grupo</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        @switch($det->status)
                                            @case('pending') bg-yellow-100 text-yellow-800 @break
                                            @case('in_progress') bg-blue-100 text-blue-800 @break
                                            @case('completed') bg-green-100 text-green-800 @break
                                        @endswitch">
                                        {{ $det->status_label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Campos en grid para entrada rápida -->
                            @if($isParentWithChildren)
                                {{-- Determinación padre: selector de categoría de valores de referencia --}}
                                @php
                                    // Obtener los IDs de los hijos de este padre en esta muestra
                                    $childTestIds = $det->test->getAllChildren()->pluck('id');
                                    $childDeterminations = $sample->determinations->whereIn('test_id', $childTestIds);
                                    
                                    // Obtener todas las categorías disponibles que tienen valores de referencia para los hijos
                                    $availableCategories = collect();
                                    foreach ($childDeterminations as $childDet) {
                                        if ($childDet->test->referenceValues) {
                                            foreach ($childDet->test->referenceValues as $refVal) {
                                                if ($refVal->category && !$availableCategories->contains('id', $refVal->category->id)) {
                                                    $availableCategories->push($refVal->category);
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <div class="bg-teal-50 rounded-lg p-4 border border-teal-200">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center text-sm text-teal-700 mb-3">
                                            <svg class="w-5 h-5 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                            </svg>
                                            <span class="font-medium">Valores de referencia según normativa:</span>
                                        </div>
                                    </div>
                                    
                                    @if($availableCategories->count() > 0)
                                        <div class="flex items-center gap-3">
                                            <select class="parent-category-select flex-1 rounded-lg border-teal-300 focus:border-teal-500 focus:ring-teal-500 text-sm font-medium"
                                                    data-parent-id="{{ $det->test_id }}"
                                                    onchange="applyParentCategory(this)">
                                                <option value="">-- Seleccionar normativa --</option>
                                                @foreach($availableCategories->sortBy('name') as $category)
                                                    <option value="{{ $category->id }}" data-code="{{ $category->code }}">
                                                        {{ $category->name }} ({{ $category->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="button" 
                                                    onclick="applyParentCategory(this.previousElementSibling)"
                                                    class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors text-sm font-medium whitespace-nowrap">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                Aplicar a todos
                                            </button>
                                        </div>
                                        <p class="text-xs text-teal-600 mt-2">
                                            Al seleccionar una normativa, se actualizarán los valores de referencia de todas las subdeterminaciones.
                                        </p>
                                    @else
                                        <p class="text-sm text-gray-500 italic">
                                            No hay valores de referencia configurados para las subdeterminaciones.
                                        </p>
                                    @endif
                                </div>
                            @else
                                {{-- Determinación normal o hija: todos los campos --}}
                                @php
                                    // Obtener los IDs de los padres de esta determinación
                                    $parentIds = [];
                                    if ($det->test->parent) {
                                        $parentIds[] = $det->test->parent;
                                    }
                                    if ($det->test->parentTests) {
                                        $parentIds = array_merge($parentIds, $det->test->parentTests->pluck('id')->toArray());
                                    }
                                    $parentIdsJson = json_encode(array_unique($parentIds));
                                    
                                    // Crear mapa de categoría -> valor de referencia para este test
                                    $refValuesByCategory = [];
                                    if ($det->test->referenceValues) {
                                        foreach ($det->test->referenceValues as $refVal) {
                                            if ($refVal->category) {
                                                $refValuesByCategory[$refVal->category->id] = $refVal->value;
                                            }
                                        }
                                    }
                                @endphp
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3">
                                    <!-- Resultado -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Resultado</label>
                                        <input type="text" 
                                               name="determinations[{{ $index }}][result]" 
                                               value="{{ $det->result }}"
                                               class="result-input w-full text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500 font-medium"
                                               data-index="{{ $index }}"
                                               placeholder="Ej: < 0, Ausente">
                                    </div>

                                    <!-- Valor de Referencia (Select o Input según disponibilidad) -->
                                    <div class="lg:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Valor de Referencia</label>
                                        @if($det->test->referenceValues && $det->test->referenceValues->count() > 0)
                                            {{-- Si hay valores predefinidos, mostrar select --}}
                                            <select name="determinations[{{ $index }}][reference_value]" 
                                                    class="result-input child-ref-select w-full text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                                    data-index="{{ $index }}"
                                                    data-test-id="{{ $det->test_id }}"
                                                    data-parent-ids="{{ $parentIdsJson }}"
                                                    data-ref-by-category="{{ json_encode($refValuesByCategory) }}">
                                                <option value="">Seleccionar normativa...</option>
                                                @foreach($det->test->referenceValues as $refValue)
                                                    <option value="{{ $refValue->value }}" 
                                                            data-category-id="{{ $refValue->category->id ?? '' }}"
                                                            {{ $det->reference_value == $refValue->value ? 'selected' : '' }}
                                                            title="{{ $refValue->category->description ?? '' }}">
                                                        {{ $refValue->value }} ({{ $refValue->category->code ?? 'N/A' }})
                                                    </option>
                                                @endforeach
                                                <option value="custom" {{ !$det->test->referenceValues->pluck('value')->contains($det->reference_value) && $det->reference_value ? 'selected' : '' }}>
                                                    -- Valor personalizado --
                                                </option>
                                            </select>
                                            {{-- Campo oculto para valor personalizado --}}
                                            <input type="text" 
                                                   name="determinations[{{ $index }}][reference_value_custom]" 
                                                   value="{{ !$det->test->referenceValues->pluck('value')->contains($det->reference_value) ? $det->reference_value : '' }}"
                                                   class="result-input w-full text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500 mt-1 hidden"
                                                   data-index="{{ $index }}"
                                                   data-custom-ref="{{ $index }}"
                                                   placeholder="Ingrese valor personalizado">
                                        @else
                                            {{-- Si no hay valores predefinidos, mostrar input --}}
                                            <input type="text" 
                                                   name="determinations[{{ $index }}][reference_value]" 
                                                   value="{{ $det->reference_value }}"
                                                   class="result-input w-full text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                                   data-index="{{ $index }}"
                                                   data-test-id="{{ $det->test_id }}"
                                                   data-parent-ids="{{ $parentIdsJson }}"
                                                   placeholder="Ej: < 500 UFC/ml">
                                        @endif
                                    </div>

                                    <!-- Método -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Método</label>
                                        <input type="text" 
                                               name="determinations[{{ $index }}][method]" 
                                               value="{{ $det->method }}"
                                               class="result-input w-full text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                               data-index="{{ $index }}"
                                               placeholder="ISO, etc.">
                                    </div>

                                    <!-- Observaciones -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Observaciones</label>
                                        <input type="text" 
                                               name="determinations[{{ $index }}][observations]" 
                                               value="{{ $det->observations }}"
                                               class="result-input w-full text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                               data-index="{{ $index }}"
                                               placeholder="Opcional">
                                    </div>

                                    <!-- Estado -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                                        <select name="determinations[{{ $index }}][status]" 
                                                class="result-input w-full text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                                data-index="{{ $index }}">
                                            <option value="pending" {{ $det->status == 'pending' ? 'selected' : '' }}>Pendiente</option>
                                            <option value="in_progress" {{ $det->status == 'in_progress' ? 'selected' : '' }}>En Proceso</option>
                                            <option value="completed" {{ $det->status == 'completed' ? 'selected' : '' }}>Completado</option>
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <!-- Unidad (solo informativo) -->
                            @if($det->unit)
                                <div class="mt-2 text-xs text-gray-500">
                                    <span class="font-medium">Unidad:</span> {{ $det->unit }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Botones de acción fijos -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 sticky bottom-0">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-600">
                            <span class="inline-flex items-center">
                                <span class="w-3 h-3 rounded-full bg-green-500 mr-1"></span>
                                <span class="font-medium">{{ $sample->determinations->where('status', 'completed')->count() }}</span> completadas
                            </span>
                            <span class="mx-2">|</span>
                            <span class="inline-flex items-center">
                                <span class="w-3 h-3 rounded-full bg-yellow-500 mr-1"></span>
                                <span class="font-medium">{{ $sample->determinations->where('status', 'pending')->count() }}</span> pendientes
                            </span>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" 
                                    onclick="markAllCompleted()"
                                    class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm font-medium">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Marcar Todo Completado
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Guardar Todo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Acciones post-guardado -->
        @if($sample->status === 'completed' && ($sample->validation_status ?? 'pending') === 'pending')
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-yellow-800">Protocolo listo para validación</p>
                            <p class="text-sm text-yellow-700">Todas las determinaciones están completadas. El protocolo puede ser validado por un usuario autorizado.</p>
                        </div>
                    </div>
                    @can('samples.validate')
                        <a href="{{ route('sample.validate.show', $sample) }}" 
                           class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors whitespace-nowrap ml-4">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Ir a Validar
                        </a>
                    @endcan
                </div>
            </div>
        @endif

        @if($sample->validation_status === 'validated')
            <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-green-800">Protocolo Validado</p>
                            <p class="text-sm text-green-700">Este protocolo ha sido validado y está disponible para descarga.</p>
                        </div>
                    </div>
                    <div class="flex gap-2 ml-4">
                        <a href="{{ route('sample.pdf.view', $sample) }}" target="_blank"
                           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors whitespace-nowrap">
                            Ver PDF
                        </a>
                        <a href="{{ route('sample.pdf.download', $sample) }}" 
                           class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors whitespace-nowrap">
                            Descargar
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        // Navegación con Enter para pasar al siguiente campo
        document.querySelectorAll('.result-input').forEach(function(input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    
                    // Obtener todos los inputs
                    const inputs = Array.from(document.querySelectorAll('.result-input'));
                    const currentIndex = inputs.indexOf(this);
                    
                    // Ir al siguiente input
                    if (currentIndex < inputs.length - 1) {
                        inputs[currentIndex + 1].focus();
                        if (inputs[currentIndex + 1].tagName === 'INPUT') {
                            inputs[currentIndex + 1].select();
                        }
                    }
                }
            });

            // Indicador visual de cambio
            input.addEventListener('change', function() {
                this.classList.add('ring-2', 'ring-yellow-400');
                this.classList.remove('ring-teal-500');
            });

            // Al hacer focus, scroll suave al bloque
            input.addEventListener('focus', function() {
                const block = this.closest('[id^="det-block-"]');
                if (block) {
                    block.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });

        // Marcar todas las determinaciones como completadas
        function markAllCompleted() {
            document.querySelectorAll('select[name$="[status]"]').forEach(function(select) {
                select.value = 'completed';
                select.classList.add('ring-2', 'ring-yellow-400');
            });
        }

        // Auto-completar estado a "completado" si se ingresa un resultado
        document.querySelectorAll('input[name$="[result]"]').forEach(function(input) {
            input.addEventListener('blur', function() {
                if (this.value.trim() !== '') {
                    const index = this.dataset.index;
                    const statusSelect = document.querySelector(`select[name="determinations[${index}][status]"]`);
                    if (statusSelect && statusSelect.value === 'pending') {
                        statusSelect.value = 'in_progress';
                    }
                }
            });
        });

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl+S para guardar
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.getElementById('resultsForm').submit();
            }
        });

        // Manejar selección de valor de referencia personalizado
        document.querySelectorAll('select[name$="[reference_value]"]').forEach(function(select) {
            const index = select.dataset.index;
            const customInput = document.querySelector(`input[data-custom-ref="${index}"]`);
            
            if (customInput) {
                // Mostrar/ocultar campo personalizado según selección
                function toggleCustomInput() {
                    if (select.value === 'custom') {
                        customInput.classList.remove('hidden');
                        customInput.required = true;
                        customInput.focus();
                    } else {
                        customInput.classList.add('hidden');
                        customInput.required = false;
                    }
                }

                select.addEventListener('change', toggleCustomInput);
                
                // Estado inicial
                if (select.value === 'custom') {
                    customInput.classList.remove('hidden');
                }
            }
        });

        // Antes de enviar el formulario, copiar valores personalizados al campo principal
        document.getElementById('resultsForm').addEventListener('submit', function(e) {
            document.querySelectorAll('select[name$="[reference_value]"]').forEach(function(select) {
                if (select.value === 'custom') {
                    const index = select.dataset.index;
                    const customInput = document.querySelector(`input[data-custom-ref="${index}"]`);
                    if (customInput && customInput.value) {
                        // Crear un input hidden con el valor personalizado
                        select.value = customInput.value;
                    }
                }
            });
        });
    </script>
</x-lab-layout>
