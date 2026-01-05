<x-admin-layout>
    <div class="flex flex-col justify-start">
        <div class="max-w-3xl mx-auto bg-white p-8 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Nueva Categoría</h2>

            <form action="{{ route('category.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @csrf

                <!-- Name -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre de categoría</label>
                    <input type="text" name="name" id="name" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Agreement -->
                <div class="md:col-span-2">
                    <label for="agreement" class="block text-sm font-medium text-gray-700">Convenio</label>
                    <input type="text" name="agreement" id="agreement" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Union Name -->
                <div class="md:col-span-2">
                    <label for="union_name" class="block text-sm font-medium text-gray-700">Nombre del sindicato</label>
                    <input type="text" name="union_name" id="union_name"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Wage -->
                <div>
                    <label for="wage" class="block text-sm font-medium text-gray-700">Salario ($)</label>
                    <input type="number" step="0.01" name="wage" id="wage" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Full Time -->
                <div>
                    <label for="full_time" class="block text-sm font-medium text-gray-700">Full Time</label>
                    <select name="full_time" id="full_time"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione...</option>
                        <option value="yes">Sí</option>
                        <option value="no">No</option>
                    </select>
                </div>

                <!-- Botón -->
                <div class="md:col-span-2 flex justify-end mt-6">
                    <button type="submit" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                        Guardar categoría
                    </button>
                </div>
            </form>
        </div>


        <div class="bg-white mt-2 pb-4 px-2 w-fit lg:w-fit rounded-lg shadow-lg ">
            <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Listado de categorías:</h2>
            <p class="mt-1 text-sm leading-6 text-gray-600">Puestos actuales:</p>    

            
                @if (empty($categories[0]))
                    <div class="mt-6 flex items-center justify-end gap-x-6">
                        No existen Categorías!
                        <a href="" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Nuevo</a>
                    </div>
                
                    
                @else
                    <div>
                        <table class="border-collapse border border-slate-400 table-auto mt-6 rounded">
                            <thead class="border border-slate-300">
                                <th class="bg-blue-300 px-2 border border-slate-300">Nombre</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Convenio</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Sindicato</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Básico</th>
                                <th class="bg-blue-300 px-2 border border-slate-300" colspan="2"></th>
            
                            </thead>
                            <tbody>
                            @foreach ($categories as $category)
                            <tr class="">
                                <td class="px-2 border border-slate-300">{{ucwords($category->name)}}</td>
                                <td class="px-2 border border-slate-300">{{ucwords($category->agreement)}}</td>
                                
                                <td class="px-2 text-center border border-slate-300">{{$category->union_name}}</td>
                                <td class="px-2 border border-slate-300">{{$category->wage}}</td>
                                <td class="px-2 py-2 border border-slate-300">
                                    <a href="{{route('category.edit',['category'=>$category->id])}}" class="rounded-md bg-green-600 mx-2 px-2 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                        Editar
                                    </a>
                                </td>
                                <td class="px-2 py-2 border border-slate-300">
                                    <a href="{{route('category.delete',['category'=>$category->id])}}" class="rounded-md bg-red-600 mx-2 px-2 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
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
</x-admin-layout>