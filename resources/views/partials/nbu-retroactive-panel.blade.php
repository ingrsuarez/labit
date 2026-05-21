@props([
    'initialNbu' => 0,
    'previewUrl' => '',
    'entityLabel' => 'admisiones',
    'nbuInputId' => 'nbu_value',
    'useParentAlpine' => false,
])

@once
<script>
    window.nbuRetroactivePanelFactory = function (config) {
        return {
            initialNbu: config.initialNbu,
            retroactive: false,
            retroactiveFrom: config.today,
            previewLoading: false,
            previewData: null,
            previewUrl: config.previewUrl,
            entityLabel: config.entityLabel,
            nbuInputId: config.nbuInputId,
            getNbuValue() {
                const el = document.getElementById(this.nbuInputId);
                return el ? (parseFloat(el.value) || 0) : 0;
            },
            nbuChanged() {
                return Math.abs(this.getNbuValue() - this.initialNbu) > 0.001;
            },
            async runPreview() {
                if (!this.retroactive || !this.nbuChanged()) return;
                this.previewLoading = true;
                this.previewData = null;
                try {
                    const response = await fetch(this.previewUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        },
                        body: JSON.stringify({
                            new_nbu_value: this.getNbuValue(),
                            from_date: this.retroactiveFrom,
                        }),
                    });
                    if (!response.ok) throw new Error('preview failed');
                    this.previewData = await response.json();
                } catch (e) {
                    this.previewData = { error: true };
                } finally {
                    this.previewLoading = false;
                }
            },
            confirmSubmit(event) {
                if (!this.retroactive || !this.nbuChanged()) return true;
                const count = this.previewData?.admissions_count ?? '?';
                const msg = '¿Confirmás actualizar ' + count + ' ' + this.entityLabel + ' desde ' + this.retroactiveFrom + '? Esta acción no se puede deshacer.';
                if (!confirm(msg)) {
                    event.preventDefault();
                    return false;
                }
                return true;
            },
        };
    };

    window.nbuRetroactiveConfirmFormSubmit = function (form, event) {
        const root = form.querySelector('[data-nbu-retroactive-root]');
        if (root && typeof Alpine !== 'undefined' && Alpine.$data(root)) {
            return Alpine.$data(root).confirmSubmit(event);
        }
        return true;
    };
</script>
@endonce

<div @class(['w-full mt-4 bg-amber-50 border border-amber-200 rounded-lg p-4'])
     @if(!$useParentAlpine)
     data-nbu-retroactive-root
     x-data="nbuRetroactivePanelFactory(@js([
         'initialNbu' => (float) $initialNbu,
         'previewUrl' => $previewUrl,
         'entityLabel' => $entityLabel,
         'nbuInputId' => $nbuInputId,
         'today' => now()->toDateString(),
     ]))"
     @endif
     x-show="nbuChanged()"
     x-cloak>
    <label class="flex items-start gap-2 cursor-pointer">
        <input type="checkbox" x-model="retroactive" @change="previewData = null"
               class="rounded border-gray-300 text-amber-600 focus:ring-amber-500 mt-0.5">
        <span>
            <span class="text-sm font-medium text-amber-900">Actualizar precios de {{ $entityLabel }} existentes</span>
            <span class="block text-xs text-amber-800 mt-1">
                Las {{ $entityLabel }} ya cargadas conservan su precio actual. Marcá esta opción
                solo si querés recalcular protocolos anteriores con el nuevo valor NBU.
            </span>
        </span>
    </label>

    <div x-show="retroactive" x-cloak class="mt-4 space-y-3">
        <input type="hidden" name="retroactive_update" :value="retroactive && nbuChanged() ? 1 : 0">
        <input type="hidden" name="retroactive_from" :value="retroactive ? retroactiveFrom : ''">

        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label :for="'retro_from_' + nbuInputId" class="block text-sm font-medium text-amber-900 mb-1">
                    Actualizar {{ $entityLabel }} desde
                </label>
                <input type="date" x-model="retroactiveFrom" :id="'retro_from_' + nbuInputId"
                       max="{{ now()->toDateString() }}"
                       class="border-amber-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm">
            </div>
            <button type="button" @click="runPreview()" :disabled="previewLoading"
                    class="px-3 py-2 text-sm bg-white border border-amber-300 text-amber-800 rounded-lg hover:bg-amber-100 disabled:opacity-50">
                <span x-show="!previewLoading">Vista previa</span>
                <span x-show="previewLoading">Calculando…</span>
            </button>
        </div>

        <div x-show="previewData && !previewData.error" role="status" aria-live="polite"
             class="bg-white rounded border border-amber-100 p-3 text-sm text-gray-700">
            <template x-if="previewData && previewData.admissions_count > 0">
                <p x-text="'Se actualizarían ' + previewData.admissions_count + ' ' + entityLabel + ' (' + previewData.rows_count + ' determinaciones).'"></p>
            </template>
            <template x-if="previewData && previewData.admissions_count === 0">
                <p class="text-gray-600">No hay {{ $entityLabel }} sin facturar desde esa fecha.</p>
            </template>
            <p x-show="previewData && previewData.excluded_invoiced_count > 0" class="text-gray-600 mt-1"
               x-text="previewData.excluded_invoiced_count + ' facturadas quedarán sin cambios.'"></p>
        </div>
        <p x-show="previewData && previewData.error" class="text-sm text-red-600">No se pudo calcular la vista previa.</p>
    </div>
</div>
