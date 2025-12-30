<x-manage>
    <div class="max-w-7xl mx-auto p-6">
        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Conceptos de Sueldo</h1>
                <p class="text-sm text-gray-600 mt-1">Gestión de haberes y deducciones para liquidación</p>
            </div>
            <a href="{{ route('salary.create') }}" 
               class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nuevo Concepto
            </a>
        </div>

        {{-- Mensajes flash --}}
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        {{-- Tarjetas resumen --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Total Conceptos</div>
                <div class="text-2xl font-semibold text-gray-900">{{ $summary['total'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Haberes</div>
                <div class="text-2xl font-semibold text-green-600">{{ $summary['haberes'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Deducciones</div>
                <div class="text-2xl font-semibold text-red-600">{{ $summary['deducciones'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-gray-500">Activos</div>
                <div class="text-2xl font-semibold text-blue-600">{{ $summary['activos'] }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Tabla de Haberes --}}
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="bg-green-600 px-4 py-3">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        HABERES
                    </h2>
                </div>
                
                @if($haberes->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        No hay haberes configurados.
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Período</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($haberes as $item)
                                <tr class="hover:bg-gray-50 {{ !$item->is_active ? 'opacity-50' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 capitalize flex items-center gap-2">
                                            {{ $item->name }}
                                            @if($item->requires_assignment)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800" title="Requiere asignación individual">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                                    </svg>
                                                    {{ $item->employees()->wherePivot('is_active', true)->count() }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $item->calculation_type_name }}
                                            @if(!$item->is_remunerative)
                                                <span class="ml-1 text-amber-600">(No Rem.)</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-semibold text-green-600">
                                            @if($item->calculation_type === 'percentage')
                                                {{ number_format($item->value, 2) }}%
                                            @elseif($item->calculation_type === 'hours')
                                                {{ number_format($item->value, 2) }}/hs
                                            @else
                                                ${{ number_format($item->value, 2, ',', '.') }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs {{ $item->applies_all_year ? 'text-gray-500' : 'text-blue-600 font-medium' }}">
                                            {{ $item->period_description }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('salary.toggle', $item) }}" 
                                           class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium cursor-pointer
                                                  {{ $item->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                            {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('salary.edit', $item) }}" 
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Tabla de Deducciones --}}
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="bg-red-600 px-4 py-3">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                        DEDUCCIONES
                    </h2>
                </div>
                
                @if($deducciones->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        No hay deducciones configuradas.
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Período</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($deducciones as $item)
                                <tr class="hover:bg-gray-50 {{ !$item->is_active ? 'opacity-50' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 capitalize flex items-center gap-2">
                                            {{ $item->name }}
                                            @if($item->requires_assignment)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800" title="Requiere asignación individual">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                                    </svg>
                                                    {{ $item->employees()->wherePivot('is_active', true)->count() }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $item->calculation_type_name }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-semibold text-red-600">
                                            @if($item->calculation_type === 'percentage')
                                                {{ number_format($item->value, 2) }}%
                                            @else
                                                ${{ number_format($item->value, 2, ',', '.') }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs {{ $item->applies_all_year ? 'text-gray-500' : 'text-blue-600 font-medium' }}">
                                            {{ $item->period_description }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('salary.toggle', $item) }}" 
                                           class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium cursor-pointer
                                                  {{ $item->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                            {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('salary.edit', $item) }}" 
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Leyenda --}}
        <div class="mt-6 bg-gray-50 rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Tipos de cálculo:</h3>
            <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                <span><strong>Porcentaje:</strong> Se calcula sobre el salario básico</span>
                <span><strong>Monto Fijo:</strong> Valor fijo que se suma/resta</span>
                <span><strong>Por Horas:</strong> Se multiplica por las horas trabajadas</span>
            </div>
        </div>
    </div>
</x-manage>

