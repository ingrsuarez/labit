<x-portal-layout>
    <div class="max-w-4xl mx-auto py-8 px-4">
        {{-- Breadcrumb --}}
        <div class="mb-6">
            <a href="{{ route('portal.circulars.index') }}" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver a Circulares
            </a>
        </div>

        {{-- Circular Card --}}
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 text-white p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-sm font-mono bg-white/20 px-2 py-0.5 rounded">{{ $circular->code }}</span>
                            <span class="text-sm opacity-80">{{ $circular->date->format('d/m/Y') }}</span>
                        </div>
                        <h1 class="text-2xl font-bold">{{ $circular->title }}</h1>
                    </div>
                    @if($signature && $signature->signed_at)
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-500 text-white">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Firmada
                        </span>
                    @else
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-amber-500 text-white">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pendiente
                        </span>
                    @endif
                </div>
            </div>

            {{-- Metadata --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 bg-gray-50 border-b">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Sector</p>
                    <p class="text-sm font-semibold text-gray-900">
                        {{ \App\Models\Circular::sectors()[$circular->sector] ?? $circular->sector }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Fecha</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $circular->date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Creado por</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $circular->creator?->name ?? 'Sistema' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Estado</p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $circular->status_color }}">
                        {{ ucfirst($circular->status) }}
                    </span>
                </div>
            </div>

            {{-- Contenido --}}
            <div class="p-8">
                <div class="prose max-w-none">
                    {!! nl2br(e($circular->description)) !!}
                </div>
            </div>

            {{-- Firma --}}
            @if($signature && $signature->signed_at)
                {{-- Ya firmada --}}
                <div class="p-6 bg-green-50 border-t border-green-100">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-10 w-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-green-900">Circular firmada</h3>
                            <p class="text-sm text-green-700">
                                Firmaste esta circular el <strong>{{ $signature->signed_at->format('d/m/Y') }}</strong> 
                                a las <strong>{{ $signature->signed_at->format('H:i') }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            @else
                {{-- Pendiente de firma --}}
                <div class="p-6 bg-amber-50 border-t border-amber-100">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-10 w-10 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-amber-900">Firma requerida</h3>
                                <p class="text-sm text-amber-700">
                                    Al firmar confirmas que has leído y comprendido el contenido de esta circular.
                                </p>
                            </div>
                        </div>
                        <form action="{{ route('portal.circulars.sign', $circular) }}" method="POST" 
                              onsubmit="return confirm('¿Confirmas que has leído y comprendido esta circular?')">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors font-semibold shadow-lg shadow-indigo-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                                Firmar Circular
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- Info de lectura --}}
        @if($signature && $signature->read_at)
            <div class="mt-4 text-center text-xs text-gray-500">
                Primera lectura: {{ $signature->read_at->format('d/m/Y H:i') }}
            </div>
        @endif
    </div>
</x-portal-layout>

