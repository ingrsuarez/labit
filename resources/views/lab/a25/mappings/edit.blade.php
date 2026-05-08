<x-lab-layout title="Editar equivalencia A25">
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
              class="bg-white rounded-xl shadow p-6 space-y-5">
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

            @php
                $testsJson = $tests->map(function ($t) {
                    return ['id' => $t->id, 'label' => ($t->code ? '[' . $t->code . '] ' : '') . $t->name];
                });
            @endphp
            <div x-data="{
                    query: '',
                    open: false,
                    selectedId: '{{ old('test_id', $mapping->test_id) }}',
                    selectedLabel: '',
                    tests: {{ Js::from($testsJson) }},
                    get filtered() {
                        if (!this.query) return this.tests.slice(0, 80);
                        const q = this.query.toLowerCase();
                        return this.tests.filter(t => t.label.toLowerCase().includes(q)).slice(0, 80);
                    },
                    select(item) {
                        this.selectedId = item.id;
                        this.selectedLabel = item.label;
                        this.query = item.label;
                        this.open = false;
                    },
                    init() {
                        if (this.selectedId) {
                            const found = this.tests.find(t => t.id == this.selectedId);
                            if (found) { this.selectedLabel = found.label; this.query = found.label; }
                        }
                    }
                }" @click.away="open = false">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Determinación en Labit <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="text"
                           x-model="query"
                           @focus="open = true"
                           @input="open = true; selectedId = ''"
                           @keydown.escape="open = false"
                           @keydown.enter.prevent="if(filtered.length) select(filtered[0])"
                           placeholder="Buscar por código o nombre..."
                           autocomplete="off"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                    <input type="hidden" name="test_id" x-model="selectedId" required>

                    <div x-show="open && filtered.length > 0"
                         x-cloak
                         class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto">
                        <template x-for="item in filtered" :key="item.id">
                            <div @mousedown.prevent="select(item)"
                                 class="px-3 py-2 text-sm cursor-pointer hover:bg-teal-50"
                                 :class="item.id == selectedId ? 'bg-teal-50 font-medium text-teal-700' : 'text-gray-800'"
                                 x-text="item.label"></div>
                        </template>
                    </div>
                </div>
                @error('test_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
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
</x-lab-layout>
