<x-admin-layout title="Editar Documento">
    <div class="max-w-2xl mx-auto bg-white p-6 shadow rounded">
        <h2 class="text-xl font-semibold mb-4">Editar Documento</h2>

        @if(session('message'))
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif 

        <form id="form-update" action="{{ route('documents.update', $document) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('POST')

            {{-- Nombre del documento --}}
            <div class="mb-4">
                <label class="block font-semibold">Nombre del documento</label>
                <select name="name" class="w-full border p-2 rounded">
                    <option value="">Seleccionar...</option>
                    @foreach([
                        'DNI', 'ALTA AFIP', 'BAJA AFIP', 'MATRICULA', 'CBU ALIAS', 'SEGURO',
                        'EXAMEN PERIODICO', 'EXAMEN PREOCUPACIONAL', 'TITULO', 'CARNET DE CONDUCIR'
                    ] as $option)
                        <option value="{{ $option }}" @selected($document->name == $option)>{{ $option }}</option>
                    @endforeach
                </select>
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Empleado --}}
            <div class="mb-4">
                <label class="block font-semibold">Empleado</label>
                <select name="employee_id" class="w-full border p-2 rounded">
                    <option value="">Seleccionar</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected($document->employee_id == $employee->id)>
                            {{ $employee->name }} {{ $employee->lastName }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Fechas --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold">Fecha de creación</label>
                    <input name="fecha_creacion" type="date" class="w-full border p-2 rounded"
                           value="{{ old('fecha_creacion', $document->fecha_creacion) }}">
                </div>
                <div>
                    <label class="block font-semibold">Fecha de vencimiento</label>
                    <input name="fecha_vencimiento" type="date" class="w-full border p-2 rounded"
                           value="{{ old('fecha_vencimiento', $document->fecha_vencimiento) }}">
                    @error('fecha_vencimiento') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Estado --}}
            <div class="mt-4 mb-4">
                <label class="block font-semibold">Estado</label>
                <select name="status" class="w-full border p-2 rounded">
                    <option value="activo" @selected($document->status == 'activo')>Activo</option>
                    <option value="vencido" @selected($document->status == 'vencido')>Vencido</option>
                    <option value="pendiente" @selected($document->status == 'pendiente')>Pendiente</option>
                </select>
            </div>

            {{-- Observaciones --}}
            <div class="mb-4">
                <label class="block font-semibold">Observaciones</label>
                <textarea name="observaciones" class="w-full border p-2 rounded" rows="3">{{ old('observaciones', $document->comments) }}</textarea>
            </div>

            {{-- Archivos existentes --}}
            <div class="mb-4">
                <label class="block font-semibold">Archivos actuales</label>
                @if($document->files->count())
                    <ul class="list-disc pl-5">
                        @foreach($document->files as $file)
                            <li class="flex justify-between items-center mb-1">
                                <a href="{{ asset('storage/'.$file->filename) }}" target="_blank" class="text-blue-600 hover:underline">
                                    📎 {{ $file->display_name ?? basename($file->filename) }}
                                </a>
                                <form action="{{ route('documents.files.destroy', $file) }}" method="POST" onsubmit="return confirm('¿Eliminar este archivo?')">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="text-red-500 hover:underline text-sm">Eliminar</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500 italic">Sin archivos</p>
                @endif
            </div>

            {{-- Subir nuevos archivos --}}
            <div class="mb-4">
                <label class="block font-semibold">Agregar nuevos archivos</label>
                <div id="file-container">
                    
                </div>
                <button type="button" onclick="addFileInput()" class="mt-2 text-sm text-blue-600 hover:underline">
                    + Agregar otro archivo
                </button>
            </div>

            {{-- Botón --}}
            <div class="mt-6 text-right">
                <button type="submit" form="form-update" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Actualizar Documento
                </button>
            </div>
        </form>
    </div>

    <script>
        function addFileInput() {
            const container = document.getElementById('file-container');
            const group = document.createElement('div');
            group.classList.add('file-group', 'mb-2');
            group.innerHTML = `
                <input type="file" name="files[]" class="border p-2 rounded w-full mb-1">
                <input type="text" name="file_names[]" placeholder="Nombre visible (Ej: Contrato, Foto...)" class="border p-2 rounded w-full">
            `;
            container.appendChild(group);
        }
    </script>
</x-admin-layout>
