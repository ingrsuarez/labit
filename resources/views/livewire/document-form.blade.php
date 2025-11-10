<div>
    <div class="max-w-2xl mx-auto bg-white p-6 shadow rounded">
    <h2 class="text-xl font-semibold mb-4">{{ $documentId ? 'Editar' : 'Nuevo' }} Documento</h2>

    <form wire:submit.prevent="save" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="block font-semibold">Nombre del documento</label>
            <input wire:model="name" type="text" class="w-full border p-2 rounded">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block font-semibold">Empleado</label>
            <select wire:model="employee_id" class="w-full border p-2 rounded">
                <option value="">Seleccionar</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }} {{ $employee->lastName }}</option>
                @endforeach
            </select>
            @error('employee_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block font-semibold">Fecha de creación</label>
            <input wire:model="fecha_creacion" type="date" class="w-full border p-2 rounded">
        </div>

        <div class="mb-4">
            <label class="block font-semibold">Fecha de vencimiento</label>
            <input wire:model="fecha_vencimiento" type="date" class="w-full border p-2 rounded">
            @error('fecha_vencimiento') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block font-semibold">Estado</label>
            <select wire:model="status" class="w-full border p-2 rounded">
                <option value="activo">Activo</option>
                <option value="vencido">Vencido</option>
                <option value="pendiente">Pendiente</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block font-semibold">Observaciones</label>
            <textarea wire:model="observaciones" class="w-full border p-2 rounded" rows="3"></textarea>
        </div>

        <div class="mb-4">
            <label class="block font-semibold">Archivos</label>
            <input name="files[]" type="file" multiple class="w-full border p-2 rounded">
            
            <label class="block mt-2 text-sm text-gray-600">Nombre visible del archivo</label>
            <input name="file_names[]" type="text" class="w-full border p-2 rounded" placeholder="Ej: DNI frente, Certificado...">
        </div>

        <div class="mt-6 text-right">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Guardar Documento
            </button>
        </div>
    </form>
</div>

</div>
