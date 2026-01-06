<x-admin-layout>
    <div class="max-w-3xl mx-auto p-6">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            {{-- Encabezado --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Nuevo Concepto de Sueldo</h2>
                    <p class="text-sm text-gray-600 mt-1">Complete los datos del nuevo haber o deducción</p>
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

            <form action="{{ route('salary.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Nombre --}}
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nombre del concepto *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               placeholder="Ej: Antigüedad, Jubilación, Zona..."
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Código --}}
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">Código (opcional)</label>
                        <input type="text" name="code" id="code" value="{{ old('code') }}"
                               placeholder="Ej: ANT, JUB, Z30"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Tipo --}}
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Tipo *</label>
                        <select name="type" id="type" required
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione...</option>
                            <option value="haber" {{ old('type') === 'haber' ? 'selected' : '' }}>Haber (suma al sueldo)</option>
                            <option value="deduccion" {{ old('type') === 'deduccion' ? 'selected' : '' }}>Deducción (resta del sueldo)</option>
                        </select>
                    </div>

                    {{-- Tipo de Cálculo --}}
                    <div>
                        <label for="calculation_type" class="block text-sm font-medium text-gray-700">Tipo de Cálculo *</label>
                        <select name="calculation_type" id="calculation_type" required
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione...</option>
                            <option value="percentage" {{ old('calculation_type') === 'percentage' ? 'selected' : '' }}>Porcentaje (%)</option>
                            <option value="fixed" {{ old('calculation_type') === 'fixed' ? 'selected' : '' }}>Monto Fijo ($)</option>
                            <option value="fixed_proportional" {{ old('calculation_type') === 'fixed_proportional' ? 'selected' : '' }}>Monto Fijo Proporcional</option>
                            <option value="hours" {{ old('calculation_type') === 'hours' ? 'selected' : '' }}>Por Horas</option>
                        </select>
                    </div>

                    {{-- Base de Cálculo (solo para Haberes) --}}
                    <div id="calculation_base_wrapper">
                        <label for="calculation_base" class="block text-sm font-medium text-gray-700">Base de Cálculo *</label>
                        <select name="calculation_base" id="calculation_base"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="basic" {{ old('calculation_base') === 'basic' ? 'selected' : '' }}>Sueldo Básico</option>
                            <option value="basic_vacaciones" {{ old('calculation_base') === 'basic_vacaciones' ? 'selected' : '' }}>Básico + Vacaciones (CCT 108/75 Zona)</option>
                            <option value="basic_antiguedad" {{ old('calculation_base', 'basic_antiguedad') === 'basic_antiguedad' ? 'selected' : '' }}>Básico + Antigüedad</option>
                            <option value="basic_antiguedad_titulo" {{ old('calculation_base') === 'basic_antiguedad_titulo' ? 'selected' : '' }}>Básico + Vacaciones + Antigüedad + Título</option>
                            <option value="basic_hours" {{ old('calculation_base') === 'basic_hours' ? 'selected' : '' }}>Básico + Horas Extras</option>
                            <option value="basic_hours_antiguedad" {{ old('calculation_base') === 'basic_hours_antiguedad' ? 'selected' : '' }}>Básico + Horas + Antigüedad</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Sobre qué monto se calcula el porcentaje</p>
                    </div>
                    
                    {{-- Info para Deducciones --}}
                    <div id="deduccion_info" class="hidden">
                        <label class="block text-sm font-medium text-gray-700">Base de Cálculo</label>
                        <div class="mt-1 px-4 py-3 bg-gray-100 rounded-lg text-sm text-gray-600">
                            <span class="font-medium">Bruto Total</span> - Las deducciones siempre se calculan sobre el total de haberes
                        </div>
                    </div>

                    {{-- Valor --}}
                    <div>
                        <label for="value" class="block text-sm font-medium text-gray-700">Valor *</label>
                        <div class="mt-1 relative">
                            <input type="number" name="value" id="value" value="{{ old('value', 0) }}" 
                                   step="0.01" min="0" required
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 pr-12">
                            <span id="value_suffix" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">%</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500" id="value_help">Ingrese el porcentaje (ej: 11 para 11%)</p>
                    </div>

                    {{-- Orden --}}
                    <div>
                        <label for="order" class="block text-sm font-medium text-gray-700">Orden de aparición</label>
                        <input type="number" name="order" id="order" value="{{ old('order', 0) }}" min="0"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Menor número = aparece primero</p>
                    </div>

                    {{-- Descripción --}}
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Descripción (opcional)</label>
                        <textarea name="description" id="description" rows="2"
                                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                    </div>

                    {{-- Opciones --}}
                    <div class="md:col-span-2 flex flex-wrap gap-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Activo</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="is_remunerative" value="1" 
                                   {{ old('is_remunerative', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Remunerativo</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="requires_assignment" value="1" 
                                   {{ old('requires_assignment') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500">
                            <span class="ml-2 text-sm text-gray-700">Requiere asignación individual</span>
                            <span class="ml-1 text-xs text-gray-500" title="Solo aplica a empleados específicos">(ej: Puesto Jerárquico, Adicional Título)</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="hide_percentage_in_receipt" value="1" 
                                   {{ old('hide_percentage_in_receipt') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-rose-600 shadow-sm focus:ring-rose-500">
                            <span class="ml-2 text-sm text-gray-700">Ocultar % en recibo</span>
                            <span class="ml-1 text-xs text-gray-500" title="No mostrar el porcentaje en el recibo de sueldo (ej: acuerdos internos)">(confidencial)</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="includes_in_antiguedad_base" value="1" 
                                   {{ old('includes_in_antiguedad_base') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">Se suma a la base de antigüedad</span>
                            <span class="ml-1 text-xs text-purple-500" title="Si está marcado, este concepto se sumará al básico para calcular la antigüedad">ⓘ</span>
                        </label>
                    </div>

                    {{-- Período de aplicación --}}
                    <div class="md:col-span-2 border-t pt-6 mt-2">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Período de aplicación</h3>
                        
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="radio" name="period_type" value="all_year" 
                                       {{ old('period_type', 'all_year') === 'all_year' ? 'checked' : '' }}
                                       onchange="togglePeriodFields()"
                                       class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Aplica todo el año</span>
                            </label>

                            <label class="flex items-center">
                                <input type="radio" name="period_type" value="recurrent" 
                                       {{ old('period_type') === 'recurrent' ? 'checked' : '' }}
                                       onchange="togglePeriodFields()"
                                       class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Se repite cada año en un mes específico</span>
                            </label>

                            <div id="recurrent_month_container" class="ml-6 {{ old('period_type') === 'recurrent' ? '' : 'hidden' }}">
                                <select name="recurrent_month" class="rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Seleccione mes...</option>
                                    @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $i => $mes)
                                        <option value="{{ $i + 1 }}" {{ old('recurrent_month') == ($i + 1) ? 'selected' : '' }}>{{ $mes }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Ej: Día de la Sanidad en Septiembre</p>
                            </div>

                            <label class="flex items-center">
                                <input type="radio" name="period_type" value="specific" 
                                       {{ old('period_type') === 'specific' ? 'checked' : '' }}
                                       onchange="togglePeriodFields()"
                                       class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Solo aplica en un período específico</span>
                            </label>

                            <div id="specific_period_container" class="ml-6 flex gap-3 {{ old('period_type') === 'specific' ? '' : 'hidden' }}">
                                <select name="specific_month" class="rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Mes...</option>
                                    @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $i => $mes)
                                        <option value="{{ $i + 1 }}" {{ old('specific_month') == ($i + 1) ? 'selected' : '' }}>{{ $mes }}</option>
                                    @endforeach
                                </select>
                                <select name="specific_year" class="rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Año...</option>
                                    @for($y = now()->year - 1; $y <= now()->year + 2; $y++)
                                        <option value="{{ $y }}" {{ old('specific_year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                                <p class="text-xs text-gray-500 self-center">Ej: Suma no remunerativa por DNU</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="flex justify-end gap-3 pt-6 border-t">
                    <a href="{{ route('salary.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                        Guardar Concepto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mostrar/ocultar base de cálculo según tipo (haber/deducción)
        function toggleCalculationBase() {
            const type = document.getElementById('type').value;
            const baseWrapper = document.getElementById('calculation_base_wrapper');
            const deduccionInfo = document.getElementById('deduccion_info');
            
            if (type === 'deduccion') {
                baseWrapper.classList.add('hidden');
                deduccionInfo.classList.remove('hidden');
            } else {
                baseWrapper.classList.remove('hidden');
                deduccionInfo.classList.add('hidden');
            }
        }
        
        document.getElementById('type').addEventListener('change', toggleCalculationBase);
        // Ejecutar al cargar
        toggleCalculationBase();

        // Actualizar sufijo y ayuda según tipo de cálculo
        document.getElementById('calculation_type').addEventListener('change', function() {
            const suffix = document.getElementById('value_suffix');
            const help = document.getElementById('value_help');
            
            switch(this.value) {
                case 'percentage':
                    suffix.textContent = '%';
                    help.textContent = 'Ingrese el porcentaje (ej: 11 para 11%)';
                    break;
                case 'fixed':
                    suffix.textContent = '$';
                    help.textContent = 'Ingrese el monto fijo en pesos';
                    break;
                case 'hours':
                    suffix.textContent = '/hs';
                    help.textContent = 'Ingrese el valor por hora';
                    break;
                default:
                    suffix.textContent = '';
                    help.textContent = '';
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
</x-admin-layout>

