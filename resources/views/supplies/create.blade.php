<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nuevo Insumo</h1>
                <p class="text-gray-500 text-sm mt-1">Registrar un nuevo insumo en el sistema</p>
            </div>
            <a href="{{ route('supplies.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
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

        <form method="POST" action="{{ route('supplies.store') }}">
            @csrf
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Insumo</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Nombre del insumo">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                        <input type="text" name="brand" value="{{ old('brand') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Marca (opcional)">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea name="description" rows="2"
                                  class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Descripción opcional">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select name="supply_category_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Sin categoría</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('supply_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unidad de Medida <span class="text-red-500">*</span></label>
                        <select name="unit" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" required>
                            <option value="unidad" {{ old('unit', 'unidad') === 'unidad' ? 'selected' : '' }}>Unidad</option>
                            <option value="litro" {{ old('unit') === 'litro' ? 'selected' : '' }}>Litro</option>
                            <option value="ml" {{ old('unit') === 'ml' ? 'selected' : '' }}>Mililitro</option>
                            <option value="kg" {{ old('unit') === 'kg' ? 'selected' : '' }}>Kilogramo</option>
                            <option value="g" {{ old('unit') === 'g' ? 'selected' : '' }}>Gramo</option>
                            <option value="caja" {{ old('unit') === 'caja' ? 'selected' : '' }}>Caja</option>
                            <option value="pack" {{ old('unit') === 'pack' ? 'selected' : '' }}>Pack</option>
                            <option value="rollo" {{ old('unit') === 'rollo' ? 'selected' : '' }}>Rollo</option>
                            <option value="metro" {{ old('unit') === 'metro' ? 'selected' : '' }}>Metro</option>
                            <option value="par" {{ old('unit') === 'par' ? 'selected' : '' }}>Par</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo</label>
                        <input type="number" name="min_stock" value="{{ old('min_stock', 0) }}" step="0.01" min="0"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="0">
                        <p class="text-xs text-gray-400 mt-1">Se mostrará alerta cuando el stock esté por debajo</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor por Defecto</label>
                        <select name="default_supplier_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Sin proveedor</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ old('default_supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="tracks_lot" value="1"
                                   {{ old('tracks_lot') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-zinc-600 focus:ring-zinc-500">
                            <span class="text-sm font-medium text-gray-700">Controla Lote y Vencimiento</span>
                        </label>
                        <p class="text-xs text-gray-400 mt-1 ml-6">Al registrar movimientos de stock se pedirá N° de lote y fecha de vencimiento</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 transition-colors text-sm shadow-sm">
                    Guardar Insumo
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
