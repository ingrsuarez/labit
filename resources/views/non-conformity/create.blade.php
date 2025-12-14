<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('non-conformity.index') }}" class="mr-4 text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Nueva No Conformidad
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <form action="{{ route('non-conformity.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Código (auto-generado) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                            <input type="text" value="{{ $code }}" disabled
                                   class="w-full rounded-lg border-gray-300 bg-gray-100 text-gray-500">
                            <p class="text-xs text-gray-500 mt-1">Generado automáticamente</p>
                        </div>

                        <!-- Fecha -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del Incidente *</label>
                            <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                                   class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500">
                            @error('date')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Empleado -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Empleado Involucrado *</label>
                            <select name="employee_id" required
                                    class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500">
                                <option value="">Seleccionar empleado...</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->lastName }} {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tipo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de No Conformidad *</label>
                            <select name="type" required
                                    class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500">
                                @foreach(\App\Models\NonConformity::types() as $key => $label)
                                    <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Severidad -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Severidad *</label>
                            <select name="severity" required
                                    class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500">
                                @foreach(\App\Models\NonConformity::severities() as $key => $label)
                                    <option value="{{ $key }}" {{ old('severity', 'leve') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('severity')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Procedimiento (si aplica) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Procedimiento Incumplido</label>
                            <input type="text" name="procedure_name" value="{{ old('procedure_name') }}"
                                   placeholder="Ej: POE-LAB-001"
                                   class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500">
                        </div>

                        <!-- Capacitación (si aplica) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Capacitación Relacionada</label>
                            <input type="text" name="training_name" value="{{ old('training_name') }}"
                                   placeholder="Ej: Buenas Prácticas de Laboratorio"
                                   class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500">
                        </div>

                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción del Incidente *</label>
                            <textarea name="description" rows="4" required
                                      placeholder="Describa detalladamente qué sucedió, cuándo y cómo se detectó..."
                                      class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Acción Correctiva -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Acción Correctiva</label>
                            <textarea name="corrective_action" rows="3"
                                      placeholder="¿Qué acción se tomó para corregir el problema?"
                                      class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500">{{ old('corrective_action') }}</textarea>
                        </div>

                        <!-- Acción Preventiva -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Acción Preventiva</label>
                            <textarea name="preventive_action" rows="3"
                                      placeholder="¿Qué se hará para evitar que vuelva a ocurrir?"
                                      class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500">{{ old('preventive_action') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <a href="{{ route('non-conformity.index') }}"
                           class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                            Registrar No Conformidad
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
