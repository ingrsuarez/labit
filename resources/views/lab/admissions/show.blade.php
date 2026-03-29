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
                <div class="flex items-center gap-2" x-data>
                    @php
                        $validatedCount = $admission->admissionTests->where('is_validated', true)->count();
                    @endphp
                    @if($validatedCount > 0)
                    <a href="{{ route('lab.admissions.pdf.view', $admission) }}" target="_blank"
                       class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Ver PDF
                    </a>
                    <a href="{{ route('lab.admissions.pdf.download', $admission) }}"
                       class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Descargar
                    </a>
                    <button onclick="document.getElementById('emailModal').classList.remove('hidden')"
                            class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Email
                    </button>
                    @endif
                    @can('lab-labels.print')
                    <button type="button"
                            @click="$dispatch('open-label-modal', { url: '{{ route('lab.admissions.labelData', $admission) }}' })"
                            class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10v3H7zM5 10h14a2 2 0 012 2v4a2 2 0 01-2 2h-2v2H9v-2H5a2 2 0 01-2-2v-4a2 2 0 012-2zm10 4a1 1 0 100-2 1 1 0 000 2z"/>
                        </svg>
                        Etiqueta
                    </button>
                    @endcan
                    @can('lab-admissions.edit')
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
                    @endcan
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
                            @can('lab-results.validate')
                            @if($withResults > $validated)
                                <form action="{{ route('lab.admissions.validateAll', $admission) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                                        Validar Todos
                                    </button>
                                </form>
                            @endif
                            @endcan
                            @can('lab-admissions.edit')
                            <button type="button" onclick="openAddTestModal()" 
                                    class="px-3 py-1 bg-teal-600 text-white text-sm rounded-lg hover:bg-teal-700">
                                + Agregar Práctica
                            </button>
                            @endcan
                        </div>
                    </div>

                    @if($admission->admissionTests->count() > 0)
                        @php
                            $allProtocolTestIds = $admission->admissionTests->pluck('test_id')->toArray();

                            $itemsByTestId = [];
                            foreach ($admission->admissionTests as $at) {
                                $itemsByTestId[$at->test_id] = $at;
                            }

                            $parentMap = [];
                            $childOf = [];
                            $isSubParentMap = [];

                            foreach ($admission->admissionTests as $at) {
                                if (!$at->test) continue;
                                $parentIds = $at->test->parentTests->pluck('id')->toArray();
                                if ($at->test->parent) {
                                    $parentIds[] = $at->test->parent;
                                    $parentIds = array_unique($parentIds);
                                }
                                $parentsInProtocol = array_intersect($parentIds, $allProtocolTestIds);

                                if (count($parentsInProtocol) > 0) {
                                    $parentId = reset($parentsInProtocol);
                                    $childOf[$at->test_id] = $parentId;
                                    if (!isset($parentMap[$parentId])) {
                                        $parentMap[$parentId] = [];
                                    }
                                    $parentMap[$parentId][] = $at->test_id;
                                }
                            }

                            foreach ($parentMap as $testId => $children) {
                                if (isset($childOf[$testId])) {
                                    $isSubParentMap[$testId] = true;
                                }
                            }

                            // Ordenar hijos dentro de cada padre por sort_order
                            foreach ($parentMap as $parentId => &$childIds) {
                                usort($childIds, function ($a, $b) use ($itemsByTestId) {
                                    $sortA = $itemsByTestId[$a]->test->sort_order ?? 0;
                                    $sortB = $itemsByTestId[$b]->test->sort_order ?? 0;
                                    return $sortA <=> $sortB;
                                });
                            }
                            unset($childIds);

                            $roots = [];
                            foreach ($admission->admissionTests as $at) {
                                if (!isset($childOf[$at->test_id]) && !in_array($at->test_id, $roots)) {
                                    $roots[] = $at->test_id;
                                }
                            }

                            usort($roots, function ($a, $b) use ($itemsByTestId) {
                                $sortA = $itemsByTestId[$a]->test->sort_order ?? 0;
                                $sortB = $itemsByTestId[$b]->test->sort_order ?? 0;
                                return $sortA <=> $sortB;
                            });

                            $orderedItems = collect();
                            $addWithChildren = function ($testId, $level) use (
                                &$addWithChildren, $parentMap, $isSubParentMap, $itemsByTestId, &$orderedItems
                            ) {
                                if (!isset($itemsByTestId[$testId])) return;
                                $isParent = isset($parentMap[$testId]);

                                $orderedItems->push([
                                    'at' => $itemsByTestId[$testId],
                                    'level' => $level,
                                    'isParent' => $isParent,
                                    'isSubParent' => isset($isSubParentMap[$testId]),
                                    'isChild' => $level > 0,
                                    'childCount' => $isParent ? count($parentMap[$testId]) : 0,
                                ]);

                                if ($isParent) {
                                    foreach ($parentMap[$testId] as $childId) {
                                        $addWithChildren($childId, $level + 1);
                                    }
                                }
                            };

                            foreach ($roots as $rootId) {
                                $addWithChildren($rootId, 0);
                            }

                            $formIndex = 0;
                            $canEditResults = auth()->user()->can('lab-results.create');
                            $canValidate = auth()->user()->can('lab-results.validate');
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
                                        @foreach($orderedItems as $item)
                                            @php
                                                $admissionTest = $item['at'];
                                                $level = $item['level'];
                                                $hasChildren = $item['isParent'];
                                                $isChild = $item['isChild'];
                                                $isSubParent = $item['isSubParent'] ?? false;
                                                $childCount = $item['childCount'] ?? 0;

                                                $paddingLeft = $level === 0 ? 'px-4' : ($level === 1 ? 'pl-10 pr-4' : 'pl-16 pr-4');

                                                $testUnit = null;
                                                $refValue = null;
                                                $needsConfig = false;
                                                if (!$hasChildren) {
                                                    $testUnit = $admissionTest->test->unit;
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
                                            <tr class="{{ $hasChildren ? 'bg-teal-50' . ($level === 0 ? ' border-l-4 border-teal-500' : ' border-l-4 border-teal-300') : 'hover:bg-gray-50' }} {{ $isChild && !$hasChildren ? 'bg-gray-50/50' : '' }} {{ $admissionTest->is_validated ? 'bg-green-50' : '' }}">
                                                <td class="{{ $paddingLeft }} py-{{ $hasChildren ? '3' : '2' }}">
                                                    <input type="hidden" name="results[{{ $formIndex }}][id]" value="{{ $admissionTest->id }}">
                                                    <div class="flex items-center">
                                                        @if($isChild && !$hasChildren)
                                                            <span class="text-xs text-gray-400 mr-1">└</span>
                                                        @endif
                                                        @if($hasChildren && !$isChild)
                                                            <span class="text-sm font-bold text-teal-700 mr-2">{{ $admissionTest->test->code }}</span>
                                                            <span class="text-sm font-semibold text-gray-900">{{ $admissionTest->test->name }}</span>
                                                            <span class="ml-2 text-xs text-teal-600">({{ $childCount }} det.)</span>
                                                            <span class="ml-2 text-xs text-gray-500">${{ number_format($admissionTest->price, 0, ',', '.') }}</span>
                                                        @elseif($isSubParent)
                                                            <span class="text-xs font-bold text-teal-600 mr-2">{{ $admissionTest->test->code }}</span>
                                                            <span class="text-sm font-semibold text-gray-800">{{ $admissionTest->test->name }}</span>
                                                            <span class="ml-2 text-xs text-teal-500">({{ $childCount }} det.)</span>
                                                        @else
                                                            <span class="text-xs font-medium text-gray-500 mr-2">{{ $admissionTest->test->code }}</span>
                                                            <span class="text-sm text-gray-700">{{ $admissionTest->test->name }}</span>
                                                            @if($needsConfig || auth()->user()->hasRole('bioquimico'))
                                                                <button type="button"
                                                                   onclick="openConfigModal({{ $admissionTest->test->id }}, '{{ $admissionTest->test->code }}', '{{ addslashes($admissionTest->test->name) }}', '{{ $admissionTest->test->unit }}', '{{ $admissionTest->test->low }}', '{{ $admissionTest->test->high }}', '{{ addslashes($admissionTest->test->method) }}')"
                                                                   class="ml-2 px-1.5 py-0.5 text-xs bg-orange-100 text-orange-700 rounded hover:bg-orange-200"
                                                                   title="Configurar unidad y valores de referencia">
                                                                    ⚙ Config.
                                                                </button>
                                                            @endif
                                                            @if(!$isChild)
                                                                <span class="ml-2 text-xs text-gray-500">${{ number_format($admissionTest->price, 0, ',', '.') }}</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                                @if($hasChildren)
                                                    <td class="px-4 py-2 text-center text-xs text-gray-400" colspan="3">
                                                        Cargar resultados en las determinaciones ↓
                                                    </td>
                                                @else
                                                    <td class="px-4 py-1">
                                                        <input type="text"
                                                               name="results[{{ $formIndex }}][result]"
                                                               value="{{ $admissionTest->result }}"
                                                               placeholder="Resultado"
                                                               {{ $admissionTest->is_validated || !$canEditResults ? 'disabled' : '' }}
                                                               class="w-full text-center border-gray-300 rounded text-sm {{ $admissionTest->is_validated || !$canEditResults ? 'bg-gray-100' : '' }}">
                                                    </td>
                                                    <td class="px-4 py-1">
                                                        <input type="hidden" name="results[{{ $formIndex }}][unit]" value="{{ $testUnit ?? '' }}">
                                                        @if($testUnit)
                                                            <span class="text-sm text-gray-600">{{ $testUnit }}</span>
                                                        @else
                                                            <span class="text-xs text-orange-500">Sin unidad</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-1">
                                                        <input type="hidden" name="results[{{ $formIndex }}][reference_value]" value="{{ $refValue ?? '' }}">
                                                        @if($refValue)
                                                            <span class="text-sm text-gray-600">{{ $refValue }}</span>
                                                        @else
                                                            <span class="text-xs text-orange-500">Sin ref.</span>
                                                        @endif
                                                    </td>
                                                @endif
                                                <td class="px-4 py-2 text-center">
                                                    @if($admissionTest->is_validated)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <svg class="w-3 h-3 {{ !$isChild ? 'mr-1' : '' }}" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                            @if(!$isChild) Validado @endif
                                                        </span>
                                                    @elseif($admissionTest->hasResult())
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            @if($isChild) ● @else Con resultado @endif
                                                        </span>
                                                    @elseif(!$hasChildren)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                            @if($isChild) ○ @else Pendiente @endif
                                                        </span>
                                                    @else
                                                        <span class="text-xs text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 text-center">
                                                    @php
                                                        $hasValidatedChildren = false;
                                                        if ($hasChildren && isset($parentMap[$admissionTest->test_id])) {
                                                            foreach ($parentMap[$admissionTest->test_id] as $cId) {
                                                                if (isset($itemsByTestId[$cId]) && $itemsByTestId[$cId]->is_validated) {
                                                                    $hasValidatedChildren = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                        $canDelete = !$admissionTest->is_validated && !$hasValidatedChildren;
                                                    @endphp
                                                    <div class="flex items-center justify-center gap-2">
                                                        @if(!$hasChildren && $canValidate)
                                                            @if($admissionTest->is_validated)
                                                                <button type="button" onclick="submitAction('{{ route('lab.admissions.unvalidateTest', [$admission, $admissionTest]) }}')" class="text-orange-500 hover:text-orange-700 text-xs" title="Quitar validación">
                                                                    Desvalidar
                                                                </button>
                                                            @elseif($admissionTest->hasResult())
                                                                <button type="button" onclick="submitAction('{{ route('lab.admissions.validateTest', [$admission, $admissionTest]) }}')" class="text-green-500 hover:text-green-700 text-xs">
                                                                    ✓
                                                                </button>
                                                            @else
                                                                <span class="text-gray-300 text-xs">-</span>
                                                            @endif
                                                        @endif
                                                        @if($canDelete && !$isChild)
                                                            <button type="button" onclick="if(confirm('¿Eliminar esta práctica del protocolo?{{ $hasChildren ? " (Se eliminarán también las determinaciones hijas)" : "" }}')) submitAction('{{ route('lab.admissions.removeTest', [$admission, $admissionTest]) }}', 'DELETE')" class="text-red-400 hover:text-red-600" title="Eliminar práctica">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                </svg>
                                                            </button>
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
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @can('lab-results.create')
                            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Guardar Resultados
                                </button>
                            </div>
                            @endcan
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

        <!-- Estado de Pago (solo Particular) -->
        @if($admission->isParticular())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Estado de Pago</h2>
                @if($admission->payment_status === 'pagado')
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Pagado</span>
                @elseif($admission->payment_status === 'parcial')
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">Parcial</span>
                @else
                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">Pendiente</span>
                @endif
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Total a cobrar</span>
                    <p class="font-bold text-lg">${{ number_format($admission->total_to_pay, 2, ',', '.') }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Pagado</span>
                    <p class="font-bold text-lg">${{ number_format($admission->paid_amount, 2, ',', '.') }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Saldo</span>
                    <p class="font-bold text-lg {{ $admission->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                        ${{ number_format($admission->balance, 2, ',', '.') }}
                    </p>
                </div>
                <div>
                    <span class="text-gray-500">Medio de pago</span>
                    <p class="font-medium">{{ ucfirst($admission->payment_method ?? '—') }}</p>
                </div>
            </div>

            @can('lab-admissions.edit')
            @if($admission->balance > 0)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <form action="{{ route('lab.admissions.registerPayment', $admission) }}" method="POST"
                          class="flex flex-wrap items-end gap-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Medio de pago</label>
                            <select name="payment_method" required class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="mercadopago">Mercado Pago</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                                <input type="number" step="0.01" name="amount"
                                       value="{{ number_format($admission->balance, 2, '.', '') }}" max="{{ $admission->balance }}" min="0.01"
                                       required class="pl-8 w-32 border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                            <input type="text" name="payment_notes" placeholder="Opcional..."
                                   class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm transition-colors">
                            Registrar Pago
                        </button>
                    </form>
                </div>
            @endif
            @endcan
        </div>
        @endif
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
        function submitAction(url, method) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.style.display = 'none';
            var csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            if (method && method !== 'POST') {
                var m = document.createElement('input');
                m.type = 'hidden';
                m.name = '_method';
                m.value = method;
                form.appendChild(m);
            }
            document.body.appendChild(form);
            form.submit();
        }

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

    <!-- Modal de Email -->
    <div id="emailModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Enviar Informe por Email</h3>
                <form action="{{ route('lab.admissions.sendEmail', $admission) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email destinatario *</label>
                        <input type="email" name="email" required
                               value="{{ $admission->patient?->email ?? '' }}"
                               placeholder="paciente@email.com"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje adicional (opcional)</label>
                        <textarea name="message" rows="3" placeholder="Mensaje personalizado..."
                                  class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('emailModal').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                            Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($admission->auditLogs->count() > 0)
    <div class="mt-6">
        <x-audit-history :logs="$admission->auditLogs" />
    </div>
    @endif

    <!-- Modal Imprimir Etiqueta -->
    <div x-data="zebraPrintModal()"
         @open-label-modal.window="openModal($event.detail.url)"
         x-cloak>

        <template x-if="open">
            <div class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/50" @click="closeModal()"></div>

                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Imprimir Etiqueta
                        </h3>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Loading -->
                    <div x-show="loading" class="py-8 text-center">
                        <svg class="animate-spin h-8 w-8 mx-auto text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">Buscando impresoras Zebra...</p>
                    </div>

                    <!-- Zebra disponible -->
                    <div x-show="!loading && zebraAvailable" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Impresora</label>
                            <select x-model="selectedPrinter" @change="onPrinterChange()"
                                    class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                                <option value="">Seleccionar impresora...</option>
                                <template x-for="printer in printers" :key="printer.uid">
                                    <option :value="printer.name" x-text="printer.name"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad de copias</label>
                            <input type="number" x-model.number="copies" min="1" max="20"
                                   class="w-24 rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                        </div>

                        <!-- Vista previa -->
                        <div class="bg-gray-50 rounded-lg p-3 border border-dashed border-gray-300">
                            <p class="text-xs text-gray-500 mb-1 font-medium">Vista previa de la etiqueta:</p>
                            <div class="bg-white border border-gray-200 rounded p-2 text-center font-mono text-xs leading-relaxed">
                                <div class="text-lg tracking-widest">|||||||||||||||||||</div>
                                <div class="font-bold">{{ $admission->protocol_number }}</div>
                                <div>{{ Str::limit($admission->patient->full_name ?? 'N/A', 30) }}</div>
                                <div class="text-gray-500">
                                    CLINICO |
                                    MAT: {{ $admission->admissionTests->pluck('test.material_abbreviation')->unique()->filter()->implode('/') ?: 'N/A' }}
                                    &nbsp; {{ $admission->date->format('d/m/Y') }}
                                </div>
                            </div>
                        </div>

                        <button @click="print()" :disabled="printing || !selectedPrinter"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="printing">
                                <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                            </template>
                            <template x-if="!printing">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                            </template>
                            <span x-text="printing ? 'Imprimiendo...' : 'Imprimir'"></span>
                        </button>
                    </div>

                    <!-- Zebra no disponible -->
                    <div x-show="!loading && !zebraAvailable" class="space-y-4">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.072 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-yellow-800">Zebra Browser Print no detectado</p>
                                    <p class="text-xs text-yellow-700 mt-1">
                                        Para imprimir directamente a la Zebra, instale
                                        <a href="https://www.zebra.com/us/en/support-downloads/printer-software/browser-print.html"
                                           target="_blank" class="underline font-medium">Zebra Browser Print</a>
                                        y reinicie el navegador.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <p class="text-sm text-gray-600 mb-3">Puede usar la impresión vía navegador como alternativa:</p>
                            <a href="{{ route('lab.admissions.label', $admission) }}" target="_blank"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Imprimir vía Navegador
                            </a>
                        </div>
                    </div>

                    <!-- Mensajes de estado -->
                    <div x-show="error && !loading" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-700" x-text="error"></p>
                    </div>
                    <div x-show="success" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-700">Etiqueta enviada a la impresora correctamente.</p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <script src="{{ asset('js/zebra-label-print.js') }}"></script>
</x-lab-layout>

