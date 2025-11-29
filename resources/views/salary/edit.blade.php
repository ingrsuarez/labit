<x-manage>
    <div class="max-w-3xl mx-auto p-6">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            {{-- Encabezado --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Editar Concepto de Sueldo</h2>
                    <p class="text-sm text-gray-600 mt-1">Modificar: <span class="font-medium capitalize">{{ $salaryItem->name }}</span></p>
                </div>
                <a href="{{ route('salary.index') }}" 
                   class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
            </div>

            {{-- Errores de validación --}}
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('salary.update', $salaryItem) }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Nombre --}}
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nombre del concepto *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $salaryItem->name) }}" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Código --}}
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">Código (opcional)</label>
                        <input type="text" name="code" id="code" value="{{ old('code', $salaryItem->code) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Tipo --}}
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Tipo *</label>
                        <select name="type" id="type" required
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="haber" {{ old('type', $salaryItem->type) === 'haber' ? 'selected' : '' }}>Haber (suma al sueldo)</option>
                            <option value="deduccion" {{ old('type', $salaryItem->type) === 'deduccion' ? 'selected' : '' }}>Deducción (resta del sueldo)</option>
                        </select>
                    </div>

                    {{-- Tipo de Cálculo --}}
                    <div>
                        <label for="calculation_type" class="block text-sm font-medium text-gray-700">Tipo de Cálculo *</label>
                        <select name="calculation_type" id="calculation_type" required
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="percentage" {{ old('calculation_type', $salaryItem->calculation_type) === 'percentage' ? 'selected' : '' }}>Porcentaje (%)</option>
                            <option value="fixed" {{ old('calculation_type', $salaryItem->calculation_type) === 'fixed' ? 'selected' : '' }}>Monto Fijo ($)</option>
                            <option value="hours" {{ old('calculation_type', $salaryItem->calculation_type) === 'hours' ? 'selected' : '' }}>Por Horas</option>
                        </select>
                    </div>

                    {{-- Valor --}}
                    <div>
                        <label for="value" class="block text-sm font-medium text-gray-700">Valor *</label>
                        <div class="mt-1 relative">
                            <input type="number" name="value" id="value" value="{{ old('value', $salaryItem->value) }}" 
                                   step="0.01" min="0" required
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 pr-12">
                            <span id="value_suffix" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">
                                {{ $salaryItem->calculation_type === 'percentage' ? '%' : ($salaryItem->calculation_type === 'hours' ? '/hs' : '$') }}
                            </span>
                        </div>
                    </div>

                    {{-- Orden --}}
                    <div>
                        <label for="order" class="block text-sm font-medium text-gray-700">Orden de aparición</label>
                        <input type="number" name="order" id="order" value="{{ old('order', $salaryItem->order) }}" min="0"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Descripción --}}
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Descripción (opcional)</label>
                        <textarea name="description" id="description" rows="2"
                                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('description', $salaryItem->description) }}</textarea>
                    </div>

                    {{-- Opciones --}}
                    <div class="md:col-span-2 flex flex-wrap gap-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" 
                                   {{ old('is_active', $salaryItem->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Activo</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="is_remunerative" value="1" 
                                   {{ old('is_remunerative', $salaryItem->is_remunerative) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Remunerativo</span>
                        </label>
                    </div>

                    {{-- Período de aplicación --}}
                    @php
                        $periodType = 'all_year';
                        if ($salaryItem->recurrent_month) $periodType = 'recurrent';
                        elseif ($salaryItem->specific_month && $salaryItem->specific_year) $periodType = 'specific';
                        elseif (!$salaryItem->applies_all_year) $periodType = 'specific';
                    @endphp
                    <div class="md:col-span-2 border-t pt-6 mt-2">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Período de aplicación</h3>
                        
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="radio" name="period_type" value="all_year" 
                                       {{ old('period_type', $periodType) === 'all_year' ? 'checked' : '' }}
                                       onchange="togglePeriodFields()"
                                       class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Aplica todo el año</span>
                            </label>

                            <label class="flex items-center">
                                <input type="radio" name="period_type" value="recurrent" 
                                       {{ old('period_type', $periodType) === 'recurrent' ? 'checked' : '' }}
                                       onchange="togglePeriodFields()"
                                       class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Se repite cada año en un mes específico</span>
                            </label>

                            <div id="recurrent_month_container" class="ml-6 {{ old('period_type', $periodType) === 'recurrent' ? '' : 'hidden' }}">
                                <select name="recurrent_month" class="rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Seleccione mes...</option>
                                    @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $i => $mes)
                                        <option value="{{ $i + 1 }}" {{ old('recurrent_month', $salaryItem->recurrent_month) == ($i + 1) ? 'selected' : '' }}>{{ $mes }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Ej: Día de la Sanidad en Septiembre</p>
                            </div>

                            <label class="flex items-center">
                                <input type="radio" name="period_type" value="specific" 
                                       {{ old('period_type', $periodType) === 'specific' ? 'checked' : '' }}
                                       onchange="togglePeriodFields()"
                                       class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Solo aplica en un período específico</span>
                            </label>

                            <div id="specific_period_container" class="ml-6 flex gap-3 {{ old('period_type', $periodType) === 'specific' ? '' : 'hidden' }}">
                                <select name="specific_month" class="rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Mes...</option>
                                    @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $i => $mes)
                                        <option value="{{ $i + 1 }}" {{ old('specific_month', $salaryItem->specific_month) == ($i + 1) ? 'selected' : '' }}>{{ $mes }}</option>
                                    @endforeach
                                </select>
                                <select name="specific_year" class="rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Año...</option>
                                    @for($y = now()->year - 1; $y <= now()->year + 2; $y++)
                                        <option value="{{ $y }}" {{ old('specific_year', $salaryItem->specific_year ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                                <p class="text-xs text-gray-500 self-center">Ej: Suma no remunerativa por DNU</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="flex justify-between pt-6 border-t">
                    <button type="button" onclick="document.getElementById('delete-form').submit()" 
                            class="px-4 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg">
                        Eliminar
                    </button>
                    
                    <div class="flex gap-3">
                        <a href="{{ route('salary.index') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>

            {{-- Formulario de eliminar (fuera del formulario principal) --}}
            <form id="delete-form" action="{{ route('salary.destroy', $salaryItem) }}" method="POST" 
                  onsubmit="return confirm('¿Está seguro de eliminar este concepto?')" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    <script>
        document.getElementById('calculation_type').addEventListener('change', function() {
            const suffix = document.getElementById('value_suffix');
            switch(this.value) {
                case 'percentage': suffix.textContent = '%'; break;
                case 'fixed': suffix.textContent = '$'; break;
                case 'hours': suffix.textContent = '/hs'; break;
            }
        });

        // Mostrar/ocultar campos de período
        function togglePeriodFields() {
            const periodType = document.querySelector('input[name="period_type"]:checked')?.value;
            const recurrentContainer = document.getElementById('recurrent_month_container');
            const specificContainer = document.getElementById('specific_period_container');
            
            recurrentContainer.classList.toggle('hidden', periodType !== 'recurrent');
            specificContainer.classList.toggle('hidden', periodType !== 'specific');
        }
    </script>
</x-manage>

