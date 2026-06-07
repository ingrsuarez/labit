<x-lab-layout title="Admisiones">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0" x-data="admissionBatchMail()">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Admisiones de Pacientes</h1>
                <p class="mt-1 text-sm text-gray-600">Gestione las admisiones del laboratorio</p>
            </div>
            @can('lab-admissions.create')
            <a href="{{ route('lab.admissions.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nueva Admisión
            </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg">
                {{ session('warning') }}
            </div>
        @endif

        @php
            $space10Enabled = app(\App\Services\Space10UploadService::class)->isEnabled();
            $labAdmissionNav = request()->only(['search', 'insurance', 'date_from', 'date_to', 'lab_branch_id', 'status']);
        @endphp

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <form action="{{ route('lab.admissions.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Buscar por protocolo, nombre o DNI..."
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div class="w-48">
                    <select name="insurance" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Todas las OS</option>
                        @foreach($insurances as $ins)
                            <option value="{{ $ins->id }}" {{ request('insurance') == $ins->id ? 'selected' : '' }}>
                                {{ strtoupper($ins->displayName()) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Todos los estados</option>
                        <option value="pending"      {{ request('status') === 'pending'      ? 'selected' : '' }}>Pendiente</option>
                        <option value="in_progress"  {{ request('status') === 'in_progress'  ? 'selected' : '' }}>En Proceso</option>
                        <option value="completed"    {{ request('status') === 'completed'    ? 'selected' : '' }}>Completado</option>
                        <option value="partially_validated" {{ request('status') === 'partially_validated' ? 'selected' : '' }}>Validado parcial</option>
                        <option value="validated"    {{ request('status') === 'validated'    ? 'selected' : '' }}>Validado</option>
                        <option value="enviado"      {{ request('status') === 'enviado'      ? 'selected' : '' }}>Enviado</option>
                        <option value="cancelled"    {{ request('status') === 'cancelled'    ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                @if(isset($branches) && $branches->count() > 1)
                <div class="w-48">
                    <select name="lab_branch_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="all" {{ request('lab_branch_id') === 'all' ? 'selected' : '' }}>Todas las sedes</option>
                        <option value="none" {{ request('lab_branch_id') === 'none' ? 'selected' : '' }}>⚠ Sin sede</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (request('lab_branch_id') == $branch->id || (!request()->has('lab_branch_id') && active_lab_branch_id() == $branch->id)) ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="w-40">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" 
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500"
                           placeholder="Desde">
                </div>
                <div class="w-40">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" 
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500"
                           placeholder="Hasta">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'insurance', 'status', 'date_from', 'date_to', 'lab_branch_id']))
                    <a href="{{ route('lab.admissions.index') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        @can('lab-admissions.show')
        <div x-show="selectedIds.length > 0" x-cloak
             class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 shadow-sm">
            <p class="text-sm text-teal-900">
                <strong><span x-text="selectedIds.length"></span></strong> protocolo(s) seleccionado(s).
                <span class="text-teal-700">Enviar por email o subir a Space10 sin correo.</span>
            </p>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if($space10Enabled)
                <button type="button" @click="openSpace10Modal()"
                        class="inline-flex items-center px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 text-sm font-medium shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Subir a Space10
                </button>
                @endif
                <button type="button" @click="openBatchModal()"
                        class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-medium shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Enviar por email
                </button>
            </div>
        </div>
        @endcan

        <!-- Listado -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            @if($admissions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @can('lab-admissions.show')
                                <th class="w-10 px-3 py-3 text-left">
                                    <input type="checkbox" x-model="selectAll" @change="toggleAll()"
                                           class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                           title="Seleccionar todos los protocolos enviables en esta página">
                                </th>
                                @endcan
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Protocolo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paciente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Obra Social</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Prácticas</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total OS</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Pac.</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($admissions as $admission)
                                @php
                                    $validatedForBatch = $admission->admissionTests->where('is_validated', true)->count() > 0;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    @can('lab-admissions.show')
                                    <td class="px-3 py-4 w-10 align-top pt-1.5">
                                        <input type="checkbox" :value="{{ $admission->id }}"
                                               x-model="selectedIds"
                                               @if(! $validatedForBatch) disabled @endif
                                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 disabled:opacity-30">
                                    </td>
                                    @endcan
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex min-w-0 flex-col items-start gap-1.5">
                                            <div class="flex flex-nowrap items-start gap-x-1 leading-none">
                                                <a href="{{ route('lab.admissions.show', array_merge(['admission' => $admission], $labAdmissionNav)) }}"
                                                   class="whitespace-nowrap text-teal-600 font-medium hover:text-teal-800 leading-snug">
                                                    {{ $admission->protocol_number ?? $admission->number }}
                                                </a>
                                                @if($admission->isInvoiced())
                                                    <span class="inline-flex shrink-0 items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                                        <i class="bi bi-check-circle mr-0.5 text-[10px]"></i> Fact.
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex min-h-[1.75rem] w-full items-start">
                                                @if($admission->labBranch && !$admission->labBranch->is_central)
                                                    <span class="inline-flex max-w-full items-center self-start rounded px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 leading-tight">
                                                        <span class="truncate" title="{{ $admission->labBranch->name }}">{{ $admission->labBranch->name }}</span>
                                                    </span>
                                                @elseif(!$admission->lab_branch_id)
                                                    <span class="inline-flex items-center self-start rounded px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 leading-tight">
                                                        Sin sede
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 align-top whitespace-nowrap text-sm text-gray-600">
                                        {{ $admission->formatted_date }}
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $admission->patient?->full_name ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            DNI: {{ $admission->patient?->patientId ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="text-sm text-gray-900">
                                            {{ strtoupper($admission->insuranceRelation?->displayName() ?? 'N/A') }}
                                        </div>
                                        @if($admission->affiliate_number)
                                            <div class="text-xs text-gray-500">
                                                Afil: {{ $admission->affiliate_number }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 align-top whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                                            {{ $admission->admissionTests->count() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 align-top whitespace-nowrap text-center">
                                        @php
                                            $statusColorMap = [
                                                'sky'    => 'bg-sky-100 text-sky-800',
                                                'yellow' => 'bg-yellow-100 text-yellow-800',
                                                'blue'   => 'bg-blue-100 text-blue-800',
                                                'green'  => 'bg-green-100 text-green-800',
                                                'indigo' => 'bg-indigo-100 text-indigo-800',
                                                'purple' => 'bg-purple-100 text-purple-800',
                                                'red'    => 'bg-red-100 text-red-800',
                                            ];
                                            $colorClass = $statusColorMap[$admission->status_color] ?? 'bg-gray-100 text-gray-700';
                                        @endphp
                                        <span class="inline-flex items-center gap-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                                {{ $admission->status_label }}
                                            </span>
                                            @if($admission->isSent())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800">Enviado</span>
                                            @endif
                                            @if($admission->isUploadedToSpace10())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-800"
                                                      title="Subido a Space10 el {{ $admission->space10_uploaded_at->format('d/m/Y H:i') }}">
                                                    Space10 ✓
                                                </span>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 align-top whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                        ${{ number_format($admission->total_insurance, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 align-top whitespace-nowrap text-sm text-right text-gray-600">
                                        ${{ number_format($admission->total_patient + $admission->total_copago, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 align-top whitespace-nowrap text-right">
                                        <a href="{{ route('lab.admissions.show', array_merge(['admission' => $admission], $labAdmissionNav)) }}"
                                           class="text-teal-600 hover:text-teal-800 text-sm">
                                            Ver
                                        </a>
                                        @if($admission->admissionTests->where('is_validated', true)->count() > 0)
                                        <a href="{{ route('lab.admissions.pdf.view', $admission) }}" target="_blank"
                                           class="text-green-600 hover:text-green-800 text-sm ml-2"
                                           title="Ver PDF ({{ $admission->admissionTests->where('is_validated', true)->count() }} validadas)">
                                            PDF
                                        </a>
                                        @can('lab-admissions.show')
                                        <a href="{{ route('lab.admissions.show', array_merge(['admission' => $admission, 'open_email' => 1], $labAdmissionNav)) }}"
                                           class="text-purple-600 hover:text-purple-800 text-sm ml-2 inline-flex items-center gap-0.5"
                                           title="Enviar informe por email">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                            Email
                                        </a>
                                        @php
                                            $rowDni = trim((string) ($admission->patient?->patientId ?? ''));
                                            $rowCanSpace10 = $space10Enabled
                                                && ! $admission->isUploadedToSpace10()
                                                && $rowDni !== '';
                                        @endphp
                                        @if($rowCanSpace10)
                                        <form action="{{ route('lab.admissions.space10', $admission) }}" method="POST" class="inline ml-2"
                                              onsubmit="return confirm('¿Subir {{ $admission->protocol_number }} a Space10 (DNI {{ $rowDni }})?')">
                                            @csrf
                                            <button type="submit"
                                                    class="text-violet-600 hover:text-violet-800 text-sm inline-flex items-center gap-0.5"
                                                    title="Subir informe a Space10 (sin email)">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                                </svg>
                                                Space10
                                            </button>
                                        </form>
                                        @endif
                                        @endcan
                                        @endif
                                        @can('lab-labels.print')
                                        <a href="{{ route('lab.admissions.show', array_merge(['admission' => $admission, 'print_label' => 1], $labAdmissionNav)) }}"
                                           class="text-purple-500 hover:text-purple-700 text-sm ml-2"
                                           title="Imprimir etiquetas">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $admissions->links() }}
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay admisiones</h3>
                    <p class="mt-1 text-sm text-gray-500">Comience creando una nueva admisión.</p>
                    @can('lab-admissions.create')
                    <div class="mt-6">
                        <a href="{{ route('lab.admissions.create') }}" 
                           class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Nueva Admisión
                        </a>
                    </div>
                    @endcan
                </div>
            @endif
        </div>

        @can('lab-admissions.show')
        @if($admissions->count() > 0)
                <div x-show="selectedIds.length > 0" x-cloak
                     class="fixed bottom-6 right-6 z-[100] pointer-events-none [&>*]:pointer-events-auto">
                    <button type="button" @click="openBatchModal()"
                            class="inline-flex items-center px-5 py-3 bg-teal-600 text-white rounded-xl shadow-lg hover:bg-teal-700 transition-colors font-medium text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Enviar <span x-text="selectedIds.length"></span> seleccionado(s)
                    </button>
                </div>

                <div x-show="showBatchModal" x-cloak
                     class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-40 p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Enviar informes por email</h3>
                            <button type="button" @click="showBatchModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <template x-if="batchToSend.length > 0">
                            <p class="text-sm text-gray-600 mb-4">
                                Se enviarán <strong x-text="batchToSend.length"></strong> protocolo(s) en <strong>un solo correo</strong> con todos los PDFs adjuntos.
                            </p>
                        </template>

                        <template x-if="batchSkipped.length > 0">
                            <div class="mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                <p class="text-sm font-medium text-yellow-800 mb-1">
                                    Los siguientes protocolos no se incluirán (sin determinaciones validadas):
                                </p>
                                <ul class="text-sm text-yellow-700 list-disc list-inside">
                                    <template x-for="row in batchSkipped" :key="row.id">
                                        <li x-text="row.protocol_number"></li>
                                    </template>
                                </ul>
                            </div>
                        </template>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Destinatario(s)</label>
                            <template x-if="batchInsuranceChips.length > 0">
                                <div class="flex flex-wrap gap-1 mb-2">
                                    <template x-for="chip in batchInsuranceChips" :key="chip.email">
                                        <button type="button"
                                                @click="batchEmail = chip.email"
                                                class="text-xs px-2 py-1 rounded-full border border-purple-400 text-purple-700 bg-purple-50 hover:bg-purple-100 truncate max-w-full"
                                                x-text="chip.label"></button>
                                    </template>
                                    <button type="button"
                                            x-show="batchInsuranceChips.length > 1"
                                            @click="applyAllInsuranceEmails()"
                                            class="text-xs px-2 py-1 rounded-full border border-gray-400 text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium">
                                        Todos (<span x-text="batchInsuranceChips.length"></span>)
                                    </button>
                                </div>
                            </template>
                            <input type="text" x-model="batchEmail" id="labBatchEmailInput"
                                   class="w-full text-sm border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
                                   placeholder="correo@dominio.com o varios separados por coma"
                                   autocomplete="email">
                            <p class="mt-1 text-xs text-gray-500">Podés elegir un correo, varios separados por coma, o usar los atajos de abajo.</p>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <button type="button"
                                        class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                        :disabled="!canUseInsuranceShortcut"
                                        :title="canUseInsuranceShortcut ? '' : 'Los protocolos seleccionados no comparten la misma obra social o la OS no tiene correos'"
                                        @click="applyInsuranceEmail()">
                                    Usar todos los emails de obra social
                                </button>
                                <button type="button"
                                        class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                        :disabled="!canUsePatientShortcut"
                                        :title="canUsePatientShortcut ? '' : 'Todos los protocolos deben ser del mismo paciente con email cargado'"
                                        @click="applyPatientEmail()">
                                    Usar email del paciente
                                </button>
                            </div>
                            <p x-show="!canUsePatientShortcut && space10EligibleCount > 0" x-cloak
                               class="mt-2 text-xs text-violet-700 bg-violet-50 border border-violet-200 rounded-lg px-3 py-2">
                                ¿Sin email del paciente? Cerrá este modal y usá
                                <strong>Subir a Space10</strong> en la barra superior, o el enlace
                                <strong>Space10</strong> en la columna Acciones de cada fila.
                            </p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje para el cuerpo del correo (opcional)</label>
                            <textarea x-model="batchMessage" rows="4"
                                      class="w-full text-sm border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
                                      placeholder="Texto adicional para el destinatario…"></textarea>
                        </div>

                        <div class="mb-4" x-show="batchToSend.length > 0">
                            <p class="text-xs text-gray-500 mb-2">Todos los PDFs se adjuntarán en un solo mensaje.</p>
                            <ul class="text-sm text-gray-700 list-disc list-inside max-h-40 overflow-y-auto">
                                <template x-for="row in batchToSend" :key="row.id">
                                    <li x-text="row.protocol_number"></li>
                                </template>
                            </ul>
                        </div>

                        <div class="flex justify-end gap-3 mt-4">
                            <button type="button" @click="showBatchModal = false"
                                    class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="button" @click="sendBatch()" :disabled="batchSending || !batchEmail || batchToSend.length === 0"
                                    class="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50">
                                <span x-show="!batchSending">Enviar</span>
                                <span x-show="batchSending">Enviando…</span>
                            </button>
                        </div>

                        <div x-show="batchResult" x-cloak class="mt-6 pt-4 border-t border-gray-200 space-y-2">
                            <p x-show="batchResult && batchResult.sent && batchResult.sent.length > 0" class="text-sm text-green-600"
                               x-text="'Enviados: ' + batchResult.sent.join(', ')"></p>
                            <p x-show="batchResult && batchResult.skipped && batchResult.skipped.length > 0" class="text-sm text-yellow-700"
                               x-text="'Salteados: ' + batchResult.skipped.join(', ')"></p>
                            <p x-show="batchResult && batchResult.errors && batchResult.errors.length > 0" class="text-sm text-red-600"
                               x-text="'Errores: ' + batchResult.errors.join(', ')"></p>
                            <button type="button" @click="batchResult = null; showBatchModal = false; window.location.reload()" class="mt-2 text-xs text-teal-600 hover:underline">
                                Cerrar y actualizar
                            </button>
                        </div>
                    </div>
                </div>

                @if($space10Enabled)
                <div x-show="showSpace10Modal" x-cloak
                     class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-40 p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Subir informes a Space10</h3>
                                <p class="text-sm text-gray-500 mt-1">Se subirá un PDF por protocolo al historial del paciente en Space10 (por DNI).</p>
                            </div>
                            <button type="button" @click="showSpace10Modal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <template x-if="space10ToUpload.length > 0">
                            <p class="mb-4 text-sm text-gray-700">
                                Se intentará subir <strong x-text="space10ToUpload.length"></strong> protocolo(s).
                            </p>
                        </template>

                        <template x-if="space10SkippedPreview.length > 0">
                            <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-yellow-800">
                                <p class="font-medium mb-1">Se omitirán en la subida:</p>
                                <ul class="list-disc list-inside space-y-0.5">
                                    <template x-for="row in space10SkippedPreview" :key="row.id">
                                        <li x-text="row.protocol_number + ' (' + row.reason + ')'"></li>
                                    </template>
                                </ul>
                            </div>
                        </template>

                        <div class="mb-4" x-show="space10ToUpload.length > 0">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">A subir</p>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <template x-for="row in space10ToUpload" :key="row.id">
                                    <li x-text="row.protocol_number + ' — DNI ' + (row.patient_dni || 'N/A')"></li>
                                </template>
                            </ul>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showSpace10Modal = false"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                                Cancelar
                            </button>
                            <button type="button" @click="sendSpace10Batch()"
                                    :disabled="space10Sending || space10ToUpload.length === 0"
                                    class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-50 text-sm font-medium">
                                <span x-show="!space10Sending">Subir a Space10</span>
                                <span x-show="space10Sending">Subiendo…</span>
                            </button>
                        </div>

                        <div x-show="space10Result" x-cloak class="mt-6 pt-4 border-t border-gray-200 space-y-2">
                            <p x-show="space10Result && space10Result.uploaded && space10Result.uploaded.length > 0" class="text-sm text-green-600"
                               x-text="'Subidos: ' + space10Result.uploaded.join(', ')"></p>
                            <p x-show="space10Result && space10Result.skipped && space10Result.skipped.length > 0" class="text-sm text-yellow-700"
                               x-text="'Omitidos: ' + space10Result.skipped.join(', ')"></p>
                            <p x-show="space10Result && space10Result.errors && space10Result.errors.length > 0" class="text-sm text-red-600"
                               x-text="'Errores: ' + space10Result.errors.join(', ')"></p>
                            <button type="button" @click="space10Result = null; showSpace10Modal = false; window.location.reload()" class="mt-2 text-xs text-violet-600 hover:underline">
                                Cerrar y actualizar
                            </button>
                        </div>
                    </div>
                </div>
                @endif
        @endif
        @endcan

        @php
            $admissionMetaJson = $admissions->map(function ($a) use ($space10Enabled) {
                $validatedForBatch = $a->admissionTests->where('is_validated', true)->count() > 0;
                $patientDni = trim((string) ($a->patient?->patientId ?? ''));

                return [
                    'id' => $a->id,
                    'protocol_number' => $a->protocol_number ?? $a->number,
                    'insurance_id' => $a->insurance,
                    'patient_id' => $a->patient_id,
                    'insurance_email' => $a->insuranceRelation?->primaryEntityEmail(),
                    'insurance_emails' => $a->insuranceRelation?->recipientEmails() ?? [],
                    'insurance_email_entries' => ($a->insuranceRelation?->emails ?? collect())->map(fn ($e) => [
                        'email' => $e->email,
                        'label' => $e->label ? $e->label.' · '.$e->email : $e->email,
                    ])->values()->all(),
                    'patient_email' => $a->patient?->email,
                    'can_batch_send' => $validatedForBatch,
                    'can_batch_space10' => $space10Enabled && $validatedForBatch && $patientDni !== '',
                    'already_on_space10' => $a->isUploadedToSpace10(),
                    'patient_dni' => $patientDni !== '' ? $patientDni : null,
                ];
            })->values();
        @endphp
        <script>
            function admissionBatchMail() {
                return {
                    selectedIds: [],
                    selectAll: false,
                    showBatchModal: false,
                    showSpace10Modal: false,
                    batchSending: false,
                    space10Sending: false,
                    batchEmail: '',
                    batchMessage: '',
                    batchResult: null,
                    space10Result: null,
                    admissionMeta: @json($admissionMetaJson),
                    batchSkipped: [],
                    batchToSend: [],
                    space10ToUpload: [],
                    space10SkippedPreview: [],

                    toggleAll() {
                        const eligible = this.admissionMeta.filter(a => a.can_batch_send);
                        if (this.selectAll) {
                            this.selectedIds = eligible.map(a => Number(a.id));
                        } else {
                            this.selectedIds = [];
                        }
                    },

                    openBatchModal() {
                        const ids = new Set(this.selectedIds.map(id => Number(id)));
                        const selected = this.admissionMeta.filter(a => ids.has(Number(a.id)));
                        this.batchSkipped = selected.filter(a => !a.can_batch_send);
                        this.batchToSend = selected.filter(a => a.can_batch_send);
                        this.batchEmail = '';
                        this.batchMessage = '';
                        this.batchResult = null;
                        this.showBatchModal = true;
                    },

                    openSpace10Modal() {
                        const ids = new Set(this.selectedIds.map(id => Number(id)));
                        const selected = this.admissionMeta.filter(a => ids.has(Number(a.id)));
                        this.space10ToUpload = [];
                        this.space10SkippedPreview = [];

                        selected.forEach(row => {
                            if (row.already_on_space10) {
                                this.space10SkippedPreview.push({ ...row, reason: 'ya subido a Space10' });
                            } else if (!row.can_batch_space10) {
                                const reason = !row.can_batch_send
                                    ? 'sin determinaciones validadas'
                                    : 'sin DNI';
                                this.space10SkippedPreview.push({ ...row, reason });
                            } else {
                                this.space10ToUpload.push(row);
                            }
                        });

                        this.space10Result = null;
                        this.showSpace10Modal = true;
                    },

                    get batchInsuranceChips() {
                        if (!this.canUseInsuranceShortcut) return [];
                        return this.batchToSend[0].insurance_email_entries || [];
                    },

                    get canUseInsuranceShortcut() {
                        const list = this.batchToSend;
                        if (list.length === 0) return false;
                        const id = list[0].insurance_id;
                        const emails = list[0].insurance_emails;
                        if (!emails?.length || !id) return false;
                        return list.every(a => a.insurance_id === id);
                    },

                    get canUsePatientShortcut() {
                        const list = this.batchToSend;
                        if (list.length === 0) return false;
                        const pid = list[0].patient_id;
                        const email = list[0].patient_email;
                        if (!pid || !email) return false;
                        return list.every(a => a.patient_id === pid);
                    },

                    get space10EligibleCount() {
                        const ids = new Set(this.selectedIds.map(id => Number(id)));
                        return this.admissionMeta.filter(a => ids.has(Number(a.id)) && a.can_batch_space10 && !a.already_on_space10).length;
                    },

                    applyInsuranceEmail() {
                        this.applyAllInsuranceEmails();
                    },

                    applyAllInsuranceEmails() {
                        const emails = this.batchToSend[0]?.insurance_emails;
                        if (emails?.length) {
                            this.batchEmail = emails.join(', ');
                        }
                    },

                    applyPatientEmail() {
                        if (this.batchToSend.length && this.batchToSend[0].patient_email) {
                            this.batchEmail = this.batchToSend[0].patient_email;
                        }
                    },

                    async sendBatch() {
                        if (!this.batchEmail || this.batchToSend.length === 0) return;
                        this.batchSending = true;
                        try {
                            const res = await fetch(@json(route('lab.admissions.batch-email')), {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                },
                                body: JSON.stringify({
                                    admission_ids: this.batchToSend.map(a => a.id),
                                    email: this.batchEmail,
                                    message: this.batchMessage || null,
                                }),
                            });
                            this.batchResult = await res.json();
                        } catch (e) {
                            this.batchResult = { sent: [], skipped: [], errors: ['Error de red'] };
                        }
                        this.batchSending = false;
                    },

                    async sendSpace10Batch() {
                        if (this.space10ToUpload.length === 0) return;
                        this.space10Sending = true;
                        try {
                            const res = await fetch(@json(route('lab.admissions.batch-space10')), {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                },
                                body: JSON.stringify({
                                    admission_ids: this.space10ToUpload.map(a => a.id),
                                }),
                            });
                            this.space10Result = await res.json();
                        } catch (e) {
                            this.space10Result = { uploaded: [], skipped: [], errors: ['Error de red'] };
                        }
                        this.space10Sending = false;
                    },
                };
            }
        </script>
    </div>
</x-lab-layout>

