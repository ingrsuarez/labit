<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar Punto de Venta</h1>
                <p class="text-gray-500 text-sm mt-1">{{ $pointOfSale->code }} - {{ $pointOfSale->name }}</p>
            </div>
            <a href="{{ route('points-of-sale.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
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

        <form method="POST" action="{{ route('points-of-sale.update', $pointOfSale) }}">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código <span class="text-red-500">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $pointOfSale->code) }}" required maxlength="5"
                               placeholder="Ej: 00001" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500 font-mono">
                        <p class="text-xs text-gray-400 mt-1">Se completa con ceros a la izquierda hasta 5 dígitos</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $pointOfSale->name) }}" required
                               placeholder="Ej: Sucursal Centro" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="address" value="{{ old('address', $pointOfSale->address) }}"
                               placeholder="Ej: Av. San Martín 1234, Ciudad" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $pointOfSale->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-zinc-700 focus:ring-zinc-500">
                            <span class="text-sm font-medium text-gray-700">Activo</span>
                        </label>
                    </div>
                </div>

                <div class="border-t border-gray-100 mt-4 pt-4" x-data="{ isElectronic: {{ old('is_electronic', $pointOfSale->is_electronic) ? 'true' : 'false' }} }">
                    <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-3">Facturación electrónica</h3>

                    <div class="mb-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_electronic" value="1" x-model="isElectronic"
                                   class="rounded border-gray-300 text-zinc-700 focus:ring-zinc-500">
                            <span class="text-sm font-medium text-gray-700">Habilitado para factura electrónica AFIP</span>
                        </label>
                    </div>

                    <div x-show="isElectronic" x-transition class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nro. Punto de Venta AFIP <span class="text-red-500">*</span></label>
                        <input type="number" name="afip_pos_number" value="{{ old('afip_pos_number', $pointOfSale->afip_pos_number) }}" min="1" max="99999"
                               placeholder="Ej: 3" class="w-full md:w-1/2 rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500 font-mono">
                        <p class="text-xs text-gray-400 mt-1">Número de punto de venta habilitado en AFIP</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 transition-colors text-sm shadow-sm">
                    Actualizar Punto de Venta
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
