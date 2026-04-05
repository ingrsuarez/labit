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

        <form method="POST" action="{{ route('stock-movements.store') }}" x-data="stockForm({
            supplyId: @js((string) old('supply_id', '')),
            type: @js((string) old('type', '')),
            labBranchId: @js((string) old('lab_branch_id', '')),
            manualLotExit: @js(old('manual_lot_exit') == '1' || old('manual_lot_exit') === true || old('manual_lot_exit') === 1),
        })">
            @csrf
            <input type="hidden" name="manual_lot_exit" :value="shouldSendManualExit() ? '1' : '0'">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5 max-w-2xl">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Insumo <span class="text-red-500">*</span></label>
                        <select name="supply_id" x-model="supplyId" @change="updateSupplyInfo(); fetchLots();" required
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar insumo...</option>
                            @foreach($supplies as $sup)
                                <option value="{{ $sup->id }}" 
                                        data-stock="{{ $sup->stock }}" 
                                        data-unit="{{ $sup->unit }}"
                                        data-tracks-lot="{{ $sup->tracks_lot ? '1' : '0' }}"
                                        {{ old('supply_id') == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->code }} - {{ $sup->name }}{{ $sup->brand ? ' ('.$sup->brand.')' : '' }} (Stock: {{ number_format((int) round((float) $sup->stock), 0, ',', '.') }} {{ $sup->unit }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sede / depósito <span class="text-red-500">*</span></label>
                        <select name="lab_branch_id" x-model="labBranchId" @change="fetchLots()" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($branches as $br)
                                <option value="{{ $br->id }}" {{ (string) old('lab_branch_id') === (string) $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="currentStock !== null" x-cloak
                         class="p-3 bg-zinc-50 rounded-lg border border-zinc-200">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Stock actual:</span>
                            <span class="text-lg font-bold text-gray-800" x-text="stockDisplay + ' ' + currentUnit"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Movimiento <span class="text-red-500">*</span></label>
                        <select name="type" x-model="type" @change="fetchLots()" required
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
                        <input type="number" name="quantity" value="{{ old('quantity') }}" step="1"
                               x-bind:min="type === 'ajuste' ? 0 : 1"
                               x-bind:placeholder="type === 'ajuste' ? '0' : '1'"
                               required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>

                    <template x-if="tracksLot">
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200 space-y-4">
                            <p class="text-sm font-medium text-blue-700">Este insumo controla Lote y Vencimiento</p>

                            {{-- Entrada / ajuste: lote manual (nuevo o absoluto global) --}}
                            <template x-if="type === 'entrada' || type === 'ajuste'">
                                <div>
                                    <p class="text-xs text-blue-800/80 mb-2">En ajuste el movimiento refleja stock global en sede; la trazabilidad por lote puede no coincidir con la suma de lotes.</p>
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

                            {{-- Salida: selector de lotes o manual --}}
                            <template x-if="type === 'salida'">
                                <div class="space-y-3">
                                    <p class="text-xs text-blue-800/80" x-show="lotsLoading">Cargando lotes disponibles…</p>
                                    <p class="text-xs text-red-600" x-show="lotsError && !lotsLoading" x-text="lotsError"></p>

                                    <template x-if="!lotsLoading && salidaUseSelector()">
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">Lote a consumir <span class="text-red-500">*</span></label>
                                            <select x-model="selectedLotKey" @change="applySelectedLot()" required
                                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                                <option value="">Elegir lote…</option>
                                                <template x-for="l in availableLots" :key="lotKey(l)">
                                                    <option :value="lotKey(l)" x-text="lotLabel(l)"></option>
                                                </template>
                                            </select>
                                            <input type="hidden" name="lot_number" :value="lotNumberOut">
                                            <input type="hidden" name="expiration_date" :value="expirationDateOut">
                                            <button type="button" @click="manualLotExit = true; selectedLotKey = ''; lotNumberOut = ''; expirationDateOut = '';"
                                                    class="text-xs text-blue-700 underline font-medium">
                                                Ingresar lote manualmente (modo avanzado)
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="!lotsLoading && salidaUseManualFields()">
                                        <div class="space-y-3">
                                            <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-2 py-1.5"
                                               x-show="salidaManualBecauseNoLots()">
                                                No hay lotes con saldo calculado en esta sede; el stock puede ser previo a trazabilidad por lote o haber ajustes globales. Podés cargar lote y vencimiento a mano.
                                            </p>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">N° de Lote <span class="text-red-500">*</span></label>
                                                    <input type="text" name="lot_number" x-model="lotNumberManual"
                                                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                                           placeholder="Ej: LOT-2026-001">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Vencimiento</label>
                                                    <input type="date" name="expiration_date" x-model="expirationDateManual"
                                                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                                </div>
                                            </div>
                                            <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                                                <input type="checkbox" name="confirm_manual_lot_exit" value="1" class="mt-0.5 rounded border-gray-300"
                                                       {{ old('confirm_manual_lot_exit') ? 'checked' : '' }}>
                                                <span>Confirmo consumo manual de lote (sin validar saldo por lote en sistema).</span>
                                            </label>
                                            <button type="button" x-show="availableLots.length > 0" @click="manualLotExit = false; resetManualFields();"
                                                    class="text-xs text-blue-700 underline font-medium">
                                                Volver al selector de lotes
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>
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
        function stockForm(initial) {
            return {
                supplyId: initial.supplyId || '',
                type: initial.type || '',
                labBranchId: initial.labBranchId || '',
                manualLotExit: initial.manualLotExit || false,
                currentStock: null,
                stockDisplay: '',
                currentUnit: '',
                tracksLot: false,
                availableLots: [],
                lotsLoading: false,
                lotsFetched: false,
                lotsError: '',
                selectedLotKey: '',
                lotNumberOut: '',
                expirationDateOut: '',
                lotNumberManual: @json(old('lot_number', '')),
                expirationDateManual: @json(old('expiration_date', '')),
                lotsBaseUrl: @json(url('/supplies')),
                updateSupplyInfo() {
                    const select = document.querySelector('select[name="supply_id"]');
                    const option = select.options[select.selectedIndex];
                    if (option && option.value) {
                        const raw = option.dataset.stock;
                        this.currentStock = raw;
                        this.stockDisplay = raw !== undefined && raw !== '' ? String(Math.round(parseFloat(raw))) : '';
                        this.currentUnit = option.dataset.unit;
                        this.tracksLot = option.dataset.tracksLot === '1';
                    } else {
                        this.currentStock = null;
                        this.stockDisplay = '';
                        this.currentUnit = '';
                        this.tracksLot = false;
                    }
                    if (!this.tracksLot) {
                        this.manualLotExit = false;
                        this.availableLots = [];
                        this.lotsFetched = false;
                    }
                },
                lotKey(l) {
                    return l.lot_number + '\x01' + (l.expiration_date || '');
                },
                lotLabel(l) {
                    let v = 'Sin vencimiento';
                    if (l.expiration_date) {
                        const p = l.expiration_date.split('-');
                        if (p.length === 3) v = p[2] + '/' + p[1] + '/' + p[0];
                    }
                    return 'Lote ' + l.lot_number + ' — Vence ' + v + ' — Disp: ' + l.quantity;
                },
                applySelectedLot() {
                    const l = this.availableLots.find(x => this.lotKey(x) === this.selectedLotKey);
                    if (l) {
                        this.lotNumberOut = l.lot_number;
                        this.expirationDateOut = l.expiration_date || '';
                    } else {
                        this.lotNumberOut = '';
                        this.expirationDateOut = '';
                    }
                },
                salidaManualBecauseNoLots() {
                    return this.lotsFetched && this.availableLots.length === 0;
                },
                salidaUseSelector() {
                    return this.type === 'salida' && !this.manualLotExit && this.availableLots.length > 0;
                },
                salidaUseManualFields() {
                    return this.type === 'salida' && (this.manualLotExit || this.salidaManualBecauseNoLots());
                },
                shouldSendManualExit() {
                    if (this.type !== 'salida' || !this.tracksLot) return false;
                    return this.manualLotExit || this.salidaManualBecauseNoLots();
                },
                resetManualFields() {
                    this.lotNumberManual = '';
                    this.expirationDateManual = '';
                },
                async fetchLots() {
                    this.lotsError = '';
                    if (!this.supplyId || !this.labBranchId || this.type !== 'salida' || !this.tracksLot) {
                        this.availableLots = [];
                        this.lotsFetched = false;
                        this.selectedLotKey = '';
                        this.lotNumberOut = '';
                        this.expirationDateOut = '';
                        return;
                    }
                    this.lotsLoading = true;
                    try {
                        const url = this.lotsBaseUrl + '/' + encodeURIComponent(this.supplyId) + '/available-lots?lab_branch_id=' + encodeURIComponent(this.labBranchId);
                        const r = await fetch(url, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!r.ok) throw new Error('HTTP');
                        this.availableLots = await r.json();
                        this.lotsFetched = true;
                        if (this.availableLots.length > 0) {
                            this.manualLotExit = false;
                        }
                        if (this.salidaManualBecauseNoLots()) {
                            this.manualLotExit = true;
                        }
                        if (this.salidaUseSelector()) {
                            this.selectedLotKey = '';
                            this.lotNumberOut = '';
                            this.expirationDateOut = '';
                        }
                    } catch (e) {
                        this.availableLots = [];
                        this.lotsFetched = true;
                        this.lotsError = 'No se pudieron cargar los lotes.';
                        this.manualLotExit = true;
                    } finally {
                        this.lotsLoading = false;
                    }
                },
                init() {
                    if (this.supplyId) this.updateSupplyInfo();
                    this.$watch('type', () => {
                        if (this.type !== 'salida') this.manualLotExit = false;
                    });
                    this.$nextTick(() => this.fetchLots());
                },
            };
        }
    </script>
</x-admin-layout>
