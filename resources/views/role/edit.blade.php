<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-6xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('role.new') }}" 
                       class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Editar Rol</h1>
                        <p class="text-sm text-gray-500">{{ ucwords($role->name) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium">
                        {{ $role->permissions_count ?? $role->permissions->count() }} permisos
                    </span>
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                        {{ $role->users_count ?? $usersWithRole->count() }} usuarios
                    </span>
                </div>
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
                {{-- Columna Principal --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Información del Rol --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Información del Rol
                            </h2>
                        </div>
                        <form action="{{ route('role.update') }}" method="POST" class="p-6">
                            @csrf
                            <input type="hidden" name="role_id" value="{{ $role->id }}">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nombre del Rol <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name" id="name" required
                                           value="{{ old('name', $role->name) }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label for="guard_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Guard Name
                                    </label>
                                    <select name="guard_name" id="guard_name"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors bg-white">
                                        <option value="web" {{ old('guard_name', $role->guard_name) == 'web' ? 'selected' : '' }}>web</option>
                                        <option value="api" {{ old('guard_name', $role->guard_name) == 'api' ? 'selected' : '' }}>api</option>
                                        <option value="sanctum" {{ old('guard_name', $role->guard_name) == 'sanctum' ? 'selected' : '' }}>sanctum</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <a href="{{ route('role.new') }}" 
                                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    Cancelar
                                </a>
                                <button type="submit" 
                                        class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Gestión de Permisos --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Permisos del Rol
                            </h2>
                        </div>
                        <div class="p-6">
                            {{-- Permisos Asignados --}}
                            <div class="mb-6">
                                <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                    Permisos Asignados ({{ $role->permissions->count() }})
                                </h3>
                                @if($role->permissions->count() > 0)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($role->permissions as $permission)
                                            <div class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-green-100 text-green-800 border border-green-200">
                                                <span>{{ $permission->name }}</span>
                                                <a href="{{ route('role.detachPermission', ['role' => $role->id, 'permission' => $permission->id]) }}"
                                                   class="ml-2 hover:text-red-600 transition-colors"
                                                   onclick="return confirm('¿Remover el permiso {{ $permission->name }}?')">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                        <p class="text-amber-800 text-sm">
                                            Este rol no tiene permisos asignados. Agrega permisos desde la sección de abajo.
                                        </p>
                                    </div>
                                @endif
                            </div>

                            {{-- Agregar Permisos por Módulo --}}
                            <div class="pt-6 border-t">
                                <h3 class="text-sm font-medium text-gray-700 mb-4 flex items-center">
                                    <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                                    Permisos Disponibles
                                </h3>
                                
                                @if($permissions->count() > 0)
                                    <div class="space-y-4" x-data="{ openModule: null }">
                                        @foreach($groupedPermissions as $module => $modulePermissions)
                                            @php
                                                $assignedInModule = $modulePermissions->filter(fn($p) => $role->hasPermissionTo($p->name))->count();
                                                $totalInModule = $modulePermissions->count();
                                            @endphp
                                            <div class="border rounded-xl overflow-hidden">
                                                <button type="button"
                                                        @click="openModule = openModule === '{{ $module }}' ? null : '{{ $module }}'"
                                                        class="w-full px-4 py-3 bg-gray-50 hover:bg-gray-100 flex items-center justify-between transition-colors">
                                                    <div class="flex items-center">
                                                        <span class="font-medium text-gray-900 capitalize">{{ $module }}</span>
                                                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full
                                                            {{ $assignedInModule == $totalInModule ? 'bg-green-100 text-green-700' : ($assignedInModule > 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                                                            {{ $assignedInModule }}/{{ $totalInModule }}
                                                        </span>
                                                    </div>
                                                    <svg class="w-5 h-5 text-gray-400 transition-transform" 
                                                         :class="{ 'rotate-180': openModule === '{{ $module }}' }"
                                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </button>
                                                <div x-show="openModule === '{{ $module }}'" 
                                                     x-collapse
                                                     class="p-4 bg-white border-t">
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                        @foreach($modulePermissions as $permission)
                                                            @php $hasPermission = $role->hasPermissionTo($permission->name); @endphp
                                                            <div class="flex items-center justify-between p-2 rounded-lg
                                                                {{ $hasPermission ? 'bg-green-50' : 'hover:bg-gray-50' }}">
                                                                <span class="text-sm {{ $hasPermission ? 'text-green-800' : 'text-gray-700' }}">
                                                                    {{ $permission->name }}
                                                                </span>
                                                                @if($hasPermission)
                                                                    <a href="{{ route('role.detachPermission', ['role' => $role->id, 'permission' => $permission->id]) }}"
                                                                       class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors">
                                                                        Quitar
                                                                    </a>
                                                                @else
                                                                    <a href="{{ route('role.attachPermission', ['role' => $role->id, 'permission' => $permission->id]) }}"
                                                                       class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors">
                                                                        Agregar
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                        <p class="text-gray-500">No hay permisos creados en el sistema</p>
                                        <a href="{{ route('permission.new') }}" class="mt-2 inline-block text-indigo-600 hover:text-indigo-800">
                                            Crear permisos →
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Columna Lateral --}}
                <div class="space-y-6">
                    {{-- Info del Rol --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-14 h-14 rounded-full flex items-center justify-center text-white text-xl font-bold
                                    @if(in_array(strtolower($role->name), ['admin', 'administrator', 'superadmin']))
                                        bg-red-500
                                    @elseif(in_array(strtolower($role->name), ['rrhh', 'hr', 'recursos humanos']))
                                        bg-purple-500
                                    @else
                                        bg-indigo-500
                                    @endif
                                ">
                                    {{ strtoupper(substr($role->name, 0, 2)) }}
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-semibold text-gray-900">{{ ucwords($role->name) }}</h3>
                                    <p class="text-sm text-gray-500">Guard: {{ $role->guard_name }}</p>
                                </div>
                            </div>
                            
                            <div class="space-y-3 pt-4 border-t">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">ID</span>
                                    <span class="font-medium text-gray-900">#{{ $role->id }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Creado</span>
                                    <span class="font-medium text-gray-900">{{ $role->created_at->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Usuarios</span>
                                    <span class="font-medium text-gray-900">{{ $usersWithRole->count() }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Permisos</span>
                                    <span class="font-medium text-gray-900">{{ $role->permissions->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Usuarios con este Rol --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gray-50">
                            <h3 class="font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                Usuarios con este Rol
                            </h3>
                        </div>
                        <div class="p-4">
                            @if($usersWithRole->count() > 0)
                                <div class="space-y-2 max-h-64 overflow-y-auto">
                                    @foreach($usersWithRole as $user)
                                        <a href="{{ route('user.edit', ['user' => $user->id]) }}"
                                           class="flex items-center p-2 rounded-lg hover:bg-gray-50 transition-colors">
                                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 text-sm font-bold">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div class="ml-3 flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</p>
                                                <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                                            </div>
                                            @if($user->employee)
                                                <span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded">Empleado</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-sm text-center py-4">Ningún usuario tiene este rol</p>
                            @endif
                        </div>
                    </div>

                    {{-- Acciones --}}
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Acciones</h3>
                        <div class="space-y-2">
                            <a href="{{ route('role.new') }}" 
                               class="flex items-center w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                                Ver Todos los Roles
                            </a>
                            <a href="{{ route('permission.new') }}" 
                               class="flex items-center w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Gestionar Permisos
                            </a>
                            <a href="{{ route('user.index') }}" 
                               class="flex items-center w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                Gestionar Usuarios
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-manage>
