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

        <form action="{{ route('customer.store') }}" method="POST">
            @csrf
            
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Información del Cliente</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                               placeholder="Nombre o razón social">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CUIT *</label>
                        <input type="text" name="taxId" value="{{ old('taxId') }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                               placeholder="XX-XXXXXXXX-X">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condición IVA</label>
                        <select name="tax" class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Seleccionar...</option>
                            <option value="Responsable Inscripto" {{ old('tax') == 'Responsable Inscripto' ? 'selected' : '' }}>Responsable Inscripto</option>
                            <option value="Monotributista" {{ old('tax') == 'Monotributista' ? 'selected' : '' }}>Monotributista</option>
                            <option value="Exento" {{ old('tax') == 'Exento' ? 'selected' : '' }}>Exento</option>
                            <option value="Consumidor Final" {{ old('tax') == 'Consumidor Final' ? 'selected' : '' }}>Consumidor Final</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                               placeholder="email@ejemplo.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                               placeholder="(XXX) XXX-XXXX">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código Postal</label>
                        <input type="text" name="postal" value="{{ old('postal') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                               placeholder="XXXX">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="address" value="{{ old('address') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                               placeholder="Calle, número, piso, depto...">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                        <input type="text" name="city" value="{{ old('city') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                               placeholder="Ciudad">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                        <select name="state" class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
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
                        <select name="country" class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="Argentina" selected>Argentina</option>
                            <option value="Uruguay">Uruguay</option>
                            <option value="Chile">Chile</option>
                            <option value="Brasil">Brasil</option>
                            <option value="Paraguay">Paraguay</option>
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
                    Crear Cliente
                </button>
            </div>
        </form>
    </div>
</x-lab-layout>
