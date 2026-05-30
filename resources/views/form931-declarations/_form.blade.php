@php
    $year = old('period_year', $declaration?->period_year ?? (int) date('Y'));
    $month = old('period_month', $declaration?->period_month ?? (int) date('n'));
    $aportes = old('amount_aportes_patronales', $declaration?->amount_aportes_patronales ?? '');
    $contribuciones = old('amount_contribuciones_patronales', $declaration?->amount_contribuciones_patronales ?? '');
    $notes = old('notes', $declaration?->notes ?? '');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Año <span class="text-red-500">*</span></label>
                <input type="number" name="period_year" value="{{ $year }}" required min="2000" max="2100" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mes <span class="text-red-500">*</span></label>
                <select name="period_month" required class="w-full rounded-lg border-gray-300 text-sm">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected((int) $month === $m)>{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Aportes patronales <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="amount_aportes_patronales" value="{{ $aportes }}" required class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contribuciones patronales <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="amount_contribuciones_patronales" value="{{ $contribuciones }}" required class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm">{{ $notes }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" name="save_draft" value="1" class="inline-flex items-center px-4 py-2.5 bg-gray-100 text-gray-800 text-sm font-medium rounded-lg hover:bg-gray-200">
            Guardar borrador
        </button>
        <button type="submit" name="confirm_now" value="1" class="inline-flex items-center px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700"
            onclick="return confirm('¿Confirmar DDJJ y generar asiento contable?')">
            Confirmar DDJJ
        </button>
    </div>
</form>
