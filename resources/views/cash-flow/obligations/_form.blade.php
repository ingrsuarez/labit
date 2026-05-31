@php
    $obligation = $obligation ?? null;
@endphp

<form method="POST" action="{{ $action }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    @csrf
    @if(($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
        <select name="category" required class="w-full rounded-lg border-gray-300 text-sm">
            @foreach($categories as $value => $label)
                <option value="{{ $value }}" @selected(old('category', $obligation?->category) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
        <input type="text" name="title" required maxlength="255"
            value="{{ old('title', $obligation?->title) }}"
            class="w-full rounded-lg border-gray-300 text-sm">
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Monto</label>
            <input type="number" step="0.01" min="0.01" name="amount" required
                value="{{ old('amount', $obligation?->amount) }}"
                class="w-full rounded-lg border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de vencimiento</label>
            <input type="date" name="due_date" required
                value="{{ old('due_date', $obligation?->due_date?->format('Y-m-d')) }}"
                class="w-full rounded-lg border-gray-300 text-sm">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $obligation?->notes) }}</textarea>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">Guardar</button>
        <a href="{{ route('cash-flow.index') }}" class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">Cancelar</a>
    </div>
</form>
