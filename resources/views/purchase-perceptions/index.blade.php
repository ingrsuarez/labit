<x-admin-layout>
    <div class="p-4 md:p-6" x-data="{
        showModal: false,
        editingId: null,
        form: { name: '', jurisdiction: '', rate: 0, accounting_account_id: '', tax_id: '', sort_order: 0, is_active: true },
        search: '',
        filterStatus: 'active',
        openCreate() {
            this.editingId = null;
            this.form = { name: '', jurisdiction: '', rate: 0, accounting_account_id: '', tax_id: '', sort_order: 0, is_active: true };
            this.showModal = true;
        },
        openEdit(id, name, jurisdiction, rate, accountId, taxId, sortOrder, isActive) {
            this.editingId = id;
            this.form = { name, jurisdiction: jurisdiction || '', rate: parseFloat(rate), accounting_account_id: accountId.toString(), tax_id: taxId ? taxId.toString() : '', sort_order: sortOrder, is_active: isActive };
            this.showModal = true;
        },
    }">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <nav class="text-sm text-gray-500 mb-1">
                    <a href="{{ route('purchase-invoices.index') }}" class="hover:text-gray-700">Compras</a>
                    <span class="mx-1">›</span>
                    <span class="text-gray-700 font-medium">Percepciones</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-800">Percepciones de compra</h1>
                <p class="text-gray-500 text-sm mt-1">Tipos de percepciones sufridas en facturas de compra</p>
            </div>
            <div class="mt-3 md:mt-0 flex gap-2">
                <a href="{{ route('purchase-perceptions.balances') }}"
                   class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="bi bi-bar-chart me-2"></i> Ver saldos
                </a>
                @can('purchase-perceptions.create')
                <button type="button" @click="openCreate()"
                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    <i class="bi bi-plus-lg me-2"></i> Nueva percepción
                </button>
                @endcan
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        {{-- Filtros --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4 flex flex-wrap gap-3 items-center">
            <input type="text" x-model="search" placeholder="Buscar por nombre o jurisdicción..."
                class="flex-1 min-w-[200px] rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <select x-model="filterStatus" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="active">Activas</option>
                <option value="inactive">Inactivas</option>
                <option value="all">Todas</option>
            </select>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($perceptions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jurisdicción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alícuota</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuenta contable</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impuesto (DDJJ)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($perceptions as $perception)
                            <tr class="hover:bg-gray-50 transition-colors"
                                x-show="
                                    (filterStatus === 'all' || (filterStatus === 'active' && {{ $perception->is_active ? 'true' : 'false' }}) || (filterStatus === 'inactive' && {{ !$perception->is_active ? 'true' : 'false' }}))
                                    && (search === '' || '{{ strtolower($perception->name . ' ' . $perception->jurisdiction) }}'.includes(search.toLowerCase()))
                                ">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $perception->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $perception->jurisdiction ?: '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $perception->rate > 0 ? number_format($perception->rate, 2, ',', '.').'%' : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    @if($perception->accountingAccount)
                                        <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded mr-1">{{ $perception->accountingAccount->code }}</span>
                                        {{ $perception->accountingAccount->name }}
                                    @else
                                        <span class="text-red-500 text-xs">Sin cuenta</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $perception->tax?->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if($perception->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activa</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactiva</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                    @can('purchase-perceptions.edit')
                                    <button type="button"
                                        @click="openEdit({{ $perception->id }}, {{ json_encode($perception->name) }}, {{ json_encode($perception->jurisdiction) }}, {{ $perception->rate }}, {{ $perception->accounting_account_id }}, {{ json_encode($perception->tax_id) }}, {{ $perception->sort_order }}, {{ $perception->is_active ? 'true' : 'false' }})"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('purchase-perceptions.toggle-active', $perception) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="text-gray-500 hover:text-gray-700 text-sm"
                                            title="{{ $perception->is_active ? 'Desactivar' : 'Activar' }}">
                                            <i class="bi {{ $perception->is_active ? 'bi-toggle-on text-green-600' : 'bi-toggle-off text-gray-400' }}"></i>
                                        </button>
                                    </form>
                                    @endcan
                                    @can('purchase-perceptions.destroy')
                                    <form method="POST" action="{{ route('purchase-perceptions.destroy', $perception) }}" class="inline"
                                        onsubmit="return confirm('¿Eliminar esta percepción?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-200">
                    {{ $perceptions->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <i class="bi bi-percent text-4xl text-gray-300"></i>
                    <p class="mt-3 text-gray-500">No hay percepciones configuradas para esta empresa.</p>
                    @can('purchase-perceptions.create')
                    <button type="button" @click="openCreate()"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        <i class="bi bi-plus-lg me-1"></i> Agregar la primera
                    </button>
                    @endcan
                </div>
            @endif
        </div>

        {{-- Modal dentro del mismo x-data que los botones --}}
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 z-10">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Editar percepción' : 'Nueva percepción'"></h3>
                    <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                {{-- Formulario crear --}}
                <template x-if="!editingId">
                    <form method="POST" action="{{ route('purchase-perceptions.store') }}" class="px-6 py-5 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="form.name" required maxlength="100" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jurisdicción</label>
                            <input type="text" name="jurisdiction" x-model="form.jurisdiction" maxlength="100" placeholder="ej: Neuquén, CABA" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Alícuota %</label>
                                <input type="number" name="rate" x-model="form.rate" step="0.01" min="0" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                                <input type="number" name="sort_order" x-model="form.sort_order" min="0" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta contable <span class="text-red-500">*</span></label>
                            <select name="accounting_account_id" x-model="form.accounting_account_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seleccionar cuenta...</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Impuesto (DDJJ)</label>
                            <select name="tax_id" x-model="form.tax_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— Sin asociar —</option>
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax->id }}">{{ $tax->name }}{{ $tax->jurisdiction ? ' ('.$tax->jurisdiction.')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" id="create_is_active" value="1" x-model="form.is_active" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="create_is_active" class="text-sm text-gray-700">Activa</label>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700">Guardar</button>
                        </div>
                    </form>
                </template>

                {{-- Formulario editar (genera la URL dinámica con JS) --}}
                <template x-if="editingId">
                    <form method="POST" :action="`/purchase-perceptions/${editingId}`" class="px-6 py-5 space-y-4">
                        @csrf @method('PUT')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="form.name" required maxlength="100" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jurisdicción</label>
                            <input type="text" name="jurisdiction" x-model="form.jurisdiction" maxlength="100" placeholder="ej: Neuquén, CABA" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Alícuota %</label>
                                <input type="number" name="rate" x-model="form.rate" step="0.01" min="0" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                                <input type="number" name="sort_order" x-model="form.sort_order" min="0" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta contable <span class="text-red-500">*</span></label>
                            <select name="accounting_account_id" x-model="form.accounting_account_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seleccionar cuenta...</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Impuesto (DDJJ)</label>
                            <select name="tax_id" x-model="form.tax_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— Sin asociar —</option>
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax->id }}">{{ $tax->name }}{{ $tax->jurisdiction ? ' ('.$tax->jurisdiction.')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" id="edit_is_active" value="1" x-model="form.is_active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="edit_is_active" class="text-sm text-gray-700">Activa</label>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700">Guardar</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>
</x-admin-layout>
