<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('sample.index') }}" class="text-teal-600 hover:text-teal-800 text-sm flex items-center mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver a Protocolos
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Nueva Muestra</h1>
            <p class="text-gray-600 mt-1">Registrar una nueva muestra de agua o alimento</p>
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

        <form action="{{ route('sample.store') }}" method="POST" x-data="sampleForm()">
            @csrf
            
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Datos de la Muestra</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Tipo de Muestra -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Muestra *</label>
                        <select name="sample_type" x-model="sampleType" required
                                class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="agua">Agua</option>
                            <option value="alimento">Alimento</option>
                        </select>
                    </div>

                    <!-- Fecha de Ingreso -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Ingreso *</label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <!-- Fecha de Toma -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Toma de Muestra *</label>
                        <input type="date" name="sampling_date" value="{{ old('sampling_date', date('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <!-- Cliente -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                        <select name="customer_id" required
                                class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Seleccionar cliente...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->taxId }})
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('customer.create') }}" class="text-sm text-teal-600 hover:text-teal-800 mt-1 inline-block">
                            + Nuevo cliente
                        </a>
                    </div>

                    <!-- Lugar -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lugar de Toma *</label>
                        <input type="text" name="location" value="{{ old('location') }}" required
                               placeholder="Ej: Planta principal, Tanque 1..."
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <!-- Dirección -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="address" value="{{ old('address') }}"
                               placeholder="Dirección del lugar de toma"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <!-- Campos para Alimentos -->
                    <div x-show="sampleType === 'alimento'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto</label>
                        <input type="text" name="product_name" value="{{ old('product_name') }}"
                               placeholder="Nombre del alimento"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div x-show="sampleType === 'alimento'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lote</label>
                        <input type="text" name="batch" value="{{ old('batch') }}"
                               placeholder="Número de lote"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea name="observations" rows="3"
                              class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                              placeholder="Observaciones adicionales...">{{ old('observations') }}</textarea>
                </div>
            </div>

            <!-- Determinaciones -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Determinaciones a Realizar</h2>
                
                <!-- Buscador -->
                <div class="mb-4">
                    <input type="text" x-model="searchTest" 
                           placeholder="Buscar determinación por nombre o código..."
                           class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                </div>

                <!-- Lista de determinaciones disponibles -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-64 overflow-y-auto border rounded-lg p-3 mb-4">
                    @foreach($tests as $test)
                        <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer"
                               x-show="'{{ strtolower($test->name . ' ' . $test->code) }}'.includes(searchTest.toLowerCase())">
                            <input type="checkbox" name="determinations[]" value="{{ $test->id }}"
                                   class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                   {{ in_array($test->id, old('determinations', [])) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm">
                                <span class="font-medium text-gray-700">{{ $test->code }}</span>
                                <span class="text-gray-500">- {{ $test->name }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <p class="text-sm text-gray-500">
                    Seleccione al menos una determinación para el protocolo.
                </p>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('sample.index') }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                    Crear Protocolo
                </button>
            </div>
        </form>
    </div>

    <script>
        function sampleForm() {
            return {
                sampleType: '{{ old('sample_type', 'agua') }}',
                searchTest: ''
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-lab-layout>
