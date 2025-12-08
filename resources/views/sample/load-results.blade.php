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
                    $orderedDeterminations = collect();
                    $processed = [];
                    
                    foreach ($sample->determinations as $det) {
                        if (in_array($det->id, $processed)) continue;
                        
                        // Si no tiene padre o su padre no está en la muestra
                        if (!$det->test->parent) {
                            $orderedDeterminations->push(['det' => $det, 'isChild' => false]);
                            $processed[] = $det->id;
                            
                            // Buscar hijos
                            foreach ($sample->determinations as $child) {
                                if ($child->test->parent == $det->test_id && !in_array($child->id, $processed)) {
                                    $orderedDeterminations->push(['det' => $child, 'isChild' => true]);
                                    $processed[] = $child->id;
                                }
                            }
                        }
                    }
                    
                    // Agregar huérfanos
                    foreach ($sample->determinations as $det) {
                        if (!in_array($det->id, $processed)) {
                            $isChild = $det->test->parent ? true : false;
                            $orderedDeterminations->push(['det' => $det, 'isChild' => $isChild]);
                        }
                    }
                @endphp

                <div class="divide-y divide-gray-200">
                    @foreach($orderedDeterminations as $index => $item)
                        @php 
                            $det = $item['det'];
                            $isChild = $item['isChild'];
                            $isParentWithChildren = !$isChild && $sample->determinations->where('test.parent', $det->test_id)->count() > 0;
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
                                {{-- Determinación padre: solo lectura, su estado depende de los hijos --}}
                                {{-- No se incluye en el formulario, no hay campos editables --}}
                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-5 h-5 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Este grupo contiene subdeterminaciones. El estado se actualiza automáticamente según los resultados de cada una.</span>
                                    </div>
                                </div>
                            @else
                                {{-- Determinación normal o hija: todos los campos --}}
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
                                                    class="result-input w-full text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                                    data-index="{{ $index }}">
                                                <option value="">Seleccionar normativa...</option>
                                                @foreach($det->test->referenceValues as $refValue)
                                                    <option value="{{ $refValue->value }}" 
                                                            {{ $det->reference_value == $refValue->value ? 'selected' : '' }}
                                                            title="{{ $refValue->category->description ?? '' }}">
                                                        {{ $refValue->value }} ({{ $refValue->category->code }})
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
