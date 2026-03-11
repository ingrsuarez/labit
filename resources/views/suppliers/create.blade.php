<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nuevo Proveedor</h1>
                <p class="text-gray-500 text-sm mt-1">Complete los datos del proveedor</p>
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

        <form method="POST" action="{{ route('suppliers.store') }}">
            @csrf

            <!-- Datos principales -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos Principales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Nombre comercial">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social</label>
                        <input type="text" name="business_name" value="{{ old('business_name') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Razón social">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CUIT</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="XX-XXXXXXXX-X">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condición IVA</label>
                        <select name="tax_condition" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            <option value="responsable_inscripto" {{ old('tax_condition') === 'responsable_inscripto' ? 'selected' : '' }}>Responsable Inscripto</option>
                            <option value="monotributo" {{ old('tax_condition') === 'monotributo' ? 'selected' : '' }}>Monotributo</option>
                            <option value="exento" {{ old('tax_condition') === 'exento' ? 'selected' : '' }}>Exento</option>
                            <option value="consumidor_final" {{ old('tax_condition') === 'consumidor_final' ? 'selected' : '' }}>Consumidor Final</option>
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
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="correo@proveedor.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Teléfono principal">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contacto (persona)</label>
                        <input type="text" name="contact_name" value="{{ old('contact_name') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Nombre de la persona de contacto">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono de contacto</label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Teléfono del contacto">
                    </div>
                </div>
            </div>

            <!-- Dirección -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Dirección</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="address" value="{{ old('address') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Calle y número">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                        <input type="text" name="city" value="{{ old('city') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                        <input type="text" name="state" value="{{ old('state') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <input type="text" name="country" value="{{ old('country', 'Argentina') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código Postal</label>
                        <input type="text" name="postal" value="{{ old('postal') }}"
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
                        <input type="text" name="cbu" value="{{ old('cbu') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="CBU (22 dígitos)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alias</label>
                        <input type="text" name="bank_alias" value="{{ old('bank_alias') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Alias de transferencia">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                               placeholder="Nombre del banco">
                    </div>
                </div>
            </div>

            <!-- Notas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notas / Observaciones</label>
                    <textarea name="notes" rows="3"
                              class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                              placeholder="Observaciones sobre el proveedor...">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 transition-colors text-sm shadow-sm">
                    Guardar Proveedor
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
