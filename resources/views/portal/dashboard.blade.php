<x-portal-layout title="Mi Perfil">
    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Mi Perfil</h1>
                    <p class="text-sm text-gray-500">Bienvenido, {{ $employee->name }}</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('portal.directory') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Directorio
                    </a>
                    @if($isSupervisor)
                        <a href="{{ route('portal.team') }}" 
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Mi Equipo ({{ $subordinatesCount }})
                        </a>
                    @endif
                </div>
            </div>

            {{-- Main Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Columna Principal --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Tarjeta de Información Personal --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                    {{ strtoupper(substr($employee->name, 0, 1)) }}{{ strtoupper(substr($employee->lastName, 0, 1)) }}
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-xl font-bold text-white capitalize">
                                        {{ $employee->name }} {{ $employee->lastName }}
                                    </h2>
                                    <p class="text-indigo-100">CUIL: {{ $employee->employeeId }}</p>
                                </div>
                                <div class="ml-auto">
                                    @if($employee->status === 'active')
                                        <span class="px-3 py-1 bg-green-500 text-white rounded-full text-sm font-medium">Activo</span>
                                    @else
                                        <span class="px-3 py-1 bg-amber-500 text-white rounded-full text-sm font-medium">Inactivo</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase">Email</label>
                                    <p class="text-gray-900">{{ $employee->email ?? '—' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase">Teléfono</label>
                                    <p class="text-gray-900">{{ $employee->phone ?? '—' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase">Sexo</label>
                                    <p class="text-gray-900 capitalize">{{ $employee->sex ?? '—' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase">Fecha de Nacimiento</label>
                                    <p class="text-gray-900">
                                        {{ $employee->birth ? \Carbon\Carbon::parse($employee->birth)->format('d/m/Y') : '—' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase">Dirección</label>
                                    <p class="text-gray-900">{{ $employee->address ?? '—' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase">Ciudad</label>
                                    <p class="text-gray-900">{{ $employee->city ?? '—' }}, {{ $employee->state ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Información Laboral --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Información Laboral
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase">Fecha de Ingreso</label>
                                    <p class="text-gray-900 font-medium">
                                        {{ $employee->start_date ? \Carbon\Carbon::parse($employee->start_date)->format('d/m/Y') : '—' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase">Antigüedad</label>
                                    <p class="text-gray-900 font-medium">{{ $antiguedadMeses }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase">Horas Semanales</label>
                                    <p class="text-gray-900 font-medium">{{ $employee->weekly_hours ?? '—' }} hs</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase">Categoría</label>
                                    <p class="text-gray-900 font-medium capitalize">{{ $category?->name ?? '—' }}</p>
                                </div>
                            </div>

                            {{-- Puestos --}}
                            @if($employee->jobs->count())
                                <div class="mt-6 pt-6 border-t">
                                    <label class="text-xs font-medium text-gray-500 uppercase mb-3 block">Puestos Asignados</label>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($employee->jobs as $job)
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-100 text-indigo-800 text-sm">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                                </svg>
                                                {{ $job->name }}
                                                @if($job->department)
                                                    <span class="ml-1 text-indigo-600">({{ $job->department }})</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Últimas Licencias --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Mis Últimas Licencias
                            </h3>
                        </div>
                        <div class="p-6">
                            @if($recentLeaves->count())
                                <div class="space-y-3">
                                    @foreach($recentLeaves as $leave)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div>
                                                <span class="font-medium text-gray-900 capitalize">{{ $leave->type }}</span>
                                                <span class="text-gray-500 text-sm ml-2">
                                                    {{ \Carbon\Carbon::parse($leave->start)->format('d/m/Y') }}
                                                    @if($leave->end)
                                                        - {{ \Carbon\Carbon::parse($leave->end)->format('d/m/Y') }}
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-sm">
                                                    {{ $leave->working_days ?? $leave->days }} días
                                                </span>
                                                @if($leave->status === 'aprobado')
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Aprobado</span>
                                                @elseif($leave->status === 'pendiente')
                                                    <span class="px-2 py-1 bg-amber-100 text-amber-800 rounded text-xs">Pendiente</span>
                                                @else
                                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">{{ ucfirst($leave->status) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-4">Sin licencias registradas</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Columna Lateral --}}
                <div class="space-y-6">
                    {{-- Tarjeta de Antigüedad --}}
                    <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white">
                        <div class="text-emerald-100 text-sm font-medium">Antigüedad</div>
                        <div class="text-4xl font-bold mt-1">{{ $antiguedad }}</div>
                        <div class="text-emerald-100 text-sm">años de servicio</div>
                        <div class="mt-4 pt-4 border-t border-emerald-400/30">
                            <div class="flex justify-between text-sm">
                                <span class="text-emerald-100">Porcentaje:</span>
                                <span class="font-semibold">{{ $antiguedadPorcentaje }}%</span>
                            </div>
                        </div>
                    </div>

                    {{-- Resumen de Licencias del Año --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gray-50">
                            <h3 class="font-semibold text-gray-900">Licencias {{ $currentYear }}</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Vacaciones</span>
                                <span class="font-semibold text-gray-900">{{ $leavesSummary['vacaciones'] }} días</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Enfermedad</span>
                                <span class="font-semibold text-gray-900">{{ $leavesSummary['enfermedad'] }} días</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Otros</span>
                                <span class="font-semibold text-gray-900">{{ $leavesSummary['otros'] }} días</span>
                            </div>
                            <div class="pt-4 border-t flex justify-between items-center">
                                <span class="font-medium text-gray-900">Total</span>
                                <span class="text-lg font-bold text-indigo-600">{{ $leavesSummary['total'] }} días</span>
                            </div>
                        </div>
                    </div>

                    {{-- Días de Vacaciones --}}
                    @php
                        $vacationSummary = $employee->getVacationSummary();
                    @endphp
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-indigo-100 text-sm font-medium">Vacaciones {{ $vacationSummary['year'] }}</div>
                            <span class="px-2 py-1 bg-white/20 rounded text-xs">
                                {{ $vacationSummary['antiquity_years'] }} años ant.
                            </span>
                        </div>
                        <div class="text-4xl font-bold">{{ $vacationSummary['available'] }}</div>
                        <div class="text-indigo-100 text-sm">días disponibles</div>
                        
                        <div class="mt-4 pt-4 border-t border-indigo-400/30 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-indigo-100">Por ley:</span>
                                <span class="font-semibold">{{ $vacationSummary['total_by_law'] }} días</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-indigo-100">Usados:</span>
                                <span class="font-semibold">{{ $vacationSummary['used'] }} días</span>
                            </div>
                            @if($vacationSummary['pending'] > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-indigo-100">Pendientes:</span>
                                    <span class="font-semibold text-amber-300">{{ $vacationSummary['pending'] }} días</span>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Barra de progreso --}}
                        <div class="mt-4">
                            @php
                                $usedPercent = $vacationSummary['total_by_law'] > 0 
                                    ? min(100, ($vacationSummary['used'] / $vacationSummary['total_by_law']) * 100) 
                                    : 0;
                                $pendingPercent = $vacationSummary['total_by_law'] > 0 
                                    ? min(100 - $usedPercent, ($vacationSummary['pending'] / $vacationSummary['total_by_law']) * 100) 
                                    : 0;
                            @endphp
                            <div class="h-2 bg-indigo-400/30 rounded-full overflow-hidden">
                                <div class="h-full flex">
                                    <div class="bg-white h-full transition-all" style="width: {{ $usedPercent }}%"></div>
                                    <div class="bg-amber-300 h-full transition-all" style="width: {{ $pendingPercent }}%"></div>
                                </div>
                            </div>
                            <div class="flex justify-between text-xs mt-1 text-indigo-200">
                                <span>Usados</span>
                                <span>Disponibles</span>
                            </div>
                        </div>
                    </div>

                    {{-- Badge de Supervisor --}}
                    @if($isSupervisor)
                        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-lg p-6 text-white">
                            <div class="flex items-center mb-3">
                                <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                <div>
                                    <div class="font-bold text-lg">Supervisor</div>
                                    <div class="text-amber-100 text-sm">{{ $subordinatesCount }} empleados a cargo</div>
                                </div>
                            </div>
                            <a href="{{ route('portal.team') }}" 
                               class="block w-full mt-4 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-center font-medium transition-colors">
                                Ver Mi Equipo
                            </a>
                        </div>
                    @endif

                    {{-- Accesos Rápidos --}}
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Accesos Rápidos</h3>
                        <div class="space-y-2">
                            <a href="{{ route('portal.directory', ['tab' => 'birthdays']) }}" 
                               class="flex items-center w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-3 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A1.5 1.5 0 003 15.546V12a9 9 0 0118 0v3.546zM12 3v2m0 0a2 2 0 100 4 2 2 0 000-4z"/>
                                </svg>
                                Cumpleaños
                            </a>
                            <a href="{{ route('portal.directory', ['tab' => 'vacations']) }}" 
                               class="flex items-center w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                                </svg>
                                Vacaciones Compañeros
                            </a>
                            <a href="{{ route('vacation.index') }}" 
                               class="flex items-center w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Solicitar Vacaciones
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-portal-layout>

