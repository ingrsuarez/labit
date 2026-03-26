<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('patient.show') }}" class="hover:text-blue-600">Pacientes</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span>Editar</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Editar Paciente</h1>
            <p class="text-gray-600 mt-1">{{ ucwords($patient->name) }} {{ ucwords($patient->lastName) }} — DNI: {{ $patient->patientId }}</p>
        </div>

        @if (session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('patient.save') }}" method="POST">
            @csrf

            <!-- Datos Personales -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Datos Personales
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="name" id="name" autocomplete="off" required autofocus
                               value="{{ old('name', ucwords($patient->name)) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Apellido *</label>
                        <input type="text" name="last_name" id="last_name" autocomplete="off" required
                               value="{{ old('last_name', ucwords($patient->lastName)) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="id" class="block text-sm font-medium text-gray-700 mb-1">DNI *</label>
                        <input type="text" name="id" id="id" autocomplete="off" required
                               value="{{ old('id', $patient->patientId) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    @php
                        $birthDate = $patient->birth ? \Carbon\Carbon::parse($patient->birth)->format('Y-m-d') : '';
                    @endphp
                    <div>
                        <label for="birth" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                        <input type="date" name="birth" id="birth" autocomplete="off"
                               value="{{ old('birth', $birthDate) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="sex" class="block text-sm font-medium text-gray-700 mb-1">Sexo *</label>
                        <select id="sex" name="sex" autocomplete="off" required
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="m" {{ old('sex', $patient->sex) == 'm' ? 'selected' : '' }}>Masculino</option>
                            <option value="f" {{ old('sex', $patient->sex) == 'f' ? 'selected' : '' }}>Femenino</option>
                        </select>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono *</label>
                        <input type="text" name="phone" id="phone" autocomplete="off" required
                               value="{{ old('phone', $patient->phone) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Información de Contacto -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Información de Contacto
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="email" autocomplete="off"
                               value="{{ old('email', $patient->email) }}"
                               placeholder="ejemplo@correo.com"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Domicilio</label>
                        <input type="text" name="address" id="address" autocomplete="off"
                               value="{{ old('address', $patient->address) }}"
                               placeholder="Calle, número, piso, dpto."
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <select id="country" name="country" autocomplete="off"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="argentina" {{ old('country', $patient->country) == 'argentina' ? 'selected' : '' }}>Argentina</option>
                            <option value="brasil" {{ old('country', $patient->country) == 'brasil' ? 'selected' : '' }}>Brasil</option>
                            <option value="uruguay" {{ old('country', $patient->country) == 'uruguay' ? 'selected' : '' }}>Uruguay</option>
                            <option value="chile" {{ old('country', $patient->country) == 'chile' ? 'selected' : '' }}>Chile</option>
                        </select>
                    </div>

                    <div>
                        <label for="state" class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                        <select id="state" name="state" autocomplete="off"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            @php $currentState = old('state', $patient->state); @endphp
                            <option value="Buenos Aires" {{ $currentState == 'Buenos Aires' ? 'selected' : '' }}>Buenos Aires</option>
                            <option value="Ciudad Autonoma de Bs As" {{ $currentState == 'Ciudad Autonoma de Bs As' ? 'selected' : '' }}>Ciudad Autónoma de Bs As</option>
                            <option value="Catamarca" {{ $currentState == 'Catamarca' ? 'selected' : '' }}>Catamarca</option>
                            <option value="Chaco" {{ $currentState == 'Chaco' ? 'selected' : '' }}>Chaco</option>
                            <option value="Chubut" {{ $currentState == 'Chubut' ? 'selected' : '' }}>Chubut</option>
                            <option value="Cordoba" {{ $currentState == 'Cordoba' ? 'selected' : '' }}>Córdoba</option>
                            <option value="Corrientes" {{ $currentState == 'Corrientes' ? 'selected' : '' }}>Corrientes</option>
                            <option value="Entre Ríos" {{ $currentState == 'Entre Ríos' ? 'selected' : '' }}>Entre Ríos</option>
                            <option value="Formosa" {{ $currentState == 'Formosa' ? 'selected' : '' }}>Formosa</option>
                            <option value="Jujuy" {{ $currentState == 'Jujuy' ? 'selected' : '' }}>Jujuy</option>
                            <option value="La Rioja" {{ $currentState == 'La Rioja' ? 'selected' : '' }}>La Rioja</option>
                            <option value="Mendoza" {{ $currentState == 'Mendoza' ? 'selected' : '' }}>Mendoza</option>
                            <option value="Misiones" {{ $currentState == 'Misiones' ? 'selected' : '' }}>Misiones</option>
                            <option value="Neuquen" {{ $currentState == 'Neuquen' ? 'selected' : '' }}>Neuquén</option>
                            <option value="Rio Negro" {{ $currentState == 'Rio Negro' ? 'selected' : '' }}>Río Negro</option>
                            <option value="Salta" {{ $currentState == 'Salta' ? 'selected' : '' }}>Salta</option>
                            <option value="San Juan" {{ $currentState == 'San Juan' ? 'selected' : '' }}>San Juan</option>
                            <option value="San Luis" {{ $currentState == 'San Luis' ? 'selected' : '' }}>San Luis</option>
                            <option value="Santa Cruz" {{ $currentState == 'Santa Cruz' ? 'selected' : '' }}>Santa Cruz</option>
                            <option value="Santa Fe" {{ $currentState == 'Santa Fe' ? 'selected' : '' }}>Santa Fe</option>
                            <option value="Santiago del Estero" {{ $currentState == 'Santiago del Estero' ? 'selected' : '' }}>Santiago del Estero</option>
                            <option value="Tierra del Fuego" {{ $currentState == 'Tierra del Fuego' ? 'selected' : '' }}>Tierra del Fuego</option>
                            <option value="Tucuman" {{ $currentState == 'Tucuman' ? 'selected' : '' }}>Tucumán</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Cobertura Médica -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Cobertura Médica
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="insurance" class="block text-sm font-medium text-gray-700 mb-1">Obra Social / Prepaga</label>
                        <div x-data="patientInsuranceCombobox()" @click.away="open = false" class="relative">
                            <div class="relative">
                                <input type="text" x-model="search" @focus="open = true" @input="open = true"
                                       @keydown.enter.prevent="if (filtered.length > 0) select(filtered[0])"
                                       @keydown.escape="open = false"
                                       placeholder="Buscar obra social..."
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <input type="hidden" name="insurance" :value="selectedId">
                                <button type="button" x-show="selectedId" @click="clear()"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">✕</button>
                            </div>
                            <div x-show="open && filtered.length > 0" x-cloak
                                 class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                <template x-for="item in filtered" :key="item.id">
                                    <div @click="select(item)"
                                         class="px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm"
                                         :class="{ 'bg-blue-100 font-medium': selectedId == item.id }">
                                        <span x-text="item.name"></span>
                                    </div>
                                </template>
                            </div>
                            <div x-show="open && search && filtered.length === 0" x-cloak
                                 class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg p-3 text-sm text-gray-500">
                                No se encontraron resultados
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Seleccione la cobertura médica del paciente</p>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('patient.show') }}"
                   class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <script>
        function patientInsuranceCombobox() {
            return {
                open: false,
                search: '',
                selectedId: '{{ old('insurance', $patient->insurance ?? '') }}',
                _selectedName: '',
                items: @json($insurances->map(fn($ins) => ['id' => $ins->id, 'name' => strtoupper($ins->name)])),
                get filtered() {
                    if (!this.search || this.search === this._selectedName) return this.items;
                    const q = this.search.toLowerCase();
                    return this.items.filter(i => i.name.toLowerCase().includes(q));
                },
                select(item) {
                    this.selectedId = item.id;
                    this.search = item.name;
                    this._selectedName = item.name;
                    this.open = false;
                },
                clear() {
                    this.selectedId = '';
                    this.search = '';
                    this._selectedName = '';
                },
                init() {
                    if (this.selectedId) {
                        const found = this.items.find(i => i.id == this.selectedId);
                        if (found) { this.search = found.name; this._selectedName = found.name; }
                    }
                }
            };
        }
    </script>

    @if(isset($patient) && $patient->auditLogs->count() > 0)
    <div class="mt-6 px-4 md:px-6 pb-6">
        <x-audit-history :logs="$patient->auditLogs" />
    </div>
    @endif
</x-lab-layout>
