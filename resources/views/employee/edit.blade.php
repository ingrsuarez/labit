<x-admin-layout>
<div class="max-w-5xl mx-auto bg-white p-8 rounded-2xl shadow-lg">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Editar Empleado</h2>

    <form action="{{ route('employee.update') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @csrf
        <input type="hidden" name="id" id="id" value="{{ $employee->id }}">
        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" name="name" id="name" 
                value="{{ old('name', $employee->name) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
        </div>

        <!-- Last Name -->
        <div>
            <label for="lastName" class="block text-sm font-medium text-gray-700">Apellido</label>
            <input type="text" name="lastName" id="lastName" 
                value="{{ old('lastName', $employee->lastName) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
        </div>

        <!-- Employee ID -->
        <div>
            <label for="employeeId" class="block text-sm font-medium text-gray-700">ID Empleado</label>
            <input type="text" name="employeeId" id="employeeId"
                value="{{ old('employeeId', $employee->employeeId) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
        </div>

        <!-- User ID -->
        <div>
            <label for="user_id" class="block text-sm font-medium text-gray-700">Usuario asociado</label>
            <select name="user_id" id="user_id" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">— Sin usuario —</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id', $employee->user_id) == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
        </div>


        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email"
                value="{{ old('email', $employee->email) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Start Date -->
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700">Fecha de inicio</label>
            <input type="date" name="start_date" id="start_date"
                value="{{ old('start_date', $employee->start_date) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Vacation Days -->
        <div>
            <label for="vacation_days" class="block text-sm font-medium text-gray-700">Días de vacaciones</label>
            <input type="number" name="vacation_days" id="vacation_days"
                value="{{ old('vacation_days', $employee->vacation_days) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Bank Account -->
        <div>
            <label for="bank_account" class="block text-sm font-medium text-gray-700">Cuenta bancaria</label>
            <input type="text" name="bank_account" id="bank_account"
            value="{{ old('bank_account', $employee->bank_account) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        
        <!-- Puestos asignados -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Puestos actuales</label>

            <div id="job-list" class="flex flex-wrap gap-2 mt-2">
                @foreach($employee->jobs as $job)
                    <div class="flex items-center bg-blue-100 text-blue-800 px-3 py-1 rounded-full job-item" data-id="{{ $job->id }}">
                        <span>{{ $job->name }}</span>
                        <button type="button" class="ml-2 text-red-500 hover:text-red-700 remove-job" data-id="{{ $job->id }}">×</button>
                        <input type="hidden" name="job_ids[]" value="{{ $job->id }}">
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Agregar nuevo puesto -->
        <div class="md:col-span-2">
            <label for="add_job" class="block text-sm font-medium text-gray-700">Agregar puesto</label>
            <div class="flex items-center gap-4 mt-1">
                <select id="add_job" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Seleccionar —</option>
                    @foreach($jobs as $job)
                        <option value="{{ $job->id }}">{{ $job->name }}</option>
                    @endforeach
                </select>
                <button type="button" id="add_job_btn"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700 focus:ring-2 focus:ring-green-500">
                    Agregar
                </button>
            </div>
        </div>
        <!-- Health Registration -->
        <div>
            <label for="health_registration" class="block text-sm font-medium text-gray-700">Registro de salud</label>
            <input type="text" name="health_registration" id="health_registration"
                    value="{{ old('health_registration', $employee->health_registration) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Sex -->
        <div>
            <label for="sex" class="block text-sm font-medium text-gray-700">Sexo</label>
            <select name="sex" id="sex" 
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                <option value="">Seleccione...</option>
                <option value="M" {{ old('sex', $employee->sex) === 'M' ? 'selected' : '' }}>Masculino</option>
                <option value="F" {{ old('sex', $employee->sex) === 'F' ? 'selected' : '' }}>Femenino</option>
                <option value="O" {{ old('sex', $employee->sex) === 'O' ? 'selected' : '' }}>Otro</option>
            </select>
        </div>

        <!-- Weekly Hours -->
        <div>
            <label for="weekly_hours" class="block text-sm font-medium text-gray-700">Horas semanales</label>
            <input type="number" name="weekly_hours" id="weekly_hours"
                value="{{ old('weekly_hours', $employee->weekly_hours) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Birth -->
        <div>
            <label for="birth" class="block text-sm font-medium text-gray-700">Fecha de nacimiento</label>
            <input type="date" name="birth" id="birth"
                value="{{ old('birth', $employee->birth) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Phone -->
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input type="text" name="phone" id="phone"
            value="{{ old('phone', $employee->phone) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Address -->
        <div class="md:col-span-2">
            <label for="address" class="block text-sm font-medium text-gray-700">Dirección</label>
            <input type="text" name="address" id="address"
                value="{{ old('address', $employee->address) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- City -->
        <div>
            <label for="city" class="block text-sm font-medium text-gray-700">Ciudad</label>
            <input type="text" name="city" id="city"
                value="{{ old('city', $employee->city) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- State -->
        <div>
            <label for="state" class="block text-sm font-medium text-gray-700">Provincia/Estado</label>
            <input type="text" name="state" id="state"
                value="{{ old('state', $employee->state) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Country -->
        <div>
            <label for="country" class="block text-sm font-medium text-gray-700">País</label>
            <input type="text" name="country" id="country"
                value="{{ old('country', $employee->country) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Status -->
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
            <select name="status" id="status"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Seleccione...</option>
                <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Activo</option>
                <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>

        <!-- Botón -->
        <div class="md:col-span-2 flex justify-end mt-6">
            <button type="submit" 
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                Guardar empleado
            </button>
        </div>
    </form>
</div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const jobList = document.getElementById('job-list');
            const addJobBtn = document.getElementById('add_job_btn');
            const addJobSelect = document.getElementById('add_job');

            // Quitar puesto
            jobList.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-job')) {
                    const jobId = e.target.dataset.id;
                    const item = document.querySelector(`.job-item[data-id="${jobId}"]`);
                    if (item) {
                        item.remove();
                    }
                }
            });

            // Agregar puesto
            addJobBtn.addEventListener('click', function () {
                const jobId = addJobSelect.value;
                const jobName = addJobSelect.options[addJobSelect.selectedIndex].text;

                // Evitar duplicados
                if (!jobId || document.querySelector(`.job-item[data-id="${jobId}"]`)) return;

                const wrapper = document.createElement('div');
                wrapper.className = "flex items-center bg-blue-100 text-blue-800 px-3 py-1 rounded-full job-item";
                wrapper.dataset.id = jobId;
                wrapper.innerHTML = `
                    <span>${jobName}</span>
                    <button type="button" class="ml-2 text-red-500 hover:text-red-700 remove-job" data-id="${jobId}">×</button>
                    <input type="hidden" name="job_ids[]" value="${jobId}">
                `;
                jobList.appendChild(wrapper);
            });
        });
    </script>
</x-admin-layout>