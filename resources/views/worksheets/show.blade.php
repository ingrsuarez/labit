<x-lab-layout>
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('worksheets.index') }}" class="text-teal-600 hover:text-teal-800 text-sm">&larr; Volver a Planillas</a>
        <div class="flex items-center gap-3 mt-2">
            <h1 class="text-2xl font-bold text-gray-800">{{ $worksheet->name }}</h1>
            @if($worksheet->type === 'clinico')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Lab Clínico</span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">Aguas/Alimentos</span>
            @endif
        </div>
        <p class="text-gray-500 text-sm mt-1">
            Tests: {{ $worksheet->tests->map(fn($t) => $t->code ?: mb_substr($t->name, 0, 4))->implode(', ') }}
        </p>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Filtros</h2>
        <form method="GET" action="{{ route('worksheets.show', $worksheet) }}" id="filterForm">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha desde</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? date('Y-m-d') }}" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha hasta</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? date('Y-m-d') }}" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Protocolo desde</label>
                    <input type="text" name="protocol_from" value="{{ $filters['protocol_from'] ?? '' }}"
                           placeholder="Opcional"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Protocolo hasta</label>
                    <input type="text" name="protocol_to" value="{{ $filters['protocol_to'] ?? '' }}"
                           placeholder="Opcional"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-6 mb-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="include_without_results" value="1"
                           {{ ($filters['include_without_results'] ?? '1') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    <span class="ml-2 text-sm text-gray-700">Sin resultados</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="include_with_results" value="1"
                           {{ ($filters['include_with_results'] ?? '1') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    <span class="ml-2 text-sm text-gray-700">Con resultados</span>
                </label>
            </div>

            <div class="flex gap-3">
                <button type="submit" name="preview" value="1"
                        class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Vista Previa
                </button>
                <button type="button" onclick="downloadPdf()"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Descargar PDF
                </button>
            </div>
        </form>
    </div>

    @if($preview !== null)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Vista previa</h3>
            <span class="text-sm text-gray-500">{{ $preview['rows']->count() }} protocolo(s)</span>
        </div>
        @if($preview['rows']->isEmpty())
            <div class="px-6 py-12 text-center text-gray-500">
                No se encontraron protocolos con los filtros seleccionados.
            </div>
        @else
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase" style="width: 100px;">N° Prot.</th>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase" style="width: 140px;">
                        {{ $worksheet->type === 'clinico' ? 'Paciente' : 'Cliente' }}
                    </th>
                    @foreach($preview['tests'] as $test)
                    <th class="px-2 py-2 text-center text-xs font-medium text-teal-700" style="word-wrap: break-word; overflow-wrap: break-word; max-width: 120px;" title="{{ $test->code }}">
                        {{ $test->name }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($preview['rows'] as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-2 py-2 font-medium text-gray-900 whitespace-nowrap" style="width: 100px;">{{ $row['protocol'] }}</td>
                    <td class="px-2 py-2 text-gray-700" style="width: 140px; word-wrap: break-word; overflow-wrap: break-word;">{{ $row['name'] }}</td>
                    @foreach($preview['tests'] as $test)
                    <td class="px-2 py-2 text-center {{ $row['results'][$test->id] ? 'text-gray-900' : 'text-gray-300' }}">
                        {{ $row['results'][$test->id] ?: '—' }}
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endif
</div>

<script>
function downloadPdf() {
    const form = document.getElementById('filterForm');
    const originalAction = form.action;
    form.action = '{{ route("worksheets.pdf", $worksheet) }}';
    form.submit();
    form.action = originalAction;
}
</script>
</x-lab-layout>
