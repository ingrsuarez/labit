<div class="p-6 shadow-lg rounded-lg bg-white">
    <h1 class="text-2xl font-bold mb-4">Agregar Análisis</h1>
    <table class="w-full divide-y divide-gray-200" id="tablaAnalisis">
        <thead class="bg-gray-200">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Código / Nombre</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Precio Particular</th>
                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($rows as $index => $row)
                <tr>
                    <td class="px-4 py-2">
                        <!-- Campo de búsqueda para el código/nombre -->
                        @livewire('analysis-search-input', ['index' => $index], key($index))
                    </td>
                    <td class="px-4 py-2">{{ $row['name'] }}</td>
                    <td class="px-4 py-2">{{ $row['precio'] }}</td>
                    <td class="px-4 py-2">{{ $row['particular'] }}</td>
                    <td class="px-4 py-2 text-center">
                        <button type="button" wire:click="removeRow({{ $index }})" class="bg-red-500 text-white px-2 py-1 rounded">Eliminar</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <button type="button" wire:click="addRow" class="bg-blue-500 text-white px-4 py-2 rounded-md mt-4">Agregar Análisis</button>
</div>
