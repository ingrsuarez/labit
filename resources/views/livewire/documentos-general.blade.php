<div>
    <div>
    <h2 class="text-2xl font-bold mb-4">Documentos</h2>

    <div class="flex gap-4 mb-6">
        <input wire:model="search" wire:keyup="update" type="text"
       placeholder="Buscar documento..." class="border p-2 rounded w-1/3">

        <select wire:model="status" wire:change="applyFilters" class="border p-2 rounded">
            <option value="">Estado</option>
            <option value="activo">Activo</option>
            <option value="vencido">Vencido</option>
            <option value="pendiente">Pendiente</option>
        </select>

        <select wire:model="employee_id" wire:change="applyFilters" class="border p-2 rounded">
            <option value="">Empleado</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->name }} {{ $employee->lastName }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex justify-end mb-4">
        <a href="{{ route('documents.create') }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
            + Nuevo Documento
        </a>
    </div>
    <table class="min-w-full bg-white shadow-md rounded border">
        <thead class="bg-slate-300">
            <tr>
                <th class="py-2 px-4 border-b">Nombre</th>
                <th class="py-2 px-4 border-b">Empleado</th>
                <th class="py-2 px-4 border-b">Vence</th>
                <th class="py-2 px-4 border-b">Subido por</th>
                <th class="py-2 px-4 border-b">Archivos</th>
                <th class="py-2 px-4 border-b">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $doc)
            <tr class="text-center">
                <td class="py-2 px-4 border-b">{{ $doc->name }}</td>
                <td class="py-2 px-4 border-b">{{ $doc->employee->name }} {{ $doc->employee->lastName }}</td>
                <td class="py-2 px-4 border-b">{{ $doc->fecha_vencimiento }}</td>
                <td class="py-2 px-4 border-b">{{ $doc->user?->name ?? 'N/A' }}</td>
                <td class="py-2 px-4 border-b text-sm text-left">
                   @forelse($doc->files as $file)
                        <a href="{{ asset('storage/' . $file->filename) }}"
                        class="text-blue-600 hover:underline inline-block mr-1"
                        target="_blank" download>
                            📎 {{ $file->display_name ?? '' }}
                        </a>
                    @empty
                        <span class="text-gray-400 italic">Sin archivos</span>
                    @endforelse

                </td>
                <td class="py-2 px-4 border-b">
                    <a href="{{ route('documents.edit',[$doc]) }}" class="text-green-500 hover:underline ml-2">Editar</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-4">No hay documentos.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $documents->links() }}
    </div>
</div>

</div>
