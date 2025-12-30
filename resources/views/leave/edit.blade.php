<x-admin-manage>
    <div class="flex flex-col justify-start">
        <div class="bg-slate-200 max-w-4xl mx-auto p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Editar licencia</h1>

            {{-- Errores de validación --}}
            @if ($errors->any())
                <div class="mb-6 rounded-xl bg-red-50 border border-red-200 p-4 text-red-700">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="text-sm">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('leave.update', $leave) }}" method="POST" enctype="multipart/form-data"
                class="bg-white rounded-2xl shadow p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                @csrf
                @method('POST')

                {{-- Empleado --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Empleado</label>
                    <select name="employee" required
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected($emp->id == $leave->employee_id)>
                                {{ ucfirst($emp->lastName) }}, {{ ucfirst($emp->name) }} — #{{ $emp->employeeId }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="employee" value="{{ $leave->employee_id }}">
                </div>

                {{-- Tipo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo</label>
                    <select name="type" required
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @foreach(['enfermedad','vacaciones','embarazo','capacitacion','horas extra'] as $t)
                            <option value="{{ $t }}" @selected($leave->type === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Médico --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Médico</label>
                    <input type="text" name="doctor" value="{{ old('doctor', $leave->doctor) }}"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Dr./Dra. ...">
                </div>

                {{-- Desde / Hasta --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Desde</label>
                    <input type="date" name="start" value="{{ old('start', $leave->start?->format('Y-m-d')) }}" required
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Hasta</label>
                    <input type="date" name="end" value="{{ old('end', $leave->end?->format('Y-m-d')) }}" required
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Horas --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Horas 50%</label>
                    <input type="number" name="hour_50" value="{{ old('hour_50', $leave->hour_50) }}" min="0"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Horas 100%</label>
                    <input type="number" name="hour_100" value="{{ old('hour_100', $leave->hour_100) }}" min="0"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Descripción --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <input type="text" name="description" value="{{ old('description', $leave->description) }}" required
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Motivo, observaciones, etc.">
                </div>

                {{-- Certificado --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Reemplazar certificado</label>
                    <input type="file" name="file" accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0 file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    @if ($leave->file)
                        <p class="text-xs mt-2">
                            Actual: <a href="{{ asset('storage/'.$leave->file) }}" target="_blank" class="text-blue-600 underline">Ver certificado</a>
                        </p>
                    @endif
                </div>

                {{-- Justificada (solo para enfermedad) --}}
                <div class="md:col-span-2 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_justified" value="1" 
                               {{ old('is_justified', $leave->is_justified) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500 w-5 h-5">
                        <span class="ml-3 text-sm font-medium text-amber-800">Licencia Justificada</span>
                    </label>
                    <p class="mt-2 text-xs text-amber-700">
                        <strong>Importante:</strong> Marcar esta opción indica que la licencia está justificada aunque no tenga certificado adjunto. 
                        En la liquidación se contará como <strong>días de enfermedad</strong> (se pagan) en lugar de <strong>inasistencia</strong> (no se pagan).
                    </p>
                </div>

                {{-- Botón --}}
                <div class="md:col-span-2 flex justify-end">
                    <button class="px-6 py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                        Actualizar licencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-manage>
