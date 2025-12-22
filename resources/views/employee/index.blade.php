<x-admin-layout title="Empleados">
    <div class="p-6 space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Empleados</h1>
                <p class="text-gray-500">Gestión y resumen de personal</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('employee.new') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nuevo Empleado
                </a>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Empleados -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Empleados</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $summary['total'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Activos -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Activos</p>
                        <p class="text-3xl font-bold text-green-600 mt-1">{{ $summary['activos'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Inactivos -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Inactivos</p>
                        <p class="text-3xl font-bold text-amber-600 mt-1">{{ $summary['inactivos'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Promedio Horas -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Prom. Horas/Sem</p>
                        <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $summary['promHoras'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Puestos -->
        @if(($summary['topJobs'] ?? collect())->count())
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Puestos con más empleados</h3>
                <div class="flex flex-wrap gap-3">
                    @foreach($summary['topJobs'] as $tj)
                        <span class="inline-flex items-center px-4 py-2 rounded-full bg-gradient-to-r from-blue-50 to-indigo-50 text-gray-800 border border-blue-100">
                            <span class="font-medium">{{ $tj->name }}</span>
                            <span class="ml-2 px-2 py-0.5 bg-blue-600 text-white text-xs rounded-full">{{ $tj->total }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Búsqueda -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                            placeholder="Nombre, apellido, legajo o email"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— Todos —</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Activo</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactivo</option>
                        </select>
                    </div>

                    <!-- Puesto -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Puesto</label>
                        <select name="job_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— Todos —</option>
                            @foreach($jobs as $job)
                                <option value="{{ $job->id }}" @selected(($filters['job_id'] ?? '') == $job->id)>{{ $job->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('employee.show') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Limpiar
                    </a>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de Empleados -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empleado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CUIL</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puesto(s)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departamento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ingreso</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($employees as $e)
                            @php
                                $puestos = $e->jobs->pluck('name')->implode(', ');
                                $deptos = $e->jobs->pluck('department')->filter()->unique()->implode(', ');
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold mr-3">
                                            {{ strtoupper(substr($e->name, 0, 1)) }}{{ strtoupper(substr($e->lastName, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 capitalize">{{ $e->name }} {{ $e->lastName }}</div>
                                            <div class="text-xs text-gray-500">#{{ $e->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600">{{ $e->employeeId ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm text-gray-600">{{ $puestos ?: '—' }}</td>
                                <td class="px-4 py-4 text-sm text-gray-600">{{ $deptos ?: '—' }}</td>
                                <td class="px-4 py-4 text-sm text-gray-600">{{ $e->email ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    {{ $e->start_date ? \Carbon\Carbon::parse($e->start_date)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-4 py-4">
                                    @if($e->status === 'active')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Activo
                                        </span>
                                    @elseif($e->status === 'inactive')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            Inactivo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            —
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="inline-flex gap-2">
                                        <a href="{{ route('employee.profile', $e) }}" 
                                           class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                            Ver
                                        </a>
                                        <a href="{{ route('employee.edit', $e) }}" 
                                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </div>
                                        <p class="text-gray-500 mb-4">No se encontraron empleados</p>
                                        <a href="{{ route('employee.new') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                            + Nuevo Empleado
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($employees->hasPages())
                <div class="px-6 py-4 bg-gray-50 border-t">
                    {{ $employees->links() }}
                </div>
            @endif
        </div>

    </div>
</x-admin-layout>
