<x-manage>
    <div class="max-w-6xl mx-auto p-6">
        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Liquidación de Sueldos</h1>
                <p class="text-sm text-gray-600 mt-1">Calcular y generar recibos de sueldo</p>
            </div>
            <a href="{{ route('payroll.closed') }}" class="mt-3 sm:mt-0 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                📋 Ver Liquidaciones Cerradas
            </a>
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

        {{-- Filtros --}}
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <form method="GET" action="{{ route('payroll.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Empleado</label>
                    <select name="employee_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Seleccionar empleado...</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ ($filters['employee_id'] ?? '') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->lastName }}, {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                    <select name="year" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ ($filters['year'] ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                    <select name="month" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ ($filters['month'] ?? now()->month) == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        🔍 Calcular
                    </button>
                </div>
            </form>
        </div>

        {{-- Resultado de la liquidación --}}
        @if($selectedEmployee && $payroll)
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                {{-- Header con Logo --}}
                <div class="p-4 border-b flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        @if(file_exists(public_path('images/logo_ipac.png')))
                            <img src="{{ asset('images/logo_ipac.png') }}" alt="Logo IPAC" class="h-12">
                        @else
                            <x-application-mark class="h-12 w-auto" />
                        @endif
                        <div>
                            <p class="font-bold text-gray-800">{{ config('app.name', 'Labit') }}</p>
                            <p class="text-xs text-gray-500">Administración</p>
                        </div>
                    </div>
                    <div class="text-right text-sm text-gray-500">
                        <p>Vista previa</p>
                        <p>{{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                
                {{-- Título --}}
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-xl font-bold">RECIBO DE SUELDO</h2>
                            <p class="text-blue-200">{{ $payroll['periodo']['label'] }}</p>
                        </div>
                        <div class="text-right text-sm">
                            <p class="text-blue-200">Convenio</p>
                            <p class="font-semibold">{{ $payroll['empleado']['convenio'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Datos del empleado --}}
                <div class="grid grid-cols-4 gap-4 p-4 bg-gray-50 border-b">
                    <div>
                        <p class="text-xs text-gray-500">Empleado:</p>
                        <p class="font-semibold">{{ $payroll['empleado']['nombre'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">CUIL:</p>
                        <p class="font-semibold">{{ $payroll['empleado']['cuil'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Categoría:</p>
                        <p class="font-semibold">{{ $payroll['empleado']['categoria'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Antigüedad:</p>
                        <p class="font-semibold">{{ $payroll['empleado']['antiguedad_anos'] }} años</p>
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
                                    <tr class="{{ ($haber['remunerativo'] ?? true) ? '' : 'text-purple-600' }}">
                                        <td class="py-2">
                                            {{ $haber['nombre'] }}
                                            @if(!($haber['remunerativo'] ?? true))
                                                <span class="text-xs">(No Rem.)</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-gray-500">{{ $haber['porcentaje'] ?? '' }}</td>
                                        <td class="text-right font-medium {{ $haber['importe'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
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
                                        ${{ number_format($payroll['total_haberes'], 2, ',', '.') }}
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
                            <p>Bruto Remunerativo: ${{ number_format($payroll['total_remunerativo'], 2, ',', '.') }}</p>
                            @if($payroll['total_no_remunerativo'] > 0)
                                <p>No Remunerativo: ${{ number_format($payroll['total_no_remunerativo'], 2, ',', '.') }}</p>
                            @endif
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
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            💾 Guardar Liquidación
                        </button>
                    </form>
                </div>
            </div>
        @elseif(!$selectedEmployee && request()->has('employee_id'))
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <p class="text-yellow-700">No se encontró el empleado seleccionado.</p>
            </div>
        @else
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-12 text-center">
                <div class="text-gray-400 text-6xl mb-4">📋</div>
                <p class="text-gray-600 text-lg">Seleccione un empleado y período para calcular la liquidación.</p>
            </div>
        @endif
    </div>
</x-manage>
