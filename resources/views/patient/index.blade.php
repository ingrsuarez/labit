<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Gestión de Pacientes</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Nuevo Paciente</h1>
            <p class="text-gray-600 mt-1">Complete los datos para registrar un nuevo paciente en el sistema</p>
        </div>

        <!-- Errores -->
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

        <form action="{{ route('patient.store') }}" method="POST">
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
                    <!-- Nombre -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="name" id="name" autocomplete="off" required autofocus
                               value="{{ old('name') }}"
                               placeholder="Ingrese el nombre"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Apellido -->
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Apellido *</label>
                        <input type="text" name="last_name" id="last_name" autocomplete="off" required
                               value="{{ old('last_name') }}"
                               placeholder="Ingrese el apellido"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- DNI -->
                    <div>
                        <label for="id" class="block text-sm font-medium text-gray-700 mb-1">DNI *</label>
                        <input type="text" name="id" id="id" autocomplete="off" required
                               value="{{ old('id') }}"
                               placeholder="Ej: 12345678"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Fecha de Nacimiento -->
                    <div>
                        <label for="birth" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento *</label>
                        <input type="date" name="birth" id="birth" autocomplete="off" required
                               value="{{ old('birth', '2000-01-01') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Sexo -->
                    <div>
                        <label for="sex" class="block text-sm font-medium text-gray-700 mb-1">Sexo *</label>
                        <select id="sex" name="sex" autocomplete="off" required
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="m" {{ old('sex') == 'm' ? 'selected' : '' }}>Masculino</option>
                            <option value="f" {{ old('sex') == 'f' ? 'selected' : '' }}>Femenino</option>
                        </select>
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono *</label>
                        <input type="text" name="phone" id="phone" autocomplete="off" required
                               value="{{ old('phone') }}"
                               placeholder="Ej: 11 1234-5678"
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
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="email" autocomplete="off"
                               value="{{ old('email') }}"
                               placeholder="ejemplo@correo.com"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Domicilio -->
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Domicilio</label>
                        <input type="text" name="address" id="address" autocomplete="off"
                               value="{{ old('address') }}"
                               placeholder="Calle, número, piso, dpto."
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- País -->
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <select id="country" name="country" autocomplete="off"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="argentina" {{ old('country', 'argentina') == 'argentina' ? 'selected' : '' }}>Argentina</option>
                            <option value="brasil" {{ old('country') == 'brasil' ? 'selected' : '' }}>Brasil</option>
                            <option value="uruguay" {{ old('country') == 'uruguay' ? 'selected' : '' }}>Uruguay</option>
                            <option value="chile" {{ old('country') == 'chile' ? 'selected' : '' }}>Chile</option>
                        </select>
                    </div>

                    <!-- Provincia -->
                    <div>
                        <label for="state" class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                        <select id="state" name="state" autocomplete="off"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="Buenos Aires" {{ old('state', 'Buenos Aires') == 'Buenos Aires' ? 'selected' : '' }}>Buenos Aires</option>
                            <option value="Ciudad Autonoma de Bs As" {{ old('state') == 'Ciudad Autonoma de Bs As' ? 'selected' : '' }}>Ciudad Autónoma de Bs As</option>
                            <option value="Catamarca" {{ old('state') == 'Catamarca' ? 'selected' : '' }}>Catamarca</option>
                            <option value="Chaco" {{ old('state') == 'Chaco' ? 'selected' : '' }}>Chaco</option>
                            <option value="Chubut" {{ old('state') == 'Chubut' ? 'selected' : '' }}>Chubut</option>
                            <option value="Cordoba" {{ old('state') == 'Cordoba' ? 'selected' : '' }}>Córdoba</option>
                            <option value="Corrientes" {{ old('state') == 'Corrientes' ? 'selected' : '' }}>Corrientes</option>
                            <option value="Entre Ríos" {{ old('state') == 'Entre Ríos' ? 'selected' : '' }}>Entre Ríos</option>
                            <option value="Formosa" {{ old('state') == 'Formosa' ? 'selected' : '' }}>Formosa</option>
                            <option value="Jujuy" {{ old('state') == 'Jujuy' ? 'selected' : '' }}>Jujuy</option>
                            <option value="La Rioja" {{ old('state') == 'La Rioja' ? 'selected' : '' }}>La Rioja</option>
                            <option value="Mendoza" {{ old('state') == 'Mendoza' ? 'selected' : '' }}>Mendoza</option>
                            <option value="Misiones" {{ old('state') == 'Misiones' ? 'selected' : '' }}>Misiones</option>
                            <option value="Neuquen" {{ old('state') == 'Neuquen' ? 'selected' : '' }}>Neuquén</option>
                            <option value="Rio Negro" {{ old('state') == 'Rio Negro' ? 'selected' : '' }}>Río Negro</option>
                            <option value="Salta" {{ old('state') == 'Salta' ? 'selected' : '' }}>Salta</option>
                            <option value="San Juan" {{ old('state') == 'San Juan' ? 'selected' : '' }}>San Juan</option>
                            <option value="San Luis" {{ old('state') == 'San Luis' ? 'selected' : '' }}>San Luis</option>
                            <option value="Santa Cruz" {{ old('state') == 'Santa Cruz' ? 'selected' : '' }}>Santa Cruz</option>
                            <option value="Santa Fe" {{ old('state') == 'Santa Fe' ? 'selected' : '' }}>Santa Fe</option>
                            <option value="Santiago del Estero" {{ old('state') == 'Santiago del Estero' ? 'selected' : '' }}>Santiago del Estero</option>
                            <option value="Tierra del Fuego" {{ old('state') == 'Tierra del Fuego' ? 'selected' : '' }}>Tierra del Fuego</option>
                            <option value="Tucuman" {{ old('state') == 'Tucuman' ? 'selected' : '' }}>Tucumán</option>
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
                    <!-- Obra Social -->
                    <div>
                        <label for="insurance" class="block text-sm font-medium text-gray-700 mb-1">Obra Social / Prepaga</label>
                        <select id="insurance" name="insurance" autocomplete="off"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccionar cobertura...</option>
                            @foreach ($insurances as $insurance)
                                <option value="{{ $insurance->id }}" {{ old('insurance') == $insurance->id ? 'selected' : '' }}>
                                    {{ strtoupper($insurance->name) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Seleccione la cobertura médica del paciente</p>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('admission.index') }}" 
                   class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Guardar Paciente
                </button>
            </div>
        </form>
    </div>
</x-lab-layout>