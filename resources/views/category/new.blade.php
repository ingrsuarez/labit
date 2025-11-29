<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-2xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Nueva Categoría</h1>
                    <p class="text-sm text-gray-600 mt-1">Complete los datos de la nueva categoría salarial</p>
                </div>
                <a href="{{ route('category.index') }}" 
                   class="inline-flex items-center px-4 py-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </a>
            </div>

            {{-- Form Card --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                {{-- Card Header --}}
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center text-white">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span class="font-semibold">Datos de la Categoría</span>
                    </div>
                </div>

                {{-- Form Content --}}
                <form action="{{ route('category.store') }}" method="POST" class="p-6">
                    @csrf

                    @if($errors->any())
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
                            <ul class="list-disc list-inside text-sm">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="space-y-6">
                        {{-- Nombre --}}
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                Nombre de la Categoría <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" required autofocus
                                   value="{{ old('name') }}"
                                   placeholder="Ej: Técnico de Laboratorio, Profesional Bioquímico..."
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>

                        {{-- Convenio y Sindicato --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="agreement" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Convenio <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="agreement" id="agreement" required
                                       value="{{ old('agreement', 'CCT 108/75 (FATSA–CADIME/CEDIM)') }}"
                                       placeholder="Ej: CCT 108/75"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>
                            <div>
                                <label for="union_name" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Sindicato
                                </label>
                                <input type="text" name="union_name" id="union_name"
                                       value="{{ old('union_name', 'FATSA / ATSA') }}"
                                       placeholder="Ej: FATSA / ATSA"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>
                        </div>

                        {{-- Salario y Horas --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="wage" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Salario Básico <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">$</span>
                                    <input type="number" step="0.01" name="wage" id="wage" required
                                           value="{{ old('wage') }}"
                                           placeholder="0.00"
                                           class="w-full pl-8 pr-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Salario mensual para jornada completa</p>
                            </div>
                            <div>
                                <label for="base_weekly_hours" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Horas Semanales (Jornada Completa) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="base_weekly_hours" id="base_weekly_hours" required
                                           value="{{ old('base_weekly_hours', 48) }}"
                                           min="1" max="60"
                                           class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">hs/sem</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Ej: 48 para administrativos, 36 para profesionales</p>
                            </div>
                        </div>

                        {{-- Info Box --}}
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm text-blue-800">
                                    <p class="font-medium">Cálculo del básico proporcional</p>
                                    <p class="mt-1">El sueldo de cada empleado se calculará proporcionalmente según sus horas semanales respecto a las horas de jornada completa de la categoría.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                        <a href="{{ route('category.index') }}" 
                           class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                            Cancelar
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 bg-blue-600 text-white rounded-xl font-medium shadow-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar Categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-manage>
