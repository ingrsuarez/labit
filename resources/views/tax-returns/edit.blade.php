<x-admin-layout>
    <div class="p-4 md:p-6 max-w-4xl" x-data="{
        taxMap: @js($taxes->keyBy('id')->map(fn ($t) => ['frequency' => $t->frequency])->toArray()),
        taxId: '{{ old('tax_id', $taxReturn->tax_id) }}',
        periodYear: {{ old('period_year', $taxReturn->period_year) }},
        periodMonth: @json(old('period_month', $taxReturn->period_month ?? (int) date('n'))),
        declared: '{{ old('declared_amount', $taxReturn->declared_amount) }}',
        notes: @js(old('notes', $taxReturn->notes ?? '')),
        loaded: @js($prefillLoaded ?? []),
        loading: false,
        needsMonth() {
            const t = this.taxMap[this.taxId];
            return t && t.frequency !== 'annual';
        },
        async loadAdvances() {
            if (!this.taxId) { alert('Seleccioná un impuesto'); return; }
            this.loading = true;
            const p = new URLSearchParams({ tax_id: this.taxId, period_year: this.periodYear });
            const t = this.taxMap[this.taxId];
            if (t && t.frequency !== 'annual') {
                p.set('period_month', this.periodMonth);
            }
            try {
                const r = await fetch(`{{ url('/tax-returns/available-advances') }}?` + p.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                const j = await r.json();
                this.loaded = (j.items || []).map(i => ({
                    ...i,
                    apply: i.amount,
                    include: true,
                }));
            } finally {
                this.loading = false;
            }
        },
    }">
        <div class="mb-6 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar borrador #{{ $taxReturn->id }}</h1>
                <p class="text-gray-500 text-sm mt-1">Cargá período, montos y anticipos a imputar</p>
            </div>
            <a href="{{ route('tax-returns.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Volver</a>
        </div>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tax-returns.update', $taxReturn) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Impuesto <span class="text-red-500">*</span></label>
                        <select name="tax_id" x-model="taxId" required class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($taxes as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Año fiscal <span class="text-red-500">*</span></label>
                        <input type="number" name="period_year" x-model="periodYear" required min="2000" max="2100" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div x-show="needsMonth()" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mes / inicio trimestre</label>
                        <select name="period_month" x-model="periodMonth" class="w-full rounded-lg border-gray-300 text-sm">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Trimestral: usar 1, 4, 7 u 10.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto declarado <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="declared_amount" x-model="declared" required class="w-full max-w-xs rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm" x-model="notes"></textarea>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <button type="button" @click="loadAdvances()" :disabled="loading"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-800 rounded-lg text-sm hover:bg-gray-200 disabled:opacity-50">
                        <span x-show="!loading">Cargar anticipos disponibles del período</span>
                        <span x-show="loading" x-cloak>Cargando…</span>
                    </button>

                    <div class="mt-4 overflow-x-auto" x-show="loaded.length > 0" x-cloak>
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pr-4">Incluir</th><th class="py-2 pr-4">Origen</th><th class="py-2 pr-4">Monto aplicado</th>
                            </tr></thead>
                            <tbody>
                                <template x-for="(row, idx) in loaded" :key="idx">
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2 pr-4"><input type="checkbox" x-model="row.include"></td>
                                        <td class="py-2 pr-4">
                                            <span x-text="row.label"></span>
                                            <span class="text-gray-400 text-xs ml-1" x-text="'('+row.kind.toUpperCase()+')'"></span>
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="number" step="0.01" min="0" x-model.number="row.apply" class="w-32 rounded border-gray-300 text-sm">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <template x-for="(row, idx) in loaded.filter(r => r.include && r.apply > 0)" :key="'h'+idx">
                <div>
                    <input type="hidden" :name="'applications['+idx+'][purchase_invoice_perception_id]'" :value="row.kind === 'fc' ? row.purchase_invoice_perception_id : ''">
                    <input type="hidden" :name="'applications['+idx+'][purchase_credit_note_perception_id]'" :value="row.kind === 'nc' ? row.purchase_credit_note_perception_id : ''">
                    <input type="hidden" :name="'applications['+idx+'][amount_applied]'" :value="row.apply">
                </div>
            </template>

            <div class="flex justify-end gap-3">
                <a href="{{ route('tax-returns.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">Guardar borrador</button>
            </div>
        </form>
    </div>
</x-admin-layout>
