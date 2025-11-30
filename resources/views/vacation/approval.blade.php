<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-7xl mx-auto">
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('vacation.index') }}" 
                       class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Panel de Aprobación</h1>
                        <p class="text-sm text-gray-500 mt-1">Visualización de superposiciones y aprobación de solicitudes</p>
                    </div>
                </div>
            </div>

            {{-- Selector de Mes --}}
            <div class="bg-white rounded-xl shadow p-4 mb-6">
                <form method="GET" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                        <select name="month" class="rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $i => $mes)
                                <option value="{{ $i + 1 }}" {{ $month == ($i + 1) ? 'selected' : '' }}>{{ $mes }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                        <select name="year" class="rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Ver Mes
                    </button>
                </form>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-800 rounded-xl">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Alertas de Superposiciones --}}
            @if(count($overlaps) > 0)
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                    <h3 class="text-lg font-semibold text-red-800 flex items-center mb-3">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        ¡Atención! {{ count($overlaps) }} superposiciones (misma categoría/puesto)
                    </h3>
                    <p class="text-sm text-red-600 mb-3">Solo se muestran conflictos entre empleados con la misma categoría o puesto.</p>
                    <div class="space-y-2">
                        @foreach($overlaps as $overlap)
                            <div class="flex items-center p-3 bg-white rounded-lg border border-red-400">
                                <div class="flex-1">
                                    <span class="font-medium">{{ $overlap['request']->employee->lastName }}, {{ $overlap['request']->employee->name }}</span>
                                    <span class="text-gray-500 mx-2">↔</span>
                                    <span class="font-medium">{{ $overlap['conflict_with']->employee->lastName }}, {{ $overlap['conflict_with']->employee->name }}</span>
                                    <span class="ml-2 text-sm text-gray-600">({{ $overlap['overlap_days'] }} días superpuestos)</span>
                                </div>
                                <div class="flex gap-2">
                                    @if($overlap['same_category'] ?? false)
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">
                                            Misma categoría
                                        </span>
                                    @endif
                                    @if($overlap['same_job'] ?? false)
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">
                                            Mismo puesto
                                        </span>
                                    @endif
                                    @if($overlap['both_pending'] ?? false)
                                        <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-xs font-medium">
                                            Ambas pendientes
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Calendario Visual --}}
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white">
                            Calendario de {{ ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'][$month - 1] }} {{ $year }}
                        </h2>
                    </div>
                    <div class="p-4">
                        {{-- Cabecera días de la semana --}}
                        <div class="grid grid-cols-7 gap-1 mb-2">
                            @foreach(['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'] as $day)
                                <div class="text-center text-xs font-medium text-gray-500 py-2">{{ $day }}</div>
                            @endforeach
                        </div>
                        
                        {{-- Días del mes --}}
                        @php
                            $firstDay = \Carbon\Carbon::create($year, $month, 1);
                            $startPadding = $firstDay->dayOfWeek;
                        @endphp
                        <div class="grid grid-cols-7 gap-1">
                            {{-- Padding inicial --}}
                            @for($i = 0; $i < $startPadding; $i++)
                                <div class="h-16"></div>
                            @endfor
                            
                            {{-- Días --}}
                            @foreach($calendarData as $dayData)
                                @php
                                    $hasApproved = collect($dayData['vacations'])->where('status', 'aprobado')->count();
                                    $hasPending = collect($dayData['vacations'])->where('status', 'pendiente')->count();
                                    $isToday = $dayData['date']->isToday();
                                @endphp
                                <div class="h-16 p-1 border rounded-lg {{ $isToday ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }} 
                                            {{ $hasApproved && $hasPending ? 'bg-amber-50' : ($hasApproved ? 'bg-green-50' : ($hasPending ? 'bg-amber-50' : '')) }}">
                                    <div class="text-xs font-medium {{ $isToday ? 'text-blue-600' : 'text-gray-700' }}">
                                        {{ $dayData['date']->day }}
                                    </div>
                                    @if(count($dayData['vacations']) > 0)
                                        <div class="space-y-0.5 mt-1">
                                            @foreach(collect($dayData['vacations'])->take(2) as $vac)
                                                <div class="text-[10px] truncate px-1 rounded {{ $vac->status === 'aprobado' ? 'bg-green-200 text-green-800' : 'bg-amber-200 text-amber-800' }}">
                                                    {{ substr($vac->employee->lastName, 0, 6) }}
                                                </div>
                                            @endforeach
                                            @if(count($dayData['vacations']) > 2)
                                                <div class="text-[10px] text-gray-500 text-center">+{{ count($dayData['vacations']) - 2 }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Leyenda --}}
                        <div class="flex justify-center gap-4 mt-4 pt-4 border-t">
                            <div class="flex items-center text-xs">
                                <div class="w-3 h-3 bg-green-200 rounded mr-1"></div>
                                <span>Aprobadas</span>
                            </div>
                            <div class="flex items-center text-xs">
                                <div class="w-3 h-3 bg-amber-200 rounded mr-1"></div>
                                <span>Pendientes</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Solicitudes del Mes --}}
                <div class="space-y-6">
                    {{-- Pendientes --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Pendientes de Aprobación ({{ $pendingRequests->count() }})
                            </h2>
                        </div>
                        <div class="divide-y">
                            @forelse($pendingRequests as $request)
                                @php
                                    $hasOverlap = collect($overlaps)->where('request.id', $request->id)->count() > 0;
                                @endphp
                                <div class="p-4 {{ $hasOverlap ? 'bg-red-50' : '' }}">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900">
                                                {{ $request->employee->lastName }}, {{ $request->employee->name }}
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                {{ \Carbon\Carbon::parse($request->start)->format('d/m/Y') }} - 
                                                {{ \Carbon\Carbon::parse($request->end)->format('d/m/Y') }}
                                                <span class="ml-2 px-2 py-0.5 bg-amber-200 text-amber-800 rounded text-xs">
                                                    {{ $request->days }} días
                                                </span>
                                            </div>
                                            @if($request->employee->jobs->count())
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ $request->employee->jobs->pluck('department')->filter()->unique()->implode(', ') }}
                                                </div>
                                            @endif
                                            @if($hasOverlap)
                                                <div class="text-xs text-red-600 mt-1 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                    </svg>
                                                    Tiene superposición
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex gap-1">
                                            <a href="{{ route('vacation.pdf', $request) }}" 
                                               class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg"
                                               title="PDF">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </a>
                                            <form action="{{ route('vacation.approve', $request) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Aprobar">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            <button type="button" onclick="openRejectModal({{ $request->id }})"
                                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Rechazar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-gray-500">
                                    No hay solicitudes pendientes este mes
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Aprobadas --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Aprobadas ({{ $approvedVacations->count() }})
                            </h2>
                        </div>
                        <div class="divide-y max-h-64 overflow-y-auto">
                            @forelse($approvedVacations as $vac)
                                <div class="p-4">
                                    <div class="font-medium text-gray-900">
                                        {{ $vac->employee->lastName }}, {{ $vac->employee->name }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ \Carbon\Carbon::parse($vac->start)->format('d/m/Y') }} - 
                                        {{ \Carbon\Carbon::parse($vac->end)->format('d/m/Y') }}
                                        <span class="ml-2 px-2 py-0.5 bg-green-200 text-green-800 rounded text-xs">
                                            {{ $vac->days }} días
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-gray-500">
                                    No hay vacaciones aprobadas este mes
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Rechazo --}}
    <div id="rejectModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Rechazar Solicitud</h3>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo del rechazo *</label>
                    <textarea name="rejection_reason" required rows="3"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-red-500 focus:border-red-500"
                              placeholder="Ingrese el motivo..."></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeRejectModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Rechazar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(id) {
            document.getElementById('rejectForm').action = '/vacation/reject/' + id;
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }
    </script>
</x-manage>

