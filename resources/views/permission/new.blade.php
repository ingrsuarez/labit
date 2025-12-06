<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-7xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestión de Permisos</h1>
                    <p class="text-sm text-gray-500">Administra los permisos del sistema</p>
                </div>
                <a href="{{ route('role.new') }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Gestionar Roles
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

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-red-800">{{ session('error') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-blue-800">{{ session('info') }}</span>
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
                {{-- Columna Izquierda: Formularios --}}
                <div class="lg:col-span-1 space-y-6">
                    {{-- Crear Permiso Individual --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Crear Permiso
                            </h2>
                        </div>
                        <form action="{{ route('permission.store') }}" method="POST" class="p-6">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nombre del Permiso <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name" id="name" required
                                           value="{{ old('name') }}"
                                           placeholder="ej: employee.edit, report.view"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                                    <p class="mt-1 text-xs text-gray-500">Formato: modulo.accion (ej: employee.edit)</p>
                                </div>
                                
                                <div>
                                    <label for="guard_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Guard Name
                                    </label>
                                    <select name="guard_name" id="guard_name"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors bg-white">
                                        <option value="web" {{ old('guard_name', 'web') == 'web' ? 'selected' : '' }}>web</option>
                                        <option value="api" {{ old('guard_name') == 'api' ? 'selected' : '' }}>api</option>
                                        <option value="sanctum" {{ old('guard_name') == 'sanctum' ? 'selected' : '' }}>sanctum</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" 
                                    class="mt-6 w-full px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Crear Permiso
                            </button>
                        </form>
                    </div>

                    {{-- Generador de Permisos por Módulo --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                Generar por Módulo
                            </h2>
                        </div>
                        <form action="{{ route('permission.generateModule') }}" method="POST" class="p-6">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label for="module" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nombre del Módulo <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="module" id="module" required
                                           placeholder="ej: report, invoice, client"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Acciones <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach(['index', 'show', 'new', 'store', 'edit', 'save', 'delete'] as $action)
                                            <label class="flex items-center p-2 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" name="actions[]" value="{{ $action }}"
                                                       class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
                                                       {{ in_array($action, ['index', 'show', 'new', 'store', 'edit', 'save']) ? 'checked' : '' }}>
                                                <span class="ml-2 text-sm text-gray-700">{{ $action }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <label for="module_guard" class="block text-sm font-medium text-gray-700 mb-2">
                                        Guard Name
                                    </label>
                                    <select name="guard_name" id="module_guard"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors bg-white">
                                        <option value="web" selected>web</option>
                                        <option value="api">api</option>
                                        <option value="sanctum">sanctum</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" 
                                    class="mt-6 w-full px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-colors flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Generar Permisos
                            </button>
                        </form>
                    </div>

                    {{-- Estadísticas --}}
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumen</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg">
                                <span class="text-amber-800">Total Permisos</span>
                                <span class="text-2xl font-bold text-amber-600">{{ $permissions->count() }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-teal-50 rounded-lg">
                                <span class="text-teal-800">Módulos</span>
                                <span class="text-2xl font-bold text-teal-600">{{ $groupedPermissions->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Columna Derecha: Lista de Permisos --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Permisos del Sistema ({{ $permissions->count() }})
                            </h2>
                        </div>
                        <div class="p-6">
                            @if($permissions->count() > 0)
                                {{-- Búsqueda --}}
                                <div class="mb-6">
                                    <input type="text" id="searchPermissions" 
                                           placeholder="Buscar permisos..."
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                </div>

                                {{-- Permisos agrupados por módulo --}}
                                <div class="space-y-4" id="permissionsList">
                                    @foreach($groupedPermissions as $module => $modulePermissions)
                                        <div class="permission-module border rounded-xl overflow-hidden" data-module="{{ $module }}">
                                            <button type="button" 
                                                    onclick="toggleModule('{{ $module }}')"
                                                    class="w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 transition-colors flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3
                                                        @if(in_array($module, ['admin', 'role', 'permission', 'user']))
                                                            bg-red-500 text-white
                                                        @elseif(in_array($module, ['portal', 'profile', 'vacation', 'leave']))
                                                            bg-green-500 text-white
                                                        @elseif(in_array($module, ['employee', 'job', 'category']))
                                                            bg-blue-500 text-white
                                                        @else
                                                            bg-gray-500 text-white
                                                        @endif
                                                    ">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                                        </svg>
                                                    </div>
                                                    <span class="font-semibold text-gray-900">{{ ucfirst($module) }}</span>
                                                    <span class="ml-2 px-2 py-0.5 text-xs bg-gray-200 text-gray-600 rounded-full">
                                                        {{ $modulePermissions->count() }}
                                                    </span>
                                                </div>
                                                <svg class="w-5 h-5 text-gray-400 transform transition-transform module-icon" id="icon-{{ $module }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                            <div class="hidden module-content" id="content-{{ $module }}">
                                                <div class="divide-y">
                                                    @foreach($modulePermissions as $permission)
                                                        <div class="permission-item flex items-center justify-between px-4 py-3 hover:bg-gray-50" 
                                                             data-name="{{ strtolower($permission->name) }}">
                                                            <div class="flex items-center">
                                                                <span class="w-2 h-2 rounded-full bg-amber-400 mr-3"></span>
                                                                <div>
                                                                    <p class="text-sm font-medium text-gray-900">{{ $permission->name }}</p>
                                                                    <p class="text-xs text-gray-500">
                                                                        Guard: {{ $permission->guard_name }}
                                                                        @if($permission->roles_count > 0)
                                                                            · {{ $permission->roles_count }} rol(es)
                                                                        @endif
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center space-x-2">
                                                                <a href="{{ route('permission.edit', $permission) }}" 
                                                                   class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                                   title="Editar">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                                    </svg>
                                                                </a>
                                                                <form action="{{ route('permission.destroy', $permission) }}" method="POST" 
                                                                      onsubmit="return confirm('¿Eliminar el permiso {{ $permission->name }}?')"
                                                                      class="inline">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" 
                                                                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                                            title="Eliminar">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                        </svg>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay permisos creados</h3>
                                    <p class="text-gray-500 mb-4">Crea tu primer permiso o usa el generador de módulos</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Permisos Sugeridos --}}
                    @if($permissions->count() < 10)
                        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-2xl p-6">
                            <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Módulos Sugeridos
                            </h3>
                            <p class="text-sm text-blue-800 mb-4">Usa el generador para crear permisos rápidamente:</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                @foreach(['portal', 'employee', 'vacation', 'leave', 'report', 'setting'] as $suggestedModule)
                                    <div class="p-3 bg-white rounded-lg border border-blue-200 text-center">
                                        <p class="font-medium text-gray-900">{{ $suggestedModule }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleModule(module) {
            const content = document.getElementById('content-' + module);
            const icon = document.getElementById('icon-' + module);
            
            content.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }

        // Búsqueda de permisos
        document.getElementById('searchPermissions')?.addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            const modules = document.querySelectorAll('.permission-module');
            
            modules.forEach(module => {
                const items = module.querySelectorAll('.permission-item');
                let hasVisibleItems = false;
                
                items.forEach(item => {
                    const name = item.dataset.name;
                    if (name.includes(search)) {
                        item.classList.remove('hidden');
                        hasVisibleItems = true;
                    } else {
                        item.classList.add('hidden');
                    }
                });
                
                // Mostrar/ocultar el módulo completo
                if (search === '') {
                    module.classList.remove('hidden');
                    module.querySelector('.module-content').classList.add('hidden');
                } else if (hasVisibleItems) {
                    module.classList.remove('hidden');
                    module.querySelector('.module-content').classList.remove('hidden');
                } else {
                    module.classList.add('hidden');
                }
            });
        });
    </script>
</x-manage>
