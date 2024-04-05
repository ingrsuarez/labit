<x-manage>
    <div class="flex flex-col justify-start">
        <div class="bg-white pb-4 px-2 rounded-lg shadow-lg">
            <form class="" action="{{route('leave.update')}}" method="POST">
                @csrf
                {{-- <div class="space-y-10 ">       --}}
                
                    <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Nueva licencia:</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Editar:</p>
                
                    <div class="mx-2 mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-8">
                        <div class="sm:col-span-2">    
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-2/6 px-4 items-center flex text-base bg-gray-300 rounded-l-lg  ">Inicio</span>
                                <input type="date" name="start" id="start" autocomplete="off" required autofocus
                                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                                    sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600" value="{{$leave->start}}">
                                <input type="hidden" name="leave_id" id="leave_id" value="{{$leave->id}}">
                            </div>
                        </div>
                        <div class="sm:col-span-2">    
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-2/6 px-4 items-center flex text-base bg-gray-300 rounded-l-lg  ">Fin</span>
                                <input type="date" name="end" id="end" autocomplete="off" required 
                                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                                    sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600" value="{{$leave->end}}">

                            </div>
                        </div>
                        <div class="sm:col-span-4">
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                                <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Empleado:</span>
                                <input type="text" readonly class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                                    sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600" value="{{ucfirst($leave->employee->name)}}">
                                <input type="hidden" name="employee_id" id="employee_id" value="{{$leave->employee_id}}">
                            </div>
                        </div>
                
                        <div class="sm:col-span-4">
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Causa:</span>
                                <select id="type" name="type" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">

                                    <option value="{{$leave->type}}" selected>{{strtoupper($leave->type)}}</option>
                                    @if(strtoupper($leave->type) != "VACACIONES")
                                        <option value="Vacaciones">VACACIONES</option>
                                    @endif
                                    @if(strtoupper($leave->type) != "ENFERMEDAD")
                                        <option value="Enfermedad">DIA ENFERMEDAD</option>
                                    @endif
                                    @if(strtoupper($leave->type) != "ESPECIAL")
                                        <option value="Licencia especial">LIC ESPECIAL </option>
                                    @endif
                                    @if(strtoupper($leave->type) != "INASISTENCIA")
                                        <option value="Inasistencia">INASISTENCIA</option>
                                    @endif
                                    @if(strtoupper($leave->type) != "SUSPENSION")
                                        <option value="Suspension">SUSPENSION</option>
                                    @endif
                                    @if(strtoupper($leave->type) != "MATERNIDAD")
                                        <option value="Maternidad">MATERNIDAD</option>
                                    @endif
                                    @if(strtoupper($leave->type) != "HORAS_EXTRA")
                                        <option value="horas_extra">HORAS EXTRA</option>
                                    @endif
                                    
                                </select>
                            </div>
                        </div>
                
                        <div class="sm:col-span-4">
                        
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-1/5 px-2 items-center flex bg-gray-300 rounded-l-lg">Médico:</span>
                                <input type="text" name="doctor" id="doctor" autocomplete="off" value="{{$leave->doctor}}" 
                                class="w-4/5 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-2/4 px-4 items-center flex bg-gray-300 rounded-l-lg">Horas al 50%:</span>
                                <input type="number" name="hour_50" id="hour_50" autocomplete="off" value="{{$leave->hour_50}}"
                                class="w-2/4 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                                    sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-2/4 px-4 items-center flex bg-gray-300 rounded-l-lg">Horas al 100%:</span>
                                <input type="number" name="hour_100" id="hour_100" autocomplete="off" value="{{$leave->hour_100}}" 
                                class="w-2/4 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400
                                sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-9">
                        
                            <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                                <span class="w-1/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Descripción:</span>
                                <textarea id="description" name="description" rows="4" cols="50" name="phone" id="phone" autocomplete="off" 
                                class="w-5/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                                sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                {{$leave->description}}
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
    </div>
</x-manage>

