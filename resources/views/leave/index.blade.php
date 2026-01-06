<x-admin-layout title="Detalle de Licencias">

<div class="max-w-7xl mx-auto p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Detalle de Licencias</h2>
        <a href="{{ route('leave.new') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            + Nueva Licencia
        </a>
    </div>

    @php
        $grouped = $leaves->groupBy(function($item) {
            return \Carbon\Carbon::parse($item->start)->format('Y-m');
        });
    @endphp
    <div class="bg-white p-4 rounded-xl shadow mb-6">
        @foreach($grouped as $periodo => $leavesPorPeriodo)
            <h3 class="text-lg font-semibold mt-6 mb-2">Período: {{ $periodo }}</h3>
            <table class="w-full text-sm text-left border mb-6">
                <thead class="bg-slate-300">
                    <tr>
                        <th class="p-2">Empleado</th>
                        <th class="p-2">CUIL</th>
                        <th class="p-2">Fecha inicio</th>
                        <th class="p-2">Fecha fin</th>
                        <th class="p-2">Tipo</th>
                        <th class="p-2">Días</th>
                        <th class="p-2">Horas 50%</th>
                        <th class="p-2">Horas 100%</th>
                        <th class="p-2">Archivo</th>
                        <th class="p-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leavesPorPeriodo as $leave)
                        <tr class="border-t">
                            <td class="p-2">{{ ucwords($leave->employee->lastName.' '.$leave->employee->name) ?? '—' }}</td>
                            <td class="p-2">{{ $leave->employee->employeeId ?? '—' }}</td>
                            <td class="p-2">{{ $leave->start }}</td>
                            <td class="p-2">{{ $leave->end }}</td>
                            <td class="p-2">{{ $leave->type }}</td>
                            <td class="p-2">{{ $leave->days }}</td>
                            <td class="p-2">{{ $leave->hour_50 }}</td>
                            <td class="p-2">{{ $leave->hour_100 }}</td>
                            <td class="p-2">
                                @if($leave->file)
                                    <a href="{{ asset('storage/' . $leave->file) }}" target="_blank" class="text-blue-500 underline">Ver</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="p-2 flex gap-2">
                                <a href="{{ route('leave.edit', $leave) }}" 
                                   class="px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-xs">
                                    Editar
                                </a>
                                <a href="{{ route('leave.delete', $leave) }}" 
                                   onclick="return confirm('¿Estás seguro de eliminar esta licencia?')"
                                   class="px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-xs">
                                    Eliminar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    </div>
</div>

</x-admin-layout>