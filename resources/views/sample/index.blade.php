<x-lab-layout>
    <div class="py-6 px-4 md:px-6" x-data="sampleFilter()">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Protocolos de Muestras</h1>
                <p class="text-gray-600 mt-1">Gestión de muestras de agua, alimentos y hielo</p>
            </div>
            @can('samples.create')
            <a href="{{ route('sample.create') }}" 
               class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Muestra
            </a>
            @endcan
        </div>

        <!-- Alertas -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px] relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="search" placeholder="Buscar por protocolo, cliente o lugar..."
                           class="pl-10 w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <select x-model="filterType" class="rounded-lg border-gray-300 shadow-sm text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Todos los tipos</option>
                        <option value="agua">Agua</option>
                        <option value="alimento">Alimento</option>
                        <option value="hielo">Hielo</option>
                    </select>
                </div>
                <div>
                    <select x-model="filterStatus" class="rounded-lg border-gray-300 shadow-sm text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Todos los estados</option>
                        <option value="validated">Validado</option>
                        <option value="enviado">Enviado</option>
                        <option value="completed">Completado</option>
                        <option value="incomplete">Incompleto</option>
                        <option value="pending">Pendiente</option>
                    </select>
                </div>
                @if(isset($branches) && $branches->count() > 1)
                <div>
                    <select x-model="filterBranch" class="rounded-lg border-gray-300 shadow-sm text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Todas las sedes</option>
                        <option value="none">⚠ Sin sede</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div x-show="search || filterType || filterStatus || filterBranch" x-cloak>
                    <button type="button" @click="search = ''; filterType = ''; filterStatus = ''; filterBranch = ''"
                            class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800">
                        Limpiar
                    </button>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">
                Mostrando <span x-text="visibleCount"></span> de {{ $samples->count() }} protocolos
            </p>
        </div>

        <div x-show="selectedIds.length > 0" x-cloak
             class="fixed bottom-6 right-6 z-50">
            <button type="button" @click="openBatchModal()"
                    class="inline-flex items-center px-5 py-3 bg-teal-600 text-white rounded-xl shadow-lg hover:bg-teal-700 transition-colors font-medium text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Enviar <span x-text="selectedIds.length"></span> seleccionado(s)
            </button>
        </div>

        <!-- Tabla de Protocolos -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="w-full table-fixed divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-[4%] px-3 py-3">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()"
                                   class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        </th>
                        <th class="w-[10%] px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocolo</th>
                        <th class="w-[6%] px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="w-[16%] px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lugar</th>
                        <th class="w-[26%] px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="w-[9%] px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Ingreso</th>
                        <th class="w-[11%] px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Determ.</th>
                        <th class="w-[8%] px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="w-[14%] px-2 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($samples as $sample)
                        @php
                            $validatedCount = $sample->determinations->where('is_validated', true)->count();
                            $calcStatus = $sample->calculated_status;
                        @endphp
                        <tr class="hover:bg-gray-50"
                            data-branch="{{ $sample->lab_branch_id }}"
                            x-show="matchesFilter(
                                '{{ strtolower($sample->protocol_number) }}',
                                '{{ strtolower(addslashes($sample->customer?->name ?? '')) }}',
                                '{{ strtolower(addslashes($sample->location ?? '')) }}',
                                '{{ strtolower($sample->sample_type ?? '') }}',
                                '{{ strtolower($calcStatus) }}',
                                '{{ $sample->lab_branch_id }}'
                            )">
                            <td class="px-3 py-4">
                                <input type="checkbox" :value="{{ $sample->id }}"
                                       x-model="selectedIds"
                                       @if($calcStatus !== 'validated' && $calcStatus !== 'enviado') disabled @endif
                                       class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 disabled:opacity-30">
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap">
                                <a href="{{ route('sample.show', $sample) }}" class="text-teal-600 hover:text-teal-800 font-medium">
                                    {{ $sample->protocol_number }}
                                </a>
                                @if($sample->isInvoiced())
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                        <i class="bi bi-check-circle text-[10px] mr-0.5"></i> Fact.
                                    </span>
                                @endif
                                @if($sample->labBranch && !$sample->labBranch->is_central)
                                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $sample->labBranch->name }}
                                    </span>
                                @elseif(!$sample->lab_branch_id)
                                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                        Sin sede
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @switch($sample->sample_type)
                                        @case('agua') bg-blue-100 text-blue-800 @break
                                        @case('alimento') bg-orange-100 text-orange-800 @break
                                        @case('hielo') bg-cyan-100 text-cyan-800 @break
                                        @default bg-gray-100 text-gray-800 @break
                                    @endswitch">
                                    {{ ucfirst($sample->sample_type) }}
                                </span>
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-900">
                                <span class="line-clamp-2" title="{{ $sample->location ?? '-' }}">{{ $sample->location ?? '-' }}</span>
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-900">
                                <span class="line-clamp-2" title="{{ $sample->customer->name ?? 'N/A' }}">{{ $sample->customer->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $sample->entry_date->format('d/m/Y') }}
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                <span>{{ $sample->determinations->count() }}</span>
                                @if($validatedCount > 0)
                                    <span class="text-green-600" title="{{ $validatedCount }} validadas">
                                        ({{ $validatedCount }} ✓)
                                    </span>
                                @endif
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @switch($calcStatus)
                                        @case('enviado') bg-sky-100 text-sky-800 @break
                                        @case('validated') bg-green-100 text-green-800 @break
                                        @case('completed') bg-blue-100 text-blue-800 @break
                                        @case('incomplete') bg-yellow-100 text-yellow-800 @break
                                        @case('pending') bg-gray-100 text-gray-800 @break
                                        @default bg-gray-100 text-gray-800 @break
                                    @endswitch">
                                    {{ $sample->status_label }}
                                </span>
                            </td>
                            <td class="px-2 py-4 whitespace-nowrap text-right text-sm font-medium space-x-1">
                                <a href="{{ route('sample.show', $sample) }}" class="text-teal-600 hover:text-teal-900">Ver</a>
                                <a href="{{ route('sample.edit', $sample) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                @if($validatedCount > 0)
                                    <a href="{{ route('sample.pdf.view', $sample) }}" target="_blank" 
                                       class="text-green-600 hover:text-green-900" title="Imprimir informe ({{ $validatedCount }} validadas)">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        PDF
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p class="mt-2">No hay protocolos registrados</p>
                                <a href="{{ route('sample.create') }}" class="mt-2 inline-block text-teal-600 hover:text-teal-800">
                                    Crear el primer protocolo
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="showBatchModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Enviar protocolos por email</h3>
                    <button type="button" @click="showBatchModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <template x-if="batchData.skipped.length > 0">
                    <div class="mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <p class="text-sm font-medium text-yellow-800 mb-1">
                            Los siguientes protocolos no están validados y no se enviarán:
                        </p>
                        <ul class="text-sm text-yellow-700 list-disc list-inside">
                            <template x-for="p in batchData.skipped" :key="p.id">
                                <li x-text="p.protocol_number + ' — ' + p.customer_name"></li>
                            </template>
                        </ul>
                    </div>
                </template>

                <template x-for="group in batchData.groups" :key="group.customer_id">
                    <div class="mb-4 border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="font-medium text-gray-800" x-text="group.customer_name"></p>
                                <p class="text-xs text-gray-500" x-text="group.samples.length + ' protocolo(s)'"></p>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" x-model="group.skip"
                                       class="rounded border-gray-300 text-gray-400">
                                Saltear
                            </label>
                        </div>

                        <div class="mb-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Email destinatario
                                <template x-if="!group.has_email">
                                    <span class="text-red-500 ml-1">⚠ Cliente sin email registrado</span>
                                </template>
                            </label>
                            <input type="email" x-model="group.email" :disabled="group.skip"
                                   class="w-full text-sm border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500 disabled:bg-gray-50 disabled:text-gray-400"
                                   placeholder="email@ejemplo.com">
                        </div>

                        <ul class="text-xs text-gray-500 list-disc list-inside">
                            <template x-for="s in group.samples" :key="s.id">
                                <li x-text="s.protocol_number"></li>
                            </template>
                        </ul>
                    </div>
                </template>

                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" @click="showBatchModal = false"
                            class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button" @click="sendBatch()" :disabled="batchSending"
                            class="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50">
                        <span x-show="!batchSending">Enviar</span>
                        <span x-show="batchSending">Enviando...</span>
                    </button>
                </div>
            </div>
        </div>

        <div x-show="batchResult" x-cloak
             class="fixed bottom-6 left-6 z-50 bg-white border border-gray-200 rounded-xl shadow-lg p-4 max-w-sm">
            <p class="text-sm font-medium text-gray-800 mb-1">Resultado del envío masivo</p>
            <p x-show="batchResult && batchResult.sent.length > 0" class="text-sm text-green-600"
               x-text="'Enviados: ' + batchResult.sent.join(', ')"></p>
            <p x-show="batchResult && batchResult.skipped.length > 0" class="text-sm text-yellow-600"
               x-text="'Salteados: ' + batchResult.skipped.join(', ')"></p>
            <p x-show="batchResult && batchResult.errors.length > 0" class="text-sm text-red-600"
               x-text="'Errores: ' + batchResult.errors.join(', ')"></p>
            <button type="button" @click="batchResult = null; window.location.reload()" class="mt-2 text-xs text-teal-600 hover:underline">
                Cerrar y actualizar
            </button>
        </div>
    </div>

    <script>
        function sampleFilter() {
            return {
                search: '',
                filterType: '',
                filterStatus: '',
                filterBranch: '',
                visibleCount: {{ $samples->count() }},

                selectedIds: [],
                selectAll: false,
                showBatchModal: false,
                batchData: { groups: [], skipped: [] },
                batchSending: false,
                batchResult: null,

                sampleMeta: @json($samples->map(fn ($s) => [
                    'id' => $s->id,
                    'protocol_number' => $s->protocol_number,
                    'customer_id' => $s->customer_id,
                    'customer_name' => $s->customer?->name ?? 'N/A',
                    'customer_email' => $s->customer?->email ?? null,
                    'is_validated' => $s->isValidated(),
                ])->values()),

                matchesFilter(protocol, customer, place, type, status, branchId) {
                    const q = this.search.toLowerCase().trim();
                    const matchesSearch = !q ||
                        protocol.includes(q) ||
                        customer.includes(q) ||
                        place.includes(q);
                    const matchesType = !this.filterType || type === this.filterType.toLowerCase();
                    const matchesStatus = !this.filterStatus || status === this.filterStatus.toLowerCase();
                    const matchesBranch = !this.filterBranch || (this.filterBranch === 'none' ? !branchId : branchId === this.filterBranch);
                    const visible = matchesSearch && matchesType && matchesStatus && matchesBranch;
                    this.$nextTick(() => this.updateCount());
                    return visible;
                },

                updateCount() {
                    const rows = this.$root.querySelectorAll('tbody tr[x-show]');
                    let count = 0;
                    rows.forEach(r => { if (r.style.display !== 'none') count++; });
                    this.visibleCount = count;
                },

                toggleAll() {
                    if (this.selectAll) {
                        this.selectedIds = this.sampleMeta
                            .filter(s => s.is_validated)
                            .map(s => s.id);
                    } else {
                        this.selectedIds = [];
                    }
                },

                openBatchModal() {
                    const selected = this.sampleMeta.filter(s => this.selectedIds.includes(s.id));
                    const validated = selected.filter(s => s.is_validated);
                    const skipped = selected.filter(s => !s.is_validated);

                    const groups = {};
                    for (const s of validated) {
                        if (!groups[s.customer_id]) {
                            groups[s.customer_id] = {
                                customer_id: s.customer_id,
                                customer_name: s.customer_name,
                                email: s.customer_email || '',
                                has_email: !!s.customer_email,
                                skip: false,
                                samples: [],
                            };
                        }
                        groups[s.customer_id].samples.push(s);
                    }

                    this.batchData = {
                        groups: Object.values(groups),
                        skipped: skipped,
                    };
                    this.showBatchModal = true;
                },

                async sendBatch() {
                    const emailOverrides = {};
                    const sampleIds = [];

                    for (const group of this.batchData.groups) {
                        if (group.skip) {
                            continue;
                        }
                        for (const s of group.samples) {
                            sampleIds.push(s.id);
                        }
                        emailOverrides[group.customer_id] = group.email;
                    }

                    if (sampleIds.length === 0) {
                        alert('No hay protocolos para enviar (revisá los grupos marcados como Saltear).');
                        return;
                    }

                    this.batchSending = true;

                    try {
                        const res = await fetch(@json(route('sample.batch-email')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                sample_ids: sampleIds,
                                email_overrides: emailOverrides,
                            }),
                        });
                        this.batchResult = await res.json();
                    } catch (e) {
                        this.batchResult = { sent: [], skipped: [], errors: ['Error de red'] };
                    }

                    this.batchSending = false;
                    this.showBatchModal = false;
                    this.selectedIds = [];
                    this.selectAll = false;
                },
            }
        }
    </script>
</x-lab-layout>
