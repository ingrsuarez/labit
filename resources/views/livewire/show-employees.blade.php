<div>
    {{-- <div class="bg-white mt-2 pb-4 px-2 rounded-lg shadow-lg"> --}}
        {{-- <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Listado de empleados:</h2> --}}
        <p class="mt-1 text-sm leading-6 text-gray-600">Listado de empleados activos:</p>    

        
            @if (empty($employees[0]))
                <div class="mt-6 flex items-center justify-end gap-x-6">
                    No existen empleados con este criterio de búsqueda!
                    <a href="" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Nuevo</a>
                </div>
            
                
            @else
                <div>
                    <table class="border-collapse border border-slate-400 table-auto mt-6 rounded">
                        <thead class="border border-slate-300">
                            <th class="bg-blue-300 px-2 border border-slate-300">Nombre</th>
                            <th class="bg-blue-300 px-2 border border-slate-300">DNI</th>
                            <th class="bg-blue-300 px-2 border border-slate-300">Horas Semanales</th>
                            <th class="bg-blue-300 px-2 border border-slate-300">Teléfono</th>
                            <th class="bg-blue-300 px-2 border border-slate-300">Fecha de nacimiento</th>
                            <th class="bg-blue-300 px-2 border border-slate-300">Puestos</th>
                            <th class="bg-blue-300 px-2 border border-slate-300"></th>
        
                        </thead>
                        <tbody>
                        @foreach ($employees as $employee)
                        <tr class="">
                            <td class="px-2 border border-slate-300">{{ucwords($employee->lastName)." ".ucwords($employee->name)}}</td>
                            <td class="px-2 border border-slate-300">{{$employee->employeeId}}</td>
                            
                            <td class="px-2 text-center border border-slate-300">{{$employee->weekly_hours}}</td>
                            <td class="px-2 border border-slate-300">{{$employee->phone}}</td>
                            <td class="px-2 text-center border border-slate-300">{{$employee->birth}}</td>
                            <td class="px-2 border border-slate-300">
                                @foreach($employee->jobs as $job)
                                        {{ucwords($job->name)." - "}}    
                                @endforeach
                            </td>
                            <td class="px-2 py-2 border border-slate-300">
                                <a href="{{route('employee.edit',$employee)}}" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                    Editar
                                </a>
                            </td>
                        </tr>
                        
                        @endforeach  
                        </tbody>
                    </table>
                </div>  
            @endif
        
    {{-- </div> --}}
</div>
