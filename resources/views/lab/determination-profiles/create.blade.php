<x-lab-layout title="Nuevo perfil">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0 max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('determination-profiles.index') }}" class="text-sm text-teal-600 hover:text-teal-800">← Volver</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Nuevo perfil de determinaciones</h1>
        </div>

        <form action="{{ route('determination-profiles.store') }}" method="POST" class="space-y-6"
              x-data="profileTestsPicker()">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre del perfil *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="mt-1 w-full rounded-lg border-gray-300" placeholder="Ej. Chequeo básico">
                    @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo de laboratorio *</label>
                    <select name="lab_type" x-model="labType" @change="clearPicked();" required
                            class="mt-1 w-full rounded-lg border-gray-300">
                        @foreach(\App\Enums\DeterminationProfileLabType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                    @error('lab_type') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar determinaciones</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="q" @keydown.enter.prevent="search()" placeholder="Código o nombre..."
                               class="flex-1 rounded-lg border-gray-300 text-sm">
                        <button type="button" @click="search()" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm">Buscar</button>
                    </div>
                    <div class="mt-2 border rounded-lg max-h-48 overflow-y-auto divide-y bg-gray-50" x-show="results.length">
                        <template x-for="t in results" :key="t.id">
                            <button type="button" @click="addTest(t)"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-teal-50 flex justify-between gap-2">
                                <span><span class="font-mono text-teal-700" x-text="t.code"></span> — <span x-text="t.name"></span></span>
                                <span class="text-teal-600 text-xs">Agregar</span>
                            </button>
                        </template>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Agregue al menos una determinación.</p>
                    @error('test_ids') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionadas (<span x-text="picked.length"></span>)</label>
                    <ul class="border rounded-lg divide-y max-h-56 overflow-y-auto bg-white">
                        <template x-for="(t, idx) in picked" :key="t.id">
                            <li class="flex items-center justify-between px-3 py-2 text-sm">
                                <span><span class="font-mono text-teal-700" x-text="t.code"></span> — <span x-text="t.name"></span></span>
                                <button type="button" @click="remove(idx)" class="text-red-600 hover:underline text-xs">Quitar</button>
                                <input type="hidden" :name="'test_ids['+idx+']'" :value="t.id">
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="bi bi-check-lg me-1"></i> Guardar
                </button>
                <a href="{{ route('determination-profiles.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
        function profileTestsPicker() {
            return {
                labType: '{{ old('lab_type', 'clinico') }}',
                q: '',
                results: [],
                picked: [],
                clearPicked() {
                    this.picked = [];
                    this.results = [];
                },
                async search() {
                    const url = new URL('{{ route('determination-profiles.search-tests') }}', window.location.origin);
                    url.searchParams.set('lab_type', this.labType);
                    url.searchParams.set('q', this.q || '');
                    const r = await fetch(url);
                    this.results = await r.json();
                },
                addTest(t) {
                    if (this.picked.find(p => p.id === t.id)) return;
                    this.picked.push(t);
                    this.results = [];
                    this.q = '';
                },
                remove(i) {
                    this.picked.splice(i, 1);
                },
            };
        }
    </script>
</x-lab-layout>
