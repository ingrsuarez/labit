<x-admin-layout title="Dashboard">
    <div class="p-6 space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Panel de Control</h1>
                <p class="text-gray-500">Resumen de gestión de recursos humanos</p>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="text-sm text-gray-500">Última actualización: {{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <!-- KPIs Principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Total Empleados -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Empleados</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalEmpleados }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            <span class="text-green-600">{{ $empleadosActivos }} activos</span> · 
                            <span class="text-gray-500">{{ $empleadosInactivos }} inactivos</span>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Puestos -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Puestos de Trabajo</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalPuestos }}</p>
                        <p class="text-xs text-gray-400 mt-1">Estructura organizacional</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Ausencias del Mes -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Ausencias del Mes</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $ausenciasDelMes }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ now()->locale('es')->isoFormat('MMMM YYYY') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Solicitudes Pendientes -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Solicitudes Pendientes</p>
                        <p class="text-3xl font-bold {{ $solicitudesPendientes > 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">{{ $solicitudesPendientes }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            @if($solicitudesPendientes > 0)
                                <a href="{{ route('vacation.approval') }}" class="text-red-600 hover:underline">Requieren atención</a>
                            @else
                                Sin pendientes
                            @endif
                        </p>
                    </div>
                    <div class="w-12 h-12 {{ $solicitudesPendientes > 0 ? 'bg-red-100' : 'bg-green-100' }} rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 {{ $solicitudesPendientes > 0 ? 'text-red-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Segunda fila de KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- Promedio Antigüedad -->
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-indigo-100">Promedio Antigüedad</p>
                        <p class="text-3xl font-bold mt-1">{{ $promedioAntiguedad }} años</p>
                        <p class="text-xs text-indigo-200 mt-1">Empleados activos</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Costo Nómina -->
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-emerald-100">Última Nómina</p>
                        <p class="text-3xl font-bold mt-1">${{ number_format($costoNomina, 0, ',', '.') }}</p>
                        <p class="text-xs text-emerald-200 mt-1">
                            @if($ultimoMesPagado)
                                {{ Carbon\Carbon::create($ultimoMesPagado->year, $ultimoMesPagado->month)->locale('es')->isoFormat('MMMM YYYY') }}
                            @else
                                Sin datos
                            @endif
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- De Vacaciones Hoy -->
            <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-cyan-100">De Vacaciones Hoy</p>
                        <p class="text-3xl font-bold mt-1">{{ $enVacacionesHoy->count() }}</p>
                        <p class="text-xs text-cyan-200 mt-1">
                            @if($enVacacionesHoy->count() > 0)
                                {{ $enVacacionesHoy->take(2)->pluck('employee.name')->join(', ') }}{{ $enVacacionesHoy->count() > 2 ? '...' : '' }}
                            @else
                                Todos presentes
                            @endif
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Empleados por Departamento -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Empleados por Departamento</h3>
                <div class="space-y-3">
                    @forelse($empleadosPorDepartamento as $dept)
                        @php
                            $porcentaje = $empleadosActivos > 0 ? ($dept['value'] / $empleadosActivos) * 100 : 0;
                            $colores = ['bg-blue-500', 'bg-purple-500', 'bg-green-500', 'bg-amber-500', 'bg-red-500', 'bg-indigo-500', 'bg-pink-500', 'bg-cyan-500'];
                            $color = $colores[$loop->index % count($colores)];
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700">{{ $dept['label'] }}</span>
                                <span class="text-gray-500">{{ $dept['value'] }} empleados</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5">
                                <div class="{{ $color }} h-2.5 rounded-full transition-all duration-500" style="width: {{ $porcentaje }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No hay datos de departamentos</p>
                    @endforelse
                </div>
            </div>

            <!-- Ausencias por Tipo -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ausencias (Últimos 3 meses)</h3>
                <div class="space-y-3">
                    @php
                        $totalAusencias = $ausenciasPorTipo->sum('value');
                        $coloresAusencias = [
                            'Vacaciones' => 'bg-blue-500',
                            'Enfermedad' => 'bg-red-500',
                            'Maternidad' => 'bg-pink-500',
                            'Paternidad' => 'bg-indigo-500',
                            'Estudio' => 'bg-amber-500',
                            'Mudanza' => 'bg-green-500',
                            'Fallecimiento' => 'bg-gray-500',
                            'Matrimonio' => 'bg-purple-500',
                            'Otro' => 'bg-cyan-500'
                        ];
                    @endphp
                    @forelse($ausenciasPorTipo as $ausencia)
                        @php
                            $porcentaje = $totalAusencias > 0 ? ($ausencia['value'] / $totalAusencias) * 100 : 0;
                            $color = $coloresAusencias[$ausencia['label']] ?? 'bg-gray-500';
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700">{{ $ausencia['label'] }}</span>
                                <span class="text-gray-500">{{ $ausencia['value'] }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5">
                                <div class="{{ $color }} h-2.5 rounded-full transition-all duration-500" style="width: {{ $porcentaje }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No hay ausencias registradas</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Contrataciones y Tipo de Contrato -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Contrataciones por Mes -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Contrataciones (Últimos 12 meses)</h3>
                <div class="flex items-end justify-between h-48 gap-2">
                    @foreach($contratacionesPorMes as $mes)
                        @php
                            $maxValue = $contratacionesPorMes->max('value') ?: 1;
                            $altura = ($mes['value'] / $maxValue) * 100;
                        @endphp
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full flex flex-col items-center justify-end h-36">
                                <span class="text-xs font-medium text-gray-700 mb-1">{{ $mes['value'] }}</span>
                                <div class="w-full bg-indigo-500 rounded-t transition-all duration-500 hover:bg-indigo-600" 
                                     style="height: {{ max($altura, 4) }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 mt-2 transform -rotate-45 origin-top-left whitespace-nowrap">{{ $mes['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Distribución por Género -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribución por Género</h3>
                <div class="space-y-4">
                    @php
                        $totalGenero = $empleadosPorGenero->sum('value');
                        $coloresGenero = [
                            'Masculino' => 'bg-blue-500',
                            'Femenino' => 'bg-pink-500',
                            'Otro' => 'bg-purple-500',
                            'No especificado' => 'bg-gray-400'
                        ];
                    @endphp
                    @forelse($empleadosPorGenero as $genero)
                        @php
                            $porcentaje = $totalGenero > 0 ? round(($genero['value'] / $totalGenero) * 100) : 0;
                            $color = $coloresGenero[$genero['label']] ?? 'bg-gray-500';
                        @endphp
                        <div class="flex items-center">
                            <div class="w-3 h-3 {{ $color }} rounded-full mr-3"></div>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-700">{{ $genero['label'] }}</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $genero['value'] }} ({{ $porcentaje }}%)</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No hay datos</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Secciones inferiores -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Próximos Cumpleaños -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">🎂 Próximos Cumpleaños</h3>
                </div>
                <div class="space-y-3">
                    @forelse($proximosCumpleanos as $cumple)
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 bg-pink-100 rounded-full flex items-center justify-center text-pink-600 font-bold mr-3">
                                {{ strtoupper(substr($cumple['employee']->name, 0, 1)) }}
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $cumple['employee']->full_name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $cumple['fecha']->locale('es')->isoFormat('D [de] MMMM') }}
                                    @if($cumple['dias'] == 0)
                                        <span class="text-pink-600 font-medium">· ¡Hoy!</span>
                                    @elseif($cumple['dias'] == 1)
                                        <span class="text-amber-600">· Mañana</span>
                                    @else
                                        <span class="text-gray-400">· en {{ $cumple['dias'] }} días</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm text-center py-4">No hay cumpleaños próximos</p>
                    @endforelse
                </div>
            </div>

            <!-- Solicitudes Pendientes -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">📋 Solicitudes Pendientes</h3>
                    @if($solicitudesRecientes->count() > 0)
                        <a href="{{ route('vacation.approval') }}" class="text-sm text-indigo-600 hover:underline">Ver todas</a>
                    @endif
                </div>
                <div class="space-y-3">
                    @forelse($solicitudesRecientes as $solicitud)
                        <div class="flex items-center p-3 bg-amber-50 rounded-lg border border-amber-100">
                            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 font-bold mr-3">
                                {{ strtoupper(substr($solicitud->employee->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $solicitud->employee->full_name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ ucfirst($solicitud->type) }} · 
                                    {{ \Carbon\Carbon::parse($solicitud->start)->format('d/m') }} - {{ \Carbon\Carbon::parse($solicitud->end)->format('d/m') }}
                                </p>
                            </div>
                            <span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs rounded-full">Pendiente</span>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 text-sm">No hay solicitudes pendientes</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Top Puestos -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">👥 Top Puestos</h3>
                    <a href="{{ route('job.new') }}" class="text-sm text-indigo-600 hover:underline">Ver todos</a>
                </div>
                <div class="space-y-3">
                    @forelse($topPuestos as $puesto)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $puesto->name }}</p>
                                <p class="text-xs text-gray-500">{{ $puesto->department ?? 'Sin departamento' }}</p>
                            </div>
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-sm font-medium rounded-full">
                                {{ $puesto->total }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm text-center py-4">No hay puestos registrados</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Accesos Rápidos -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Accesos Rápidos</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <a href="{{ route('employee.new') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 text-center">Nuevo Empleado</span>
                </a>
                <a href="{{ route('payroll.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 text-center">Liquidaciones</span>
                </a>
                <a href="{{ route('vacation.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 text-center">Vacaciones</span>
                </a>
                <a href="{{ route('leave.new') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 text-center">Nueva Licencia</span>
                </a>
                <a href="{{ route('manage.chart') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 text-center">Organigrama</span>
                </a>
                <a href="{{ route('admission.index') }}" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 text-center">Laboratorio</span>
                </a>
            </div>
        </div>

    </div>
</x-admin-layout>
