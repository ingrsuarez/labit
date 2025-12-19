<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="text-center">
            <div class="mb-6">
                <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-10 h-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-2">Acceso Pendiente</h2>
            
            <p class="text-gray-600 mb-6">
                Tu cuenta ha sido creada exitosamente, pero aún no tienes permisos asignados para acceder al sistema.
            </p>

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-500">
                    <strong>Usuario:</strong> {{ Auth::user()->name }}<br>
                    <strong>Email:</strong> {{ Auth::user()->email }}
                </p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-left">
                        <p class="text-sm text-blue-800">
                            <strong>¿Qué hacer?</strong><br>
                            Contacta al administrador del sistema para que te asigne los permisos correspondientes o te asocie como empleado.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col space-y-3">
                <a href="{{ route('access.pending') }}" 
                   class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Verificar Acceso
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>







