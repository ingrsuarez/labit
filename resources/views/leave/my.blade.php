<x-manage>
    <div class="max-w-5xl mx-auto p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-4">Mis licencias</h1>

        @if (isset($employee))
            <p class="text-sm text-gray-600 mb-6">
                Empleado: <span class="font-semibold">{{ ucwords($employee->lastName.' '.$employee->name) }}</span>
                (Legajo: {{ $employee->employeeId }})
            </p>
        @endif

        @if($leaves->isEmpty())
            <div class="bg-white rounded-xl shadow p-6 text-center text-gray-500">
                No tenés licencias registradas.
            </div>
        @else
            @php
                $grouped = $leaves->groupBy(function($item) {
                    return \Carbon\Carbon::parse($item->start)->format('Y-m');
                });
            @endphp

            <div class="bg-white p-4 rounded-xl shadow">
                @foreach($grouped as $periodo => $leavesPorPeriodo)
                    <h3 class="text-lg font-semibold mt-6 mb-2">Período: {{ $periodo }}</h3>
                    <table class="w-full text-sm text-left border mb-6">
                        <thead class="bg-slate-300">
                            <tr>
                                <th class="p-2">Fecha inicio</th>
                                <th class="p-2">Fecha fin</th>
                                <th class="p-2">Tipo</th>
                                <th class="p-2">Días</th>
                                <th class="p-2">Horas 50%</th>
                                <th class="p-2">Horas 100%</th>
                                <th class="p-2">Detalle</th>
                                <th class="p-2">Archivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leavesPorPeriodo as $leave)
                                <tr class="border-t">
                                    <td class="p-2">{{ $leave->start }}</td>
                                    <td class="p-2">{{ $leave->end }}</td>
                                    <td class="p-2 capitalize">{{ $leave->type }}</td>
                                    <td class="p-2">{{ $leave->days }}</td>
                                    <td class="p-2">{{ $leave->hour_50 }}</td>
                                    <td class="p-2">{{ $leave->hour_100 }}</td>
                                    <td class="p-2">{{ $leave->description }}</td>
                                    <td class="p-2">
                                        @if($leave->file)
                                            <a href="{{ asset('storage/' . $leave->file) }}" target="_blank" class="text-blue-500 underline">Ver</a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        @endif
    </div>
</x-manage>


