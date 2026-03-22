<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="mb-6">
            <a href="{{ route('companies.show', $company) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Volver al detalle</a>
            <h2 class="text-xl font-bold text-gray-800 mt-2">Editar: {{ $company->name }}</h2>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-6 max-w-2xl">
            <form action="{{ route('companies.update', $company) }}" method="POST">
                @csrf
                @method('PUT')
                @include('companies._form', ['company' => $company])
                <div class="flex justify-end mt-6 space-x-3">
                    <a href="{{ route('companies.show', $company) }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancelar</a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-zinc-800 rounded-lg hover:bg-zinc-700 transition-colors">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
