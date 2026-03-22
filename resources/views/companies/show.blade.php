<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <a href="{{ route('companies.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Volver a empresas</a>
                <h2 class="text-xl font-bold text-gray-800 mt-2">{{ $company->name }}</h2>
                <p class="text-sm text-gray-500 font-mono">CUIT: {{ $company->cuit }}</p>
            </div>
            <div class="flex items-center space-x-3">
                @can('companies.edit')
                <a href="{{ route('companies.edit', $company) }}" class="inline-flex items-center px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors">Editar</a>
                @endcan
                @if($company->is_active)
                    @can('companies.delete')
                    <form action="{{ route('companies.destroy', $company) }}" method="POST" onsubmit="return confirm('¿Desactivar esta empresa?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-500 text-white text-sm font-medium rounded-lg hover:bg-red-600 transition-colors">Desactivar</button>
                    </form>
                    @endcan
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Datos de la empresa --}}
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Datos Fiscales</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between"><dt class="text-sm text-gray-500">Razón Social</dt><dd class="text-sm font-medium text-gray-900">{{ $company->name }}</dd></div>
                    @if($company->short_name)<div class="flex justify-between"><dt class="text-sm text-gray-500">Nombre Corto</dt><dd class="text-sm text-gray-900">{{ $company->short_name }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-sm text-gray-500">CUIT</dt><dd class="text-sm font-mono text-gray-900">{{ $company->cuit }}</dd></div>
                    <div class="flex justify-between"><dt class="text-sm text-gray-500">Condición IVA</dt><dd class="text-sm text-gray-900">{{ $company->tax_condition }}</dd></div>
                    @if($company->address)<div class="flex justify-between"><dt class="text-sm text-gray-500">Domicilio</dt><dd class="text-sm text-gray-900">{{ $company->address }}</dd></div>@endif
                    @if($company->city || $company->state)<div class="flex justify-between"><dt class="text-sm text-gray-500">Localidad</dt><dd class="text-sm text-gray-900">{{ collect([$company->city, $company->state])->filter()->join(', ') }}</dd></div>@endif
                    @if($company->phone)<div class="flex justify-between"><dt class="text-sm text-gray-500">Teléfono</dt><dd class="text-sm text-gray-900">{{ $company->phone }}</dd></div>@endif
                    @if($company->email)<div class="flex justify-between"><dt class="text-sm text-gray-500">Email</dt><dd class="text-sm text-gray-900">{{ $company->email }}</dd></div>@endif
                    @if($company->iibb)<div class="flex justify-between"><dt class="text-sm text-gray-500">IIBB</dt><dd class="text-sm text-gray-900">{{ $company->iibb }}</dd></div>@endif
                    @if($company->activity_start)<div class="flex justify-between"><dt class="text-sm text-gray-500">Inicio Actividades</dt><dd class="text-sm text-gray-900">{{ $company->activity_start->format('d/m/Y') }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-sm text-gray-500">Estado</dt><dd>@if($company->is_active)<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activa</span>@else<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactiva</span>@endif</dd></div>
                </dl>
            </div>

            {{-- Usuarios asignados --}}
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Usuarios Asignados ({{ $company->users->count() }})</h3>

                @if($company->users->count())
                    <div class="space-y-2 mb-4">
                        @foreach($company->users as $user)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <span class="text-sm font-medium text-gray-900">{{ $user->name }}</span>
                                    <span class="text-xs text-gray-500 ml-2">{{ $user->email }}</span>
                                    @if($user->pivot->is_default)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Por defecto</span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if(!$user->pivot->is_default)
                                        <form action="{{ route('companies.set-default', [$company, $user]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">Marcar default</button>
                                        </form>
                                    @endif
                                    @can('companies.assign-users')
                                    <form action="{{ route('companies.detach-user', [$company, $user]) }}" method="POST" onsubmit="return confirm('¿Desvincular este usuario?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800">Quitar</button>
                                    </form>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 mb-4">No hay usuarios asignados a esta empresa.</p>
                @endif

                @can('companies.assign-users')
                @if($availableUsers->count())
                    <form action="{{ route('companies.attach-user', $company) }}" method="POST" class="flex items-end space-x-2 pt-4 border-t">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Agregar usuario</label>
                            <select name="user_id" required class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                <option value="">Seleccionar...</option>
                                @foreach($availableUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="flex items-center space-x-2 pb-1">
                            <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-zinc-600 focus:ring-zinc-500">
                            <span class="text-xs text-gray-600">Default</span>
                        </label>
                        <button type="submit" class="px-4 py-2 bg-zinc-800 text-white text-sm font-medium rounded-lg hover:bg-zinc-700 transition-colors">Agregar</button>
                    </form>
                @endif
                @endcan
            </div>
        </div>
    </div>
</x-admin-layout>
