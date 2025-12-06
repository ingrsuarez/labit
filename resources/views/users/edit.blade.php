<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-5xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('user.index') }}" 
                       class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Editar Usuario</h1>
                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($user->employee)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                            Empleado vinculado
                        </span>
                    @endif
                    @if($user->roles->count() > 0)
                        <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium">
                            {{ $user->roles->count() }} rol(es)
                        </span>
                    @else
                        <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-sm font-medium">
                            Sin roles
                        </span>
                    @endif
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
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-red-800 font-medium">Errores de validación</span>
                    </div>
                    <ul class="list-disc list-inside text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Columna Principal - Datos del Usuario --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Información Personal --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Información Personal
                            </h2>
                        </div>
                        <form action="{{ route('user.save') }}" method="POST" class="p-6">
                            @csrf
                            <input type="hidden" name="id" value="{{ $user->id }}">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nombre <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name" id="name" required
                                           value="{{ old('name', $user->name) }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Apellido
                                    </label>
                                    <input type="text" name="last_name" id="last_name"
                                           value="{{ old('last_name', $user->lastName) }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" name="email" id="email" required
                                           value="{{ old('email', $user->email) }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label for="userId" class="block text-sm font-medium text-gray-700 mb-2">
                                        DNI / Documento
                                    </label>
                                    <input type="text" name="userId" id="userId"
                                           value="{{ old('userId', $user->userId) }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                </div>
                            </div>

                            {{-- Vinculación con Empleado --}}
                            <div class="mt-6 pt-6 border-t">
                                <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Vincular con Empleado
                                </label>
                                <select name="employee_id" id="employee_id"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                    <option value="">— Sin vincular —</option>
                                    @foreach($availableEmployees as $emp)
                                        <option value="{{ $emp->id }}" 
                                                {{ $user->employee && $user->employee->id == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->lastName }}, {{ $emp->name }} ({{ $emp->employeeId }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-sm text-gray-500">
                                    Al vincular un empleado, el usuario podrá acceder al Portal de Empleados.
                                </p>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <a href="{{ route('user.index') }}" 
                                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    Cancelar
                                </a>
                                <button type="submit" 
                                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Gestión de Roles --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Gestión de Roles
                            </h2>
                        </div>
                        <div class="p-6">
                            {{-- Roles Asignados --}}
                            <div class="mb-6">
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Roles Asignados</h3>
                                @if($user->roles->count() > 0)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($user->roles as $role)
                                            <div class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium
                                                @if(in_array(strtolower($role->name), ['admin', 'administrator', 'superadmin']))
                                                    bg-red-100 text-red-800 border border-red-200
                                                @elseif(in_array(strtolower($role->name), ['rrhh', 'hr', 'recursos humanos']))
                                                    bg-purple-100 text-purple-800 border border-purple-200
                                                @else
                                                    bg-indigo-100 text-indigo-800 border border-indigo-200
                                                @endif
                                            ">
                                                <span>{{ ucwords($role->name) }}</span>
                                                <a href="{{ route('role.detach', ['role' => $role->id, 'user' => $user->id]) }}"
                                                   class="ml-2 hover:opacity-70 transition-opacity"
                                                   onclick="return confirm('¿Remover el rol {{ $role->name }}?')">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <span class="text-amber-800 text-sm">
                                                Este usuario no tiene roles asignados. 
                                                @if(!$user->employee)
                                                    <strong>No podrá acceder al sistema.</strong>
                                                @else
                                                    Solo podrá acceder al Portal de Empleados.
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Agregar Roles --}}
                            <div class="pt-6 border-t">
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Agregar Rol</h3>
                                @php
                                    $availableRoles = $roles->filter(fn($r) => !$user->hasRole($r->name));
                                @endphp
                                @if($availableRoles->count() > 0)
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                        @foreach($availableRoles as $role)
                                            <a href="{{ route('role.attach', ['role' => $role->id, 'user' => $user->id]) }}"
                                               class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-indigo-300 transition-colors group">
                                                <span class="text-gray-700 group-hover:text-indigo-600">
                                                    {{ ucwords($role->name) }}
                                                </span>
                                                <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500 text-sm">Todos los roles disponibles ya están asignados.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Columna Lateral --}}
                <div class="space-y-6">
                    {{-- Resumen del Usuario --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xl font-bold">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr($user->lastName ?? '', 0, 1)) }}
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-semibold text-gray-900">{{ $user->name }} {{ $user->lastName }}</h3>
                                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                            
                            <div class="space-y-3 pt-4 border-t">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">ID</span>
                                    <span class="font-medium text-gray-900">#{{ $user->id }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Registrado</span>
                                    <span class="font-medium text-gray-900">{{ $user->created_at->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Último acceso</span>
                                    <span class="font-medium text-gray-900">
                                        {{ $user->updated_at ? $user->updated_at->diffForHumans() : '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Estado de Acceso --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gray-50">
                            <h3 class="font-semibold text-gray-900">Estado de Acceso</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            {{-- Verificación de acceso --}}
                            @php
                                $hasRoles = $user->roles->count() > 0;
                                $hasEmployee = $user->employee !== null;
                                $hasAccess = $hasRoles || $hasEmployee;
                            @endphp

                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Roles asignados</span>
                                @if($hasRoles)
                                    <span class="flex items-center text-green-600">
                                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Sí ({{ $user->roles->count() }})
                                    </span>
                                @else
                                    <span class="flex items-center text-red-600">
                                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                        No
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Empleado vinculado</span>
                                @if($hasEmployee)
                                    <span class="flex items-center text-green-600">
                                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Sí
                                    </span>
                                @else
                                    <span class="flex items-center text-gray-400">
                                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                        No
                                    </span>
                                @endif
                            </div>

                            <div class="pt-4 border-t">
                                @if($hasAccess)
                                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                            <div>
                                                <p class="font-medium text-green-800">Acceso habilitado</p>
                                                <p class="text-xs text-green-600">
                                                    @if($hasRoles && $hasEmployee)
                                                        Acceso completo + Portal
                                                    @elseif($hasRoles)
                                                        Acceso según roles
                                                    @else
                                                        Solo Portal de Empleados
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            <div>
                                                <p class="font-medium text-red-800">Sin acceso</p>
                                                <p class="text-xs text-red-600">
                                                    Asigne un rol o vincule un empleado
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Empleado Vinculado --}}
                    @if($user->employee)
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                            <div class="px-6 py-4 border-b bg-green-50">
                                <h3 class="font-semibold text-green-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Empleado Vinculado
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold">
                                        {{ strtoupper(substr($user->employee->name, 0, 1)) }}{{ strtoupper(substr($user->employee->lastName ?? '', 0, 1)) }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="font-medium text-gray-900">{{ $user->employee->name }} {{ $user->employee->lastName }}</p>
                                        <p class="text-sm text-gray-500">{{ $user->employee->employeeId }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('employee.profile', $user->employee) }}" 
                                   class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    Ver Perfil del Empleado
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Acciones --}}
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Acciones</h3>
                        <div class="space-y-2">
                            <a href="{{ route('user.index') }}" 
                               class="flex items-center w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                                Ver Todos los Usuarios
                            </a>
                            @if($roles->count() == 0)
                                <a href="{{ route('role.new') }}" 
                                   class="flex items-center w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                    <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Crear Nuevo Rol
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-manage>
