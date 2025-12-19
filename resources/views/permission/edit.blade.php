<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-5xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <a href="{{ route('permission.new') }}" 
                       class="mr-4 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Editar Permiso</h1>
                        <p class="text-sm text-gray-500">{{ $permission->name }}</p>
                    </div>
                </div>
                <form action="{{ route('permission.destroy', $permission) }}" method="POST"
                      onsubmit="return confirm('¿Estás seguro de eliminar este permiso? Esta acción no se puede deshacer.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Eliminar Permiso
                    </button>
                </form>
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

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-red-800">{{ session('error') }}</span>
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
                {{-- Columna Principal --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Información del Permiso --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Información del Permiso
                            </h2>
                        </div>
                        <form action="{{ route('permission.update', $permission) }}" method="POST" class="p-6">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nombre del Permiso <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name" id="name" required
                                           value="{{ old('name', $permission->name) }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                                    <p class="mt-1 text-xs text-gray-500">Formato: modulo.accion</p>
                                </div>
                                
                                <div>
                                    <label for="guard_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Guard Name
                                    </label>
                                    <select name="guard_name" id="guard_name"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors bg-white">
                                        <option value="web" {{ old('guard_name', $permission->guard_name) == 'web' ? 'selected' : '' }}>web</option>
                                        <option value="api" {{ old('guard_name', $permission->guard_name) == 'api' ? 'selected' : '' }}>api</option>
                                        <option value="sanctum" {{ old('guard_name', $permission->guard_name) == 'sanctum' ? 'selected' : '' }}>sanctum</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" 
                                        class="px-6 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Roles con este Permiso --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Roles con este Permiso ({{ $rolesWithPermission->count() }})
                            </h2>
                        </div>
                        <div class="p-6">
                            @if($rolesWithPermission->count() > 0)
                                <div class="space-y-3">
                                    @foreach($rolesWithPermission as $role)
                                        <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full flex items-center justify-center
                                                    @if(in_array(strtolower($role->name), ['admin', 'administrator', 'superadmin']))
                                                        bg-red-500 text-white
                                                    @elseif(in_array(strtolower($role->name), ['rrhh', 'hr', 'recursos humanos']))
                                                        bg-purple-500 text-white
                                                    @elseif(in_array(strtolower($role->name), ['empleado', 'employee']))
                                                        bg-green-500 text-white
                                                    @else
                                                        bg-indigo-500 text-white
                                                    @endif
                                                ">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="font-medium text-gray-900">{{ ucwords($role->name) }}</p>
                                                    <p class="text-xs text-gray-500">Guard: {{ $role->guard_name }}</p>
                                                </div>
                                            </div>
                                            <form action="{{ route('permission.detachRole', [$permission, $role]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="px-3 py-1 text-red-600 hover:bg-red-50 rounded-lg transition-colors text-sm font-medium">
                                                    Quitar
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <p class="text-gray-500">Este permiso no está asignado a ningún rol</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="lg:col-span-1 space-y-6">
                    {{-- Resumen --}}
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumen</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-amber-50 rounded-lg">
                                <span class="text-amber-800 text-sm">Roles asignados</span>
                                <span class="text-xl font-bold text-amber-600">{{ $rolesWithPermission->count() }}</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <p><strong>Creado:</strong> {{ $permission->created_at->format('d/m/Y H:i') }}</p>
                                <p><strong>Actualizado:</strong> {{ $permission->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Agregar a Rol --}}
                    @if($availableRoles->count() > 0)
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                                <h3 class="text-lg font-semibold text-white flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Agregar a Rol
                                </h3>
                            </div>
                            <div class="p-4 max-h-64 overflow-y-auto">
                                <div class="space-y-2">
                                    @foreach($availableRoles as $role)
                                        <form action="{{ route('permission.attachRole', [$permission, $role]) }}" method="POST">
                                            @csrf
                                            <button type="submit" 
                                                    class="w-full flex items-center justify-between p-3 border rounded-lg hover:bg-green-50 hover:border-green-300 transition-colors text-left">
                                                <span class="font-medium text-gray-700">{{ ucwords($role->name) }}</span>
                                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Acciones Rápidas --}}
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Acciones Rápidas</h3>
                        <div class="space-y-3">
                            <a href="{{ route('permission.new') }}" 
                               class="flex items-center p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                                <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                                <span class="text-gray-700">Ver todos los permisos</span>
                            </a>
                            <a href="{{ route('role.new') }}" 
                               class="flex items-center p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                                <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span class="text-gray-700">Gestionar roles</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-manage>







