<x-app-layout>
  
    
    <form class="ml-4 md:ml-6" action="{{route('group.store')}}" method="POST">
      @csrf
        {{-- <div class="space-y-10 ">       --}}
      <div class="bg-white pb-4 px-2 rounded-lg shadow-lg ">
        <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Nuevo Grupo:</h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">Grupo de obras sociales:</p>
      
        <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-9">
            <div class="sm:col-span-3">
                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                    <span class="w-2/6 px-4 items-center flex text-base bg-gray-300 rounded-l-lg  ">Nombre</span>
                    <input type="text" name="name" id="name" autocomplete="off" required autofocus
                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
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
                    <input type="text" name="phone" id="phone" autocomplete="off" 
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
      </div>
    </form>
          
  
</x-app-layout>