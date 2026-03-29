<form action="{{ $labBranch ? route('lab-branches.update', $labBranch) : route('lab-branches.store') }}"
      method="POST"
      class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
    @csrf
    @if($labBranch) @method('PUT') @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $labBranch->name ?? '') }}" required
                   class="w-full border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
                   placeholder="Ej: Laboratorio Central, Sede Neuquén">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
            <input type="text" name="address" value="{{ old('address', $labBranch->address ?? '') }}"
                   class="w-full border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
                   placeholder="Calle y número">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
            <input type="text" name="city" value="{{ old('city', $labBranch->city ?? '') }}"
                   class="w-full border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
            <input type="text" name="province" value="{{ old('province', $labBranch->province ?? '') }}"
                   class="w-full border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Código Postal</label>
            <input type="text" name="zip_code" value="{{ old('zip_code', $labBranch->zip_code ?? '') }}" maxlength="10"
                   class="w-full border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <input type="text" name="phone" value="{{ old('phone', $labBranch->phone ?? '') }}" maxlength="50"
                   class="w-full border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $labBranch->email ?? '') }}"
                   class="w-full border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500">
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center">
                <input type="hidden" name="is_central" value="0">
                <input type="checkbox" name="is_central" value="1"
                       {{ old('is_central', $labBranch->is_central ?? false) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                <span class="ml-2 text-sm text-gray-700">Esta es la sede central</span>
            </label>
            <p class="text-xs text-gray-400 mt-1">Solo una sede puede ser la central. Al marcarla, se desmarcará la anterior.</p>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
        <a href="{{ route('lab-branches.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
        <button type="submit"
                class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors text-sm font-medium">
            {{ $labBranch ? 'Guardar Cambios' : 'Crear Sede' }}
        </button>
    </div>
</form>
