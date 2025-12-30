<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-4xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Asignar Concepto a Empleados</h1>
                    <p class="text-sm text-gray-600 mt-1">Seleccione los empleados que reciben este concepto</p>
                </div>
                <a href="{{ route('salary.edit', $salaryItem) }}" 
                   class="inline-flex items-center px-4 py-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </a>
            </div>

            {{-- Concepto Info Card --}}
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-2xl shadow-lg p-6 mb-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold">{{ $salaryItem->name }}</h2>
                        <p class="text-amber-100 text-sm mt-1">
                            {{ $salaryItem->type === 'haber' ? 'Haber' : 'Deducción' }} • 
                            {{ $salaryItem->calculation_type === 'percentage' ? $salaryItem->value . '%' : '$' . number_format($salaryItem->value, 2, ',', '.') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-3xl font-bold">{{ count($assignedIds) }}</span>
                        <p class="text-amber-100 text-sm">empleados asignados</p>
                    </div>
                </div>
            </div>

            {{-- Form Card --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <form action="{{ route('salary.assignments.save', $salaryItem) }}" method="POST">
                    @csrf
                    
                    {{-- Header con búsqueda y acciones rápidas --}}
                    <div class="bg-gray-50 px-6 py-4 border-b flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <input type="text" id="searchInput" placeholder="Buscar empleado..." 
                                   class="px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm w-64">
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="selectAll()" 
                                    class="px-3 py-1.5 text-sm text-amber-700 bg-amber-100 rounded-lg hover:bg-amber-200 transition-colors">
                                Seleccionar todos
                            </button>
                            <button type="button" onclick="selectNone()" 
                                    class="px-3 py-1.5 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                                Deseleccionar todos
                            </button>
                        </div>
                    </div>

                    {{-- Lista de empleados --}}
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3" id="employeesList">
                            @forelse($employees as $employee)
                                @php
                                    $isAssigned = in_array($employee->id, $assignedIds);
                                    $category = $employee->jobs->first()?->category;
                                @endphp
                                <label class="employee-item flex items-center p-3 rounded-xl border-2 cursor-pointer transition-all
                                              {{ $isAssigned ? 'border-amber-500 bg-amber-50' : 'border-gray-200 hover:border-amber-300 hover:bg-amber-50/50' }}"
                                       data-name="{{ strtolower($employee->name . ' ' . $employee->lastName) }}">
                                    <input type="checkbox" name="employees[]" value="{{ $employee->id }}"
                                           {{ $isAssigned ? 'checked' : '' }}
                                           class="employee-checkbox w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                           onchange="updateStyle(this)">
                                    <div class="ml-3 flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 truncate">
                                            {{ $employee->lastName }}, {{ $employee->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate">
                                            @if($category)
                                                {{ ucwords($category->name) }}
                                            @else
                                                <span class="text-gray-400">Sin categoría</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($isAssigned)
                                        <svg class="check-icon w-5 h-5 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="check-icon w-5 h-5 text-gray-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </label>
                            @empty
                                <div class="col-span-3 text-center py-12 text-gray-500">
                                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <p>No hay empleados registrados</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Footer con contador y botón guardar --}}
                    <div class="bg-gray-50 px-6 py-4 border-t flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <span id="selectedCount">{{ count($assignedIds) }}</span> empleados seleccionados
                        </div>
                        <button type="submit" 
                                class="px-6 py-3 bg-amber-600 text-white rounded-xl font-medium shadow-lg hover:bg-amber-700 focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-all">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar Asignaciones
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Búsqueda de empleados
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.employee-item').forEach(item => {
                const name = item.dataset.name;
                item.style.display = name.includes(searchTerm) ? 'flex' : 'none';
            });
        });

        // Seleccionar todos
        function selectAll() {
            document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
                checkbox.checked = true;
                updateStyle(checkbox);
            });
            updateCount();
        }

        // Deseleccionar todos
        function selectNone() {
            document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                updateStyle(checkbox);
            });
            updateCount();
        }

        // Actualizar estilo visual
        function updateStyle(checkbox) {
            const label = checkbox.closest('label');
            const icon = label.querySelector('.check-icon');
            
            if (checkbox.checked) {
                label.classList.remove('border-gray-200');
                label.classList.add('border-amber-500', 'bg-amber-50');
                icon.classList.remove('text-gray-300');
                icon.classList.add('text-amber-600');
            } else {
                label.classList.add('border-gray-200');
                label.classList.remove('border-amber-500', 'bg-amber-50');
                icon.classList.add('text-gray-300');
                icon.classList.remove('text-amber-600');
            }
            updateCount();
        }

        // Actualizar contador
        function updateCount() {
            const count = document.querySelectorAll('.employee-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = count;
        }

        // Inicializar contadores
        updateCount();
    </script>
</x-manage>






