@php
    $user = auth()->user();
@endphp

<!-- Sidebar Mobile Toggle -->
<div class="md:hidden fixed top-0 left-0 right-0 z-50 bg-teal-700 px-4 py-3 flex items-center justify-between">
    <div class="flex items-center">
        <button id="lab-sidebar-toggle" class="text-white p-2 rounded-lg hover:bg-teal-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <span class="ml-3 text-white font-semibold">Laboratorio</span>
    </div>
</div>

<!-- Sidebar -->
<aside id="lab-sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-teal-700 to-teal-900 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out overflow-y-auto">
    <div class="flex flex-col h-full">
        <!-- Header del Sidebar -->
        <div class="px-6 py-5 border-b border-teal-600">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}">
                    <x-application-mark class="h-10 w-auto" />
                </a>
                <div class="ml-3">
                    <span class="text-white font-semibold text-sm">Sistema de</span>
                    <span class="block text-teal-200 text-xs">Laboratorio</span>
                </div>
            </div>
        </div>

        <!-- Navegación Principal -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            
            <!-- Separador: Laboratorio Clínico -->
            <div class="pb-2">
                <span class="px-4 text-xs font-semibold text-teal-300 uppercase tracking-wider">
                    Laboratorio Clínico
                </span>
            </div>

            <a href="{{ route('lab.admissions.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('lab.admissions.index') || request()->routeIs('lab.admissions.show') || request()->routeIs('lab.admissions.edit')
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Protocolos
            </a>

            <a href="{{ route('lab.admissions.create') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('lab.admissions.create') 
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Nuevo Protocolo
            </a>

            <a href="{{ route('patient.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('patient.index') 
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Nuevo Paciente
            </a>

            <a href="{{ route('insurance.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('insurance.*') 
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Obras Sociales
            </a>

            <a href="{{ route('lab.reports.monthly') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('lab.reports.*') 
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Reportes
            </a>

            <!-- Separador: Muestras -->
            <div class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-teal-300 uppercase tracking-wider">
                    Muestras (Agua/Alimentos)
                </span>
            </div>

            <a href="{{ route('sample.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('sample.index') 
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                Protocolos
            </a>

            <a href="{{ route('sample.create') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('sample.create') 
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Nueva Muestra
            </a>

            <a href="{{ route('customer.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('customer.*') 
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Clientes
            </a>

            <!-- Separador: Configuración -->
            <div class="pt-4 pb-2">
                <span class="px-4 text-xs font-semibold text-teal-300 uppercase tracking-wider">
                    Configuración
                </span>
            </div>


            <a href="{{ route('tests.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('tests.*') 
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                Determinaciones
            </a>

            <a href="{{ route('reference-categories.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('reference-categories.*') 
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Valores de Referencia
            </a>

            <a href="{{ route('materials.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('materials.*') 
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Materiales
            </a>

            <a href="{{ route('nomenclator.index') }}" 
               class="flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors
                {{ request()->routeIs('nomenclator.*') 
                    ? 'bg-teal-600 text-white' 
                    : 'text-teal-100 hover:bg-teal-600/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Nomencladores
            </a>

        </nav>

        <!-- Footer del Sidebar -->
        <div class="px-3 py-4 border-t border-teal-600">
            <!-- Ir a Administración -->
            <a href="{{ route('manage.index') }}" 
               class="flex items-center px-4 py-2 text-sm rounded-lg text-teal-100 hover:bg-teal-600/50 hover:text-white transition-colors mb-2">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Administración
            </a>

            <!-- Portal del Empleado -->
            @if($user->employee)
                <a href="{{ route('portal.dashboard') }}" 
                   class="flex items-center px-4 py-2 text-sm rounded-lg text-teal-100 hover:bg-teal-600/50 hover:text-white transition-colors mb-2">
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
                        class="flex items-center w-full px-4 py-2 text-sm rounded-lg text-teal-100 hover:bg-red-600/20 hover:text-red-300 transition-colors">
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
<div id="lab-sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('lab-sidebar');
        const toggle = document.getElementById('lab-sidebar-toggle');
        const overlay = document.getElementById('lab-sidebar-overlay');

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

