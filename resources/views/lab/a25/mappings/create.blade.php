<x-lab-layout title="Nueva equivalencia A25">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0 max-w-2xl">
        <div class="mb-6">
            <div class="text-sm text-gray-500 mb-1">
                <a href="{{ route('a25.mappings.index') }}" class="hover:text-teal-600">Equivalencias A25</a>
                <span class="mx-1">/</span>
                <span>Nueva</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Nueva equivalencia A25 ↔ Labit</h1>
        </div>

        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        <form action="{{ route('a25.mappings.store') }}" method="POST"
              class="bg-white rounded-xl shadow p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre en el equipo A25 <span class="text-red-500">*</span>
                </label>
                <input type="text" name="equipment_analyte_name"
                       value="{{ old('equipment_analyte_name') }}"
                       placeholder="ej. Got wiener"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500"
                       required>
                <p class="text-xs text-gray-500 mt-1">Copiar el texto exactamente como aparece en el archivo del equipo (respeta mayúsculas y espacios).</p>
                @error('equipment_analyte_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Determinación en Labit <span class="text-red-500">*</span>
                </label>
                <select name="test_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                    <option value="">— Seleccionar —</option>
                    @foreach($tests as $test)
                        <option value="{{ $test->id }}" {{ old('test_id') == $test->id ? 'selected' : '' }}>
                            {{ $test->code ? "[{$test->code}] " : '' }}{{ $test->name }}
                        </option>
                    @endforeach
                </select>
                @error('test_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de material</label>
                    <input type="text" name="material_type" value="{{ old('material_type', 'SER') }}"
                           maxlength="20"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                    <p class="text-xs text-gray-500 mt-1">Ej: SER (suero), ORI (orina). Por defecto: SER.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sede (opcional)</label>
                    <select name="lab_branch_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                        <option value="">Global (todas las sedes)</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('lab_branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="px-5 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-medium">
                    Guardar
                </button>
                <a href="{{ route('a25.mappings.index') }}"
                   class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</x-lab-layout>
