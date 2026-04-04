@props(['source'])

@php
    $entry = \App\Models\JournalEntry::query()
        ->with(['lines' => fn ($q) => $q->orderBy('id'), 'lines.account'])
        ->where('source_type', $source::class)
        ->where('source_id', $source->id)
        ->first();
@endphp

@if($entry)
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2zM15 7V3M9 7V3"/>
                </svg>
                <h2 class="text-lg font-semibold text-gray-800">Asiento contable</h2>
                @if($entry->is_automatic)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">Automático</span>
                @endif
            </div>
            <p class="text-sm text-gray-500">
                N.º {{ str_pad((string) $entry->number, 4, '0', STR_PAD_LEFT) }} · {{ $entry->date->format('d/m/Y') }}
            </p>
        </div>
        @if($entry->description)
            <p class="px-4 py-2 text-sm text-gray-600 border-b border-slate-100 bg-slate-50/50">{{ $entry->description }}</p>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cuenta</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Detalle</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Debe</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Haber</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($entry->lines as $line)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <span class="font-mono font-medium text-gray-800">{{ $line->account?->code ?? '—' }}</span>
                                @if($line->account?->name)
                                    <span class="text-gray-600"> — {{ $line->account->name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $line->description ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-right tabular-nums text-gray-800">
                                @if((float) $line->debit > 0)
                                    ${{ number_format($line->debit, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right tabular-nums text-gray-800">
                                @if((float) $line->credit > 0)
                                    ${{ number_format($line->credit, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
