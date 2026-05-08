<x-lab-layout title="Vista previa — import.txt">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0 max-w-5xl mx-auto">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <a href="{{ route('a25.index') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 mb-2">
                    ← Volver al listado
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Vista previa del worklist</h1>
                <p class="text-sm text-gray-600 mt-1">Revisá el contenido del <code class="bg-gray-100 px-1 rounded text-xs">import.txt</code> antes de descargarlo.</p>
            </div>

            {{-- Botón descargar --}}
            @if($result['lines'] > 0)
                <form action="{{ route('a25.worklist') }}" method="POST">
                    @csrf
                    @foreach($admissionIds as $id)
                        <input type="hidden" name="admission_ids[]" value="{{ $id }}">
                    @endforeach
                    @if($labBranchId)
                        <input type="hidden" name="lab_branch_id" value="{{ $labBranchId }}">
                    @endif
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Descargar import.txt
                    </button>
                </form>
            @endif
        </div>

        {{-- Resumen --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold text-teal-600">{{ $result['lines'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Líneas incluidas</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold text-gray-800">{{ count($admissionIds) }}</p>
                <p class="text-xs text-gray-500 mt-1">Protocolos seleccionados</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold {{ $result['skipped'] > 0 ? 'text-amber-500' : 'text-gray-400' }}">{{ $result['skipped'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Determinaciones omitidas</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                @php $ready = collect($result['detail'])->where('lines', '!=', [])->count(); @endphp
                <p class="text-2xl font-bold text-blue-600">{{ $ready }}</p>
                <p class="text-xs text-gray-500 mt-1">Protocolos con líneas</p>
            </div>
        </div>

        @if($result['lines'] === 0)
            <div class="bg-amber-50 border border-amber-300 rounded-xl p-5 text-amber-800 text-sm">
                <p class="font-semibold mb-1">⚠ El worklist está vacío</p>
                <ul class="list-disc list-inside space-y-1 text-xs">
                    <li>Verificá que los protocolos tengan <strong>ID de equipo</strong> asignado.</li>
                    <li>Verificá que las determinaciones pendientes tengan <strong>equivalencias A25 configuradas</strong>.</li>
                </ul>
                <a href="{{ route('a25.index') }}" class="mt-3 inline-block text-teal-700 underline text-xs">← Volver y revisar</a>
            </div>
        @else

            {{-- Detalle por protocolo --}}
            <div class="space-y-3 mb-6">
                @foreach($result['detail'] as $item)
                    @php $admission = $item['admission']; @endphp
                    <div class="bg-white rounded-xl border {{ count($item['lines']) > 0 ? 'border-gray-200' : 'border-amber-200 bg-amber-50' }} overflow-hidden">
                        <div class="px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="font-medium text-teal-700 text-sm">{{ $admission->protocol_number }}</span>
                                <span class="text-gray-500 text-xs">{{ $admission->patient?->full_name ?? '—' }}</span>
                                @if($admission->external_equipment_sample_id)
                                    <span class="font-mono text-xs text-blue-700 bg-blue-50 px-2 py-0.5 rounded">{{ $admission->external_equipment_sample_id }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if(count($item['lines']) > 0)
                                    <span class="text-xs bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full font-medium">
                                        {{ count($item['lines']) }} línea(s)
                                    </span>
                                @else
                                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">
                                        Omitido — {{ $item['reason'] ?? 'Sin determinaciones con mapeo A25' }}
                                    </span>
                                @endif
                                @if($item['skipped'] > 0 && !$item['reason'])
                                    <span class="text-xs text-gray-400">{{ $item['skipped'] }} omitida(s) sin mapeo</span>
                                @endif
                            </div>
                        </div>
                        @if(count($item['lines']) > 0)
                            <div class="border-t border-gray-100 px-4 py-2 bg-gray-50">
                                <pre class="text-xs text-gray-700 font-mono leading-relaxed overflow-x-auto">{{ implode("\n", $item['lines']) }}</pre>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Contenido completo del archivo --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Contenido completo del import.txt</h3>
                    <span class="text-xs text-gray-400">{{ $result['lines'] }} línea(s) · separadas por TAB</span>
                </div>
                <div class="p-4 bg-gray-900 rounded-b-xl overflow-x-auto">
                    <pre class="text-xs text-green-400 font-mono leading-relaxed whitespace-pre">{{ $result['content'] }}</pre>
                </div>
            </div>
        @endif

        {{-- Acción final --}}
        @if($result['lines'] > 0)
            <div class="mt-6 flex justify-end">
                <form action="{{ route('a25.worklist') }}" method="POST">
                    @csrf
                    @foreach($admissionIds as $id)
                        <input type="hidden" name="admission_ids[]" value="{{ $id }}">
                    @endforeach
                    @if($labBranchId)
                        <input type="hidden" name="lab_branch_id" value="{{ $labBranchId }}">
                    @endif
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-teal-600 text-white rounded-xl hover:bg-teal-700 font-semibold text-sm shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Descargar import.txt
                    </button>
                </form>
            </div>
        @endif
    </div>
</x-lab-layout>
