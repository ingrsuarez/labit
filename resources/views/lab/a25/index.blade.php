<x-lab-layout title="Interfaz Biosystems A25">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Interfaz Biosystems A25</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Generá la worklist para enviar al equipo e importá los resultados cuando el equipo termina.
                </p>
            </div>
            @can('a25.mappings.manage')
                <a href="{{ route('a25.mappings.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                    ⚙ Equivalencias A25 ↔ Labit
                </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Columna izquierda: worklist --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-800">1. Generar worklist (import.txt)</h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Seleccioná los protocolos que querés enviar al equipo. Solo se incluyen
                            determinaciones pendientes con equivalencia A25 configurada y con ID de equipo asignado.
                        </p>
                    </div>

                    <form action="{{ route('a25.worklist') }}" method="POST" id="worklist-form">
                        @csrf

                        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-3">
                            <select name="lab_branch_id"
                                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-teal-500">
                                <option value="">Sede: todas</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <label class="text-xs text-gray-500">
                                <input type="checkbox" id="select-all" class="mr-1">
                                Seleccionar todos
                            </label>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="w-8 px-4 py-2"></th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Protocolo</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID Equipo</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Det. pendientes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($admissions as $admission)
                                        @php
                                            $pending = $admission->admissionTests->filter(fn($t) => !$t->is_validated && empty($t->result))->count();
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 text-center">
                                                <input type="checkbox" name="admission_ids[]"
                                                       value="{{ $admission->id }}"
                                                       class="admission-check h-4 w-4 text-teal-600 border-gray-300 rounded">
                                            </td>
                                            <td class="px-4 py-2 font-medium text-teal-700">
                                                <a href="{{ route('lab.admissions.show', $admission) }}" class="hover:underline">
                                                    {{ $admission->protocol_number }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-2 text-gray-700">{{ $admission->patient?->full_name ?? '—' }}</td>
                                            <td class="px-4 py-2 font-mono text-xs text-blue-700">
                                                {{ $admission->external_equipment_sample_id }}
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    {{ $pending }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">
                                                No hay protocolos con ID de equipo asignado y determinaciones pendientes.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($admissions->isNotEmpty())
                            <div class="px-5 py-3 border-t border-gray-100 flex justify-between items-center">
                                <div class="text-xs text-gray-500">{{ $admissions->total() }} protocolos en total</div>
                                <button type="submit"
                                        class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-medium">
                                    ↓ Descargar import.txt
                                </button>
                            </div>
                        @endif
                    </form>
                </div>

                <div class="mt-4">{{ $admissions->links() }}</div>
            </div>

            {{-- Columna derecha: importar resultados --}}
            <div class="space-y-4">
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-800">2. Importar resultados del equipo</h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Subí el archivo <code class="text-xs bg-gray-100 px-1 rounded">EXP(...).txt</code>
                            que exportó el A25. Los resultados se aplican automáticamente.
                        </p>
                    </div>
                    <form action="{{ route('a25.import') }}" method="POST" enctype="multipart/form-data"
                          class="px-5 py-4 space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sede (para resolver equivalencias)</label>
                            <select name="lab_branch_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-teal-500">
                                <option value="">Global (sin filtro de sede)</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Archivo de resultados <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="results_file" accept=".txt,.csv" required
                                   class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                        </div>

                        <button type="submit"
                                class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                            ↑ Importar resultados
                        </button>
                    </form>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-800 space-y-1.5">
                    <p class="font-semibold">ℹ️ Instrucciones de uso</p>
                    <ol class="list-decimal list-inside space-y-1">
                        <li>Asigná el <strong>ID de equipo</strong> a cada protocolo desde la pantalla de admisión.</li>
                        <li>Descargá el <code class="bg-amber-100 px-1 rounded">import.txt</code> y copialo en la carpeta del equipo.</li>
                        <li>Esperá que el A25 procese y exporte el archivo de resultados.</li>
                        <li>Subí el archivo de resultados aquí para cargarlos en Labit.</li>
                    </ol>
                    <p class="mt-2">Configurá las equivalencias en <a href="{{ route('a25.mappings.index') }}" class="underline">⚙ Equivalencias A25 ↔ Labit</a>.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('.admission-check').forEach(cb => cb.checked = this.checked);
        });
    </script>
</x-lab-layout>
