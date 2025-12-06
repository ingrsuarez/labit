<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-6xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestión de Roles</h1>
                    <p class="text-sm text-gray-500">Administra los roles y permisos del sistema</p>
                </div>
                <a href="{{ route('permission.new') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Gestionar Permisos
                </a>
            </div>

            {{-- Alertas --}}
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-green-800">{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="list-disc list-inside text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Formulario Crear Rol --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden sticky top-24">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Crear Nuevo Rol
                            </h2>
                        </div>
                        <form action="{{ route('role.store') }}" method="POST" class="p-6">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nombre del Rol <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name" id="name" required
                                           value="{{ old('name') }}"
                                           placeholder="ej: administrador, rrhh, supervisor"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
                                    <p class="mt-1 text-xs text-gray-500">Se guardará en minúsculas</p>
                                </div>
                                
                                <div>
                                    <label for="guard_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Guard Name
                                    </label>
                                    <select name="guard_name" id="guard_name"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors bg-white">
                                        <option value="web" {{ old('guard_name', 'web') == 'web' ? 'selected' : '' }}>web</option>
                                        <option value="api" {{ old('guard_name') == 'api' ? 'selected' : '' }}>api</option>
                                        <option value="sanctum" {{ old('guard_name') == 'sanctum' ? 'selected' : '' }}>sanctum</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Para aplicaciones web usar "web"</p>
                                </div>
                            </div>

                            <button type="submit" 
                                    class="mt-6 w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Crear Rol
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Lista de Roles --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Roles del Sistema ({{ $roles->count() }})
                            </h2>
                        </div>
                        <div class="p-6">
                            @if($roles->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($roles as $role)
                                        <div class="p-4 border rounded-xl hover:shadow-md transition-shadow
                                            @if(in_array(strtolower($role->name), ['admin', 'administrator', 'superadmin']))
                                                border-red-200 bg-red-50
                                            @elseif(in_array(strtolower($role->name), ['rrhh', 'hr', 'recursos humanos']))
                                                border-purple-200 bg-purple-50
                                            @else
                                                border-gray-200
                                            @endif
                                        ">
                                            <div class="flex items-start justify-between">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                                                        @if(in_array(strtolower($role->name), ['admin', 'administrator', 'superadmin']))
                                                            bg-red-500 text-white
                                                        @elseif(in_array(strtolower($role->name), ['rrhh', 'hr', 'recursos humanos']))
                                                            bg-purple-500 text-white
                                                        @else
                                                            bg-indigo-500 text-white
                                                        @endif
                                                    ">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <h3 class="font-semibold text-gray-900">{{ ucwords($role->name) }}</h3>
                                                        <p class="text-xs text-gray-500">Guard: {{ $role->guard_name }}</p>
                                                    </div>
                                                </div>
                                                <a href="{{ route('role.edit', $role) }}" 
                                                   class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </a>
                                            </div>
                                            
                                            <div class="mt-4 flex items-center gap-4 text-sm">
                                                <div class="flex items-center text-gray-600">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                                    </svg>
                                                    {{ $role->users_count ?? 0 }} usuarios
                                                </div>
                                                <div class="flex items-center text-gray-600">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                                    </svg>
                                                    {{ $role->permissions_count ?? 0 }} permisos
                                                </div>
                                            </div>

                                            <div class="mt-3 pt-3 border-t">
                                                <a href="{{ route('role.edit', $role) }}" 
                                                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                                    Editar rol y permisos →
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay roles creados</h3>
                                    <p class="text-gray-500 mb-4">Crea tu primer rol para comenzar a gestionar los accesos</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Roles Recomendados --}}
                    @if($roles->count() == 0)
                        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-2xl p-6">
                            <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Roles Recomendados
                            </h3>
                            <p class="text-sm text-blue-800 mb-4">Te sugerimos crear los siguientes roles para comenzar:</p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="p-3 bg-white rounded-lg border border-blue-200">
                                    <p class="font-medium text-gray-900">admin</p>
                                    <p class="text-xs text-gray-500">Acceso completo al sistema</p>
                                </div>
                                <div class="p-3 bg-white rounded-lg border border-blue-200">
                                    <p class="font-medium text-gray-900">rrhh</p>
                                    <p class="text-xs text-gray-500">Gestión de empleados y nómina</p>
                                </div>
                                <div class="p-3 bg-white rounded-lg border border-blue-200">
                                    <p class="font-medium text-gray-900">supervisor</p>
                                    <p class="text-xs text-gray-500">Gestión de equipos</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-manage>
