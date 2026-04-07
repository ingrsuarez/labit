<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar servicio</h1>
                <p class="text-gray-500 text-sm mt-1">{{ $purchaseService->name }}</p>
            </div>
            <a href="{{ route('purchase-services.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
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

        <form method="POST" action="{{ route('purchase-services.update', $purchaseService) }}">
            @csrf
            @method('PUT')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5 max-w-2xl space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $purchaseService->name) }}" required
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código interno</label>
                    <input type="text" name="code" value="{{ old('code', $purchaseService->code) }}" maxlength="50"
                           class="w-full max-w-xs rounded-lg border-gray-300 text-sm font-mono focus:border-zinc-500 focus:ring-zinc-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select name="purchase_service_category_id" class="w-full max-w-md rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        <option value="">Sin categoría</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) old('purchase_service_category_id', $purchaseService->purchase_service_category_id) === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $purchaseService->sort_order) }}" min="0" max="32767"
                           class="w-32 rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $purchaseService->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-zinc-600 focus:ring-zinc-500">
                        <span class="text-sm font-medium text-gray-700">Servicio activo</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-3 max-w-2xl">
                <a href="{{ route('purchase-services.index') }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 text-sm">Cancelar</a>
                <button type="submit" class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 text-sm shadow-sm">Guardar</button>
            </div>
        </form>
    </div>
</x-admin-layout>
