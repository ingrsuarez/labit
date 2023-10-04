<x-app-layout>
    
    
    <form class="mx-4 md:mx-6" action="{{route('admission.store')}}" method="POST">
      @csrf
        {{-- Selected Patient --}}
        @isset($current_patient)
            <div class="bg-white pb-4 px-2 rounded-lg shadow-lg">
                <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">
                    Paciente: 
                    
                    {{ucfirst($current_patient->name).' '.ucfirst($current_patient->lastName) }}
                </h2>
                <p class="mt-1 text-sm leading-6 text-gray-400">Dni: 
                    <span class="text-gray-800 font-bold">{{$current_patient->patientId}}</span>
                    - Fecha de nacimiento: 
                    <span class="text-gray-800 font-bold"> {{$current_patient->birth}}</span>
                    - Teléfono:
                    <span class="text-gray-800 font-bold"> {{$current_patient->phone}}</span>
                </p>
                <p class="mt-1 text-sm leading-6 text-gray-600">Fecha de nacimiento: {{$current_patient->birth}}</p>
                <div class="mt-2 flex items-center justify-end gap-x-6">
                    
                    <a href="{{route('admission.index')}}" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Modificar</a>
                </div>
            </div>
            <div class="bg-white pb-4 px-2 mt-2 rounded-lg shadow-lg ">
                <h2 class=" text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Datos del pedido:</h2>
                {{-- <p class="mt-1 text-sm leading-6 text-gray-600">Agregar análisis a la base de datos:</p> --}}
              
                <div class="text-sm mt-4 grid grid-cols-1 gap-x-6 gap-y-2 md:grid-cols-7">
                    
                    <div class="sm:col-span-2 ">
        
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-1/3 px-4 items-center flex bg-gray-300 rounded-l-lg">Fecha:</span>
                            <input type="date" name="birth" id="birth" autocomplete="off" value="<?php echo date("Y-m-d");?>"
                             class="w-2/3 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>    
                    <div class="sm:col-span-3 ">
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Cobertura médica:</span>
                            <select id="insurance" name="insurance" autocomplete="off" class="text-sm w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                @foreach ($insurances as $insurance)
                                    <option value="{{$insurance->id}}">{{strtoupper($insurance->name)}}</option>
                                @endforeach

                            </select>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                            <span class="w-3/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Número de afiliado:</span>
                            <input type="text" name="insurance_cod" id="insurance_cod" autocomplete="off"
                            class="w-3/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>
    
                    <div class="sm:col-span-2">
                        
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                            <span class="w-3/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Extracción:</span>
                            <select id="room" name="room" autocomplete="off" class="text-sm w-3/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                <option value="1">Sala 1</option>
                                <option value="2">Sala 2</option> 
                                <option value="3">Sala 3</option>   
                            </select>
                        </div>
                    </div>
    
                    <div class="sm:col-span-2">           
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                            <span class="w-3/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Nro. autorización:</span>
                            <input type="text" name="authorization_code	" id="authorization_code	" autocomplete="off"
                            class="w-3/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>
                
                    
                </div>
                
                <div class="flex"> 
                        
                    <table id="table_test" class="table-auto border-collapse border border-slate-400 mt-6 rounded">
                        <thead class="border border-slate-300">
                            <th class="bg-blue-300 px-2 border border-slate-300">Código</th>
                            <th class="bg-blue-300 px-2 border border-slate-300">Nombre</th>
                            <th class="bg-blue-300 px-2 border border-slate-300">Valor</th>
                            <th class="bg-blue-300 px-2 border border-slate-300">Particular</th>
                            <th class="bg-blue-300 px-2 border border-slate-300"></th>
        
                        </thead>
                        <tbody>
                       
                            <tr id="tr1" class="">
                                <td class="px-2 border border-slate-300"><input autofocus type="text" class="flex rounded-md border-0 text-gray-900 placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600"></td>
                                <td class="px-2 border border-slate-300"><input type="text" class="flex rounded-md border-0 text-gray-900"></td>
                                <td class="px-2 border border-slate-300"><input type="text"></td>
                                <td class="px-2 border border-slate-300"><input wire:keydown.tab="addRow"></td>
                                <td id="last_row" class="px-2 py-2 border border-slate-300"><a href="#" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Seleccionar</a></td>
                            </tr>
                        </tbody>
                    </table>
                
                
                    
                
            </div>
                
          
                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancelar</button>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Guardar</button>
                </div>
            </div>
        @else
            <div class="bg-white pb-4 px-2 rounded-lg shadow-lg ">
                <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">
                    Paciente:
                </h2>
                <p class="mt-1 text-sm leading-6 text-gray-600">Complete los datos:</p>
                @livewire('patient.find')
            </div>
        @endif

    </form>
    <script>
        let table_test = document.getElementById("table_test");    
        let last_row = document.getElementById("last_row");
        
        last_row.addEventListener("keydown", (e) => {  
            if(e.key === "Tab"){
                var row = table_test.insertRow(2);
                var cell1 = row.insertCell(0);
                var cell2 = row.insertCell(1);
                var cell3 = row.insertCell(2);
                var cell4 = row.insertCell(3);
                var cell4 = row.insertCell(3);
                cell1.innerHTML = "NEW CELL1";
                cell1.classList.add('px-2','border','border-slate-300');
                cell2.innerHTML = "NEW CELL2";
                cell2.classList.add('px-2','border','border-slate-300');
                cell3.innerHTML = "NEW CELL3";
                cell3.classList.add('px-2','border','border-slate-300');
                cell4.innerHTML = "NEW CELL4";
                cell4.classList.add('px-2','border','border-slate-300');
                cell5.innerHTML = "NEW CELL5";
                cell5.classList.add('px-2','border','border-slate-300');
            }
        });
    </script>
                  
  
</x-app-layout>