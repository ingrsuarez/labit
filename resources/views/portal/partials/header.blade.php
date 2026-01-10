@php
    $user = auth()->user();
    $employee = $user->employee;
@endphp

<header class="hidden md:block bg-white shadow-sm border-b sticky top-0 z-30">
    <div class="px-6 py-4">
        <div class="flex items-center justify-between">
            <!-- Logo y Título -->
            <div class="flex items-center">
                <a href="{{ route('portal.dashboard') }}" class="mr-4">
                    <img src="{{ asset('images/logo_ipac.png') }}" alt="IPAC" class="h-10 w-auto">
                </a>
                <div>
                    <h1 class="text-lg font-semibold text-gray-900">
                        @if(request()->routeIs('portal.dashboard'))
                            Mi Perfil
                        @elseif(request()->routeIs('portal.team'))
                            Mi Equipo
                        @elseif(request()->routeIs('portal.directory'))
                            Directorio
                        @elseif(request()->routeIs('portal.circulars.*'))
                            Circulares
                        @else
                            Portal del Empleado
                        @endif
                    </h1>
                    <p class="text-sm text-gray-500">
                        {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </p>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="flex items-center space-x-4">
                <!-- Notificaciones -->
                @php
                    $pendingCircularsCount = $employee ? \App\Models\Circular::pendingForEmployee($employee)->count() : 0;
                    $hasNotifications = $pendingCircularsCount > 0;
                @endphp
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" 
                            class="relative p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if($hasNotifications)
                            <span class="absolute top-1 right-1 w-5 h-5 bg-red-500 rounded-full text-white text-xs flex items-center justify-center font-bold animate-pulse">
                                {{ $pendingCircularsCount }}
                            </span>
                        @endif
                    </button>

                    <!-- Dropdown de notificaciones -->
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         @click.away="open = false"
                         class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border py-2 z-50">
                        
                        <div class="px-4 py-2 border-b">
                            <h3 class="text-sm font-semibold text-gray-900">Notificaciones</h3>
                        </div>

                        @if($hasNotifications)
                            <div class="max-h-64 overflow-y-auto">
                                @if($pendingCircularsCount > 0)
                                    <a href="{{ route('portal.circulars.index') }}" 
                                       class="block px-4 py-3 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ $pendingCircularsCount }} circular{{ $pendingCircularsCount > 1 ? 'es' : '' }} pendiente{{ $pendingCircularsCount > 1 ? 's' : '' }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    Requieren tu firma
                                                </p>
                                            </div>
                                            <span class="flex-shrink-0 w-2 h-2 bg-red-500 rounded-full"></span>
                                        </div>
                                    </a>
                                @endif
                            </div>
                            <div class="px-4 py-2 border-t">
                                <a href="{{ route('portal.circulars.index') }}" 
                                   class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                    Ver todas las circulares →
                                </a>
                            </div>
                        @else
                            <div class="px-4 py-8 text-center">
                                <svg class="mx-auto h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">No hay notificaciones</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Usuario Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" 
                            class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                            {{ strtoupper(substr($employee->name ?? $user->name, 0, 1)) }}
                        </div>
                        <div class="hidden lg:block text-left">
                            <p class="text-sm font-medium text-gray-900 capitalize">
                                {{ $employee->name ?? $user->name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $employee->jobs->first()->name ?? 'Empleado' }}
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
                            Configuración de Cuenta
                        </a>
                        
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
