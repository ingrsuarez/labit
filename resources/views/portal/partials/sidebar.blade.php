@php
    $user = auth()->user();
    $employee = $user->employee;
    $isSupervisor = $employee ? $employee->isSupervisor() : false;
@endphp

<!-- Sidebar Mobile Toggle -->
<div class="md:hidden fixed top-0 left-0 right-0 z-50 bg-indigo-700 px-4 py-3 flex items-center justify-between">
    <div class="flex items-center">
        <button id="sidebar-toggle" class="text-white p-2 rounded-lg hover:bg-indigo-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <span class="ml-3 text-white font-semibold">Portal del Empleado</span>
    </div>
    <a href="{{ route('dashboard') }}" class="text-indigo-200 hover:text-white text-sm">
        Ir al Sistema →
    </a>
</div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-indigo-800 to-indigo-900 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
    <!-- Logo y Usuario -->
    <div class="flex flex-col h-full">
        <!-- Info del Usuario -->
        <div class="px-4 py-4 border-b border-indigo-700">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold">
                    {{ strtoupper(substr($employee->name ?? 'U', 0, 1)) }}{{ strtoupper(substr($employee->lastName ?? '', 0, 1)) }}
                </div>
                <div class="ml-3">
                    <p class="text-white text-sm font-medium truncate capitalize">
                        {{ $employee->name ?? $user->name }} {{ $employee->lastName ?? '' }}
                    </p>
                    <p class="text-indigo-300 text-xs truncate">
                        {{ $employee->jobs->first()->name ?? 'Empleado' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Navegación Principal -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <!-- Mi Perfil -->
            <a href="{{ route('portal.dashboard') }}" 
               class="flex items-center px-4 py-3 text-sm rounded-lg transition-colors
                {{ request()->routeIs('portal.dashboard') 
                    ? 'bg-indigo-700 text-white' 
                    : 'text-indigo-200 hover:bg-indigo-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Mi Perfil
            </a>

            <!-- Directorio -->
            <a href="{{ route('portal.directory') }}" 
               class="flex items-center px-4 py-3 text-sm rounded-lg transition-colors
                {{ request()->routeIs('portal.directory') 
                    ? 'bg-indigo-700 text-white' 
                    : 'text-indigo-200 hover:bg-indigo-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Directorio
            </a>

            <!-- Mi Equipo (solo supervisores) -->
            @if($isSupervisor)
                <a href="{{ route('portal.team') }}" 
                   class="flex items-center px-4 py-3 text-sm rounded-lg transition-colors
                    {{ request()->routeIs('portal.team') 
                        ? 'bg-indigo-700 text-white' 
                        : 'text-indigo-200 hover:bg-indigo-700/50 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Mi Equipo
                    @php
                        $subordinatesCount = $employee->getSubordinates()->count();
                    @endphp
                    @if($subordinatesCount > 0)
                        <span class="ml-auto bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full">
                            {{ $subordinatesCount }}
                        </span>
                    @endif
                </a>
            @endif

            <!-- Separador -->
            <div class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-indigo-400 uppercase tracking-wider">
                    Empresa
                </span>
            </div>

            <!-- Circulares -->
            @php
                $pendingCirculars = \App\Models\Circular::pendingForEmployee($employee)->count();
            @endphp
            <a href="{{ route('portal.circulars.index', ['tab' => 'signed']) }}" 
               class="flex items-center px-4 py-3 text-sm rounded-lg transition-colors
                {{ request()->routeIs('portal.circulars.*') 
                    ? 'bg-indigo-700 text-white' 
                    : 'text-indigo-200 hover:bg-indigo-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Circulares
                @if($pendingCirculars > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full animate-pulse">
                        {{ $pendingCirculars }}
                    </span>
                @endif
            </a>

            <!-- Organigrama -->
            <a href="{{ route('manage.chart') }}" 
               class="flex items-center px-4 py-3 text-sm rounded-lg transition-colors
                {{ request()->routeIs('manage.chart') 
                    ? 'bg-indigo-700 text-white' 
                    : 'text-indigo-200 hover:bg-indigo-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
                Organigrama
            </a>

            <!-- Cumpleaños -->
            <a href="{{ route('portal.directory', ['tab' => 'birthdays']) }}" 
               class="flex items-center px-4 py-3 text-sm rounded-lg transition-colors text-indigo-200 hover:bg-indigo-700/50 hover:text-white">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A1.5 1.5 0 003 15.546V12a9 9 0 0118 0v3.546zM12 3v2m0 0a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                Cumpleaños
            </a>

            <!-- Vacaciones -->
            <a href="{{ route('portal.directory', ['tab' => 'vacations']) }}" 
               class="flex items-center px-4 py-3 text-sm rounded-lg transition-colors text-indigo-200 hover:bg-indigo-700/50 hover:text-white">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                </svg>
                Vacaciones
            </a>

            <!-- Separador -->
            <div class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-indigo-400 uppercase tracking-wider">
                    Solicitudes
                </span>
            </div>

            <!-- Mis Solicitudes -->
            <a href="{{ route('portal.requests') }}" 
               class="flex items-center px-4 py-3 text-sm rounded-lg transition-colors
                {{ request()->routeIs('portal.requests') 
                    ? 'bg-indigo-700 text-white' 
                    : 'text-indigo-200 hover:bg-indigo-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Mis Solicitudes
            </a>

            <!-- Nueva Solicitud -->
            <a href="{{ route('portal.requests', ['tab' => 'new']) }}" 
               class="flex items-center px-4 py-3 text-sm rounded-lg transition-colors text-indigo-200 hover:bg-indigo-700/50 hover:text-white">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nueva Solicitud
            </a>
        </nav>

        <!-- Footer del Sidebar -->
        <div class="px-3 py-4 border-t border-indigo-700">
            <!-- Ir al Sistema (solo si tiene permisos admin) -->
            @if($user->roles->where('name', '!=', 'empleado')->count() > 0 || $user->permissions->count() > 0)
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center px-4 py-2 text-sm rounded-lg text-indigo-200 hover:bg-indigo-700/50 hover:text-white transition-colors mb-2">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Panel Administrativo
                </a>
            @endif

            <!-- Cerrar Sesión -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                        class="flex items-center w-full px-4 py-2 text-sm rounded-lg text-indigo-200 hover:bg-red-600/20 hover:text-red-300 transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </div>
</aside>

<!-- Overlay para móvil -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebar-toggle');
        const overlay = document.getElementById('sidebar-overlay');

        if (toggle) {
            toggle.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            });
        }
    });
</script>
