<x-lab-layout title="Protocolo {{ $vetAdmission->protocol_number }}">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0" x-data="vetShowPage()">
        <div class="mb-6">
            <a href="{{ route('vet.admissions.index') }}" class="text-amber-600 hover:text-amber-800 text-sm flex items-center mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver a Protocolos
            </a>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Protocolo {{ $vetAdmission->protocol_number }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $vetAdmission->status_color }}-100 text-{{ $vetAdmission->status_color }}-800 mt-1">
                        {{ $vetAdmission->status_label }}
                    </span>
                </div>
                <div class="flex gap-2 flex-wrap">
                    @can('vet-admissions.edit')
                        <a href="{{ route('vet.admissions.edit', $vetAdmission) }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm font-medium transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Editar Protocolo
                        </a>
                    @endcan
                    @if($vetAdmission->vetTests->where('is_validated', true)->count() > 0)
                        <a href="{{ route('vet.admissions.viewPdf', $vetAdmission) }}" target="_blank"
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                            Ver PDF
                        </a>
                        <a href="{{ route('vet.admissions.downloadPdf', $vetAdmission) }}"
                           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                            Descargar PDF
                        </a>
                        <button @click="showEmailModal = true" type="button"
                                class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium">
                            Enviar por Email
                        </button>
                    @endif
                    @can('vet-labels.print')
                    <button type="button"
                            @click="$dispatch('open-label-modal', { url: '{{ route('vet.admissions.labelData', $vetAdmission) }}' })"
                            class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10v3H7zM5 10h14a2 2 0 012 2v4a2 2 0 01-2 2h-2v2H9v-2H5a2 2 0 01-2-2v-4a2 2 0 012-2zm10 4a1 1 0 100-2 1 1 0 000 2z"/>
                        </svg>
                        Etiqueta
                    </button>
                    @endcan
                    <form action="{{ route('vet.admissions.validateAll', $vetAdmission) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-medium">
                            Validar Todos
                        </button>
                    </form>
                    <button type="button" @click="showAddTestsModal = true"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar Prácticas
                    </button>
                    @can('sales-invoices.create')
                        @if(!$vetAdmission->isInvoiced())
                            <a href="{{ route('sales-invoices.from-protocol', ['protocol_type' => 'vet_admission', 'protocol_id' => $vetAdmission->id]) }}"
                               class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="bi bi-receipt mr-1"></i> Facturar
                            </a>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                <i class="bi bi-check-circle mr-1"></i> Facturado
                            </span>
                        @endif
                    @endcan
                    @can('vet-admissions.delete')
                        @php
                            $allPending = $vetAdmission->vetTests->every(fn($t) => $t->status === 'pending');
                        @endphp
                        @if($allPending)
                            <button
                                type="button"
                                onclick="if(confirm('¿Eliminar este protocolo? Esta acción no se puede deshacer.')) vetDeleteAction('{{ route('vet.admissions.destroy', $vetAdmission) }}')"
                                class="px-3 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium inline-flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Eliminar protocolo
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif

        @can('a25.worklist')
            <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-700">ID equipo A25</p>
                    <p class="text-xs text-gray-500">Identificador de muestra para el analizador Biosystems A25 (import de resultados).</p>
                </div>
                <form action="{{ route('a25.assignVetSampleId', $vetAdmission) }}" method="POST" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="text" name="external_equipment_sample_id"
                           value="{{ $vetAdmission->external_equipment_sample_id }}"
                           maxlength="50"
                           placeholder="ej. C002638S"
                           class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-40 font-mono focus:ring-2 focus:ring-teal-500">
                    <button type="submit"
                            class="px-3 py-1.5 bg-teal-600 text-white rounded-lg text-sm hover:bg-teal-700">
                        Guardar
                    </button>
                </form>
            </div>
        @endcan

        @can('vet-admissions.edit')
            @if(($vetProfiles ?? collect())->isNotEmpty())
                <div class="mb-6 p-4 bg-white border border-amber-200 rounded-xl shadow-sm">
                    <h3 class="text-sm font-semibold text-amber-900 mb-2">Aplicar perfiles guardados</h3>
                    <p class="text-xs text-gray-600 mb-3">Las determinaciones ya cargadas no se duplican.</p>
                    <form action="{{ route('vet.admissions.determination-profiles.apply', $vetAdmission) }}" method="POST" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        @csrf
                        <div class="flex-1">
                            <select name="profile_ids[]" multiple required size="4" class="w-full rounded-lg border-gray-300 text-sm">
                                @foreach($vetProfiles as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">Aplicar perfiles</button>
                    </form>
                    @can('determination-profiles.manage')
                        <a href="{{ route('determination-profiles.index') }}" class="inline-block mt-2 text-xs text-amber-700 hover:underline">Gestionar perfiles</a>
                    @endcan
                </div>
            @endif
        @endcan

        @if($vetAdmission->determinationProfileApplications->isNotEmpty())
            <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Perfiles aplicados</h3>
                <ul class="text-sm space-y-2">
                    @foreach($vetAdmission->determinationProfileApplications as $app)
                        <li class="flex flex-wrap gap-x-4 text-xs text-gray-700 border-b border-gray-100 pb-2">
                            <span class="text-gray-500">{{ $app->created_at->format('d/m/Y H:i') }}</span>
                            <span>{{ $app->user?->name ?? '—' }}</span>
                            <span>
                                @foreach($app->profiles_snapshot ?? [] as $snap)
                                    <span class="inline-flex px-2 py-0.5 rounded bg-white border font-mono mr-1">{{ $snap['name'] ?? '' }}</span>
                                @endforeach
                            </span>
                            <span class="text-gray-500">+{{ $app->tests_added_count }} / omitidas {{ $app->tests_skipped_duplicate_count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Info del protocolo --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-amber-700 mb-3 uppercase tracking-wider">Datos del Animal</h3>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-gray-500">Animal</dt>
                    <dd class="font-medium text-gray-900">{{ $vetAdmission->animal_name }}</dd>
                    <dt class="text-gray-500">Especie</dt>
                    <dd><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">{{ $vetAdmission->species->name ?? '-' }}</span></dd>
                    <dt class="text-gray-500">Raza</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->breed ?? '-' }}</dd>
                    <dt class="text-gray-500">Edad</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->age ?? '-' }}</dd>
                    <dt class="text-gray-500">Dueño</dt>
                    <dd class="font-medium text-gray-900">{{ $vetAdmission->owner_name }}</dd>
                    <dt class="text-gray-500">Tel. Dueño</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->owner_phone ?? '-' }}</dd>
                    @if($vetAdmission->owner_email)
                        <dt class="text-gray-500">Email</dt>
                        <dd><a href="mailto:{{ $vetAdmission->owner_email }}" class="text-amber-600 hover:text-amber-800">{{ $vetAdmission->owner_email }}</a></dd>
                    @endif
                </dl>
                @php
                    $historyCount = \App\Models\VetAdmission::where('owner_name', $vetAdmission->owner_name)
                        ->where('animal_name', $vetAdmission->animal_name)
                        ->count();
                @endphp
                @if($historyCount > 1)
                    <a href="{{ route('vet.admissions.index', ['animal' => $vetAdmission->animal_name, 'owner' => $vetAdmission->owner_name]) }}"
                       class="inline-flex items-center text-sm text-amber-600 hover:text-amber-800 mt-3">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Ver historial ({{ $historyCount }} protocolos)
                    </a>
                @endif
                </dl>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-amber-700 mb-3 uppercase tracking-wider">Datos del Protocolo</h3>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-gray-500">Fecha</dt>
                    <dd class="font-medium text-gray-900">{{ $vetAdmission->date->format('d/m/Y') }}</dd>
                    <dt class="text-gray-500">Veterinaria</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->customer->name ?? '-' }}</dd>
                    <dt class="text-gray-500">Derivante</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->veterinarian->name ?? 'Sin derivante' }}</dd>
                    <dt class="text-gray-500">Total</dt>
                    <dd class="font-bold text-amber-700">${{ number_format($vetAdmission->total_price, 2, ',', '.') }}</dd>
                    <dt class="text-gray-500">Creado por</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->creator->name ?? '-' }}</dd>
                    @if($vetAdmission->labBranch)
                    <dt class="text-gray-500">Sede de origen</dt>
                    <dd class="text-gray-900 font-medium">
                        {{ $vetAdmission->labBranch->name }}
                        @if($vetAdmission->labBranch->city)
                            <span class="text-gray-400">— {{ $vetAdmission->labBranch->city }}</span>
                        @endif
                    </dd>
                    @endif
                </dl>
                @if($vetAdmission->observations)
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 font-medium mb-1">Observaciones</p>
                        <p class="text-sm text-gray-700">{{ $vetAdmission->observations }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Resultados --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 text-white px-5 py-4 flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-semibold">Resultados</h2>
                    <p class="text-amber-100 text-sm">{{ $vetAdmission->vetTests->count() }} determinaciones</p>
                </div>
            </div>

            <form action="{{ route('vet.admissions.loadResults', $vetAdmission) }}" method="POST">
                @csrf
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
                            @foreach($vetAdmission->getVetTestsOrderedForDisplay() as $idx => $entry)
                                @php
                                    $vt = $entry['vt'];
                                    $level = $entry['level'];
                                    $isTreeChild = $entry['isChild'];
                                    $isParentHeader = $entry['isParent'] && ! $entry['isSubParent'];
                                    $disabled = $vt->is_validated;
                                @endphp
                                @if($isRecepcionLab && $isTreeChild && !$entry['isParent'])
                                    @continue
                                @endif
                                <tr class="{{ $isTreeChild ? 'bg-gray-50/50' : '' }} {{ $vt->is_validated ? 'bg-green-50/40' : '' }}">
                                    <td class="px-4 py-2">
                                        <div class="flex items-start" style="padding-left: {{ $level * 16 }}px">
                                            @if($isTreeChild)<span class="text-gray-300 mr-2 shrink-0">↳</span>@endif
                                            <div class="min-w-0">
                                                <span class="text-xs font-mono text-gray-400">{{ $vt->test->code }}</span>
                                                <span class="text-sm {{ $isParentHeader ? 'font-semibold text-gray-900' : 'font-medium text-gray-900' }} ml-1">{{ $vt->test->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="hidden" name="results[{{ $idx }}][id]" value="{{ $vt->id }}">
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
                                            <input type="checkbox"
                                                   name="results[{{ $idx }}][is_ratified]"
                                                   value="1"
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
                                            <button type="button"
                                                    onclick="vetSubmitAction('{{ route('vet.admissions.unvalidateTest', [$vetAdmission, $vt]) }}')"
                                                    class="text-red-600 hover:text-red-800 text-xs font-medium" title="Desvalidar">✕</button>
                                        @elseif($vt->hasResult())
                                            <button type="button"
                                                    onclick="vetSubmitAction('{{ route('vet.admissions.validateTest', [$vetAdmission, $vt]) }}')"
                                                    class="text-green-600 hover:text-green-800 text-xs font-medium" title="Validar">✓</button>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        @if(!$vt->is_validated && !$isTreeChild && (!$isRecepcionLab || $vt->status === 'pending'))
                                            <button type="button"
                                                    onclick="if(confirm('¿Quitar esta práctica del protocolo?')) vetDeleteAction('{{ route('vet.admissions.removeTest', [$vetAdmission, $vt]) }}')"
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

                <div class="px-5 py-4 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-medium">
                        Guardar Resultados
                    </button>
                </div>
            </form>
        </div>

        {{-- Modal de agregar prácticas --}}
        <div x-show="showAddTestsModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             @keydown.escape.window="showAddTestsModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 p-6" @click.outside="showAddTestsModal = false">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar prácticas al protocolo
                </h3>
                <form action="{{ route('vet.admissions.addTests', $vetAdmission) }}" method="POST">
                    @csrf
                    <div class="relative mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar determinación</label>
                        <input type="text" x-model="addTestSearch" x-ref="addTestSearchInput"
                               @input="searchNewTests()"
                               @keydown.enter.prevent="selectFirstNewTest()"
                               @keydown.escape="addTestDropdownOpen = false; addTestSearch = ''"
                               placeholder="Buscar por código o nombre..."
                               class="w-full border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                        <div x-show="addTestDropdownOpen && addTestResults.length > 0"
                             class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="test in addTestResults" :key="test.id">
                                <div @click="selectNewTest(test)"
                                     class="px-3 py-2 hover:bg-emerald-50 cursor-pointer flex justify-between items-center text-sm">
                                    <div>
                                        <span class="font-mono text-xs text-gray-400" x-text="test.code"></span>
                                        <span class="ml-1 font-medium" x-text="test.name"></span>
                                    </div>
                                    <span class="text-emerald-600 font-medium" x-text="'$' + test.price.toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="selectedNewTests.length > 0" class="mb-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">Prácticas a agregar:</p>
                        <div class="space-y-1 max-h-40 overflow-y-auto">
                            <template x-for="(t, idx) in selectedNewTests" :key="t.test_id">
                                <div class="flex items-center justify-between bg-emerald-50 rounded-lg px-3 py-2">
                                    <input type="hidden" :name="'tests[' + idx + '][test_id]'" :value="t.test_id">
                                    <div class="text-sm">
                                        <span class="font-mono text-xs text-gray-400" x-text="t.code"></span>
                                        <span class="ml-1 font-medium" x-text="t.name"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-emerald-700 font-medium" x-text="'$' + t.price.toFixed(2)"></span>
                                        <button type="button" @click="selectedNewTests.splice(idx, 1)"
                                                class="text-red-400 hover:text-red-600">&times;</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showAddTestsModal = false; selectedNewTests = []; addTestSearch = ''"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="selectedNewTests.length === 0"
                                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Agregar al protocolo
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal de envío de email --}}
        <div x-show="showEmailModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             @keydown.escape.window="showEmailModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6" @click.outside="showEmailModal = false">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Enviar Resultados por Email</h3>
                <form action="{{ route('vet.admissions.sendEmail', $vetAdmission) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email destinatario *</label>
                            <input type="email" name="email" required
                                   value="{{ $vetAdmission->owner_email ?? $vetAdmission->customer->email ?? '' }}"
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                   placeholder="email@ejemplo.com">
                            @if($vetAdmission->owner_email || $vetAdmission->customer->email || $vetAdmission->veterinarian?->email)
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if($vetAdmission->owner_email)
                                        <button type="button"
                                                onclick="this.closest('form').querySelector('input[name=email]').value='{{ $vetAdmission->owner_email }}'"
                                                class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded hover:bg-gray-200">
                                            Dueño: {{ $vetAdmission->owner_email }}
                                        </button>
                                    @endif
                                    @if($vetAdmission->customer->email)
                                        <button type="button"
                                                onclick="this.closest('form').querySelector('input[name=email]').value='{{ $vetAdmission->customer->email }}'"
                                                class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded hover:bg-gray-200">
                                            {{ $vetAdmission->customer->name }}: {{ $vetAdmission->customer->email }}
                                        </button>
                                    @endif
                                    @if($vetAdmission->veterinarian?->email)
                                        <button type="button"
                                                onclick="this.closest('form').querySelector('input[name=email]').value='{{ $vetAdmission->veterinarian->email }}'"
                                                class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded hover:bg-gray-200">
                                            {{ $vetAdmission->veterinarian->name }}: {{ $vetAdmission->veterinarian->email }}
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje personalizado</label>
                            <textarea name="message" rows="3"
                                      class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                      placeholder="Mensaje opcional para incluir en el email..."></textarea>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="showEmailModal = false"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                            Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                            @php
                                $parentTestIds = $vetAdmission->vetTests->pluck('test_id')->toArray();
                                $materialLabels = [];
                                foreach ($vetAdmission->vetTests as $vt) {
                                    $test = $vt->test;
                                    if (!$test || !$test->materialRelation) continue;
                                    if ($test->parentTests && $test->parentTests->whereIn('id', $parentTestIds)->isNotEmpty()) continue;
                                    $mid = $test->materialRelation->id;
                                    if (!isset($materialLabels[$mid])) {
                                        $materialLabels[$mid] = $test->material_abbreviation;
                                    }
                                }
                                $previewMaterials = array_values($materialLabels);
                            @endphp
                            <p class="text-xs text-gray-600 mb-2">
                                Se imprimirán <strong>{{ count($previewMaterials) ?: 1 }}</strong> etiqueta{{ (count($previewMaterials) ?: 1) > 1 ? 's' : '' }}:
                            </p>
                            @if(count($previewMaterials) > 0)
                                <div class="flex flex-wrap gap-1 mb-2">
                                    @foreach($previewMaterials as $mat)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">{{ $mat }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="bg-white border border-gray-200 rounded p-2 text-center font-mono text-xs leading-relaxed">
                                <div class="text-lg tracking-widest">|||||||||||||||||||</div>
                                <div class="font-bold">{{ $vetAdmission->protocol_number }}</div>
                                <div>{{ Str::limit($vetAdmission->owner_name ?? 'N/A', 30) }}</div>
                                <div class="text-gray-500">
                                    VETERINARIO | <strong>{{ $previewMaterials[0] ?? '?' }}</strong> | {{ $vetAdmission->date->format('d/m/Y') }}
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
                            <a href="{{ route('vet.admissions.label', $vetAdmission) }}" target="_blank"
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
                        <p class="text-sm text-green-700">Etiqueta(s) enviada(s) a la impresora correctamente.</p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <script>
        function vetSubmitAction(url) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.style.display = 'none';
            var csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }

        function vetDeleteAction(url) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.style.display = 'none';
            var csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            var method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            form.appendChild(method);
            document.body.appendChild(form);
            form.submit();
        }

        function vetShowPage() {
            return {
                showEmailModal: false,
                showAddTestsModal: false,
                addTestSearch: '',
                addTestResults: [],
                addTestDropdownOpen: false,
                selectedNewTests: [],
                searchTestsUrl: @json(route('vet.admissions.searchTests')),
                customerId: @json($vetAdmission->customer_id),
                existingTestIds: @json($vetAdmission->vetTests->pluck('test_id')->toArray()),

                async searchNewTests() {
                    if (this.addTestSearch.length < 2) {
                        this.addTestResults = [];
                        this.addTestDropdownOpen = false;
                        return;
                    }
                    try {
                        const url = `${this.searchTestsUrl}?q=${encodeURIComponent(this.addTestSearch)}&customer_id=${this.customerId}`;
                        const resp = await fetch(url);
                        const tests = await resp.json();
                        const selectedIds = this.selectedNewTests.map(t => t.test_id);
                        this.addTestResults = tests.filter(t =>
                            !this.existingTestIds.includes(t.id) && !selectedIds.includes(t.id)
                        );
                        this.addTestDropdownOpen = true;
                    } catch (e) {
                        this.addTestResults = [];
                    }
                },

                selectFirstNewTest() {
                    if (this.addTestResults.length > 0) {
                        this.selectNewTest(this.addTestResults[0]);
                    }
                },

                selectNewTest(test) {
                    this.selectedNewTests.push({
                        test_id: test.id,
                        code: test.code,
                        name: test.name,
                        price: test.price,
                    });
                    this.addTestSearch = '';
                    this.addTestResults = [];
                    this.addTestDropdownOpen = false;
                    this.$refs.addTestSearchInput.focus();
                },
            };
        }
    </script>

    <script src="{{ asset('js/zebra-label-print.js') }}"></script>
</x-lab-layout>
