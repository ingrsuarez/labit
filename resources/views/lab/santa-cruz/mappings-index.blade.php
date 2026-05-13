<x-lab-layout title="Santa Cruz — mapeos">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Mapeos Santa Cruz → Labit</h1>
                <p class="mt-1 text-sm text-gray-600">Equivalencias por código de prestación del XML del cliente.</p>
            </div>
            <a href="{{ route('lab.santa-cruz.sync') }}" class="text-sm text-teal-700 hover:underline">← Volver a sincronización</a>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Código (normalizado)</th>
                        <th class="px-4 py-2 text-left">Nombre (referencia)</th>
                        <th class="px-4 py-2 text-left">Determinación Labit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($mappings as $m)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs">{{ $m->prestacion_code }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $m->prestacion_name ?: '—' }}</td>
                            <td class="px-4 py-2">
                                @if($m->test)
                                    <span class="font-mono text-xs text-gray-500">{{ $m->test->code }}</span>
                                    {{ $m->test->name }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-500">No hay mapeos guardados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $mappings->links() }}
        </div>
    </div>
</x-lab-layout>
