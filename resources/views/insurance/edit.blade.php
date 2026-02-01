<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('insurance.index') }}" class="text-blue-600 hover:text-blue-800 text-sm flex items-center mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver a Coberturas
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Editar Cobertura</h1>
            <p class="text-gray-600 mt-1">Modifica los datos de <span class="font-semibold uppercase">{{ $insurance->name }}</span></p>
        </div>

        <!-- Errores -->
        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('insurance.update', $insurance) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Datos Principales -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Datos Principales
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Tipo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cobertura *</label>
                        <select name="type" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="particular" {{ old('type', $insurance->type) == 'particular' ? 'selected' : '' }}>Particular (sin cobertura)</option>
                            <option value="obra_social" {{ old('type', $insurance->type) == 'obra_social' ? 'selected' : '' }}>Obra Social</option>
                            <option value="prepaga" {{ old('type', $insurance->type) == 'prepaga' ? 'selected' : '' }}>Prepaga</option>
                        </select>
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="name" required 
                               value="{{ old('name', $insurance->name) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- CUIT -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CUIT</label>
                        <input type="text" name="tax_id" 
                               value="{{ old('tax_id', $insurance->tax_id) }}"
                               placeholder="XX-XXXXXXXX-X"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- IVA -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condicion IVA</label>
                        <select name="tax" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="inscripto" {{ old('tax', $insurance->tax) == 'inscripto' ? 'selected' : '' }}>Inscripto</option>
                            <option value="exento" {{ old('tax', $insurance->tax) == 'exento' ? 'selected' : '' }}>Exento</option>
                        </select>
                    </div>

                    <!-- Grupo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Grupo</label>
                        <select name="group" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Ninguno</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}" {{ old('group', $insurance->group) == $group->id ? 'selected' : '' }}>
                                    {{ strtoupper($group->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Contacto -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Informacion de Contacto
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" 
                               value="{{ old('email', $insurance->email) }}"
                               placeholder="contacto@ejemplo.com"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Telefono -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                        <input type="text" name="phone" 
                               value="{{ old('phone', $insurance->phone) }}"
                               placeholder="11 1234-5678"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Direccion -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Direccion</label>
                        <input type="text" name="address" 
                               value="{{ old('address', $insurance->address) }}"
                               placeholder="Calle, numero, ciudad"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Pais -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pais</label>
                        <select name="country" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="argentina" {{ old('country', $insurance->country) == 'argentina' ? 'selected' : '' }}>Argentina</option>
                            <option value="brasil" {{ old('country', $insurance->country) == 'brasil' ? 'selected' : '' }}>Brasil</option>
                            <option value="uruguay" {{ old('country', $insurance->country) == 'uruguay' ? 'selected' : '' }}>Uruguay</option>
                            <option value="chile" {{ old('country', $insurance->country) == 'chile' ? 'selected' : '' }}>Chile</option>
                        </select>
                    </div>

                    <!-- Provincia -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                        <select name="state" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            @php
                                $provincias = ['Buenos Aires', 'Ciudad Autonoma de Bs As', 'Catamarca', 'Chaco', 'Chubut', 'Cordoba', 'Corrientes', 'Entre Rios', 'Formosa', 'Jujuy', 'La Rioja', 'Mendoza', 'Misiones', 'Neuquen', 'Rio Negro', 'Salta', 'San Juan', 'San Luis', 'Santa Cruz', 'Santa Fe', 'Santiago del Estero', 'Tierra del Fuego', 'Tucuman'];
                            @endphp
                            @foreach($provincias as $provincia)
                                <option value="{{ $provincia }}" {{ old('state', $insurance->state) == $provincia ? 'selected' : '' }}>{{ $provincia }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Facturacion -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Facturacion y Precios
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Valor NBU -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor NBU</label>
                        <input type="number" step="0.01" name="nbu_value" 
                               value="{{ old('nbu_value', $insurance->nbu_value) }}"
                               placeholder="0.00"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Valor por unidad de nomenclador bioquimico</p>
                    </div>

                    <!-- NBU Base -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NBU Base</label>
                        <input type="number" step="0.01" name="nbu" 
                               value="{{ old('nbu', $insurance->nbu) }}"
                               placeholder="0.00"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Coseguro -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Coseguro por defecto</label>
                        <input type="number" step="0.01" name="price" 
                               value="{{ old('price', $insurance->price) }}"
                               placeholder="0.00"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Observaciones
                </h2>

                <div>
                    <textarea name="instructions" rows="3" 
                              placeholder="Notas importantes sobre la cobertura, requisitos especiales, etc."
                              class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">{{ old('instructions', $insurance->instructions) }}</textarea>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('insurance.index') }}" 
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
</x-lab-layout>
