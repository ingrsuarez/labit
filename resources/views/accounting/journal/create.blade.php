<x-admin-layout>
@php
$initialLines = null;
if (old('lines') && is_array(old('lines'))) {
    $initialLines = collect(old('lines'))->map(fn ($l) => [
        'account_id' => (string) ($l['accounting_account_id'] ?? ''),
        'description' => (string) ($l['description'] ?? ''),
        'debit' => (float) ($l['debit'] ?? 0),
        'credit' => (float) ($l['credit'] ?? 0),
    ])->values()->all();
}
@endphp
<div class="p-4 md:p-6 max-w-5xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Nuevo asiento manual</h1>
            <p class="text-gray-500 text-sm mt-1">Registre el debe y el haber; el asiento debe cuadrar.</p>
        </div>
        <a href="{{ route('accounting.journal.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 self-start">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver al libro diario
        </a>
    </div>

    @if($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.journal.store') }}" class="space-y-6" x-data="journalForm(@js($initialLines))">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 md:p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                    <input type="date" name="date" id="date" required value="{{ old('date', now()->toDateString()) }}"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <input type="text" name="description" id="description" required maxlength="500" value="{{ old('description') }}"
                           placeholder="Concepto del asiento"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                </div>
            </div>

            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cuenta</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Debe</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Haber</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(line, index) in lines" :key="index">
                            <tr>
                                <td class="px-3 py-2 align-top">
                                    <select :name="'lines[' + index + '][accounting_account_id]'" x-model="line.account_id" required
                                            class="w-full min-w-[14rem] rounded-lg border-gray-300 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                                        <option value="">Seleccionar cuenta…</option>
                                        @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <input type="text" :name="'lines[' + index + '][description]'" x-model="line.description"
                                           placeholder="Opcional"
                                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <input type="number" :name="'lines[' + index + '][debit]'" x-model.number="line.debit"
                                           min="0" step="0.01" placeholder="0,00"
                                           @input="if (line.debit > 0) line.credit = 0"
                                           class="w-full rounded-lg border-gray-300 text-sm text-right font-mono shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <input type="number" :name="'lines[' + index + '][credit]'" x-model.number="line.credit"
                                           min="0" step="0.01" placeholder="0,00"
                                           @input="if (line.credit > 0) line.debit = 0"
                                           class="w-full rounded-lg border-gray-300 text-sm text-right font-mono shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                                </td>
                                <td class="px-1 py-2 align-top text-center">
                                    <button type="button" @click="removeLine(index)" x-show="lines.length > 2"
                                            class="p-1 text-red-500 hover:text-red-700 rounded" title="Quitar línea">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="2" class="px-3 py-2 text-right text-sm font-semibold text-gray-600">Totales</td>
                            <td class="px-3 py-2 text-right font-mono font-semibold text-gray-900" x-text="'$ ' + totalDebit.toFixed(2).replace('.', ',')"></td>
                            <td class="px-3 py-2 text-right font-mono font-semibold text-gray-900" x-text="'$ ' + totalCredit.toFixed(2).replace('.', ',')"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="text-sm flex items-start gap-2" :class="isBalanced ? 'text-green-600' : 'text-red-600'">
                    <template x-if="isBalanced">
                        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </template>
                    <template x-if="!isBalanced">
                        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </template>
                    <span x-text="isBalanced ? 'Asiento balanceado.' : ('Diferencia: $ ' + Math.abs(totalDebit - totalCredit).toFixed(2).replace('.', ','))"></span>
                </div>
                <button type="button" @click="addLine()" class="text-sm text-teal-700 hover:text-teal-900 font-medium inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Agregar línea
                </button>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" :disabled="!isBalanced"
                    :class="isBalanced ? 'bg-zinc-800 hover:bg-zinc-700' : 'bg-gray-300 cursor-not-allowed'"
                    class="px-6 py-2.5 text-white rounded-lg text-sm font-medium transition-colors">
                Guardar asiento
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function journalForm(initialLines) {
    const defaultLines = [
        { account_id: '', description: '', debit: 0, credit: 0 },
        { account_id: '', description: '', debit: 0, credit: 0 },
    ];
    const lines = (Array.isArray(initialLines) && initialLines.length > 0)
        ? initialLines.map((l) => ({
            account_id: l.account_id != null ? String(l.account_id) : '',
            description: l.description ?? '',
            debit: parseFloat(l.debit) || 0,
            credit: parseFloat(l.credit) || 0,
        }))
        : defaultLines;

    return {
        lines,

        get totalDebit() {
            return this.lines.reduce((s, l) => s + (parseFloat(l.debit) || 0), 0);
        },

        get totalCredit() {
            return this.lines.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0);
        },

        get isBalanced() {
            return Math.abs(this.totalDebit - this.totalCredit) < 0.01;
        },

        addLine() {
            this.lines.push({ account_id: '', description: '', debit: 0, credit: 0 });
        },

        removeLine(index) {
            if (this.lines.length > 2) {
                this.lines.splice(index, 1);
            }
        },
    };
}
</script>
@endpush
</x-admin-layout>
