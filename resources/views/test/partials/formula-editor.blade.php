@php
    $prefix = $prefix ?? 'create';
    $catalog = ($formulaCatalog ?? collect())->map(fn ($t) => [
        'id' => $t->id,
        'code' => $t->code,
        'name' => $t->name,
        'label' => $t->code.' — '.ucfirst($t->name),
    ])->values();
@endphp

<div class="md:col-span-2 border-t border-gray-200 pt-4 mt-2"
     x-data="testFormulaEditor(@js($catalog), @js($prefix))"
     x-init="init()">
    <label class="inline-flex items-start gap-2 cursor-pointer">
        <input type="checkbox" name="formula_enabled" value="1" x-model="enabled"
               class="mt-1 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
        <span class="text-sm text-gray-700">
            <span class="font-medium">Determinación calculada</span>
            <span class="block text-xs text-gray-500 mt-0.5">El resultado se calcula en el protocolo a partir de otras prácticas. Se actualiza al cambiar las prácticas de la fórmula.</span>
        </span>
    </label>

    <input type="hidden" name="formula_json" :value="serialized()">

    <div x-show="enabled" x-cloak class="mt-4 space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Agregar a la fórmula</label>
            <select x-model="pickerId" @change="addTestFromPicker()" class="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="">Buscar determinación…</option>
                <template x-for="item in catalog" :key="item.id">
                    <option :value="item.id" x-text="item.label"></option>
                </template>
            </select>
        </div>

        <div class="flex flex-wrap gap-1">
            <template x-for="op in ['(', ')', '+', '-', '*', '/']" :key="op">
                <button type="button" @click="addOp(op)"
                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-mono bg-white hover:bg-gray-100"
                        x-text="displayOp(op)"></button>
            </template>
            <button type="button" @click="clearTokens()"
                    class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700">Limpiar</button>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Expresión</label>
            <div class="min-h-[3rem] rounded-lg border border-gray-300 bg-white p-3 flex flex-wrap gap-1 items-center">
                <template x-if="tokens.length === 0">
                    <span class="text-sm text-gray-400">Agregue prácticas y operadores</span>
                </template>
                <template x-for="(token, index) in tokens" :key="index">
                    <span x-show="token.type === 'test'"
                          class="inline-flex items-center rounded-full bg-teal-100 text-teal-800 px-2 py-0.5 text-xs font-medium"
                          x-text="token.code"></span>
                    <span x-show="token.type === 'op' || token.type === 'paren'"
                          class="text-sm font-mono text-gray-700"
                          x-text="displayOp(token.value)"></span>
                </template>
            </div>
            <p class="text-xs text-gray-500 mt-1">Vista legible: <span class="font-medium text-gray-700" x-text="displayExpression()"></span></p>
            <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1 mt-2">
                Los equipos (LISCOM) no deben enviar resultado para esta práctica.
            </p>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function testFormulaEditor(catalog, prefix) {
                return {
                    catalog,
                    prefix,
                    enabled: false,
                    tokens: [],
                    pickerId: '',
                    init() {
                        if (prefix === 'edit') {
                            window.addEventListener('edit-formula-load', (event) => {
                                const state = event.detail || {};
                                this.enabled = !!state.enabled;
                                this.tokens = state.tokens || [];
                            });
                            if (window.__editFormulaState) {
                                this.enabled = window.__editFormulaState.enabled;
                                this.tokens = window.__editFormulaState.tokens || [];
                            }
                        }
                    },
                    serialized() {
                        if (!this.enabled) return '';
                        return JSON.stringify({
                            tokens: this.tokens,
                            expression_display: this.displayExpression(),
                        });
                    },
                    displayOp(op) {
                        return { '*': '×', '/': '÷' }[op] || op;
                    },
                    displayExpression() {
                        return this.tokens.map(t => {
                            if (t.type === 'test') return t.name || t.code;
                            if (t.type === 'op' || t.type === 'paren') return this.displayOp(t.value);
                            return '';
                        }).join(' ');
                    },
                    addTestFromPicker() {
                        if (!this.pickerId) return;
                        const item = this.catalog.find(c => String(c.id) === String(this.pickerId));
                        if (!item) return;
                        this.tokens.push({
                            type: 'test',
                            test_id: item.id,
                            code: item.code,
                            name: item.name,
                        });
                        this.pickerId = '';
                    },
                    addOp(op) {
                        if (op === '(' || op === ')') {
                            this.tokens.push({ type: 'paren', value: op });
                        } else {
                            this.tokens.push({ type: 'op', value: op });
                        }
                    },
                    clearTokens() {
                        if (this.tokens.length && !confirm('¿Limpiar la expresión?')) return;
                        this.tokens = [];
                    },
                };
            }
        </script>
    @endpush
@endonce
