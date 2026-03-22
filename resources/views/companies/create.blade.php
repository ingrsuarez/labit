<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="mb-6">
            <a href="{{ route('companies.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Volver a empresas</a>
            <h2 class="text-xl font-bold text-gray-800 mt-2">Nueva Empresa</h2>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-6 max-w-2xl">
            <form action="{{ route('companies.store') }}" method="POST">
                @csrf
                @include('companies._form')
                <div class="flex justify-end mt-6 space-x-3">
                    <a href="{{ route('companies.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancelar</a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-zinc-800 rounded-lg hover:bg-zinc-700 transition-colors">Crear Empresa</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
