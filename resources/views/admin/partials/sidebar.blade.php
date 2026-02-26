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
                    <img src="{{ asset('images/logo_ipac.png') }}" alt="IPAC" class="h-10 w-auto">
                </a>
                <div class="ml-3">
                    <span class="text-white font-semibold text-sm">Panel</span>
                    <span class="block text-zinc-400 text-xs">Administrativo</span>
                </div>
            </div>
        </div>

        <!-- Navegación Principal -->
        <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                {{ request()->routeIs('dashboard') 
                    ? 'bg-zinc-700 text-white' 
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <!-- Ir a Laboratorio -->
            <a href="{{ route('lab.section.clinico') }}" 
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors text-teal-400 hover:bg-teal-600/20 hover:text-teal-300">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                Ir a Laboratorio
            </a>

            <div class="border-t border-zinc-700 my-2"></div>

            <!-- Personal -->
            <a href="{{ route('admin.section.personal') }}"
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                {{ request()->routeIs('admin.section.personal') || request()->routeIs('employee.*') || request()->routeIs('job.*') || request()->routeIs('category.*') || request()->routeIs('documents.*') || request()->routeIs('circular.*') || request()->routeIs('manage.chart') || request()->routeIs('non-conformity.*')
                    ? 'bg-zinc-700 text-white'
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Personal
            </a>

            <!-- Ausencias -->
            <a href="{{ route('admin.section.ausencias') }}"
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                {{ request()->routeIs('admin.section.ausencias') || request()->routeIs('vacation.*') || request()->routeIs('leave.*')
                    ? 'bg-zinc-700 text-white'
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Ausencias
            </a>

            <!-- Liquidaciones -->
            <a href="{{ route('admin.section.liquidaciones') }}"
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                {{ request()->routeIs('admin.section.liquidaciones') || request()->routeIs('payroll.*') || request()->routeIs('salary.*')
                    ? 'bg-zinc-700 text-white'
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Liquidaciones
            </a>

            <!-- Configuración -->
            <a href="{{ route('admin.section.configuracion') }}"
               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
                {{ request()->routeIs('admin.section.configuracion') || request()->routeIs('user.*') || request()->routeIs('role.*') || request()->routeIs('permission.*')
                    ? 'bg-zinc-700 text-white'
                    : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Configuración
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

