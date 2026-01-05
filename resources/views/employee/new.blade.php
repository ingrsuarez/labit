<x-admin-layout>
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-2xl shadow-lg">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Nuevo Empleado</h2>

    <form action="{{ route('employee.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" name="name" id="name" 
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
        </div>

        <!-- Last Name -->
        <div>
            <label for="lastName" class="block text-sm font-medium text-gray-700">Apellido</label>
            <input type="text" name="lastName" id="lastName" 
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
        </div>

        <!-- Employee ID -->
        <div>
            <label for="employeeId" class="block text-sm font-medium text-gray-700">ID Empleado</label>
            <input type="text" name="employeeId" id="employeeId"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
        </div>

        <!-- User ID -->
        <div>
            <label for="user_id" class="block text-sm font-medium text-gray-700">Usuario asociado</label>
            <select name="user_id" id="user_id" 
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">— Sin usuario —</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
        </div>


        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Start Date -->
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700">Fecha de inicio</label>
            <input type="date" name="start_date" id="start_date"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Vacation Days -->
        <div>
            <label for="vacation_days" class="block text-sm font-medium text-gray-700">Días de vacaciones</label>
            <input type="number" name="vacation_days" id="vacation_days"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Bank Account -->
        <div>
            <label for="bank_account" class="block text-sm font-medium text-gray-700">Cuenta bancaria</label>
            <input type="text" name="bank_account" id="bank_account"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Position -->
        <div>
            <label for="job_id" class="block text-sm font-medium text-gray-700">Puesto</label>
            <select name="job_id" id="job_id"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">— Seleccionar —</option>
                @foreach($jobs as $job)
                    <option value="{{ $job->id }}">{{ $job->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Health Registration -->
        <div>
            <label for="health_registration" class="block text-sm font-medium text-gray-700">Registro de salud</label>
            <input type="text" name="health_registration" id="health_registration"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Sex -->
        <div>
            <label for="sex" class="block text-sm font-medium text-gray-700">Sexo</label>
            <select name="sex" id="sex" 
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                <option value="">Seleccione...</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
                <option value="O">Otro</option>
            </select>
        </div>

        <!-- Weekly Hours -->
        <div>
            <label for="weekly_hours" class="block text-sm font-medium text-gray-700">Horas semanales</label>
            <input type="number" name="weekly_hours" id="weekly_hours"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Birth -->
        <div>
            <label for="birth" class="block text-sm font-medium text-gray-700">Fecha de nacimiento</label>
            <input type="date" name="birth" id="birth"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Phone -->
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input type="text" name="phone" id="phone"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Address -->
        <div class="md:col-span-2">
            <label for="address" class="block text-sm font-medium text-gray-700">Dirección</label>
            <input type="text" name="address" id="address"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- City -->
        <div>
            <label for="city" class="block text-sm font-medium text-gray-700">Ciudad</label>
            <input type="text" name="city" id="city"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- State -->
        <div>
            <label for="state" class="block text-sm font-medium text-gray-700">Provincia/Estado</label>
            <input type="text" name="state" id="state"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Country -->
        <div>
            <label for="country" class="block text-sm font-medium text-gray-700">País</label>
            <input type="text" name="country" id="country"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Status -->
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
            <select name="status" id="status"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Seleccione...</option>
                <option value="active">Activo</option>
                <option value="inactive">Inactivo</option>
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

</x-admin-layout>