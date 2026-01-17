<x-portal-layout title="Organigrama">
    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Organigrama</h1>
                    <p class="text-sm text-gray-500">Estructura organizacional de la empresa</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('portal.dashboard') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver
                    </a>
                </div>
            </div>

            {{-- Organigrama --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden p-6">
                @livewire('organization-chart', ['employees' => $employees, 'job' => $job, 'currentEmployeeId' => $employee->id])
            </div>
        </div>
    </div>
</x-portal-layout>
