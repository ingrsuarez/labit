<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('customer.index') }}" class="text-teal-600 hover:text-teal-800 text-sm flex items-center mb-2">
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

        <form action="{{ route('customer.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Información del Cliente</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                        <input type="text" name="name" value="{{ old('name', $customer->name) }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CUIT *</label>
                        <input type="text" name="taxId" value="{{ old('taxId', $customer->taxId) }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condición IVA</label>
                        <select name="tax" class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Seleccionar...</option>
                            <option value="Responsable Inscripto" {{ old('tax', $customer->tax) == 'Responsable Inscripto' ? 'selected' : '' }}>Responsable Inscripto</option>
                            <option value="Monotributista" {{ old('tax', $customer->tax) == 'Monotributista' ? 'selected' : '' }}>Monotributista</option>
                            <option value="Exento" {{ old('tax', $customer->tax) == 'Exento' ? 'selected' : '' }}>Exento</option>
                            <option value="Consumidor Final" {{ old('tax', $customer->tax) == 'Consumidor Final' ? 'selected' : '' }}>Consumidor Final</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código Postal</label>
                        <input type="text" name="postal" value="{{ old('postal', $customer->postal) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="address" value="{{ old('address', $customer->address) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                        <input type="text" name="city" value="{{ old('city', $customer->city) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                        <select name="state" class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Seleccionar...</option>
                            @foreach(['Buenos Aires', 'CABA', 'Catamarca', 'Chaco', 'Chubut', 'Córdoba', 'Corrientes', 'Entre Ríos', 'Formosa', 'Jujuy', 'La Pampa', 'La Rioja', 'Mendoza', 'Misiones', 'Neuquén', 'Río Negro', 'Salta', 'San Juan', 'San Luis', 'Santa Cruz', 'Santa Fe', 'Santiago del Estero', 'Tierra del Fuego', 'Tucumán'] as $state)
                                <option value="{{ $state }}" {{ old('state', $customer->state) == $state ? 'selected' : '' }}>{{ $state }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <select name="country" class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="Argentina" {{ old('country', $customer->country) == 'Argentina' ? 'selected' : '' }}>Argentina</option>
                            <option value="Uruguay" {{ old('country', $customer->country) == 'Uruguay' ? 'selected' : '' }}>Uruguay</option>
                            <option value="Chile" {{ old('country', $customer->country) == 'Chile' ? 'selected' : '' }}>Chile</option>
                            <option value="Brasil" {{ old('country', $customer->country) == 'Brasil' ? 'selected' : '' }}>Brasil</option>
                            <option value="Paraguay" {{ old('country', $customer->country) == 'Paraguay' ? 'selected' : '' }}>Paraguay</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                        <select name="status" required class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="activo" {{ old('status', $customer->status) == 'activo' ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('status', $customer->status) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
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
                        class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</x-lab-layout>
