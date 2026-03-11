<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar Insumo</h1>
                <p class="text-gray-500 text-sm mt-1">{{ $supply->code }} - {{ $supply->name }}</p>
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

        <form method="POST" action="{{ route('supplies.update', $supply) }}">
            @csrf
            @method('PUT')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Insumo</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $supply->name) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                        <input type="text" name="brand" value="{{ old('brand', $supply->brand) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Marca (opcional)">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea name="description" rows="2"
                                  class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">{{ old('description', $supply->description) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select name="supply_category_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Sin categoría</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('supply_category_id', $supply->supply_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unidad de Medida <span class="text-red-500">*</span></label>
                        <select name="unit" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" required>
                            @foreach(['unidad' => 'Unidad', 'litro' => 'Litro', 'ml' => 'Mililitro', 'kg' => 'Kilogramo', 'g' => 'Gramo', 'caja' => 'Caja', 'pack' => 'Pack', 'rollo' => 'Rollo', 'metro' => 'Metro', 'par' => 'Par'] as $val => $label)
                                <option value="{{ $val }}" {{ old('unit', $supply->unit) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo</label>
                        <input type="number" name="min_stock" value="{{ old('min_stock', $supply->min_stock) }}" step="0.01" min="0"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor por Defecto</label>
                        <select name="default_supplier_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Sin proveedor</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ old('default_supplier_id', $supply->default_supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="tracks_lot" value="1"
                                   {{ old('tracks_lot', $supply->tracks_lot) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-zinc-600 focus:ring-zinc-500">
                            <span class="text-sm font-medium text-gray-700">Controla Lote y Vencimiento</span>
                        </label>
                        <p class="text-xs text-gray-400 mt-1 ml-6">Al registrar movimientos de stock se pedirá N° de lote y fecha de vencimiento</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', $supply->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-zinc-600 focus:ring-zinc-500">
                            <span class="text-sm font-medium text-gray-700">Insumo activo</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Info de stock actual (solo lectura) -->
            <div class="bg-zinc-50 rounded-xl border border-zinc-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Stock Actual</h2>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-2xl font-bold {{ $supply->isLowStock() ? 'text-amber-600' : 'text-gray-800' }}">
                            {{ number_format($supply->stock, 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Stock actual ({{ $supply->unit }})</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-600">{{ number_format($supply->min_stock, 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 mt-1">Stock mínimo</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-600">${{ number_format($supply->last_price, 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 mt-1">Último precio</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-3 text-center">El stock se modifica desde Movimientos de Stock, no desde este formulario.</p>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('supplies.index') }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors text-sm">
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
