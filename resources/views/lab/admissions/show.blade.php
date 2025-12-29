<x-lab-layout title="Admisión {{ $admission->protocol_number }}">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('lab.admissions.index') }}" class="hover:text-teal-600">Admisiones</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span>{{ $admission->protocol_number }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Protocolo: {{ $admission->protocol_number }}</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Fecha: {{ $admission->formatted_date }} | 
                        Creado por: {{ $admission->creator?->name ?? 'N/A' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('lab.admissions.edit', $admission) }}" 
                       class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Editar
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Principal -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Datos del Paciente -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Paciente</h2>
                    @if($admission->patient)
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Nombre Completo</p>
                                <p class="font-medium text-gray-900">{{ $admission->patient->full_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">DNI</p>
                                <p class="font-medium text-gray-900">{{ $admission->patient->patientId }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Fecha de Nacimiento</p>
                                <p class="font-medium text-gray-900">
                                    {{ $admission->patient->birth?->format('d/m/Y') ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Teléfono</p>
                                <p class="font-medium text-gray-900">{{ $admission->patient->phone ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500">Paciente no encontrado</p>
                    @endif
                </div>

                <!-- Datos de la Admisión -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos de la Admisión</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Obra Social</p>
                            <p class="font-medium text-gray-900">
                                {{ strtoupper($admission->insuranceRelation?->name ?? 'N/A') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Nro. Afiliado</p>
                            <p class="font-medium text-gray-900">{{ $admission->affiliate_number ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Médico Solicitante</p>
                            <p class="font-medium text-gray-900">{{ $admission->requesting_doctor ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Diagnóstico</p>
                            <p class="font-medium text-gray-900">{{ $admission->diagnosis ?? 'N/A' }}</p>
                        </div>
                    </div>
                    @if($admission->observations)
                        <div class="mt-4">
                            <p class="text-sm text-gray-500">Observaciones</p>
                            <p class="text-gray-900">{{ $admission->observations }}</p>
                        </div>
                    @endif
                </div>

                <!-- Prácticas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-800">
                            Prácticas 
                            <span class="text-sm font-normal text-gray-500">({{ $admission->admissionTests->count() }})</span>
                        </h2>
                    </div>

                    @if($admission->admissionTests->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Práctica</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Paga</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Copago</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($admission->admissionTests as $admissionTest)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $admissionTest->test->code }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ $admissionTest->test->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                                ${{ number_format($admissionTest->price, 2, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                @php
                                                    $statusColors = [
                                                        'authorized' => 'bg-green-100 text-green-800',
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        'rejected' => 'bg-red-100 text-red-800',
                                                        'not_required' => 'bg-gray-100 text-gray-600',
                                                    ];
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$admissionTest->authorization_status] ?? 'bg-gray-100 text-gray-600' }}">
                                                    {{ $admissionTest->authorization_status_label }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                                @if($admissionTest->paid_by_patient)
                                                    <span class="text-orange-600 font-medium">Paciente</span>
                                                @else
                                                    <span class="text-teal-600">Obra Social</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">
                                                @if($admissionTest->copago > 0)
                                                    ${{ number_format($admissionTest->copago, 2, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="px-6 py-12 text-center text-gray-500">
                            No hay prácticas en esta admisión
                        </div>
                    @endif
                </div>
            </div>

            <!-- Columna Lateral - Resumen -->
            <div class="space-y-6">
                <!-- Resumen de Totales -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Resumen</h2>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-gray-600">Total Obra Social</span>
                            <span class="font-semibold text-teal-600 text-lg">
                                ${{ number_format($admission->total_insurance, 2, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-gray-600">Total Paciente</span>
                            <span class="font-medium text-gray-900">
                                ${{ number_format($admission->total_patient, 2, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-gray-600">Total Copagos</span>
                            <span class="font-medium text-gray-900">
                                ${{ number_format($admission->total_copago, 2, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-3 bg-gray-50 -mx-6 px-6 rounded-b-lg">
                            <span class="font-semibold text-gray-800">TOTAL GENERAL</span>
                            <span class="font-bold text-xl text-gray-900">
                                ${{ number_format($admission->total, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Estadísticas</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Prácticas Autorizadas</span>
                                <span class="font-medium">
                                    {{ $admission->admissionTests->whereIn('authorization_status', ['authorized', 'not_required'])->count() }}
                                    / {{ $admission->admissionTests->count() }}
                                </span>
                            </div>
                            @php
                                $total = $admission->admissionTests->count();
                                $authorized = $admission->admissionTests->whereIn('authorization_status', ['authorized', 'not_required'])->count();
                                $percent = $total > 0 ? ($authorized / $total) * 100 : 0;
                            @endphp
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Paga Obra Social</span>
                                <span class="font-medium">
                                    {{ $admission->admissionTests->where('paid_by_patient', false)->count() }}
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Paga Paciente</span>
                                <span class="font-medium">
                                    {{ $admission->admissionTests->where('paid_by_patient', true)->count() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-lab-layout>

