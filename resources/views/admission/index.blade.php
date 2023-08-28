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
                <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Datos del pedido:</h2>
                <p class="mt-1 text-sm leading-6 text-gray-600">Agregar análisis a la base de datos:</p>
              
                <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-6">
                    <div class="sm:col-span-3">
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Cobertura médica:</span>
                            <select id="insurance" name="insurance" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
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
                            <span class="w-3/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Sala de extracciones:</span>
                            <select id="insurance" name="insurance" autocomplete="off" class="w-3/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                <option value="1">Sala 1</option>
                                <option value="2">Sala 2</option> 
                                <option value="3">Sala 3</option>   
                            </select>
                        </div>
                    </div>
    
                    <div class="sm:col-span-2">
                        
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                            <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Código de autorización:</span>
                            <input type="text" name="authorization_code	" id="authorization_code	" autocomplete="off"
                            class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>
                
                    <div class="sm:col-span-6">
                        
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                            <span class="w-1/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Instrucciones:</span>
                            <input type="text" name="instructions" id="instructions" autocomplete="off"
                            class="w-5/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>
                
                    <div class="sm:col-span-2">
                        
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-3/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Decimales:</span>
                            <input type="number" name="decimals" id="decimals" autocomplete="off" 
                            class="w-3/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>
                
                    <div class="sm:col-span-4 ">
                        
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-1/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Método:</span>
                            <input type="text" name="method" id="method" autocomplete="off" 
                            class="w-5/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>
                    
                    <div class="sm:col-span-6">
                        
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                            <span class="w-1/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Cuestionario:</span>
                            <input type="text" name="questions" id="questions" autocomplete="off"
                            class="w-5/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>
    
                    
            
                    <div class="sm:col-span-3">
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Material:</span>
                            <select id="material" name="material" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                <option value="1">EDTA</option>
                                <option value="2">SUERO</option>
                                <option value="3">ORINA</option>
                                <option value="4">CITRATO</option>
                                <option value="5">EPARINA</option>
                            </select>
                        </div>
                    </div>
                    {{-- <div class="sm:col-span-3">
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Padres:</span>
                            <select id="parent" name="parent" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                <option value=""> Ninguno </option>
                                @foreach ($parents as $parent)
                                <option value="{{$parent->id}}">{{strtoupper($parent->name)}}</option>
                                @endforeach
    
                            </select>
                        </div>
                    </div> --}}
        
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

        


        {{-- <div class="space-y-10 ">       --}}
      {{-- 
      
        <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-9">
            <div class="sm:col-span-3">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                    <span class="w-2/6 px-4 items-center flex text-base bg-gray-300 rounded-l-lg  ">Nombre</span>
                    <input type="text" name="name" id="name" autocomplete="off" required autofocus
                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
            </div>

            <div class="sm:col-span-3">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                    <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Cuit:</span>
                    <input type="text" name="tax_id" id="tax_id" autocomplete="off" required
                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
            </div>

            <div class="sm:col-span-3">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                    <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">IVA:</span>
                    <select id="tax" name="tax" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        <option value="inscripto">Inscripto</option>
                        <option value="exento">Exento</option>
                    </select>
                </div>
            </div>

            <div class="sm:col-span-3">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                    <span class="w-1/4 px-4 items-center flex bg-gray-300 rounded-l-lg">Email:</span>
                    <input type="email" name="email" id="email" autocomplete="off" 
                    class="w-3/4 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
            </div>

            <div class="sm:col-span-3 ">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                    <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Teléfono:</span>
                    <input type="text" name="phone" id="phone" autocomplete="off" required
                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
            </div>  

            <div class="sm:col-span-3 ">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                    <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Domicilio:</span>
                    <input type="text" name="address" id="address" autocomplete="off" 
                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
            </div>

            <div class="sm:col-span-6">
                    
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                    <span class="w-1/5 px-4 items-center flex bg-gray-300 rounded-l-lg">Observaciones:</span>
                    <input type="text" name="instructions" id="instructions" autocomplete="off"
                    class="w-4/5 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
            </div>

            <div class="sm:col-span-3 ">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                    <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Grupo:</span>
                    <select id="group" name="group" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        <option value=""> Ninguno </option>
                        @foreach ($groups as $group)
                        <option value="{{$group->id}}">{{strtoupper($group->name)}}</option>
                        @endforeach

                    </select>
                </div>
            </div>


            <div class="sm:col-span-3 ">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                    <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">NBU:</span>
                    <input type="text" name="nbu" id="nbu" autocomplete="off" 
                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
            </div>

            <div class="sm:col-span-3 ">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                    <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Coseguro:</span>
                    <input type="text" name="price" id="price" autocomplete="off" 
                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
            </div>

            <div class="sm:col-span-3">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                    <span class="w-1/4 px-4 items-center flex bg-gray-300 rounded-l-lg">Pais:</span>
                    <select id="country" name="country" autocomplete="off" class="w-3/4 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    <option value="argentina">Argentina</option>
                    <option value="brasil">Brasil</option>
                    <option value="uruguay">Uruguay</option>
                    <option value="chile">Chile</option>
                    </select>
                </div>
            </div>

            <div class="sm:col-span-3">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                    <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Provincia:</span>
                    <select id="state" name="state" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    <option value="Buenos Aires">Buenos Aires</option>
                    <option value="Ciudad Autonoma de Bs As">Ciudad Autonoma de Bs As</option>
                    <option value="Catamarca">Catamarca</option>
                    <option value="Chaco">Chaco</option>
                    <option value="Chubut">Chubut</option>
                    <option value="Cordoba">Cordoba</option>
                    <option value="Corrientes">Corrientes</option>
                    <option value="Entre Ríos">Entre Ríos</option>
                    <option value="Formosa">Formosa</option>
                    <option value="Jujuy">Jujuy</option>
                    <option value="La Rioja">La rioja</option>
                    <option value="Mendoza">Mendoza</option>
                    <option value="Misiones">Misiones</option>
                    <option value="Neuquen">Neuquen</option>
                    <option value="Rio Negro">Rio Negro</option>
                    <option value="Salta">Salta</option>
                    <option value="San Juan">San Juan</option>
                    <option value="San Luis">San Luis</option>
                    <option value="Santa Cruz">Santa Cruz</option>
                    <option value="Santa Fe">Santa Fe</option>
                    <option value="Santiago del Estero">Santiago del Estero</option>
                    <option value="Tierra del Fuego">Tierra del Fuego</option>
                    <option value="Tucuman">Tucuman</option>
                    </select>
                </div>
            </div>
        </div>
  
  
        <div class="mt-6 flex items-center justify-end gap-x-6">
          <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancelar</button>
          <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Guardar</button>
        </div>
      </div> --}}
    </form>
    <script>
        function myFunction() {
          alert('Clik')
        }
    </script>
                  
  
</x-app-layout>