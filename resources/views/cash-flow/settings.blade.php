<x-admin-layout>
    <div class="p-4 md:p-6 max-w-lg">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Configuración de vencimientos</h1>
            <p class="text-sm text-gray-500 mt-1">Días del mes para IVA y Form 931 en el calendario</p>
        </div>

        <form method="POST" action="{{ route('cash-flow.settings.update') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Día de vencimiento IVA (1–28)</label>
                <input type="number" name="iva_due_day" min="1" max="28" required
                    value="{{ old('iva_due_day', $settings->iva_due_day) }}"
                    class="w-full max-w-xs rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Día de vencimiento Form 931 (1–28)</label>
                <input type="number" name="form931_due_day" min="1" max="28" required
                    value="{{ old('form931_due_day', $settings->form931_due_day) }}"
                    class="w-full max-w-xs rounded-lg border-gray-300 text-sm">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">Guardar</button>
                <a href="{{ route('cash-flow.index') }}" class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">Cancelar</a>
            </div>
        </form>
    </div>
</x-admin-layout>
