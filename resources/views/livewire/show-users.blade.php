<div>
    <p class="mt-1 text-sm leading-6 text-gray-600">Listado de usuarios:</p>    

        
    @if (empty($users[0]))
        <div class="mt-6 flex items-center justify-end gap-x-6">
            No existen usuarios con este criterio de búsqueda!
            <a href="" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Nuevo</a>
        </div>
    
        
    @else
        <div>
            <table class="border-collapse border border-slate-400 table-auto mt-6 rounded">
                <thead class="border border-slate-300">
                    <th class="bg-blue-300 px-2 border border-slate-300">Nombre</th>
                    <th class="bg-blue-300 px-2 border border-slate-300">Mail</th>
                    <th class="bg-blue-300 px-2 border border-slate-300"></th>

                </thead>
                <tbody>
                @foreach ($users as $user)
                <tr class="">
                    <td class="px-2 border border-slate-300">{{ucwords($user->name)}}</td>
                    <td class="px-2 border border-slate-300">{{$user->email}}</td>
                    {{-- <td class="px-2 border border-slate-300">
                        @foreach($user->jobs as $job)
                                {{ucwords($job->name)." - "}}    
                        @endforeach
                    </td> --}}
                    <td class="px-2 py-2 border border-slate-300">
                        <a href="{{route('user.edit',['user'=>$user->id])}}" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Roles
                        </a>
                    </td>
                </tr>
                
                @endforeach  
                </tbody>
            </table>
        </div>  
    @endif
</div>
