<x-admin-layout>
    <div class="py-6 px-4 md:px-6" x-data="{
        cuit: '{{ old('taxId', '') }}',
        loading: false,
        afipVerified: false,
        afipError: null,
        cuitStatus: null,
        afipActivity: null,
        ivaLocked: false,
        ivaManuallyUnlocked: false,

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
            this.cuitStatus = null;
            this.afipActivity = null;

            try {
                const response = await fetch(`/customer/consultar-cuit/${this.cleanCuit}`);
                const data = await response.json();

                if (data.success) {
                    this.afipVerified = true;
                    this.cuitStatus = data.estado_cuit;
                    this.afipActivity = data.actividad_principal;

                    // Autocompletar campos
                    const nameInput = document.querySelector('input[name=name]');
                    if (nameInput && data.razon_social) nameInput.value = data.razon_social;

                    const addressInput = document.querySelector('input[name=address]');
                    if (addressInput && data.domicilio.direccion) addressInput.value = data.domicilio.direccion;

                    const cityInput = document.querySelector('input[name=city]');
                    if (cityInput && data.domicilio.localidad) cityInput.value = data.domicilio.localidad;

                    const postalInput = document.querySelector('input[name=postal]');
                    if (postalInput && data.domicilio.cod_postal) postalInput.value = data.domicilio.cod_postal;

                    // Provincia
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

                    // Condición IVA
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

                    // Hidden inputs
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
            <h1 class="text-2xl font-bold text-gray-800">Nuevo Cliente</h1>
            <p class="text-gray-600 mt-1">Registrar un nuevo cliente</p>
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

        <form action="{{ route('customer.store') }}" method="POST">
            @csrf
            <input type="hidden" name="afip_activity" value="{{ old('afip_activity', '') }}">
            <input type="hidden" name="cuit_status" value="{{ old('cuit_status', '') }}">
            <input type="hidden" name="afip_verified_at" value="{{ old('afip_verified_at', '') }}">

            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Información del Cliente</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Nombre o razón social">
                    </div>

                    <!-- CUIT con botón Consultar AFIP -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CUIT *</label>
                        <div class="flex gap-2">
                            <input type="text" name="taxId" x-model="cuit" required
                                   class="flex-1 rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500"
                                   placeholder="XX-XXXXXXXX-X">
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
                                <span x-text="loading ? 'Consultando...' : 'Consultar AFIP'"></span>
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
                            <option value="Responsable Inscripto" {{ old('tax') == 'Responsable Inscripto' ? 'selected' : '' }}>Responsable Inscripto</option>
                            <option value="Monotributista" {{ old('tax') == 'Monotributista' ? 'selected' : '' }}>Monotributista</option>
                            <option value="Exento" {{ old('tax') == 'Exento' ? 'selected' : '' }}>Exento</option>
                            <option value="Consumidor Final" {{ old('tax') == 'Consumidor Final' ? 'selected' : '' }}>Consumidor Final</option>
                        </select>
                        <!-- Hidden para enviar el valor cuando disabled -->
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
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="email@ejemplo.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="(XXX) XXX-XXXX">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código Postal</label>
                        <input type="text" name="postal" value="{{ old('postal') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="XXXX">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="address" value="{{ old('address') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Calle, número, piso, depto...">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                        <input type="text" name="city" value="{{ old('city') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Ciudad">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                        <select name="state" class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            <option value="Buenos Aires" {{ old('state') == 'Buenos Aires' ? 'selected' : '' }}>Buenos Aires</option>
                            <option value="CABA" {{ old('state') == 'CABA' ? 'selected' : '' }}>Ciudad Autónoma de Buenos Aires</option>
                            <option value="Catamarca" {{ old('state') == 'Catamarca' ? 'selected' : '' }}>Catamarca</option>
                            <option value="Chaco" {{ old('state') == 'Chaco' ? 'selected' : '' }}>Chaco</option>
                            <option value="Chubut" {{ old('state') == 'Chubut' ? 'selected' : '' }}>Chubut</option>
                            <option value="Córdoba" {{ old('state') == 'Córdoba' ? 'selected' : '' }}>Córdoba</option>
                            <option value="Corrientes" {{ old('state') == 'Corrientes' ? 'selected' : '' }}>Corrientes</option>
                            <option value="Entre Ríos" {{ old('state') == 'Entre Ríos' ? 'selected' : '' }}>Entre Ríos</option>
                            <option value="Formosa" {{ old('state') == 'Formosa' ? 'selected' : '' }}>Formosa</option>
                            <option value="Jujuy" {{ old('state') == 'Jujuy' ? 'selected' : '' }}>Jujuy</option>
                            <option value="La Pampa" {{ old('state') == 'La Pampa' ? 'selected' : '' }}>La Pampa</option>
                            <option value="La Rioja" {{ old('state') == 'La Rioja' ? 'selected' : '' }}>La Rioja</option>
                            <option value="Mendoza" {{ old('state') == 'Mendoza' ? 'selected' : '' }}>Mendoza</option>
                            <option value="Misiones" {{ old('state') == 'Misiones' ? 'selected' : '' }}>Misiones</option>
                            <option value="Neuquén" {{ old('state') == 'Neuquén' ? 'selected' : '' }}>Neuquén</option>
                            <option value="Río Negro" {{ old('state') == 'Río Negro' ? 'selected' : '' }}>Río Negro</option>
                            <option value="Salta" {{ old('state') == 'Salta' ? 'selected' : '' }}>Salta</option>
                            <option value="San Juan" {{ old('state') == 'San Juan' ? 'selected' : '' }}>San Juan</option>
                            <option value="San Luis" {{ old('state') == 'San Luis' ? 'selected' : '' }}>San Luis</option>
                            <option value="Santa Cruz" {{ old('state') == 'Santa Cruz' ? 'selected' : '' }}>Santa Cruz</option>
                            <option value="Santa Fe" {{ old('state') == 'Santa Fe' ? 'selected' : '' }}>Santa Fe</option>
                            <option value="Santiago del Estero" {{ old('state') == 'Santiago del Estero' ? 'selected' : '' }}>Santiago del Estero</option>
                            <option value="Tierra del Fuego" {{ old('state') == 'Tierra del Fuego' ? 'selected' : '' }}>Tierra del Fuego</option>
                            <option value="Tucumán" {{ old('state') == 'Tucumán' ? 'selected' : '' }}>Tucumán</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <select name="country" class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="Argentina" selected>Argentina</option>
                            <option value="Uruguay">Uruguay</option>
                            <option value="Chile">Chile</option>
                            <option value="Brasil">Brasil</option>
                            <option value="Paraguay">Paraguay</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descuento (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" max="100" name="discount_percent"
                                   value="{{ old('discount_percent', 0) }}"
                                   class="w-full rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">%</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Descuento general aplicado al precio de cada determinación</p>
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
                    Crear Cliente
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
