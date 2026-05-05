<x-lab-layout title="Pacientes">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pacientes</h1>
                <p class="mt-1 text-sm text-gray-600">Gestione los pacientes del laboratorio</p>
            </div>
            @can('patients.create')
            <a href="{{ route('patient.index') }}"
               class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nuevo Paciente
            </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <form action="{{ route('lab.patients.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Buscar por nombre, apellido o DNI..."
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div class="w-48">
                    <select name="insurance" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Todas las OS</option>
                        @foreach($insurances as $ins)
                            <option value="{{ $ins->id }}" {{ request('insurance') == $ins->id ? 'selected' : '' }}>
                                {{ strtoupper($ins->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'insurance']))
                    <a href="{{ route('lab.patients.index') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <!-- Listado -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($patients->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DNI</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre y Apellido</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Obra Social</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($patients as $patient)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $patient->patientId }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $patient->full_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ strtoupper($patient->insuranceRelation?->name ?? '—') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $patient->phone ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $patient->email ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        @can('patients.edit')
                                        <a href="{{ route('patient.edit', ['current_patient' => $patient->patientId]) }}"
                                           class="text-teal-600 hover:text-teal-800 text-sm font-medium">
                                            Editar
                                        </a>
                                        @endcan
                                        @can('lab-admissions.create')
                                        <a href="{{ route('lab.admissions.create', ['patient_id' => $patient->id]) }}"
                                           class="text-blue-600 hover:text-blue-800 text-sm ml-3"
                                           title="Nueva admisión para este paciente">
                                            + Admisión
                                        </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $patients->links() }}
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No se encontraron pacientes</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(request()->hasAny(['search', 'insurance']))
                            Probá con otros filtros o <a href="{{ route('lab.patients.index') }}" class="text-teal-600 hover:text-teal-800">limpiá la búsqueda</a>.
                        @else
                            Comenzá registrando un nuevo paciente.
                        @endif
                    </p>
                    @can('patients.create')
                    <div class="mt-6">
                        <a href="{{ route('patient.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Nuevo Paciente
                        </a>
                    </div>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</x-lab-layout>
