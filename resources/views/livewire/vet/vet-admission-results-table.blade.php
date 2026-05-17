<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Determinación</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-36">Resultado</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Unidad</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-40">Val. Referencia</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">Método</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20" title="Ratificado: valor anormal/atípico verificado por bioquímico">Ratif.</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Estado</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Validar</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-16">Quitar</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($orderedEntries as $idx => $entry)
                @php
                    $vt = $entry['vt'];
                    $level = $entry['level'];
                    $isTreeChild = $entry['isChild'];
                    $isParentHeader = $entry['isParent'] && ! $entry['isSubParent'];
                    $disabled = $vt->is_validated;
                @endphp
                @if($isRecepcionLab && $isTreeChild && ! $entry['isParent'])
                    @continue
                @endif
                <tr wire:key="vat-{{ $vt->id }}" class="{{ $isTreeChild ? 'bg-gray-50/50' : '' }} {{ $vt->is_validated ? 'bg-green-50/40' : '' }}">
                    <td class="px-4 py-2">
                        <input type="hidden" name="results[{{ $idx }}][id]" value="{{ $vt->id }}">
                        <div class="flex items-start" style="padding-left: {{ $level * 16 }}px">
                            @if($isTreeChild)<span class="text-gray-300 mr-2 shrink-0">↳</span>@endif
                            <div class="min-w-0">
                                <span class="text-xs font-mono text-gray-400">{{ $vt->test->code }}</span>
                                <span class="text-sm {{ $isParentHeader ? 'font-semibold text-gray-900' : 'font-medium text-gray-900' }} ml-1">{{ $vt->test->name }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" name="results[{{ $idx }}][result]" value="{{ $vt->result }}"
                               class="w-full border-gray-300 rounded text-sm {{ $disabled ? 'bg-gray-100' : '' }}"
                               {{ $disabled ? 'disabled' : '' }}>
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" name="results[{{ $idx }}][unit]" value="{{ $vt->unit }}"
                               class="w-full border-gray-300 rounded text-sm {{ $disabled ? 'bg-gray-100' : '' }}"
                               {{ $disabled ? 'disabled' : '' }}>
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" name="results[{{ $idx }}][reference_value]" value="{{ $vt->reference_value }}"
                               class="w-full border-gray-300 rounded text-sm {{ $disabled ? 'bg-gray-100' : '' }}"
                               {{ $disabled ? 'disabled' : '' }}>
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" name="results[{{ $idx }}][method]" value="{{ $vt->method }}"
                               class="w-full border-gray-300 rounded text-sm {{ $disabled ? 'bg-gray-100' : '' }}"
                               {{ $disabled ? 'disabled' : '' }}>
                    </td>
                    <td class="px-4 py-2 text-center">
                        @if(! $isParentHeader)
                            <input type="hidden" name="results[{{ $idx }}][is_ratified]" value="0">
                            <input type="checkbox" name="results[{{ $idx }}][is_ratified]" value="1"
                                   {{ $vt->is_ratified ? 'checked' : '' }}
                                   title="Valor anormal/atípico — verificado por bioquímico (editable también después de validar)"
                                   class="h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500 cursor-pointer">
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center">
                        @if($vt->is_validated)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Validado</span>
                        @elseif($vt->hasResult())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Completo</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Pendiente</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center">
                        @if($vt->is_validated)
                            <button type="button" wire:click="unvalidateTest({{ $vt->id }})"
                                    class="text-red-600 hover:text-red-800 text-xs font-medium" title="Desvalidar">✕</button>
                        @elseif($vt->hasResult())
                            <button type="button" wire:click="validateTest({{ $vt->id }})"
                                    class="text-green-600 hover:text-green-800 text-xs font-medium" title="Validar">✓</button>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center">
                        @php
                            $vetCanRemoveRow = ! $vt->is_validated && ! $entry['isParent'] && ! $vt->hasResult() && ! $vt->is_ratified && (! $isRecepcionLab || $vt->status === 'pending');
                        @endphp
                        @if($vetCanRemoveRow)
                            <button type="button" wire:click="removeTest({{ $vt->id }})"
                                    class="text-red-400 hover:text-red-600 transition-colors" title="Quitar del protocolo">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
