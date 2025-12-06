<x-admin-layout title="Puestos de Trabajo">
    <div class="p-6 space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Puestos de Trabajo</h1>
                <p class="text-gray-500">Gestión de la estructura organizacional</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('manage.chart') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    Ver Organigrama
                </a>
                <a href="{{ route('job.new') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo Puesto
                </a>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Puestos</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $jobs->count() }}</p>
                    </div>
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Categorías</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $categories->count() }}</p>
                    </div>
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Departamentos</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $jobs->pluck('department')->filter()->unique()->count() }}</p>
                    </div>
                    <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Con Empleados</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $jobs->filter(fn($j) => $j->employees->count() > 0)->count() }}</p>
                    </div>
                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <!-- Búsqueda -->
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" 
                               id="searchInput"
                               placeholder="Buscar puesto por nombre, departamento..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Filtro por Categoría -->
                <div class="md:w-48">
                    <select id="filterCategory" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Todas las categorías</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ ucwords($category->name) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro por Departamento -->
                <div class="md:w-48">
                    <select id="filterDepartment" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Todos los departamentos</option>
                        @foreach($jobs->pluck('department')->filter()->unique()->sort() as $dept)
                            <option value="{{ $dept }}">{{ ucwords($dept) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Listado de Puestos -->
        @if($jobs->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">No hay puestos registrados</h3>
                <p class="text-gray-500 mt-1">Comienza creando el primer puesto de trabajo</p>
                <a href="{{ route('job.new') }}" class="inline-flex items-center px-4 py-2 mt-4 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Crear Puesto
                </a>
            </div>
        @else
            <!-- Grid de Puestos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="jobsGrid">
                @foreach($jobs as $job)
                    <div class="job-card bg-white rounded-xl shadow-sm border hover:shadow-md transition-shadow"
                         data-name="{{ strtolower($job->name) }}"
                         data-department="{{ strtolower($job->department ?? '') }}"
                         data-category="{{ $job->category_id }}">
                        <div class="p-5">
                            <!-- Header del puesto -->
                            <div class="flex items-start justify-between">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                        {{ strtoupper(substr($job->name, 0, 2)) }}
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="font-semibold text-gray-900">{{ ucwords($job->name) }}</h3>
                                        <p class="text-sm text-gray-500">{{ ucwords($job->department ?? 'Sin departamento') }}</p>
                                    </div>
                                </div>
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false" 
                                         x-transition
                                         class="absolute right-0 mt-1 w-36 bg-white rounded-lg shadow-lg border py-1 z-10">
                                        <a href="{{ route('job.edit', $job->id) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Editar
                                        </a>
                                        <a href="{{ route('job.delete', ['job' => $job->id]) }}" 
                                           onclick="return confirm('¿Eliminar este puesto?')"
                                           class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                            Eliminar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Info del puesto -->
                            <div class="mt-4 space-y-2">
                                @if($job->category)
                                    <div class="flex items-center text-sm">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        <span class="text-gray-600">{{ ucwords($job->category->name) }}</span>
                                    </div>
                                @endif
                                
                                @if($job->parent)
                                    <div class="flex items-center text-sm">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                        </svg>
                                        <span class="text-gray-600">Depende de: {{ ucwords($job->parent->name) }}</span>
                                    </div>
                                @endif

                                @if($job->email)
                                    <div class="flex items-center text-sm">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <a href="mailto:{{ $job->email }}" class="text-indigo-600 hover:underline truncate">{{ $job->email }}</a>
                                    </div>
                                @endif
                            </div>

                            <!-- Empleados en el puesto -->
                            <div class="mt-4 pt-4 border-t">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Empleados asignados</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $job->employees->count() > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $job->employees->count() }}
                                    </span>
                                </div>
                                @if($job->employees->count() > 0)
                                    <div class="flex -space-x-2 mt-2">
                                        @foreach($job->employees->take(5) as $emp)
                                            <div class="w-8 h-8 bg-gray-200 rounded-full border-2 border-white flex items-center justify-center text-xs font-medium text-gray-600" 
                                                 title="{{ $emp->full_name }}">
                                                {{ strtoupper(substr($emp->name, 0, 1)) }}
                                            </div>
                                        @endforeach
                                        @if($job->employees->count() > 5)
                                            <div class="w-8 h-8 bg-indigo-100 rounded-full border-2 border-white flex items-center justify-center text-xs font-medium text-indigo-600">
                                                +{{ $job->employees->count() - 5 }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Subordinados -->
                            @if($job->childs && $job->childs->count() > 0)
                                <div class="mt-3 pt-3 border-t">
                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                        {{ $job->childs->count() }} puesto(s) subordinado(s)
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Mensaje cuando no hay resultados del filtro -->
            <div id="noResults" class="hidden bg-white rounded-xl shadow-sm border p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">No se encontraron resultados</h3>
                <p class="text-gray-500 mt-1">Intenta con otros términos de búsqueda o filtros</p>
            </div>
        @endif

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const filterCategory = document.getElementById('filterCategory');
            const filterDepartment = document.getElementById('filterDepartment');
            const jobsGrid = document.getElementById('jobsGrid');
            const noResults = document.getElementById('noResults');
            const cards = document.querySelectorAll('.job-card');

            function filterCards() {
                const searchTerm = searchInput.value.toLowerCase();
                const categoryId = filterCategory.value;
                const department = filterDepartment.value.toLowerCase();
                
                let visibleCount = 0;

                cards.forEach(card => {
                    const name = card.dataset.name;
                    const cardDept = card.dataset.department;
                    const cardCategory = card.dataset.category;

                    const matchesSearch = name.includes(searchTerm) || cardDept.includes(searchTerm);
                    const matchesCategory = !categoryId || cardCategory === categoryId;
                    const matchesDepartment = !department || cardDept === department;

                    if (matchesSearch && matchesCategory && matchesDepartment) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (noResults && jobsGrid) {
                    if (visibleCount === 0) {
                        jobsGrid.classList.add('hidden');
                        noResults.classList.remove('hidden');
                    } else {
                        jobsGrid.classList.remove('hidden');
                        noResults.classList.add('hidden');
                    }
                }
            }

            if (searchInput) searchInput.addEventListener('input', filterCards);
            if (filterCategory) filterCategory.addEventListener('change', filterCards);
            if (filterDepartment) filterDepartment.addEventListener('change', filterCards);
        });
    </script>
</x-admin-layout>
