<x-admin-layout>
    <div class="p-4 md:p-6 max-w-4xl">
        <div class="mb-6 flex flex-wrap justify-between gap-4 items-start">
            <div>
                <nav class="text-sm text-gray-500 mb-1">
                    <a href="{{ route('tax-returns.index') }}" class="hover:text-gray-700">Declaraciones</a>
                    <span class="mx-1">›</span>
                    <span class="text-gray-700 font-medium">DDJJ #{{ $taxReturn->id }}</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-800">{{ $taxReturn->tax->name }} — {{ $taxReturn->period_label }}</h1>
                <p class="text-gray-500 text-sm mt-1">
                    @if($taxReturn->status === 'draft')
                        <span class="text-yellow-700 font-medium">Borrador</span>
                    @elseif($taxReturn->status === 'confirmed')
                        <span class="text-green-700 font-medium">Confirmada</span>
                    @else
                        <span class="text-gray-700 font-medium">Anulada</span>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('tax-returns.manage')
                    @if($taxReturn->isDraft())
                        <a href="{{ route('tax-returns.edit', $taxReturn) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">Editar borrador</a>
                        <form method="POST" action="{{ route('tax-returns.confirm', $taxReturn) }}" onsubmit="return confirm('¿Confirmar declaración y generar asiento?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">Confirmar DDJJ</button>
                        </form>
                        <form method="POST" action="{{ route('tax-returns.destroy', $taxReturn) }}" onsubmit="return confirm('¿Eliminar borrador?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">Eliminar</button>
                        </form>
                    @endif
                    @if($taxReturn->isConfirmed())
                        <form method="POST" action="{{ route('tax-returns.cancel', $taxReturn) }}" onsubmit="return confirm('¿Anular y generar reverso?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm rounded-lg hover:bg-orange-700">Anular DDJJ</button>
                        </form>
                    @endif
                @endcan
                <a href="{{ route('tax-returns.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">&larr; Listado</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Montos</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Declarado</dt><dd class="font-medium">${{ number_format($taxReturn->declared_amount, 2, ',', '.') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Anticipos aplicados</dt><dd class="font-medium">${{ number_format($taxReturn->applied_total, 2, ',', '.') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Diferencia (declarado − aplicado)</dt><dd class="font-medium">${{ number_format($taxReturn->balance, 2, ',', '.') }}</dd></div>
                </dl>
                @if($taxReturn->notes)
                    <p class="mt-4 text-sm text-gray-600 border-t border-gray-100 pt-3">{{ $taxReturn->notes }}</p>
                @endif
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Asientos</h2>
                @if($taxReturn->journalEntry)
                    <p class="text-sm text-gray-700 mb-2">
                        Asiento #{{ $taxReturn->journalEntry->number }} — {{ $taxReturn->journalEntry->date->format('d/m/Y') }}
                    </p>
                    @can('contabilidad.section')
                        <a href="{{ route('accounting.journal.index', ['search' => 'DDJJ '.$taxReturn->tax->name]) }}" class="text-indigo-600 text-sm hover:underline">Ver en Libro Diario</a>
                    @else
                        <p class="text-xs text-gray-500">Pedí acceso a contabilidad para ver el Libro Diario.</p>
                    @endcan
                @else
                    <p class="text-sm text-gray-500">Sin asiento (borrador o anulada sin movimiento).</p>
                @endif
                @if($taxReturn->cancellationJournalEntry)
                    <p class="text-sm text-gray-600 mt-3">Reverso: asiento #{{ $taxReturn->cancellationJournalEntry->number }}</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-900">Líneas de aplicación</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500"><tr>
                        <th class="px-6 py-3 text-left">Origen</th>
                        <th class="px-6 py-3 text-right">Monto</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($taxReturn->applications as $app)
                            <tr>
                                <td class="px-6 py-3">{{ $app->sourceLabel() }}</td>
                                <td class="px-6 py-3 text-right">${{ number_format($app->amount_applied, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-6 py-8 text-center text-gray-500">Sin líneas de aplicación.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
