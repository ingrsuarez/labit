<x-manage>
    <div class="flex flex-col lg:flex-row justify-start">
    <div class="bg-white pb-4 px-2 w-fit lg:w-full rounded-lg shadow-lg">
        <form class="" action="{{route('job.save')}}" method="POST">
            @csrf
              {{-- <div class="space-y-10 ">       --}}
                <input type="hidden" name="id" value="{{$job->id}}">
                <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Nuevo puesto:</h2>
                <p class="mt-1 text-sm leading-6 text-gray-600">Complete los datos:</p>
              
                <div class="mx-2 mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-8">
                    <div class="sm:col-span-4 border-slate-400 border-2 rounded-lg  ">
                        
                        <div class="justify-items-stretch flex flex-wrap">
                            <span class="w-2/6 px-4 items-center flex text-base bg-gray-300 rounded-l-lg  ">Nombre</span>
                            <input type="text" name="name" id="name" autocomplete="off" required autofocus
                                class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                                sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600"
                                value="{{ucwords($job->name)}}">
                            
                        </div>
                    </div>

                    <div class="sm:col-span-4">
          
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap ">
                            <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Orden</span>
                            <input type="text" name="order" id="order" autocomplete="off" required
                            class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400
                             sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600"
                             value="{{$job->order}}">
                        </div>
                    </div>
              
                    <div class="sm:col-span-4">
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                          <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Supervisor:</span>
                          <select id="parent" name="parent" autocomplete="off" class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 h-full focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            
                            @foreach ($jobs as $parent)
                                @if($parent->id == $job->parent_id)
                                    <option value="{{$parent->id}}" selected>{{ucwords($parent->name)}}</option>
                                @elseif($parent->order < $job->order)
                                    <option value="{{$parent->id}}">{{ucwords($parent->name)}}</option>
                                @endif
                            @endforeach
                          </select>
                        </div>
                    </div>
              
                    <div class="sm:col-span-4">
                    
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-1/5 px-2 items-center flex bg-gray-300 rounded-l-lg">Email:</span>
                            <input type="email" name="email" id="email" autocomplete="off" 
                            class="w-4/5 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400
                             sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600"
                             value="{{$job->email}}">
                        </div>
                    </div>

                    <div class="sm:col-span-5">
                    
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-1/4 px-4 items-center flex bg-gray-300 rounded-l-lg">Sector:</span>
                            <input type="text" name="department" id="department" autocomplete="off" 
                            class="w-3/4 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400
                             sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600"
                             value="{{$job->department}}">
                        </div>
                    </div>
                    
                    <div class="sm:col-span-9">
                    
                        <div class="border-slate-400 border-2 rounded-lg justify-items-stretch flex flex-wrap">
                            <span class="w-2/6 px-4 items-center flex bg-gray-300 rounded-l-lg">Responsabilidades:</span>
                            <textarea id="responsibilities" name="responsibilities" rows="4" cols="50" name="phone" id="phone" autocomplete="off" 
                            class="w-4/6 flex rounded-r-md border-0 text-gray-900 shadow-sm  placeholder:text-gray-400 
                            sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600"
                            >{{$job->responsibilities}}
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
</x-manage>