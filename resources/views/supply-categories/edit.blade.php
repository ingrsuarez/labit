<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar Categoría</h1>
                <p class="text-gray-500 text-sm mt-1">{{ $supplyCategory->name }}</p>
            </div>
            <a href="{{ route('supply-categories.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
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

        <form method="POST" action="{{ route('supply-categories.update', $supplyCategory) }}">
            @csrf
            @method('PUT')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5 max-w-2xl">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $supplyCategory->name) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prefijo de Código <span class="text-red-500">*</span></label>
                        <input type="text" name="code_prefix" value="{{ old('code_prefix', $supplyCategory->code_prefix) }}" required maxlength="3"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500 uppercase font-mono"
                               placeholder="Ej: HEM, QUI, AGU"
                               oninput="this.value = this.value.toUpperCase()">
                        <p class="text-xs text-gray-400 mt-1">3 letras que identifican la categoría en el código del insumo (ej: HEM-00001)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea name="description" rows="2"
                                  class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">{{ old('description', $supplyCategory->description) }}</textarea>
                    </div>
                    <div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', $supplyCategory->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-zinc-600 focus:ring-zinc-500">
                            <span class="text-sm font-medium text-gray-700">Categoría activa</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 max-w-2xl">
                <a href="{{ route('supply-categories.index') }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors text-sm">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 transition-colors text-sm shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
