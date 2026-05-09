<x-lab-layout title="Editar equivalencia A25">
    @php
        $testsJson = $tests->map(function ($t) {
            return ['id' => $t->id, 'label' => ($t->code ? '[' . $t->code . '] ' : '') . $t->name];
        });
        $currentTestIds = $mapping->tests->pluck('id')->all();
    @endphp

    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0 max-w-2xl">
        <div class="mb-6">
            <div class="text-sm text-gray-500 mb-1">
                <a href="{{ route('a25.mappings.index') }}" class="hover:text-teal-600">Equivalencias A25</a>
                <span class="mx-1">/</span>
                <span>Editar</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Editar equivalencia A25</h1>
        </div>

        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        <form action="{{ route('a25.mappings.update', $mapping) }}" method="POST"
              class="bg-white rounded-xl shadow p-6 space-y-5"
              x-data="a25MappingForm({{ Js::from($testsJson) }}, {{ Js::from($currentTestIds) }})">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre en el equipo A25 <span class="text-red-500">*</span>
                </label>
                <input type="text" name="equipment_analyte_name"
                       value="{{ old('equipment_analyte_name', $mapping->equipment_analyte_name) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500"
                       required>
                @error('equipment_analyte_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Lista dinámica de determinaciones --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Determinación(es) en Labit <span class="text-red-500">*</span>
                </label>
                <p class="text-xs text-gray-500 mb-3">Podés agregar más de una determinación. El resultado del equipo se aplicará a todas las que estén en el protocolo. Incluye laboratorio clínico y veterinario: buscá por código o nombre (lista completa, con scroll).</p>

                <div class="space-y-2">
                    <template x-for="(row, index) in rows" :key="row.uid">
                        <div class="flex items-start gap-2" @click.away="row.open = false">
                            <div class="flex-1 relative">
                                <input type="text"
                                       x-model="row.query"
                                       @focus="row.open = true"
                                       @input="row.open = true; row.selectedId = null"
                                       @keydown.escape="row.open = false"
                                       @keydown.enter.prevent="selectFirst(row)"
                                       placeholder="Buscar por código o nombre..."
                                       autocomplete="off"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                                <input type="hidden"
                                       :name="'test_ids[' + index + ']'"
                                       :value="row.selectedId ?? ''">

                                <div x-show="row.open && getFiltered(row).length > 0"
                                     x-cloak
                                     class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                                    <template x-for="item in getFiltered(row)" :key="item.id">
                                        <div @mousedown.prevent="selectItem(row, item)"
                                             class="px-3 py-2 text-sm cursor-pointer hover:bg-teal-50"
                                             :class="item.id == row.selectedId ? 'bg-teal-50 font-medium text-teal-700' : 'text-gray-800'"
                                             x-text="item.label"></div>
                                    </template>
                                </div>
                            </div>

                            <button type="button"
                                    @click="removeRow(index)"
                                    x-show="rows.length > 1"
                                    class="mt-1 p-2 text-red-400 hover:text-red-600 rounded-lg hover:bg-red-50"
                                    title="Quitar">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                <button type="button"
                        @click="addRow()"
                        class="mt-3 inline-flex items-center gap-1 text-sm text-teal-600 hover:text-teal-800 font-medium">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Agregar otra determinación
                </button>

                @error('test_ids')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                @error('test_ids.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de material</label>
                    <input type="text" name="material_type"
                           value="{{ old('material_type', $mapping->material_type) }}"
                           maxlength="20"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sede (opcional)</label>
                    <select name="lab_branch_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                        <option value="">Global (todas las sedes)</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('lab_branch_id', $mapping->lab_branch_id) == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        @click="prepareSubmit($event)"
                        class="px-5 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-medium">
                    Guardar cambios
                </button>
                <a href="{{ route('a25.mappings.index') }}"
                   class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <script>
    function a25MappingForm(allTests, initialIds) {
        return {
            allTests,
            rows: initialIds.length
                ? initialIds.map((id, i) => {
                    const found = allTests.find(t => t.id == id);
                    return { uid: i, query: found ? found.label : '', selectedId: id, open: false };
                  })
                : [{ uid: 0, query: '', selectedId: null, open: false }],
            _uid: initialIds.length || 1,

            getFiltered(row) {
                const q = row.query.toLowerCase().trim();
                if (!q) {
                    return this.allTests;
                }

                return this.allTests.filter(t => t.label.toLowerCase().includes(q));
            },

            selectItem(row, item) {
                row.selectedId = item.id;
                row.query = item.label;
                row.open = false;
            },

            selectFirst(row) {
                const filtered = this.getFiltered(row);
                if (filtered.length) this.selectItem(row, filtered[0]);
            },

            addRow() {
                this.rows.push({ uid: this._uid++, query: '', selectedId: null, open: false });
            },

            removeRow(index) {
                if (this.rows.length > 1) this.rows.splice(index, 1);
            },

            prepareSubmit(event) {
                const missing = this.rows.filter(r => !r.selectedId);
                if (missing.length) {
                    event.preventDefault();
                    alert('Seleccioná una determinación válida en cada fila antes de guardar.');
                }
            },
        };
    }
    </script>
</x-lab-layout>
