<x-lab-layout title="Resultado de importación A25">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0 max-w-4xl">
        <div class="mb-6">
            <div class="text-sm text-gray-500 mb-1">
                <a href="{{ route('a25.index') }}" class="hover:text-teal-600">Interfaz A25</a>
                <span class="mx-1">/</span>
                <span>Resultado de importación</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Resultado de importación A25</h1>
        </div>

        {{-- Resumen --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-green-700">{{ $importResult['ingested'] }}</div>
                <div class="text-sm text-green-600 mt-1">Ingresados</div>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-amber-700">{{ $importResult['overwritten'] }}</div>
                <div class="text-sm text-amber-600 mt-1">Sobreescritos</div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-red-700">{{ $importResult['rejected'] }}</div>
                <div class="text-sm text-red-600 mt-1">Rechazados</div>
            </div>
        </div>

        {{-- Detalle línea por línea --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-800">Detalle ({{ count($importResult['lines']) }} líneas procesadas)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-12">#</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Analito</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Protocolo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($importResult['lines'] as $line)
                            @php
                                $isOk = in_array($line['status'], ['ingested', 'overwritten']);
                                $rowClass = $isOk ? '' : 'bg-red-50';
                                $badgeClass = match($line['status']) {
                                    'ingested' => 'bg-green-100 text-green-800',
                                    'overwritten' => 'bg-amber-100 text-amber-800',
                                    default => 'bg-red-100 text-red-800',
                                };
                                $label = match($line['status']) {
                                    'ingested' => 'Ingresado',
                                    'overwritten' => 'Sobreescrito',
                                    default => 'Rechazado',
                                };
                            @endphp
                            <tr class="{{ $rowClass }} hover:bg-gray-50">
                                <td class="px-4 py-2 text-gray-400 text-xs">{{ $line['line'] }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $line['analyte'] ?? '—' }}</td>
                                <td class="px-4 py-2 text-xs">
                                    @if(!empty($line['protocol_number']))
                                        <a href="{{ route('lab.admissions.index') }}" class="text-teal-700 hover:underline">
                                            {{ $line['protocol_number'] }}
                                        </a>
                                    @else
                                        {{ $line['sample_id'] ?? '—' }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 font-mono text-xs">
                                    @if(!empty($line['value']))
                                        {{ $line['value'] }} {{ $line['unit'] ?? '' }}
                                        @if(!empty($line['previous_value']))
                                            <span class="text-amber-600 ml-1">(antes: {{ $line['previous_value'] }})</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-500">
                                    @if(!$isOk)
                                        <span class="text-red-600">{{ $line['reason'] ?? '' }}</span>
                                        @if(!empty($line['message']))
                                            — {{ $line['message'] }}
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('a25.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                ← Volver a la interfaz A25
            </a>
        </div>
    </div>
</x-lab-layout>
