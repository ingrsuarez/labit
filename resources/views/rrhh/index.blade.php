<x-admin-layout title="Recursos Humanos">
    <div class="p-6 space-y-10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Recursos Humanos</h1>
                <p class="text-gray-500 text-sm mt-1">Accesos a personal, licencias y liquidaciones</p>
            </div>
            @if (auth()->user()?->hasAnyRole(['admin', 'contador']))
                <a href="{{ route('rrhh.resumen') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Ver resumen →
                </a>
            @endif
        </div>

        @forelse ($sections as $section)
            <section id="{{ $section['key'] }}" @class([
                'scroll-mt-6 space-y-4',
                'pt-8 mt-2 border-t border-zinc-200/80' => ! $loop->first,
            ])>
                <div class="flex flex-col gap-2 rounded-xl border border-zinc-200/90 bg-gradient-to-r from-zinc-100 via-zinc-50 to-zinc-100 px-4 py-3.5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="hidden h-9 w-1 shrink-0 rounded-full bg-zinc-500 sm:block" aria-hidden="true"></span>
                        <h2 class="text-base font-semibold tracking-tight text-zinc-900">{{ $section['title'] }}</h2>
                    </div>
                    <p class="text-sm leading-snug text-zinc-600 sm:max-w-md sm:text-right lg:max-w-xl">{{ $section['description'] }}</p>
                </div>
                @include('admin.partials.section-cards', ['items' => $section['items']])
            </section>
        @empty
            <p class="text-sm text-gray-500">No tenés secciones de RRHH habilitadas.</p>
        @endforelse
    </div>
</x-admin-layout>
