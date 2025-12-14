<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        @php
            // Verificar si este test es un padre (tiene hijos)
            $allChildren = $test->getAllChildren();
            $isParent = $allChildren->count() > 0;
        @endphp

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-start md:justify-between mb-6">
            <div>
                <a href="{{ route('tests.index') }}" class="text-teal-600 hover:text-teal-800 text-sm flex items-center mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver a Determinaciones
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Valores de Referencia</h1>
                <p class="text-gray-600 mt-1">
                    <span class="font-semibold text-teal-600">{{ $test->name }}</span> 
                    <span class="text-gray-400">({{ $test->code }})</span>
                    @if($isParent)
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Grupo ({{ $allChildren->count() }} subdeterminaciones)
                        </span>
                    @endif
                </p>
            </div>
        </div>

        <!-- Alertas -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($isParent)
            @php
                // Calcular categorías usadas (se usa en varios lugares)
                $usedCategories = collect();
                foreach($allChildren as $child) {
                    if ($child->referenceValues) {
                        foreach ($child->referenceValues as $refVal) {
                            if ($refVal->category && !$usedCategories->contains('id', $refVal->category->id)) {
                                $usedCategories->push($refVal->category);
                            }
                        }
                    }
                }
            @endphp
            
            {{-- VISTA PARA DETERMINACIONES PADRE --}}
            <div class="bg-gradient-to-br from-teal-50 to-cyan-50 rounded-lg shadow-sm p-6 border border-teal-200 mb-6">
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-teal-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-teal-800 mb-2">Esta es una determinación de grupo</h2>
                        <p class="text-teal-700 mb-4">
                            <strong>{{ $test->name }}</strong> es una determinación padre que agrupa {{ $allChildren->count() }} subdeterminaciones.
                            Los valores de referencia deben configurarse en cada subdeterminación individual.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Selector de categoría predeterminada -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    Categoría/Normativa Predeterminada
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    Seleccioná la normativa que se aplicará automáticamente a todas las subdeterminaciones cuando se cree un nuevo protocolo con este grupo.
                </p>
                
                <form action="{{ route('tests.update-default-category', $test) }}" method="POST" class="flex items-end gap-4">
                    @csrf
                    @method('PUT')
                    
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Normativa predeterminada</label>
                        <select name="default_reference_category_id" 
                                class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="">-- Sin predeterminado (seleccionar manualmente) --</option>
                            @foreach($usedCategories->sortBy('name') as $category)
                                <option value="{{ $category->id }}" {{ $test->default_reference_category_id == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }} ({{ $category->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar
                    </button>
                </form>
                
                @if($test->default_reference_category_id && $test->defaultReferenceCategory)
                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center text-green-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-medium">Normativa activa: {{ $test->defaultReferenceCategory->name }} ({{ $test->defaultReferenceCategory->code }})</span>
                        </div>
                        <p class="text-sm text-green-600 mt-1">
                            Al crear un protocolo con este grupo, los valores de referencia de esta normativa se asignarán automáticamente.
                        </p>
                    </div>
                @else
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center text-yellow-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span class="font-medium">Sin normativa predeterminada</span>
                        </div>
                        <p class="text-sm text-yellow-600 mt-1">
                            Deberás seleccionar la normativa manualmente al cargar resultados en cada protocolo.
                        </p>
                    </div>
                @endif
            </div>

            <!-- Lista de subdeterminaciones -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-teal-600 to-teal-700 text-white px-5 py-4">
                    <h2 class="text-lg font-semibold">Subdeterminaciones de este grupo</h2>
                    <p class="text-teal-100 text-sm">Configurá los valores de referencia en cada una</p>
                </div>

                <div class="divide-y divide-gray-200">
                    @foreach($allChildren as $child)
                        @php
                            $childRefCount = $child->referenceValues ? $child->referenceValues->count() : 0;
                        @endphp
                        <div class="p-4 hover:bg-gray-50 flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="text-teal-400 mr-3">↳</span>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $child->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $child->code }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($childRefCount > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $childRefCount }} valor{{ $childRefCount > 1 ? 'es' : '' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Sin configurar
                                    </span>
                                @endif
                                <a href="{{ route('tests.reference-values.index', $child) }}" 
                                   class="px-3 py-1.5 bg-teal-100 text-teal-700 rounded-lg hover:bg-teal-200 text-sm font-medium transition-colors">
                                    Configurar
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Resumen de categorías configuradas en subdeterminaciones -->
            @if($usedCategories->count() > 0)
                <div class="mt-6 bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Categorías/Normativas configuradas en este grupo
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($usedCategories->sortBy('name') as $category)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                {{ $category->name }} ({{ $category->code }})
                            </span>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        Estas son las normativas que estarán disponibles para seleccionar cuando cargues resultados de este grupo.
                    </p>
                </div>
            @else
                <div class="mt-6 bg-yellow-50 rounded-lg shadow-sm p-5 border border-yellow-200">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-500 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <h3 class="text-sm font-semibold text-yellow-800 mb-1">Sin normativas configuradas</h3>
                            <p class="text-sm text-yellow-700">
                                Ninguna subdeterminación tiene valores de referencia configurados aún.
                                Configurá los valores en cada subdeterminación para poder seleccionar normativas al cargar resultados.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

        @else
            {{-- VISTA PARA DETERMINACIONES NORMALES (NO PADRES) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Formulario para agregar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Valor de Referencia
                    </h2>
                    
                    <form action="{{ route('tests.reference-values.store', $test) }}" method="POST">
                        @csrf
                        
                        <div class="space-y-4">
                            <!-- Categoría existente o nueva -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría/Regulación</label>
                                <select name="category_id" id="category_select"
                                        class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                        onchange="toggleNewCategory()">
                                    <option value="">-- Sin categoría --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                    <option value="new">+ Nueva categoría...</option>
                                </select>
                            </div>

                            <!-- Nueva categoría (oculto por defecto) -->
                            <div id="new_category_div" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de nueva categoría</label>
                                <input type="text" name="category_name" 
                                       class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                       placeholder="Ej: Código Alimentario Argentino">
                            </div>

                            <!-- Min/Max primero -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mínimo</label>
                                    <input type="text" name="min_value" id="min_value"
                                           class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                           placeholder="Ej: 6.5"
                                           oninput="generateReferenceValue()">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Máximo</label>
                                    <input type="text" name="max_value" id="max_value"
                                           class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                           placeholder="Ej: 8.5"
                                           oninput="generateReferenceValue()">
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 -mt-2">Completá mínimo y/o máximo para generar el valor automáticamente</p>

                            <!-- Valor de referencia (autogenerado o manual) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valor de Referencia *</label>
                                <input type="text" name="value" id="ref_value" required
                                       class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                       placeholder="Se genera automáticamente o escribí manualmente">
                                <p class="text-xs text-gray-500 mt-1">Ejemplos: "6.5 - 8.5", "< 500 UFC/ml", "Ausente"</p>
                            </div>

                            <!-- Es default -->
                            <div class="flex items-center">
                                <input type="checkbox" name="is_default" value="1" id="is_default"
                                       class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                <label for="is_default" class="ml-2 text-sm text-gray-700">
                                    Valor predeterminado
                                </label>
                            </div>

                            <button type="submit" 
                                    class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium">
                                Agregar Valor
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Gestionar categorías -->
                <div class="mt-4">
                    <a href="{{ route('reference-categories.index') }}" 
                       class="w-full block px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-center text-sm">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Gestionar Categorías
                    </a>
                </div>

                <!-- Información -->
                <div class="mt-4 bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <h3 class="text-sm font-medium text-blue-800 mb-2">Información</h3>
                    <ul class="text-xs text-blue-700 space-y-1">
                        <li>• Las <strong>categorías</strong> representan regulaciones o estándares (ej: CAA, Agua Envasada).</li>
                        <li>• El <strong>valor predeterminado</strong> se usará automáticamente al crear protocolos.</li>
                        <li>• Al cargar resultados, el usuario podrá elegir qué valor de referencia aplicar.</li>
                    </ul>
                </div>
            </div>

            <!-- Lista de valores existentes -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-teal-600 to-teal-700 text-white px-5 py-4">
                        <h2 class="text-lg font-semibold">Valores de Referencia Configurados</h2>
                        <p class="text-teal-100 text-sm">{{ $test->referenceValues->count() }} valores</p>
                    </div>

                    @if($test->referenceValues->count() > 0)
                        <div class="divide-y divide-gray-200">
                            @foreach($test->referenceValues as $refValue)
                                <div class="p-4 hover:bg-gray-50 {{ $refValue->is_default ? 'bg-green-50' : '' }}">
                                    <form action="{{ route('tests.reference-values.update', [$test, $refValue]) }}" method="POST" class="space-y-3">
                                        @csrf
                                        @method('PUT')
                                        
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-2">
                                                    @if($refValue->is_default)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                            ★ Predeterminado
                                                        </span>
                                                    @endif
                                                    @if($refValue->category)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                            {{ $refValue->category->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                                    <div class="md:col-span-1">
                                                        <label class="block text-xs text-gray-500 mb-1">Categoría</label>
                                                        <select name="category_id" class="w-full rounded border-gray-300 text-sm">
                                                            <option value="">Sin categoría</option>
                                                            @foreach($categories as $category)
                                                                <option value="{{ $category->id }}" {{ $refValue->reference_category_id == $category->id ? 'selected' : '' }}>
                                                                    {{ $category->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="md:col-span-1">
                                                        <label class="block text-xs text-gray-500 mb-1">Valor *</label>
                                                        <input type="text" name="value" value="{{ $refValue->value }}" required
                                                               class="w-full rounded border-gray-300 text-sm font-medium">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Mínimo</label>
                                                        <input type="text" name="min_value" value="{{ $refValue->min_value }}"
                                                               class="w-full rounded border-gray-300 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-1">Máximo</label>
                                                        <input type="text" name="max_value" value="{{ $refValue->max_value }}"
                                                               class="w-full rounded border-gray-300 text-sm">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center justify-between pt-2">
                                            <div class="flex items-center">
                                                <input type="checkbox" name="is_default" value="1" id="default_{{ $refValue->id }}"
                                                       {{ $refValue->is_default ? 'checked' : '' }}
                                                       class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                                <label for="default_{{ $refValue->id }}" class="ml-2 text-sm text-gray-600">
                                                    Predeterminado
                                                </label>
                                            </div>
                                            
                                            <div class="flex items-center gap-2">
                                                <button type="submit" 
                                                        class="px-3 py-1 bg-teal-100 text-teal-700 rounded hover:bg-teal-200 text-sm">
                                                    Guardar
                                                </button>
                                                <button type="button" 
                                                        onclick="if(confirm('¿Eliminar este valor?')) document.getElementById('delete-form-{{ $refValue->id }}').submit()"
                                                        class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-sm">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <form id="delete-form-{{ $refValue->id }}" 
                                          action="{{ route('tests.reference-values.destroy', [$test, $refValue]) }}" 
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p>No hay valores de referencia configurados</p>
                            <p class="text-sm mt-1">Usa el formulario de la izquierda para agregar el primero.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <script>
            function toggleNewCategory() {
                const select = document.getElementById('category_select');
                const newCategoryDiv = document.getElementById('new_category_div');
                
                if (select.value === 'new') {
                    newCategoryDiv.classList.remove('hidden');
                    select.value = '';
                } else {
                    newCategoryDiv.classList.add('hidden');
                }
            }

            function generateReferenceValue() {
                const minVal = document.getElementById('min_value').value.trim();
                const maxVal = document.getElementById('max_value').value.trim();
                const refInput = document.getElementById('ref_value');
                
                // Solo generar si el usuario no ha escrito manualmente
                // o si el valor actual parece autogenerado
                const currentVal = refInput.value.trim();
                const looksAutogenerated = currentVal === '' || 
                                           currentVal.match(/^[\d.,\s]+\-[\d.,\s]+$/) ||
                                           currentVal.match(/^[<>≤≥]\s*[\d.,]+/) ||
                                           currentVal.match(/^[\d.,]+\s*[<>≤≥]/);

                if (!looksAutogenerated && currentVal !== '') {
                    return; // No sobrescribir valores manuales
                }

                let generated = '';
                
                if (minVal && maxVal) {
                    // Rango: "6.5 - 8.5"
                    generated = `${minVal} - ${maxVal}`;
                } else if (maxVal && !minVal) {
                    // Solo máximo: "< 500"
                    generated = `< ${maxVal}`;
                } else if (minVal && !maxVal) {
                    // Solo mínimo: "> 10"
                    generated = `> ${minVal}`;
                }
                
                if (generated) {
                    refInput.value = generated;
                }
            }
        </script>
        @endif
    </div>
</x-lab-layout>



