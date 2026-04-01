@php
$categoryColors = [
    'transferencia' => 'bg-blue-100 text-blue-700',
    'cobro' => 'bg-green-100 text-green-700',
    'cheque' => 'bg-purple-100 text-purple-700',
    'impuesto' => 'bg-red-100 text-red-700',
    'comision' => 'bg-orange-100 text-orange-700',
    'iva_comision' => 'bg-orange-100 text-orange-600',
    'pago_tarjeta' => 'bg-pink-100 text-pink-700',
    'pago_servicio' => 'bg-pink-100 text-pink-600',
    'debin' => 'bg-indigo-100 text-indigo-700',
    'debito_automatico' => 'bg-yellow-100 text-yellow-700',
    'pago_tarjeta_credito' => 'bg-pink-100 text-pink-700',
    'movimiento_interno' => 'bg-gray-100 text-gray-600',
];
$categoryLabels = [
    'transferencia' => 'Transferencia',
    'cobro' => 'Cobro',
    'cheque' => 'Cheque',
    'impuesto' => 'Impuesto',
    'comision' => 'Comisión',
    'iva_comision' => 'IVA Comisión',
    'pago_tarjeta' => 'Pago Tarjeta',
    'pago_servicio' => 'Pago Servicio',
    'debin' => 'DEBIN',
    'debito_automatico' => 'Déb. Automático',
    'pago_tarjeta_credito' => 'Pago TC',
    'movimiento_interno' => 'Mov. Interno',
];
$statusColors = [
    'pending' => 'bg-gray-100 text-gray-600',
    'matched' => 'bg-green-100 text-green-700',
    'ignored' => 'bg-yellow-100 text-yellow-700',
];
$statusLabels = [
    'pending' => 'Pendiente',
    'matched' => 'Conciliado',
    'ignored' => 'Ignorado',
];
$progress = $statement->reconciliation_progress;
@endphp
<x-admin-layout>
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Extracto Bancario</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $bankAccount->display_name }} — {{ $statement->period_from->format('d/m/Y') }} al {{ $statement->period_to->format('d/m/Y') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('accounting.bank-statements.index', $bankAccount) }}" class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                ← Extractos
            </a>
            @can('contabilidad.reconciliation.execute')
            <a href="{{ route('accounting.reconciliation.index', [$bankAccount, $statement]) }}" class="px-4 py-2 text-sm text-white bg-zinc-800 rounded-lg hover:bg-zinc-700">
                Conciliar Extracto
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
            <p class="text-xl font-bold text-gray-800">{{ $statement->movements_count }}</p>
            <p class="text-xs text-gray-400">Movimientos</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
            <p class="text-xl font-bold text-green-600">$ {{ number_format($statement->total_credits, 2, ',', '.') }}</p>
            <p class="text-xs text-gray-400">Créditos</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
            <p class="text-xl font-bold text-red-600">$ {{ number_format($statement->total_debits, 2, ',', '.') }}</p>
            <p class="text-xs text-gray-400">Débitos</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
            <p class="text-xl font-bold text-gray-800">$ {{ number_format($statement->closing_balance ?? 0, 2, ',', '.') }}</p>
            <p class="text-xs text-gray-400">Saldo Cierre</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
            <div class="flex items-center gap-2">
                <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500 rounded-full" style="width: {{ $progress['percent'] }}%"></div>
                </div>
                <span class="text-sm font-bold text-gray-800">{{ $progress['percent'] }}%</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ $progress['matched'] }} conciliados, {{ $progress['pending'] }} pendientes</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-4">
        <select name="category" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm py-1.5">
            <option value="">Todas las categorías</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->category }}" {{ $filterCategory === $cat->category ? 'selected' : '' }}>
                {{ $categoryLabels[$cat->category] ?? $cat->category }} ({{ $cat->cnt }})
            </option>
            @endforeach
        </select>
        <select name="type" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm py-1.5">
            <option value="">Créditos y Débitos</option>
            <option value="credit" {{ $filterType === 'credit' ? 'selected' : '' }}>Solo Créditos</option>
            <option value="debit" {{ $filterType === 'debit' ? 'selected' : '' }}>Solo Débitos</option>
        </select>
        <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm py-1.5">
            <option value="">Todos los estados</option>
            <option value="pending" {{ $filterStatus === 'pending' ? 'selected' : '' }}>Pendiente</option>
            <option value="matched" {{ $filterStatus === 'matched' ? 'selected' : '' }}>Conciliado</option>
            <option value="ignored" {{ $filterStatus === 'ignored' ? 'selected' : '' }}>Ignorado</option>
        </select>
        @if($filterCategory || $filterType || $filterStatus)
        <a href="{{ route('accounting.bank-statements.show', [$bankAccount, $statement]) }}" class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700">Limpiar filtros</a>
        @endif
    </form>

    {{-- Movements table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                    <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                    <th class="px-3 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">Débito</th>
                    <th class="px-3 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">Crédito</th>
                    <th class="px-3 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                    <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($movements as $mov)
                <tr class="hover:bg-gray-50 group" title="{{ $mov->detail }}">
                    <td class="px-3 py-2 text-sm text-gray-600 whitespace-nowrap">{{ $mov->date->format('d/m') }}</td>
                    <td class="px-3 py-2 text-sm text-gray-900 max-w-[300px]">
                        <span class="block truncate">{{ $mov->concept }}</span>
                        @if($mov->detail)
                        <span class="block text-xs text-gray-400 truncate">{{ $mov->detail }}</span>
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        @if($mov->category)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $categoryColors[$mov->category] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $categoryLabels[$mov->category] ?? $mov->category }}
                        </span>
                        @else
                        <span class="text-xs text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-right text-sm font-mono {{ $mov->debit > 0 ? 'text-red-600' : 'text-gray-300' }}">
                        {{ $mov->debit > 0 ? '$ ' . number_format($mov->debit, 2, ',', '.') : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right text-sm font-mono {{ $mov->credit > 0 ? 'text-green-600' : 'text-gray-300' }}">
                        {{ $mov->credit > 0 ? '$ ' . number_format($mov->credit, 2, ',', '.') : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right text-sm font-mono text-gray-600">
                        {{ $mov->balance !== null ? '$ ' . number_format($mov->balance, 2, ',', '.') : '—' }}
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$mov->reconciliation_status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $statusLabels[$mov->reconciliation_status] ?? $mov->reconciliation_status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No hay movimientos con los filtros aplicados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Category summary --}}
    @if($categories->isNotEmpty() && !$filterCategory)
    <div class="mt-6 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Resumen por categoría</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($categories as $cat)
            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2">
                <div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $categoryColors[$cat->category] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $categoryLabels[$cat->category] ?? $cat->category }}
                    </span>
                    <span class="text-xs text-gray-400 ml-1">({{ $cat->cnt }})</span>
                </div>
                <div class="text-right text-xs font-mono">
                    @if($cat->total_credit > 0)<span class="text-green-600">+{{ number_format($cat->total_credit, 0, ',', '.') }}</span>@endif
                    @if($cat->total_debit > 0)<span class="text-red-600 ml-1">-{{ number_format($cat->total_debit, 0, ',', '.') }}</span>@endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
</x-admin-layout>
