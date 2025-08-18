<x-manage>
    <div class="flex flex-col justify-start">
        <div class="bg-slate-200 max-w-4xl mx-auto p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Nueva licencia</h1>

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

            <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data"
                class="bg-white rounded-2xl shadow p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                @csrf

                {{-- Empleado --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Empleado</label>
                    <select name="employee" required
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Seleccione —</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected(old('employee') == $emp->id)>
                                {{ ucfirst($emp->lastName) }}, {{ ucfirst($emp->name) }} — #{{ $emp->employeeId }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo</label>
                    <select name="type" required
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Seleccione —</option>
                        @foreach(['enfermedad','vacaciones','embarazo','capacitacion','horas extra'] as $t)
                            <option value="{{ $t }}" @selected(old('type') === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Médico (opcional) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Médico</label>
                    <input type="text" name="doctor" value="{{ old('doctor') }}"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Dr./Dra. ...">
                </div>

                {{-- Desde / Hasta --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Desde</label>
                    <input type="date" name="start" value="{{ old('start') }}" required
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Hasta</label>
                    <input type="date" name="end" value="{{ old('end') }}" required
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Horas 50% / 100% (opcionales) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Horas 50%</label>
                    <input type="number" name="hour_50" value="{{ old('hour_50') }}" min="0"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Horas 100%</label>
                    <input type="number" name="hour_100" value="{{ old('hour_100') }}" min="0"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Descripción --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <input type="text" name="description" value="{{ old('description') }}" required
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Motivo, observaciones, etc.">
                </div>

                {{-- Certificado (imagen) --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Certificado (imagen)</label>
                    <input type="file" name="file" accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0 file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">Formatos: JPG, PNG, WEBP. Tamaño máx. 5MB.</p>
                </div>

                {{-- Botón --}}
                <div class="md:col-span-2 flex justify-end">
                    <button class="px-6 py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                        Guardar licencia
                    </button>
                </div>
            </form>
        </div>



    </div>
</x-manage>