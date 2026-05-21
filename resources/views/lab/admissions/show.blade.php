<x-lab-layout title="Admisión {{ $admission->protocol_number }}">
    <x-protocol-action-toast />
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <!-- Header: sticky en viewport; top-14 = barra móvil; md:top-20 ≈ altura real header lab (py-4 + título) -->
        <div class="sticky top-14 z-20 mb-6 border-b border-gray-200 bg-gray-100 pb-4 pt-2 shadow-sm md:top-20">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('lab.admissions.index', request()->only(['search', 'insurance', 'date_from', 'date_to', 'lab_branch_id', 'status'])) }}" class="hover:text-teal-600">Admisiones</a>
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
                <div class="flex items-center gap-2 flex-wrap" x-data>
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
                    <div class="inline-flex shrink-0 overflow-hidden rounded-lg border border-gray-700 shadow-sm" role="group" aria-label="Navegar protocolos pendientes">
                        <a href="{{ route('lab.admissions.previous-pending', array_merge(['admission' => $admission], request()->only(['search', 'insurance', 'date_from', 'date_to', 'lab_branch_id', 'status']))) }}"
                           class="inline-flex items-center gap-1 border-r border-gray-700 bg-gray-900 px-2.5 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 sm:px-3"
                           title="Protocolo pendiente anterior (sin validar ni enviar; mismos filtros)">
                            <svg class="h-4 w-4 shrink-0 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                            </svg>
                            <span class="hidden sm:inline">Anterior</span>
                        </a>
                        <a href="{{ route('lab.admissions.next-pending', array_merge(['admission' => $admission], request()->only(['search', 'insurance', 'date_from', 'date_to', 'lab_branch_id', 'status']))) }}"
                           class="inline-flex items-center gap-1 bg-gray-900 px-2.5 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 sm:px-3"
                           title="Próximo protocolo pendiente (sin validar ni enviar; mismos filtros)">
                            <span class="hidden sm:inline">Siguiente</span>
                            <svg class="h-4 w-4 shrink-0 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                    @can('lab-results.create')
                        @if($admission->admissionTests->count() > 0)
                            <button type="submit"
                                    form="lab-admission-results"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium inline-flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Guardar
                            </button>
                        @endif
                    @endcan
                    <a href="{{ route('lab.admissions.edit', $admission) }}" 
                       class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Editar
                    </a>
                    @endcan
                    @can('sales-invoices.create')
                        @if($admission->isParticular() && !$admission->isInvoiced())
                            <a href="{{ route('sales-invoices.from-protocol', ['protocol_type' => 'admission', 'protocol_id' => $admission->id]) }}"
                               class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="bi bi-receipt mr-1"></i> Facturar
                            </a>
                        @elseif($admission->isInvoiced())
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                <i class="bi bi-check-circle mr-1"></i> Facturado
                            </span>
                        @endif
                    @endcan
                    @can('lab-admissions.delete')
                        @php
                            $allPending = $admission->admissionTests->every(fn($t) => !$t->is_validated && !$t->hasResult());
                        @endphp
                        @if($allPending)
                            <button
                                type="button"
                                onclick="if(confirm('¿Eliminar este protocolo? Esta acción no se puede deshacer.')) submitAction('{{ route('lab.admissions.destroy', $admission) }}', 'DELETE')"
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

        @if(session('warning'))
            <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg">
                {{ session('warning') }}
            </div>
        @endif

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
                        @if($admission->labBranch)
                        <div>
                            <p class="text-sm text-gray-500">Sede de origen</p>
                            <p class="font-medium text-gray-900">
                                {{ $admission->labBranch->name }}
                                @if($admission->labBranch->city)
                                    <span class="text-gray-500">— {{ $admission->labBranch->city }}</span>
                                @endif
                            </p>
                        </div>
                        @endif
                        <div>
                            <p class="text-sm text-gray-500">Obra Social</p>
                            <p class="font-medium text-gray-900">
                                {{ strtoupper($admission->insuranceRelation?->displayName() ?? 'N/A') }}
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

                <!-- ID equipo A25 -->
                @can('a25.worklist')
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-4">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-700">ID equipo A25</p>
                        <p class="text-xs text-gray-500">Identificador de muestra para el analizador Biosystems A25</p>
                    </div>
                    <form action="{{ route('a25.assignSampleId', $admission) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        <input type="text" name="external_equipment_sample_id"
                               value="{{ $admission->external_equipment_sample_id }}"
                               maxlength="50"
                               placeholder="ej. C002638S"
                               class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-36 font-mono focus:ring-2 focus:ring-teal-500">
                        <button type="submit"
                                class="px-3 py-1.5 bg-teal-600 text-white rounded-lg text-sm hover:bg-teal-700">
                            Guardar
                        </button>
                    </form>
                </div>
                @endcan

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

                    @can('lab-admissions.edit')
                        @if(($clinicalProfiles ?? collect())->isNotEmpty())
                            @php
                                $withResultsBanner = $admission->admissionTests->filter(fn ($at) => $at->result !== null && $at->result !== '')->count() > 0;
                            @endphp
                            @if($withResultsBanner)
                                <div class="mx-6 mt-4 p-3 bg-blue-50 border-l-4 border-blue-400 text-sm text-blue-900 rounded">
                                    Podés aplicar perfiles guardados: solo se <strong>agregan</strong> prácticas nuevas; no se modifican resultados existentes.
                                </div>
                            @endif
                            <div class="mx-6 mt-4 mb-2 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <h3 class="text-sm font-semibold text-gray-800 mb-2">Aplicar perfiles guardados</h3>
                                <p class="text-xs text-gray-600 mb-3">Las prácticas ya cargadas no se duplican. Respeta cobertura / nomenclador de la obra social.</p>
                                <form action="{{ route('lab.admissions.determination-profiles.apply', $admission) }}" method="POST" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                                    @csrf
                                    <div class="flex-1 min-w-[200px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Perfiles (uno o varios con Ctrl/Cmd)</label>
                                        <select name="profile_ids[]" multiple required size="4"
                                                class="w-full rounded-lg border-gray-300 text-sm">
                                            @foreach($clinicalProfiles as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 whitespace-nowrap">
                                        Aplicar perfiles
                                    </button>
                                </form>
                                @can('determination-profiles.manage')
                                    <a href="{{ route('determination-profiles.index') }}" class="inline-block mt-2 text-xs text-teal-600 hover:underline">Gestionar perfiles</a>
                                @endcan
                            </div>
                        @else
                            <div class="mx-6 mt-4 text-sm text-gray-500">
                                No hay perfiles configurados para laboratorio clínico.
                                @can('determination-profiles.manage')
                                    <a href="{{ route('determination-profiles.create') }}" class="text-teal-600 hover:underline">Crear perfil</a>
                                @endcan
                            </div>
                        @endif
                    @endcan

                    @if($admission->determinationProfileApplications->isNotEmpty())
                        <div class="mx-6 mt-4 border-t border-gray-100 pt-4">
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Perfiles aplicados (historial)</h3>
                            <ul class="text-sm text-gray-700 space-y-2">
                                @foreach($admission->determinationProfileApplications as $app)
                                    <li class="flex flex-wrap gap-x-4 gap-y-1 border-b border-gray-50 pb-2">
                                        <span class="text-gray-500">{{ $app->created_at->format('d/m/Y H:i') }}</span>
                                        <span>{{ $app->user?->name ?? '—' }}</span>
                                        <span>
                                            @foreach($app->profiles_snapshot ?? [] as $snap)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-xs font-mono mr-1" title="ID {{ $snap['id'] ?? '' }}">{{ $snap['name'] ?? '' }}</span>
                                            @endforeach
                                        </span>
                                        <span class="text-xs text-gray-500">+{{ $app->tests_added_count }} / omitidas {{ $app->tests_skipped_duplicate_count }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($admission->admissionTests->count() > 0)
                        <form id="lab-admission-results" action="{{ route('lab.admissions.saveResults', $admission) }}" method="POST">
                            @csrf
                            @livewire('lab.lab-admission-results-table', [
                                'admissionId' => $admission->id,
                                'isRecepcionLab' => $isRecepcionLab,
                            ], key('lab-results-'.$admission->id))
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
                        @if($admission->isSent())
                            <div class="flex items-center gap-2 text-sky-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                </svg>
                                <span class="font-medium">Enviado el {{ $admission->sent_at->format('d/m/Y H:i') }}</span>
                            </div>
                        @elseif($validated === $total && $total > 0)
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

        // Buscador de prácticas para agregar al protocolo (API con insurance_id de la admisión)
        const searchTestsUrl = @json(route('lab.admissions.searchTests'));
        const insuranceId = @json($admission->insurance);
        const existingTestIds = @json($admission->admissionTests->pluck('test_id')->values());
        let filteredTests = [];
        let addTestSearchTimeout = null;

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

            clearTimeout(addTestSearchTimeout);
            addTestSearchTimeout = setTimeout(async () => {
                resultsContainer.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Buscando...</p>';
                try {
                    const response = await fetch(`${searchTestsUrl}?q=${encodeURIComponent(query)}&insurance_id=${insuranceId}`);
                    const tests = await response.json();
                    filteredTests = tests.filter(t => !existingTestIds.includes(t.id));
                    renderAddTestResults(filteredTests);
                } catch (e) {
                    resultsContainer.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Error al buscar prácticas</p>';
                    filteredTests = [];
                }
            }, 250);
        }

        function renderAddTestResults(tests) {
            const resultsContainer = document.getElementById('add-test-results');

            if (tests.length === 0) {
                resultsContainer.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No se encontraron prácticas</p>';
                return;
            }

            const csrfToken = '{{ csrf_token() }}';

            resultsContainer.innerHTML = tests.map((test, index) => {
                const price = parseFloat(test.calculated_price ?? test.price ?? 0);
                const authStatus = test.requires_authorization ? 'pending' : 'not_required';

                return `
                <form action="{{ route('lab.admissions.addTest', $admission) }}" method="POST" class="block" id="add-test-form-${index}">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="test_id" value="${test.id}">
                    <input type="hidden" name="price" value="${price}">
                    <input type="hidden" name="authorization_status" value="${authStatus}">
                    <button type="submit" class="w-full text-left px-4 py-3 hover:bg-teal-50 rounded-lg border border-gray-200 mb-1 flex justify-between items-center ${index === 0 ? 'bg-teal-50 border-teal-300' : ''}">
                        <div>
                            <span class="font-medium text-teal-600">${test.code}</span>
                            <span class="text-gray-700 ml-2">${test.name}</span>
                        </div>
                        <span class="text-sm text-gray-500">$${price.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
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

    <script>
        const labAccordion = {
            collapsed: new Set(),

            toggle(rowEl, parentId) {
                if (this.collapsed.has(parentId)) {
                    this.collapsed.delete(parentId);
                } else {
                    this.collapsed.add(parentId);
                }
                const isCollapsed = this.collapsed.has(parentId);

                // Rotar chevron
                const chevron = rowEl.querySelector('[data-chevron]');
                if (chevron) {
                    chevron.style.transform = isCollapsed ? 'rotate(-90deg)' : 'rotate(0deg)';
                }

                // Mostrar/ocultar hijos directos
                document.querySelectorAll('tr[data-group="' + parentId + '"]').forEach(function(child) {
                    if (isCollapsed) {
                        child.style.display = 'none';
                    } else {
                        // Solo mostrar si el padre-del-padre no está también colapsado
                        var parentGroup = child.getAttribute('data-parent-group');
                        if (!parentGroup || !labAccordion.collapsed.has(parentGroup)) {
                            child.style.display = '';
                        }
                    }
                    // Si este hijo también es padre (sub-padre), manejar sus hijos
                    var subParentId = child.getAttribute('data-sub-parent-id');
                    if (subParentId) {
                        document.querySelectorAll('tr[data-group="' + subParentId + '"]').forEach(function(grandChild) {
                            grandChild.style.display = (isCollapsed || labAccordion.collapsed.has(subParentId)) ? 'none' : '';
                        });
                    }
                });
            }
        };
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
                        @php
                            $emailPaciente = $admission->patient?->email ?? '';
                            $emailOS = $admission->insuranceRelation?->email ?? '';
                        @endphp
                        {{-- Accesos rápidos --}}
                        @if($emailPaciente || $emailOS)
                            <div class="flex flex-wrap gap-1 mb-2">
                                @if($emailPaciente)
                                    <button type="button"
                                            onclick="document.getElementById('emailInput').value = '{{ $emailPaciente }}'"
                                            class="text-xs px-2 py-1 rounded-full border border-teal-400 text-teal-700 bg-teal-50 hover:bg-teal-100 truncate max-w-full">
                                        👤 {{ $emailPaciente }}
                                    </button>
                                @endif
                                @if($emailOS && $emailOS !== $emailPaciente)
                                    <button type="button"
                                            onclick="document.getElementById('emailInput').value = '{{ $emailOS }}'"
                                            class="text-xs px-2 py-1 rounded-full border border-purple-400 text-purple-700 bg-purple-50 hover:bg-purple-100 truncate max-w-full">
                                        🏥 {{ $emailOS }}
                                    </button>
                                @endif
                            </div>
                        @endif
                        <input type="email" name="email" id="emailInput" required
                               value="{{ $emailPaciente }}"
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
    <div x-data="zebraPrintModal(@js(route('lab.admissions.label', $admission, absolute: true)))"
         @open-label-modal.window="openModal($event.detail.url)"
         x-cloak>

        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/50" @click="closeModal()"></div>

                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6 max-h-[90vh] overflow-y-auto">
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
                        <p class="mt-2 text-sm text-gray-500">Cargando materiales e impresoras…</p>
                    </div>

                    <div x-show="!loading" class="space-y-4">
                        <p x-show="labelRows.length === 0" class="text-sm text-gray-600">No hay materiales para etiquetar en este protocolo.</p>

                        <div x-show="labelRows.length > 0" class="space-y-3">
                                <div class="flex justify-between items-center gap-2">
                                    <p class="text-sm font-medium text-gray-700">Materiales a imprimir</p>
                                    <div class="text-xs shrink-0 space-x-2">
                                        <button type="button" @click="selectAllMaterials()" class="text-purple-600 hover:underline">Todas</button>
                                        <button type="button" @click="clearAllMaterials()" class="text-purple-600 hover:underline">Ninguna</button>
                                    </div>
                                </div>
                                <div class="max-h-36 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-100">
                                    <template x-for="row in labelRows" :key="row.material_key">
                                        <label class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                                   :value="String(row.material_key)"
                                                   x-model="selectedKeys">
                                            <span class="text-sm text-gray-800">
                                                <strong x-text="row.material"></strong>
                                                <span x-show="row.material_name" class="text-gray-600 font-normal"> — <span x-text="row.material_name"></span></span>
                                            </span>
                                        </label>
                                    </template>
                                </div>
                                <p x-show="selectedKeys.length === 0" class="text-xs text-red-600">Seleccione al menos un material.</p>
                            </div>

                        <!-- Zebra disponible -->
                        <div x-show="zebraAvailable && labelRows.length > 0" class="space-y-4 border-t border-gray-100 pt-4">
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

                            <div class="bg-gray-50 rounded-lg p-3 border border-dashed border-gray-300">
                                <p class="text-xs text-gray-500 mb-1 font-medium">Vista previa</p>
                                <p class="text-xs text-gray-600 mb-2">
                                    Se imprimirán <strong x-text="selectedKeys.length"></strong> etiqueta(s) seleccionada(s).
                                </p>
                                <div class="bg-white border border-gray-200 rounded p-2 text-center font-mono text-xs leading-relaxed" x-show="firstPreviewRow()">
                                    <div class="text-lg tracking-widest">|||||||||||||||||||</div>
                                    <div class="font-bold">{{ $admission->protocol_number }}</div>
                                    <div>{{ Str::limit($admission->patient->full_name ?? 'N/A', 30) }}</div>
                                    <div class="text-gray-500">
                                        <span x-text="previewField('branch_name', 'CLINICO')"></span> |
                                        <strong x-text="previewField('material', '?')"></strong> |
                                        <span x-text="previewField('entry_date', '{{ $admission->date?->format('d/m/Y') ?? '' }}')"></span>
                                    </div>
                                </div>
                            </div>

                            <button @click="print()" :disabled="printing || !selectedPrinter || selectedKeys.length === 0"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="printing" class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <svg x-show="!printing" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                <span x-text="printing ? 'Imprimiendo...' : 'Imprimir'"></span>
                            </button>
                        </div>

                        <!-- Zebra no disponible -->
                        <div x-show="!zebraAvailable && labelRows.length > 0" class="space-y-4 border-t border-gray-100 pt-4">
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

                            <div>
                                <p class="text-sm text-gray-600 mb-3">Puede usar la impresión vía navegador como alternativa:</p>
                                <a :href="browserPrintHref()" target="_blank" rel="noopener"
                                   :class="selectedKeys.length === 0 ? 'opacity-50 pointer-events-none' : ''"
                                   class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Imprimir vía Navegador
                                </a>
                            </div>
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
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <script src="{{ asset('js/zebra-label-print.js') }}"></script>
    @if(request('print_label'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                window.dispatchEvent(new CustomEvent('open-label-modal', {
                    detail: { url: '{{ route('lab.admissions.labelData', $admission) }}' }
                }));
            }, 500);
        });
    </script>
    @endif
    @if(request()->boolean('open_email'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('emailModal');
            if (modal) {
                modal.classList.remove('hidden');
            }
        });
    </script>
    @endif
</x-lab-layout>

