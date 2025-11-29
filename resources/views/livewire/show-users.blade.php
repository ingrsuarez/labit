<div>
    <p class="mt-1 text-sm leading-6 text-gray-600">Listado de usuarios del sistema:</p>    

        
    @if (empty($users[0]))
        <div class="mt-6 flex items-center justify-end gap-x-6">
            No existen usuarios con este criterio de búsqueda!
            <a href="" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Nuevo</a>
        </div>
    
        
    @else
        <div class="overflow-x-auto">
            <table class="border-collapse border border-slate-400 table-auto mt-6 rounded w-full">
                <thead class="border border-slate-300">
                    <th class="bg-blue-500 text-white px-4 py-2 border border-slate-300">Nombre</th>
                    <th class="bg-blue-500 text-white px-4 py-2 border border-slate-300">Email</th>
                    <th class="bg-blue-500 text-white px-4 py-2 border border-slate-300">Roles</th>
                    <th class="bg-blue-500 text-white px-4 py-2 border border-slate-300">Empleado Asociado</th>
                    <th class="bg-blue-500 text-white px-4 py-2 border border-slate-300">Acciones</th>
                </thead>
                <tbody>
                @foreach ($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border border-slate-300">{{ucwords($user->name)}}</td>
                    <td class="px-4 py-2 border border-slate-300">{{$user->email}}</td>
                    <td class="px-4 py-2 border border-slate-300">
                        @if($user->roles->count() > 0)
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->roles as $role)
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full 
                                        @if($role->name == 'admin' || $role->name == 'administrator') 
                                            bg-red-100 text-red-700
                                        @elseif($role->name == 'rrhh' || $role->name == 'hr')
                                            bg-purple-100 text-purple-700
                                        @else
                                            bg-blue-100 text-blue-700
                                        @endif
                                    ">
                                        {{ucwords($role->name)}}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-gray-400 text-sm italic">Sin roles asignados</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 border border-slate-300">
                        @if($user->employee)
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Vinculado
                                </span>
                                <span class="text-sm text-gray-700">
                                    {{ucwords($user->employee->name)}} {{ucwords($user->employee->lastName ?? '')}}
                                </span>
                            </div>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-500">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                No vinculado
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-2 border border-slate-300">
                        <div class="flex gap-2">
                            <a href="{{route('user.edit',['user'=>$user->id])}}" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Editar
                            </a>
                        </div>
                    </td>
                </tr>
                
                @endforeach  
                </tbody>
            </table>
        </div>
        
        <!-- Resumen -->
        <div class="mt-4 flex gap-4 text-sm text-gray-600">
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                Total usuarios: {{ $users->count() }}
            </span>
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                Con empleado: {{ $users->filter(fn($u) => $u->employee)->count() }}
            </span>
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-gray-400"></span>
                Sin empleado: {{ $users->filter(fn($u) => !$u->employee)->count() }}
            </span>
        </div>
    @endif
</div>
