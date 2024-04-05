<x-manage>
    <div class="flex flex-col justify-start">


        <div class="bg-white mt-2 pb-4 px-2 w-fit lg:w-fit rounded-lg shadow-lg ">
            <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Novedades:</h2>
            <p class="mt-1 text-sm leading-6 text-gray-600">Licencias:</p>    

            
                @if (empty($resumes[0]))
                    <div class="mt-6 flex items-center justify-end gap-x-6">
                        No existen licencias!
                        <a href="" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Nuevo</a>
                    </div>
                
                    
                @else
                    <div>
                        <table class="border-collapse border border-slate-400 table-auto mt-6 rounded">
                            <thead class="border border-slate-300">
                                <th class="bg-blue-300 px-2 border border-slate-300">Año</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Mes</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Empleado</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Tipo</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Categoría</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Días</th>
                                <th class="bg-blue-300 px-2 border border-slate-300"></th>
                            </thead>
                            <tbody>
                            @foreach ($resumes as $leave)
                            <tr class="">
                                <td class="px-2 border border-slate-300">{{$leave->year}}</td>
                                <td class="px-2 border border-slate-300">{{$leave->month}}</td>
                                
                                <td class="px-2 border border-slate-300">{{ucwords($leave->employee)}}</td>
                                <td class="px-2 border border-slate-300">{{ucwords($leave->type)}}</td>
                                <td class="px-2 border border-slate-300">{{ucwords($leave->category)}}</td>
                                <td class="px-2 border border-slate-300"><strong>{{ucwords($leave->days)}}</strong></td>
                                <td class="px-2 py-2 border border-slate-300">
                                    <a href="{{route('leave.delete',$leave->leave_id)}}" class="rounded-md bg-red-600 mx-2 px-2 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
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