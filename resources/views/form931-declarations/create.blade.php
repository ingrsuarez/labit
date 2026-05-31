<x-admin-layout>
    <div class="p-4 md:p-6 max-w-3xl">
        <div class="mb-6 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nueva DDJJ Form 931</h1>
                <p class="text-gray-500 text-sm mt-1">Cargá aportes y contribuciones patronales del período</p>
            </div>
            <a href="{{ route('form931-declarations.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Volver</a>
        </div>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @include('form931-declarations._form', [
            'action' => route('form931-declarations.store'),
            'method' => 'POST',
            'declaration' => null,
        ])
    </div>
</x-admin-layout>
