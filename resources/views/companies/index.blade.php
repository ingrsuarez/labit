<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Empresas</h2>
                <p class="text-sm text-gray-500">Gestión de empresas del sistema</p>
            </div>
            @can('companies.create')
            <a href="{{ route('companies.create') }}" class="inline-flex items-center px-4 py-2 bg-zinc-800 text-white text-sm font-medium rounded-lg hover:bg-zinc-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Empresa
            </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CUIT</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condición IVA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuarios</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($companies as $company)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $company->name }}</div>
                                @if($company->short_name)
                                    <div class="text-sm text-gray-500">{{ $company->short_name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ $company->cuit }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $company->tax_condition }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $company->users_count }}</td>
                            <td class="px-6 py-4">
                                @if($company->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activa</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactiva</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('companies.show', $company) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Ver</a>
                                @can('companies.edit')
                                <a href="{{ route('companies.edit', $company) }}" class="text-amber-600 hover:text-amber-800 text-sm font-medium">Editar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay empresas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
