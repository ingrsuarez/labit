<x-app-layout>
    
    
    <form class="mx-4 md:mx-6" action="{{route('admission.store')}}" method="POST">
      @csrf
        {{-- Selected Patient --}}
        @isset($current_patient)
            <div class="bg-white pb-4 px-2 max-w-sm rounded-lg shadow-lg">
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
                    <div class="w-full">
                        <label for="name" class="mb-2 text-sm font-medium text-gray-900">Fecha:</label>
                        <input type="date" placeholder="Fecha" name="date" autocomplete="off" value="<?php echo date("Y-m-d");?>" autofocus required 
                        class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                    </div>
                    <div class="w-full">
                        <label for="name" class="mb-2 text-sm font-medium text-gray-900">Nombre:</label>
                        <input type="text" placeholder="Nombre" name="name" autocomplete="off" autofocus required 
                        class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                    </div>
                    <div class="w-full">
                        <label for="client" class="mb-2 text-sm font-medium text-gray-900">Cliente:</label>
                        <select id="client_id" name="client_id" autocomplete="off" class="w-full form-input px-8 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm transition duration-150 ease-in-out">
                            <option disabled selected value="">Seleccionar</option>
                            @foreach ($insurances as $insurance)
                            <option value="{{$insurance->id}}">{{strtoupper($insurance->name)}}</option>
                            @endforeach
                        </select>
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
                    
                    {{--@livewire('show-analisis'),['employees'=>$employees]--}}

                    {{-- <table id="table_test" class="table-auto border-collapse border border-slate-400 mt-6 rounded">
                        <thead class="border border-slate-300">
                            <th class="bg-blue-300 px-2 border border-slate-300">Código</th>
                            <th class="bg-blue-300 px-2 border border-slate-300">Nombre</th>
                            <th class="bg-blue-300 px-2 border border-slate-300">Valor</th>
                            <th class="bg-blue-300 px-2 border border-slate-300">Particular</th>
                            <th class="bg-blue-300 px-2 border border-slate-300"></th>
        
                        </thead>
                        <tbody>
                       
                            <tr id="tr1" class="">
                                <td class="px-2 border border-slate-300"><input type="text" id="codeAna" class="flex rounded-md border-0 text-gray-900 placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600"></td>
                                <td class="px-2 border border-slate-300"><input type="text" class="flex rounded-md border-0 text-gray-900"></td>
                                <td class="px-2 border border-slate-300"><input type="text"></td>
                                <td class="px-2 border border-slate-300"><input wire:keydown.tab="addRow"></td>
                                <td id="last_row" class="px-2 py-2 border border-slate-300"><a href="#" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Seleccionar</a></td>
                            </tr>
                        </tbody>
                    </table> --}}
                </div>
                <div class="col-span-2 bg-white p-6 shadow-lg rounded-lg">
                    <h1 class="text-2xl font-bold mb-4">Análisis:</h1>
                    @livewire('admission-analysis-table')
                    {{-- <table class="w-full divide-y divide-gray-200" id="tablaAnalisis">
                        <thead class="bg-gray-200">
                        <tr>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Valor</th>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Particular</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                        </tr>
                        </thead>
                        <tbody id="analisis" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td>
                                    @livewire('analysis-search')
                                </td>
                                <td>
                                    
                                </td>
                                <td>
                                    
                                </td>
                                <td>
                                    
                                </td>
                            </tr>
                        </tbody>
                    </table> --}}
                    
                    
                    {{-- <div id="movimientos" class="mb-4">
                        <div class="mb-4">
                            <label for="item_id" class="block text-sm font-medium text-gray-700">Item</label>
                            <select name="movimientos[0][item_id]" id="item_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div> --}}
                    {{-- <div class="my-2">
                        <button type="button" onclick="addAnalisis()" class="bg-blue-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mb-4">Agregar análisis</button>
                        
                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Guardar</button>
                    </div> --}}
                </div>   
                
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function addAnalisis() {
            const table = document.getElementById('tablaAnalisis').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
    
            const cellCode = newRow.insertCell(0);
            const cellName = newRow.insertCell(1);
            const cellValue = newRow.insertCell(2);
            const cellParticular = newRow.insertCell(3);
            const cellActions = newRow.insertCell(4);
    
            // Campos de entrada vacíos para el nuevo análisis
            cellCode.innerHTML = `<input type="text" name="code[]" class="w-full text-gray-900 rounded-md">`;
            cellName.innerHTML = `<input type="text" name="name[]" class="w-full text-gray-900 rounded-md">`;
            cellValue.innerHTML = `<input type="text" name="value[]" class="w-full text-gray-900 rounded-md">`;
            cellParticular.innerHTML = `<input type="text" name="particular_price[]" class="w-full text-gray-900 rounded-md">`;
    
            // Botón de acción para eliminar el análisis de la fila
            cellActions.innerHTML = `<button type="button" onclick="removeRow(this)" class="bg-red-500 text-white px-2 py-1 rounded">Eliminar</button>`;
        }
    </script>
                  
  
</x-app-layout>