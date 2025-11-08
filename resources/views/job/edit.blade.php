<x-manage>
    <div class="mx-auto max-w-5xl p-4 lg:p-6">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Editar puesto</h2>
                    <p class="text-sm text-gray-500">Completá o actualizá los datos del puesto.</p>
                </div>
                <a href="{{ url()->previous() }}"
                class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
            </div>

            <form
                class="px-5 pb-6 pt-4"
                action="{{ route('job.save') }}"
                method="POST"
            >
                @csrf
                {{-- Si usás resource:
                @method('PUT')
                <input type="hidden" name="id" value="{{ $job->id }}">
                --}}
                <input type="hidden" name="id" value="{{ $job->id ?? '' }}">

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {{-- Nombre --}}
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            required
                            autofocus
                            autocomplete="off"
                            value="{{ old('name', $job->name ?? '') }}"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="Ej.: Secretaria Administrativa"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Categoría --}}
                    <div>
                        <label for="category" class="mb-1 block text-sm font-medium text-gray-700">Categoría</label>
                        <select
                            id="category"
                            name="category"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Ninguna</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    @selected( old('category', $job->category_id ?? '') == $category->id )>
                                    {{ ucwords($category->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Supervisor --}}
                    <div>
                        <label for="parent" class="mb-1 block text-sm font-medium text-gray-700">Supervisor</label>
                        <select
                            id="parent"
                            name="parent"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Sin supervisor</option>
                            @foreach ($jobs as $parent)
                                @continue( isset($job->id) && $parent->id === $job->id ) {{-- evita ser su propio supervisor --}}
                                <option value="{{ $parent->id }}"
                                    @selected( old('parent', $job->parent_id ?? '') == $parent->id )>
                                    {{ ucwords($parent->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            autocomplete="off"
                            value="{{ old('email', $job->email ?? '') }}"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="ejemplo@clinic.com"
                        >
                        @error('email')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Sector --}}
                    <div>
                        <label for="department" class="mb-1 block text-sm font-medium text-gray-700">Sector</label>
                        <input
                            type="text"
                            id="department"
                            name="department"
                            autocomplete="off"
                            value="{{ old('department', $job->department ?? '') }}"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="Recepción, Administración, Laboratorio, etc."
                        >
                        @error('department')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Convenio --}}
                    <div>
                        <label for="agreement" class="mb-1 block text-sm font-medium text-gray-700">Convenio</label>
                        <input
                            type="text"
                            id="agreement"
                            name="agreement"
                            autocomplete="off"
                            value="{{ old('agreement', $job->agreement ?? '') }}"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="Sanidad 108/75, etc."
                        >
                        @error('agreement')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Responsabilidades (ocupa 2 col) --}}
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between">
                            <label for="responsibilities" class="mb-1 block text-sm font-medium text-gray-700">Responsabilidades</label>
                            <span class="text-xs text-gray-500">Podés pegar texto con viñetas.</span>
                        </div>
                        <textarea
                            id="responsibilities"
                            name="responsibilities"
                            rows="5"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="• Atención a pacientes
    • Gestión de turnos
    • Facturación y liquidación de obras sociales
    • Archivo y documentación, etc."
                        >{{ old('responsibilities', $job->responsibilities ?? '') }}</textarea>
                        @error('responsibilities')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Footer acciones --}}
                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ url()->previous() }}"
                    class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>



</x-manage>