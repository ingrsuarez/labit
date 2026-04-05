<x-admin-layout>
@php
    $fmtMoney = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.');
    $fmtNro = fn ($je) => $je->date->format('Y').'-'.str_pad((string) $je->number, 4, '0', STR_PAD_LEFT);
    $tipoLabels = [
        'activo' => 'Activo',
        'pasivo' => 'Pasivo',
        'patrimonio_neto' => 'Patrimonio neto',
        'resultado_positivo' => 'Resultado positivo',
        'resultado_negativo' => 'Resultado negativo',
    ];
    $balanceClass = fn ($v) => (float) $v >= 0 ? 'text-green-700' : 'text-red-600';
    $y = (int) request('year', now()->year);
    $m = (int) request('month', now()->month);
    $periodLabel = \Carbon\Carbon::createFromDate($y, $m, 1)->locale('es')->translatedFormat('F Y');
@endphp
<div class="p-4 md:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Libro Mayor</h1>
            <p class="text-gray-500 text-sm mt-1">Movimientos y saldos por cuenta imputable</p>
        </div>
        <a href="{{ route('accounting.section') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 self-start">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver
        </a>
    </div>

    <form method="GET" action="{{ route('accounting.ledger') }}" class="flex flex-wrap gap-4 items-end mb-6 p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Cuenta</label>
            <select name="account_id" class="rounded-lg border-gray-300 text-sm min-w-64 shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                <option value="">Seleccionar cuenta…</option>
                @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" @selected((string) request('account_id') === (string) $acc->id)>
                    {{ $acc->code }} — {{ $acc->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Mes</label>
            <select name="month" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                @foreach(range(1, 12) as $mo)
                <option value="{{ $mo }}" @selected($m === $mo)>
                    {{ \Carbon\Carbon::createFromDate($y, $mo, 1)->locale('es')->translatedFormat('F') }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Año</label>
            <select name="year" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                @foreach(range(now()->year, now()->year - 3) as $yr)
                <option value="{{ $yr }}" @selected($y === $yr)>{{ $yr }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-zinc-800 text-white rounded-lg text-sm font-medium hover:bg-zinc-700">Consultar</button>
    </form>

    @if($account)
    <div class="mb-4 text-sm text-gray-600">
        <span class="font-semibold text-gray-800">{{ $account->code }} — {{ $account->name }}</span>
        <span class="mx-2 text-gray-300">|</span>
        <span>Tipo: {{ $tipoLabels[$account->type] ?? $account->type }}</span>
        <span class="mx-2 text-gray-300">|</span>
        <span>Período: {{ ucfirst($periodLabel) }}</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Asiento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debe</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Haber</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr class="bg-gray-50/80">
                        <td class="px-4 py-3 text-gray-500">—</td>
                        <td class="px-4 py-3 text-gray-500">—</td>
                        <td class="px-4 py-3 font-medium text-gray-700">Saldo al inicio del período</td>
                        <td class="px-4 py-3 text-right text-gray-400">—</td>
                        <td class="px-4 py-3 text-right text-gray-400">—</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold {{ $balanceClass($openBalance) }}">{{ $fmtMoney($openBalance) }}</td>
                    </tr>
                    @forelse($movements as $line)
                    @php
                        $je = $line->journalEntry;
                        $d = (float) $line->debit;
                        $h = (float) $line->credit;
                        $journalUrl = route('accounting.journal.index', [
                            'entry_number' => $je->number,
                            'date_from' => $je->date->toDateString(),
                            'date_to' => $je->date->toDateString(),
                        ]);
                        $desc = $je->description;
                        if ($line->description) {
                            $desc .= ' · '.$line->description;
                        }
                    @endphp
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $je->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ $journalUrl }}" class="font-mono text-teal-700 hover:text-teal-900 hover:underline">{{ $fmtNro($je) }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-800 max-w-md">{{ $desc }}</td>
                        <td class="px-4 py-3 text-right font-mono text-gray-800">{{ $d > 0 ? $fmtMoney($d) : '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono text-gray-800">{{ $h > 0 ? $fmtMoney($h) : '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold {{ $balanceClass($line->running_balance) }}">{{ $fmtMoney($line->running_balance) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">Sin movimientos en este período.</td>
                    </tr>
                    @endforelse
                    <tr class="bg-gray-50 font-medium border-t-2 border-gray-200">
                        <td class="px-4 py-3 text-gray-500">—</td>
                        <td class="px-4 py-3 text-gray-500">—</td>
                        <td class="px-4 py-3 text-gray-800">Saldo al cierre del período</td>
                        <td class="px-4 py-3 text-right text-gray-400">—</td>
                        <td class="px-4 py-3 text-right text-gray-400">—</td>
                        <td class="px-4 py-3 text-right font-mono font-bold {{ $balanceClass($closeBalance) }}">{{ $fmtMoney($closeBalance) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @elseif(request()->filled('account_id'))
    <p class="text-sm text-red-600">No se encontró la cuenta indicada.</p>
    @else
    <p class="text-sm text-gray-500">Seleccione una cuenta y período, luego pulse Consultar.</p>
    @endif
</div>
</x-admin-layout>
