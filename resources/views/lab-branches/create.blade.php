<x-lab-layout title="Nueva Sede">
    <div class="p-4 md:p-6">
        <div class="max-w-2xl mx-auto">
            <div class="mb-6">
                <a href="{{ route('lab-branches.index') }}" class="text-teal-600 hover:text-teal-800 text-sm flex items-center mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver a Sedes
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Nueva Sede</h1>
            </div>

            @include('lab-branches._form', ['labBranch' => null])
        </div>
    </div>
</x-lab-layout>
