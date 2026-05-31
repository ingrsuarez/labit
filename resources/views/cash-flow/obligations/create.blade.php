<x-admin-layout>
    <div class="p-4 md:p-6 max-w-lg">
        <div class="mb-6 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nueva obligación manual</h1>
                <p class="text-sm text-gray-500 mt-1">E-cheq, plan ARCA, ganancias o cuota de equipo</p>
            </div>
            <a href="{{ route('cash-flow.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Calendario</a>
        </div>

        @include('cash-flow.obligations._form', [
            'action' => route('cash-flow.obligations.store'),
            'method' => 'POST',
            'categories' => $categories,
        ])
    </div>
</x-admin-layout>
