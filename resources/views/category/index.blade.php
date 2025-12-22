<x-manage>
    <div class="flex flex-col justify-start">
        <div class="bg-white pb-4 px-4 w-full lg:w-fit rounded-lg shadow-lg">
            <div class="flex justify-between items-center">
                <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-4 -mr-4 py-2 px-4 shadow-lg">
                    Listado de categorías
                </h2>
            </div>
            
            <div class="mt-4 mb-4 flex justify-end">
                <a href="{{ route('category.new') }}" 
                   class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    + Nueva Categoría
                </a>
            </div>

            @if ($categories->isEmpty())
                <div class="mt-6 flex items-center justify-center gap-x-6">
                    <p class="text-gray-500">No existen Categorías!</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="border-collapse border border-slate-400 table-auto mt-2 rounded w-full">
                        <thead class="border border-slate-300">
                            <tr>
                                <th class="bg-blue-300 px-2 py-2 border border-slate-300">Nombre</th>
                                <th class="bg-blue-300 px-2 py-2 border border-slate-300">Convenio</th>
                                <th class="bg-blue-300 px-2 py-2 border border-slate-300">Sindicato</th>
                                <th class="bg-blue-300 px-2 py-2 border border-slate-300">Básico</th>
                                <th class="bg-blue-300 px-2 py-2 border border-slate-300">Puestos</th>
                                <th class="bg-blue-300 px-2 py-2 border border-slate-300" colspan="2">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td class="px-2 py-1 border border-slate-300">{{ ucwords($category->name) }}</td>
                                    <td class="px-2 py-1 border border-slate-300">{{ ucwords($category->agreement) }}</td>
                                    <td class="px-2 py-1 text-center border border-slate-300">{{ $category->union_name }}</td>
                                    <td class="px-2 py-1 border border-slate-300">${{ number_format($category->wage, 2) }}</td>
                                    <td class="px-2 py-1 text-center border border-slate-300">
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                            {{ $category->jobs_count }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 border border-slate-300">
                                        <a href="{{ route('category.edit', ['category' => $category->id]) }}" 
                                           class="rounded-md bg-green-600 mx-2 px-2 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                                            Editar
                                        </a>
                                    </td>
                                    <td class="px-2 py-2 border border-slate-300">
                                        <a href="{{ route('category.delete', ['category' => $category->id]) }}" 
                                           class="rounded-md bg-red-600 mx-2 px-2 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                            Eliminar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-manage>

