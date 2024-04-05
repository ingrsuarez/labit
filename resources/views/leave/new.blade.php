<x-manage>
    <div class="flex flex-col justify-start">
        <div class="bg-white pb-4 px-2 rounded-lg shadow-lg">
            <form class="" action="{{route('leave.store')}}" method="POST">
                @csrf
                {{-- <div class="space-y-10 ">       --}}
                
                    <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Nueva licencia:</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Complete los datos:</p>
                
                    <div class="mx-2 mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-8">
                        <div class="sm:col-span-2">    
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-2/6 px-4 items-center flex text-base bg-gray-300 rounded-l-lg  ">Inicio</span>
                                <input type="date" name="start" id="start" autocomplete="off" required autofocus
                                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                                    sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">

                            </div>
                        </div>
                        <div class="sm:col-span-2">    
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-2/6 px-4 items-center flex text-base bg-gray-300 rounded-l-lg  ">Fin</span>
                                <input type="date" name="end" id="end" autocomplete="off" required autofocus
                                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                                    sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">

                            </div>
                        </div>
                        <div class="sm:col-span-4">
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                                <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Empleado:</span>
                                <select id="category" name="employee" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">   
                                        @foreach ($employees as $employee)
                                        <option value="{{$employee->id}}">{{ucwords($employee->name.' '.$employee->lastName)}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                
                        <div class="sm:col-span-4">
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Causa:</span>
                            <select id="type" name="type" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                <option value="Vacaciones">VACACIONES</option>
                                <option value="Enfermedad">DIA ENFERMEDAD</option>
                                <option value="Licencia especial">LIC ESPECIAL </option>
                                <option value="Inasistencia">INASISTENCIA</option>
                                <option value="Suspension">SUSPENSION</option>
                                <option value="Maternidad">MATERNIDAD</option>
                                <option value="horas_extra">HORAS EXTRA</option>
                            </select>
                            </div>
                        </div>
                
                        <div class="sm:col-span-4">
                        
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-1/5 px-2 items-center flex bg-gray-300 rounded-l-lg">Médico:</span>
                                <input type="text" name="doctor" id="doctor" autocomplete="off" 
                                class="w-4/5 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-2/4 px-4 items-center flex bg-gray-300 rounded-l-lg">Horas al 50%:</span>
                                <input type="number" name="hour_50" id="hour_50" autocomplete="off" 
                                class="w-2/4 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                                    sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-2/4 px-4 items-center flex bg-gray-300 rounded-l-lg">Horas al 100%:</span>
                                <input type="number" name="hour_100" id="hour_100" autocomplete="off" 
                                class="w-2/4 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400
                                sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-9">
                        
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-1/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Descripción:</span>
                                <textarea id="description" name="description" rows="4" cols="50" name="phone" id="phone" autocomplete="off" 
                                class="w-5/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                </textarea>
                            </div>
                        </div>

                    </div>
                    <div class="mt-6 flex items-center justify-end gap-x-6">
                        <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancelar</button>
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Guardar</button>
                    </div>
            </form>
        </div>

        <div class="bg-white mt-2 pb-4 px-2 w-fit lg:w-fit rounded-lg shadow-lg ">
            <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Listado de licencias:</h2>
            <p class="mt-1 text-sm leading-6 text-gray-600">Licencias:</p>    

            
                @if (empty($leaves[0]))
                    <div class="mt-6 flex items-center justify-end gap-x-6">
                        No existen licencias!
                        <a href="" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Nuevo</a>
                    </div>
                
                    
                @else
                    <div>
                        <table class="border-collapse border border-slate-400 table-auto mt-6 rounded">
                            <thead class="border border-slate-300">
                                <th class="bg-blue-300 px-2 border border-slate-300">Comienzo</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Final</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Descripción</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Nombre</th>
                                <th class="bg-blue-300 px-2 border border-slate-300" colspan="2"></th>
            
                            </thead>
                            <tbody>
                            @foreach ($leaves as $leave)
                            <tr class="">
                                <td class="px-2 border border-slate-300">{{ucwords($leave->start)}}</td>
                                <td class="px-2 border border-slate-300">{{ucwords($leave->end)}}</td>
                                
                                <td class="px-2 border border-slate-300">{{ucwords($leave->description)}}</td>
                                <td class="px-2 border border-slate-300">{{$leave->employee->name}}</td>
                                <td class="px-2 py-2 border border-slate-300">
                                    <a href="{{route('leave.edit',$leave->id)}}" class="rounded-md bg-green-600 mx-2 px-2 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                        Editar
                                    </a>
                                </td>
                                <td class="px-2 py-2 border border-slate-300">
                                    <a href="{{route('leave.edit',['leave'=>$leave->id])}}" class="rounded-md bg-red-600 mx-2 px-2 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
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