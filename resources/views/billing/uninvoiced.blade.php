<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Protocolos sin facturar</h1>
                <p class="text-gray-500 text-sm mt-1">Control de facturación de protocolos de laboratorio</p>
            </div>
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
                <div class="text-sm text-gray-500">Total sin facturar</div>
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
                @if(request()->hasAny(['module', 'insurance_id', 'date_from', 'date_to', 'customer_id']) && ($module !== 'all' || request('insurance_id') || request('date_from') || request('date_to') || request('customer_id')))
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
                <div class="p-6 text-center text-sm text-gray-400">No hay protocolos clínicos sin facturar</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
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
                            @foreach($admissions as $adm)
                                <tr class="hover:bg-gray-50">
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
                                            @if($adm->isParticular())
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
                <div class="p-6 text-center text-sm text-gray-400">No hay protocolos de aguas sin facturar</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
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
                            @foreach($samples as $sample)
                                <tr class="hover:bg-gray-50">
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
                <div class="p-6 text-center text-sm text-gray-400">No hay protocolos veterinarios sin facturar</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
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
                            @foreach($vetAdmissions as $vet)
                                <tr class="hover:bg-gray-50">
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
                                            @can('sales-invoices.create')
                                                <a href="{{ route('sales-invoices.from-protocol', ['protocol_type' => 'vet_admission', 'protocol_id' => $vet->id]) }}"
                                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition-colors">
                                                    <i class="bi bi-receipt mr-1"></i> Facturar
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @endif
    </div>
</x-admin-layout>
