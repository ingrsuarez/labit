<x-admin-layout>
    <div class="p-4 md:p-6 max-w-4xl">
        <div class="mb-6 flex flex-wrap justify-between gap-4 items-start">
            <div>
                <nav class="text-sm text-gray-500 mb-1">
                    <a href="{{ route('form931-declarations.index') }}" class="hover:text-gray-700">Form 931</a>
                    <span class="mx-1">›</span>
                    <span class="text-gray-700 font-medium">{{ $declaration->period_label }}</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-800">DDJJ Form 931 — {{ $declaration->period_label }}</h1>
                <p class="text-gray-500 text-sm mt-1">
                    @if($declaration->status === 'draft')
                        <span class="text-yellow-700 font-medium">Borrador</span>
                    @elseif($declaration->status === 'confirmed')
                        <span class="text-green-700 font-medium">Confirmada</span>
                    @else
                        <span class="text-gray-700 font-medium">Anulada</span>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('form931.manage')
                    @if($declaration->isDraft())
                        <a href="{{ route('form931-declarations.edit', $declaration) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">Editar borrador</a>
                        <form method="POST" action="{{ route('form931-declarations.confirm', $declaration) }}" onsubmit="return confirm('¿Confirmar DDJJ y generar asiento?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">Confirmar DDJJ</button>
                        </form>
                        <form method="POST" action="{{ route('form931-declarations.destroy', $declaration) }}" onsubmit="return confirm('¿Eliminar borrador?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">Eliminar</button>
                        </form>
                    @endif
                    @if($declaration->isConfirmed())
                        <form method="POST" action="{{ route('form931-declarations.cancel', $declaration) }}" onsubmit="return confirm('¿Anular y generar reverso?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm rounded-lg hover:bg-orange-700">Anular DDJJ</button>
                        </form>
                    @endif
                @endcan
                <a href="{{ route('form931-declarations.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">&larr; Listado</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Montos</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Aportes patronales</dt><dd class="font-medium">${{ number_format($declaration->amount_aportes_patronales, 2, ',', '.') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Contribuciones patronales</dt><dd class="font-medium">${{ number_format($declaration->amount_contribuciones_patronales, 2, ',', '.') }}</dd></div>
                    <div class="flex justify-between border-t border-gray-100 pt-2"><dt class="text-gray-700 font-medium">Total</dt><dd class="font-bold text-gray-900">${{ number_format($declaration->total, 2, ',', '.') }}</dd></div>
                </dl>
                @if($declaration->notes)
                    <p class="mt-4 text-sm text-gray-600 border-t border-gray-100 pt-3">{{ $declaration->notes }}</p>
                @endif
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Asientos</h2>
                @if($declaration->journalEntry)
                    <p class="text-sm text-gray-700 mb-2">
                        Asiento #{{ $declaration->journalEntry->number }} — {{ $declaration->journalEntry->date->format('d/m/Y') }}
                    </p>
                    @can('contabilidad.section')
                        <a href="{{ route('accounting.journal.index', ['search' => 'Form 931']) }}" class="text-indigo-600 text-sm hover:underline">Ver en Libro Diario</a>
                    @else
                        <p class="text-xs text-gray-500">Pedí acceso a contabilidad para ver el Libro Diario.</p>
                    @endcan
                @else
                    <p class="text-sm text-gray-500">Sin asiento (borrador o anulada sin movimiento).</p>
                @endif
                @if($declaration->cancellationJournalEntry)
                    <p class="text-sm text-gray-600 mt-3">Reverso: asiento #{{ $declaration->cancellationJournalEntry->number }}</p>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
