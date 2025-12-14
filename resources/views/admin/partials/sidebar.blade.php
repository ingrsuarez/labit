@php
    $user = auth()->user();
@endphp

<!-- Sidebar Mobile Toggle -->
<div class="md:hidden fixed top-0 left-0 right-0 z-50 bg-zinc-800 px-4 py-3 flex items-center justify-between">
    <div class="flex items-center">
        <button id="admin-sidebar-toggle" class="text-white p-2 rounded-lg hover:bg-zinc-700 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <span class="ml-3 text-white font-semibold">Panel Administrativo</span>
    </div>
</div>

<!-- Sidebar -->
<aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-zinc-800 to-zinc-900 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out overflow-y-auto">
    <div class="flex flex-col h-full">
        <!-- Header del Sidebar -->
        <div class="px-6 py-5 border-b border-zinc-700">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}">
                    <x-application-mark class="h-10 w-auto" />
                </a>
                <div class="ml-3">
                    <span class="text-white font-semibold text-sm">Panel</span>
                    <span class="block text-zinc-400 text-xs">Administrativo</span>
                </div>
            </div>
        </div>

        <!-- Navegación Principal -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" 
               class="flex items-center px-4 py-3 text-sm rounded-lg transition-colors
                {{ request()->routeIs('dashboard') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <!-- Ir a Laboratorio -->
            <a href="{{ route('sample.index') }}" 
               class="flex items-center px-4 py-3 text-sm rounded-lg transition-colors text-teal-400 hover:bg-teal-600/20 hover:text-teal-300">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                Ir a Laboratorio
            </a>

            <!-- Separador: Personal -->
            <div class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                    Personal
                </span>
            </div>

            <a href="{{ route('employee.new') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('employee.*') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Empleados
            </a>

            <a href="{{ route('job.list') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('job.*') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Puestos
            </a>

            <a href="{{ route('category.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('category.*') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Categorías
            </a>

            <a href="{{ route('documents.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('documents.*') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Documentos
            </a>

            <a href="{{ route('manage.chart') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('manage.chart') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
                Organigrama
            </a>

            <!-- Separador: Vacaciones y Licencias -->
            <div class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                    Ausencias
                </span>
            </div>

            <a href="{{ route('vacation.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('vacation.index') || request()->routeIs('vacation.store')
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Vacaciones
            </a>

            <a href="{{ route('vacation.approval') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('vacation.approval') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Aprobaciones
            </a>

            <a href="{{ route('vacation.calendar') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('vacation.calendar') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Calendario
            </a>

            <a href="{{ route('vacation.holidays') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('vacation.holidays') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                Feriados
            </a>

            <a href="{{ route('leave.resume') }}"
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('leave.*')
                    ? 'bg-zinc-700 text-white'
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Licencias
            </a>

            <a href="{{ route('non-conformity.index') }}"
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('non-conformity.*')
                    ? 'bg-zinc-700 text-white'
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                No Conformidades
            </a>

            <!-- Separador: Liquidaciones -->
            <div class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                    Liquidaciones
                </span>
            </div>

            <a href="{{ route('payroll.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('payroll.*') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Generar Recibos
            </a>

            <a href="{{ route('payroll.closed') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('payroll.closed') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                Historial
            </a>

            <a href="{{ route('salary.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('salary.*') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Conceptos Salariales
            </a>

            <!-- Separador: Configuración -->
            <div class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                    Configuración
                </span>
            </div>

            <a href="{{ route('user.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('user.*') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Usuarios
            </a>

            <a href="{{ route('role.new') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('role.*') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Roles y Permisos
            </a>

        </nav>

        <!-- Footer del Sidebar -->
        <div class="px-3 py-4 border-t border-zinc-700">
            <!-- Portal del Empleado -->
            @if($user->employee)
                <a href="{{ route('portal.dashboard') }}" 
                   class="flex items-center px-4 py-2 text-sm rounded-lg text-zinc-300 hover:bg-zinc-700/50 hover:text-white transition-colors mb-2">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Mi Portal
                </a>
            @endif

            <!-- Cerrar Sesión -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                        class="flex items-center w-full px-4 py-2 text-sm rounded-lg text-zinc-300 hover:bg-red-600/20 hover:text-red-400 transition-colors">
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
<div id="admin-sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('admin-sidebar');
        const toggle = document.getElementById('admin-sidebar-toggle');
        const overlay = document.getElementById('admin-sidebar-overlay');

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

