<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Circulares
            </h2>
            <a href="{{ route('circular.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Circular
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Mensajes Flash -->
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Estadísticas -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-400">
                    <p class="text-sm text-gray-500">Total</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                    <p class="text-sm text-gray-500">Activas</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['activas'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-400">
                    <p class="text-sm text-gray-500">Inactivas</p>
                    <p class="text-2xl font-bold text-gray-600">{{ $stats['inactivas'] }}</p>
                </div>
            </div>

            <!-- Búsqueda y Filtros -->
            <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" 
                                   placeholder="Buscar por título, descripción o código..."
                                   class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Todos</option>
                            @foreach(\App\Models\Circular::statuses() as $key => $label)
                                <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sector</label>
                        <select name="sector" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Todos</option>
                            @foreach(\App\Models\Circular::sectors() as $key => $label)
                                <option value="{{ $key }}" {{ request('sector') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 text-sm">
                            Buscar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                @if($circulars->count())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sector</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Firmas</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($circulars as $circular)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('circular.show', $circular) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                                {{ $circular->code }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $circular->date->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ Str::limit($circular->title, 50) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Por: {{ $circular->creator->name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ \App\Models\Circular::sectors()[$circular->sector] ?? $circular->sector }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @php
                                                $signedSignatures = $circular->signatures->whereNotNull('signed_at')->sortByDesc('signed_at');
                                            @endphp
                                            @if($signedSignatures->count() > 0)
                                                <div class="flex flex-wrap gap-1 max-w-xs">
                                                    @foreach($signedSignatures->take(3) as $sig)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800" 
                                                              title="Firmó: {{ $sig->signed_at->format('d/m/Y H:i') }}">
                                                            <svg class="w-3 h-3 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            {{ ucwords($sig->employee->lastName ?? '') }} {{ ucfirst($sig->employee->name ?? '') }}
                                                        </span>
                                                    @endforeach
                                                    @if($signedSignatures->count() > 3)
                                                        <a href="{{ route('circular.signatures', $circular) }}" 
                                                           class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 hover:bg-gray-200">
                                                            +{{ $signedSignatures->count() - 3 }} más
                                                        </a>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Sin firmas</span>
                                            @endif
                                            <a href="{{ route('circular.signatures', $circular) }}" class="block mt-1 text-xs text-indigo-600 hover:text-indigo-800">
                                                Ver seguimiento →
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2 py-1 text-xs rounded-full {{ $circular->status_color }}">
                                                {{ ucfirst($circular->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <a href="{{ route('circular.pdf', $circular) }}" target="_blank" class="text-gray-600 hover:text-gray-900 mr-3" title="PDF">
                                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </a>
                                            <a href="{{ route('circular.show', $circular) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                Ver
                                            </a>
                                            <a href="{{ route('circular.edit', $circular) }}" class="text-gray-600 hover:text-gray-900">
                                                Editar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t">
                        {{ $circulars->withQueryString()->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay circulares</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            @if(request('search'))
                                No se encontraron resultados para "{{ request('search') }}"
                            @else
                                Comenzá creando una nueva circular.
                            @endif
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('circular.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Nueva Circular
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
