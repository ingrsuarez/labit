<x-admin-layout>
    <div class="max-w-3xl mx-auto py-8 px-4">
        <div class="mb-6">
            <a href="{{ route('api-clients.show', $client) }}"
               class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver al detalle
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h1 class="text-xl font-bold text-gray-900">Editar API Key</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Sede y empresa son inmutables. Para cambiar la key, usá <strong>regenerar</strong> desde el detalle.
                </p>
            </div>

            <form method="POST" action="{{ route('api-clients.update', $client) }}" class="px-6 py-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required maxlength="255"
                           value="{{ old('name', $client->name) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Sede (no editable)</label>
                        <p class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700">
                            {{ $client->labBranch?->name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Empresa (no editable)</label>
                        <p class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700">
                            {{ $client->company?->displayName() ?? '—' }}
                        </p>
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                    <textarea name="notes" id="notes" rows="3" maxlength="2000"
                              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $client->notes) }}</textarea>
                    @error('notes')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $client->active))
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Activa</span>
                </label>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('api-clients.show', $client) }}"
                       class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
