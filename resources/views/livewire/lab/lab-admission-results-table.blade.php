<div>
    <div>
        <table class="w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Práctica</th>
                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase w-28">Resultado</th>
                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase w-16">Unidad</th>
                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase w-28">Valor Ref.</th>
                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase w-12" title="Ratificado: valor anormal/atípico verificado por bioquímico">Ratif.</th>
                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Estado</th>
                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php $formIndex = 0; @endphp
                @foreach($orderedItems as $item)
                    @php
                        $admissionTest = $item['at'];
                        $level = $item['level'];
                        $hasChildren = $item['isParent'];
                        $isChild = $item['isChild'];
                        $isSubParent = $item['isSubParent'] ?? false;
                        $childCount = $item['childCount'] ?? 0;
                    @endphp
                    @if($isRecepcionLab && $isChild && !$hasChildren)
                        @continue
                    @endif
                    @php
                        $paddingLeft = $level === 0 ? 'px-4' : ($level === 1 ? 'pl-10 pr-4' : 'pl-16 pr-4');
                        $testUnit = null;
                        $refValue = null;
                        if (!$hasChildren) {
                            $testUnit = $admissionTest->test->unit;
                            $refValue = \App\Livewire\Lab\LabAdmissionResultsTable::referenceLineForTest($admissionTest);
                        }
                        $directParentKey = (string) ($childOf[$admissionTest->test_id] ?? '');
                        $grandParentKey = ($directParentKey !== '') ? (string) ($childOf[(int) $directParentKey] ?? '') : '';
                    @endphp
                    <tr wire:key="at-{{ $admissionTest->id }}"
                        @if($hasChildren) wire:ignore.self @endif
                        @if($isChild) data-group="{{ $directParentKey }}" {{ $grandParentKey !== '' ? "data-parent-group=\"{$grandParentKey}\"" : '' }} @endif
                        @if($hasChildren) onclick="labAccordion.toggle(this, '{{ (string) $admissionTest->test_id }}')" @endif
                        @if($isChild && $hasChildren) data-sub-parent-id="{{ (string) $admissionTest->test_id }}" @endif
                        class="{{ $hasChildren ? 'bg-teal-50' . ($level === 0 ? ' border-l-4 border-teal-500' : ' border-l-4 border-teal-300') : 'hover:bg-gray-50' }} {{ $isChild && !$hasChildren ? 'bg-gray-50/50' : '' }} {{ $admissionTest->is_validated ? 'bg-green-50' : '' }}{{ $hasChildren ? ' cursor-pointer select-none' : '' }}">
                        <td class="{{ $paddingLeft }} py-{{ $hasChildren ? '3' : '2' }}">
                            <input type="hidden" name="results[{{ $formIndex }}][id]" value="{{ $admissionTest->id }}">
                            <div class="flex items-center">
                                @if($isChild && !$hasChildren)
                                    <span class="text-xs text-gray-400 mr-1">└</span>
                                @endif
                                @if($hasChildren && !$isChild)
                                    <svg data-chevron class="w-4 h-4 text-teal-500 mr-1 flex-shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                    <span class="text-sm font-bold text-teal-700 mr-2">{{ $admissionTest->test->code }}</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $admissionTest->test->name }}</span>
                                    <span class="ml-2 text-xs text-teal-600">({{ $childCount }} det.)</span>
                                    <span class="ml-2 text-xs text-gray-500">${{ number_format($admissionTest->price, 0, ',', '.') }}</span>
                                @elseif($isSubParent)
                                    <svg data-chevron class="w-3.5 h-3.5 text-teal-400 mr-1 flex-shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                    <span class="text-xs font-bold text-teal-600 mr-2">{{ $admissionTest->test->code }}</span>
                                    <span class="text-sm font-semibold text-gray-800">{{ $admissionTest->test->name }}</span>
                                    <span class="ml-2 text-xs text-teal-500">({{ $childCount }} det.)</span>
                                @else
                                    <span class="text-xs font-medium text-gray-500 mr-2">{{ $admissionTest->test->code }}</span>
                                    <span class="text-sm text-gray-700">{{ $admissionTest->test->name }}</span>
                                    @if(auth()->user()->hasRole('admin'))
                                        <button type="button"
                                                onclick="openConfigModal({{ $admissionTest->test->id }}, '{{ $admissionTest->test->code }}', '{{ addslashes($admissionTest->test->name) }}', '{{ $admissionTest->test->unit }}', '{{ $admissionTest->test->low }}', '{{ $admissionTest->test->high }}', '{{ addslashes($admissionTest->test->method) }}')"
                                                class="ml-2 inline-flex shrink-0 items-center justify-center rounded p-0.5 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-1"
                                                title="Configurar unidad y valores de referencia de la práctica"
                                                aria-label="Configurar unidad y valores de referencia de la práctica">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </button>
                                    @endif
                                    @if(!$isChild)
                                        <span class="ml-2 text-xs text-gray-500">${{ number_format($admissionTest->price, 0, ',', '.') }}</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                        @if($hasChildren)
                            <td class="px-2 py-2 text-center text-xs text-gray-400" colspan="4">Cargar resultados en las determinaciones ↓</td>
                        @else
                            <td class="px-2 py-1">
                                <input type="text" name="results[{{ $formIndex }}][result]" value="{{ $admissionTest->result }}" placeholder="Resultado"
                                       {{ $admissionTest->is_validated || !$canEditResults ? 'disabled' : '' }}
                                       class="w-full text-center border-gray-300 rounded text-sm {{ $admissionTest->is_validated || !$canEditResults ? 'bg-gray-100' : '' }}">
                            </td>
                            <td class="px-2 py-1">
                                <input type="hidden" name="results[{{ $formIndex }}][unit]" value="{{ $testUnit ?? '' }}">
                                @if($testUnit)
                                    <span class="text-sm text-gray-600">{{ $testUnit }}</span>
                                @else
                                    <span class="text-xs text-orange-500">Sin unidad</span>
                                @endif
                            </td>
                            <td class="px-2 py-1">
                                <input type="hidden" name="results[{{ $formIndex }}][reference_value]" value="{{ $refValue ?? '' }}">
                                @if($refValue)
                                    <span class="text-sm text-gray-600">{{ $refValue }}</span>
                                @else
                                    <span class="text-xs text-orange-500">Sin ref.</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 text-center">
                                <input type="hidden" name="results[{{ $formIndex }}][is_ratified]" value="0">
                                <input type="checkbox" name="results[{{ $formIndex }}][is_ratified]" value="1"
                                       {{ $admissionTest->is_ratified ? 'checked' : '' }}
                                       {{ !$canValidate ? 'disabled' : '' }}
                                       title="Valor anormal/atípico — verificado por bioquímico (editable también después de validar)"
                                       class="h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500 {{ !$canValidate ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }}">
                            </td>
                        @endif
                        <td class="px-2 py-2 text-center">
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
                        <td class="px-2 py-2 text-center">
                            @php
                                $hasValidatedChildren = false;
                                $itemsByTestIdMap = $admission->admissionTests->keyBy('test_id');
                                if ($hasChildren && isset($parentMap[$admissionTest->test_id])) {
                                    foreach ($parentMap[$admissionTest->test_id] as $cId) {
                                        if (isset($itemsByTestIdMap[$cId]) && $itemsByTestIdMap[$cId]->is_validated) {
                                            $hasValidatedChildren = true;
                                            break;
                                        }
                                    }
                                }
                                $canDelete = ! $admissionTest->is_validated && ! $hasValidatedChildren;
                                if ($isRecepcionLab && $admissionTest->hasResult()) {
                                    $canDelete = false;
                                }
                            @endphp
                            <div class="flex items-center justify-center gap-2">
                                @if(!$hasChildren && $canValidate)
                                    @if($admissionTest->is_validated)
                                        <button type="button" wire:click="unvalidateTest({{ $admissionTest->id }})" class="text-orange-500 hover:text-orange-700 text-xs" title="Quitar validación">Desvalidar</button>
                                    @elseif($admissionTest->hasResult())
                                        <button type="button" wire:click="validateTest({{ $admissionTest->id }})" class="text-green-500 hover:text-green-700 text-xs" title="Validar">✓</button>
                                    @else
                                        <span class="text-gray-300 text-xs">-</span>
                                    @endif
                                @endif
                                @if($canDelete && !$isChild)
                                    <button type="button" wire:click="removeTest({{ $admissionTest->id }})"
                                            class="text-red-400 hover:text-red-600" title="Eliminar práctica">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @elseif($canDelete && $isChild && !$hasChildren && !$admissionTest->is_ratified)
                                    <button type="button" wire:click="removeTest({{ $admissionTest->id }})"
                                            class="text-red-400 hover:text-red-600" title="Quitar determinación hoja">
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
</div>
