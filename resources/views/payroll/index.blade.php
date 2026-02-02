<x-admin-layout title="Liquidación de Sueldos">
    <div class="max-w-6xl mx-auto p-6">
        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Liquidación de Sueldos</h1>
                <p class="text-sm text-gray-600 mt-1">Calcular liquidación individual</p>
            </div>
            <div class="mt-3 sm:mt-0 flex gap-2">
                <a href="{{ route('payroll.sac') }}" 
                   class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 inline-flex items-center gap-2">
                    💰 Liquidar SAC
                </a>
                <a href="{{ route('payroll.closed') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center gap-2">
                    📋 Ver Liquidaciones Cerradas
                </a>
            </div>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mes / Período</label>
                    <select name="month" id="month-select" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $filters['month'] == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->locale('es')->translatedFormat('F') }}
                            </option>
                        @endforeach
                        <option disabled class="bg-gray-100">──────────────</option>
                        <option value="sac-1" class="font-semibold text-amber-600">💰 SAC Junio (1er Semestre)</option>
                        <option value="sac-2" class="font-semibold text-amber-600">💰 SAC Diciembre (2do Semestre)</option>
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

            {{-- Historial de Períodos Anteriores --}}
            @if(count($previousPayrolls) > 0)
                <div class="bg-white rounded-xl shadow-sm mt-6 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            📊 Historial de Netos - Períodos Anteriores
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Últimos períodos liquidados para {{ $selectedEmployee->name }} {{ $selectedEmployee->lastName }}</p>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            @foreach($previousPayrolls as $prev)
                                <div class="bg-gray-50 rounded-lg p-3 border {{ $prev['status'] === 'pagado' ? 'border-green-200' : ($prev['status'] === 'liquidado' ? 'border-blue-200' : 'border-gray-200') }}">
                                    <div class="text-xs text-gray-500 mb-1 capitalize">{{ $prev['period_label'] }}</div>
                                    <div class="text-lg font-bold {{ $prev['status'] === 'pagado' ? 'text-green-600' : ($prev['status'] === 'liquidado' ? 'text-blue-600' : 'text-gray-700') }}">
                                        ${{ number_format($prev['neto_a_cobrar'], 2, ',', '.') }}
                                    </div>
                                    <div class="text-xs mt-1">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                            {{ $prev['status'] === 'pagado' ? 'bg-green-100 text-green-700' : 
                                               ($prev['status'] === 'liquidado' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                                            {{ $prev['status_label'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Resumen del historial --}}
                        <div class="mt-4 pt-4 border-t flex flex-wrap gap-4 text-sm">
                            <div class="bg-blue-50 rounded-lg px-4 py-2">
                                <span class="text-blue-600 font-medium">Promedio:</span>
                                <span class="text-blue-800 font-bold ml-1">
                                    ${{ number_format(collect($previousPayrolls)->avg('neto_a_cobrar'), 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="bg-green-50 rounded-lg px-4 py-2">
                                <span class="text-green-600 font-medium">Máximo:</span>
                                <span class="text-green-800 font-bold ml-1">
                                    ${{ number_format(collect($previousPayrolls)->max('neto_a_cobrar'), 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="bg-amber-50 rounded-lg px-4 py-2">
                                <span class="text-amber-600 font-medium">Mínimo:</span>
                                <span class="text-amber-800 font-bold ml-1">
                                    ${{ number_format(collect($previousPayrolls)->min('neto_a_cobrar'), 2, ',', '.') }}
                                </span>
                            </div>
                            @php
                                $currentNeto = $payroll['neto_a_cobrar'];
                                $avgNeto = collect($previousPayrolls)->avg('neto_a_cobrar');
                                $diff = $avgNeto > 0 ? (($currentNeto - $avgNeto) / $avgNeto) * 100 : 0;
                            @endphp
                            <div class="bg-purple-50 rounded-lg px-4 py-2">
                                <span class="text-purple-600 font-medium">vs Promedio:</span>
                                <span class="font-bold ml-1 {{ $diff >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 1, ',', '.') }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @else
            {{-- Estado vacío --}}
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <div class="text-6xl mb-4">📋</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Selecciona un empleado</h3>
                <p class="text-gray-500">Elige un empleado y el período para calcular su liquidación de sueldo.</p>
            </div>
        @endif
    </div>

    <script>
        document.getElementById('month-select').addEventListener('change', function() {
            const value = this.value;
            if (value === 'sac-1' || value === 'sac-2') {
                const semester = value === 'sac-1' ? 1 : 2;
                const year = document.querySelector('select[name="year"]').value;
                const employeeId = document.querySelector('select[name="employee_id"]').value;
                
                let url = '{{ route("payroll.sac") }}?year=' + year + '&semester=' + semester;
                if (employeeId) {
                    url += '&employee_id=' + employeeId;
                }
                
                window.location.href = url;
            }
        });
    </script>
</x-admin-layout>

