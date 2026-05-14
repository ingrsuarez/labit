<x-lab-layout title="Santa Cruz — sincronización FTP">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0"
         x-data="santaCruzSync({
            insuranceId: {{ json_encode($insuranceId) }},
            searchTestsUrl: @json(route('lab.admissions.searchTests')),
         })">
        {{-- El scan/import son POST síncronos (FTP + XML); sin feedback parece “congelado”. --}}
        <div x-show="scanBusy || importBusy" x-cloak
             class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-6"
             aria-live="polite" aria-busy="true">
            <div class="max-w-md rounded-xl bg-white p-6 shadow-xl text-center space-y-3">
                <p class="text-lg font-semibold text-gray-900" x-text="importBusy ? 'Importando…' : 'Sincronizando con el FTP…'"></p>
                <p class="text-sm text-gray-600">
                    La petición sigue en el servidor (lista y descarga de XML puede tardar varios minutos con FTPS y muchos archivos).
                    No cierres esta pestaña; cuando termine, la página se recargará sola.
                </p>
                <p class="text-xs text-gray-500">Si falla por tiempo, subí <code class="bg-gray-100 px-1 rounded">SANTA_CRUZ_SCAN_MAX_SECONDS</code> o <code class="bg-gray-100 px-1 rounded">SANTA_CRUZ_FTP_TIMEOUT</code> en <code>.env</code>.</p>
            </div>
        </div>

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Santa Cruz O&amp;G — importación XML</h1>
                <p class="mt-1 text-sm text-gray-600">Sincronizar carpeta FTP, revisar mapeos de prácticas e importar admisiones clínicas.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form action="{{ route('lab.santa-cruz.sync.scan') }}" method="POST" @submit="scanBusy = true">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-medium disabled:opacity-60"
                            :disabled="scanBusy || importBusy">
                        <span x-show="! scanBusy">Sincronizar desde FTP</span>
                        <span x-show="scanBusy" x-cloak>Sincronizando…</span>
                    </button>
                </form>
                @can('santacruz.import')
                <a href="{{ route('lab.santa-cruz.mappings.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                    Mapeos guardados
                </a>
                @endcan
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="mb-4 bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-lg text-sm">{{ session('warning') }}</div>
        @endif

        @if(!$insuranceId)
            <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-lg text-sm">
                Configurá <code class="bg-amber-100 px-1 rounded">SANTA_CRUZ_INSURANCE_ID</code> en <code>.env</code> con el ID de la obra social Santa Cruz en Labit.
            </div>
        @endif

        @if(!empty($imported))
            <div class="mb-6 bg-teal-50 border border-teal-200 rounded-lg p-4">
                <h2 class="font-semibold text-teal-900 mb-2">Última importación</h2>
                <ul class="space-y-1 text-sm">
                    @foreach($imported as $row)
                        <li>
                            <a href="{{ route('lab.admissions.show', $row['admission_id']) }}" class="text-teal-700 hover:underline font-medium">{{ $row['protocol_number'] }}</a>
                            <span class="text-gray-600">— {{ $row['file'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(empty($rows))
            <p class="text-gray-500 text-sm">Todavía no hay una vista previa. Presioná <strong>Sincronizar desde FTP</strong> para listar los archivos <code>.xml</code>.</p>
        @else
            <form action="{{ route('lab.santa-cruz.sync.import') }}" method="POST" id="import-form" class="space-y-4"
                  @submit="if (! confirm('¿Importar los archivos seleccionados? Se crearán admisiones y se moverán los XML a la carpeta procesados en el FTP.')) { $event.preventDefault(); return; } importBusy = true;">
                @csrf
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium disabled:opacity-50"
                            :disabled="scanBusy || importBusy || ! insuranceId">
                        <span x-show="! importBusy">Importar seleccionados</span>
                        <span x-show="importBusy" x-cloak>Importando…</span>
                    </button>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left w-10"></th>
                                <th class="px-3 py-2 text-left">Archivo</th>
                                <th class="px-3 py-2 text-left">Paciente</th>
                                <th class="px-3 py-2 text-left">DNI</th>
                                <th class="px-3 py-2 text-left">Accession</th>
                                <th class="px-3 py-2 text-left">Prácticas</th>
                                <th class="px-3 py-2 text-left">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($rows as $idx => $row)
                                <tr class="align-top">
                                    <td class="px-3 py-2">
                                        @if(empty($row['error']) && ($row['ready'] ?? false))
                                            <input type="checkbox" name="files[]" value="{{ $row['file'] }}" class="rounded border-gray-300 text-teal-600">
                                        @else
                                            <input type="checkbox" disabled class="rounded border-gray-200 opacity-40" title="Resolvé todos los mapeos antes de importar">
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['file'] }}</td>
                                    <td class="px-3 py-2">
                                        @if(!empty($row['parsed']))
                                            {{ $row['parsed']['last_name'] }}, {{ $row['parsed']['first_name'] }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">{{ $row['parsed']['document_number'] ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $row['parsed']['accession_number'] ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ count($row['practicas_resolved'] ?? []) }}</td>
                                    <td class="px-3 py-2">
                                        @if(!empty($row['error']))
                                            <span class="text-red-600">{{ $row['error'] }}</span>
                                        @elseif($row['ready'] ?? false)
                                            <span class="text-green-700 font-medium">Listo</span>
                                        @else
                                            <span class="text-amber-700">Pendiente mapeos</span>
                                        @endif
                                    </td>
                                </tr>
                                @if(!empty($row['practicas_resolved']))
                                    <tr class="bg-gray-50/80">
                                        <td colspan="7" class="px-4 py-3">
                                            <p class="text-xs font-semibold text-gray-600 mb-2">Prácticas (XML)</p>
                                            <ul class="space-y-2">
                                                @foreach($row['practicas_resolved'] as $p)
                                                    <li class="flex flex-wrap items-center gap-2 text-xs">
                                                        <code class="bg-white px-1.5 py-0.5 rounded border">{{ $p['prestacion_code'] }}</code>
                                                        <span class="text-gray-700">{{ $p['prestacion_name'] }}</span>
                                                        @if($p['mapped'])
                                                            <span class="text-green-700">✓ mapeada</span>
                                                        @else
                                                            <span class="text-red-600">sin mapeo</span>
                                                            <button type="button" class="text-teal-700 hover:underline"
                                                                    @click="openMappingModal(@js($p['prestacion_code']), @js($p['prestacion_name']))">
                                                                Crear mapeo
                                                            </button>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        @endif

        {{-- Modal mapeo --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @keydown.escape.window="modalOpen = false">
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4" @click.outside="modalOpen = false">
                <h3 class="text-lg font-semibold text-gray-900">Nuevo mapeo Santa Cruz → Labit</h3>
                <form action="{{ route('lab.santa-cruz.mappings.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <input type="hidden" name="prestacion_code" :value="mapCode">
                    <input type="hidden" name="prestacion_name" :value="mapName">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Código prestación (XML)</label>
                        <p class="text-sm font-mono bg-gray-100 rounded px-2 py-1" x-text="mapCode"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Buscar determinación Labit</label>
                        <input type="text" x-model="searchQ" @input.debounce.300ms="searchTests()" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Código o nombre…">
                    </div>
                    <input type="hidden" name="test_id" :value="selectedTestId">
                    <div x-show="selectedTestLabel" class="text-sm text-gray-800 bg-teal-50 border border-teal-100 rounded px-2 py-1" x-text="selectedTestLabel"></div>
                    <ul class="max-h-48 overflow-y-auto border border-gray-200 rounded divide-y divide-gray-100 text-sm" x-show="searchResults.length">
                        <template x-for="t in searchResults" :key="t.id">
                            <li>
                                <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50"
                                        @click="pickTest(t)">
                                    <span class="font-mono text-xs text-gray-500" x-text="t.code"></span>
                                    <span x-text="t.name"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-3 py-1.5 text-sm text-gray-600" @click="modalOpen = false">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg disabled:opacity-40" :disabled="!selectedTestId">Guardar mapeo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-lab-layout>
