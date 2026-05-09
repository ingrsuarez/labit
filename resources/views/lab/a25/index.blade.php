<x-lab-layout title="Interfaz Biosystems A25">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Interfaz Biosystems A25</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Seleccioná protocolos para generar el worklist y subí los resultados del equipo.
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

            {{-- Columna izquierda: selección de protocolos para worklist --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-800">1. Generar worklist (import.txt)</h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Se muestran protocolos <strong>clínicos</strong> y <strong>veterinarios</strong> en estado
                            <strong>Pendiente</strong> o <strong>En Proceso</strong>.
                            Seleccioná los que querés enviar al A25 y hacé clic en <em>Vista previa</em>.
                            Solo se incluyen determinaciones con equivalencia A25 configurada.
                        </p>
                    </div>

                    {{-- Filtros --}}
                    <form method="GET" action="{{ route('a25.index') }}" class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3">
                        <select name="lab_branch_id"
                                onchange="this.form.submit()"
                                class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-teal-500">
                            <option value="">Sede: todas</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchFilter == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer select-none">
                            <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-gray-300 text-teal-600">
                            Seleccionar todos (clínico y veterinario)
                        </label>
                    </form>

                    {{-- Clínico --}}
                    <div class="px-5 py-2 border-b border-gray-100 bg-teal-50/60">
                        <h3 class="text-xs font-semibold text-teal-900 uppercase tracking-wide">Laboratorio clínico</h3>
                    </div>
                    <form action="{{ route('a25.worklist.preview') }}" method="POST" id="worklist-form">
                        @csrf
                        @if($branchFilter)
                            <input type="hidden" name="lab_branch_id" value="{{ $branchFilter }}">
                        @endif

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="w-8 px-4 py-2"></th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Protocolo</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Det. pendientes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                    @forelse($admissions as $admission)
                        @php
                            $pendingCount = $admission->admissionTests
                                ->filter(fn($t) => !$t->is_validated && !$t->hasResult())
                                ->count();
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-center">
                                <input type="checkbox"
                                       name="admission_ids[]"
                                       value="{{ $admission->id }}"
                                       class="worklist-check h-4 w-4 text-teal-600 border-gray-300 rounded">
                            </td>
                            <td class="px-4 py-2 font-medium text-teal-700">
                                <a href="{{ route('lab.admissions.show', $admission) }}" class="hover:underline">
                                    {{ $admission->protocol_number }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-gray-700">{{ $admission->patient?->full_name ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-500 text-xs">{{ $admission->labBranch?->name ?? '—' }}</td>
                            <td class="px-4 py-2">
                                @if($admission->status === 'pending')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                @elseif($admission->status === 'in_progress')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">En Proceso</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                @if($pendingCount > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ $pendingCount }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">
                                                No hay protocolos pendiente o en proceso.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    {{-- Veterinario --}}
                    <div class="px-5 py-2 border-b border-gray-100 bg-amber-50/80">
                        <h3 class="text-xs font-semibold text-amber-900 uppercase tracking-wide">Laboratorio veterinario</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-8 px-4 py-2"></th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Protocolo</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Animal / tutor</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sede</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Det. pendientes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($vetAdmissions as $vetAdmission)
                                    @php
                                        $vetPendingCount = $vetAdmission->vetTests
                                            ->filter(fn($t) => !$t->is_validated && !$t->hasResult())
                                            ->count();
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-center">
                                            <input type="checkbox"
                                                   name="vet_admission_ids[]"
                                                   value="{{ $vetAdmission->id }}"
                                                   class="worklist-check h-4 w-4 text-teal-600 border-gray-300 rounded">
                                        </td>
                                        <td class="px-4 py-2 font-medium text-amber-800">
                                            <a href="{{ route('vet.admissions.show', $vetAdmission) }}" class="hover:underline">
                                                {{ $vetAdmission->protocol_number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2 text-gray-700">
                                            <span class="font-medium">{{ $vetAdmission->animal_name }}</span>
                                            <span class="text-gray-500 text-xs"> · {{ $vetAdmission->owner_name }}</span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $vetAdmission->labBranch?->name ?? '—' }}</td>
                                        <td class="px-4 py-2">
                                            @if($vetAdmission->status === 'pending')
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                            @elseif($vetAdmission->status === 'in_progress')
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">En Proceso</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            @if($vetPendingCount > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    {{ $vetPendingCount }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">
                                            No hay protocolos veterinarios pendiente o en proceso.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                        @if($admissions->isNotEmpty() || $vetAdmissions->isNotEmpty())
                            <div class="px-5 py-3 border-t border-gray-100 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                                <div class="text-xs text-gray-500">
                                    {{ $admissions->total() }} clínico(s)
                                    @if($vetAdmissions->total() > 0)
                                        · {{ $vetAdmissions->total() }} veterinario(s)
                                    @endif
                                </div>
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Vista previa del worklist
                                </button>
                            </div>
                        @endif
                    </form>
                </div>

                <div class="mt-2 space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Paginación — clínico</p>
                        {{ $admissions->links() }}
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Paginación — veterinario</p>
                        {{ $vetAdmissions->links() }}
                    </div>
                </div>
            </div>

            {{-- Columna derecha: importar resultados --}}
            <div class="space-y-4">
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-800">2. Importar resultados del equipo</h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Subí el archivo <code class="text-xs bg-gray-100 px-1 rounded">EXP(...).txt</code>
                            que exportó el A25. Los resultados se aplican según la columna de muestra: número de protocolo o ID de equipo cargado en cada protocolo (clínico o veterinario).
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
                        <li>Seleccioná los protocolos a enviar al A25 y hacé clic en <em>Vista previa del worklist</em>.</li>
                        <li>Revisá el contenido y descargá el <code class="bg-amber-100 px-1 rounded">import.txt</code>.</li>
                        <li>Copiá el archivo en la carpeta del equipo A25.</li>
                        <li>Cuando el A25 termine, subí el archivo <code class="bg-amber-100 px-1 rounded">EXP(...).txt</code> en el panel de la derecha.</li>
                    </ol>
                    <p class="mt-2">Configurá las equivalencias en <a href="{{ route('a25.mappings.index') }}" class="underline">⚙ Equivalencias A25 ↔ Labit</a>.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const selectAll = document.getElementById('select-all');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.worklist-check').forEach(cb => cb.checked = this.checked);
            });
        }
    </script>
</x-lab-layout>
