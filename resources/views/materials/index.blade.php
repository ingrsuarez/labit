<x-lab-layout>
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Materiales</h1>
            <p class="text-gray-600 text-sm mt-1">Gestión de materiales para determinaciones</p>
        </div>
        <button onclick="openCreateModal()" 
                class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Material
        </button>
    </div>

    <!-- Mensajes Flash -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            {{ session('error') }}
        </div>
    @endif

    <!-- Tabla de Materiales -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        @if($materials->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unidad</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($materials as $material)
                            <tr class="hover:bg-gray-50 {{ !$material->is_active ? 'opacity-50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">{{ $material->code }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">{{ $material->name }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-500">{{ Str::limit($material->description, 50) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-500">{{ $material->unit ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($material->isLowStock())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            {{ number_format($material->stock, 2) }}
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-900">{{ number_format($material->stock, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($material->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <button onclick="openEditModal({{ json_encode($material) }})" 
                                            class="text-teal-600 hover:text-teal-900 mr-3">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button onclick="confirmDelete({{ $material->id }})" 
                                            class="text-red-600 hover:text-red-900">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay materiales</h3>
                <p class="mt-1 text-sm text-gray-500">Comenzá agregando un nuevo material.</p>
                <div class="mt-6">
                    <button onclick="openCreateModal()" 
                            class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Material
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal Crear/Editar Material -->
<div id="materialModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal()"></div>
        
        <div class="relative inline-block w-full max-w-lg p-6 my-8 text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Nuevo Material</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="materialForm" method="POST">
                @csrf
                <input type="hidden" id="formMethod" name="_method" value="POST">
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
                            <input type="text" name="code" id="inputCode" required
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="MAT-001">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unidad</label>
                            <input type="text" name="unit" id="inputUnit"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="ml, gr, unidad">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="name" id="inputName" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                               placeholder="Nombre del material">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea name="description" id="inputDescription" rows="2"
                                  class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                  placeholder="Descripción opcional"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock Actual</label>
                            <input type="number" name="stock" id="inputStock" step="0.01" min="0"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo</label>
                            <input type="number" name="min_stock" id="inputMinStock" step="0.01" min="0"
                                   class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                                   placeholder="0.00">
                        </div>
                    </div>
                    
                    <div id="activeField" class="hidden">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" id="inputActive" value="1"
                                   class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Material activo</span>
                        </label>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form para eliminar -->
<form id="deleteForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
    const modal = document.getElementById('materialModal');
    const form = document.getElementById('materialForm');
    const modalTitle = document.getElementById('modalTitle');
    const formMethod = document.getElementById('formMethod');
    const activeField = document.getElementById('activeField');

    function openCreateModal() {
        modalTitle.textContent = 'Nuevo Material';
        form.action = '{{ route("materials.store") }}';
        formMethod.value = 'POST';
        activeField.classList.add('hidden');
        
        // Limpiar campos
        document.getElementById('inputCode').value = '';
        document.getElementById('inputName').value = '';
        document.getElementById('inputDescription').value = '';
        document.getElementById('inputUnit').value = '';
        document.getElementById('inputStock').value = '';
        document.getElementById('inputMinStock').value = '';
        
        modal.classList.remove('hidden');
    }

    function openEditModal(material) {
        modalTitle.textContent = 'Editar Material';
        form.action = `/materials/${material.id}`;
        formMethod.value = 'PUT';
        activeField.classList.remove('hidden');
        
        // Llenar campos
        document.getElementById('inputCode').value = material.code;
        document.getElementById('inputName').value = material.name;
        document.getElementById('inputDescription').value = material.description || '';
        document.getElementById('inputUnit').value = material.unit || '';
        document.getElementById('inputStock').value = material.stock;
        document.getElementById('inputMinStock').value = material.min_stock;
        document.getElementById('inputActive').checked = material.is_active;
        
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    function confirmDelete(id) {
        if (confirm('¿Estás seguro de que deseas eliminar este material?')) {
            const form = document.getElementById('deleteForm');
            form.action = `/materials/${id}`;
            form.submit();
        }
    }

    // Cerrar modal con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
</script>
</x-lab-layout>
