<x-app-layout>
  
    
    <form class="ml-2 w-fit" action="{{route('test.store')}}" method="POST">
        @csrf
        <div class="bg-white pb-4 px-2 rounded-lg shadow-lg ">
            <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Agregar Análisis:</h2>
            <p class="mt-1 text-sm leading-6 text-gray-600">Agregar análisis a la base de datos:</p>
          
            <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-6">
                <div class="sm:col-span-4 border-slate-400 border-2 rounded-lg  ">
                
                    <div class="justify-items-stretch flex flex-wrap">
                        <span class="w-2/6 px-4 items-center flex text-base bg-gray-300 rounded-l-lg  ">Nombre</span>
                        <input type="text" name="name" id="name" autocomplete="off" required autofocus
                        class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    </div>
                </div>
            
                <div class="sm:col-span-2">
                    
                    <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                        <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Unidad</span>
                        <input type="text" name="unit" id="unit" autocomplete="off"
                        class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    
                    <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                        <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">NBU:</span>
                        <input type="text" name="nbu" id="nbu" autocomplete="off" required
                        class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    
                    <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                        <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Código:</span>
                        <input type="text" name="code" id="code" autocomplete="off"
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
                <div class="sm:col-span-3">
                    <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                        <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Padres:</span>
                        <select id="parent" name="parent" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            <option value=""> Ninguno </option>
                            @foreach ($parents as $parent)
                            <option value="{{$parent->id}}">{{strtoupper($parent->name)}}</option>
                            @endforeach

                        </select>
                    </div>
                </div>
    
            </div>
      
      
            <div class="mt-6 flex items-center justify-end gap-x-6">
                <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancelar</button>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Guardar</button>
            </div>
        </div>





    </form>
</x-app-layout>