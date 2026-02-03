<x-admin-layout title="Liquidaciones Guardadas">
    <div class="max-w-7xl mx-auto p-6">
        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Liquidaciones Guardadas</h1>
                <p class="text-sm text-gray-600 mt-1">Liquidaciones cerradas y pagadas</p>
            </div>
            <a href="{{ route('payroll.index') }}" 
               class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm">
                ← Volver a Liquidación
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
        <form method="GET" class="bg-white rounded-xl shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                    <select name="year" class="w-full rounded-lg border-gray-300">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                    <select name="month" class="w-full rounded-lg border-gray-300">
                        @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $m)
                            <option value="{{ $i + 1 }}" {{ $month == $i + 1 ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Filtrar
                    </button>
                </div>
                <div class="flex items-end gap-2">
                    {{-- Acciones masivas --}}
                    @if($payrolls->where('status', 'borrador')->count() > 0)
                        <form action="{{ route('payroll.liquidarBulk') }}" method="POST" class="inline"
                              onsubmit="return confirm('¿Cerrar todas las liquidaciones en borrador?')">
                            @csrf
                            <input type="hidden" name="year" value="{{ $year }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <button type="submit" class="px-3 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm">
                                Cerrar Todos
                            </button>
                        </form>
                    @endif
                    @if($payrolls->where('status', 'liquidado')->count() > 0)
                        <form action="{{ route('payroll.pagarBulk') }}" method="POST" class="inline"
                              onsubmit="return confirm('¿Marcar todas como pagadas?')">
                            @csrf
                            <input type="hidden" name="year" value="{{ $year }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                Pagar Todos
                            </button>
                        </form>
                    @endif
                    {{-- Descargar PDFs masivamente --}}
                    @if($payrolls->whereIn('status', ['liquidado', 'pagado'])->count() > 0)
                        <a href="{{ route('payroll.downloadBulkPdf', ['year' => $year, 'month' => $month]) }}" 
                           class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm inline-flex items-center gap-1"
                           title="Descargar todos los recibos cerrados en PDF">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Descargar PDFs
                        </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Resumen --}}
        @if($payrolls->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Liquidaciones</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $payrolls->count() }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Total Bruto</p>
                    <p class="text-2xl font-bold text-blue-600">${{ number_format($totals['total_bruto'], 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Total Deducciones</p>
                    <p class="text-2xl font-bold text-red-600">${{ number_format($totals['total_deducciones'], 2, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-sm text-gray-500">Total Neto</p>
                    <p class="text-2xl font-bold text-green-600">${{ number_format($totals['total_neto'], 2, ',', '.') }}</p>
                </div>
            </div>

            {{-- Tabla de liquidaciones --}}
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Bruto</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Deducciones</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Neto</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($payrolls as $payroll)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-900 text-sm">{{ $payroll->employee_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $payroll->employee_cuil }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $payroll->category_name }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                    ${{ number_format($payroll->total_haberes, 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-right text-sm text-red-600">
                                    -${{ number_format($payroll->total_deducciones, 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-right text-sm font-bold text-green-600">
                                    ${{ number_format($payroll->neto_a_cobrar, 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-center">
                                    @if($payroll->status === 'borrador')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                            Borrador
                                        </span>
                                    @elseif($payroll->status === 'liquidado')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                            Liquidado
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                            Pagado
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-center">
                                    <div class="flex justify-center gap-1">
                                        {{-- Ver detalle --}}
                                        <a href="{{ route('payroll.show', $payroll) }}" 
                                           class="p-1 text-gray-600 hover:text-blue-600" title="Ver detalle">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        @if($payroll->status === 'borrador')
                                            {{-- Liquidar --}}
                                            <form action="{{ route('payroll.liquidar', $payroll) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('¿Cerrar esta liquidación? Los montos quedarán fijos.')">
                                                @csrf
                                                <button type="submit" class="p-1 text-amber-600 hover:text-amber-800" title="Cerrar liquidación">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            {{-- Eliminar --}}
                                            <form action="{{ route('payroll.destroy', $payroll) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('¿Eliminar esta liquidación?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-red-600 hover:text-red-800" title="Eliminar">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @elseif($payroll->status === 'liquidado')
                                            {{-- Pagar --}}
                                            <form action="{{ route('payroll.pagar', $payroll) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('¿Marcar como pagada?')">
                                                @csrf
                                                <button type="submit" class="p-1 text-green-600 hover:text-green-800" title="Marcar como pagado">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            {{-- Reabrir --}}
                                            <form action="{{ route('payroll.reabrir', $payroll) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('¿Reabrir esta liquidación?')">
                                                @csrf
                                                <button type="submit" class="p-1 text-gray-600 hover:text-gray-800" title="Reabrir">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            {{-- Pagado - solo ver --}}
                                            <span class="p-1 text-green-600" title="Pagado el {{ $payroll->paid_at?->format('d/m/Y') }}">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No hay liquidaciones guardadas</h3>
                <p class="mt-2 text-sm text-gray-500">
                    No se encontraron liquidaciones para {{ \Carbon\Carbon::createFromDate($year, $month, 1)->locale('es')->translatedFormat('F Y') }}.
                </p>
                <a href="{{ route('payroll.index', ['year' => $year, 'month' => $month]) }}" 
                   class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Crear Liquidaciones
                </a>
            </div>
        @endif
    </div>
</x-admin-layout>
