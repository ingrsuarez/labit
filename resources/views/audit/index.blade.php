<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Auditoría del Sistema</h1>
                <p class="text-gray-500 text-sm mt-1">Registro de actividad de todos los módulos</p>
            </div>
            <div class="mt-3 md:mt-0 text-sm text-gray-500">
                {{ $logs->total() }} registros encontrados
            </div>
        </div>

        <form method="GET" action="{{ route('audit.index') }}">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Buscar en descripción..."
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500 pl-9">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <div>
                        <select name="user_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Todos los usuarios</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select name="action" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Todas las acciones</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                                    {{ \App\Models\AuditLog::ACTION_LABELS[$action]['label'] ?? ucfirst($action) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select name="module" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Todos los módulos</option>
                            @foreach($modules as $module)
                                <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>
                                    {{ \App\Models\AuditLog::MODULE_NAMES['App\\Models\\' . $module] ?? $module }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               placeholder="Desde"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>

                    <div>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               placeholder="Hasta"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-3">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filtrar
                    </button>
                    <a href="{{ route('audit.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        Limpiar
                    </a>
                </div>
            </div>
        </form>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Módulo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($logs as $log)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                                        {{ $log->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                        {{ $log->user_name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @php
                                            $colorClasses = match($log->action_color) {
                                                'green'  => 'bg-green-100 text-green-700',
                                                'blue'   => 'bg-blue-100 text-blue-700',
                                                'red'    => 'bg-red-100 text-red-700',
                                                'teal'   => 'bg-teal-100 text-teal-700',
                                                'yellow' => 'bg-yellow-100 text-yellow-700',
                                                default  => 'bg-gray-100 text-gray-700',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClasses }}">
                                            {{ $log->action_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 max-w-md truncate">
                                        {{ $log->description }}
                                    </td>
                                    <td class="px-4 py-3 text-sm whitespace-nowrap">
                                        @if($log->auditable_type)
                                            @if($log->auditable_url)
                                                <a href="{{ $log->auditable_url }}" class="text-zinc-700 hover:text-zinc-900 hover:underline font-medium">
                                                    {{ $log->module_name }} #{{ $log->auditable_id }}
                                                </a>
                                            @else
                                                <span class="text-gray-500">{{ $log->module_name }} #{{ $log->auditable_id }}</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">
                                        {{ $log->ip_address ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <p class="text-sm text-gray-600">
                            Mostrando {{ $logs->firstItem() }}–{{ $logs->lastItem() }} de {{ $logs->total() }} registros
                        </p>
                        {{ $logs->links() }}
                    </div>
                </div>
            @else
                <div class="px-6 py-16 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No se encontraron registros de actividad</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(request()->hasAny(['search', 'user_id', 'action', 'module', 'date_from', 'date_to']))
                            Probá ajustando los filtros de búsqueda.
                        @else
                            Todavía no hay actividad registrada en el sistema.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
