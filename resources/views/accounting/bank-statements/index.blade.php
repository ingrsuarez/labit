<x-admin-layout>
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Extractos Bancarios</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $bankAccount->display_name }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('accounting.bank-accounts.index') }}" class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                ← Cuentas
            </a>
            @can('contabilidad.bank_statements.import')
            <a href="{{ route('accounting.bank-statements.create', $bankAccount) }}" class="px-4 py-2 text-sm text-white bg-zinc-800 rounded-lg hover:bg-zinc-700">
                Importar Extracto
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Archivo</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Movimientos</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Créditos</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Débitos</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Conciliación</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($statements as $statement)
                @php $progress = $statement->reconciliation_progress; @endphp
                <tr>
                    <td class="px-4 py-3 text-sm text-gray-900">
                        {{ $statement->period_from->format('d/m/Y') }} — {{ $statement->period_to->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 truncate max-w-[200px]" title="{{ $statement->filename }}">{{ $statement->filename }}</td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $statement->movements_count }}</td>
                    <td class="px-4 py-3 text-right text-sm text-green-600 font-mono">$ {{ number_format($statement->total_credits, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-sm text-red-600 font-mono">$ {{ number_format($statement->total_debits, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-sm text-gray-900 font-mono font-medium">$ {{ number_format($statement->closing_balance ?? 0, 2, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2 justify-center">
                            <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full" style="width: {{ $progress['percent'] }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500">{{ $progress['percent'] }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('accounting.bank-statements.show', [$bankAccount, $statement]) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                                Ver
                            </a>
                            @can('contabilidad.bank_statements.delete')
                            <form action="{{ route('accounting.bank-statements.destroy', [$bankAccount, $statement]) }}" method="POST" onsubmit="return confirm('¿Eliminar este extracto?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-300 text-red-600 hover:bg-red-50">Eliminar</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400">
                        No hay extractos importados.
                        <a href="{{ route('accounting.bank-statements.create', $bankAccount) }}" class="text-zinc-600 hover:underline">Importar el primero</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-admin-layout>
