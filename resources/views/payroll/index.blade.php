<x-manage>
    <div class="max-w-6xl mx-auto p-6">
        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Liquidación de Sueldos</h1>
                <p class="text-sm text-gray-600 mt-1">CCT 108/75 (FATSA–CADIME/CEDIM) - Convenio de Sanidad</p>
            </div>
            <a href="{{ route('payroll.bulk', ['year' => $filters['year'], 'month' => $filters['month']]) }}" 
               class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Liquidación Masiva
            </a>
        </div>

        {{-- Filtros --}}
        <form method="GET" class="bg-white p-4 rounded-xl shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Empleado</label>
                    <select name="employee_id" required
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione un empleado...</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" {{ ($filters['employee_id'] ?? '') == $e->id ? 'selected' : '' }}>
                                {{ ucfirst($e->lastName) }}, {{ ucfirst($e->name) }} — {{ $e->employeeId }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Año</label>
                    <input type="number" name="year" value="{{ $filters['year'] }}" min="2020" max="2100"
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mes</label>
                    <select name="month"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $filters['month'] == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromDate(2000, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Calcular Recibo
                    </button>
                </div>
            </div>
        </form>

        @if($payroll)
            {{-- Recibo de Sueldo --}}
            <div class="bg-white rounded-xl shadow-lg overflow-hidden" id="recibo">
                {{-- Encabezado del recibo --}}
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-xl font-bold">RECIBO DE SUELDO</h2>
                            <p class="text-blue-200 text-sm">{{ $payroll['periodo']['label'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-blue-200">Convenio</p>
                            <p class="font-semibold">{{ $payroll['empleado']['convenio'] ?? 'CCT 108/75' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Datos del empleado --}}
                <div class="border-b border-gray-200 p-4 bg-gray-50">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Empleado:</span>
                            <p class="font-semibold text-gray-900 capitalize">{{ $payroll['empleado']['nombre'] }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">CUIL:</span>
                            <p class="font-semibold text-gray-900">{{ $payroll['empleado']['cuil'] }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Categoría:</span>
                            <p class="font-semibold text-gray-900 capitalize">{{ $payroll['empleado']['categoria'] }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Antigüedad:</span>
                            <p class="font-semibold text-gray-900">{{ $payroll['empleado']['antiguedad_anos'] }} años</p>
                        </div>
                    </div>
                </div>

                {{-- Novedades del período --}}
                @if(array_sum($payroll['novedades']) > 0)
                    <div class="border-b border-gray-200 p-4 bg-amber-50">
                        <h3 class="text-sm font-semibold text-amber-800 mb-2">Novedades del Período:</h3>
                        <div class="flex flex-wrap gap-4 text-sm">
                            @if($payroll['novedades']['dias_vacaciones'] > 0)
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded">
                                    Vacaciones: {{ $payroll['novedades']['dias_vacaciones'] }} días
                                </span>
                            @endif
                            @if($payroll['novedades']['dias_enfermedad'] > 0)
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded">
                                    Enfermedad: {{ $payroll['novedades']['dias_enfermedad'] }} días
                                </span>
                            @endif
                            @if($payroll['novedades']['horas_50'] > 0)
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">
                                    Hs. Extra 50%: {{ $payroll['novedades']['horas_50'] }} hs
                                </span>
                            @endif
                            @if($payroll['novedades']['horas_100'] > 0)
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded">
                                    Hs. Extra 100%: {{ $payroll['novedades']['horas_100'] }} hs
                                </span>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-200">
                    {{-- HABERES --}}
                    <div class="p-4">
                        <h3 class="text-lg font-bold text-green-700 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            HABERES
                        </h3>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-500 border-b">
                                    <th class="text-left py-2">Concepto</th>
                                    <th class="text-right py-2">%</th>
                                    <th class="text-right py-2">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payroll['haberes'] as $haber)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2 capitalize">
                                            {{ $haber['nombre'] }}
                                            @if(isset($haber['remunerativo']) && !$haber['remunerativo'])
                                                <span class="text-xs text-amber-600">(No Rem.)</span>
                                            @endif
                                        </td>
                                        <td class="text-right py-2 text-gray-500">{{ $haber['porcentaje'] ?? '' }}</td>
                                        <td class="text-right py-2 font-medium text-green-600">
                                            ${{ number_format($haber['importe'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-green-300 bg-green-50">
                                    <td colspan="2" class="py-3 font-bold text-green-800">TOTAL HABERES</td>
                                    <td class="text-right py-3 font-bold text-green-800 text-lg">
                                        ${{ number_format($payroll['subtotal'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- DEDUCCIONES --}}
                    <div class="p-4">
                        <h3 class="text-lg font-bold text-red-700 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                            DEDUCCIONES
                        </h3>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-500 border-b">
                                    <th class="text-left py-2">Concepto</th>
                                    <th class="text-right py-2">%</th>
                                    <th class="text-right py-2">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payroll['deducciones'] as $deduccion)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2 capitalize">{{ $deduccion['nombre'] }}</td>
                                        <td class="text-right py-2 text-gray-500">{{ $deduccion['porcentaje'] ?? '' }}</td>
                                        <td class="text-right py-2 font-medium text-red-600">
                                            ${{ number_format($deduccion['importe'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-red-300 bg-red-50">
                                    <td colspan="2" class="py-3 font-bold text-red-800">TOTAL DEDUCCIONES</td>
                                    <td class="text-right py-3 font-bold text-red-800 text-lg">
                                        ${{ number_format($payroll['total_deducciones'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- NETO A COBRAR --}}
                <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-blue-200 text-sm">NETO A COBRAR</p>
                            <p class="text-3xl font-bold">${{ number_format($payroll['neto_a_cobrar'], 2, ',', '.') }}</p>
                        </div>
                        <div class="text-right text-sm text-blue-200">
                            <p>Bruto: ${{ number_format($payroll['subtotal'], 2, ',', '.') }}</p>
                            <p>Deducciones: -${{ number_format($payroll['total_deducciones'], 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Footer del recibo --}}
                <div class="p-4 bg-gray-50 border-t text-center text-xs text-gray-500">
                    <p>Generado el {{ now()->format('d/m/Y H:i') }} | Sistema de Gestión de RRHH</p>
                </div>
            </div>

            {{-- Botón de imprimir --}}
            <div class="mt-4 flex justify-end gap-2">
                <button onclick="window.print()" 
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-600 text-white hover:bg-gray-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir
                </button>
            </div>
        @else
            {{-- Estado vacío --}}
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Seleccione un empleado</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Elija un empleado y el período para calcular su recibo de sueldo.
                </p>
            </div>
        @endif
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            #recibo, #recibo * { visibility: visible; }
            #recibo { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</x-manage>

