<x-admin-layout>
    <div class="p-4 md:p-6 max-w-lg">
        <div class="mb-6 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editar obligación</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $obligation->title }}</p>
            </div>
            <a href="{{ route('cash-flow.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Calendario</a>
        </div>

        @include('cash-flow.obligations._form', [
            'action' => route('cash-flow.obligations.update', $obligation),
            'method' => 'PUT',
            'obligation' => $obligation,
            'categories' => $categories,
        ])

        <form method="POST" action="{{ route('cash-flow.obligations.destroy', $obligation) }}" class="mt-4" onsubmit="return confirm('¿Eliminar obligación?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Eliminar obligación</button>
        </form>
    </div>
</x-admin-layout>
