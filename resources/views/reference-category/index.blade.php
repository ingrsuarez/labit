<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Categorías de Referencia</h1>
                <p class="text-gray-600 mt-1">Regulaciones y normativas para valores de referencia</p>
            </div>
            <a href="{{ route('tests.index') }}" 
               class="mt-4 md:mt-0 text-teal-600 hover:text-teal-800 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver a Determinaciones
            </a>
        </div>

        <!-- Alertas -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Formulario para crear -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nueva Categoría
                    </h2>
                    
                    <form action="{{ route('reference-categories.store') }}" method="POST">
                        @csrf
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                                <input type="text" name="name" required
                                       class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                       placeholder="Ej: Código Alimentario Argentino">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
                                <input type="text" name="code" required maxlength="20"
                                       class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm uppercase"
                                       placeholder="Ej: CAA">
                                <p class="text-xs text-gray-500 mt-1">Código corto único (máx 20 caracteres)</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                <textarea name="description" rows="2"
                                          class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                          placeholder="Descripción opcional..."></textarea>
                            </div>

                            <button type="submit" 
                                    class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium">
                                Crear Categoría
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Información -->
                <div class="mt-4 bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <h3 class="text-sm font-medium text-blue-800 mb-2">Ejemplos de categorías</h3>
                    <ul class="text-xs text-blue-700 space-y-1">
                        <li>• <strong>CAA</strong> - Código Alimentario Argentino</li>
                        <li>• <strong>AE</strong> - Agua Envasada</li>
                        <li>• <strong>RH</strong> - Recursos Hídricos</li>
                        <li>• <strong>OMS</strong> - Organización Mundial de la Salud</li>
                    </ul>
                </div>
            </div>

            <!-- Lista de categorías -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-teal-600 to-teal-700 text-white px-5 py-4">
                        <h2 class="text-lg font-semibold">Categorías Existentes</h2>
                        <p class="text-teal-100 text-sm">{{ $categories->count() }} categorías</p>
                    </div>

                    @if($categories->count() > 0)
                        <div class="divide-y divide-gray-200">
                            @foreach($categories as $category)
                                <div class="p-4 hover:bg-gray-50 {{ !$category->is_active ? 'bg-gray-100 opacity-60' : '' }}">
                                    <form action="{{ route('reference-categories.update', $category) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">Código</label>
                                                <input type="text" name="code" value="{{ $category->code }}" required
                                                       class="w-full rounded border-gray-300 text-sm font-mono font-bold uppercase">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-xs text-gray-500 mb-1">Nombre</label>
                                                <input type="text" name="name" value="{{ $category->name }}" required
                                                       class="w-full rounded border-gray-300 text-sm">
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label class="flex items-center">
                                                    <input type="checkbox" name="is_active" value="1" 
                                                           {{ $category->is_active ? 'checked' : '' }}
                                                           class="rounded border-gray-300 text-teal-600">
                                                    <span class="ml-1 text-xs text-gray-600">Activa</span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <input type="text" name="description" value="{{ $category->description }}"
                                                   class="w-full rounded border-gray-300 text-sm text-gray-500"
                                                   placeholder="Descripción...">
                                        </div>
                                        
                                        <div class="mt-3 flex justify-between items-center">
                                            <span class="text-xs text-gray-400">
                                                {{ $category->referenceValues->count() }} valores de referencia
                                            </span>
                                            <div class="flex gap-2">
                                                <button type="submit" 
                                                        class="px-3 py-1 bg-teal-100 text-teal-700 rounded hover:bg-teal-200 text-sm">
                                                    Guardar
                                                </button>
                                                @if($category->referenceValues->count() == 0)
                                                    <button type="button" 
                                                            onclick="if(confirm('¿Eliminar esta categoría?')) document.getElementById('delete-{{ $category->id }}').submit()"
                                                            class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-sm">
                                                        Eliminar
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <form id="delete-{{ $category->id }}" 
                                          action="{{ route('reference-categories.destroy', $category) }}" 
                                          method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <p>No hay categorías creadas</p>
                            <p class="text-sm mt-1">Creá la primera usando el formulario.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-lab-layout>



