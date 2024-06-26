<x-manage>
    <div class="flex flex-col justify-start">

        <div class="bg-color-transparent" id="accordionExample">
            <div
            class=" rounded-t-[15px] border-0 bg-white dark:border-neutral-600 dark:bg-neutral-800">
            <h2 class="mb-0 " id="headingOne">
                <button
                class="bg-blue-500 group relative flex w-full items-center rounded-t-[15px] border-0  px-5 py-4 text-left text-base
                transition [overflow-anchor:none] hover:z-[2] focus:z-[3] focus:outline-none text-white"
                
                type="button"
                data-te-collapse-init
                data-te-target="#collapseOne"
                aria-expanded="true"
                aria-controls="collapseOne">
                Nuevo Permiso
                <span
                    class="ml-auto h-5 w-5 shrink-0 rotate-[-180deg] fill-[#336dec] transition-transform duration-200 ease-in-out group-[[data-te-collapse-collapsed]]:rotate-0 group-[[data-te-collapse-collapsed]]:fill-[#212529] motion-reduce:transition-none dark:fill-blue-300 dark:group-[[data-te-collapse-collapsed]]:fill-white"
                    id="iconOne">
                    <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="h-6 w-6">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </span>
                </button>
            </h2>
            <div
                id="collapseOne"
                class="!visible"
                data-te-collapse-item
                data-te-collapse-show
                aria-labelledby="headingOne"
                data-te-parent="#accordionExample">
                <div class="px-5 py-4">
                    <form class="" action="{{route('permission.store')}}" method="POST">
                        @csrf                
                        {{-- <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Nuevo empleado:</h2> --}}
                        <p class="mt-1 text-sm leading-6 text-gray-600">Complete los datos:</p>
                    
                        <div class="mx-2 mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-8">
                            <div class="sm:col-span-4 border-slate-400 border-2 rounded-lg  ">
                                
                                <div class="justify-items-stretch flex flex-wrap">
                                    <span class="w-2/6 px-4 items-center flex text-base bg-gray-300 rounded-l-lg  ">Nombre</span>
                                    <input type="text" name="name" id="name" autocomplete="off" required autofocus
                                        class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                </div>
                            </div>
        
                            <div class="sm:col-span-4">
                
                                <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                                    <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Guard Name</span>
                                    <input type="text" name="guard_name" id="guard_name" autocomplete="off" required
                                    class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                </div>
                            </div>
                    
                        </div>
                        <div class="mt-6 flex items-center justify-end gap-x-6">
                            <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancelar</button>
                            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white 
                            shadow-lg hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 
                            focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            </div>
            {{-- Listado de permisos --}}
            <div
            class="border border-t-0 border-neutral-200 bg-white dark:border-neutral-600 dark:bg-neutral-800">
                <h2 class="mb-0" id="headingTwo">
                <button
                class="bg-blue-500 group relative flex w-full items-center border-0  px-5 py-4 text-left text-base
                transition [overflow-anchor:none] hover:z-[2] focus:z-[3] focus:outline-none text-white"
                    type="button"
                    data-te-collapse-init
                    data-te-collapse-collapsed
                    data-te-target="#collapseTwo"
                    aria-expanded="false"
                    aria-controls="collapseTwo">
                    Listado de permisos
                    <span
                    class="-mr-1 ml-auto h-5 w-5 shrink-0 rotate-[-180deg] fill-[#336dec] transition-transform duration-200 
                    ease-in-out group-[[data-te-collapse-collapsed]]:mr-0 group-[[data-te-collapse-collapsed]]:rotate-0 
                    group-[[data-te-collapse-collapsed]]:fill-[#212529] motion-reduce:transition-none dark:fill-blue-300 
                    dark:group-[[data-te-collapse-collapsed]]:fill-white" id="iconTwo">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="h-6 w-6">
                        <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                    </span>
                </button>
                </h2>
                <div
                id="collapseTwo"
                class="!visible hidden"
                data-te-collapse-item
                aria-labelledby="headingTwo"
                data-te-parent="#accordionExample">
                    <div class="px-5 py-4">
                        <p class="mt-1 text-sm leading-6 text-gray-600">Listado de permisos:</p>    

            
                        @if (empty($permissions[0]))
                            <div class="mt-6 flex items-center justify-end gap-x-6">
                                No existen permisos creados!
                                <a href="" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Nuevo</a>
                            </div>
                        
                            
                        @else
                            <div>
                                <table class="border-collapse border border-slate-400 table-auto mt-6 rounded">
                                    <thead class="border border-slate-300">
                                        <th class="bg-blue-300 px-2 border border-slate-300">Nombre</th>
                                        <th class="bg-blue-300 px-2 border border-slate-300">Guard</th>
                                        <th class="bg-blue-300 px-2 border border-slate-300">Fecha</th>
                                        <th class="bg-blue-300 px-2 border border-slate-300"></th>
                    
                                    </thead>
                                    <tbody>
                                    @foreach ($permissions as $permission)
                                    <tr class="">
                                        <td class="px-2 border border-slate-300">{{ucwords($permission->name)}}</td>
                                        <td class="px-2 border border-slate-300">{{ucfirst($permission->guard_name)}}</td>                                        
                                        <td class="px-2 text-center border border-slate-300">{{$permission->created_at}}</td>
                                        <td class="px-2 py-2 border border-slate-300">
                                            <a href="{{route('permission.edit',['permission'=>$permission->id])}}" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                                Editar
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
            </div>


        </div>
    </div>

    <script>
        var accordion1 = document.getElementById('headingOne');
        var collapse1 = document.getElementById('collapseOne');
        var iconRotate1 = document.getElementById('iconOne');
        
        accordion1.addEventListener("click",function(){
            collapse1.classList.toggle("hidden");
            iconRotate1.classList.toggle("rotate-[-0deg]");
        });

        var accordion2 = document.getElementById('headingTwo');
        var collapse2 = document.getElementById('collapseTwo');
        var iconRotate2 = document.getElementById('iconTwo');
        
        accordion2.addEventListener("click",function(){
            collapse2.classList.toggle("hidden");
            iconRotate2.classList.toggle("rotate-[-0deg]");
        });
    </script>
</x-manage>