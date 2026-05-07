<x-lab-layout title="Nuevo Protocolo Veterinario">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0" x-data="vetAdmissionForm()">
        <div class="mb-6">
            <a href="{{ route('vet.admissions.index') }}" class="text-amber-600 hover:text-amber-800 text-sm flex items-center mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver a Protocolos
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Nuevo Protocolo Veterinario</h1>
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

        <form action="{{ route('vet.admissions.store') }}" method="POST">
            @csrf
            @include('components.branch-select', ['selectedBranchId' => old('lab_branch_id')])
            <div class="space-y-6">
                {{-- Veterinaria y Derivante --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-amber-700 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Veterinaria y Derivante
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Veterinaria *</label>
                            <select name="customer_id" x-model="customerId" @change="onCustomerChange()" required
                                    class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Seleccionar veterinaria...</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500" x-show="customerId && customerNbuRate() > 0" x-cloak>
                                Valor NBU de esta veterinaria: <span class="font-medium text-amber-700" x-text="'$' + customerNbuRate().toLocaleString('es-AR', { minimumFractionDigits: 2 })"></span>
                            </p>
                            <p class="mt-1 text-xs text-amber-700" x-show="customerId && customerNbuRate() === 0" x-cloak>
                                Configurá el valor NBU en Clientes → editar esta veterinaria.
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Veterinario Derivante</label>
                            <select name="veterinarian_id" x-model="veterinarianId"
                                    class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Sin derivante</option>
                                <template x-for="v in veterinarians" :key="v.id">
                                    <option :value="v.id" x-text="v.name + (v.matricula ? ' (Mat: ' + v.matricula + ')' : '')"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Datos del Animal --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-amber-700 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        Datos del Animal
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Animal *</label>
                            <input type="text" name="animal_name" value="{{ old('animal_name') }}" required
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" placeholder="Ej: Firulais">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Especie *</label>
                            <select name="species_id" required class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Seleccionar...</option>
                                @foreach($species as $sp)
                                    <option value="{{ $sp->id }}" {{ old('species_id') == $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Raza</label>
                            <input type="text" name="breed" value="{{ old('breed') }}"
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" placeholder="Ej: Labrador">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Edad</label>
                            <input type="text" name="age" value="{{ old('age') }}"
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" placeholder="Ej: 3 años">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Dueño *</label>
                            <input type="text" name="owner_name" value="{{ old('owner_name') }}" required
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono del Dueño</label>
                            <input type="text" name="owner_phone" value="{{ old('owner_phone') }}"
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email del Dueño</label>
                            <input type="email" name="owner_email" value="{{ old('owner_email') }}"
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                   placeholder="email@ejemplo.com">
                        </div>
                    </div>
                </div>

                {{-- Determinaciones --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-amber-700 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Determinaciones
                    </h2>
                    <p class="mb-4 text-sm text-gray-600">
                        Para buscar y agregar prácticas del <strong>nomenclador veterinario</strong>, primero elegí la <strong>veterinaria</strong> en la sección superior. Luego escribí acá el código o nombre (mínimo 2 caracteres).
                    </p>

                    @if(isset($vetProfiles) && $vetProfiles->isNotEmpty())
                    <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg" x-show="customerId" x-cloak>
                        <h3 class="text-sm font-semibold text-amber-900 mb-1">Aplicar perfiles guardados</h3>
                        <p class="text-xs text-amber-800 mb-3">Las determinaciones ya cargadas no se duplican. Los importes usan el NBU de la veterinaria elegida.</p>
                        <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-amber-900 mb-1">Perfiles (Ctrl/Cmd para varios)</label>
                                <select multiple size="3" class="w-full rounded-lg border-amber-200 text-sm"
                                        @change="profileIdsForApply = Array.from($event.target.selectedOptions).map(o => parseInt(o.value, 10))">
                                    @foreach($vetProfiles as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" @click="applyProfilesFromVet()"
                                    class="w-full sm:w-auto shrink-0 px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-gray-900 shadow-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 border border-gray-950">
                                Aplicar perfiles
                            </button>
                        </div>
                        @can('determination-profiles.manage')
                            <a href="{{ route('determination-profiles.index') }}" class="inline-block mt-2 text-xs text-amber-800 hover:underline">Gestionar perfiles</a>
                        @endcan
                        <template x-if="profileApplyMessage">
                            <div class="mt-3 text-sm p-3 rounded border"
                                 :class="profileApplyTone === 'warn' ? 'bg-amber-100 border-amber-300 text-amber-950' : 'bg-green-50 border-green-200 text-green-900'"
                                 x-text="profileApplyMessage"></div>
                        </template>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                            <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div class="md:col-span-2 relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Agregar determinación</label>
                            <input type="text" x-model="testSearch" x-ref="testSearchInput"
                                   @input="searchTests()"
                                   @focus="onTestSearchFocus()"
                                   @keydown.enter.prevent="selectFirstTest()"
                                   @keydown.escape="showTestDropdown = false; testSearch = ''"
                                   placeholder="Buscar por código o nombre... (Enter para agregar)"
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">

                            {{-- Dropdown de sugerencias --}}
                            <p x-show="searchError" x-cloak class="mt-1 text-sm text-red-600" x-text="searchError"></p>

                            <div x-show="showTestDropdown && filteredTests.length > 0"
                                 @click.away="showTestDropdown = false"
                                 class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                <template x-for="test in filteredTests" :key="test.id">
                                    <div @click="addTest(test)"
                                         class="px-4 py-3 hover:bg-amber-50 cursor-pointer border-b border-gray-100 last:border-0">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <span class="font-mono font-medium text-gray-900" x-text="test.code"></span>
                                                <span class="text-gray-600" x-text="' — ' + test.name"></span>
                                                <template x-if="test.parent_name">
                                                    <span class="text-xs text-gray-400 ml-1" x-text="'(hijo de ' + test.parent_name + ')'"></span>
                                                </template>
                                            </div>
                                            <span class="font-medium text-amber-600" x-text="'$' + parseFloat(test.price || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla de determinaciones seleccionadas --}}
                    <div x-show="testsData.length > 0" class="border rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-amber-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-amber-800 uppercase">Código</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-amber-800 uppercase">Nombre</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-amber-800 uppercase">Precio</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-amber-800 uppercase w-16">Quitar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(t, i) in testsData" :key="t.test_id">
                                    <tr>
                                        <td class="px-4 py-2 text-sm font-mono text-gray-600" x-text="t.code"></td>
                                        <td class="px-4 py-2 text-sm text-gray-900" x-text="t.name"></td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-700" x-text="'$' + parseFloat(t.price || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})"></td>
                                        <td class="px-4 py-2 text-center">
                                            <button type="button" @click="removeTest(i)"
                                                    class="text-red-500 hover:text-red-700" title="Quitar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div x-show="testsData.length === 0" class="text-center py-8 text-gray-400">
                        Buscá una determinación por código o nombre y presioná Enter para agregarla.
                    </div>

                    {{-- Hidden inputs --}}
                    <template x-for="(t, i) in testsData" :key="'hidden-'+t.test_id">
                        <div>
                            <input type="hidden" :name="'tests['+i+'][test_id]'" :value="t.test_id">
                            <input type="hidden" :name="'tests['+i+'][price]'" :value="t.price">
                        </div>
                    </template>

                    <div class="mt-4 flex justify-between items-center">
                        <span class="text-sm text-gray-600">
                            <span x-text="testsData.length"></span> determinación(es) seleccionada(s)
                        </span>
                        <span class="text-lg font-bold text-amber-700">
                            Total: $<span x-text="totalPrice.toLocaleString('es-AR', {minimumFractionDigits: 2})"></span>
                        </span>
                    </div>
                </div>

                {{-- Observaciones --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea name="observations" rows="3"
                              class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                              placeholder="Observaciones opcionales...">{{ old('observations') }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" :disabled="testsData.length === 0"
                            class="px-6 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        Crear Protocolo
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function vetAdmissionForm() {
            return {
                customerId: '{{ old("customer_id", "") }}',
                veterinarianId: '{{ old("veterinarian_id", "") }}',
                customerNbuValues: @json($customerNbuValues ?? []),
                searchTestsUrl: @json(route('vet.admissions.searchTests')),
                veterinarians: [],
                testSearch: '',
                filteredTests: [],
                showTestDropdown: false,
                testsData: [],
                totalPrice: 0,
                searchError: '',

                profileIdsForApply: [],
                profileApplyMessage: '',
                profileApplyTone: 'ok',
                previewProfilesUrl: @json(route('vet.determination-profiles.preview')),

                customerNbuRate() {
                    const id = this.customerId;
                    if (!id) return 0;
                    const v = this.customerNbuValues[id];
                    return parseFloat(v) || 0;
                },

                init() {
                    if (this.customerId) this.loadVeterinarians();
                },

                onCustomerChange() {
                    this.searchError = '';
                    this.loadVeterinarians();
                    this.recalculateTestPrices();
                },

                onTestSearchFocus() {
                    if (!this.customerId) {
                        this.searchError = 'Elegí primero la veterinaria para buscar prácticas.';
                        this.showTestDropdown = false;
                        return;
                    }
                    this.searchError = '';
                    if (this.filteredTests.length > 0) {
                        this.showTestDropdown = true;
                    }
                },

                recalculateTestPrices() {
                    const rate = this.customerNbuRate();
                    this.testsData = this.testsData.map((t) => ({
                        ...t,
                        price: Math.round(rate * (parseFloat(t.nbu) || 0) * 100) / 100,
                    }));
                    this.totalPrice = this.testsData.reduce((sum, t) => sum + t.price, 0);
                },

                async loadVeterinarians() {
                    this.veterinarians = [];
                    this.veterinarianId = '';
                    if (!this.customerId) return;
                    try {
                        const res = await fetch(`{{ url('vet/customer') }}/${this.customerId}/veterinarians`);
                        this.veterinarians = await res.json();
                    } catch (e) { console.error(e); }
                },

                async searchTests() {
                    if (!this.customerId) {
                        this.filteredTests = [];
                        this.showTestDropdown = false;
                        if (this.testSearch.length >= 2) {
                            this.searchError = 'Elegí primero la veterinaria para buscar prácticas.';
                        } else {
                            this.searchError = '';
                        }
                        return;
                    }
                    this.searchError = '';
                    if (this.testSearch.length < 2) {
                        this.filteredTests = [];
                        return;
                    }
                    try {
                        const url = `${this.searchTestsUrl}?q=${encodeURIComponent(this.testSearch)}&customer_id=${encodeURIComponent(this.customerId)}`;
                        const response = await fetch(url);
                        const data = await response.json();
                        if (!response.ok) {
                            this.filteredTests = [];
                            this.searchError = data.error || 'No se pudo buscar.';
                            return;
                        }
                        const tests = Array.isArray(data) ? data : [];
                        this.filteredTests = tests.filter(t => !this.testsData.find(td => td.test_id === t.id));
                        this.showTestDropdown = true;
                    } catch (error) {
                        console.error('Error buscando tests:', error);
                        this.searchError = 'Error de red al buscar.';
                    }
                },

                selectFirstTest() {
                    if (this.filteredTests.length > 0) {
                        this.addTest(this.filteredTests[0]);
                    }
                },

                addTest(test) {
                    if (this.testsData.find(t => t.test_id === test.id)) {
                        this.testSearch = '';
                        this.filteredTests = [];
                        this.showTestDropdown = false;
                        return;
                    }

                    this.testsData.push({
                        test_id: test.id,
                        code: test.code,
                        name: test.name,
                        nbu: parseFloat(test.nbu) || 0,
                        price: parseFloat(test.price) || 0,
                    });

                    this.testSearch = '';
                    this.filteredTests = [];
                    this.showTestDropdown = false;
                    this.totalPrice = this.testsData.reduce((sum, t) => sum + t.price, 0);

                    this.$nextTick(() => {
                        this.$refs.testSearchInput.focus();
                    });
                },

                removeTest(index) {
                    this.testsData.splice(index, 1);
                    this.totalPrice = this.testsData.reduce((sum, t) => sum + t.price, 0);
                },

                async applyProfilesFromVet() {
                    if (!this.customerId || !this.profileIdsForApply?.length) {
                        this.profileApplyTone = 'warn';
                        this.profileApplyMessage = 'Elegí veterinaria y al menos un perfil.';
                        return;
                    }
                    this.profileApplyMessage = '';
                    try {
                        const res = await fetch(this.previewProfilesUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                customer_id: parseInt(this.customerId, 10),
                                profile_ids: this.profileIdsForApply,
                                existing_test_ids: this.testsData.map(t => t.test_id),
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            throw new Error(data.message || data.error || 'Error al aplicar perfiles');
                        }
                        let added = 0;
                        const skipped = data.tests_skipped_duplicate_count || 0;
                        for (const row of data.vet_rows || []) {
                            if (this.testsData.find(t => t.test_id === row.test_id)) {
                                continue;
                            }
                            this.testsData.push({
                                test_id: row.test_id,
                                code: row.code,
                                name: row.name,
                                nbu: parseFloat(row.nbu) || 0,
                                price: parseFloat(row.price) || 0,
                            });
                            added++;
                        }
                        this.totalPrice = this.testsData.reduce((sum, t) => sum + t.price, 0);
                        if (added === 0 && skipped > 0) {
                            this.profileApplyTone = 'warn';
                            this.profileApplyMessage = 'Todas las prácticas de estos perfiles ya estaban cargadas (' + skipped + ' omitidas).';
                        } else {
                            this.profileApplyTone = 'ok';
                            this.profileApplyMessage = 'Se agregaron ' + added + ' determinaciones. ' + skipped + ' ya estaban en el pedido.';
                        }
                    } catch (e) {
                        this.profileApplyTone = 'warn';
                        this.profileApplyMessage = e.message || 'No se pudo aplicar.';
                    }
                },
            }
        }
    </script>
</x-lab-layout>
