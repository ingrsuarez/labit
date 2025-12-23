<x-admin-layout title="Liquidación de Sueldos">
    <div class="max-w-6xl mx-auto p-6">
        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Liquidación de Sueldos</h1>
                <p class="text-sm text-gray-600 mt-1">Calcular liquidación individual</p>
            </div>
            <a href="{{ route('payroll.closed') }}" 
               class="mt-3 sm:mt-0 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center gap-2">
                📋 Ver Liquidaciones Cerradas
            </a>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <form action="{{ route('payroll.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                    <select name="month" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $filters['month'] == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->locale('es')->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Calcular Liquidación
                    </button>
                </div>
            </form>
        </div>

        {{-- Resultado de la liquidación --}}
        @if($payroll)
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                {{-- Header con datos del empleado --}}
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-xl font-bold">{{ $payroll['empleado']['nombre'] }}</h2>
                            <p class="text-blue-200">{{ $payroll['periodo']['label'] }}</p>
                        </div>
                        <div class="text-right text-sm">
                            <p class="text-blue-200">{{ $payroll['empleado']['categoria'] }}</p>
                            <p class="font-semibold">Antigüedad: {{ $payroll['empleado']['antiguedad_anos'] }} años</p>
                        </div>
                    </div>
                </div>

                {{-- Datos del empleado --}}
                <div class="grid grid-cols-4 gap-4 p-4 bg-gray-50 border-b">
                    <div>
                        <p class="text-xs text-gray-500">CUIL:</p>
                        <p class="font-semibold">{{ $payroll['empleado']['cuil'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Convenio:</p>
                        <p class="font-semibold">{{ $payroll['empleado']['convenio'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Fecha Ingreso:</p>
                        <p class="font-semibold">{{ $payroll['empleado']['fecha_ingreso'] ? \Carbon\Carbon::parse($payroll['empleado']['fecha_ingreso'])->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Salario Básico:</p>
                        <p class="font-semibold">${{ number_format($payroll['salario_basico'], 2, ',', '.') }}</p>
                    </div>
                </div>

                {{-- Haberes y Deducciones --}}
                <div class="grid grid-cols-2 gap-0 divide-x">
                    {{-- Haberes --}}
                    <div class="p-4">
                        <h3 class="text-lg font-bold text-green-600 mb-3 flex items-center gap-2">
                            <span class="text-xl">+</span> HABERES
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
                                @foreach($payroll['haberes'] as $haber)
                                    <tr class="{{ !($haber['remunerativo'] ?? true) ? 'text-purple-600' : '' }}">
                                        <td class="py-2">
                                            {{ $haber['nombre'] }}
                                            @if(!($haber['remunerativo'] ?? true))
                                                <span class="text-xs">(No Rem.)</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-gray-500">{{ $haber['porcentaje'] ?? '' }}</td>
                                        <td class="text-right font-medium text-green-600">
                                            ${{ number_format($haber['importe'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-gray-200">
                                <tr class="font-bold">
                                    <td class="pt-3 text-green-700">TOTAL HABERES</td>
                                    <td></td>
                                    <td class="pt-3 text-right text-green-700">
                                        ${{ number_format($payroll['subtotal'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
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
                                @foreach($payroll['deducciones'] as $deduccion)
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
                                        ${{ number_format($payroll['total_deducciones'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Neto a cobrar --}}
                <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-blue-200 text-sm">NETO A COBRAR</p>
                            <p class="text-4xl font-bold">${{ number_format($payroll['neto_a_cobrar'], 2, ',', '.') }}</p>
                        </div>
                        <div class="text-right text-sm">
                            <p>Remunerativo: ${{ number_format($payroll['total_remunerativo'], 2, ',', '.') }}</p>
                            <p>No Remunerativo: ${{ number_format($payroll['total_no_remunerativo'], 2, ',', '.') }}</p>
                            <p>Deducciones: -${{ number_format($payroll['total_deducciones'], 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="p-4 bg-gray-50 flex justify-end gap-3">
                    <form action="{{ route('payroll.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
                        <input type="hidden" name="year" value="{{ $filters['year'] }}">
                        <input type="hidden" name="month" value="{{ $filters['month'] }}">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 inline-flex items-center gap-2">
                            💾 Guardar Liquidación
                        </button>
                    </form>
                </div>
            </div>
        @else
            {{-- Estado vacío --}}
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <div class="text-6xl mb-4">📋</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Selecciona un empleado</h3>
                <p class="text-gray-500">Elige un empleado y el período para calcular su liquidación de sueldo.</p>
            </div>
        @endif
    </div>
</x-admin-layout>

