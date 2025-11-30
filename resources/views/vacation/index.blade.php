<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-7xl mx-auto">
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestión de Vacaciones</h1>
                    <p class="text-sm text-gray-500 mt-1">Panel de solicitudes y aprobaciones</p>
                </div>
                <div class="flex gap-3 mt-4 sm:mt-0">
                    <a href="{{ route('vacation.calendar') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Calendario
                    </a>
                    <a href="{{ route('vacation.approval') }}" 
                       class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Panel de Aprobación
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 border border-green-300 text-green-800 rounded-xl">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-xl">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Resumen --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="text-sm text-gray-500">Total Solicitudes</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $summary['total_requests'] }}</div>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="text-sm text-gray-500">Pendientes</div>
                    <div class="text-2xl font-bold text-amber-600">{{ $summary['pending'] }}</div>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="text-sm text-gray-500">Aprobadas</div>
                    <div class="text-2xl font-bold text-green-600">{{ $summary['approved'] }}</div>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="text-sm text-gray-500">Rechazadas</div>
                    <div class="text-2xl font-bold text-red-600">{{ $summary['rejected'] }}</div>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="text-sm text-gray-500">Días Otorgados</div>
                    <div class="text-2xl font-bold text-blue-600">{{ $summary['total_days'] }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Formulario de Nueva Solicitud --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Nueva Solicitud
                            </h2>
                        </div>
                        <form action="{{ route('vacation.store') }}" method="POST" class="p-6 space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Empleado *</label>
                                <select name="employee_id" id="employee_select" required
                                        onchange="updateVacationInfo(this.value)"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Seleccionar empleado...</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" 
                                                data-total="{{ $emp->vacation_days_by_law }}"
                                                data-used="{{ $emp->getUsedVacationDays() }}"
                                                data-pending="{{ $emp->getPendingVacationDays() }}"
                                                data-available="{{ $emp->getAvailableVacationDays() }}"
                                                data-antiquity="{{ $emp->antiquity_years }}">
                                            {{ $emp->lastName }}, {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            {{-- Info de días disponibles --}}
                            <div id="vacation_info" class="hidden p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <span class="text-gray-600">Antigüedad:</span>
                                        <span id="info_antiquity" class="font-medium text-gray-900">-</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Por ley:</span>
                                        <span id="info_total" class="font-medium text-gray-900">-</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Usados:</span>
                                        <span id="info_used" class="font-medium text-green-700">-</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Pendientes:</span>
                                        <span id="info_pending" class="font-medium text-amber-700">-</span>
                                    </div>
                                </div>
                                <div class="mt-2 pt-2 border-t border-blue-200 flex justify-between">
                                    <span class="font-medium text-gray-700">Disponibles:</span>
                                    <span id="info_available" class="font-bold text-blue-700 text-lg">-</span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Desde *</label>
                                    <input type="date" name="start" id="start_date" required min="{{ now()->format('Y-m-d') }}"
                                           onchange="calculateDays()"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hasta *</label>
                                    <input type="date" name="end" id="end_date" required
                                           onchange="calculateDays()"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            
                            {{-- Días a solicitar --}}
                            <div id="days_info" class="hidden text-center p-2 bg-gray-100 rounded-lg">
                                <span class="text-sm text-gray-600">Días a solicitar:</span>
                                <span id="days_count" class="font-bold text-gray-900 ml-1">0</span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                                <textarea name="description" rows="2"
                                          class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Opcional..."></textarea>
                            </div>
                            <button type="submit" 
                                    class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                Crear Solicitud
                            </button>
                        </form>
                    </div>
                    
                    {{-- Tabla de días disponibles --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden mt-6">
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Días Disponibles {{ now()->year }}
                            </h2>
                        </div>
                        <div class="p-4 max-h-80 overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="text-left px-2 py-2 font-medium text-gray-600">Empleado</th>
                                        <th class="text-center px-2 py-2 font-medium text-gray-600">Disp.</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($employees->sortBy('lastName') as $emp)
                                        @php
                                            $available = $emp->getAvailableVacationDays();
                                            $total = $emp->vacation_days_by_law;
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-2 py-2">
                                                <div class="font-medium text-gray-900">{{ $emp->lastName }}</div>
                                                <div class="text-xs text-gray-500">{{ $emp->antiquity_years }} años</div>
                                            </td>
                                            <td class="text-center px-2 py-2">
                                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold
                                                    {{ $available === 0 ? 'bg-red-100 text-red-700' : ($available < 7 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                                                    {{ $available }}
                                                </span>
                                                <div class="text-xs text-gray-400">/ {{ $total }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Solicitudes Pendientes --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Solicitudes Pendientes ({{ $pendingRequests->count() }})
                            </h2>
                        </div>
                        <div class="p-6">
                            @if($pendingRequests->count())
                                <div class="space-y-3">
                                    @foreach($pendingRequests as $request)
                                        <div class="flex items-center justify-between p-4 bg-amber-50 border border-amber-200 rounded-xl">
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900">
                                                    {{ $request->employee->lastName }}, {{ $request->employee->name }}
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    {{ \Carbon\Carbon::parse($request->start)->format('d/m/Y') }} - 
                                                    {{ \Carbon\Carbon::parse($request->end)->format('d/m/Y') }}
                                                    <span class="ml-2 px-2 py-0.5 bg-amber-200 text-amber-800 rounded text-xs">
                                                        {{ $request->days }} días
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <a href="{{ route('vacation.pdf', $request) }}" 
                                                   class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                   title="Descargar PDF">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </a>
                                                <form action="{{ route('vacation.approve', $request) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                                            title="Aprobar">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                                <button type="button" 
                                                        onclick="openRejectModal({{ $request->id }})"
                                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                        title="Rechazar">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-8">No hay solicitudes pendientes</p>
                            @endif
                        </div>
                    </div>

                    {{-- Vacaciones en Curso --}}
                    @if($current->count())
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden mt-6">
                            <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                                <h2 class="text-lg font-semibold text-white flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                    </svg>
                                    En Vacaciones Actualmente
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="space-y-3">
                                    @foreach($current as $vac)
                                        <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-xl">
                                            <div>
                                                <div class="font-medium text-gray-900">
                                                    {{ $vac->employee->lastName }}, {{ $vac->employee->name }}
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    Hasta {{ \Carbon\Carbon::parse($vac->end)->format('d/m/Y') }}
                                                    ({{ \Carbon\Carbon::parse($vac->end)->diffInDays(now()) + 1 }} días restantes)
                                                </div>
                                            </div>
                                            <span class="px-3 py-1 bg-green-500 text-white rounded-full text-sm">En curso</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Próximas Vacaciones Aprobadas --}}
                    @if($approvedFuture->count())
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden mt-6">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                                <h2 class="text-lg font-semibold text-white flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Próximas Vacaciones Aprobadas
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="space-y-3">
                                    @foreach($approvedFuture->take(5) as $vac)
                                        <div class="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-xl">
                                            <div>
                                                <div class="font-medium text-gray-900">
                                                    {{ $vac->employee->lastName }}, {{ $vac->employee->name }}
                                                </div>
                                                <div class="text-sm text-gray-600">
                                                    {{ \Carbon\Carbon::parse($vac->start)->format('d/m/Y') }} - 
                                                    {{ \Carbon\Carbon::parse($vac->end)->format('d/m/Y') }}
                                                    <span class="ml-2 px-2 py-0.5 bg-blue-200 text-blue-800 rounded text-xs">
                                                        {{ $vac->days }} días
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                En {{ \Carbon\Carbon::parse($vac->start)->diffInDays(now()) }} días
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
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
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
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
        
        function updateVacationInfo(employeeId) {
            const infoDiv = document.getElementById('vacation_info');
            
            if (!employeeId) {
                infoDiv.classList.add('hidden');
                return;
            }
            
            const select = document.getElementById('employee_select');
            const option = select.options[select.selectedIndex];
            
            document.getElementById('info_antiquity').textContent = option.dataset.antiquity + ' años';
            document.getElementById('info_total').textContent = option.dataset.total + ' días';
            document.getElementById('info_used').textContent = option.dataset.used + ' días';
            document.getElementById('info_pending').textContent = option.dataset.pending + ' días';
            document.getElementById('info_available').textContent = option.dataset.available + ' días';
            
            infoDiv.classList.remove('hidden');
        }
        
        function calculateDays() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const daysInfo = document.getElementById('days_info');
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const diffTime = end - start;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                if (diffDays > 0) {
                    document.getElementById('days_count').textContent = diffDays;
                    daysInfo.classList.remove('hidden');
                    
                    // Verificar si excede los disponibles
                    const select = document.getElementById('employee_select');
                    if (select.value) {
                        const option = select.options[select.selectedIndex];
                        const available = parseInt(option.dataset.available);
                        const daysSpan = document.getElementById('days_count');
                        
                        if (diffDays > available) {
                            daysSpan.classList.add('text-red-600');
                            daysSpan.classList.remove('text-gray-900');
                        } else {
                            daysSpan.classList.remove('text-red-600');
                            daysSpan.classList.add('text-gray-900');
                        }
                    }
                } else {
                    daysInfo.classList.add('hidden');
                }
            } else {
                daysInfo.classList.add('hidden');
            }
        }
    </script>
</x-manage>

