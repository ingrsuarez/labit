<x-manage>
    
    <div class="bg-white py-4 px-2 rounded-lg shadow-lg ">
        <form class="ml-4 md:mx-6" action="{{route('employee.store')}}" method="POST">
            @csrf
              {{-- <div class="space-y-10 ">       --}}
            
                <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Nuevo empleado:</h2>
                <p class="mt-1 text-sm leading-6 text-gray-600">Complete los datos:</p>
              
                <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-8">
                    <div class="sm:col-span-4 border-slate-400 border-2 rounded-lg  ">
                        
                        <div class="justify-items-stretch flex flex-wrap">
                            <span class="w-2/6 px-4 items-center flex text-base bg-gray-300 rounded-l-lg  ">Nombre</span>
                            <input type="text" name="name" id="name" autocomplete="off" required autofocus
                                class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>

                    <div class="sm:col-span-4">
          
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                            <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Apellido</span>
                            <input type="text" name="last_name" id="last-name" autocomplete="off" required
                            class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>
              
                    <div class="sm:col-span-3">
                    
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                            <span class="w-1/5 px-2 items-center flex bg-gray-300 rounded-l-lg">DNI:</span>
                            <input type="text" name="id" id="id" autocomplete="off" required
                            class="w-4/5 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>
              
                    <div class="sm:col-span-5">
                    
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-1/5 px-2 items-center flex bg-gray-300 rounded-l-lg">Email:</span>
                            <input type="email" name="email" id="email" autocomplete="off" 
                            class="w-4/5 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>

                    <div class="sm:col-span-4">
                    
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-1/4 px-4 items-center flex bg-gray-300 rounded-l-lg">CBU:</span>
                            <input type="text" name="bank_account" id="bank_account" autocomplete="off" 
                            class="w-3/4 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>

                    <div class="sm:col-span-4">
                    
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-1/3 px-4 items-center flex bg-gray-300 rounded-l-lg">Teléfono:</span>
                            <input type="text" name="phone" id="phone" autocomplete="off" 
                            class="w-2/3 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>

                    <div class="sm:col-span-3">
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                          <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Puesto:</span>
                          <select id="position" name="position" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            <option value="">Ninguno</option>
                          </select>
                        </div>
                    </div>

                    <div class="sm:col-span-4">
                    
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-1/2 px-4 items-center flex bg-gray-300 rounded-l-lg">Horas semanales:</span>
                            <input type="number" name="weekly_hours" id="weekly_hours" autocomplete="off" 
                            class="w-1/2 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>

                    <div class="sm:col-span-4">
                    
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-1/2 px-4 items-center flex bg-gray-300 rounded-l-lg">Fecha nacimiento:</span>
                            <input type="date" name="weekly_hours" id="weekly_hours" autocomplete="off" 
                            class="w-1/2 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>

                    <div class="sm:col-span-4">
                    
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-1/3 px-4 items-center flex bg-gray-300 rounded-l-lg">Domicilio:</span>
                            <input type="text" name="address" id="address" autocomplete="off" 
                            class="w-2/3 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancelar</button>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Guardar</button>
                </div>
        </form>
    </div>
</x-manage>