<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Ajuste de Stock</h1>
                <p class="text-gray-500 text-sm mt-1">Registrar entrada, salida o ajuste manual de stock</p>
            </div>
            <a href="{{ route('stock-movements.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                &larr; Volver al historial
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

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('stock-movements.store') }}" x-data="stockForm()">
            @csrf
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5 max-w-2xl">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Insumo <span class="text-red-500">*</span></label>
                        <select name="supply_id" x-model="supplyId" @change="updateSupplyInfo" required
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar insumo...</option>
                            @foreach($supplies as $sup)
                                <option value="{{ $sup->id }}" 
                                        data-stock="{{ $sup->stock }}" 
                                        data-unit="{{ $sup->unit }}"
                                        data-tracks-lot="{{ $sup->tracks_lot ? '1' : '0' }}"
                                        {{ old('supply_id') == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->code }} - {{ $sup->name }}{{ $sup->brand ? ' ('.$sup->brand.')' : '' }} (Stock: {{ number_format($sup->stock, 2) }} {{ $sup->unit }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="currentStock !== null" x-cloak
                         class="p-3 bg-zinc-50 rounded-lg border border-zinc-200">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Stock actual:</span>
                            <span class="text-lg font-bold text-gray-800" x-text="currentStock + ' ' + currentUnit"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Movimiento <span class="text-red-500">*</span></label>
                        <select name="type" x-model="type" required
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            <option value="entrada" {{ old('type') === 'entrada' ? 'selected' : '' }}>Entrada (suma al stock)</option>
                            <option value="salida" {{ old('type') === 'salida' ? 'selected' : '' }}>Salida (resta del stock)</option>
                            <option value="ajuste" {{ old('type') === 'ajuste' ? 'selected' : '' }}>Ajuste (establece el stock)</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1" x-show="type === 'ajuste'">El ajuste establece el stock en la cantidad indicada.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity" value="{{ old('quantity') }}" step="0.01" min="0.01" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="0.00">
                    </div>

                    <template x-if="tracksLot">
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200 space-y-4">
                            <p class="text-sm font-medium text-blue-700">Este insumo controla Lote y Vencimiento</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">N° de Lote</label>
                                    <input type="text" name="lot_number" value="{{ old('lot_number') }}"
                                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                           placeholder="Ej: LOT-2026-001">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Vencimiento</label>
                                    <input type="date" name="expiration_date" value="{{ old('expiration_date') }}"
                                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                </div>
                            </div>
                        </div>
                    </template>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2"
                                  class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Motivo del ajuste (opcional)">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end max-w-2xl">
                <button type="submit"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 transition-colors text-sm shadow-sm">
                    Registrar Movimiento
                </button>
            </div>
        </form>
    </div>

    <script>
        function stockForm() {
            return {
                supplyId: '{{ old("supply_id", "") }}',
                type: '{{ old("type", "") }}',
                currentStock: null,
                currentUnit: '',
                tracksLot: false,
                updateSupplyInfo() {
                    const select = document.querySelector('select[name="supply_id"]');
                    const option = select.options[select.selectedIndex];
                    if (option && option.value) {
                        this.currentStock = option.dataset.stock;
                        this.currentUnit = option.dataset.unit;
                        this.tracksLot = option.dataset.tracksLot === '1';
                    } else {
                        this.currentStock = null;
                        this.currentUnit = '';
                        this.tracksLot = false;
                    }
                },
                init() {
                    if (this.supplyId) this.updateSupplyInfo();
                }
            }
        }
    </script>
</x-admin-layout>
