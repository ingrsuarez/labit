<x-lab-layout title="Deudores">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Deudores</h1>
            <p class="mt-1 text-sm text-gray-600">Admisiones con pagos pendientes o parciales</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Deuda Total Acumulada</p>
                        <p class="text-3xl font-bold text-red-600">${{ number_format($totalDebt, 2, ',', '.') }}</p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-lg">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Admisiones Pendientes</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $debtorCount }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <form action="{{ route('lab.debtors') }}" method="GET" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar paciente</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nombre, apellido o DNI..."
                           class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm transition-colors">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'date_from', 'date_to']))
                    <a href="{{ route('lab.debtors') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800 text-sm">Limpiar</a>
                @endif
            </form>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($admissions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Protocolo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pagado</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($admissions as $admission)
                                <tr class="{{ $admission->payment_status === 'pendiente' ? 'bg-red-50/50' : 'bg-yellow-50/50' }} hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        {{ $admission->formatted_date }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-teal-600">
                                        <a href="{{ route('lab.admissions.show', $admission) }}" class="hover:underline">
                                            {{ $admission->protocol_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $admission->patient?->full_name ?? 'N/A' }}
                                        <span class="text-gray-500 text-xs block">DNI: {{ $admission->patient?->patientId ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium">
                                        ${{ number_format($admission->total_to_pay, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                                        ${{ number_format($admission->paid_amount, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold text-red-600">
                                        ${{ number_format($admission->balance, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($admission->payment_status === 'parcial')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Parcial</span>
                                        @else
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <a href="{{ route('lab.admissions.show', $admission) }}"
                                           class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition-colors">
                                            Cobrar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($admissions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $admissions->links() }}
                    </div>
                @endif
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-green-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Sin deudas pendientes</h3>
                    <p class="text-gray-500">No hay admisiones con pagos pendientes o parciales.</p>
                </div>
            @endif
        </div>
    </div>
</x-lab-layout>
