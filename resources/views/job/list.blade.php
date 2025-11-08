<x-manage>
    <div class="mx-auto max-w-7xl p-4 lg:p-6">
        {{-- Header --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Puestos</h2>
                <p class="text-sm text-gray-500">Listado de puestos actuales</p>
            </div>
            <div class="flex items-center gap-2">
                {{-- (Opcional) Buscador --}}
                {{-- 
                <form action="{{ route('job.index') }}" method="GET" class="hidden md:block">
                    <div class="relative">
                        <input name="q" placeholder="Buscar puesto..."
                            class="w-64 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="pointer-events-none absolute right-2 top-2.5">
                            <!-- icono -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m21 21-4.35-4.35M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16z" />
                            </svg>
                        </span>
                    </div>
                </form>
                --}}
                <a href="{{ route('job.new') }}"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <!-- plus icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-1.5 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                    </svg>
                    Nuevo
                </a>
            </div>
        </div>

        {{-- Contenido --}}
        <div class="mt-4">
            @forelse ($jobs as $job)
                {{-- Card compacta en móvil; tabla en desktop (grid responsive) --}}
                <div class="mb-3 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:hidden">
                    <div class="flex items-start gap-3 p-4">
                        {{-- “Avatar” con iniciales del puesto --}}
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 font-semibold">
                            {{ strtoupper(substr($job->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between">
                                <h3 class="truncate text-base font-semibold text-gray-900">{{ ucwords($job->name) }}</h3>
                                <span class="inline-flex shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                    {{ ucwords(optional($job->category)->name ?? 'Sin categoría') }}
                                </span>
                            </div>
                            <div class="mt-1 text-sm text-gray-600">
                                <p><span class="font-medium text-gray-700">Sector:</span> {{ ucwords($job->department) }}</p>
                                <p class="truncate">
                                    <span class="font-medium text-gray-700">Email:</span>
                                    <a href="mailto:{{ $job->email }}" class="text-indigo-600 hover:underline">{{ $job->email }}</a>
                                </p>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <a href="{{ route('job.edit', ['job' => $job->id]) }}"
                                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">
                                    Editar
                                </a>

                                {{-- Si usás DELETE (recomendado) --}}
                                <form action="{{ route('job.delete', ['job' => $job->id]) }}" method="POST"
                                    onsubmit="return confirm('¿Eliminar este puesto?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500">
                                        Eliminar
                                    </button>
                                </form>

                                {{-- Si borrás por GET, usá esto en lugar del form:
                                <a href="{{ route('job.delete', ['job' => $job->id]) }}"
                                class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500">
                                    Eliminar
                                </a>
                                --}}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Estado vacío --}}
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center shadow-sm">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7h18M3 12h18M3 17h18M12 5v14" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900">No existen puestos de trabajo</h3>
                    <p class="mt-1 text-sm text-gray-500">Crea el primer puesto del centro.</p>
                    <div class="mt-6">
                        <a href="{{ route('job.new') }}"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Crear puesto
                        </a>
                    </div>
                </div>
            @endforelse

            {{-- Tabla en desktop --}}
            @if($jobs->isNotEmpty())
                <div class="mt-4 hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                    <div class="max-h-[70vh] overflow-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="sticky top-0 z-10 bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                                <tr>
                                    <th class="px-4 py-3">Nombre</th>
                                    <th class="px-4 py-3">Categoría</th>
                                    <th class="px-4 py-3">Sector</th>
                                    <th class="px-4 py-3">Email</th>
                                    <th class="px-4 py-3 text-center" colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($jobs as $job)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ ucwords($job->name) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                                {{ ucwords(optional($job->category)->name ?? 'Sin categoría') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-100">
                                                {{ ucwords($job->department) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="mailto:{{ $job->email }}" class="text-indigo-600 hover:underline">
                                                {{ $job->email }}
                                            </a>
                                        </td>
                                        <td class="px-2 py-3">
                                            <a href="{{ route('job.edit', ['job' => $job->id]) }}"
                                            class="inline-flex w-full items-center justify-center rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">
                                                Editar
                                            </a>
                                        </td>
                                        <td class="px-2 py-3">
                                            <form action="{{ route('job.delete', ['job' => $job->id]) }}" method="POST"
                                                onsubmit="return confirm('¿Eliminar este puesto?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex w-full items-center justify-center rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500">
                                                    Eliminar
                                                </button>
                                            </form>

                                            {{-- Si usás GET:
                                            <a href="{{ route('job.delete', ['job' => $job->id]) }}"
                                            class="inline-flex w-full items-center justify-center rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500">
                                                Eliminar
                                            </a>
                                            --}}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- (Opcional) Paginación --}}
                    @if(method_exists($jobs, 'links'))
                        <div class="border-t border-gray-100 bg-white px-4 py-3">
                            {{ $jobs->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-manage>