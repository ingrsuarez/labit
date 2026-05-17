<x-admin-layout title="Recursos Humanos">
    <div class="p-6 space-y-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Recursos Humanos</h1>
                <p class="text-gray-500 text-sm mt-1">Accesos a personal, ausencias y liquidaciones</p>
            </div>
            @if (auth()->user()?->hasAnyRole(['admin', 'contador']))
                <a href="{{ route('rrhh.resumen') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Ver resumen →
                </a>
            @endif
        </div>

        @forelse ($sections as $section)
            <section id="{{ $section['key'] }}" class="border-b border-gray-200 pb-8 last:border-0 scroll-mt-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ $section['title'] }}</h2>
                <p class="text-sm text-gray-500 mt-0.5 mb-4">{{ $section['description'] }}</p>
                @include('admin.partials.section-cards', ['items' => $section['items']])
            </section>
        @empty
            <p class="text-sm text-gray-500">No tenés secciones de RRHH habilitadas.</p>
        @endforelse
    </div>
</x-admin-layout>
