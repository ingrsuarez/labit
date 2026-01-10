<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <a href="{{ route('circular.index') }}" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ $circular->code }}
                    </h2>
                    <p class="text-sm text-gray-500">Circular</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 text-sm rounded-full {{ $circular->status_color }}">
                    {{ ucfirst($circular->status) }}
                </span>
                <a href="{{ route('circular.signatures', $circular) }}"
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Firmas
                </a>
                <a href="{{ route('circular.print', $circular) }}" target="_blank"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir
                </a>
                <a href="{{ route('circular.pdf', $circular) }}" target="_blank"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    PDF
                </a>
                <a href="{{ route('circular.edit', $circular) }}" 
                   class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 text-sm">
                    Editar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Mensajes Flash -->
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Contenido -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <!-- Encabezado -->
                <div class="bg-indigo-50 px-6 py-4 border-b border-indigo-100">
                    <h3 class="text-lg font-semibold text-indigo-900">{{ $circular->title }}</h3>
                </div>
                
                <!-- Metadatos -->
                <div class="px-6 py-4 grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 border-b">
                    <div>
                        <p class="text-xs text-gray-500">Código</p>
                        <p class="font-medium text-gray-900">{{ $circular->code }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Fecha</p>
                        <p class="font-medium text-gray-900">{{ $circular->date->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Sector</p>
                        <p class="font-medium text-gray-900">{{ \App\Models\Circular::sectors()[$circular->sector] ?? $circular->sector }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Creado por</p>
                        <p class="font-medium text-gray-900">{{ $circular->creator->name }}</p>
                    </div>
                </div>

                <!-- Contenido -->
                <div class="px-6 py-6">
                    <h4 class="text-sm font-medium text-gray-500 mb-3">Contenido</h4>
                    <div class="prose max-w-none text-gray-700 whitespace-pre-line">
                        {{ $circular->description }}
                    </div>
                </div>

                <!-- Pie -->
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center text-sm text-gray-500">
                    <span>Creada: {{ $circular->created_at->format('d/m/Y H:i') }}</span>
                    <span>Última actualización: {{ $circular->updated_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
