<x-admin-layout title="Inicio">
    <div class="p-6 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Hola, {{ auth()->user()->name }}
            </h1>
            <p class="text-gray-500 text-sm mt-1">Tus accesos más utilizados</p>
        </div>

        @if (count($shortcuts) > 0)
            @include('admin.partials.section-cards', ['items' => $shortcuts])
        @else
            <p class="text-sm text-gray-500">No tenés módulos habilitados.</p>
        @endif
    </div>
</x-admin-layout>
