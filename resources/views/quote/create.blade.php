<x-lab-layout title="Nuevo Presupuesto">
    <div class="p-4 md:p-6" x-data="quoteForm()">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nuevo Presupuesto</h1>
                <p class="text-gray-500 text-sm mt-1">Complete los datos y agregue las determinaciones</p>
            </div>
            <a href="{{ route('quotes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                &larr; Volver al listado
            </a>
        </div>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('quotes.store') }}" @submit="prepareSubmit">
            @csrf

            <!-- Datos del cliente -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Cliente</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2 relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar cliente registrado</label>
                        <div class="relative">
                            <input type="text" x-model="customerSearch" @input.debounce.300ms="searchCustomers"
                                   @keydown.escape="customerResults = []"
                                   placeholder="Escribí nombre, CUIT o email del cliente..."
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 pl-10">
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <div x-show="customerResults.length > 0" x-cloak
                             @click.outside="customerResults = []"
                             class="absolute z-20 w-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 max-h-60 overflow-y-auto">
                            <template x-for="c in customerResults" :key="c.id">
                                <button type="button" @click="selectCustomer(c)"
                                        class="w-full px-4 py-2.5 text-left hover:bg-teal-50 text-sm border-b border-gray-100 last:border-0 transition-colors">
                                    <div class="font-medium text-gray-800" x-text="c.name"></div>
                                    <div class="text-xs text-gray-500">
                                        <span x-show="c.taxId" x-text="'CUIT: ' + c.taxId"></span>
                                        <span x-show="c.taxId && c.email"> | </span>
                                        <span x-show="c.email" x-text="c.email"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                        <input type="hidden" name="customer_id" x-model="selectedCustomerId">
                        <template x-if="selectedCustomerId">
                            <div class="mt-2 inline-flex items-center gap-2 bg-teal-50 text-teal-700 px-3 py-1.5 rounded-lg text-sm">
                                <span x-text="customerName"></span>
                                <button type="button" @click="clearCustomer" class="text-teal-500 hover:text-teal-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre / Razón Social <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" x-model="customerName" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                               placeholder="Nombre del cliente">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="customer_email" x-model="customerEmail"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                               placeholder="correo@ejemplo.com">
                    </div>
                </div>
            </div>

            <!-- Determinaciones y Servicios -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Determinaciones y Servicios</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <!-- Buscador de determinaciones -->
                    <div class="relative">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Determinaciones</label>
                        <div class="relative">
                            <input type="text" x-model="searchQuery" @input.debounce.300ms="searchTests"
                                   @keydown.escape="searchResults = []"
                                   placeholder="Buscar determinación..."
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 pl-10">
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <div x-show="searchResults.length > 0" x-cloak
                             @click.outside="searchResults = []"
                             class="absolute z-20 w-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 max-h-60 overflow-y-auto">
                            <template x-for="test in searchResults" :key="test.id">
                                <button type="button" @click="addTest(test)"
                                        class="w-full px-4 py-2.5 text-left hover:bg-teal-50 text-sm flex justify-between items-center border-b border-gray-100 last:border-0 transition-colors">
                                    <div>
                                        <span class="font-medium text-gray-800" x-text="test.code"></span>
                                        <span class="text-gray-500 mx-1">-</span>
                                        <span class="text-gray-700" x-text="test.name"></span>
                                    </div>
                                    <span class="text-teal-600 font-semibold" x-text="test.price ? ('$' + parseFloat(test.price).toFixed(2)) : 'Sin precio'"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Buscador de servicios -->
                    <div class="relative">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Servicios</label>
                        <div class="relative">
                            <input type="text" x-model="serviceSearchQuery" @input.debounce.300ms="searchServices"
                                   @keydown.escape="serviceSearchResults = []"
                                   placeholder="Buscar servicio..."
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 pl-10">
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div x-show="serviceSearchResults.length > 0" x-cloak
                             @click.outside="serviceSearchResults = []"
                             class="absolute z-20 w-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 max-h-60 overflow-y-auto">
                            <template x-for="svc in serviceSearchResults" :key="svc.id">
                                <button type="button" @click="addService(svc)"
                                        class="w-full px-4 py-2.5 text-left hover:bg-amber-50 text-sm flex justify-between items-center border-b border-gray-100 last:border-0 transition-colors">
                                    <span class="font-medium text-gray-800" x-text="svc.name"></span>
                                    <span class="text-amber-600 font-semibold" x-text="svc.price ? ('$' + parseFloat(svc.price).toFixed(2)) : 'Sin precio'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Agregar ítem manual -->
                <button type="button" @click="addManualItem"
                        class="mb-4 text-sm text-teal-600 hover:text-teal-800 font-medium inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar ítem manual
                </button>

                <!-- Tabla de ítems -->
                <div x-show="items.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-1/2">Descripción</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase w-20">Cant.</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-32">P. Unitario</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-32">Total</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2">
                                        <input type="hidden" :name="'items[' + index + '][test_id]'" :value="item.test_id || ''">
                                        <input type="text" :name="'items[' + index + '][description]'" x-model="item.description" required
                                               class="w-full rounded border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity"
                                               @input="recalculate" min="1" required
                                               class="w-20 rounded border-gray-300 text-sm text-center focus:border-teal-500 focus:ring-teal-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" :name="'items[' + index + '][unit_price]'" x-model.number="item.unit_price"
                                               @input="recalculate" min="0" step="0.01" required
                                               class="w-32 rounded border-gray-300 text-sm text-right focus:border-teal-500 focus:ring-teal-500">
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + (item.quantity * item.unit_price).toFixed(2)"></td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div x-show="items.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Buscá una determinación o servicio para agregar al presupuesto
                </div>
            </div>

            <!-- Totales y opciones -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Opciones -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Opciones</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Válido hasta</label>
                            <input type="date" name="valid_until"
                                   value="{{ now()->addDays(30)->format('Y-m-d') }}"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notas / Observaciones</label>
                            <textarea name="notes" rows="3"
                                      class="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                                      placeholder="Condiciones, plazos, etc."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Resumen -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Resumen</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium text-gray-800" x-text="'$' + subtotal.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600">IVA</span>
                                <input type="number" name="tax_rate" x-model.number="taxRate" @input="recalculate"
                                       min="0" max="100" step="0.5"
                                       class="w-16 rounded border-gray-300 text-xs text-center focus:border-teal-500 focus:ring-teal-500">
                                <span class="text-gray-400 text-xs">%</span>
                            </div>
                            <span class="font-medium text-gray-800" x-text="'$' + taxAmount.toFixed(2)"></span>
                        </div>
                        <div class="border-t border-gray-200 pt-3 flex justify-between">
                            <span class="text-gray-800 font-semibold">Total</span>
                            <span class="text-xl font-bold text-teal-700" x-text="'$' + total.toFixed(2)"></span>
                        </div>
                    </div>

                    <button type="submit" :disabled="items.length === 0"
                            class="mt-6 w-full py-3 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm">
                        Crear Presupuesto
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function quoteForm() {
            return {
                selectedCustomerId: '',
                customerName: '{{ old("customer_name", "") }}',
                customerEmail: '{{ old("customer_email", "") }}',
                customerSearch: '',
                customerResults: [],
                searchQuery: '',
                searchResults: [],
                serviceSearchQuery: '',
                serviceSearchResults: [],
                items: [],
                taxRate: {{ old('tax_rate', 0) }},
                subtotal: 0,
                taxAmount: 0,
                total: 0,

                async searchCustomers() {
                    if (this.customerSearch.length < 2) {
                        this.customerResults = [];
                        return;
                    }
                    try {
                        const response = await fetch(`{{ route('quotes.searchCustomers') }}?q=${encodeURIComponent(this.customerSearch)}`);
                        this.customerResults = await response.json();
                    } catch (e) {
                        this.customerResults = [];
                    }
                },

                selectCustomer(customer) {
                    this.selectedCustomerId = customer.id;
                    this.customerName = customer.name;
                    this.customerEmail = customer.email || '';
                    this.customerSearch = '';
                    this.customerResults = [];
                },

                clearCustomer() {
                    this.selectedCustomerId = '';
                    this.customerName = '';
                    this.customerEmail = '';
                    this.customerSearch = '';
                },

                async searchTests() {
                    if (this.searchQuery.length < 2) {
                        this.searchResults = [];
                        return;
                    }
                    try {
                        const response = await fetch(`{{ route('quotes.searchTests') }}?q=${encodeURIComponent(this.searchQuery)}`);
                        this.searchResults = await response.json();
                    } catch (e) {
                        this.searchResults = [];
                    }
                },

                addTest(test) {
                    const exists = this.items.find(i => i.test_id === test.id);
                    if (exists) {
                        exists.quantity++;
                        this.recalculate();
                        this.searchResults = [];
                        this.searchQuery = '';
                        return;
                    }
                    this.items.push({
                        test_id: test.id,
                        description: test.code + ' - ' + test.name,
                        quantity: 1,
                        unit_price: parseFloat(test.price) || 0,
                    });
                    this.recalculate();
                    this.searchResults = [];
                    this.searchQuery = '';
                },

                async searchServices() {
                    if (this.serviceSearchQuery.length < 2) {
                        this.serviceSearchResults = [];
                        return;
                    }
                    try {
                        const response = await fetch(`{{ route('quotes.searchServices') }}?q=${encodeURIComponent(this.serviceSearchQuery)}`);
                        this.serviceSearchResults = await response.json();
                    } catch (e) {
                        this.serviceSearchResults = [];
                    }
                },

                addService(svc) {
                    this.items.push({
                        test_id: null,
                        description: svc.name,
                        quantity: 1,
                        unit_price: parseFloat(svc.price) || 0,
                    });
                    this.recalculate();
                    this.serviceSearchResults = [];
                    this.serviceSearchQuery = '';
                },

                addManualItem() {
                    this.items.push({
                        test_id: null,
                        description: '',
                        quantity: 1,
                        unit_price: 0,
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.recalculate();
                },

                recalculate() {
                    this.subtotal = this.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
                    this.taxAmount = Math.round(this.subtotal * (this.taxRate / 100) * 100) / 100;
                    this.total = this.subtotal + this.taxAmount;
                },

                prepareSubmit() {
                    if (this.items.length === 0) {
                        alert('Debe agregar al menos una determinación.');
                        return false;
                    }
                    return true;
                }
            }
        }
    </script>
</x-lab-layout>
