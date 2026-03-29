<x-lab-layout title="Lab. Veterinario">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Lab. Veterinario — Protocolos</h1>
                <p class="mt-1 text-sm text-gray-600">Gestione los protocolos veterinarios</p>
            </div>
            <a href="{{ route('vet.admissions.create') }}"
               class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nuevo Protocolo
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <form action="{{ route('vet.admissions.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Buscar por protocolo, dueño o animal..."
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div class="w-40">
                    <select name="species_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                        <option value="">Todas las especies</option>
                        @foreach($species as $sp)
                            <option value="{{ $sp->id }}" {{ request('species_id') == $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-48">
                    <select name="customer_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                        <option value="">Todas las veterinarias</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-36">
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500" placeholder="Desde">
                </div>
                <div class="w-36">
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500" placeholder="Hasta">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Filtrar</button>
                @if(request()->hasAny(['search', 'species_id', 'customer_id', 'date_from', 'date_to']))
                    <a href="{{ route('vet.admissions.index') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($admissions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocolo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Especie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dueño</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinaria</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($admissions as $adm)
                                @php
                                    $color = $adm->status_color;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('vet.admissions.show', $adm) }}" class="text-amber-600 hover:text-amber-800 font-medium">
                                            {{ $adm->protocol_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $adm->date->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $adm->animal_name }}</div>
                                        @if($adm->breed)<div class="text-xs text-gray-500">{{ $adm->breed }}</div>@endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            {{ $adm->species->name ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $adm->owner_name }}</div>
                                        @if($adm->owner_phone)<div class="text-xs text-gray-500">{{ $adm->owner_phone }}</div>@endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $adm->customer->name ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                                            {{ $adm->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <a href="{{ route('vet.admissions.show', $adm) }}" class="text-amber-600 hover:text-amber-800 text-sm font-medium">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-200">{{ $admissions->links() }}</div>
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s-8-4.5-8-11.8A4 4 0 0112 4a4 4 0 018 5.2c0 7.3-8 11.8-8 11.8z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay protocolos veterinarios</h3>
                    <p class="mt-1 text-sm text-gray-500">Comience creando un nuevo protocolo.</p>
                    <div class="mt-6">
                        <a href="{{ route('vet.admissions.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Nuevo Protocolo
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-lab-layout>
