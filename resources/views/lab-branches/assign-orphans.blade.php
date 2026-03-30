<x-lab-layout title="Asignar sede a protocolos">
    <div class="p-4 md:p-6">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Asignar sede a protocolos</h1>
                    <p class="text-gray-500 mt-1">Asigná una sede a los protocolos que no tienen una asignada</p>
                </div>
                <a href="{{ route('lab-branches.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver a sedes
                </a>
            </div>

            @php
                $totalOrphans = $orphanAdmissions + $orphanSamples + $orphanVet;
            @endphp

            @if($totalOrphans === 0)
                <div class="bg-green-50 border border-green-200 rounded-xl p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="mt-3 text-lg font-medium text-green-800">Todos los protocolos tienen sede asignada</h3>
                    <p class="mt-1 text-sm text-green-600">No hay protocolos huérfanos que necesiten asignación.</p>
                </div>
            @else
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-amber-800">Protocolos sin sede asignada</h3>
                            <div class="mt-2 text-sm text-amber-700 space-y-1">
                                @if($orphanAdmissions > 0)
                                    <p>• <strong>{{ $orphanAdmissions }}</strong> admisiones de laboratorio clínico</p>
                                @endif
                                @if($orphanSamples > 0)
                                    <p>• <strong>{{ $orphanSamples }}</strong> protocolos de muestras</p>
                                @endif
                                @if($orphanVet > 0)
                                    <p>• <strong>{{ $orphanVet }}</strong> protocolos veterinarios</p>
                                @endif
                                <p class="pt-1 font-medium">Total: {{ $totalOrphans }} protocolos</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <form action="{{ route('lab-branches.assign-orphans.store') }}" method="POST"
                          onsubmit="return confirm('¿Asignar los protocolos seleccionados a esta sede? Esta acción no se puede deshacer fácilmente.')">
                        @csrf

                        <div class="space-y-6">
                            <div>
                                <label for="lab_branch_id" class="block text-sm font-medium text-gray-700 mb-1">Sede destino</label>
                                <select name="lab_branch_id" id="lab_branch_id" required
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                    <option value="">Seleccioná una sede...</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">
                                            {{ $branch->name }}{{ $branch->is_central ? ' (Central)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('lab_branch_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Módulos a asignar</label>
                                <div class="space-y-3">
                                    @if($orphanAdmissions > 0)
                                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                                        <input type="checkbox" name="modules[]" value="admissions" checked
                                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">Lab. Clínico</span>
                                            <span class="text-sm text-gray-500 ml-1">({{ $orphanAdmissions }} admisiones)</span>
                                        </div>
                                    </label>
                                    @endif

                                    @if($orphanSamples > 0)
                                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                                        <input type="checkbox" name="modules[]" value="samples" checked
                                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">Muestras</span>
                                            <span class="text-sm text-gray-500 ml-1">({{ $orphanSamples }} protocolos)</span>
                                        </div>
                                    </label>
                                    @endif

                                    @if($orphanVet > 0)
                                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                                        <input type="checkbox" name="modules[]" value="vet_admissions" checked
                                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">Veterinario</span>
                                            <span class="text-sm text-gray-500 ml-1">({{ $orphanVet }} protocolos)</span>
                                        </div>
                                    </label>
                                    @endif
                                </div>
                                @error('modules')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="pt-4 border-t border-gray-200">
                                <button type="submit"
                                        class="w-full px-4 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors font-medium">
                                    Asignar sede a los protocolos seleccionados
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-lab-layout>
