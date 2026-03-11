<x-admin-layout>
    <div class="p-4 md:p-6"
         x-data="{
             search: '{{ request('search') }}',
             status: '{{ request('status') }}',
             loading: false,
             init() {
                 this.$watch('search', () => this.fetchResults());
                 this.$watch('status', () => this.fetchResults());
             },
             fetchResults() {
                 this.loading = true;
                 const params = new URLSearchParams();
                 if (this.search) params.set('search', this.search);
                 if (this.status) params.set('status', this.status);
                 const url = '{{ route('purchase-quotation-requests.index') }}' + (params.toString() ? '?' + params.toString() : '');

                 fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                     .then(r => r.text())
                     .then(html => {
                         const parser = new DOMParser();
                         const doc = parser.parseFromString(html, 'text/html');
                         const newResults = doc.getElementById('results-container');
                         if (newResults) {
                             document.getElementById('results-container').innerHTML = newResults.innerHTML;
                         }
                         this.loading = false;
                         window.history.replaceState({}, '', url);
                     })
                     .catch(() => this.loading = false);
             }
         }">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Solicitudes de Cotización</h1>
                <p class="text-gray-500 text-sm mt-1">Pedidos de cotización a proveedores</p>
            </div>
            <a href="{{ route('purchase-quotation-requests.create') }}"
               class="mt-3 md:mt-0 inline-flex items-center px-4 py-2.5 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Solicitud
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-3">
                <div class="flex-1 relative">
                    <input type="text" x-model.debounce.400ms="search"
                           placeholder="Buscar por número, nombre o razón social del proveedor..."
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    <div x-show="loading" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                <div class="w-full md:w-48">
                    <select x-model="status" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        <option value="">Todos los estados</option>
                        <option value="borrador">Borrador</option>
                        <option value="enviada">Enviada</option>
                        <option value="recibida">Recibida</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
                <button x-show="search || status" @click="search = ''; status = ''" type="button"
                        class="px-4 py-2 text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors">Limpiar</button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        <div id="results-container">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                @if($requests->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Número</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Proveedor</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Ítems</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($requests as $req)
                                    @php
                                        $statusColors = [
                                            'borrador' => 'bg-gray-100 text-gray-700',
                                            'enviada' => 'bg-blue-100 text-blue-700',
                                            'recibida' => 'bg-green-100 text-green-700',
                                            'cancelada' => 'bg-red-100 text-red-700',
                                        ];
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a href="{{ route('purchase-quotation-requests.show', $req) }}" class="text-zinc-700 font-semibold hover:text-zinc-900 text-sm">
                                                {{ $req->number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $req->supplier->name }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $req->date->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">{{ $req->items->count() }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$req->status] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $req->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm space-x-2">
                                            <a href="{{ route('purchase-quotation-requests.show', $req) }}" class="text-gray-500 hover:text-zinc-700">Ver</a>
                                            @if($req->status === 'borrador')
                                                <a href="{{ route('purchase-quotation-requests.edit', $req) }}" class="text-gray-500 hover:text-zinc-700">Editar</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($requests->hasPages())
                        <div class="px-4 py-3 border-t border-gray-200">{{ $requests->links() }}</div>
                    @endif
                @else
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay solicitudes de cotización</h3>
                        <p class="mt-1 text-sm text-gray-500">Creá tu primera solicitud de cotización.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
