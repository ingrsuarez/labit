<x-admin-layout title="Dashboard">
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Panel ejecutivo</h1>
                <p class="text-gray-500 text-sm">
                    Resumen financiero de {{ now()->locale('es')->isoFormat('MMMM YYYY') }}
                </p>
            </div>
            <div class="flex flex-col md:flex-row md:items-center gap-2 text-sm">
                @if (active_company())
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-gray-700 rounded-md text-xs font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        {{ active_company()->name }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-100 text-amber-800 rounded-md text-xs font-medium">
                        Sin empresa seleccionada
                    </span>
                @endif
                <span class="text-xs text-gray-400">
                    Actualizado {{ now()->format('H:i') }}
                </span>
            </div>
        </div>

        @unless (active_company())
            <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded">
                <p class="text-sm text-amber-800">
                    Seleccioná una empresa desde el header para ver los datos del panel financiero.
                </p>
            </div>
        @endunless

        <!-- Grilla de 4 widgets -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-financial-widget :data="$ventas" />
            <x-financial-widget :data="$compras" />
            <x-financial-widget :data="$ingresos" />
            <x-financial-widget :data="$egresos" />
        </div>

    </div>
</x-admin-layout>
