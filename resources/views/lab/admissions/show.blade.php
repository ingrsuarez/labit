<x-lab-layout title="Admisión {{ $admission->protocol_number }}">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('lab.admissions.index') }}" class="hover:text-teal-600">Admisiones</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span>{{ $admission->protocol_number }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Protocolo: {{ $admission->protocol_number }}</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Fecha: {{ $admission->formatted_date }} | 
                        Creado por: {{ $admission->creator?->name ?? 'N/A' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <form action="{{ route('lab.admissions.syncChildren', $admission) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="px-4 py-2 bg-teal-100 text-teal-700 rounded-lg hover:bg-teal-200 transition-colors"
                                title="Sincronizar determinaciones hijas de las prácticas">
                            Sincronizar Det.
                        </button>
                    </form>
                    <a href="{{ route('lab.admissions.edit', $admission) }}" 
                       class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Editar
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Principal -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Datos del Paciente -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Paciente</h2>
                    @if($admission->patient)
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Nombre Completo</p>
                                <p class="font-medium text-gray-900">{{ $admission->patient->full_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">DNI</p>
                                <p class="font-medium text-gray-900">{{ $admission->patient->patientId }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Fecha de Nacimiento</p>
                                <p class="font-medium text-gray-900">
                                    {{ $admission->patient->birth?->format('d/m/Y') ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Teléfono</p>
                                <p class="font-medium text-gray-900">{{ $admission->patient->phone ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500">Paciente no encontrado</p>
                    @endif
                </div>

                <!-- Datos de la Admisión -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos de la Admisión</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Obra Social</p>
                            <p class="font-medium text-gray-900">
                                {{ strtoupper($admission->insuranceRelation?->name ?? 'N/A') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Nro. Afiliado</p>
                            <p class="font-medium text-gray-900">{{ $admission->affiliate_number ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Médico Solicitante</p>
                            <p class="font-medium text-gray-900">{{ $admission->requesting_doctor ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Diagnóstico</p>
                            <p class="font-medium text-gray-900">{{ $admission->diagnosis ?? 'N/A' }}</p>
                        </div>
                    </div>
                    @if($admission->observations)
                        <div class="mt-4">
                            <p class="text-sm text-gray-500">Observaciones</p>
                            <p class="text-gray-900">{{ $admission->observations }}</p>
                        </div>
                    @endif
                </div>

                <!-- Prácticas y Resultados -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">
                            Prácticas y Resultados
                            <span class="text-sm font-normal text-gray-500">({{ $admission->admissionTests->count() }})</span>
                        </h2>
                        @php
                            $withResults = $admission->admissionTests->whereNotNull('result')->count();
                            $validated = $admission->admissionTests->where('is_validated', true)->count();
                            $total = $admission->admissionTests->count();
                        @endphp
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-gray-500">
                                <span class="font-medium text-blue-600">{{ $withResults }}</span> con resultado |
                                <span class="font-medium text-green-600">{{ $validated }}</span> validados
                            </span>
                            @if($withResults > $validated)
                                <form action="{{ route('lab.admissions.validateAll', $admission) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                                        Validar Todos
                                    </button>
                                </form>
                            @endif
                            <button type="button" onclick="openAddTestModal()" 
                                    class="px-3 py-1 bg-teal-600 text-white text-sm rounded-lg hover:bg-teal-700">
                                + Agregar Práctica
                            </button>
                        </div>
                    </div>

                    @if($admission->admissionTests->count() > 0)
                        @php
                            // IDs de tests en el protocolo que son padres
                            $testIdsInProtocol = $admission->admissionTests->pluck('test_id')->toArray();
                            
                            // Separar prácticas padre (con precio > 0) de hijos (precio = 0)
                            $parentTests = $admission->admissionTests->where('price', '>', 0);
                            $childTests = $admission->admissionTests->where('price', '<=', 0);
                            
                            // Crear un mapa de hijos por padre (solo si el padre está en el protocolo)
                            $childrenByParent = [];
                            $orphanTests = collect(); // Prácticas con precio 0 que no tienen padre en el protocolo
                            
                            foreach ($childTests as $child) {
                                $parentIds = $child->test->parentTests->pluck('id')->toArray();
                                // Filtrar solo los padres que están en el protocolo
                                $parentsInProtocol = array_intersect($parentIds, $testIdsInProtocol);
                                
                                if (count($parentsInProtocol) > 0) {
                                    // Tiene padre en el protocolo, asignar al primer padre encontrado
                                    $parentId = reset($parentsInProtocol);
                                    if (!isset($childrenByParent[$parentId])) {
                                        $childrenByParent[$parentId] = collect();
                                    }
                                    $childrenByParent[$parentId]->push($child);
                                } else {
                                    // No tiene padre en el protocolo, es huérfana - mostrar como padre
                                    $orphanTests->push($child);
                                }
                            }
                            
                            // Combinar padres reales con huérfanas
                            $parentTests = $parentTests->concat($orphanTests);
                            $formIndex = 0;
                        @endphp
                        
                        <form action="{{ route('lab.admissions.saveResults', $admission) }}" method="POST">
                            @csrf
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Práctica</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-40">Resultado</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Unidad</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-40">Valor Ref.</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-28">Estado</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($parentTests as $admissionTest)
                                            @php 
                                                $hasChildren = isset($childrenByParent[$admissionTest->test_id]) && $childrenByParent[$admissionTest->test_id]->count() > 0;
                                                
                                                // Para prácticas sin hijos, obtener unidad y valor de referencia
                                                if (!$hasChildren) {
                                                    $testUnit = $admissionTest->test->unit;
                                                    $refValue = null;
                                                    $defaultRef = $admissionTest->test->referenceValues->where('is_default', true)->first();
                                                    if ($defaultRef) {
                                                        if ($defaultRef->min_value !== null && $defaultRef->max_value !== null) {
                                                            $refValue = $defaultRef->min_value . ' - ' . $defaultRef->max_value;
                                                        } elseif ($defaultRef->value) {
                                                            $refValue = $defaultRef->value;
                                                        }
                                                    } elseif ($admissionTest->test->low !== null && $admissionTest->test->high !== null) {
                                                        $refValue = $admissionTest->test->low . ' - ' . $admissionTest->test->high;
                                                    }
                                                    $needsConfig = empty($testUnit) || empty($refValue);
                                                }
                                            @endphp
                                            <!-- Fila de la práctica padre -->
                                            <tr class="{{ $hasChildren ? 'bg-teal-50 border-l-4 border-teal-500' : 'hover:bg-gray-50' }} {{ $admissionTest->is_validated ? 'bg-green-50' : '' }}">
                                                <td class="px-4 py-3">
                                                    <input type="hidden" name="results[{{ $formIndex }}][id]" value="{{ $admissionTest->id }}">
                                                    <div class="flex items-center">
                                                        <span class="text-sm font-bold text-teal-700 mr-2">{{ $admissionTest->test->code }}</span>
                                                        <span class="text-sm font-semibold text-gray-900">{{ $admissionTest->test->name }}</span>
                                                        @if($hasChildren)
                                                            <span class="ml-2 text-xs text-teal-600">({{ $childrenByParent[$admissionTest->test_id]->count() }} det.)</span>
                                                        @elseif($needsConfig ?? false)
                                                            <button type="button"
                                                               onclick="openConfigModal({{ $admissionTest->test->id }}, '{{ $admissionTest->test->code }}', '{{ addslashes($admissionTest->test->name) }}', '{{ $admissionTest->test->unit }}', '{{ $admissionTest->test->low }}', '{{ $admissionTest->test->high }}', '{{ addslashes($admissionTest->test->method) }}')"
                                                               class="ml-2 px-1.5 py-0.5 text-xs bg-orange-100 text-orange-700 rounded hover:bg-orange-200"
                                                               title="Configurar unidad y valores de referencia">
                                                                ⚙ Config.
                                                            </button>
                                                        @endif
                                                        <span class="ml-2 text-xs text-gray-500">${{ number_format($admissionTest->price, 0, ',', '.') }}</span>
                                                    </div>
                                                </td>
                                                @if($hasChildren)
                                                    <!-- Si tiene hijos, el resultado se muestra solo como indicador -->
                                                    <td class="px-4 py-2 text-center text-xs text-gray-400" colspan="3">
                                                        Cargar resultados en las determinaciones ↓
                                                    </td>
                                                @else
                                                    <td class="px-4 py-2">
                                                        <input type="text" 
                                                               name="results[{{ $formIndex }}][result]" 
                                                               value="{{ $admissionTest->result }}"
                                                               placeholder="Resultado"
                                                               {{ $admissionTest->is_validated ? 'disabled' : '' }}
                                                               class="w-full text-center border-gray-300 rounded text-sm {{ $admissionTest->is_validated ? 'bg-gray-100' : '' }}">
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <!-- Unidad: solo lectura -->
                                                        <input type="hidden" name="results[{{ $formIndex }}][unit]" value="{{ $testUnit ?? '' }}">
                                                        @if($testUnit ?? null)
                                                            <span class="text-sm text-gray-600">{{ $testUnit }}</span>
                                                        @else
                                                            <span class="text-xs text-orange-500">Sin unidad</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <!-- Valor de referencia: solo lectura -->
                                                        <input type="hidden" name="results[{{ $formIndex }}][reference_value]" value="{{ $refValue ?? '' }}">
                                                        @if($refValue ?? null)
                                                            <span class="text-sm text-gray-600">{{ $refValue }}</span>
                                                        @else
                                                            <span class="text-xs text-orange-500">Sin ref.</span>
                                                        @endif
                                                    </td>
                                                @endif
                                                <td class="px-4 py-3 text-center">
                                                    @if($admissionTest->is_validated)
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                            Validado
                                                        </span>
                                                    @elseif($admissionTest->result)
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            Con resultado
                                                        </span>
                                                    @elseif(!$hasChildren)
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                            Pendiente
                                                        </span>
                                                    @else
                                                        <span class="text-xs text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    @php
                                                        // Verificar si tiene hijos validados
                                                        $hasValidatedChildren = false;
                                                        if ($hasChildren) {
                                                            $hasValidatedChildren = $childrenByParent[$admissionTest->test_id]->where('is_validated', true)->count() > 0;
                                                        }
                                                        $canDelete = !$admissionTest->is_validated && !$hasValidatedChildren;
                                                    @endphp
                                                    <div class="flex items-center justify-center gap-2">
                                                        @if(!$hasChildren)
                                                            @if($admissionTest->is_validated)
                                                                <form action="{{ route('lab.admissions.unvalidateTest', [$admission, $admissionTest]) }}" method="POST" class="inline">
                                                                    @csrf
                                                                    <button type="submit" class="text-orange-600 hover:text-orange-800 text-xs" title="Quitar validación">
                                                                        Desvalidar
                                                                    </button>
                                                                </form>
                                                            @elseif($admissionTest->result)
                                                                <form action="{{ route('lab.admissions.validateTest', [$admission, $admissionTest]) }}" method="POST" class="inline">
                                                                    @csrf
                                                                    <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium" title="Validar">
                                                                        Validar
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        @endif
                                                        <!-- Botón eliminar práctica (solo si no está validada y no tiene hijos validados) -->
                                                        @if($canDelete)
                                                            <form action="{{ route('lab.admissions.removeTest', [$admission, $admissionTest]) }}" method="POST" class="inline"
                                                                  onsubmit="return confirm('¿Eliminar esta práctica del protocolo?{{ $hasChildren ? ' (Se eliminarán también las determinaciones hijas)' : '' }}')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-400 hover:text-red-600" title="Eliminar práctica">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        @elseif($hasValidatedChildren)
                                                            <span class="text-gray-300 text-xs" title="Desvalide las determinaciones hijas primero">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                </svg>
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                            @php $formIndex++; @endphp
                                            
                                            <!-- Filas de determinaciones hijas -->
                                            @if($hasChildren)
                                                @foreach($childrenByParent[$admissionTest->test_id] as $childTest)
                                                    @php
                                                        // Obtener unidad y valor de referencia del test
                                                        $testUnit = $childTest->test->unit;
                                                        
                                                        // Buscar valor de referencia: primero de referenceValues, luego low/high
                                                        $refValue = null;
                                                        $defaultRef = $childTest->test->referenceValues->where('is_default', true)->first();
                                                        if ($defaultRef) {
                                                            if ($defaultRef->min_value !== null && $defaultRef->max_value !== null) {
                                                                $refValue = $defaultRef->min_value . ' - ' . $defaultRef->max_value;
                                                            } elseif ($defaultRef->value) {
                                                                $refValue = $defaultRef->value;
                                                            }
                                                        } elseif ($childTest->test->low !== null && $childTest->test->high !== null) {
                                                            $refValue = $childTest->test->low . ' - ' . $childTest->test->high;
                                                        }
                                                        
                                                        // Verificar si falta configuración
                                                        $needsConfig = empty($testUnit) || empty($refValue);
                                                    @endphp
                                                    <tr class="hover:bg-gray-50 bg-gray-50/50 {{ $childTest->is_validated ? 'bg-green-50' : '' }}">
                                                        <td class="px-4 py-2 pl-10">
                                                            <input type="hidden" name="results[{{ $formIndex }}][id]" value="{{ $childTest->id }}">
                                                            <div class="flex items-center">
                                                                <span class="text-xs text-gray-400 mr-1">└</span>
                                                                <span class="text-xs font-medium text-gray-500 mr-2">{{ $childTest->test->code }}</span>
                                                                <span class="text-sm text-gray-700">{{ $childTest->test->name }}</span>
                                                                @if($needsConfig)
                                                                    <button type="button"
                                                                       onclick="openConfigModal({{ $childTest->test->id }}, '{{ $childTest->test->code }}', '{{ addslashes($childTest->test->name) }}', '{{ $childTest->test->unit }}', '{{ $childTest->test->low }}', '{{ $childTest->test->high }}', '{{ addslashes($childTest->test->method) }}')"
                                                                       class="ml-2 px-1.5 py-0.5 text-xs bg-orange-100 text-orange-700 rounded hover:bg-orange-200"
                                                                       title="Configurar unidad y valores de referencia">
                                                                        ⚙ Config.
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-1">
                                                            <input type="text" 
                                                                   name="results[{{ $formIndex }}][result]" 
                                                                   value="{{ $childTest->result }}"
                                                                   placeholder="Resultado"
                                                                   {{ $childTest->is_validated ? 'disabled' : '' }}
                                                                   class="w-full text-center border-gray-300 rounded text-sm {{ $childTest->is_validated ? 'bg-gray-100' : '' }}">
                                                        </td>
                                                        <td class="px-4 py-1">
                                                            <!-- Unidad: solo lectura, se guarda oculto -->
                                                            <input type="hidden" name="results[{{ $formIndex }}][unit]" value="{{ $testUnit }}">
                                                            @if($testUnit)
                                                                <span class="text-sm text-gray-600">{{ $testUnit }}</span>
                                                            @else
                                                                <span class="text-xs text-orange-500">Sin unidad</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-1">
                                                            <!-- Valor de referencia: solo lectura -->
                                                            <input type="hidden" name="results[{{ $formIndex }}][reference_value]" value="{{ $refValue }}">
                                                            @if($refValue)
                                                                <span class="text-sm text-gray-600">{{ $refValue }}</span>
                                                            @else
                                                                <span class="text-xs text-orange-500">Sin ref.</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            @if($childTest->is_validated)
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                                    </svg>
                                                                </span>
                                                            @elseif($childTest->result)
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                    ●
                                                                </span>
                                                            @else
                                                                <span class="text-gray-300 text-xs">○</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            @if($childTest->is_validated)
                                                                <form action="{{ route('lab.admissions.unvalidateTest', [$admission, $childTest]) }}" method="POST" class="inline">
                                                                    @csrf
                                                                    <button type="submit" class="text-orange-500 hover:text-orange-700 text-xs">
                                                                        ✕
                                                                    </button>
                                                                </form>
                                                            @elseif($childTest->result)
                                                                <form action="{{ route('lab.admissions.validateTest', [$admission, $childTest]) }}" method="POST" class="inline">
                                                                    @csrf
                                                                    <button type="submit" class="text-green-500 hover:text-green-700 text-xs">
                                                                        ✓
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <span class="text-gray-300 text-xs">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @php $formIndex++; @endphp
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Guardar Resultados
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="px-6 py-12 text-center text-gray-500">
                            No hay prácticas en esta admisión
                        </div>
                    @endif
                </div>
            </div>

            <!-- Columna Lateral - Resumen -->
            <div class="space-y-6">
                <!-- Resumen de Totales -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Resumen</h2>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-gray-600">Total Obra Social</span>
                            <span class="font-semibold text-teal-600 text-lg">
                                ${{ number_format($admission->total_insurance, 2, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-gray-600">Total Paciente</span>
                            <span class="font-medium text-gray-900">
                                ${{ number_format($admission->total_patient, 2, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-gray-600">Total Copagos</span>
                            <span class="font-medium text-gray-900">
                                ${{ number_format($admission->total_copago, 2, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-3 bg-gray-50 -mx-6 px-6 rounded-b-lg">
                            <span class="font-semibold text-gray-800">TOTAL GENERAL</span>
                            <span class="font-bold text-xl text-gray-900">
                                ${{ number_format($admission->total, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Estado del Protocolo</h2>
                    
                    @php
                        $total = $admission->admissionTests->count();
                        $withResults = $admission->admissionTests->whereNotNull('result')->count();
                        $validated = $admission->admissionTests->where('is_validated', true)->count();
                        $resultsPercent = $total > 0 ? ($withResults / $total) * 100 : 0;
                        $validatedPercent = $total > 0 ? ($validated / $total) * 100 : 0;
                    @endphp
                    
                    <div class="space-y-4">
                        <!-- Resultados cargados -->
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Resultados Cargados</span>
                                <span class="font-medium text-blue-600">
                                    {{ $withResults }} / {{ $total }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $resultsPercent }}%"></div>
                            </div>
                        </div>
                        
                        <!-- Validados -->
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Prácticas Validadas</span>
                                <span class="font-medium text-green-600">
                                    {{ $validated }} / {{ $total }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $validatedPercent }}%"></div>
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Paga Obra Social</span>
                                <span class="font-medium">
                                    {{ $admission->admissionTests->where('paid_by_patient', false)->count() }}
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Paga Paciente</span>
                                <span class="font-medium">
                                    {{ $admission->admissionTests->where('paid_by_patient', true)->count() }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estado general -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        @if($validated === $total && $total > 0)
                            <div class="flex items-center gap-2 text-green-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-medium">Protocolo Completo</span>
                            </div>
                        @elseif($withResults === $total && $total > 0)
                            <div class="flex items-center gap-2 text-blue-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-medium">Pendiente Validación</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-yellow-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-medium">En Proceso</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Configurar Determinación -->
    <div id="modal-config-test" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <form id="form-config-test" method="POST">
                @csrf
                @method('PATCH')
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Configurar Determinación</h3>
                    <button type="button" onclick="closeConfigModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <div class="px-6 py-4">
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-500">Determinación</p>
                        <p class="font-medium text-gray-900" id="config-test-name">-</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unidad *</label>
                            <input type="text" name="unit" id="config-unit" required
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="Ej: ml/dl, UFC/100ml, mg/L">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valor Mínimo</label>
                            <input type="text" name="low" id="config-low"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="Ej: 2000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valor Máximo</label>
                            <input type="text" name="high" id="config-high"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="Ej: 8000">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Método</label>
                            <input type="text" name="method" id="config-method"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="Ej: Espectrofotometría">
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" onclick="closeConfigModal()"
                            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Agregar Práctica -->
    <div id="modal-add-test" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[80vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Agregar Práctica</h3>
                <button type="button" onclick="closeAddTestModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="px-6 py-4">
                <input type="text" id="add-test-search" 
                       placeholder="Buscar por código o nombre... (Enter para agregar)"
                       class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                       onkeyup="searchTestsToAdd(this.value)"
                       onkeydown="if(event.key === 'Enter') { event.preventDefault(); addFirstTest(); }">
            </div>
            
            <div class="flex-1 overflow-y-auto px-6 pb-4">
                <div id="add-test-results" class="space-y-1">
                    <p class="text-sm text-gray-500 text-center py-4">Escriba para buscar prácticas...</p>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                <button type="button" onclick="closeAddTestModal()"
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Variables para el buscador de prácticas
        let allTests = @json($availableTests);
        let filteredTests = [];

        function openConfigModal(testId, code, name, unit, low, high, method) {
            document.getElementById('form-config-test').action = '/tests/' + testId + '/quick';
            document.getElementById('config-test-name').textContent = code + ' - ' + name;
            document.getElementById('config-unit').value = unit || '';
            document.getElementById('config-low').value = low || '';
            document.getElementById('config-high').value = high || '';
            document.getElementById('config-method').value = method || '';
            document.getElementById('modal-config-test').classList.remove('hidden');
        }

        function closeConfigModal() {
            document.getElementById('modal-config-test').classList.add('hidden');
        }

        function openAddTestModal() {
            document.getElementById('add-test-search').value = '';
            document.getElementById('add-test-results').innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Escriba para buscar prácticas...</p>';
            document.getElementById('modal-add-test').classList.remove('hidden');
            document.getElementById('add-test-search').focus();
        }

        function closeAddTestModal() {
            document.getElementById('modal-add-test').classList.add('hidden');
        }

        function searchTestsToAdd(query) {
            const resultsContainer = document.getElementById('add-test-results');
            
            if (query.length < 2) {
                resultsContainer.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Escriba al menos 2 caracteres...</p>';
                filteredTests = [];
                return;
            }

            const lowerQuery = query.toLowerCase();
            
            // Filtrar y ordenar por relevancia
            filteredTests = allTests
                .filter(test => 
                    test.code.toLowerCase().includes(lowerQuery) || 
                    test.name.toLowerCase().includes(lowerQuery)
                )
                .sort((a, b) => {
                    const aCode = a.code.toLowerCase();
                    const bCode = b.code.toLowerCase();
                    const aName = a.name.toLowerCase();
                    const bName = b.name.toLowerCase();
                    
                    // Prioridad 1: Código exacto
                    if (aCode === lowerQuery && bCode !== lowerQuery) return -1;
                    if (bCode === lowerQuery && aCode !== lowerQuery) return 1;
                    
                    // Prioridad 2: Código empieza con el texto
                    const aCodeStarts = aCode.startsWith(lowerQuery);
                    const bCodeStarts = bCode.startsWith(lowerQuery);
                    if (aCodeStarts && !bCodeStarts) return -1;
                    if (bCodeStarts && !aCodeStarts) return 1;
                    
                    // Prioridad 3: Código contiene el texto
                    const aCodeContains = aCode.includes(lowerQuery);
                    const bCodeContains = bCode.includes(lowerQuery);
                    if (aCodeContains && !bCodeContains) return -1;
                    if (bCodeContains && !aCodeContains) return 1;
                    
                    // Prioridad 4: Nombre empieza con el texto
                    const aNameStarts = aName.startsWith(lowerQuery);
                    const bNameStarts = bName.startsWith(lowerQuery);
                    if (aNameStarts && !bNameStarts) return -1;
                    if (bNameStarts && !aNameStarts) return 1;
                    
                    // Por defecto, ordenar por código
                    return aCode.localeCompare(bCode);
                })
                .slice(0, 10);

            if (filteredTests.length === 0) {
                resultsContainer.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No se encontraron prácticas</p>';
                return;
            }

            const csrfToken = '{{ csrf_token() }}';
            const insuranceNbu = {{ $admission->insuranceRelation->nbu_value ?? 1 }};
            
            resultsContainer.innerHTML = filteredTests.map((test, index) => {
                // Calcular precio: usar precio del test, o NBU * NBU de obra social, mínimo 1
                let price = test.price || 0;
                if (price === 0 && test.nbu) {
                    price = test.nbu * insuranceNbu;
                }
                if (price === 0) price = 1; // Mínimo 1 para que aparezca como padre
                
                return `
                <form action="{{ route('lab.admissions.addTest', $admission) }}" method="POST" class="block" id="add-test-form-${index}">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="test_id" value="${test.id}">
                    <input type="hidden" name="price" value="${price}">
                    <input type="hidden" name="authorization_status" value="not_required">
                    <button type="submit" class="w-full text-left px-4 py-3 hover:bg-teal-50 rounded-lg border border-gray-200 mb-1 flex justify-between items-center ${index === 0 ? 'bg-teal-50 border-teal-300' : ''}">
                        <div>
                            <span class="font-medium text-teal-600">${test.code}</span>
                            <span class="text-gray-700 ml-2">${test.name}</span>
                        </div>
                        <span class="text-sm text-gray-500">$${price.toLocaleString()}</span>
                    </button>
                </form>
            `}).join('');
        }

        function addFirstTest() {
            if (filteredTests.length > 0) {
                const form = document.getElementById('add-test-form-0');
                if (form) form.submit();
            }
        }

        // Cerrar modales con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeConfigModal();
                closeAddTestModal();
            }
        });

        // Cerrar modales al hacer clic fuera
        document.getElementById('modal-config-test').addEventListener('click', function(e) {
            if (e.target === this) {
                closeConfigModal();
            }
        });

        document.getElementById('modal-add-test').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddTestModal();
            }
        });
    </script>
</x-lab-layout>

