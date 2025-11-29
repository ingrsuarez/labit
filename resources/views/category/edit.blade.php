<x-manage>
    <div class="w-full px-4 py-6">
        <div class="max-w-2xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Editar Categoría</h1>
                    <p class="text-sm text-gray-600 mt-1">Modifique los datos de la categoría salarial</p>
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
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-white">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span class="font-semibold">{{ ucwords($category->name) }}</span>
                        </div>
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm text-white">
                            ID: {{ $category->id }}
                        </span>
                    </div>
                </div>

                {{-- Form Content --}}
                <form action="{{ route('category.save') }}" method="POST" class="p-6">
                    @csrf
                    <input type="hidden" name="id" value="{{ $category->id }}">

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
                                   value="{{ old('name', ucwords($category->name)) }}"
                                   placeholder="Ej: Técnico de Laboratorio, Profesional Bioquímico..."
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                        </div>

                        {{-- Convenio y Sindicato --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="agreement" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Convenio <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="agreement" id="agreement" required
                                       value="{{ old('agreement', $category->agreement) }}"
                                       placeholder="Ej: CCT 108/75"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                            </div>
                            <div>
                                <label for="union_name" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Sindicato
                                </label>
                                <input type="text" name="union_name" id="union_name"
                                       value="{{ old('union_name', ucwords($category->union_name)) }}"
                                       placeholder="Ej: FATSA / ATSA"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
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
                                           value="{{ old('wage', $category->wage) }}"
                                           placeholder="0.00"
                                           class="w-full pl-8 pr-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Salario mensual para jornada completa</p>
                            </div>
                            <div>
                                <label for="base_weekly_hours" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Horas Semanales (Jornada Completa) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="base_weekly_hours" id="base_weekly_hours" required
                                           value="{{ old('base_weekly_hours', $category->base_weekly_hours ?? 48) }}"
                                           min="1" max="60"
                                           class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">hs/sem</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Ej: 48 para administrativos, 36 para profesionales</p>
                            </div>
                        </div>

                        {{-- Info Box --}}
                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-emerald-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm text-emerald-800">
                                    <p class="font-medium">Cálculo del básico proporcional</p>
                                    <p class="mt-1">El sueldo de cada empleado se calculará proporcionalmente según sus horas semanales respecto a las horas de jornada completa de la categoría.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
                        {{-- Delete Button --}}
                        <button type="button" onclick="confirmDelete()"
                                class="px-4 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Eliminar
                        </button>
                        
                        <div class="flex gap-3">
                            <a href="{{ route('category.index') }}" 
                               class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-medium shadow-lg hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Form (hidden) --}}
    <form id="delete-form" action="{{ route('category.delete', $category) }}" method="GET" class="hidden"></form>

    <script>
        function confirmDelete() {
            if (confirm('¿Está seguro de eliminar esta categoría? Esta acción no se puede deshacer.')) {
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</x-manage>
