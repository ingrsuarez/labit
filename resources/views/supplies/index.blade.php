<x-admin-layout>
    <div class="p-4 md:p-6"
         x-data="{
             search: '{{ request('search') }}',
             category: '{{ request('category') }}',
             stock_status: '{{ request('stock_status') }}',
             loading: false,
             mergeModal: false,
             mergeSource: { id: null, name: '', code: '' },
             mergeTarget: null,
             mergeSearch: '',
             mergeSearchResults: [],
             mergeSearchLoading: false,
             mergePreviewData: null,
             mergePreviewLoading: false,
             mergeSubmitting: false,
             init() {
                 this.$watch('search', () => this.fetchResults());
                 this.$watch('category', () => this.fetchResults());
                 this.$watch('stock_status', () => this.fetchResults());
             },
             fetchResults() {
                 this.loading = true;
                 const params = new URLSearchParams();
                 if (this.search) params.set('search', this.search);
                 if (this.category) params.set('category', this.category);
                 if (this.stock_status) params.set('stock_status', this.stock_status);
                 const url = '{{ route('supplies.index') }}' + (params.toString() ? '?' + params.toString() : '');

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
             },
             openMergeModal(id, name, code) {
                 this.mergeSource = { id, name, code };
                 this.mergeTarget = null;
                 this.mergeSearch = '';
                 this.mergeSearchResults = [];
                 this.mergePreviewData = null;
                 this.mergeModal = true;
             },
             async searchMergeTarget() {
                 if (this.mergeSearch.length < 2) { this.mergeSearchResults = []; return; }
                 this.mergeSearchLoading = true;
                 try {
                     const resp = await fetch(`{{ route('supplies.search') }}?q=${encodeURIComponent(this.mergeSearch)}`, {
                         headers: { 'Accept': 'application/json' }
                     });
                     const data = await resp.json();
                     this.mergeSearchResults = data.filter(s => s.id !== this.mergeSource.id);
                 } catch (e) {
                     this.mergeSearchResults = [];
                 } finally {
                     this.mergeSearchLoading = false;
                 }
             },
             async selectMergeTarget(supply) {
                 this.mergeTarget = supply;
                 this.mergeSearch = supply.name;
                 this.mergeSearchResults = [];
                 this.mergePreviewLoading = true;
                 try {
                     const previewUrl = '{{ route('supplies.merge-preview', ['supply' => '__ID__']) }}'.replace('__ID__', this.mergeSource.id);
                     const resp = await fetch(previewUrl, {
                         headers: { 'Accept': 'application/json' }
                     });
                     this.mergePreviewData = await resp.json();
                 } catch (e) {
                     this.mergePreviewData = null;
                 } finally {
                     this.mergePreviewLoading = false;
                 }
             }
         }">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Insumos</h1>
                <p class="text-gray-500 text-sm mt-1">Control de insumos y stock</p>
            </div>
            <div class="flex items-center gap-3 mt-3 md:mt-0">
                @if($lowStockCount > 0)
                    <span class="inline-flex items-center px-3 py-1.5 bg-amber-50 border border-amber-200 text-amber-700 text-sm font-medium rounded-lg">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        {{ $lowStockCount }} con stock bajo
                    </span>
                @endif
                <a href="{{ route('supplies.create') }}"
                   class="inline-flex items-center px-4 py-2.5 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo Insumo
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-3">
                <div class="flex-1 relative">
                    <input type="text" x-model.debounce.400ms="search"
                           placeholder="Buscar por nombre, código o descripción..."
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    <div x-show="loading" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                <div class="w-full md:w-44">
                    <select x-model="category" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        <option value="">Todas las categorías</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-40">
                    <select x-model="stock_status" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        <option value="">Todo el stock</option>
                        <option value="low">Stock bajo</option>
                        <option value="zero">Sin stock</option>
                        <option value="ok">Stock OK</option>
                    </select>
                </div>
                <button x-show="search || category || stock_status" @click="search = ''; category = ''; stock_status = ''" type="button"
                        class="px-4 py-2 text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors">Limpiar</button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Tabla -->
        <div id="results-container">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($supplies->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Insumo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Categoría</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Unidad</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider max-w-[14rem]">Por sede</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Mínimo</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Últ. Precio</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($supplies as $supply)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 font-mono">
                                        {{ $supply->code }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <a href="{{ route('supplies.show', $supply) }}" class="text-zinc-700 font-semibold hover:text-zinc-900 text-sm">
                                            {{ $supply->name }}
                                        </a>
                                        @if($supply->brand)
                                            <span class="text-xs text-gray-400">· {{ $supply->brand }}</span>
                                        @endif
                                        @if(!$supply->is_active)
                                            <span class="ml-1 text-xs text-gray-400">(inactivo)</span>
                                        @endif
                                        @if($supply->tracks_lot)
                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-600" title="Controla lote/vencimiento">Lote</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $supply->category->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-center">
                                        {{ $supply->unit }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        @if($supply->isLowStock())
                                            <span class="text-sm font-bold text-amber-600">
                                                {{ number_format($supply->stock, 2, ',', '.') }}
                                            </span>
                                            <svg class="w-4 h-4 inline text-amber-500 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                        @else
                                            <span class="text-sm font-semibold text-gray-800">
                                                {{ number_format($supply->stock, 2, ',', '.') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 max-w-[14rem]">
                                        @php
                                            $byBranch = $supply->branchStocks->sortBy(fn ($r) => $r->labBranch->name ?? '');
                                        @endphp
                                        @forelse($byBranch as $bs)
                                            <div class="truncate" title="{{ $bs->labBranch->name ?? '—' }}">{{ $bs->labBranch->name ?? '—' }}: {{ number_format($bs->quantity, 2, ',', '.') }}</div>
                                        @empty
                                            <span class="text-gray-400">—</span>
                                        @endforelse
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-right">
                                        {{ number_format($supply->min_stock, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right">
                                        ${{ number_format($supply->last_price, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm space-x-2">
                                        <a href="{{ route('supplies.show', $supply) }}" class="text-gray-500 hover:text-zinc-700">Ver</a>
                                        <a href="{{ route('supplies.edit', $supply) }}" class="text-gray-500 hover:text-zinc-700">Editar</a>
                                        @can('supplies.edit')
                                            @if($supply->is_active)
                                                <button type="button"
                                                        @click="openMergeModal({{ $supply->id }}, @js($supply->name), @js($supply->code))"
                                                        class="text-orange-500 hover:text-orange-700">
                                                    Unificar
                                                </button>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($supplies->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200">
                        {{ $supplies->links() }}
                    </div>
                @endif
            @else
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay insumos</h3>
                    <p class="mt-1 text-sm text-gray-500">Comenzá registrando tu primer insumo.</p>
                    <div class="mt-4">
                        <a href="{{ route('supplies.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">
                            Nuevo Insumo
                        </a>
                    </div>
                </div>
            @endif
        </div>
        </div>

        {{-- Modal Unificar Insumo --}}
        <div x-show="mergeModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="mergeModal = false">
            <div class="absolute inset-0 bg-black/50" @click="mergeModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg" @click.stop>

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Unificar insumo</h2>
                        <p class="text-sm text-gray-500 mt-0.5">El insumo origen quedará inactivo y sus referencias pasarán al destino.</p>
                    </div>
                    <button type="button" @click="mergeModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">

                    <div class="p-4 bg-orange-50 border border-orange-200 rounded-lg">
                        <p class="text-xs font-semibold text-orange-700 uppercase tracking-wide mb-1">Insumo a desactivar (origen)</p>
                        <p class="text-sm font-medium text-gray-900" x-text="mergeSource.code + ' — ' + mergeSource.name"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Insumo destino <span class="text-red-500">*</span>
                            <span class="text-gray-400 font-normal">(sus referencias se conservan, recibe el stock)</span>
                        </label>
                        <div class="relative">
                            <input type="text" x-model="mergeSearch"
                                   @input.debounce.300ms="searchMergeTarget()"
                                   placeholder="Buscar por nombre o código..."
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <template x-if="mergeSearchResults.length > 0">
                                <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-52 overflow-y-auto">
                                    <template x-for="s in mergeSearchResults" :key="s.id">
                                        <button type="button" @click="selectMergeTarget(s)"
                                                class="w-full text-left px-4 py-2.5 hover:bg-gray-50 border-b border-gray-100 last:border-0">
                                            <span class="text-xs text-gray-400 font-mono" x-text="s.code"></span>
                                            <span class="text-sm text-gray-800 ml-2" x-text="s.name"></span>
                                            <template x-if="s.brand">
                                                <span class="text-xs text-gray-400 ml-1" x-text="'— ' + s.brand"></span>
                                            </template>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <template x-if="mergeTarget">
                            <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <div>
                                    <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">Destino seleccionado</p>
                                    <p class="text-sm text-gray-900" x-text="mergeTarget.code + ' — ' + mergeTarget.name"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <template x-if="mergePreviewLoading">
                        <p class="text-sm text-gray-400 text-center py-2">Calculando referencias...</p>
                    </template>
                    <template x-if="mergePreviewData && !mergePreviewLoading">
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-xs font-semibold text-yellow-800 uppercase tracking-wide mb-2">Referencias que se reasignarán</p>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li x-show="mergePreviewData.stock_movements > 0">
                                    · <span x-text="mergePreviewData.stock_movements"></span> movimientos de stock
                                </li>
                                <li x-show="mergePreviewData.purchase_invoice_items > 0">
                                    · <span x-text="mergePreviewData.purchase_invoice_items"></span> ítems de factura de compra
                                </li>
                                <li x-show="mergePreviewData.purchase_order_items > 0">
                                    · <span x-text="mergePreviewData.purchase_order_items"></span> ítems de orden de compra
                                </li>
                                <li x-show="mergePreviewData.delivery_note_items > 0">
                                    · <span x-text="mergePreviewData.delivery_note_items"></span> ítems de remito
                                </li>
                                <li x-show="mergePreviewData.purchase_quotation_request_items > 0">
                                    · <span x-text="mergePreviewData.purchase_quotation_request_items"></span> ítems de cotización
                                </li>
                                <li x-show="mergePreviewData.purchase_credit_note_items > 0">
                                    · <span x-text="mergePreviewData.purchase_credit_note_items"></span> ítems de nota de crédito
                                </li>
                                <li x-show="mergePreviewData.branch_stocks > 0">
                                    · Stock de <span x-text="mergePreviewData.branch_stocks"></span> sede(s) sumado al destino
                                </li>
                            </ul>
                            <p class="text-xs text-yellow-700 mt-3 font-medium">⚠️ Esta acción es irreversible.</p>
                        </div>
                    </template>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" @click="mergeModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <form :action="'{{ route('supplies.merge', ['supply' => '__ID__']) }}'.replace('__ID__', mergeSource.id)" method="POST"
                          @submit.prevent="if(mergeTarget) { mergeSubmitting = true; $el.submit(); }">
                        @csrf
                        <input type="hidden" name="target_id" :value="mergeTarget?.id">
                        <button type="submit"
                                :disabled="!mergeTarget || mergeSubmitting"
                                class="px-5 py-2 text-sm font-semibold text-white bg-orange-600 rounded-lg hover:bg-orange-700 disabled:opacity-50 transition-colors">
                            <span x-show="!mergeSubmitting">Confirmar unificación</span>
                            <span x-show="mergeSubmitting">Procesando...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
