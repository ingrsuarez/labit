@php
    $user = auth()->user();
@endphp

<header class="hidden md:block bg-white shadow-sm border-b sticky top-0 z-30">
    <div class="px-6 py-4">
        <div class="flex items-center justify-between">
            <!-- Título de la página -->
            <div>
                <h1 class="text-lg font-semibold text-gray-900">
                    @if(request()->routeIs('dashboard'))
                        Dashboard
                    @elseif(request()->routeIs('admission.*'))
                        Admisión de Pedidos
                    @elseif(request()->routeIs('patient.*'))
                        Gestión de Pacientes
                    @elseif(request()->routeIs('tests.*'))
                        Análisis Clínicos
                    @elseif(request()->routeIs('group.*'))
                        Grupos de Análisis
                    @elseif(request()->routeIs('insurance.*'))
                        Coberturas Médicas
                    @else
                        Laboratorio
                    @endif
                </h1>
                <p class="text-sm text-gray-500">
                    {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </p>
            </div>

            <!-- Acciones -->
            <div class="flex items-center space-x-4">
                <!-- Notificaciones -->
                <button class="relative p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </button>

                <!-- Usuario Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" 
                            class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 bg-teal-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="hidden lg:block text-left">
                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $user->roles->first()->name ?? 'Usuario' }}
                            </p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         @click.away="open = false"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-1 z-50">
                        
                        <a href="{{ route('profile.show') }}" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Mi Perfil
                        </a>

                        <a href="{{ route('manage.index') }}" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Administración
                        </a>

                        @if($user->employee)
                            <a href="{{ route('portal.dashboard') }}" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Portal del Empleado
                            </a>
                        @endif
                        
                        <div class="border-t my-1"></div>
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" 
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Header Móvil (espacio para el toggle) -->
<div class="h-14 md:hidden"></div>

