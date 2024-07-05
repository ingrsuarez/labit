<div>
    <table id="table_test" class="table-auto border-collapse border border-slate-400 mt-6 rounded">
        <thead class="border border-slate-300">
            <th class="bg-blue-300 px-2 border border-slate-300">Código</th>
            <th class="bg-blue-300 px-2 border border-slate-300">Nombre</th>
            <th class="bg-blue-300 px-2 border border-slate-300">Valor</th>
            <th class="bg-blue-300 px-2 border border-slate-300">Particular</th>
            <th class="bg-blue-300 px-2 border border-slate-300"></th>

        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                @foreach($row as $index => $cell)
                    <td><input wire:model.live.debounce.500ms="input" type="text"></td>
                @endforeach
                </tr>
            @endforeach
                <tr>
                    <td colspan="2"><a class="cursor-pointer bg-slate-400" wire:click="addRow">Add Row</a></td>
                </tr>
            {{-- <tr id="tr1" class="">
                
                <td class="px-2 border border-slate-300"><input type="text" id="codeAna" class="flex rounded-md border-0 text-gray-900 placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600"></td>
                <td class="px-2 border border-slate-300"><input type="text" class="flex rounded-md border-0 text-gray-900"></td>
                <td class="px-2 border border-slate-300"><input type="text"></td>
                <td class="px-2 border border-slate-300"><input wire:keydown.tab="addRow"></td>
                <td id="last_row" class="px-2 py-2 border border-slate-300"><a href="#" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Seleccionar</a></td>
            </tr> --}}
        </tbody>
    </table>
</div>
