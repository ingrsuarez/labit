<x-lab-layout title="Editar Protocolo {{ $vetAdmission->protocol_number }}">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0" x-data="vetEditForm()">
        <div class="mb-6">
            <a href="{{ route('vet.admissions.show', $vetAdmission) }}" class="text-amber-600 hover:text-amber-800 text-sm flex items-center mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver al Protocolo
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Editar Protocolo {{ $vetAdmission->protocol_number }}</h1>
        </div>

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('vet.admissions.update', $vetAdmission) }}" method="POST">
            @csrf
            @method('PUT')
            @include('components.branch-select', ['selectedBranchId' => old('lab_branch_id', $vetAdmission->lab_branch_id)])
            <div class="space-y-6">
                {{-- Veterinaria y Derivante --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-amber-700 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Veterinaria y Derivante
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Veterinaria *</label>
                            <select name="customer_id" x-model="customerId" @change="onCustomerChange()" required
                                    class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Seleccionar veterinaria...</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ old('customer_id', $vetAdmission->customer_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Veterinario Derivante</label>
                            <select name="veterinarian_id" x-model="veterinarianId"
                                    class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Sin derivante</option>
                                <template x-for="v in veterinarians" :key="v.id">
                                    <option :value="v.id"
                                            :selected="v.id == initialVeterinarianId"
                                            x-text="v.name + (v.matricula ? ' (Mat: ' + v.matricula + ')' : '')"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Datos del Animal --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-amber-700 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        Datos del Animal
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Animal *</label>
                            <input type="text" name="animal_name" value="{{ old('animal_name', $vetAdmission->animal_name) }}" required
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Especie *</label>
                            <select name="species_id" required class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Seleccionar...</option>
                                @foreach($species as $sp)
                                    <option value="{{ $sp->id }}" {{ old('species_id', $vetAdmission->species_id) == $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Raza</label>
                            <input type="text" name="breed" value="{{ old('breed', $vetAdmission->breed) }}"
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Edad</label>
                            <input type="text" name="age" value="{{ old('age', $vetAdmission->age) }}"
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Dueño *</label>
                            <input type="text" name="owner_name" value="{{ old('owner_name', $vetAdmission->owner_name) }}" required
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono del Dueño</label>
                            <input type="text" name="owner_phone" value="{{ old('owner_phone', $vetAdmission->owner_phone) }}"
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email del Dueño</label>
                            <input type="email" name="owner_email" value="{{ old('owner_email', $vetAdmission->owner_email) }}"
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                            <input type="date" name="date" value="{{ old('date', $vetAdmission->date->format('Y-m-d')) }}" required
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        </div>
                    </div>
                </div>

                {{-- Observaciones --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea name="observations" rows="3"
                              class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">{{ old('observations', $vetAdmission->observations) }}</textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('vet.admissions.show', $vetAdmission) }}"
                       class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-6 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors font-medium">
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function vetEditForm() {
            return {
                customerId: '{{ old("customer_id", $vetAdmission->customer_id) }}',
                veterinarianId: '{{ old("veterinarian_id", $vetAdmission->veterinarian_id) }}',
                initialVeterinarianId: '{{ old("veterinarian_id", $vetAdmission->veterinarian_id) }}',
                veterinarians: @json($veterinarians),

                init() {
                    // veterinarians pre-cargados desde el controlador; si cambia customer, recarga
                },

                onCustomerChange() {
                    this.loadVeterinarians();
                },

                async loadVeterinarians() {
                    this.veterinarians = [];
                    this.veterinarianId = '';
                    this.initialVeterinarianId = '';
                    if (!this.customerId) return;
                    try {
                        const res = await fetch(`{{ url('vet/customer') }}/${this.customerId}/veterinarians`);
                        this.veterinarians = await res.json();
                    } catch (e) { console.error(e); }
                },
            }
        }
    </script>
</x-lab-layout>
