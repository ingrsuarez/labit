<x-admin-layout title="Detalle de Liquidación">
    <div class="max-w-4xl mx-auto p-6">
        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Detalle de Liquidación</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $payroll->period_label }}</p>
            </div>
            <a href="{{ route('payroll.closed', ['year' => $payroll->year, 'month' => $payroll->month]) }}" 
               class="mt-3 sm:mt-0 text-blue-600 hover:text-blue-800">
                ← Volver a Liquidaciones
            </a>
        </div>

        {{-- Estado --}}
        <div class="mb-4 flex items-center gap-4">
            @if($payroll->status === 'borrador')
                <span class="px-3 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800">
                    📝 Borrador
                </span>
            @elseif($payroll->status === 'liquidado')
                <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                    🔒 Liquidado el {{ $payroll->liquidated_at?->format('d/m/Y H:i') }}
                </span>
            @else
                <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800">
                    ✅ Pagado el {{ $payroll->paid_at?->format('d/m/Y H:i') }}
                </span>
            @endif
        </div>

        {{-- Recibo --}}
        <div class="bg-white rounded-xl shadow-lg overflow-hidden" id="recibo-content">
            {{-- Header con Logo --}}
            <div class="p-4 border-b flex justify-between items-center">
                <div class="flex items-center gap-4">
                    @if(file_exists(public_path('images/logo.png')))
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-12">
                    @else
                        <x-application-mark class="h-12 w-auto" />
                    @endif
                    <div>
                        <p class="font-bold text-gray-800">{{ config('app.name', 'Labit') }}</p>
                        <p class="text-xs text-gray-500">Administración</p>
                    </div>
                </div>
                <div class="text-right text-sm text-gray-500">
                    <p>{{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </div>
            
            {{-- Título --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-xl font-bold">RECIBO DE SUELDO</h2>
                        <p class="text-blue-200">{{ $payroll->period_label }}</p>
                    </div>
                    <div class="text-right text-sm">
                        <p class="text-blue-200">Convenio</p>
                        <p class="font-semibold">CCT 108/75 (FATSA-CADIME/CEDIM)</p>
                    </div>
                </div>
            </div>

            {{-- Datos del empleado --}}
            <div class="grid grid-cols-4 gap-4 p-4 bg-gray-50 border-b">
                <div>
                    <p class="text-xs text-gray-500">Empleado:</p>
                    <p class="font-semibold">{{ $payroll->employee_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">CUIL:</p>
                    <p class="font-semibold">{{ $payroll->employee_cuil }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Categoría:</p>
                    <p class="font-semibold">{{ $payroll->category_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Antigüedad:</p>
                    <p class="font-semibold">{{ $payroll->antiguedad_years }} años</p>
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
                            @foreach($payroll->haberes as $haber)
                                <tr class="{{ !$haber->is_remunerative ? 'text-purple-600' : '' }}">
                                    <td class="py-2">
                                        {{ $haber->name }}
                                        @if(!$haber->is_remunerative)
                                            <span class="text-xs">(No Rem.)</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-gray-500">{{ $haber->percentage }}</td>
                                    <td class="text-right font-medium text-green-600">
                                        ${{ number_format($haber->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-gray-200">
                            <tr class="font-bold">
                                <td class="pt-3 text-green-700">TOTAL HABERES</td>
                                <td></td>
                                <td class="pt-3 text-right text-green-700">
                                    ${{ number_format($payroll->total_haberes, 2, ',', '.') }}
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
                            @foreach($payroll->deducciones as $deduccion)
                                <tr>
                                    <td class="py-2">{{ $deduccion->name }}</td>
                                    <td class="text-center text-gray-500">{{ $deduccion->percentage }}</td>
                                    <td class="text-right font-medium text-red-600">
                                        ${{ number_format($deduccion->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-gray-200">
                            <tr class="font-bold">
                                <td class="pt-3 text-red-700">TOTAL DEDUCCIONES</td>
                                <td></td>
                                <td class="pt-3 text-right text-red-700">
                                    ${{ number_format($payroll->total_deducciones, 2, ',', '.') }}
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
                        <p class="text-4xl font-bold">${{ number_format($payroll->neto_a_cobrar, 2, ',', '.') }}</p>
                    </div>
                    <div class="text-right text-sm">
                        <p>Bruto: ${{ number_format($payroll->total_haberes, 2, ',', '.') }}</p>
                        <p>Deducciones: -${{ number_format($payroll->total_deducciones, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="p-4 bg-gray-50 text-center text-xs text-gray-500">
                <p>Liquidación guardada el {{ $payroll->created_at->format('d/m/Y H:i') }}</p>
                @if($payroll->approvedBy)
                    <p>Aprobado por: {{ $payroll->approvedBy->name }}</p>
                @endif
            </div>
        </div>

        {{-- Acciones --}}
        <div class="mt-6 flex justify-end gap-3 print:hidden">
            @if($payroll->status === 'borrador')
                <form action="{{ route('payroll.liquidar', $payroll) }}" method="POST"
                      onsubmit="return confirm('¿Cerrar esta liquidación?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                        🔒 Cerrar Liquidación
                    </button>
                </form>
                <form action="{{ route('payroll.destroy', $payroll) }}" method="POST"
                      onsubmit="return confirm('¿Eliminar esta liquidación?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        🗑️ Eliminar
                    </button>
                </form>
            @elseif($payroll->status === 'liquidado')
                <form action="{{ route('payroll.pagar', $payroll) }}" method="POST"
                      onsubmit="return confirm('¿Marcar como pagada?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        ✅ Marcar como Pagada
                    </button>
                </form>
                <form action="{{ route('payroll.reabrir', $payroll) }}" method="POST"
                      onsubmit="return confirm('¿Reabrir esta liquidación?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        🔓 Reabrir
                    </button>
                </form>
            @endif
            
            <a href="{{ route('payroll.pdf', $payroll) }}" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 inline-flex items-center">
                📄 Descargar PDF
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                🖨️ Imprimir
            </button>
        </div>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            #recibo-content, #recibo-content * { visibility: visible; }
            #recibo-content { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</x-admin-layout>
