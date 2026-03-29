<x-lab-layout title="Editar Admisión">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0" x-data="editAdmission()">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('lab.admissions.index') }}" class="hover:text-teal-600">Admisiones</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <a href="{{ route('lab.admissions.show', $admission) }}" class="hover:text-teal-600">{{ $admission->protocol_number }}</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span>Editar</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Editar Admisión — {{ $admission->protocol_number }}</h1>
        </div>

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('lab.admissions.update', $admission) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Sección: Paciente (solo lectura) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Paciente</h2>
                <div class="bg-teal-50 rounded-lg p-4">
                    <p class="text-lg font-medium text-gray-900">{{ $admission->patient->full_name }}</p>
                    <p class="text-sm text-gray-600">
                        DNI: <span class="font-medium">{{ $admission->patient->patientId }}</span>
                        @if($admission->patient->birth)
                            | Nacimiento: <span class="font-medium">{{ $admission->patient->birth->format('d/m/Y') }}</span>
                        @endif
                        @if($admission->patient->phone)
                            | Tel: <span class="font-medium">{{ $admission->patient->phone }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Sección: Datos de la Admisión -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos de la Admisión</h2>

                @include('components.branch-select', ['selectedBranchId' => old('lab_branch_id', $admission->lab_branch_id)])

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                    <!-- Fecha -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input type="date" name="date"
                               value="{{ old('date', $admission->date?->format('Y-m-d') ?? $admission->date) }}" required
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <!-- Obra Social -->
                    <div class="relative" @click.away="showInsuranceDropdown = false">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Obra Social *</label>
                        <div class="relative">
                            <input type="text"
                                   x-model="insuranceSearch"
                                   @focus="showInsuranceDropdown = true"
                                   @input="showInsuranceDropdown = true"
                                   @keydown.enter.prevent="if (filteredInsurances.length > 0) selectInsurance(filteredInsurances[0])"
                                   @keydown.escape="showInsuranceDropdown = false"
                                   placeholder="Buscar obra social..."
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                            <input type="hidden" name="insurance_id" :value="insuranceId" required>
                            <button type="button" x-show="insuranceId" @click="clearInsurance()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                ✕
                            </button>
                        </div>
                        <div x-show="showInsuranceDropdown && filteredInsurances.length > 0" x-cloak
                             class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="ins in filteredInsurances" :key="ins.id">
                                <div @click="selectInsurance(ins)"
                                     class="px-3 py-2 cursor-pointer hover:bg-teal-50 text-sm"
                                     :class="{ 'bg-teal-100 font-medium': insuranceId == ins.id }">
                                    <span x-text="ins.name"></span>
                                </div>
                            </template>
                        </div>
                        <div x-show="showInsuranceDropdown && insuranceSearch && filteredInsurances.length === 0" x-cloak
                             class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg p-3 text-sm text-gray-500">
                            No se encontraron resultados
                        </div>
                    </div>

                    <!-- Número de Afiliado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nro. Afiliado</label>
                        <input type="text" name="affiliate_number"
                               value="{{ old('affiliate_number', $admission->affiliate_number) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <!-- Médico Solicitante -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Médico Solicitante</label>
                        <input type="text" name="requesting_doctor"
                               value="{{ old('requesting_doctor', $admission->requesting_doctor) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                </div>

                <!-- Diagnóstico y Observaciones -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Diagnóstico</label>
                        <input type="text" name="diagnosis"
                               value="{{ old('diagnosis', $admission->diagnosis) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <input type="text" name="observations"
                               value="{{ old('observations', $admission->observations) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                </div>
            </div>

            <!-- Prácticas actuales (solo lectura) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Prácticas ({{ $admission->admissionTests->count() }})
                </h2>
                <p class="text-sm text-gray-500 mb-4">Las prácticas se gestionan desde la vista de detalle del protocolo.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Práctica</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($admission->admissionTests as $at)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $at->test->code }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $at->test->name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">${{ number_format($at->price, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('lab.admissions.show', $admission) }}"
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <script>
        function editAdmission() {
            return {
                insuranceId: '{{ old('insurance_id', $admission->insurance) }}',
                insuranceSearch: '',
                showInsuranceDropdown: false,
                insuranceItems: @json($insurances->map(fn($ins) => ['id' => $ins->id, 'name' => strtoupper($ins->name), 'type' => $ins->type])),
                _selectedInsuranceName: '',

                get filteredInsurances() {
                    if (!this.insuranceSearch || this.insuranceSearch === this._selectedInsuranceName) return this.insuranceItems;
                    const q = this.insuranceSearch.toLowerCase();
                    return this.insuranceItems.filter(i => i.name.toLowerCase().includes(q));
                },

                init() {
                    if (this.insuranceId) {
                        const found = this.insuranceItems.find(i => i.id == this.insuranceId);
                        if (found) {
                            this.insuranceSearch = found.name;
                            this._selectedInsuranceName = found.name;
                        }
                    }
                },

                selectInsurance(ins) {
                    this.insuranceId = ins.id;
                    this.insuranceSearch = ins.name;
                    this._selectedInsuranceName = ins.name;
                    this.showInsuranceDropdown = false;
                },

                clearInsurance() {
                    this.insuranceId = '';
                    this.insuranceSearch = '';
                    this._selectedInsuranceName = '';
                },
            }
        }
    </script>
</x-lab-layout>
