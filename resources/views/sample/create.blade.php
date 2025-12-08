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
                
                <!-- Buscador con autocompletado -->
                <div class="mb-4 relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar y agregar determinación</label>
                    <input type="text" 
                           x-model="searchTest" 
                           @keydown.tab.prevent="addFirstFiltered()"
                           @keydown.enter.prevent="addFirstFiltered()"
                           @input="showSuggestions = true"
                           @focus="showSuggestions = true"
                           @click.away="showSuggestions = false"
                           placeholder="Escriba código o nombre y presione Tab o Enter para agregar..."
                           class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                           autocomplete="off">
                    
                    <!-- Lista desplegable de sugerencias -->
                    <div x-show="showSuggestions && searchTest.length > 0 && filteredTests.length > 0" 
                         x-cloak
                         class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                        <template x-for="test in filteredTests" :key="test.id">
                            <div @click="addTest(test)" 
                                 class="px-4 py-2 hover:bg-teal-50 cursor-pointer flex justify-between items-center">
                                <span>
                                    <span class="font-medium text-teal-600" x-text="test.code"></span>
                                    <span class="text-gray-600" x-text="' - ' + test.name"></span>
                                </span>
                                <span class="text-xs text-gray-400">Tab/Enter para agregar</span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Tabla de determinaciones seleccionadas -->
                <div class="border rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Determinación</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(test, index) in selectedTests" :key="test.id">
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-teal-600" x-text="test.code"></td>
                                    <td class="px-4 py-3 text-sm text-gray-900" x-text="test.name"></td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" @click="removeTest(index)" 
                                                class="text-red-600 hover:text-red-800 text-sm">
                                            Eliminar
                                        </button>
                                    </td>
                                    <input type="hidden" name="determinations[]" :value="test.id">
                                </tr>
                            </template>
                            <tr x-show="selectedTests.length === 0">
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    Busque y agregue determinaciones usando el campo de arriba
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="text-sm text-gray-500 mt-3">
                    <span class="font-medium" x-text="selectedTests.length"></span> determinación(es) seleccionada(s).
                    Use <kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-xs">Tab</kbd> o 
                    <kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-xs">Enter</kbd> para agregar rápidamente.
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
                searchTest: '',
                showSuggestions: false,
                selectedTests: [],
                allTests: @json($tests->map(fn($t) => ['id' => $t->id, 'code' => $t->code, 'name' => $t->name])),
                
                get filteredTests() {
                    if (!this.searchTest || this.searchTest.length < 1) return [];
                    const search = this.searchTest.toLowerCase();
                    return this.allTests.filter(test => {
                        const matchesSearch = test.code.toLowerCase().includes(search) || 
                                             test.name.toLowerCase().includes(search);
                        const notSelected = !this.selectedTests.find(s => s.id === test.id);
                        return matchesSearch && notSelected;
                    }).slice(0, 10); // Limitar a 10 resultados
                },
                
                addFirstFiltered() {
                    if (this.filteredTests.length > 0) {
                        this.addTest(this.filteredTests[0]);
                    }
                },
                
                addTest(test) {
                    if (!this.selectedTests.find(s => s.id === test.id)) {
                        this.selectedTests.push(test);
                    }
                    this.searchTest = '';
                    this.showSuggestions = false;
                },
                
                removeTest(index) {
                    this.selectedTests.splice(index, 1);
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-lab-layout>
