<x-admin-layout title="Liquidación de SAC">
    <div class="max-w-5xl mx-auto p-6">
        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Liquidación de SAC</h1>
                <p class="text-sm text-gray-600 mt-1">Sueldo Anual Complementario (Medio Aguinaldo)</p>
            </div>
            <div class="mt-3 sm:mt-0 flex gap-2">
                <a href="{{ route('payroll.index') }}" 
                   class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 inline-flex items-center gap-2">
                    ← Liquidación Mensual
                </a>
                <a href="{{ route('payroll.closed') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center gap-2">
                    📋 Ver Liquidaciones
                </a>
            </div>
        </div>

        {{-- Mensajes --}}
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        {{-- Info del SAC --}}
        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-200 rounded-xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <span class="text-2xl">💰</span>
                <div class="text-sm text-amber-800">
                    <p class="font-semibold">Ley 20.744, Art. 122 - Sueldo Anual Complementario</p>
                    <p class="mt-1">El SAC equivale al <strong>50% de la mejor remuneración mensual</strong> percibida en cada semestre.</p>
                    <p class="mt-1">
                        <strong>1er Semestre:</strong> Enero a Junio (pago hasta 30/06) | 
                        <strong>2do Semestre:</strong> Julio a Diciembre (pago hasta 18/12)
                    </p>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <form action="{{ route('payroll.sac') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Empleado</label>
                    <select name="employee_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Seleccionar empleado...</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ $filters['employee_id'] == $employee->id ? 'selected' : '' }}>
                                {{ $employee->lastName }}, {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                    <select name="year" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ $filters['year'] == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Período SAC</label>
                    <select name="semester" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1" {{ $filters['semester'] == 1 ? 'selected' : '' }}>SAC Junio (1er Semestre)</option>
                        <option value="2" {{ $filters['semester'] == 2 ? 'selected' : '' }}>SAC Diciembre (2do Semestre)</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                        Calcular SAC
                    </button>
                </div>
            </form>
        </div>

        {{-- Resultado del SAC --}}
        @if($sacPayroll)
            @if(isset($sacPayroll['error']))
                <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                    <span class="text-4xl">⚠️</span>
                    <p class="mt-2 text-red-700 font-medium">{{ $sacPayroll['error'] }}</p>
                </div>
            @elseif($sacPayroll['sac_bruto'] > 0)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-amber-500 to-yellow-600 text-white p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-xl font-bold">{{ $sacPayroll['empleado']['nombre'] }}</h2>
                                <p class="text-amber-100">{{ $sacPayroll['periodo']['label'] }}</p>
                            </div>
                            <div class="text-right text-sm">
                                <p class="text-amber-100">{{ $sacPayroll['empleado']['categoria'] }}</p>
                                <p class="font-semibold">
                                    @if($sacPayroll['es_proporcional'])
                                        Proporcional: {{ $sacPayroll['meses_trabajados'] }} meses
                                    @else
                                        Semestre completo
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Datos del cálculo --}}
                    <div class="grid grid-cols-4 gap-4 p-4 bg-gray-50 border-b">
                        <div>
                            <p class="text-xs text-gray-500">CUIL:</p>
                            <p class="font-semibold">{{ $sacPayroll['empleado']['cuil'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Mejor Sueldo del Semestre:</p>
                            <p class="font-semibold text-blue-600">${{ number_format($sacPayroll['mejor_sueldo'], 2, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Meses Trabajados:</p>
                            <p class="font-semibold">{{ $sacPayroll['meses_trabajados'] }} de 6</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Fecha Ingreso:</p>
                            <p class="font-semibold">{{ $sacPayroll['empleado']['fecha_ingreso'] ? \Carbon\Carbon::parse($sacPayroll['empleado']['fecha_ingreso'])->format('d/m/Y') : '-' }}</p>
                        </div>
                    </div>

                    {{-- Haberes y Deducciones --}}
                    <div class="grid grid-cols-2 gap-0 divide-x">
                        {{-- Haberes (SAC) --}}
                        <div class="p-4">
                            <h3 class="text-lg font-bold text-green-600 mb-3 flex items-center gap-2">
                                <span class="text-xl">+</span> HABERES
                            </h3>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-gray-500 text-xs">
                                        <th class="text-left pb-2">Concepto</th>
                                        <th class="text-right pb-2">Importe</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($sacPayroll['haberes'] as $haber)
                                        <tr>
                                            <td class="py-2">{{ $haber['nombre'] }}</td>
                                            <td class="text-right font-medium text-green-600">
                                                ${{ number_format($haber['importe'], 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t-2 border-gray-200">
                                    <tr class="font-bold">
                                        <td class="pt-3 text-green-700">TOTAL SAC BRUTO</td>
                                        <td class="pt-3 text-right text-green-700">
                                            ${{ number_format($sacPayroll['sac_bruto'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            {{-- Fórmula usada --}}
                            <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
                                <p class="font-medium">Fórmula aplicada:</p>
                                @if($sacPayroll['es_proporcional'])
                                    <p class="mt-1">SAC = (Mejor sueldo × Meses) / 12</p>
                                    <p>SAC = (${{ number_format($sacPayroll['mejor_sueldo'], 2, ',', '.') }} × {{ $sacPayroll['meses_trabajados'] }}) / 12</p>
                                @else
                                    <p class="mt-1">SAC = Mejor sueldo / 2</p>
                                    <p>SAC = ${{ number_format($sacPayroll['mejor_sueldo'], 2, ',', '.') }} / 2</p>
                                @endif
                            </div>
                        </div>

                        {{-- Deducciones --}}
                        <div class="p-4">
                            <h3 class="text-lg font-bold text-red-600 mb-3 flex items-center gap-2">
                                <span class="text-xl">−</span> DEDUCCIONES
                            </h3>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-gray-500 text-xs">
                                        <th class="text-left pb-2">Concepto</th>
                                        <th class="text-center pb-2">%</th>
                                        <th class="text-right pb-2">Importe</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($sacPayroll['deducciones'] as $deduccion)
                                        <tr>
                                            <td class="py-2">{{ $deduccion['nombre'] }}</td>
                                            <td class="text-center text-gray-500">{{ $deduccion['porcentaje'] ?? '' }}</td>
                                            <td class="text-right font-medium text-red-600">
                                                ${{ number_format($deduccion['importe'], 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t-2 border-gray-200">
                                    <tr class="font-bold">
                                        <td class="pt-3 text-red-700">TOTAL DEDUCCIONES</td>
                                        <td></td>
                                        <td class="pt-3 text-right text-red-700">
                                            ${{ number_format($sacPayroll['total_deducciones'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Neto a cobrar --}}
                    <div class="bg-gradient-to-r from-amber-600 to-yellow-700 text-white p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-amber-200 text-sm">NETO A COBRAR</p>
                                <p class="text-4xl font-bold">${{ number_format($sacPayroll['neto_a_cobrar'], 2, ',', '.') }}</p>
                            </div>
                            <div class="text-right text-sm">
                                <p>SAC Bruto: ${{ number_format($sacPayroll['sac_bruto'], 2, ',', '.') }}</p>
                                <p>Deducciones: -${{ number_format($sacPayroll['total_deducciones'], 2, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Acciones --}}
                    <div class="p-4 bg-gray-50 flex justify-end gap-3">
                        <form action="{{ route('payroll.storeSAC') }}" method="POST">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
                            <input type="hidden" name="year" value="{{ $filters['year'] }}">
                            <input type="hidden" name="semester" value="{{ $filters['semester'] }}">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 inline-flex items-center gap-2">
                                💾 Guardar Recibo de SAC
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
                    <span class="text-4xl">⚠️</span>
                    <p class="mt-2 text-yellow-700 font-medium">No se pudo calcular el SAC. Verifique que existan liquidaciones guardadas del semestre.</p>
                </div>
            @endif
        @else
            {{-- Estado vacío --}}
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <div class="text-6xl mb-4">💰</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Selecciona un empleado</h3>
                <p class="text-gray-500">Elige un empleado, año y semestre para calcular el SAC (Medio Aguinaldo).</p>
            </div>
        @endif
    </div>
</x-admin-layout>

