<x-lab-layout title="Nomenclador - {{ strtoupper($insurance->name) }}">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('nomenclator.index') }}" class="hover:text-teal-600">Nomencladores</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span>{{ strtoupper($insurance->name) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ strtoupper($insurance->name) }}</h1>
                    <p class="mt-1 text-sm text-gray-600">Gestione las prácticas y precios para esta obra social</p>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- Configuración NBU -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Valor NBU</h2>
            <form action="{{ route('nomenclator.updateNbu', $insurance) }}" method="POST" class="flex items-end gap-4">
                @csrf
                @method('PUT')
                <div class="flex-1 max-w-xs">
                    <label for="nbu_value" class="block text-sm font-medium text-gray-700 mb-1">Valor de 1 NBU</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                        <input type="number" step="0.01" name="nbu_value" id="nbu_value" 
                               value="{{ $insurance->nbu_value }}"
                               class="pl-8 w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="recalculate_prices" id="recalculate_prices" value="1"
                           class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    <label for="recalculate_prices" class="text-sm text-gray-600">Recalcular precios</label>
                </div>
                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                    Guardar
                </button>
            </form>
        </div>

        <!-- Agregar Práctica -->
        <div x-data="nomenclatorManager()" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Agregar Práctica</h2>
            <form action="{{ route('nomenclator.store', $insurance) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <!-- Buscador de práctica -->
                    <div class="md:col-span-2 relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Práctica</label>
                        <input type="text" x-model="search" @input="searchTests" @focus="showDropdown = true"
                               placeholder="Buscar por código o nombre..."
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <input type="hidden" name="test_id" x-model="selectedTest.id">
                        
                        <!-- Dropdown de resultados -->
                        <div x-show="showDropdown && filteredTests.length > 0" 
                             @click.away="showDropdown = false"
                             class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="test in filteredTests" :key="test.id">
                                <div @click="selectTest(test)" 
                                     class="px-4 py-2 hover:bg-gray-50 cursor-pointer">
                                    <span class="font-medium text-gray-900" x-text="test.code"></span>
                                    <span class="text-gray-600" x-text="' - ' + test.name"></span>
                                    <span class="text-sm text-gray-400 ml-2" x-text="'(' + (test.nbu || 1) + ' NBU)'"></span>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Práctica seleccionada -->
                        <div x-show="selectedTest.id" class="mt-2 p-2 bg-teal-50 rounded-lg text-sm">
                            <span class="font-medium" x-text="selectedTest.code"></span>
                            <span x-text="' - ' + selectedTest.name"></span>
                            <button type="button" @click="clearSelection" class="ml-2 text-red-500 hover:text-red-700">×</button>
                        </div>
                    </div>

                    <!-- NBU -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NBU</label>
                        <input type="number" step="0.01" name="nbu_units" x-model="nbuUnits" @input="calculatePrice"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <!-- Precio -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                            <input type="number" step="0.01" name="price" x-model="price"
                                   class="pl-8 w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <!-- Copago -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Copago</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                            <input type="number" step="0.01" name="copago" value="0"
                                   class="pl-8 w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <!-- Requiere Autorización -->
                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="requires_authorization" value="1"
                                   class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="text-sm text-gray-700">Req. Autoriz.</span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" :disabled="!selectedTest.id"
                            class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Agregar Práctica
                    </button>
                </div>
            </form>
        </div>

        <!-- Listado de Prácticas -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">
                    Prácticas en Nomenclador 
                    <span class="text-sm font-normal text-gray-500">({{ $nomenclator->count() }})</span>
                </h2>
                @if($nomenclator->count() > 0)
                    <form action="{{ route('nomenclator.recalculate', $insurance) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-teal-600 hover:text-teal-800">
                            Recalcular todos los precios
                        </button>
                    </form>
                @endif
            </div>

            @if($nomenclator->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Práctica</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">NBU</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Copago</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Autorización</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($nomenclator as $item)
                                <tr class="hover:bg-gray-50" x-data="{ editing: false }">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $item->test->code }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $item->test->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">
                                        <span x-show="!editing">{{ number_format($item->nbu_units, 2, ',', '.') }}</span>
                                        <input x-show="editing" type="number" step="0.01" name="nbu_units" 
                                               value="{{ $item->nbu_units }}" form="form-{{ $item->id }}"
                                               class="w-20 text-right border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                        <span x-show="!editing">${{ number_format($item->price, 2, ',', '.') }}</span>
                                        <input x-show="editing" type="number" step="0.01" name="price" 
                                               value="{{ $item->price }}" form="form-{{ $item->id }}"
                                               class="w-28 text-right border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">
                                        <span x-show="!editing">${{ number_format($item->copago, 2, ',', '.') }}</span>
                                        <input x-show="editing" type="number" step="0.01" name="copago" 
                                               value="{{ $item->copago }}" form="form-{{ $item->id }}"
                                               class="w-24 text-right border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span x-show="!editing">
                                            @if($item->requires_authorization)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    SÍ
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                    NO
                                                </span>
                                            @endif
                                        </span>
                                        <input x-show="editing" type="checkbox" name="requires_authorization" value="1"
                                               {{ $item->requires_authorization ? 'checked' : '' }} form="form-{{ $item->id }}"
                                               class="rounded border-gray-300 text-teal-600">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <form id="form-{{ $item->id }}" 
                                              action="{{ route('nomenclator.update', [$insurance, $item]) }}" 
                                              method="POST" class="inline">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        
                                        <button x-show="!editing" @click="editing = true" 
                                                class="text-teal-600 hover:text-teal-800 mr-2">
                                            Editar
                                        </button>
                                        <button x-show="editing" type="submit" form="form-{{ $item->id }}"
                                                class="text-green-600 hover:text-green-800 mr-2">
                                            Guardar
                                        </button>
                                        <button x-show="editing" @click="editing = false" type="button"
                                                class="text-gray-600 hover:text-gray-800 mr-2">
                                            Cancelar
                                        </button>
                                        
                                        <form action="{{ route('nomenclator.destroy', [$insurance, $item]) }}" 
                                              method="POST" class="inline"
                                              onsubmit="return confirm('¿Está seguro de eliminar esta práctica del nomenclador?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay prácticas en el nomenclador</h3>
                    <p class="mt-1 text-sm text-gray-500">Agregue prácticas usando el formulario de arriba.</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        function nomenclatorManager() {
            return {
                search: '',
                filteredTests: [],
                showDropdown: false,
                selectedTest: { id: null, code: '', name: '', nbu: 1 },
                nbuUnits: 1,
                price: 0,
                nbuValue: {{ $insurance->nbu_value ?? 0 }},

                async searchTests() {
                    if (this.search.length < 2) {
                        this.filteredTests = [];
                        return;
                    }

                    try {
                        const response = await fetch(`{{ route('nomenclator.searchTests', $insurance) }}?q=${encodeURIComponent(this.search)}`);
                        this.filteredTests = await response.json();
                        this.showDropdown = true;
                    } catch (error) {
                        console.error('Error searching tests:', error);
                    }
                },

                selectTest(test) {
                    this.selectedTest = test;
                    this.search = test.code + ' - ' + test.name;
                    this.nbuUnits = test.nbu || 1;
                    this.calculatePrice();
                    this.showDropdown = false;
                },

                clearSelection() {
                    this.selectedTest = { id: null, code: '', name: '', nbu: 1 };
                    this.search = '';
                    this.nbuUnits = 1;
                    this.price = 0;
                },

                calculatePrice() {
                    this.price = (this.nbuUnits * this.nbuValue).toFixed(2);
                }
            }
        }
    </script>
</x-lab-layout>

