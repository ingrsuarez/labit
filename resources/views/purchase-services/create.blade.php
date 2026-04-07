<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nuevo servicio de compra</h1>
                <p class="text-gray-500 text-sm mt-1">Ej. Análisis derivado laboratorio veterinario</p>
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

        <form method="POST" action="{{ route('purchase-services.store') }}">
            @csrf
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5 max-w-2xl space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código interno</label>
                    <input type="text" name="code" value="{{ old('code') }}" maxlength="50"
                           class="w-full max-w-xs rounded-lg border-gray-300 text-sm font-mono focus:border-zinc-500 focus:ring-zinc-500"
                           placeholder="Opcional, ej. 010">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select name="purchase_service_category_id" class="w-full max-w-md rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        <option value="">Sin categoría</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) old('purchase_service_category_id') === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="32767"
                           class="w-32 rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                </div>
            </div>
            <div class="flex justify-end max-w-2xl">
                <button type="submit" class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 text-sm shadow-sm">Guardar</button>
            </div>
        </form>
    </div>
</x-admin-layout>
