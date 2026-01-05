<x-admin-layout>
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('category.index') }}" 
                       class="p-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Editar Categoría</h1>
                        <p class="text-blue-100 text-sm mt-1">Modificar los datos de la categoría salarial</p>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <form action="{{ route('category.save') }}" method="POST" class="p-8">
                @csrf
                <input type="hidden" name="id" value="{{ $category->id }}">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {{-- Columna izquierda: Datos básicos --}}
                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Información General
                        </h3>

                        {{-- Nombre --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre de la categoría <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" required autofocus
                                   value="{{ old('name', ucwords($category->name)) }}"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                   placeholder="Ej: Profesional Bioquímico">
                        </div>

                        {{-- Convenio --}}
                        <div>
                            <label for="agreement" class="block text-sm font-medium text-gray-700 mb-2">
                                Convenio Colectivo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="agreement" id="agreement" required
                                   value="{{ old('agreement', $category->agreement) }}"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                   placeholder="Ej: CCT 108/75 (FATSA-CADIME/CEDIM)">
                        </div>

                        {{-- Sindicato --}}
                        <div>
                            <label for="union_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Sindicato
                            </label>
                            <input type="text" name="union_name" id="union_name"
                                   value="{{ old('union_name', $category->union_name) }}"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                   placeholder="Ej: FATSA / ATSA">
                        </div>
                    </div>

                    {{-- Columna derecha: Datos salariales --}}
                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Datos Salariales
                        </h3>

                        {{-- Salario Básico --}}
                        <div>
                            <label for="wage" class="block text-sm font-medium text-gray-700 mb-2">
                                Salario Básico Mensual <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">$</span>
                                <input type="number" step="0.01" name="wage" id="wage" required
                                       value="{{ old('wage', $category->wage) }}"
                                       class="w-full pl-8 pr-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="0.00">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Salario base para jornada completa</p>
                        </div>

                        {{-- Horas Semanales Base --}}
                        <div>
                            <label for="base_weekly_hours" class="block text-sm font-medium text-gray-700 mb-2">
                                Horas Semanales Base <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="base_weekly_hours" id="base_weekly_hours" required
                                       min="1" max="60"
                                       value="{{ old('base_weekly_hours', $category->base_weekly_hours ?? 48) }}"
                                       class="w-full px-4 py-3 pr-16 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">hs/sem</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Jornada completa según convenio (generalmente 48 hs)</p>
                        </div>

                        {{-- Full Time --}}
                        <div>
                            <label for="full_time" class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de Jornada
                            </label>
                            <select name="full_time" id="full_time"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="">Sin especificar</option>
                                <option value="yes" {{ old('full_time', $category->full_time) === 'yes' ? 'selected' : '' }}>
                                    Tiempo completo
                                </option>
                                <option value="no" {{ old('full_time', $category->full_time) === 'no' ? 'selected' : '' }}>
                                    Tiempo parcial
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Resumen informativo --}}
                <div class="mt-8 p-4 bg-blue-50 rounded-xl border border-blue-100">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-medium">Información importante</p>
                            <p class="mt-1 text-blue-600">
                                Las horas semanales base se usan para calcular el salario proporcional de empleados con jornada reducida.
                                Por ejemplo, un empleado con 36 hs semanales recibirá el 75% del salario básico si la jornada base es 48 hs.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="mt-8 flex items-center justify-end gap-4 pt-6 border-t">
                    <a href="{{ route('category.index') }}" 
                       class="px-6 py-3 text-gray-700 font-medium hover:text-gray-900 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-xl shadow-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

        {{-- Información adicional --}}
        @if($category->jobs_count ?? $category->jobs()->count() > 0)
        <div class="mt-6 bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Puestos asociados
            </h3>
            <p class="text-gray-600">
                Esta categoría tiene <span class="font-semibold text-blue-600">{{ $category->jobs_count ?? $category->jobs()->count() }}</span> puesto(s) asociado(s).
                Los cambios en el salario básico afectarán a todos los empleados en estos puestos.
            </p>
        </div>
        @endif
    </div>
</x-admin-layout>
