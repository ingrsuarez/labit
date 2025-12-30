<x-admin-manage>
    <div class="max-w-7xl mx-auto p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Resumen (últimos 4 meses) por empleado</h1>

        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CUIL</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ingreso</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Horas sem.</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puesto</th>

                        @foreach($monthsMeta as $m)
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                {{ $m['label'] }}
                                <div class="text-[11px] text-gray-400 font-normal">Vac / Enf / Emb / 50% / 100%</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($employees as $e)
                        @php
                            $latestJob = $e->jobs->sortByDesc(fn($j) => optional($j->pivot)->created_at)->first();
                            $puesto    = optional($latestJob)->name;
                            $categoria = optional(optional($latestJob)->category)->name ?? $e->position;
                        @endphp

                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 capitalize">{{ $e->lastName }}, {{ $e->name }}</div>
                                {{-- <div class="text-xs text-gray-500">#{{ $e->id }}</div> --}}
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $e->employeeId ?? '—' }} {{-- reemplazar por $e->cuil si existe --}}
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $e->start_date ? \Illuminate\Support\Carbon::parse($e->start_date)->format('Y-m-d') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ (int)($e->weekly_hours ?? 0) }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $categoria ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $puesto ?? '—' }}</td>

                            @foreach($monthsMeta as $m)
                                @php
                                    $mdata = $byEmpMonth[$e->id][$m['key']] ?? ['vac'=>0,'enf'=>0,'emb'=>0,'h50'=>0,'h100'=>0,'files'=>collect()];
                                @endphp
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1 text-xs text-gray-700">
                                        <span class="inline-flex px-2 py-0.5 rounded bg-gray-100">Vac {{ $mdata['vac'] }}</span>
                                        <span class="inline-flex px-2 py-0.5 rounded bg-gray-100">Enf {{ $mdata['enf'] }}</span>
                                        <span class="inline-flex px-2 py-0.5 rounded bg-gray-100">Emb {{ $mdata['emb'] }}</span>
                                        <span class="inline-flex px-2 py-0.5 rounded bg-gray-100">50% {{ $mdata['h50'] }}</span>
                                        <span class="inline-flex px-2 py-0.5 rounded bg-gray-100">100% {{ $mdata['h100'] }}</span>
                                    </div>

                                    @if(($mdata['files'] ?? collect())->isNotEmpty())
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @foreach($mdata['files'] as $idx => $file)
                                                <a href="{{ asset('storage/'.$file) }}" target="_blank"
                                                class="inline-flex items-center px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[11px] hover:bg-blue-100">
                                                    Cert {{ $idx+1 }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ 6 + count($monthsMeta) }}" class="px-4 py-6 text-center text-gray-500">Sin empleados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-manage>