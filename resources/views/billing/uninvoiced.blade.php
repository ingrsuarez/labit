<x-admin-layout>
    <div class="p-4 md:p-6" x-data="batchBilling()">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Protocolos sin facturar</h1>
                <p class="text-gray-500 text-sm mt-1">Control de facturación de protocolos de laboratorio</p>
            </div>
        </div>

        {{-- Barra de acción masiva --}}
        <div x-show="selectedIds.length > 0" x-cloak
             class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between">
            <span class="text-sm text-blue-800">
                <strong x-text="selectedIds.length"></strong> protocolo(s) seleccionado(s)
            </span>
            <button @click="submitBatch()"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="bi bi-receipt mr-1"></i> Facturar seleccionados
            </button>
        </div>

        {{-- Resumen de totales --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border p-4">
                <div class="text-2xl font-bold text-teal-600">{{ $admissions->count() }}</div>
                <div class="text-sm text-gray-500">Lab Clínico</div>
                <div class="text-xs text-gray-400 mt-1">${{ number_format($totals['clinico'], 2, ',', '.') }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-4">
                <div class="text-2xl font-bold text-cyan-600">{{ $samples->count() }}</div>
                <div class="text-sm text-gray-500">Aguas y Alimentos</div>
                <div class="text-xs text-gray-400 mt-1">${{ number_format($totals['aguas'], 2, ',', '.') }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-4">
                <div class="text-2xl font-bold text-amber-600">{{ $vetAdmissions->count() }}</div>
                <div class="text-sm text-gray-500">Veterinario</div>
                <div class="text-xs text-gray-400 mt-1">${{ number_format($totals['veterinario'], 2, ',', '.') }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-4">
                <div class="text-2xl font-bold text-gray-800">{{ $admissions->count() + $samples->count() + $vetAdmissions->count() }}</div>
                <div class="text-sm text-gray-500">Total</div>
                <div class="text-xs text-gray-400 mt-1">${{ number_format($totals['clinico'] + $totals['aguas'] + $totals['veterinario'], 2, ',', '.') }}</div>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('billing.uninvoiced') }}" class="bg-white rounded-xl shadow-sm border p-4 mb-6">
            <div class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Módulo</label>
                    <select name="module" class="rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="all" {{ $module === 'all' ? 'selected' : '' }}>Todos los módulos</option>
                        <option value="clinico" {{ $module === 'clinico' ? 'selected' : '' }}>Lab Clínico</option>
                        <option value="aguas" {{ $module === 'aguas' ? 'selected' : '' }}>Aguas y Alimentos</option>
                        <option value="veterinario" {{ $module === 'veterinario' ? 'selected' : '' }}>Veterinario</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
                    <select name="billing_status" class="rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="uninvoiced" {{ $billingStatus === 'uninvoiced' ? 'selected' : '' }}>Sin facturar</option>
                        <option value="invoiced" {{ $billingStatus === 'invoiced' ? 'selected' : '' }}>Facturados</option>
                        <option value="all" {{ $billingStatus === 'all' ? 'selected' : '' }}>Todos</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Obra Social</label>
                    <select name="insurance_id" class="rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Todas</option>
                        @foreach($insurances as $ins)
                            <option value="{{ $ins->id }}" {{ request('insurance_id') == $ins->id ? 'selected' : '' }}>{{ $ins->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Cliente</label>
                    <select name="customer_id" class="rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Todos</option>
                        @foreach($customers as $cust)
                            <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>{{ $cust->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700 transition-colors">
                    <i class="bi bi-funnel mr-1"></i> Filtrar
                </button>
                @if(request()->hasAny(['module', 'insurance_id', 'date_from', 'date_to', 'customer_id', 'billing_status']) && ($module !== 'all' || $billingStatus !== 'uninvoiced' || request('insurance_id') || request('date_from') || request('date_to') || request('customer_id')))
                    <a href="{{ route('billing.uninvoiced') }}" class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">
                        <i class="bi bi-x-circle mr-1"></i> Limpiar
                    </a>
                @endif
            </div>
        </form>

        {{-- Lab Clínico --}}
        @if(in_array($module, ['all', 'clinico']))
        <div class="bg-white rounded-xl shadow-sm border mb-6 overflow-hidden">
            <div class="px-4 py-3 bg-teal-50 border-b flex items-center justify-between">
                <h2 class="text-sm font-semibold text-teal-800">
                    <i class="bi bi-heart-pulse mr-1"></i> Lab Clínico
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-teal-100 text-teal-700">{{ $admissions->count() }}</span>
                </h2>
            </div>
            @if($admissions->isEmpty())
                <div class="p-6 text-center text-sm text-gray-400">No hay protocolos clínicos {{ $billingStatus === 'uninvoiced' ? 'sin facturar' : '' }}</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                @if($billingStatus === 'uninvoiced')
                                <th class="w-8 px-2 py-2">
                                    <input type="checkbox" @click="toggleAllInSection('admission', $event)" class="rounded border-gray-300">
                                </th>
                                @endif
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Protocolo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Obra Social</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Determinaciones</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $admissionGroups = $admissions->groupBy(fn($a) => $a->insuranceRelation?->name ?? 'Sin OS');
                            @endphp
                            @foreach($admissionGroups as $osName => $group)
                                <tr class="bg-gray-50">
                                    <td colspan="{{ $billingStatus === 'uninvoiced' ? 9 : 8 }}" class="px-4 py-2 text-sm font-semibold text-gray-700">
                                        {{ $osName }} — {{ $group->count() }} protocolo(s)
                                        @if($billingStatus === 'uninvoiced')
                                        <button type="button" @click="selectGroup('admission', {{ $group->pluck('id') }})" class="ml-2 text-xs text-blue-600 hover:underline">
                                            Seleccionar todos
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @foreach($group as $adm)
                                    <tr class="hover:bg-gray-50">
                                        @if($billingStatus === 'uninvoiced')
                                        <td class="px-2 py-2">
                                            <input type="checkbox" value="admission_{{ $adm->id }}" data-protocol-id="{{ $adm->id }}" data-type="admission"
                                                   x-model="selectedIds" class="rounded border-gray-300">
                                        </td>
                                        @endif
                                        <td class="px-4 py-2 font-mono text-xs">{{ $adm->protocol_number }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $adm->date?->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2">{{ $adm->patient?->name ?? '-' }}</td>
                                        <td class="px-4 py-2">
                                            @if($adm->isParticular())
                                                <span class="px-1.5 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-700">Particular</span>
                                            @else
                                                {{ $adm->insuranceRelation?->name ?? '-' }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $adm->labBranch?->name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $adm->admissionTests->pluck('test.name')->filter()->implode(', ') }}</td>
                                        <td class="px-4 py-2 text-right font-medium">${{ number_format($adm->total_patient ?: $adm->patient_price ?: 0, 2, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="{{ route('lab.admissions.show', $adm) }}" class="text-gray-400 hover:text-teal-600 transition-colors" title="Ver protocolo">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                @if($adm->isParticular() && $billingStatus === 'uninvoiced')
                                                    @can('sales-invoices.create')
                                                        <a href="{{ route('sales-invoices.from-protocol', ['protocol_type' => 'admission', 'protocol_id' => $adm->id]) }}"
                                                           class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition-colors">
                                                            <i class="bi bi-receipt mr-1"></i> Facturar
                                                        </a>
                                                    @endcan
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @endif

        {{-- Aguas y Alimentos --}}
        @if(in_array($module, ['all', 'aguas']))
        <div class="bg-white rounded-xl shadow-sm border mb-6 overflow-hidden">
            <div class="px-4 py-3 bg-cyan-50 border-b flex items-center justify-between">
                <h2 class="text-sm font-semibold text-cyan-800">
                    <i class="bi bi-droplet mr-1"></i> Aguas y Alimentos
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-cyan-100 text-cyan-700">{{ $samples->count() }}</span>
                </h2>
            </div>
            @if($samples->isEmpty())
                <div class="p-6 text-center text-sm text-gray-400">No hay protocolos de aguas {{ $billingStatus === 'uninvoiced' ? 'sin facturar' : '' }}</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                @if($billingStatus === 'uninvoiced')
                                <th class="w-8 px-2 py-2">
                                    <input type="checkbox" @click="toggleAllInSection('sample', $event)" class="rounded border-gray-300">
                                </th>
                                @endif
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Protocolo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Determinaciones</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $sampleGroups = $samples->groupBy(fn($s) => $s->customer?->name ?? 'Sin cliente');
                            @endphp
                            @foreach($sampleGroups as $clientName => $group)
                                <tr class="bg-gray-50">
                                    <td colspan="{{ $billingStatus === 'uninvoiced' ? 8 : 7 }}" class="px-4 py-2 text-sm font-semibold text-gray-700">
                                        {{ $clientName }} — {{ $group->count() }} protocolo(s)
                                        @if($billingStatus === 'uninvoiced')
                                        <button type="button" @click="selectGroup('sample', {{ $group->pluck('id') }})" class="ml-2 text-xs text-blue-600 hover:underline">
                                            Seleccionar todos
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @foreach($group as $sample)
                                    <tr class="hover:bg-gray-50">
                                        @if($billingStatus === 'uninvoiced')
                                        <td class="px-2 py-2">
                                            <input type="checkbox" value="sample_{{ $sample->id }}" data-protocol-id="{{ $sample->id }}" data-type="sample"
                                                   x-model="selectedIds" class="rounded border-gray-300">
                                        </td>
                                        @endif
                                        <td class="px-4 py-2 font-mono text-xs">{{ $sample->protocol_number }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $sample->entry_date?->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2">{{ $sample->customer?->name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $sample->labBranch?->name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $sample->determinations->pluck('test.name')->filter()->implode(', ') }}</td>
                                        <td class="px-4 py-2 text-right font-medium">${{ number_format($sample->determinations->sum('price'), 2, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <a href="{{ route('sample.show', $sample) }}" class="text-gray-400 hover:text-cyan-600 transition-colors" title="Ver protocolo">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @endif

        {{-- Veterinario --}}
        @if(in_array($module, ['all', 'veterinario']))
        <div class="bg-white rounded-xl shadow-sm border mb-6 overflow-hidden">
            <div class="px-4 py-3 bg-amber-50 border-b flex items-center justify-between">
                <h2 class="text-sm font-semibold text-amber-800">
                    <i class="bi bi-bug mr-1"></i> Veterinario
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">{{ $vetAdmissions->count() }}</span>
                </h2>
            </div>
            @if($vetAdmissions->isEmpty())
                <div class="p-6 text-center text-sm text-gray-400">No hay protocolos veterinarios {{ $billingStatus === 'uninvoiced' ? 'sin facturar' : '' }}</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                @if($billingStatus === 'uninvoiced')
                                <th class="w-8 px-2 py-2">
                                    <input type="checkbox" @click="toggleAllInSection('vet_admission', $event)" class="rounded border-gray-300">
                                </th>
                                @endif
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Protocolo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dueño</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Animal</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Veterinario</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $vetGroups = $vetAdmissions->groupBy(fn($v) => $v->customer?->name ?? 'Sin veterinario');
                            @endphp
                            @foreach($vetGroups as $vetName => $group)
                                <tr class="bg-gray-50">
                                    <td colspan="{{ $billingStatus === 'uninvoiced' ? 9 : 8 }}" class="px-4 py-2 text-sm font-semibold text-gray-700">
                                        {{ $vetName }} — {{ $group->count() }} protocolo(s)
                                        @if($billingStatus === 'uninvoiced')
                                        <button type="button" @click="selectGroup('vet_admission', {{ $group->pluck('id') }})" class="ml-2 text-xs text-blue-600 hover:underline">
                                            Seleccionar todos
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @foreach($group as $vet)
                                    <tr class="hover:bg-gray-50">
                                        @if($billingStatus === 'uninvoiced')
                                        <td class="px-2 py-2">
                                            <input type="checkbox" value="vet_admission_{{ $vet->id }}" data-protocol-id="{{ $vet->id }}" data-type="vet_admission"
                                                   x-model="selectedIds" class="rounded border-gray-300">
                                        </td>
                                        @endif
                                        <td class="px-4 py-2 font-mono text-xs">{{ $vet->protocol_number }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $vet->date?->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2">{{ $vet->owner_name ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ $vet->animal_name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-gray-500">{{ $vet->customer?->name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $vet->labBranch?->name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-right font-medium">${{ number_format($vet->total_price ?? 0, 2, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="{{ route('vet.admissions.show', $vet) }}" class="text-gray-400 hover:text-amber-600 transition-colors" title="Ver protocolo">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                @if($billingStatus === 'uninvoiced')
                                                @can('sales-invoices.create')
                                                    <a href="{{ route('sales-invoices.from-protocol', ['protocol_type' => 'vet_admission', 'protocol_id' => $vet->id]) }}"
                                                       class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition-colors">
                                                        <i class="bi bi-receipt mr-1"></i> Facturar
                                                    </a>
                                                @endcan
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function batchBilling() {
            return {
                selectedIds: [],
                currentType: null,

                toggleAllInSection(type, event) {
                    const checkboxes = document.querySelectorAll(`input[data-type="${type}"]`);
                    if (event.target.checked) {
                        this.currentType = type;
                        this.selectedIds = [];
                        checkboxes.forEach(el => {
                            if (!this.selectedIds.includes(el.value)) {
                                this.selectedIds.push(el.value);
                            }
                        });
                    } else {
                        checkboxes.forEach(el => {
                            this.selectedIds = this.selectedIds.filter(id => id !== el.value);
                        });
                        if (this.selectedIds.length === 0) this.currentType = null;
                    }
                },

                selectGroup(type, ids) {
                    if (this.currentType && this.currentType !== type) {
                        this.selectedIds = [];
                    }
                    this.currentType = type;
                    ids.forEach(id => {
                        const prefixed = type + '_' + String(id);
                        if (!this.selectedIds.includes(prefixed)) {
                            this.selectedIds.push(prefixed);
                        }
                    });
                },

                extractIds() {
                    return this.selectedIds.map(v => v.replace(/^[a-z_]+_/, ''));
                },

                submitBatch() {
                    if (this.selectedIds.length === 0) return;

                    const type = this.currentType || this.detectType();
                    if (!type) {
                        alert('Seleccione protocolos de un solo módulo a la vez.');
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("billing.batch-preview") }}';

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);

                    const typeInput = document.createElement('input');
                    typeInput.type = 'hidden';
                    typeInput.name = 'protocol_type';
                    typeInput.value = type;
                    form.appendChild(typeInput);

                    this.extractIds().forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'protocol_ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                },

                detectType() {
                    if (this.selectedIds.length === 0) return null;
                    const firstChecked = document.querySelector(`input[data-type][value="${this.selectedIds[0]}"]`);
                    return firstChecked ? firstChecked.dataset.type : null;
                },

                init() {
                    this.$watch('selectedIds', (val) => {
                        if (val.length > 0 && !this.currentType) {
                            this.currentType = this.detectType();
                        }
                        if (val.length > 0 && this.currentType) {
                            const otherTypes = document.querySelectorAll(`input[data-type]:not([data-type="${this.currentType}"])`);
                            otherTypes.forEach(el => {
                                el.disabled = true;
                                el.closest('tr')?.classList.add('opacity-50');
                            });
                        } else {
                            document.querySelectorAll('input[data-type]').forEach(el => {
                                el.disabled = false;
                                el.closest('tr')?.classList.remove('opacity-50');
                            });
                            this.currentType = null;
                        }
                    });
                }
            }
        }
    </script>
    @endpush
</x-admin-layout>
