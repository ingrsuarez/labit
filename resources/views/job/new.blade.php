<x-admin-layout>
    <div class="flex flex-col justify-start">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl shadow-lg">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Nuevo Puesto</h2>

            <form action="{{ route('job.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @csrf

                <!-- Nombre del puesto -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre del puesto</label>
                    <input type="text" name="name" id="name" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Parent (job padre) -->
                <div>
                    <label for="parent_id" class="block text-sm font-medium text-gray-700">Puesto padre</label>
                    <select name="parent_id" id="parent_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Ninguno —</option>
                        @foreach($jobs as $job)
                            <option value="{{ $job->id }}">{{ $job->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Departamento -->
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700">Departamento</label>
                    <input type="text" name="department" id="department"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Agreement -->
                <div>
                    <label for="agreement" class="block text-sm font-medium text-gray-700">Convenio</label>
                    <input type="text" name="agreement" id="agreement"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Categoría -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Categoría</label>
                    <select name="category_id" id="category_id" required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Seleccionar —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->agreement }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Responsibilities -->
                <div class="md:col-span-2">
                    <label for="responsibilities" class="block text-sm font-medium text-gray-700">Responsabilidades</label>
                    <textarea name="responsibilities" id="responsibilities" rows="4"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <!-- Email -->
                <div class="md:col-span-2">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email de contacto</label>
                    <input type="email" name="email" id="email"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Botón -->
                <div class="md:col-span-2 flex justify-end mt-6">
                    <button type="submit" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                        Guardar puesto
                    </button>
                </div>
            </form>
        </div>




    </div>
</x-admin-layout>