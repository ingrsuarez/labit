<x-manage>
    <div class="max-w-7xl mx-auto p-6">
        {{-- Encabezado con título y botón --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Empleados</h1>
            <a href="{{ route('employee.new') }}" 
               class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nuevo Empleado
            </a>
        </div>

        {{-- Filtros --}}
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white p-4 rounded-xl shadow mb-6">
            {{-- Búsqueda --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Buscar</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                    placeholder="Nombre, apellido, legajo o email"
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            {{-- Estado --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Estado</label>
                <select name="status"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Todos —</option>
                    <option value="active"   @selected(($filters['status'] ?? '') === 'active')>Activo</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactivo</option>
                </select>
            </div>

            {{-- Puesto --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Puesto</label>
                <select name="job_id"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Todos —</option>
                    @foreach($jobs as $job)
                        <option value="{{ $job->id }}" @selected(($filters['job_id'] ?? '') == $job->id)>{{ $job->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-4 flex gap-3 justify-end">
                <a href="{{ route('employee.show') }}"
                class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Limpiar</a>
                <button class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Filtrar</button>
            </div>
        </form>

        {{-- Tarjetas resumen --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Total</div>
                <div class="text-2xl font-semibold">{{ $summary['total'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Activos</div>
                <div class="text-2xl font-semibold text-green-600">{{ $summary['activos'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Inactivos</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $summary['inactivos'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Prom. horas/sem</div>
                <div class="text-2xl font-semibold">{{ $summary['promHoras'] }}</div>
            </div>
        </div>

        {{-- Top puestos --}}
        @if(($summary['topJobs'] ?? collect())->count())
            <div class="bg-white rounded-xl shadow p-4 mb-6">
                <div class="text-sm font-medium text-gray-700 mb-2">Puestos con más empleados</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($summary['topJobs'] as $tj)
                        <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-800 text-sm">
                            {{ $tj->name }} — {{ $tj->total }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
        
            {{-- @livewire('organization-chart', ['employees' => $employees, 'job' => $job ?? null]) --}}
            <!-- component -->
            {{-- @livewire('organization-chart',['employees'=>$employees, 'job'=>$job]) --}}


        </div>
        {{-- Tabla --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cuil</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puesto(s)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Depto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inicio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($employees as $e)
                        @php
                            $puestos = $e->jobs->pluck('name')->implode(', ');
                            $deptos  = $e->jobs->pluck('department')->filter()->unique()->implode(', ');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 capitalize">{{ $e->name }} {{ $e->lastName }}</div>
                                <div class="text-xs text-gray-500">#{{ $e->id }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $e->employeeId }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $puestos ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $deptos ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $e->email ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $e->start_date ? \Illuminate\Support\Carbon::parse($e->start_date)->format('Y-m-d') : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($e->status === 'active')
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Activo</span>
                                @elseif($e->status === 'inactive')
                                    <span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-700">Inactivo</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('employee.edit',$e )}}"
                                    class="text-blue-600 hover:text-blue-800 text-sm">Editar</a>
                                    <a href="{{ route('employee.show') }}?id={{ $e->id }}"
                                    class="text-gray-600 hover:text-gray-800 text-sm">Ver</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">Sin resultados.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-4 py-3 bg-gray-50">
                {{ $employees->links() }}
            </div>
        </div>
    </div>
</x-manage>