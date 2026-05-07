<x-admin-layout>
    <div class="max-w-3xl mx-auto py-8 px-4">
        <div class="mb-6">
            <a href="{{ route('api-clients.index') }}"
               class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver al listado
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h1 class="text-xl font-bold text-gray-900">Nueva API Key</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Generá una key para que un sistema externo (ej: una sede de LISCOM) consuma la API de labit.
                </p>
            </div>

            <form method="POST" action="{{ route('api-clients.store') }}" class="px-6 py-6 space-y-5">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required maxlength="255"
                           value="{{ old('name') }}"
                           placeholder="Ej: LISCOM Sede Centro"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="lab_branch_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Sede
                        </label>
                        <select name="lab_branch_id" id="lab_branch_id"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Sin sede (acceso global)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('lab_branch_id') == $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Dejá vacío para que la key tenga acceso a protocolos de todas las sedes.</p>
                        @error('lab_branch_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Empresa <span class="text-red-500">*</span>
                        </label>
                        <select name="company_id" id="company_id" required
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccionar empresa…</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>
                                    {{ $company->displayName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                        Notas
                    </label>
                    <textarea name="notes" id="notes" rows="3" maxlength="2000"
                              placeholder="Persona responsable, contexto del uso…"
                              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="patient_data_level" class="block text-sm font-medium text-gray-700 mb-1">
                        Nivel de datos del paciente
                    </label>
                    <select name="patient_data_level" id="patient_data_level"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(\App\Models\ApiClient::PATIENT_DATA_LEVELS as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('patient_data_level', \App\Models\ApiClient::LEVEL_MINIMAL) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        <strong>Mínimo:</strong> el sistema externo NO recibe el DNI del paciente (recomendado).
                        <strong>Estándar</strong> requiere justificación legal — incluye datos personales sensibles.
                    </p>
                    @error('patient_data_level')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="active" value="1" checked
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Activa al crearse</span>
                </label>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-900 flex items-start gap-2">
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>
                        La key se va a mostrar <strong>una sola vez</strong> después de guardar.
                        Asegurate de copiarla y guardarla en un lugar seguro inmediatamente.
                    </p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('api-clients.index') }}"
                       class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                        Generar API key
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
