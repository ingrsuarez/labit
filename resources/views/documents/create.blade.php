<x-admin-layout title="Crear Documento">
    
    <div class="max-w-2xl mx-auto bg-white p-6 shadow rounded">
        <h2 class="text-xl font-semibold mb-4">Nuevo Documento</h2>

        @if(session('message'))
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif

        <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label class="block font-semibold">Nombre del documento</label>
                <select name="name" class="w-full border p-2 rounded">
                    <option value="">Seleccionar...</option>
                    <option value="DNI" @selected(old('name') == 'DNI')>DNI</option>
                    <option value="ALTA AFIP" @selected(old('name') == 'ALTA AFIP')>ALTA AFIP</option>
                    <option value="BAJA AFIP" @selected(old('name') == 'BAJA AFIP')>BAJA AFIP</option>
                    <option value="MATRICULA" @selected(old('name') == 'MATRICULA')>MATRICULA</option>
                    <option value="CBU ALIAS" @selected(old('name') == 'CBU ALIAS')>CBU ALIAS</option>
                    <option value="SEGURO" @selected(old('name') == 'SEGURO')>SEGURO</option>
                    <option value="EXAMEN PERIODICO" @selected(old('name') == 'EXAMEN PERIODICO')>EXAMEN PERIÓDICO</option>
                    <option value="EXAMEN PREOCUPACIONAL" @selected(old('name') == 'EXAMEN PREOCUPACIONAL')>EXAMEN PREOCUPACIONAL</option>
                    <option value="TITULO" @selected(old('name') == 'TITULO')>TÍTULO</option>
                    <option value="CARNET DE CONDUCIR" @selected(old('name') == 'CARNET DE CONDUCIR')>CARNET DE CONDUCIR</option>
                </select>
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block font-semibold">Empleado</label>
                <select name="employee_id" class="w-full border p-2 rounded">
                    <option value="">Seleccionar</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                            {{ $employee->name }} {{ $employee->lastName }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block font-semibold">Fecha de creación</label>
                <input name="fecha_creacion" type="date" class="w-full border p-2 rounded" value="{{ old('fecha_creacion') }}">
            </div>

            <div class="mb-4">
                <label class="block font-semibold">Fecha de vencimiento</label>
                <input name="fecha_vencimiento" type="date" class="w-full border p-2 rounded" value="{{ old('fecha_vencimiento') }}">
                @error('fecha_vencimiento') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block font-semibold">Estado</label>
                <select name="status" class="w-full border p-2 rounded">
                    <option value="activo" @selected(old('status') == 'activo')>Activo</option>
                    <option value="vencido" @selected(old('status') == 'vencido')>Vencido</option>
                    <option value="pendiente" @selected(old('status') == 'pendiente')>Pendiente</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block font-semibold">Observaciones</label>
                <textarea name="observaciones" class="w-full border p-2 rounded" rows="3">{{ old('observaciones') }}</textarea>
            </div>

            <div class="mb-4">
                <label class="block font-semibold">Archivos</label>

                <div id="file-container">
                    <div class="file-group mb-2">
                        <input type="file" name="files[]" class="border p-2 rounded w-full mb-1">
                        <input type="text" name="file_names[]" placeholder="Nombre archivo (Ej: DNI frente)" class="border p-2 rounded w-full">
                    </div>
                </div>

                <button type="button" onclick="addFileInput()" class="mt-2 text-sm text-blue-600 hover:underline">
                    + Agregar otro archivo
                </button>
            </div>

            <div class="mt-6 text-right">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Guardar Documento
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