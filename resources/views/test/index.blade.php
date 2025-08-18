<x-app-layout>
  
    
    <form class="mx-4 w-fit" action="{{route('test.store')}}" method="POST">
        @csrf
        <div class="bg-white pb-4 px-2 rounded-lg shadow-lg ">
            <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Agregar Análisis:</h2>
            <p class="mt-1 text-sm leading-6 text-gray-600">Agregar análisis a la base de datos:</p>
            <div class="text-sm mt-4 grid grid-cols-1 gap-x-6 gap-y-2 md:grid-cols-7">
                <div class="w-full">
                    <label for="name" class="mb-2 text-sm font-medium text-gray-900">Código</label>
                    <input type="text" placeholder="Código" name="code" id="code" autocomplete="off" required
                    class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                </div>
                <div class="w-full">
                    <label for="name" class="mb-2 text-sm font-medium text-gray-900">Nombre</label>
                    <input type="text" placeholder="Nombre" name="name" id="name" autocomplete="off" required autofocus
                    class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                </div>
                <div class="w-full">
                    <label for="name" class="mb-2 text-sm font-medium text-gray-900">Unidad</label>
                    <input type="text" placeholder="Unidad" name="unit" id="unit" autocomplete="off" required
                    class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                </div>
                <div class="w-full">
                    <label for="name" class="mb-2 text-sm font-medium text-gray-900">NBU</label>
                    <input type="text" placeholder="NBU" name="nbu" id="nbu" autocomplete="off" required
                    class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                </div>
                <div class="w-full">
                    <label for="name" class="mb-2 text-sm font-medium text-gray-900">Decimales</label>
                    <input type="number" placeholder="Decimales" name="decimals" id="decimals" autocomplete="off" required
                    class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                </div>
                <div class="w-full">
                    <label for="name" class="mb-2 text-sm font-medium text-gray-900">Instrucciones</label>
                    <input type="text" placeholder="Instrucciones" name="instructions" id="instructions" autocomplete="off" required
                    class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                </div>
                <div class="w-full">
                    <label for="name" class="mb-2 text-sm font-medium text-gray-900">Método</label>
                    <input type="text" placeholder="Método" name="method" id="method" autocomplete="off" required
                    class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                </div>
                <div class="w-full">
                    <label for="name" class="mb-2 text-sm font-medium text-gray-900">Cuestionario</label>
                    <input type="text" placeholder="Cuestionario" name="questions" id="questions" autocomplete="off" 
                    class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                </div>
                <div class="w-full">
                    <label for="material" class="mb-2 text-sm font-medium text-gray-900">Material:</label>
                    <select id="material" name="material" autocomplete="off" class="w-full form-input px-8 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm transition duration-150 ease-in-out">
                        <option disabled selected value="">Seleccionar</option>
                        <option value="1">EDTA</option>
                        <option value="2">SUERO</option>
                        <option value="3">ORINA</option>
                        <option value="4">CITRATO</option>
                        <option value="5">EPARINA</option>
                    </select>
                </div>
                <div class="w-full">
                    <label for="material" class="mb-2 text-sm font-medium text-gray-900">Padres:</label>
                    <select id="material" name="material" autocomplete="off" class="w-full form-input px-8 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm transition duration-150 ease-in-out">
                        <option selected value=""> Ninguno </option>
                        @foreach ($parents as $parent)
                            <option value="{{$parent->id}}">{{$parent->code.' '.strtoupper($parent->name)}}</option>
                        @endforeach
                    </select>
                </div>

            </div>
      
      
            <div class="mt-6 flex items-center justify-end gap-x-6">
                <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancelar</button>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Guardar</button>
            </div>
        </div>





    </form>
</x-app-layout>