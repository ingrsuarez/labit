<x-manage>
    <div class="max-w-7xl mx-auto p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Resumen de licencias</h1>

        {{-- Filtros --}}
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 bg-white p-4 rounded-xl shadow mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Año</label>
                <input type="number" name="year" value="{{ $filters['year'] ?? '' }}" min="2000" max="2100"
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Mes</label>
                <select name="month"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Todos —</option>
                    @for($m=1; $m<=12; $m++)
                        <option value="{{ $m }}" @selected(($filters['month'] ?? '') == $m)>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>
            <div class="">
                <label class="block text-sm font-medium text-gray-700">Empleado</label>
                <select name="employee_id"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Todos —</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(($filters['employee_id'] ?? '') == $e->id)>
                            {{ ucfirst($e->lastName) }}, {{ ucfirst($e->name) }} — #{{ $e->employeeId }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3 flex-wrap items-end">
                <a href="{{ route('leave.resume') }}"
                class="px-4 py-2 my-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Limpiar</a>
                <button class="px-4 my-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Filtrar</button>
                
            {{-- Botón que abre la vista de 4 meses centrada en este mes --}}
                @php
                    $yearFilter = request('year') ?? now()->year;
                    $monthFilter = request('month') ?? now()->month;
                    $ymFilter = sprintf('%04d-%02d', $yearFilter, $monthFilter);
                @endphp
                <a href="{{ route('leave.resume.compact', array_merge(request()->only(['employee_id','year','month']), ['anchor' => $ymFilter])) }}"
                class="px-4 py-2 my-3 border border-green-500 rounded-lg bg-green-200 text-gray-800 hover:bg-green-300">
                    Últimos 
                </a>
            </div>
        </form>

        {{-- Tabla agrupada por Año-Mes --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            @php
                $grouped = $resumes->groupBy(fn($r) => sprintf('%04d-%02d', $r->year, $r->month));
            @endphp

            @forelse($grouped as $ym => $rows)
                @php
                    // Extraer año y mes del período
                    [$periodYear, $periodMonth] = explode('-', $ym);
                    $periodMonth = (int)$periodMonth;
                    $periodYear = (int)$periodYear;
                @endphp
                <div class="bg-gray-100 px-4 py-3 border-t-2 border-blue-300 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-600">Periodo</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $ym }}</div>
                    </div>

                    {{-- Botones de exportación por período --}}
                    <div class="flex gap-2">
                        <a href="{{ route('leave.export.excel', ['year' => $periodYear, 'month' => $periodMonth, 'employee_id' => request('employee_id')]) }}"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm hover:bg-green-700 transition-colors shadow-sm"
                           title="Descargar Excel del período {{ $ym }}">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Excel
                        </a>
                        <a href="{{ route('leave.export.pdf', ['year' => $periodYear, 'month' => $periodMonth, 'employee_id' => request('employee_id')]) }}"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm hover:bg-red-700 transition-colors shadow-sm"
                           title="Descargar PDF del período {{ $ym }}">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            PDF
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CUIL</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Horas sem.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total días</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Horas 50%</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Horas 100%</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Certificados</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($rows as $r)
                                @php
                                    $files = collect(explode('|', (string)$r->files))
                                        ->filter(fn($f) => !empty($f));
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $r->employee }}</div>
                                        {{-- <div class="text-xs text-gray-500">#{{ $r->employee_id }}</div> --}}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $r->cuil ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ (int)($r->weekly_hours ?? 0) }}</td>
                                    
                                    
                                    <td class="px-4 py-3 text-gray-700 capitalize">{{ $r->type }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ $r->cantidad }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ (int)$r->total_dias }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ (int)$r->horas_50 }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ (int)$r->horas_100 }}</td>
                                    <td class="px-4 py-3">
                                        @if($files->isEmpty())
                                            <span class="text-gray-400 text-sm">—</span>
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($files as $idx => $file)
                                                    <a href="{{ asset('storage/'.$file) }}" target="_blank"
                                                    class="inline-flex items-center px-2 py-1 rounded-md bg-blue-50 text-blue-700 text-xs hover:bg-blue-100">
                                                        Cert. {{ $idx+1 }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('leave.edit', $r) }}"
                                            class="text-blue-600 text-sm hover:underline">
                                            Editar
                                        </a>
                                     
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="p-6 text-center text-gray-500">No hay novedades para el criterio seleccionado.</div>
            @endforelse
        </div>
    </div>

    <div class="flex flex-col justify-start">


        <div class="bg-white mt-2 pb-4 px-2 w-fit lg:w-fit rounded-lg shadow-lg ">
            <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Novedades:</h2>
            <p class="mt-1 text-sm leading-6 text-gray-600">Licencias:</p>    

            
                @if (empty($resumes[0]))
                    <div class="mt-6 flex items-center justify-end gap-x-6">
                        No existen licencias!
                        <a href="" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Nuevo</a>
                    </div>
                
                    
                @else
                    <div>
                        <table class="border-collapse border border-slate-400 table-auto mt-6 rounded">
                            <thead class="border border-slate-300">
                                <th class="bg-blue-300 px-2 border border-slate-300">Año</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Mes</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Empleado</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Tipo</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Categoría</th>
                                <th class="bg-blue-300 px-2 border border-slate-300">Días</th>
                                <th class="bg-blue-300 px-2 border border-slate-300"></th>
                            </thead>
                            <tbody>
                            @foreach ($resumes as $leave)
                            <tr class="">
                                <td class="px-2 border border-slate-300">{{$leave->year}}</td>
                                <td class="px-2 border border-slate-300">{{$leave->month}}</td>
                                
                                <td class="px-2 border border-slate-300">{{ucwords($leave->employee)}}</td>
                                <td class="px-2 border border-slate-300">{{ucwords($leave->type)}}</td>
                                <td class="px-2 border border-slate-300">{{ucwords($leave->category)}}</td>
                                <td class="px-2 border border-slate-300"><strong>{{ucwords($leave->days)}}</strong></td>
                                <td class="px-2 py-2 border border-slate-300">
                                    <a href="{{route('leave.delete',$leave->leave_id)}}" class="rounded-md bg-red-600 mx-2 px-2 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                        Eliminar
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
</x-manage>