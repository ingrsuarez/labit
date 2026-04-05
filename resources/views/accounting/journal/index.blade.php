<x-admin-layout>
@php
    $fmtMoney = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.');
    $fmtNro = fn ($e) => $e->date->format('Y').'-'.str_pad((string) $e->number, 4, '0', STR_PAD_LEFT);
@endphp
<div class="p-4 md:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Libro Diario</h1>
            <p class="text-gray-500 text-sm mt-1">Registro cronológico de asientos contables</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('accounting.section') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver
            </a>
            @can('contabilidad.entries.create')
            <a href="{{ route('accounting.journal.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-zinc-800 rounded-lg hover:bg-zinc-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo asiento manual
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
        {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <form method="GET" action="{{ route('accounting.journal.index') }}" class="flex flex-wrap gap-3 items-end mb-6 p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Fecha desde</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Fecha hasta</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
            <select name="type" class="rounded-lg border-gray-300 text-sm min-w-[10rem] shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                <option value="">Todos</option>
                <option value="automatic" @selected(request('type') === 'automatic')>Automáticos</option>
                <option value="manual" @selected(request('type') === 'manual')>Manuales</option>
            </select>
        </div>
        <div class="flex-1 min-w-[12rem]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Buscar descripción</label>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Texto en descripción…"
                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-zinc-800 text-white rounded-lg text-sm font-medium hover:bg-zinc-700">Filtrar</button>
        @if(request()->hasAny(['date_from','date_to','type','search','entry_number']))
        <a href="{{ route('accounting.journal.index') }}" class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900">Limpiar</a>
        @endif
    </form>

    @php
        $pageDebit = $entries->sum(fn ($e) => (float) $e->lines->sum('debit'));
        $pageCredit = $entries->sum(fn ($e) => (float) $e->lines->sum('credit'));
    @endphp

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-10"></th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origen</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debe</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Haber</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" x-data="{ open: null }">
                    @forelse($entries as $entry)
                    @php
                        $src = \App\Http\Controllers\JournalEntryController::sourcePresentation($entry->source_type, $entry->source_id);
                        $d = (float) $entry->lines->sum('debit');
                        $c = (float) $entry->lines->sum('credit');
                    @endphp
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-3 py-3">
                            <button type="button"
                                    @click.stop="open = open === {{ $entry->id }} ? null : {{ $entry->id }}"
                                    class="p-1 rounded text-gray-500 hover:bg-gray-200 hover:text-gray-800"
                                    title="Ver líneas">
                                <svg class="w-5 h-5 transition-transform" :class="open === {{ $entry->id }} ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </td>
                        <td class="px-3 py-3 text-sm font-mono text-gray-800 whitespace-nowrap">{{ $fmtNro($entry) }}</td>
                        <td class="px-3 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $entry->date->format('d/m/Y') }}</td>
                        <td class="px-3 py-3 text-sm text-gray-800 max-w-xs truncate" title="{{ $entry->description }}">{{ $entry->description }}</td>
                        <td class="px-3 py-3 text-sm">
                            @if($src)
                                @if($src['url'])
                                    <a href="{{ $src['url'] }}" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-50 text-teal-800 border border-teal-100 hover:bg-teal-100">{{ $src['label'] }}</a>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">{{ $src['label'] }}</span>
                                @endif
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-sm text-right font-mono text-gray-800">{{ $fmtMoney($d) }}</td>
                        <td class="px-3 py-3 text-sm text-right font-mono text-gray-800">{{ $fmtMoney($c) }}</td>
                        <td class="px-3 py-3 text-sm">
                            @if($entry->is_automatic)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Automático
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-800 border border-blue-100">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Manual
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right text-sm whitespace-nowrap">
                            @if(!$entry->is_automatic)
                                @can('contabilidad.entries.edit')
                                <a href="{{ route('accounting.journal.edit', $entry) }}" class="text-zinc-700 hover:text-zinc-900 font-medium mr-2">Editar</a>
                                @endcan
                                @can('contabilidad.entries.delete')
                                <form action="{{ route('accounting.journal.destroy', $entry) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este asiento?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Eliminar</button>
                                </form>
                                @endcan
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr x-show="open === {{ $entry->id }}" x-cloak class="bg-gray-50">
                        <td colspan="9" class="px-4 py-3">
                            <p class="text-xs font-medium text-gray-500 mb-2">Líneas del asiento</p>
                            <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden bg-white">
                                <thead class="bg-gray-100 text-gray-600">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Código</th>
                                        <th class="px-3 py-2 text-left">Cuenta</th>
                                        <th class="px-3 py-2 text-right">Debe</th>
                                        <th class="px-3 py-2 text-right">Haber</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($entry->lines as $line)
                                    <tr>
                                        <td class="px-3 py-2 font-mono text-gray-700">{{ $line->account->code ?? '—' }}</td>
                                        <td class="px-3 py-2 text-gray-800">{{ $line->account->name ?? '—' }}</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ $line->debit > 0 ? $fmtMoney($line->debit) : '—' }}</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ $line->credit > 0 ? $fmtMoney($line->credit) : '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-500 text-sm">No hay asientos que coincidan con los filtros.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($entries->isNotEmpty())
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="5" class="px-3 py-3 text-right text-sm font-semibold text-gray-600">Totales (página actual)</td>
                        <td class="px-3 py-3 text-sm text-right font-mono font-semibold text-gray-900">{{ $fmtMoney($pageDebit) }}</td>
                        <td class="px-3 py-3 text-sm text-right font-mono font-semibold text-gray-900">{{ $fmtMoney($pageCredit) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($entries->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 bg-white">
            {{ $entries->links() }}
        </div>
        @endif
    </div>
</div>
</x-admin-layout>
