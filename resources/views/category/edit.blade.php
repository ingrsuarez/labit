<x-manage>
    <div class="flex flex-col justify-start">
    <div class="bg-white pb-4 px-2 rounded-lg shadow-lg">
        <form class="" action="{{route('category.save')}}" method="POST">
            @csrf
              {{-- <div class="space-y-10 ">       --}}
            
                <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Editar categoría:</h2>
                <p class="mt-1 text-sm leading-6 text-gray-600">Complete los datos:</p>
                <input type="hidden" name="id" value="{{$category->id}}">
                <div class="mx-2 mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-8">
                    <div class="sm:col-span-4 border-slate-400 border-2 rounded-lg  ">
                        
                        <div class="justify-items-stretch flex flex-wrap">
                            <span class="w-2/6 px-4 items-center flex text-base bg-gray-300 rounded-l-lg  ">Nombre</span>
                            <input type="text" name="name" id="name" autocomplete="off" required autofocus
                                class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                                sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600" value="{{ucwords($category->name)}}">
                        </div>
                    </div>

                    <div class="sm:col-span-4">
          
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                            <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Convenio:</span>
                            <input type="text" name="agreement" id="agreement" autocomplete="off" required
                            class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                            sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600" value="{{ucwords($category->agreement)}}">
                        </div>
                    </div>
              
                    <div class="sm:col-span-4">
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                            <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Salario:</span>
                            <input type="number" step="0.01" name="wage" id="wage" autocomplete="off" required
                            class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                            sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600" value="{{ucwords($category->wage)}}">
                        </div>
                    </div>
              
                    <div class="sm:col-span-4">
                    
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-1/5 px-2 items-center flex bg-gray-300 rounded-l-lg">Sindicato:</span>
                            <input type="text" name="union_name" id="union_name" autocomplete="off" 
                            class="w-4/5 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                            sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600" value="{{ucwords($category->union_name)}}">
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