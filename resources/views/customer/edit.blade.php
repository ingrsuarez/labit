<x-admin-layout>
    <div class="py-6 px-4 md:px-6" x-data="{
        cuit: '{{ old('taxId', $customer->taxId) }}',
        loading: false,
        afipVerified: {{ $customer->isAfipVerified() ? 'true' : 'false' }},
        afipError: null,
        cuitStatus: '{{ $customer->cuit_status ?? '' }}',
        afipActivity: '{{ $customer->afip_activity ?? '' }}',
        ivaLocked: {{ $customer->isAfipVerified() ? 'true' : 'false' }},
        ivaManuallyUnlocked: false,
        hasVetType: {{ in_array('veterinario', $customer->type ?? []) ? 'true' : 'false' }},
        vets: [],
        vetLoading: false,
        vetForm: { name: '', phone: '', email: '', matricula: '' },
        vetEditId: null,
        vetShowForm: false,

        get cleanCuit() {
            return this.cuit.replace(/\D/g, '');
        },

        get canConsult() {
            return this.cleanCuit.length === 11 && !this.loading;
        },

        async consultarAfip() {
            if (!this.canConsult) return;

            this.loading = true;
            this.afipError = null;

            try {
                const response = await fetch(`/customer/consultar-cuit/${this.cleanCuit}`);
                const data = await response.json();

                if (data.success) {
                    this.afipVerified = true;
                    this.cuitStatus = data.estado_cuit;
                    this.afipActivity = data.actividad_principal;

                    const nameInput = document.querySelector('input[name=name]');
                    if (nameInput && data.razon_social) nameInput.value = data.razon_social;

                    const addressInput = document.querySelector('input[name=address]');
                    if (addressInput && data.domicilio.direccion) addressInput.value = data.domicilio.direccion;

                    const cityInput = document.querySelector('input[name=city]');
                    if (cityInput && data.domicilio.localidad) cityInput.value = data.domicilio.localidad;

                    const postalInput = document.querySelector('input[name=postal]');
                    if (postalInput && data.domicilio.cod_postal) postalInput.value = data.domicilio.cod_postal;

                    const stateSelect = document.querySelector('select[name=state]');
                    if (stateSelect && data.domicilio.provincia) {
                        const options = stateSelect.options;
                        for (let i = 0; i < options.length; i++) {
                            if (options[i].value === data.domicilio.provincia) {
                                stateSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }

                    const taxSelect = document.querySelector('select[name=tax]');
                    if (taxSelect && data.condicion_iva) {
                        const options = taxSelect.options;
                        for (let i = 0; i < options.length; i++) {
                            if (options[i].value === data.condicion_iva) {
                                taxSelect.selectedIndex = i;
                                break;
                            }
                        }
                        this.ivaLocked = true;
                        this.ivaManuallyUnlocked = false;
                    }

                    document.querySelector('input[name=afip_activity]').value = data.actividad_principal || '';
                    document.querySelector('input[name=cuit_status]').value = data.estado_cuit || '';
                    document.querySelector('input[name=afip_verified_at]').value = new Date().toISOString().slice(0, 19).replace('T', ' ');
                } else {
                    this.afipError = data.error || 'No se pudo consultar AFIP.';
                }
            } catch (e) {
                this.afipError = 'No se pudo conectar con AFIP. Podés cargar los datos manualmente.';
            } finally {
                this.loading = false;
            }
        },

        unlockIva() {
            this.ivaLocked = false;
            this.ivaManuallyUnlocked = true;
        },

        updateVetType() {
            const checkboxes = document.querySelectorAll('input[name=\'type[]\']');
            this.hasVetType = false;
            checkboxes.forEach(cb => {
                if (cb.value === 'veterinario' && cb.checked) this.hasVetType = true;
            });
            if (this.hasVetType && this.vets.length === 0) this.loadVets();
        },

        async loadVets() {
            this.vetLoading = true;
            try {
                const res = await fetch(`/labit/public/customer/{{ $customer->id }}/veterinarians`);
                this.vets = await res.json();
            } catch (e) { console.error(e); }
            this.vetLoading = false;
        },

        resetVetForm() {
            this.vetForm = { name: '', phone: '', email: '', matricula: '' };
            this.vetEditId = null;
            this.vetShowForm = false;
        },

        editVet(vet) {
            this.vetForm = { name: vet.name, phone: vet.phone, email: vet.email || '', matricula: vet.matricula || '' };
            this.vetEditId = vet.id;
            this.vetShowForm = true;
        },

        async saveVet() {
            const url = this.vetEditId
                ? `/labit/public/customer/{{ $customer->id }}/veterinarians/${this.vetEditId}`
                : `/labit/public/customer/{{ $customer->id }}/veterinarians`;
            const method = this.vetEditId ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(this.vetForm)
                });
                if (res.ok) { this.resetVetForm(); this.loadVets(); }
            } catch (e) { console.error(e); }
        },

        async deleteVet(id) {
            if (!confirm('¿Eliminar este veterinario?')) return;
            try {
                await fetch(`/labit/public/customer/{{ $customer->id }}/veterinarians/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });
                this.loadVets();
            } catch (e) { console.error(e); }
        },

        init() {
            if (this.hasVetType) this.loadVets();
        }
    }">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('customer.index') }}" class="text-zinc-600 hover:text-zinc-800 text-sm flex items-center mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver a Clientes
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Editar Cliente</h1>
            <p class="text-gray-600 mt-1">{{ $customer->name }}</p>
        </div>

        <!-- Errores -->
        @if($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Error AFIP -->
        <div x-show="afipError" x-cloak class="mb-4 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-r-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span x-text="afipError"></span>
            </div>
        </div>

        <form action="{{ route('customer.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="afip_activity" value="{{ old('afip_activity', $customer->afip_activity ?? '') }}">
            <input type="hidden" name="cuit_status" value="{{ old('cuit_status', $customer->cuit_status ?? '') }}">
            <input type="hidden" name="afip_verified_at" value="{{ old('afip_verified_at', $customer->afip_verified_at ? $customer->afip_verified_at->format('Y-m-d H:i:s') : '') }}">

            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Información del Cliente</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                        <input type="text" name="name" value="{{ old('name', $customer->name) }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                    </div>

                    <!-- CUIT con botón Consultar AFIP -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CUIT *</label>
                        <div class="flex gap-2">
                            <input type="text" name="taxId" x-model="cuit" required
                                   class="flex-1 rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                            <button type="button" @click="consultarAfip()"
                                    :disabled="!canConsult"
                                    class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap transition-colors">
                                <template x-if="loading">
                                    <svg class="animate-spin h-4 w-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </template>
                                <template x-if="!loading">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </template>
                                <span x-text="loading ? 'Consultando...' : (afipVerified ? 'Re-consultar AFIP' : 'Consultar AFIP')"></span>
                            </button>
                        </div>

                        <!-- Badge estado CUIT -->
                        <div x-show="cuitStatus" x-cloak class="mt-2">
                            <span x-show="cuitStatus === 'ACTIVO'"
                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                CUIT Activo
                            </span>
                            <span x-show="cuitStatus && cuitStatus !== 'ACTIVO'"
                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                CUIT <span x-text="cuitStatus"></span>
                            </span>
                        </div>
                        <p x-show="afipActivity" x-cloak class="text-xs text-gray-500 mt-1" x-text="afipActivity"></p>
                    </div>

                    <!-- Condición IVA con lock -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Condición IVA
                            <span x-show="ivaLocked && !ivaManuallyUnlocked" x-cloak
                                  class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                                Verificado por AFIP
                            </span>
                            <span x-show="ivaManuallyUnlocked" x-cloak
                                  class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200">
                                Editado manualmente
                            </span>
                        </label>
                        <select name="tax" :disabled="ivaLocked"
                                class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500"
                                :class="{ 'bg-gray-100': ivaLocked }">
                            <option value="">Seleccionar...</option>
                            <option value="Responsable Inscripto" {{ old('tax', $customer->tax) == 'Responsable Inscripto' ? 'selected' : '' }}>Responsable Inscripto</option>
                            <option value="Monotributista" {{ old('tax', $customer->tax) == 'Monotributista' ? 'selected' : '' }}>Monotributista</option>
                            <option value="Exento" {{ old('tax', $customer->tax) == 'Exento' ? 'selected' : '' }}>Exento</option>
                            <option value="Consumidor Final" {{ old('tax', $customer->tax) == 'Consumidor Final' ? 'selected' : '' }}>Consumidor Final</option>
                        </select>
                        <template x-if="ivaLocked">
                            <input type="hidden" name="tax" :value="document.querySelector('select[name=tax]')?.value">
                        </template>
                        <p x-show="ivaLocked" x-cloak class="text-xs text-gray-500 mt-1">
                            Dato verificado por AFIP.
                            <a href="#" @click.prevent="unlockIva()" class="text-indigo-600 underline">Desbloquear edición</a>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código Postal</label>
                        <input type="text" name="postal" value="{{ old('postal', $customer->postal) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="address" value="{{ old('address', $customer->address) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                        <input type="text" name="city" value="{{ old('city', $customer->city) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                        <select name="state" class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach(['Buenos Aires', 'CABA', 'Catamarca', 'Chaco', 'Chubut', 'Córdoba', 'Corrientes', 'Entre Ríos', 'Formosa', 'Jujuy', 'La Pampa', 'La Rioja', 'Mendoza', 'Misiones', 'Neuquén', 'Río Negro', 'Salta', 'San Juan', 'San Luis', 'Santa Cruz', 'Santa Fe', 'Santiago del Estero', 'Tierra del Fuego', 'Tucumán'] as $state)
                                <option value="{{ $state }}" {{ old('state', $customer->state) == $state ? 'selected' : '' }}>{{ $state }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <select name="country" class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="Argentina" {{ old('country', $customer->country) == 'Argentina' ? 'selected' : '' }}>Argentina</option>
                            <option value="Uruguay" {{ old('country', $customer->country) == 'Uruguay' ? 'selected' : '' }}>Uruguay</option>
                            <option value="Chile" {{ old('country', $customer->country) == 'Chile' ? 'selected' : '' }}>Chile</option>
                            <option value="Brasil" {{ old('country', $customer->country) == 'Brasil' ? 'selected' : '' }}>Brasil</option>
                            <option value="Paraguay" {{ old('country', $customer->country) == 'Paraguay' ? 'selected' : '' }}>Paraguay</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                        <select name="status" required class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="activo" {{ old('status', $customer->status) == 'activo' ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('status', $customer->status) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descuento (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" max="100" name="discount_percent"
                                   value="{{ old('discount_percent', $customer->discount_percent ?? 0) }}"
                                   class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">%</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Descuento general aplicado al precio de cada determinación</p>
                    </div>

                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Cliente</label>
                        <div class="flex flex-wrap gap-4">
                            @foreach(['obra_social' => 'Obra Social', 'aguas' => 'Aguas y Alimentos', 'veterinario' => 'Veterinario', 'clinico' => 'Clínico', 'particular' => 'Particular', 'laborales' => 'Laborales'] as $key => $label)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="type[]" value="{{ $key }}"
                                           class="rounded border-gray-300 text-zinc-600 focus:ring-zinc-500"
                                           @change="updateVetType()"
                                           {{ in_array($key, old('type', $customer->type ?? ['aguas'])) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Seleccionar uno o más tipos</p>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('customer.index') }}"
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-zinc-700 text-white rounded-lg hover:bg-zinc-800">
                    Guardar Cambios
                </button>
            </div>
        </form>

        <!-- Sección Veterinarios (solo si tipo incluye veterinario) -->
        <div x-show="hasVetType" x-cloak class="bg-white rounded-lg shadow-sm p-6 mt-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b">
                <h2 class="text-lg font-semibold text-gray-800">Veterinarios Asociados</h2>
                <button type="button" @click="vetShowForm = true; resetVetForm(); vetShowForm = true;"
                        class="inline-flex items-center px-3 py-1.5 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Agregar Veterinario
                </button>
            </div>

            <!-- Formulario inline -->
            <div x-show="vetShowForm" x-cloak class="bg-gray-50 rounded-lg p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" x-model="vetForm.name" class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 text-sm" placeholder="Nombre completo">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono *</label>
                        <input type="text" x-model="vetForm.phone" class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 text-sm" placeholder="(XXX) XXX-XXXX">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" x-model="vetForm.email" class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 text-sm" placeholder="email@ejemplo.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Matrícula</label>
                        <input type="text" x-model="vetForm.matricula" class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 text-sm" placeholder="MP-XXXX">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-3">
                    <button type="button" @click="resetVetForm()" class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100">Cancelar</button>
                    <button type="button" @click="saveVet()" class="px-3 py-1.5 text-sm bg-amber-600 text-white rounded-lg hover:bg-amber-700" x-text="vetEditId ? 'Guardar' : 'Agregar'"></button>
                </div>
            </div>

            <!-- Tabla veterinarios -->
            <div x-show="vetLoading" class="text-center py-4 text-gray-500 text-sm">Cargando...</div>
            <table x-show="vets.length > 0 && !vetLoading" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Matrícula</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="vet in vets" :key="vet.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="vet.name"></td>
                            <td class="px-4 py-3 text-sm text-gray-500" x-text="vet.phone"></td>
                            <td class="px-4 py-3 text-sm text-gray-500" x-text="vet.email || '-'"></td>
                            <td class="px-4 py-3 text-sm text-gray-500" x-text="vet.matricula || '-'"></td>
                            <td class="px-4 py-3 text-right text-sm">
                                <button type="button" @click="editVet(vet)" class="text-zinc-600 hover:text-zinc-900 mr-2">Editar</button>
                                <button type="button" @click="deleteVet(vet.id)" class="text-red-600 hover:text-red-900">Eliminar</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <p x-show="vets.length === 0 && !vetLoading" class="text-sm text-gray-500 text-center py-4">No hay veterinarios registrados para este cliente.</p>
        </div>
    </div>
</x-admin-layout>
