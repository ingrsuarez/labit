<x-admin-layout>
    <div class="p-4 md:p-6" x-data="quotationForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nueva Solicitud de Cotización</h1>
                <p class="text-gray-500 text-sm mt-1">Solicitar cotización de insumos a un proveedor</p>
            </div>
            <a href="{{ route('purchase-quotation-requests.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
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

        <form method="POST" action="{{ route('purchase-quotation-requests.store') }}">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos de la Solicitud</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <select name="supplier_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Válido hasta</label>
                        <input type="date" name="valid_until" value="{{ old('valid_until', date('Y-m-d', strtotime('+7 days'))) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Observaciones opcionales">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Insumos a Cotizar</h2>

                <div class="mb-4 relative">
                    <input type="text" x-model="supplySearch"
                           @input="searchSupplies()"
                           @focus="showDropdown = true"
                           @keydown.enter.prevent="selectFirst()"
                           @keydown.escape="showDropdown = false; supplySearch = ''"
                           placeholder="Buscar por código o nombre... (Enter para agregar)"
                           autocomplete="off"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">

                    <div x-show="showDropdown && matchedSupplies.length > 0"
                         @click.away="showDropdown = false"
                         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="sup in matchedSupplies" :key="sup.id">
                            <div @click="addSupply(sup)"
                                 class="px-4 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 text-sm">
                                <span class="font-mono text-xs text-gray-400" x-text="sup.code"></span>
                                <span class="font-medium text-gray-800 ml-1" x-text="sup.name"></span>
                                <span class="text-gray-400 text-xs ml-1" x-text="sup.brand || ''"></span>
                                <span class="text-gray-400 text-xs ml-1" x-text="'(' + sup.unit + ')'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="items.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Insumo</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase w-32">Cantidad</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-48">Notas</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm">
                                        <input type="hidden" :name="'items[' + index + '][supply_id]'" :value="item.supply_id">
                                        <span class="font-mono text-gray-400 text-xs" x-text="item.code"></span>
                                        <span class="ml-1 text-gray-700" x-text="item.name"></span>
                                        <span class="text-gray-400 text-xs ml-1" x-text="'(' + item.unit + ')'"></span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity"
                                               min="1" step="1" required
                                               class="w-28 rounded border-gray-300 text-sm text-center focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" :name="'items[' + index + '][notes]'" x-model="item.notes"
                                               placeholder="Opcional"
                                               class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600">
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
                    Tipeá el nombre de un insumo y presioná Enter para agregarlo
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="items.length === 0"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Crear Solicitud
                </button>
            </div>
        </form>
    </div>

    @php
        $suppliesJson = $supplies->map(fn($s) => [
            'id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'brand' => $s->brand,
            'unit' => $s->unit,
        ])->values();
    @endphp

    <script>
        function quotationForm() {
            return {
                allSupplies: @json($suppliesJson),
                items: [],
                supplySearch: '',
                matchedSupplies: [],
                showDropdown: false,

                searchSupplies() {
                    if (this.supplySearch.length < 1) {
                        this.matchedSupplies = [];
                        this.showDropdown = false;
                        return;
                    }
                    const q = this.supplySearch.toLowerCase();
                    this.matchedSupplies = this.allSupplies.filter(s =>
                        !this.items.find(i => i.supply_id == s.id) &&
                        (s.name.toLowerCase().includes(q) ||
                         s.code.toLowerCase().includes(q) ||
                         (s.brand && s.brand.toLowerCase().includes(q)))
                    );
                    this.showDropdown = true;
                },

                selectFirst() {
                    if (this.matchedSupplies.length > 0) {
                        this.addSupply(this.matchedSupplies[0]);
                    }
                },

                addSupply(sup) {
                    if (this.items.find(i => i.supply_id == sup.id)) return;
                    this.items.push({
                        supply_id: sup.id,
                        name: sup.name,
                        code: sup.code,
                        unit: sup.unit,
                        quantity: 1,
                        notes: '',
                    });
                    this.supplySearch = '';
                    this.matchedSupplies = [];
                    this.showDropdown = false;
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                }
            }
        }
    </script>
</x-admin-layout>
