<x-admin-layout>
<div class="p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Nueva Cuenta Contable</h1>
        <p class="text-gray-500 text-sm mt-1">Agregar una cuenta al plan de cuentas</p>
    </div>

    <div class="max-w-2xl bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <form action="{{ route('accounting.accounts.store') }}" method="POST"
              x-data="{
                  parentId: '',
                  parentType: '',
                  parentLevel: 0,
                  parents: @js($parentAccounts->map(fn($a) => ['id' => $a->id, 'code' => $a->code, 'name' => $a->name, 'type' => $a->type, 'level' => $a->level])),
                  onParentChange() {
                      if (this.parentId) {
                          const p = this.parents.find(x => x.id == this.parentId);
                          if (p) { this.parentType = p.type; this.parentLevel = p.level + 1; }
                      } else { this.parentType = ''; this.parentLevel = 0; }
                  }
              }">
            @csrf

            <div class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                        <input type="text" name="code" id="code" value="{{ old('code') }}" required
                               placeholder="ej: 1.1.03"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-zinc-500 focus:border-zinc-500 text-sm font-mono">
                        @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-zinc-500 focus:border-zinc-500 text-sm">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">Cuenta padre (opcional)</label>
                    <select name="parent_id" id="parent_id" x-model="parentId" @change="onParentChange()"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-zinc-500 focus:border-zinc-500 text-sm">
                        <option value="">— Sin padre (cuenta raíz) —</option>
                        @foreach($parentAccounts as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->code }} — {{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="type" id="type" :disabled="parentId != ''" required
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-zinc-500 focus:border-zinc-500 text-sm disabled:bg-gray-100 disabled:text-gray-500">
                            <option value="">Seleccionar...</option>
                            @foreach($types as $key => $label)
                            <option value="{{ $key }}" :selected="parentId ? parentType === '{{ $key }}' : '{{ old('type') }}' === '{{ $key }}'">{{ $label }}</option>
                            @endforeach
                        </select>
                        <template x-if="parentId">
                            <input type="hidden" name="type" :value="parentType">
                        </template>
                        <p x-show="parentId" class="text-xs text-gray-400 mt-1">Heredado de la cuenta padre</p>
                        @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="level" class="block text-sm font-medium text-gray-700 mb-1">Nivel</label>
                        <input type="number" name="level" id="level" min="1" max="4" required
                               :value="parentId ? parentLevel : '{{ old('level', 1) }}'"
                               :readonly="parentId != ''"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-zinc-500 focus:border-zinc-500 text-sm read-only:bg-gray-100">
                        <p x-show="parentId" class="text-xs text-gray-400 mt-1">Calculado automáticamente</p>
                        @error('level') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_header" id="is_header" value="1" {{ old('is_header') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-zinc-600 shadow-sm focus:ring-zinc-500">
                    <label for="is_header" class="text-sm text-gray-700">Es cuenta de agrupación (no recibe imputaciones)</label>
                </div>

                <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-zinc-800 rounded-lg hover:bg-zinc-700 transition-colors">
                        Guardar
                    </button>
                    <a href="{{ route('accounting.accounts.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
</x-admin-layout>
