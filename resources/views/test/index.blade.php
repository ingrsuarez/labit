<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Determinaciones</h1>
                <p class="text-gray-600 mt-1">Gestión de análisis y determinaciones del laboratorio</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-create').classList.remove('hidden')"
                    class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Determinación
            </button>
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

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[250px]">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Buscar por código o nombre..."
                           class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    Buscar
                </button>
                @if(request('search'))
                    <a href="{{ route('tests.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <!-- Tabla de Determinaciones -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Código
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nombre
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Unidad
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Método
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Valores Ref.
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tests as $test)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-teal-600 font-medium">{{ $test->code }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ ucfirst($test->name) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $test->unit ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $test->method ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('tests.reference-values.index', $test) }}" 
                                   class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                    @if($test->referenceValues && $test->referenceValues->count() > 0)
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded">
                                            {{ $test->referenceValues->count() }}
                                        </span>
                                    @elseif($test->low || $test->high)
                                        <span class="text-gray-500">{{ $test->low ?? '-' }} - {{ $test->high ?? '-' }}</span>
                                    @else
                                        <span class="text-gray-400">Configurar</span>
                                    @endif
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('tests.reference-values.index', $test) }}" 
                                   class="text-blue-600 hover:text-blue-900" title="Valores de referencia">
                                    Refs
                                </a>
                                <button type="button" 
                                        onclick="openEditModal({{ $test->id }}, '{{ $test->code }}', '{{ addslashes($test->name) }}', '{{ $test->unit }}', '{{ $test->method }}', '{{ $test->low }}', '{{ $test->high }}', '{{ $test->decimals }}', '{{ $test->nbu }}', '{{ addslashes($test->instructions) }}', '{{ $test->material }}', {{ json_encode($test->parentTests->pluck('id')->toArray()) }})"
                                        class="text-indigo-600 hover:text-indigo-900">
                                    Editar
                                </button>
                                <form action="{{ route('tests.destroy', $test) }}" method="POST" class="inline"
                                      onsubmit="return confirm('¿Eliminar esta determinación?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                                <p class="mt-2">No hay determinaciones registradas</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($tests->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $tests->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Crear -->
    <div id="modal-create" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <form action="{{ route('test.store') }}" method="POST">
                @csrf
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Nueva Determinación</h3>
                </div>
                
                <div class="px-6 py-4">
                    @if($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
                            <input type="text" name="code" required
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="Ej: COL-T">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input type="text" name="name" required
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="Ej: Coliformes Totales">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unidad</label>
                            <input type="text" name="unit"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="Ej: UFC/100ml">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Método</label>
                            <input type="text" name="method"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="Ej: Filtración por membrana">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valor Mínimo</label>
                            <input type="text" name="low"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="Valor de referencia mínimo">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valor Máximo</label>
                            <input type="text" name="high"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="Valor de referencia máximo">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Decimales</label>
                            <input type="number" name="decimals" value="2" min="0" max="6"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NBU</label>
                            <input type="number" name="nbu"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Material</label>
                            <select name="material" class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                                <option value="">Ninguno</option>
                                @foreach($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Análisis Padres</label>
                            <p class="text-xs text-gray-500 mb-2">Puede seleccionar múltiples padres. Dejar vacío si esta determinación es un padre.</p>
                            <input type="text" id="create-parent-search" 
                                   placeholder="🔍 Buscar por código o nombre..."
                                   class="w-full mb-2 rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                   onkeyup="filterParentOptions('create-parent-search', 'create-parent-ids')">
                            <div class="relative">
                                <select name="parent_ids[]" id="create-parent-ids" multiple size="6"
                                        class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                                    @foreach($parents as $parent)
                                        <option value="{{ $parent->id }}" data-search="{{ strtolower($parent->code . ' ' . $parent->name) }}">{{ $parent->code }} - {{ ucfirst($parent->name) }}</option>
                                    @endforeach
                                </select>
                                <div id="create-parent-ids-count" class="absolute bottom-1 right-2 text-xs text-gray-400"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Ctrl+Click para seleccionar múltiples</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Instrucciones</label>
                        <textarea name="instructions" rows="2"
                                  class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                  placeholder="Instrucciones para la toma de muestra..."></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        Crear Determinación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar -->
    <div id="modal-edit" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <form id="form-edit" method="POST">
                @csrf
                @method('PUT')
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Editar Determinación</h3>
                </div>
                
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
                            <input type="text" name="code" id="edit-code" required
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input type="text" name="name" id="edit-name" required
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unidad</label>
                            <input type="text" name="unit" id="edit-unit"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Método</label>
                            <input type="text" name="method" id="edit-method"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valor Mínimo</label>
                            <input type="text" name="low" id="edit-low"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valor Máximo</label>
                            <input type="text" name="high" id="edit-high"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Decimales</label>
                            <input type="number" name="decimals" id="edit-decimals" min="0" max="6"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NBU</label>
                            <input type="number" name="nbu" id="edit-nbu"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Material</label>
                            <select name="material" id="edit-material" class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                                <option value="">Ninguno</option>
                                @foreach($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Análisis Padres</label>
                            <p class="text-xs text-gray-500 mb-2">Puede seleccionar múltiples padres. Dejar vacío si esta determinación es un padre.</p>
                            <input type="text" id="edit-parent-search" 
                                   placeholder="🔍 Buscar por código o nombre..."
                                   class="w-full mb-2 rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 text-sm"
                                   onkeyup="filterParentOptions('edit-parent-search', 'edit-parent-ids')">
                            <div class="relative">
                                <select name="parent_ids[]" id="edit-parent-ids" multiple size="6"
                                        class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                                    @foreach($parents as $parent)
                                        <option value="{{ $parent->id }}" data-search="{{ strtolower($parent->code . ' ' . $parent->name) }}">{{ $parent->code }} - {{ ucfirst($parent->name) }}</option>
                                    @endforeach
                                </select>
                                <div id="edit-parent-ids-count" class="absolute bottom-1 right-2 text-xs text-gray-400"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Ctrl+Click para seleccionar múltiples</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Instrucciones</label>
                        <textarea name="instructions" id="edit-instructions" rows="2"
                                  class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Función para filtrar opciones del selector de padres
        function filterParentOptions(searchInputId, selectId) {
            const searchInput = document.getElementById(searchInputId);
            const select = document.getElementById(selectId);
            const countDiv = document.getElementById(selectId + '-count');
            const searchTerm = searchInput.value.toLowerCase().trim();
            
            let visibleCount = 0;
            let totalCount = 0;
            
            Array.from(select.options).forEach(option => {
                totalCount++;
                const searchData = option.getAttribute('data-search') || option.text.toLowerCase();
                
                if (searchTerm === '' || searchData.includes(searchTerm)) {
                    option.style.display = '';
                    option.disabled = false;
                    visibleCount++;
                } else {
                    option.style.display = 'none';
                    option.disabled = true;
                }
            });
            
            // Mostrar contador de resultados
            if (countDiv) {
                if (searchTerm !== '') {
                    countDiv.textContent = visibleCount + ' de ' + totalCount;
                } else {
                    countDiv.textContent = '';
                }
            }
        }

        // Función para limpiar el buscador cuando se abre el modal
        function clearParentSearch(searchInputId, selectId) {
            const searchInput = document.getElementById(searchInputId);
            if (searchInput) {
                searchInput.value = '';
                filterParentOptions(searchInputId, selectId);
            }
        }

        function openEditModal(id, code, name, unit, method, low, high, decimals, nbu, instructions, material, parentIds) {
            document.getElementById('form-edit').action = '/tests/' + id;
            document.getElementById('edit-code').value = code;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-unit').value = unit || '';
            document.getElementById('edit-method').value = method || '';
            document.getElementById('edit-low').value = low || '';
            document.getElementById('edit-high').value = high || '';
            document.getElementById('edit-decimals').value = decimals || 2;
            document.getElementById('edit-nbu').value = nbu || '';
            document.getElementById('edit-instructions').value = instructions || '';
            document.getElementById('edit-material').value = material || '';
            
            // Limpiar el buscador de padres
            clearParentSearch('edit-parent-search', 'edit-parent-ids');
            
            // Seleccionar múltiples padres
            const parentSelect = document.getElementById('edit-parent-ids');
            Array.from(parentSelect.options).forEach(option => {
                option.selected = parentIds && parentIds.includes(parseInt(option.value));
            });
            
            document.getElementById('modal-edit').classList.remove('hidden');
        }

        // Cerrar modales con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('modal-create').classList.add('hidden');
                document.getElementById('modal-edit').classList.add('hidden');
            }
        });

        // Limpiar el buscador cuando se abre el modal de crear
        document.querySelector('button[onclick*="modal-create"]')?.addEventListener('click', function() {
            clearParentSearch('create-parent-search', 'create-parent-ids');
        });

        // Auto-abrir modal de edición si viene el parámetro edit en la URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            if (editId) {
                // Buscar el test en la tabla por su ID
                const testData = @json($tests->keyBy('id'));
                if (testData[editId]) {
                    const test = testData[editId];
                    const parentIds = test.parent_tests ? test.parent_tests.map(p => p.id) : [];
                    openEditModal(
                        test.id,
                        test.code,
                        test.name,
                        test.unit,
                        test.method,
                        test.low,
                        test.high,
                        test.decimals,
                        test.nbu,
                        test.instructions,
                        test.material,
                        parentIds
                    );
                }
            }

            // Auto-abrir modal de crear si hay errores de validación
            @if($errors->any())
                document.getElementById('modal-create').classList.remove('hidden');
            @endif
        });
    </script>
</x-lab-layout>
