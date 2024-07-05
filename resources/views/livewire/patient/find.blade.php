<div>
    
    <div class="inline-flex">
        <input wire:model.live="dni" class="flex rounded-md text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600"
         name="dni" type="search" placeholder="DNI" autofocus>
        <input wire:model.live="name" class="flex rounded-md mx-2 text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600"
         name="nombre" type="search" placeholder="Nombre">
        <input wire:model.live="lastName" class="flex rounded-md text-gray-900 shadow-sm  placeholder:text-gray-400 sm:text-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600"
         name="apellido" type="search" placeholder="Apellido" aria-label="Search" >
        
                       
    </div>
    <br>
    <div class="inline-flex">
        @if (empty($patients[0]))
            <div class="mt-6 flex items-center justify-end gap-x-6">
                No existen Pacientes con ese criterio de búsqueda!
                <a href="{{route('patient.index')}}" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Nuevo</a>
            </div>
        
            
        @else
            <div>
                <table class="border-collapse border border-slate-400 table-auto mt-6 rounded">
                    <thead class="border border-slate-300">
                        <th class="bg-blue-300 px-2 border border-slate-300">DNI</th>
                        <th class="bg-blue-300 px-2 border border-slate-300">Apellido</th>
                        <th class="bg-blue-300 px-2 border border-slate-300">Nombre</th>
                        <th class="bg-blue-300 px-2 border border-slate-300">Teléfono</th>
                        <th class="bg-blue-300 px-2 border border-slate-300">Fecha de nacimiento</th>
                        <th class="bg-blue-300 px-2 border border-slate-300">Email</th>
                        <th class="bg-blue-300 px-2 border border-slate-300"></th>
    
                    </thead>
                    <tbody>
                    @foreach ($patients as $patient)
                    <tr class="">
                        <td class="px-2 border border-slate-300">{{$patient->patientId}}</td>
                        <td class="px-2 border border-slate-300">{{ucfirst($patient->lastName)}}</td>
                        <td class="px-2 border border-slate-300">{{ucfirst($patient->name)}}</td>
                        <td class="px-2 border border-slate-300">{{$patient->phone}}</td>
                        <td class="px-2 border border-slate-300">{{$patient->birth}}</td>
                        <td class="px-2 border border-slate-300">{{$patient->email}}</td>
                        <td class="px-2 py-2 border border-slate-300"><a href="{{route('admission.index',['current_patient'=>$patient->patientId])}}" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Seleccionar</a></td>
                    </tr>
                    
                    @endforeach  
                    </tbody>
                </table>
            </div>  
        @endif
        
    </div>
</div>
