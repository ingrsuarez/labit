@props([
    'emails' => collect(),
    'legacyEmail' => null,
    'accent' => 'zinc',
])

@php
    $presets = ['Resultados', 'Facturación', 'Pagos', 'Otro'];
    $focusRing = $accent === 'blue' ? 'focus:border-blue-500 focus:ring-blue-500' : 'focus:border-zinc-500 focus:ring-zinc-500';
    $btnAccent = $accent === 'blue' ? 'text-blue-600 hover:text-blue-800' : 'text-zinc-600 hover:text-zinc-800';
    $radioClass = $accent === 'blue' ? 'text-blue-600 focus:ring-blue-500' : 'text-zinc-600 focus:ring-zinc-500';

    $initialRows = old('emails');

    if ($initialRows === null) {
        $initialRows = $emails->map(function ($e) use ($presets) {
            $label = $e->label ?? '';
            $isPreset = in_array($label, ['Resultados', 'Facturación', 'Pagos'], true);

            return [
                'email' => $e->email,
                'label_preset' => $isPreset ? $label : ($label !== '' ? 'Otro' : ''),
                'label_custom' => $isPreset ? '' : $label,
                'is_primary' => (bool) $e->is_primary,
            ];
        })->values()->all();
    }

    if ($initialRows === [] && $legacyEmail) {
        $initialRows = [[
            'email' => $legacyEmail,
            'label_preset' => '',
            'label_custom' => '',
            'is_primary' => true,
        ]];
    }
@endphp

<div
    x-data="{
        rows: @js($initialRows),
        addRow() {
            const isFirst = this.rows.length === 0;
            this.rows.push({ email: '', label_preset: '', label_custom: '', is_primary: isFirst });
        },
        removeRow(index) {
            const wasPrimary = this.rows[index]?.is_primary;
            this.rows.splice(index, 1);
            if (this.rows.length && wasPrimary) {
                this.rows[0].is_primary = true;
            }
        },
        setPrimary(index) {
            this.rows.forEach((row, i) => { row.is_primary = i === index; });
        },
        showCustom(row) {
            return row.label_preset === 'Otro';
        }
    }"
    class="md:col-span-2"
>
    <div class="flex items-center justify-between mb-2">
        <div>
            <label class="block text-sm font-medium text-gray-700">Correos electrónicos</label>
            <p class="text-xs text-gray-500 mt-0.5">Podés agregar varios correos. El principal se usa por defecto al enviar protocolos.</p>
        </div>
        <button type="button" @click="addRow()"
                class="inline-flex items-center gap-1 text-sm {{ $btnAccent }}">
            <i class="bi bi-plus-lg"></i> Agregar email
        </button>
    </div>

    <template x-if="rows.length === 0">
        <p class="text-sm text-gray-400 italic mb-2">Sin correos configurados.</p>
    </template>

    <template x-for="(row, index) in rows" :key="index">
        <div class="border border-gray-200 rounded-lg p-3 mb-2 bg-gray-50">
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <label class="inline-flex items-center gap-2 shrink-0 cursor-pointer">
                    <input type="radio"
                           :name="'primary_email_index'"
                           :checked="row.is_primary"
                           @change="setPrimary(index)"
                           class="{{ $radioClass }}">
                    <span class="text-xs text-gray-600">Principal</span>
                </label>

                <select :name="'emails[' + index + '][label_preset]'" x-model="row.label_preset"
                        class="w-full md:w-40 rounded-lg border-gray-300 text-sm {{ $focusRing }}">
                    <option value="">Sin etiqueta</option>
                    @foreach (['Resultados', 'Facturación', 'Pagos', 'Otro'] as $preset)
                        <option value="{{ $preset }}">{{ $preset }}</option>
                    @endforeach
                </select>

                <input type="text"
                       x-show="showCustom(row)"
                       :name="'emails[' + index + '][label_custom]'"
                       x-model="row.label_custom"
                       placeholder="Etiqueta personalizada"
                       maxlength="50"
                       class="w-full md:w-44 rounded-lg border-gray-300 text-sm {{ $focusRing }}">

                <input type="email"
                       :name="'emails[' + index + '][email]'"
                       x-model="row.email"
                       placeholder="correo@ejemplo.com"
                       class="flex-1 rounded-lg border-gray-300 text-sm {{ $focusRing }}">

                <input type="hidden" :name="'emails[' + index + '][is_primary]'" :value="row.is_primary ? 1 : 0">

                <button type="button" @click="removeRow(index)"
                        class="shrink-0 p-2 text-red-500 hover:text-red-700"
                        aria-label="Eliminar correo">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </template>

    @error('emails')
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>
