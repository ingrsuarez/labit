<x-lab-layout title="Resultados pendientes">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Resultados pendientes</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Protocolos con al menos una práctica sin resultado cargado. Clic en el protocolo para abrir la carga.
                </p>
            </div>
            <a href="{{ route('lab.admissions.index') }}"
               class="inline-flex shrink-0 items-center text-sm font-medium text-teal-700 hover:underline">
                Ir a admisiones
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <form action="{{ route('lab.admissions.pending-results') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Buscar por protocolo, nombre o DNI..."
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div class="w-48">
                    <select name="insurance" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Todas las OS</option>
                        @foreach($insurances as $ins)
                            <option value="{{ $ins->id }}" {{ request('insurance') == $ins->id ? 'selected' : '' }}>
                                {{ strtoupper($ins->displayName()) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Todos los estados</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>En Proceso</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completado</option>
                        <option value="validated" {{ request('status') === 'validated' ? 'selected' : '' }}>Validado</option>
                        <option value="enviado" {{ request('status') === 'enviado' ? 'selected' : '' }}>Enviado</option>
                    </select>
                </div>
                @if(isset($branches) && $branches->count() > 1)
                    <div class="w-48">
                        <select name="lab_branch_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                            <option value="all" {{ request('lab_branch_id') === 'all' ? 'selected' : '' }}>Todas las sedes</option>
                            <option value="none" {{ request('lab_branch_id') === 'none' ? 'selected' : '' }}>Sin sede</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (request('lab_branch_id') == $branch->id || (!request()->has('lab_branch_id') && active_lab_branch_id() == $branch->id)) ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'insurance', 'status', 'lab_branch_id']))
                    <a href="{{ route('lab.admissions.pending-results') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700 self-center">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($admissions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocolo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Fecha</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Determinaciones pendientes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($admissions as $admission)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <a href="{{ route('lab.admissions.show', $admission) }}#lab-admission-results"
                                           class="text-teal-700 hover:underline font-medium">
                                            {{ $admission->protocol_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if($admission->patient)
                                            {{ $admission->patient->full_name }}
                                        @else
                                            <span class="text-gray-400">Sin paciente</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                        {{ $admission->date?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800 max-w-xl">
                                        <span class="break-words" title="{{ e($admission->pending_determinations_label ?? '') }}">
                                            {{ $admission->pending_determinations_label ?? '' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    {{ $admissions->withQueryString()->links() }}
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <p class="text-gray-700 font-medium">No hay protocolos con resultados pendientes.</p>
                    <p class="mt-2 text-gray-500 text-sm">Cuando falte cargar algún resultado, aparecerán aquí.</p>
                </div>
            @endif
        </div>
    </div>
</x-lab-layout>
