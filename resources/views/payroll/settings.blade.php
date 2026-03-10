<x-admin-layout title="Configuración de Recibos">
    <div class="max-w-3xl mx-auto p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Configuración de Recibos</h1>
                <p class="text-sm text-gray-600 mt-1">Firma del empleador para los recibos de sueldo</p>
            </div>
            <a href="{{ route('payroll.index') }}" 
               class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm">
                ← Volver a Liquidaciones
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Firma del Empleador</h2>
            <p class="text-sm text-gray-500 mb-6">
                Cargá una imagen con la firma del empleador (PNG o JPG, máximo 2MB). Se insertará automáticamente en los recibos de sueldo generados en PDF.
            </p>

            @if($signature)
                <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-sm font-medium text-gray-700 mb-3">Firma actual:</p>
                    <div class="flex items-center gap-6">
                        <div class="bg-white p-3 rounded border border-gray-300">
                            <img src="{{ asset('storage/' . $signature) }}" 
                                 alt="Firma del empleador" 
                                 class="max-h-24 w-auto">
                        </div>
                        <form action="{{ route('payroll.deleteSignature') }}" method="POST"
                              onsubmit="return confirm('¿Eliminar la firma del empleador?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Eliminar Firma
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <form action="{{ route('payroll.updateSignature') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="signature" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $signature ? 'Reemplazar firma' : 'Cargar firma' }}
                    </label>
                    <input type="file" 
                           name="signature" 
                           id="signature"
                           accept="image/png,image/jpeg"
                           required
                           class="block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-lg file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-blue-50 file:text-blue-700
                                  hover:file:bg-blue-100
                                  cursor-pointer">
                    <p class="mt-1 text-xs text-gray-400">Formatos aceptados: PNG, JPG. Tamaño máximo: 2MB.</p>
                </div>

                <div id="preview-container" class="mb-4 hidden">
                    <p class="text-sm font-medium text-gray-700 mb-2">Vista previa:</p>
                    <div class="bg-gray-50 p-3 rounded border border-gray-200 inline-block">
                        <img id="signature-preview" src="" alt="Vista previa" class="max-h-24 w-auto">
                    </div>
                </div>

                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    {{ $signature ? 'Actualizar Firma' : 'Guardar Firma' }}
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('signature').addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(ev) {
                    document.getElementById('signature-preview').src = ev.target.result;
                    document.getElementById('preview-container').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</x-admin-layout>
