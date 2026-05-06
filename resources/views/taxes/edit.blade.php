<x-admin-layout>
    <div class="p-4 md:p-6 max-w-2xl">
        <div class="mb-6 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar impuesto</h1>
                <p class="text-gray-500 text-sm mt-1">{{ $tax->name }}</p>
            </div>
            <a href="{{ route('taxes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Volver</a>
        </div>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('taxes.update', $tax) }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $tax->name) }}" required maxlength="255" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jurisdicción</label>
                <input type="text" name="jurisdiction" value="{{ old('jurisdiction', $tax->jurisdiction) }}" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta a pagar (pasivo) <span class="text-red-500">*</span></label>
                <select name="liability_account_id" required class="w-full rounded-lg border-gray-300 text-sm">
                    @foreach($liabilityAccounts as $acc)
                        <option value="{{ $acc->id }}" @selected(old('liability_account_id', $tax->liability_account_id) == $acc->id)>{{ $acc->code }} — {{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Periodicidad <span class="text-red-500">*</span></label>
                <select name="frequency" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="monthly" @selected(old('frequency', $tax->frequency) === 'monthly')>Mensual</option>
                    <option value="quarterly" @selected(old('frequency', $tax->frequency) === 'quarterly')>Trimestral</option>
                    <option value="annual" @selected(old('frequency', $tax->frequency) === 'annual')>Anual</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_active', $tax->is_active))>
                <label for="is_active" class="text-sm text-gray-700">Activo</label>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('taxes.index') }}" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-5 py-2 text-sm font-semibold bg-green-600 text-white rounded-lg hover:bg-green-700">Guardar</button>
            </div>
        </form>
    </div>
</x-admin-layout>
