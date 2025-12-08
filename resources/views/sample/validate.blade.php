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
                <h1 class="text-2xl font-bold text-gray-800">Validar Protocolo</h1>
                <p class="text-gray-600 mt-1">{{ $sample->protocol_number }} - {{ $sample->customer->name ?? 'N/A' }}</p>
            </div>
            <div class="mt-4 md:mt-0 flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $sample->sample_type == 'agua' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                    {{ ucfirst($sample->sample_type) }}
                </span>
                @php
                    $validatedCount = $sample->determinations->where('is_validated', true)->count();
                    $totalCount = $sample->determinations->count();
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    {{ $validatedCount > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($validatedCount > 0)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @endif
                    </svg>
                    {{ $validatedCount }}/{{ $totalCount }} validadas
                </span>
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

        <!-- Instrucciones -->
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm text-blue-800 font-medium">Validación por determinación</p>
                    <p class="text-sm text-blue-700">
                        Seleccione las determinaciones que desea incluir en el informe. 
                        Solo las determinaciones validadas aparecerán en el PDF.
                        Las determinaciones deben estar completadas para poder validarlas.
                    </p>
                </div>
            </div>
        </div>

        @php
            // Ordenar determinaciones: padres primero, luego hijos
            $orderedDeterminations = collect();
            $processed = [];
            
            foreach ($sample->determinations as $det) {
                if (in_array($det->id, $processed)) continue;
                
                if (!$det->test->parent) {
                    $hasChildren = $sample->determinations->where('test.parent', $det->test_id)->count() > 0;
                    $orderedDeterminations->push(['det' => $det, 'isChild' => false, 'isParent' => $hasChildren]);
                    $processed[] = $det->id;
                    
                    foreach ($sample->determinations as $child) {
                        if ($child->test->parent == $det->test_id && !in_array($child->id, $processed)) {
                            $orderedDeterminations->push(['det' => $child, 'isChild' => true, 'isParent' => false]);
                            $processed[] = $child->id;
                        }
                    }
                }
            }
            
            foreach ($sample->determinations as $det) {
                if (!in_array($det->id, $processed)) {
                    $isChild = $det->test->parent ? true : false;
                    $orderedDeterminations->push(['det' => $det, 'isChild' => $isChild, 'isParent' => false]);
                }
            }
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Lista de determinaciones (IZQUIERDA - 2/3) -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <!-- Encabezado -->
                    <div class="bg-gradient-to-r from-teal-600 to-teal-700 text-white p-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-lg font-bold uppercase">{{ $sample->sample_type }} - {{ $sample->location }}</h2>
                                <p class="text-teal-100 text-sm">Seleccione las determinaciones que desea validar para el informe</p>
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-bold">{{ $sample->protocol_number }}</div>
                                <div class="text-teal-100 text-sm">{{ $sample->customer->name ?? '' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de determinaciones -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">
                                        <input type="checkbox" id="selectAllCheckbox" 
                                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                               onchange="toggleSelectAll(this)">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Determinación</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Resultado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referencia</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Validada</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($orderedDeterminations as $item)
                                    @php 
                                        $det = $item['det'];
                                        $isChild = $item['isChild'];
                                        $isParent = $item['isParent'];
                                        $canValidate = $det->status === 'completed';
                                    @endphp
                                    <tr class="{{ $det->is_validated ? 'bg-green-50' : ($det->status !== 'completed' ? 'bg-gray-50' : '') }} {{ $isChild ? 'border-l-4 border-teal-300' : '' }}">
                                        <td class="px-4 py-3">
                                            @if($canValidate)
                                                <input type="checkbox" 
                                                       name="determinations[]" 
                                                       value="{{ $det->id }}"
                                                       form="bulkValidationForm"
                                                       class="det-checkbox rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                                       data-status="{{ $det->status }}"
                                                       {{ $det->is_validated ? 'checked' : '' }}>
                                            @else
                                                <span class="text-gray-400" title="Debe estar completada">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 {{ $isChild ? 'pl-8' : '' }}">
                                            <div class="flex items-start">
                                                @if($isChild)
                                                    <span class="text-teal-400 mr-2">↳</span>
                                                @endif
                                                <div>
                                                    <div class="text-sm font-medium {{ $isParent ? 'text-teal-700' : 'text-gray-900' }}">
                                                        {{ $det->test->name ?? 'N/A' }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">{{ $det->test->code ?? '' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if(!$isParent)
                                                <span class="font-medium text-gray-900">{{ $det->result ?? '-' }}</span>
                                                @if($det->unit)
                                                    <span class="text-gray-500">{{ $det->unit }}</span>
                                                @endif
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $det->reference_value ?? '-' }}
                                        </td>
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
                                        <td class="px-4 py-3 text-center">
                                            @if($det->is_validated)
                                                <span class="inline-flex items-center text-green-600" title="Validada por {{ $det->determinationValidator->name ?? 'N/A' }}">
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                </span>
                                            @else
                                                <span class="text-gray-300">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($canValidate)
                                                <form action="{{ route('sample.determination.toggleValidation', $det) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="px-3 py-1 text-xs rounded {{ $det->is_validated ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }} transition-colors">
                                                        {{ $det->is_validated ? 'Quitar' : 'Validar' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-400">Incompleta</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer con leyenda -->
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                        <div class="flex flex-wrap gap-4 text-xs">
                            <span class="inline-flex items-center">
                                <span class="w-2.5 h-2.5 rounded bg-green-100 border border-green-300 mr-1.5"></span>
                                Validadas
                            </span>
                            <span class="inline-flex items-center">
                                <span class="w-2.5 h-2.5 rounded bg-gray-100 border border-gray-300 mr-1.5"></span>
                                Incompletas
                            </span>
                            <span class="inline-flex items-center">
                                <span class="w-2.5 h-2.5 rounded bg-white border border-gray-300 mr-1.5"></span>
                                Completadas sin validar
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de acciones (DERECHA - 1/3) -->
            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-4">
                    <!-- Resumen compacto -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Resumen
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-gray-800">{{ $totalCount }}</div>
                                <div class="text-xs text-gray-500">Total</div>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ $sample->determinations->where('status', 'completed')->count() }}</div>
                                <div class="text-xs text-blue-600">Completadas</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-green-600">{{ $validatedCount }}</div>
                                <div class="text-xs text-green-600">Validadas</div>
                            </div>
                            <div class="bg-yellow-50 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-yellow-600">{{ $totalCount - $validatedCount }}</div>
                                <div class="text-xs text-yellow-600">Sin validar</div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Acciones</h3>
                        <form action="{{ route('sample.validateDeterminations', $sample) }}" method="POST" id="bulkValidationForm">
                            @csrf
                            <input type="hidden" name="action" value="validate" id="bulkAction">
                            
                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Notas del Validador</label>
                                <textarea name="validator_notes" rows="2" 
                                          class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                          placeholder="Opcional...">{{ $sample->validator_notes }}</textarea>
                            </div>

                            <div class="space-y-2">
                                <button type="submit" onclick="document.getElementById('bulkAction').value='validate'"
                                        class="w-full px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium text-sm flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Validar Seleccionadas
                                </button>
                                <button type="submit" onclick="document.getElementById('bulkAction').value='unvalidate'"
                                        class="w-full px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Quitar Validación
                                </button>
                            </div>
                        </form>

                        <div class="mt-3 pt-3 border-t border-gray-200 space-y-2">
                            <button type="button" onclick="selectAllCompleted()" 
                                    class="w-full px-3 py-1.5 text-xs bg-blue-50 text-blue-700 rounded hover:bg-blue-100 transition-colors">
                                Seleccionar completadas
                            </button>
                            <button type="button" onclick="deselectAll()" 
                                    class="w-full px-3 py-1.5 text-xs bg-gray-50 text-gray-700 rounded hover:bg-gray-100 transition-colors">
                                Deseleccionar todas
                            </button>
                        </div>
                    </div>

                    <!-- Informe PDF -->
                    @if($validatedCount > 0)
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg shadow-sm p-4 border border-green-200">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-green-800">Informe Listo</h3>
                                    <p class="text-xs text-green-600">{{ $validatedCount }} determinaciones</p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <a href="{{ route('sample.pdf.view', $sample) }}" target="_blank"
                                   class="w-full flex items-center justify-center px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Ver PDF
                                </a>
                                <a href="{{ route('sample.pdf.download', $sample) }}" 
                                   class="w-full flex items-center justify-center px-3 py-2 bg-white text-green-700 border border-green-300 rounded-lg hover:bg-green-50 transition-colors text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Descargar PDF
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="bg-yellow-50 rounded-lg shadow-sm p-4 border border-yellow-200">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-yellow-800">Sin validar</p>
                                    <p class="text-xs text-yellow-700">Valide determinaciones para generar el informe.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Notas actuales -->
                    @if($sample->validator_notes)
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                            <p class="text-xs font-medium text-gray-600 mb-1">Notas guardadas:</p>
                            <p class="text-sm text-gray-700">{{ $sample->validator_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.det-checkbox');
            checkboxes.forEach(cb => {
                if (cb.dataset.status === 'completed') {
                    cb.checked = checkbox.checked;
                }
            });
        }

        function selectAllCompleted() {
            const checkboxes = document.querySelectorAll('.det-checkbox');
            checkboxes.forEach(cb => {
                if (cb.dataset.status === 'completed') {
                    cb.checked = true;
                }
            });
            document.getElementById('selectAllCheckbox').checked = true;
        }

        function deselectAll() {
            const checkboxes = document.querySelectorAll('.det-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('selectAllCheckbox').checked = false;
        }

        // Actualizar checkbox principal según el estado de los individuales
        document.querySelectorAll('.det-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const allChecked = document.querySelectorAll('.det-checkbox:checked').length === 
                                   document.querySelectorAll('.det-checkbox[data-status="completed"]').length;
                document.getElementById('selectAllCheckbox').checked = allChecked;
            });
        });
    </script>
</x-lab-layout>
