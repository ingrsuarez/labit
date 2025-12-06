<x-portal-layout title="Mi Equipo">
    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('portal.dashboard') }}" 
                       class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Mi Equipo</h1>
                        <p class="text-sm text-gray-500">Empleados a tu cargo</p>
                    </div>
                </div>
                <div class="flex gap-3 mt-4 sm:mt-0">
                    <a href="{{ route('portal.directory') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Directorio General
                    </a>
                </div>
            </div>

            {{-- Resumen Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Equipo</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $teamSummary['total'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Activos</p>
                            <p class="text-2xl font-bold text-green-600">{{ $teamSummary['activos'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-amber-100 text-amber-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Licencias Pendientes</p>
                            <p class="text-2xl font-bold text-amber-600">{{ $pendingLeaves->count() }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">De Vacaciones</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $currentVacations->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Columna Principal --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Licencias Pendientes de Aprobación --}}
                    @if($pendingLeaves->count() > 0)
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4">
                                <h2 class="text-lg font-semibold text-white flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Licencias Pendientes de Aprobación
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="space-y-3">
                                    @foreach($pendingLeaves as $leave)
                                        <div class="flex items-center justify-between p-4 bg-amber-50 border border-amber-200 rounded-xl">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-amber-200 rounded-full flex items-center justify-center text-amber-700 font-bold">
                                                    {{ strtoupper(substr($leave->employee->name, 0, 1)) }}{{ strtoupper(substr($leave->employee->lastName, 0, 1)) }}
                                                </div>
                                                <div class="ml-3">
                                                    <p class="font-medium text-gray-900">{{ $leave->employee->name }} {{ $leave->employee->lastName }}</p>
                                                    <p class="text-sm text-gray-500">
                                                        {{ ucfirst($leave->type) }} • 
                                                        {{ \Carbon\Carbon::parse($leave->start)->format('d/m/Y') }} - 
                                                        {{ \Carbon\Carbon::parse($leave->end)->format('d/m/Y') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <span class="px-3 py-1 bg-amber-500 text-white rounded-full text-sm font-medium">
                                                    {{ $leave->working_days ?? $leave->days }} días
                                                </span>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Solicitado {{ \Carbon\Carbon::parse($leave->created_at)->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-4 text-center">
                                    <a href="{{ route('vacation.approval') }}" 
                                       class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
                                        Gestionar Aprobaciones
                                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Lista de Empleados del Equipo --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white">Empleados del Equipo</h2>
                        </div>
                        <div class="p-6">
                            @if($subordinates->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($subordinates as $sub)
                                        @php
                                            $isOnVacation = $currentVacations->where('employee_id', $sub->id)->count() > 0;
                                            $leavesDaysUsed = $sub->leaves->sum('days');
                                        @endphp
                                        <div class="p-4 border rounded-xl hover:shadow-md transition-shadow {{ $isOnVacation ? 'bg-blue-50 border-blue-200' : 'border-gray-200' }}">
                                            <div class="flex items-start justify-between">
                                                <div class="flex items-center">
                                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold
                                                        {{ $sub->status === 'active' ? 'bg-indigo-500' : 'bg-gray-400' }}">
                                                        {{ strtoupper(substr($sub->name, 0, 1)) }}{{ strtoupper(substr($sub->lastName, 0, 1)) }}
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="font-semibold text-gray-900">{{ $sub->lastName }}, {{ $sub->name }}</p>
                                                        <p class="text-sm text-gray-500">{{ $sub->jobs->first()?->name ?? '—' }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col items-end gap-1">
                                                    @if($isOnVacation)
                                                        <span class="px-2 py-1 bg-blue-500 text-white rounded text-xs font-medium">De vacaciones</span>
                                                    @endif
                                                    @if($sub->status === 'active')
                                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Activo</span>
                                                    @else
                                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">Inactivo</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="mt-3 pt-3 border-t grid grid-cols-3 gap-2 text-center">
                                                <div>
                                                    <p class="text-xs text-gray-500">Antigüedad</p>
                                                    <p class="font-semibold text-gray-900">{{ $sub->antiquity_years }} años</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Vacaciones usadas</p>
                                                    <p class="font-semibold text-gray-900">{{ $sub->getUsedVacationDays() }} días</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Disponibles</p>
                                                    <p class="font-semibold text-indigo-600">{{ $sub->getAvailableVacationDays() }} días</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-8">No tienes empleados a cargo</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Columna Lateral --}}
                <div class="space-y-6">
                    {{-- De Vacaciones Actualmente --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 border-b bg-blue-50">
                            <h3 class="font-semibold text-blue-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                                </svg>
                                De Vacaciones
                            </h3>
                        </div>
                        <div class="p-4">
                            @if($currentVacations->count() > 0)
                                <div class="space-y-3">
                                    @foreach($currentVacations as $vac)
                                        <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                                {{ strtoupper(substr($vac->employee->name, 0, 1)) }}
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="font-medium text-gray-900 text-sm">{{ $vac->employee->name }} {{ $vac->employee->lastName }}</p>
                                                <p class="text-xs text-gray-500">
                                                    Hasta {{ \Carbon\Carbon::parse($vac->end)->format('d/m') }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-4 text-sm">Nadie de vacaciones</p>
                            @endif
                        </div>
                    </div>

                    {{-- Próximos Cumpleaños del Equipo --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 border-b bg-pink-50">
                            <h3 class="font-semibold text-pink-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A1.5 1.5 0 003 15.546V12a9 9 0 0118 0v3.546zM12 3v2m0 0a2 2 0 100 4 2 2 0 000-4z"/>
                                </svg>
                                Próximos Cumpleaños
                            </h3>
                        </div>
                        <div class="p-4">
                            @if($upcomingBirthdays->count() > 0)
                                <div class="space-y-3">
                                    @foreach($upcomingBirthdays->take(5) as $emp)
                                        <div class="flex items-center p-3 rounded-lg {{ $emp->days_until_birthday == 0 ? 'bg-pink-100 border border-pink-300' : 'bg-gray-50' }}">
                                            <div class="w-8 h-8 bg-pink-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                                {{ strtoupper(substr($emp->name, 0, 1)) }}
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="font-medium text-gray-900 text-sm">{{ $emp->name }} {{ $emp->lastName }}</p>
                                                <p class="text-xs text-gray-500">
                                                    @if($emp->days_until_birthday == 0)
                                                        🎉 ¡Hoy cumple {{ $emp->turning_age }} años!
                                                    @elseif($emp->days_until_birthday == 1)
                                                        Mañana - {{ $emp->turning_age }} años
                                                    @else
                                                        En {{ $emp->days_until_birthday }} días - {{ $emp->turning_age }} años
                                                    @endif
                                                </p>
                                            </div>
                                            @if($emp->days_until_birthday == 0)
                                                <span class="text-2xl">🎂</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-4 text-sm">Sin cumpleaños próximos</p>
                            @endif
                        </div>
                    </div>

                    {{-- Estadísticas Rápidas --}}
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                        <h3 class="font-semibold mb-4">Resumen del Equipo</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-indigo-100">Promedio Antigüedad</span>
                                <span class="font-bold">
                                    {{ round($subordinates->avg('antiquity_years'), 1) }} años
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-indigo-100">Vacaciones usadas (prom.)</span>
                                <span class="font-bold">
                                    {{ round($subordinates->avg(fn($s) => $s->getUsedVacationDays()), 1) }} días
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-indigo-100">Horas semanales (prom.)</span>
                                <span class="font-bold">
                                    {{ round($subordinates->avg('weekly_hours'), 1) }} hs
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-portal-layout>
