<x-lab-layout>
<div class="container mx-auto px-4 py-6" x-data="worksheetEditForm()">
    <div class="mb-6">
        <a href="{{ route('worksheets.index') }}" class="text-teal-600 hover:text-teal-800 text-sm">&larr; Volver a Planillas</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Editar Planilla: {{ $worksheet->name }}</h1>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('worksheets.update', $worksheet) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la planilla</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $worksheet->name) }}" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select name="type" id="type" required x-model="type" @change="selectedTests = []; searchQuery = '';"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="clinico">Lab Clínico</option>
                        <option value="muestras">Aguas y Alimentos</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Determinaciones de la planilla</h2>

            <div class="relative mb-4">
                <input type="text" x-model="searchQuery" @input.debounce.300ms="searchTests()"
                       placeholder="Buscar determinación por código o nombre..."
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500 pr-10">
                <svg class="w-5 h-5 text-gray-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>

                <div x-show="searchResults.length > 0" x-cloak
                     class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    <template x-for="test in searchResults" :key="test.id">
                        <button type="button" @click="addTest(test)"
                                class="w-full text-left px-4 py-2 hover:bg-teal-50 text-sm border-b border-gray-100 last:border-0">
                            <span class="font-medium text-teal-700" x-text="test.code || '—'"></span>
                            <span class="text-gray-600 ml-2" x-text="test.name"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div x-show="selectedTests.length === 0" class="text-center py-8 text-gray-400">
                <p>No hay determinaciones seleccionadas.</p>
            </div>

            <div x-show="selectedTests.length > 0">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Orden</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="(test, index) in selectedTests" :key="test.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-gray-500" x-text="index + 1"></td>
                                <td class="px-4 py-2 text-sm font-medium text-teal-700" x-text="test.code || '—'"></td>
                                <td class="px-4 py-2 text-sm text-gray-900" x-text="test.name"></td>
                                <td class="px-4 py-2 text-right space-x-1">
                                    <button type="button" @click="moveUp(index)" :disabled="index === 0"
                                            class="text-gray-400 hover:text-gray-600 disabled:opacity-30" title="Subir">&#9650;</button>
                                    <button type="button" @click="moveDown(index)" :disabled="index === selectedTests.length - 1"
                                            class="text-gray-400 hover:text-gray-600 disabled:opacity-30" title="Bajar">&#9660;</button>
                                    <button type="button" @click="removeTest(index)"
                                            class="text-red-500 hover:text-red-700 ml-2" title="Quitar">&times;</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <template x-for="(test, index) in selectedTests" :key="'input-' + test.id">
                <input type="hidden" name="tests[]" :value="test.id">
            </template>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>

<script>
function worksheetEditForm() {
    return {
        type: '{{ old("type", $worksheet->type) }}',
        searchQuery: '',
        searchResults: [],
        selectedTests: @json($worksheet->tests->map(fn($t) => ['id' => $t->id, 'code' => $t->code, 'name' => $t->name])->values()),

        async searchTests() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                return;
            }
            try {
                const resp = await fetch(`{{ route('worksheets.searchTests') }}?q=${encodeURIComponent(this.searchQuery)}&type=${this.type}`);
                const data = await resp.json();
                this.searchResults = data.filter(t => !this.selectedTests.find(s => s.id === t.id));
            } catch (e) {
                this.searchResults = [];
            }
        },

        addTest(test) {
            if (!this.selectedTests.find(s => s.id === test.id)) {
                this.selectedTests.push({ id: test.id, code: test.code, name: test.name });
            }
            this.searchQuery = '';
            this.searchResults = [];
        },

        removeTest(index) {
            this.selectedTests.splice(index, 1);
        },

        moveUp(index) {
            if (index > 0) {
                [this.selectedTests[index - 1], this.selectedTests[index]] =
                    [this.selectedTests[index], this.selectedTests[index - 1]];
            }
        },

        moveDown(index) {
            if (index < this.selectedTests.length - 1) {
                [this.selectedTests[index], this.selectedTests[index + 1]] =
                    [this.selectedTests[index + 1], this.selectedTests[index]];
            }
        }
    };
}
</script>
</x-lab-layout>
