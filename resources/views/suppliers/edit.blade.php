<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar Proveedor</h1>
                <p class="text-gray-500 text-sm mt-1">{{ $supplier->code }} - {{ $supplier->name }}</p>
            </div>
            <a href="{{ route('suppliers.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
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

        <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
            @csrf
            @method('PUT')

            <!-- Datos principales -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos Principales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social</label>
                        <input type="text" name="business_name" value="{{ old('business_name', $supplier->business_name) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CUIT</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id', $supplier->tax_id) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condición IVA</label>
                        <select name="tax_condition" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            <option value="responsable_inscripto" {{ old('tax_condition', $supplier->tax_condition) === 'responsable_inscripto' ? 'selected' : '' }}>Responsable Inscripto</option>
                            <option value="monotributo" {{ old('tax_condition', $supplier->tax_condition) === 'monotributo' ? 'selected' : '' }}>Monotributo</option>
                            <option value="exento" {{ old('tax_condition', $supplier->tax_condition) === 'exento' ? 'selected' : '' }}>Exento</option>
                            <option value="consumidor_final" {{ old('tax_condition', $supplier->tax_condition) === 'consumidor_final' ? 'selected' : '' }}>Consumidor Final</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="activo" {{ old('status', $supplier->status) === 'activo' ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('status', $supplier->status) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Contacto -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Contacto</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $supplier->email) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contacto (persona)</label>
                        <input type="text" name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono de contacto</label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone', $supplier->contact_phone) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                </div>
            </div>

            <!-- Dirección -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Dirección</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="address" value="{{ old('address', $supplier->address) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                        <input type="text" name="city" value="{{ old('city', $supplier->city) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                        <input type="text" name="state" value="{{ old('state', $supplier->state) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <input type="text" name="country" value="{{ old('country', $supplier->country) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código Postal</label>
                        <input type="text" name="postal" value="{{ old('postal', $supplier->postal) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                </div>
            </div>

            <!-- Datos bancarios -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos Bancarios</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CBU</label>
                        <input type="text" name="cbu" value="{{ old('cbu', $supplier->cbu) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alias</label>
                        <input type="text" name="bank_alias" value="{{ old('bank_alias', $supplier->bank_alias) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $supplier->bank_name) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                </div>
            </div>

            <!-- Notas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notas / Observaciones</label>
                    <textarea name="notes" rows="3"
                              class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">{{ old('notes', $supplier->notes) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('suppliers.index') }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors text-sm">
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
