<x-lab-layout title="Nueva Admisión">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0" x-data="admissionManager()">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('lab.admissions.index') }}" class="hover:text-teal-600">Admisiones</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span>Nueva Admisión</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Nueva Admisión de Paciente</h1>
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

        <form action="{{ route('lab.admissions.store') }}" method="POST" @submit="prepareSubmit">
            @csrf

            <!-- Sección: Paciente -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Paciente</h2>
                
                @if($patient)
                    <!-- Paciente ya seleccionado -->
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    <div class="bg-teal-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-lg font-medium text-gray-900">{{ $patient->full_name }}</p>
                                <p class="text-sm text-gray-600">
                                    DNI: <span class="font-medium">{{ $patient->patientId }}</span>
                                    @if($patient->birth)
                                        | Nacimiento: <span class="font-medium">{{ $patient->birth->format('d/m/Y') }}</span>
                                    @endif
                                    @if($patient->phone)
                                        | Tel: <span class="font-medium">{{ $patient->phone }}</span>
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('lab.admissions.create') }}" class="text-teal-600 hover:text-teal-800 text-sm">
                                Cambiar
                            </a>
                        </div>
                    </div>
                @else
                    <!-- Buscador de paciente -->
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar Paciente</label>
                        <input type="text" x-model="patientSearch" @input="searchPatients" @focus="showPatientDropdown = true"
                               placeholder="Buscar por DNI, nombre o apellido..."
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <input type="hidden" name="patient_id" x-model="selectedPatient.id">
                        
                        <!-- Dropdown de pacientes -->
                        <div x-show="showPatientDropdown && filteredPatients.length > 0" 
                             @click.away="showPatientDropdown = false"
                             class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="patient in filteredPatients" :key="patient.id">
                                <div @click="selectPatient(patient)" 
                                     class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0">
                                    <div class="font-medium text-gray-900" x-text="patient.fullName"></div>
                                    <div class="text-sm text-gray-500">
                                        <span x-text="'DNI: ' + patient.patientId"></span>
                                        <span x-show="patient.birth" x-text="' | Nac: ' + patient.birth"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Paciente seleccionado -->
                        <div x-show="selectedPatient.id" class="mt-3 bg-teal-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900" x-text="selectedPatient.fullName"></p>
                                    <p class="text-sm text-gray-600">
                                        DNI: <span class="font-medium" x-text="selectedPatient.patientId"></span>
                                    </p>
                                </div>
                                <button type="button" @click="clearPatient" class="text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Botón para crear paciente nuevo -->
                        <div x-show="!selectedPatient.id && patientSearch.length > 2 && filteredPatients.length === 0" 
                             class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-sm text-yellow-800 mb-2">No se encontró el paciente. ¿Desea crearlo?</p>
                            <a href="{{ route('patient.index') }}" target="_blank"
                               class="inline-flex items-center px-3 py-1.5 bg-yellow-600 text-white text-sm rounded-lg hover:bg-yellow-700">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                                Crear Paciente Nuevo
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sección: Datos de la Admisión -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos de la Admisión</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Fecha -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <!-- Obra Social -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Obra Social *</label>
                        <select name="insurance_id" x-model="insuranceId" @change="onInsuranceChange" required
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Seleccionar...</option>
                            @foreach($insurances as $ins)
                                <option value="{{ $ins->id }}" 
                                        data-nbu="{{ $ins->nbu_value }}"
                                        {{ old('insurance_id', $patient?->insurance) == $ins->id ? 'selected' : '' }}>
                                    {{ strtoupper($ins->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Número de Afiliado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nro. Afiliado</label>
                        <input type="text" name="affiliate_number" 
                               value="{{ old('affiliate_number', $patient?->insurance_cod) }}"
                               x-model="affiliateNumber"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <!-- Médico Solicitante -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Médico Solicitante</label>
                        <input type="text" name="requesting_doctor" value="{{ old('requesting_doctor') }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                </div>

                <!-- Diagnóstico y Observaciones -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Diagnóstico</label>
                        <input type="text" name="diagnosis" value="{{ old('diagnosis') }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <input type="text" name="observations" value="{{ old('observations') }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                </div>
            </div>

            <!-- Sección: Prácticas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Prácticas / Análisis</h2>
                
                <!-- Buscador de prácticas -->
                <div class="mb-4 relative" x-show="insuranceId">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Agregar Práctica</label>
                    <input type="text" x-model="testSearch" @input="searchTests" @focus="showTestDropdown = true"
                           placeholder="Buscar por código o nombre..."
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    
                    <!-- Dropdown de prácticas -->
                    <div x-show="showTestDropdown && filteredTests.length > 0" 
                         @click.away="showTestDropdown = false"
                         class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="test in filteredTests" :key="test.id">
                            <div @click="addTest(test)" 
                                 class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="font-medium text-gray-900" x-text="test.code"></span>
                                        <span class="text-gray-600" x-text="' - ' + test.name"></span>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-medium text-teal-600" x-text="'$' + formatNumber(test.calculated_price || 0)"></span>
                                        <span x-show="test.requires_authorization" class="ml-2 text-xs text-yellow-600">(Req. Aut.)</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="!insuranceId" class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-4">
                    Seleccione una obra social para agregar prácticas
                </div>

                <!-- Tabla de prácticas seleccionadas -->
                <div x-show="selectedTests.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Práctica</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Paga Paciente</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Copago</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(test, index) in selectedTests" :key="test.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900" x-text="test.code"></td>
                                    <td class="px-4 py-3 text-sm text-gray-900" x-text="test.name"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                                        <input type="number" step="0.01" x-model="test.price" @input="calculateTotals"
                                               class="w-28 text-right border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <select x-model="test.authorization_status" @change="calculateTotals"
                                                class="text-sm border-gray-300 rounded">
                                            <option value="not_required">No requiere</option>
                                            <option value="authorized">Autorizado</option>
                                            <option value="pending">Pendiente</option>
                                            <option value="rejected">Rechazado</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <input type="checkbox" x-model="test.paid_by_patient" @change="calculateTotals"
                                               class="rounded border-gray-300 text-teal-600">
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <input type="number" step="0.01" x-model="test.copago" @input="calculateTotals"
                                               class="w-24 text-right border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <button type="button" @click="removeTest(index)" 
                                                class="text-red-500 hover:text-red-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Totales:</td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right" x-text="'$' + formatNumber(totalGeneral)"></td>
                                <td colspan="2" class="px-4 py-3 text-sm text-right">
                                    <span class="text-teal-600">OS: $<span x-text="formatNumber(totalInsurance)"></span></span>
                                </td>
                                <td colspan="2" class="px-4 py-3 text-sm text-left">
                                    <span class="text-orange-600">Pac: $<span x-text="formatNumber(totalPatient)"></span></span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div x-show="selectedTests.length === 0 && insuranceId" class="text-center py-8 text-gray-500">
                    No hay prácticas agregadas. Use el buscador para agregar prácticas.
                </div>

                <!-- Hidden inputs para enviar los tests -->
                <template x-for="(test, index) in selectedTests" :key="'hidden-' + test.id">
                    <div>
                        <input type="hidden" :name="'tests[' + index + '][test_id]'" :value="test.id">
                        <input type="hidden" :name="'tests[' + index + '][price]'" :value="test.price">
                        <input type="hidden" :name="'tests[' + index + '][authorization_status]'" :value="test.authorization_status">
                        <input type="hidden" :name="'tests[' + index + '][paid_by_patient]'" :value="test.paid_by_patient ? 1 : 0">
                        <input type="hidden" :name="'tests[' + index + '][copago]'" :value="test.copago">
                    </div>
                </template>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('lab.admissions.index') }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        :disabled="selectedTests.length === 0 || !hasPatient"
                        class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Guardar Admisión
                </button>
            </div>
        </form>
    </div>

    <script>
        function admissionManager() {
            return {
                // Paciente
                patientSearch: '',
                filteredPatients: [],
                showPatientDropdown: false,
                selectedPatient: { id: null, fullName: '', patientId: '' },
                hasPatient: {{ $patient ? 'true' : 'false' }},

                // Admisión
                insuranceId: '{{ old('insurance_id', $patient?->insurance ?? '') }}',
                affiliateNumber: '{{ old('affiliate_number', $patient?->insurance_cod ?? '') }}',

                // Prácticas
                testSearch: '',
                filteredTests: [],
                showTestDropdown: false,
                selectedTests: [],
                allTests: @json($tests),

                // Totales
                totalInsurance: 0,
                totalPatient: 0,
                totalGeneral: 0,

                async searchPatients() {
                    if (this.patientSearch.length < 2) {
                        this.filteredPatients = [];
                        return;
                    }

                    try {
                        const response = await fetch(`{{ route('lab.admissions.searchPatients') }}?q=${encodeURIComponent(this.patientSearch)}`);
                        this.filteredPatients = await response.json();
                        this.showPatientDropdown = true;
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                selectPatient(patient) {
                    this.selectedPatient = patient;
                    this.patientSearch = patient.fullName;
                    this.hasPatient = true;
                    this.showPatientDropdown = false;
                    
                    // Auto-completar datos de obra social si el paciente tiene
                    if (patient.insurance_id) {
                        this.insuranceId = patient.insurance_id;
                        this.affiliateNumber = patient.affiliate_number || '';
                    }
                },

                clearPatient() {
                    this.selectedPatient = { id: null, fullName: '', patientId: '' };
                    this.patientSearch = '';
                    this.hasPatient = false;
                },

                onInsuranceChange() {
                    // Limpiar prácticas seleccionadas cuando cambia la OS
                    this.selectedTests = [];
                    this.calculateTotals();
                },

                async searchTests() {
                    if (this.testSearch.length < 2 || !this.insuranceId) {
                        this.filteredTests = [];
                        return;
                    }

                    try {
                        const response = await fetch(`{{ route('lab.admissions.searchTests') }}?q=${encodeURIComponent(this.testSearch)}&insurance_id=${this.insuranceId}`);
                        const tests = await response.json();
                        // Filtrar los que ya están agregados
                        this.filteredTests = tests.filter(t => !this.selectedTests.find(st => st.id === t.id));
                        this.showTestDropdown = true;
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                addTest(test) {
                    // Evitar duplicados
                    if (this.selectedTests.find(t => t.id === test.id)) {
                        return;
                    }

                    this.selectedTests.push({
                        id: test.id,
                        code: test.code,
                        name: test.name,
                        price: test.calculated_price || test.price || 0,
                        authorization_status: test.requires_authorization ? 'pending' : 'not_required',
                        paid_by_patient: false,
                        copago: test.copago || 0,
                    });

                    this.testSearch = '';
                    this.filteredTests = [];
                    this.showTestDropdown = false;
                    this.calculateTotals();
                },

                removeTest(index) {
                    this.selectedTests.splice(index, 1);
                    this.calculateTotals();
                },

                calculateTotals() {
                    this.totalInsurance = 0;
                    this.totalPatient = 0;

                    this.selectedTests.forEach(test => {
                        const price = parseFloat(test.price) || 0;
                        const copago = parseFloat(test.copago) || 0;

                        if (test.paid_by_patient || test.authorization_status === 'rejected') {
                            this.totalPatient += price;
                        } else {
                            this.totalInsurance += price - copago;
                            this.totalPatient += copago;
                        }
                    });

                    this.totalGeneral = this.totalInsurance + this.totalPatient;
                },

                formatNumber(num) {
                    return parseFloat(num).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                prepareSubmit(e) {
                    if (this.selectedTests.length === 0) {
                        e.preventDefault();
                        alert('Debe agregar al menos una práctica');
                        return false;
                    }
                    return true;
                }
            }
        }
    </script>
</x-lab-layout>

