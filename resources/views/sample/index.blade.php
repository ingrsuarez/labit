<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Protocolos de Muestras</h1>
                <p class="text-gray-600 mt-1">Gestión de muestras de agua y alimentos</p>
            </div>
            <a href="{{ route('sample.create') }}" 
               class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Muestra
            </a>
        </div>

        <!-- Alertas -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Buscar por protocolo o cliente..."
                           class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                </div>
                <div>
                    <select name="sample_type" class="rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Todos los tipos</option>
                        <option value="agua" {{ request('sample_type') == 'agua' ? 'selected' : '' }}>Agua</option>
                        <option value="alimento" {{ request('sample_type') == 'alimento' ? 'selected' : '' }}>Alimento</option>
                    </select>
                </div>
                <div>
                    <select name="status" class="rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Todos los estados</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>En Proceso</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completado</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'sample_type', 'status']))
                    <a href="{{ route('sample.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <!-- Tabla de Protocolos -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Protocolo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Lugar
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fecha Ingreso
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Determinaciones
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($samples as $sample)
                        @php
                            $validatedCount = $sample->determinations->where('is_validated', true)->count();
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('sample.show', $sample) }}" class="text-teal-600 hover:text-teal-800 font-medium">
                                    {{ $sample->protocol_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $sample->sample_type == 'agua' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                                    {{ ucfirst($sample->sample_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $sample->location ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $sample->customer->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $sample->entry_date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span>{{ $sample->determinations->count() }}</span>
                                @if($validatedCount > 0)
                                    <span class="ml-1 text-green-600" title="{{ $validatedCount }} validadas">
                                        ({{ $validatedCount }} ✓)
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $calcStatus = $sample->calculated_status;
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @switch($calcStatus)
                                        @case('validated') bg-green-100 text-green-800 @break
                                        @case('completed') bg-blue-100 text-blue-800 @break
                                        @case('incomplete') bg-yellow-100 text-yellow-800 @break
                                        @case('pending') bg-gray-100 text-gray-800 @break
                                        @default bg-gray-100 text-gray-800 @break
                                    @endswitch">
                                    {{ $sample->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('sample.show', $sample) }}" class="text-teal-600 hover:text-teal-900">
                                    Ver
                                </a>
                                <a href="{{ route('sample.edit', $sample) }}" class="text-indigo-600 hover:text-indigo-900">
                                    Editar
                                </a>
                                @if($validatedCount > 0)
                                    <a href="{{ route('sample.pdf.view', $sample) }}" target="_blank" 
                                       class="text-green-600 hover:text-green-900" title="Imprimir informe ({{ $validatedCount }} validadas)">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        PDF
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p class="mt-2">No hay protocolos registrados</p>
                                <a href="{{ route('sample.create') }}" class="mt-2 inline-block text-teal-600 hover:text-teal-800">
                                    Crear el primer protocolo
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($samples->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $samples->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-lab-layout>
