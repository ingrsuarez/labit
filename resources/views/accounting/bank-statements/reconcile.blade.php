@php
$categoryColors = [
    'transferencia' => 'bg-blue-100 text-blue-700',
    'cobro' => 'bg-green-100 text-green-700',
    'cheque' => 'bg-purple-100 text-purple-700',
    'impuesto' => 'bg-red-100 text-red-700',
    'comision' => 'bg-orange-100 text-orange-700',
    'iva_comision' => 'bg-orange-100 text-orange-600',
    'pago_tarjeta' => 'bg-pink-100 text-pink-700',
    'pago_servicio' => 'bg-pink-100 text-pink-600',
    'debin' => 'bg-indigo-100 text-indigo-700',
    'debito_automatico' => 'bg-yellow-100 text-yellow-700',
    'pago_tarjeta_credito' => 'bg-pink-100 text-pink-700',
    'movimiento_interno' => 'bg-gray-100 text-gray-600',
];
$categoryLabels = [
    'transferencia' => 'Transferencia', 'cobro' => 'Cobro', 'cheque' => 'Cheque',
    'impuesto' => 'Impuesto', 'comision' => 'Comisión', 'iva_comision' => 'IVA Com.',
    'pago_tarjeta' => 'Pago Tarjeta', 'pago_servicio' => 'Pago Servicio',
    'debin' => 'DEBIN', 'debito_automatico' => 'Déb. Auto.',
    'pago_tarjeta_credito' => 'Pago TC', 'movimiento_interno' => 'Mov. Int.',
];
$statusLabels = ['pending' => 'Pendiente', 'matched' => 'Conciliado', 'ignored' => 'Ignorado'];

$pendingCategories = $movements->where('reconciliation_status', 'pending')
    ->whereNotNull('category')
    ->groupBy('category')
    ->map(fn($g) => $g->count())
    ->sortDesc();
@endphp

<x-admin-layout>
<div class="container mx-auto px-4 py-6" x-data="reconciliation()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Conciliación Bancaria</h1>
            <p class="text-gray-500 text-sm">{{ $bankAccount->display_name }} — {{ $statement->period_from->format('d/m/Y') }} al {{ $statement->period_to->format('d/m/Y') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('accounting.bank-statements.show', [$bankAccount, $statement]) }}" class="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">← Extracto</a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Action bar --}}
    <div class="flex flex-wrap items-center gap-3 mb-4 bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
        @can('contabilidad.reconciliation.execute')
        <form action="{{ route('accounting.reconciliation.auto', [$bankAccount, $statement]) }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 text-sm text-white bg-zinc-800 rounded-lg hover:bg-zinc-700">Conciliación Automática</button>
        </form>
        @endcan

        @can('contabilidad.reconciliation.manual')
        @if($pendingCategories->has('comision') || $pendingCategories->has('iva_comision') || $pendingCategories->has('impuesto'))
        <div class="flex gap-1">
            @foreach(['comision', 'iva_comision', 'impuesto'] as $cat)
                @if($pendingCategories->has($cat))
                <form action="{{ route('accounting.reconciliation.bulk-ignore', $statement) }}" method="POST">
                    @csrf
                    <input type="hidden" name="category" value="{{ $cat }}">
                    <button type="submit" class="px-3 py-2 text-xs border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50" title="Ignorar {{ $pendingCategories[$cat] }} movimientos">
                        Ignorar {{ $categoryLabels[$cat] ?? $cat }} ({{ $pendingCategories[$cat] }})
                    </button>
                </form>
                @endif
            @endforeach
        </div>
        @endif
        @endcan

        <div class="ml-auto flex items-center gap-3">
            <div class="flex items-center gap-2">
                <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500 rounded-full" style="width: {{ $progress['percent'] }}%"></div>
                </div>
                <span class="text-sm font-semibold text-gray-700">{{ $progress['percent'] }}%</span>
            </div>
            <span class="text-xs text-gray-500">{{ $progress['matched'] }} conciliados · {{ $progress['pending'] }} pendientes · {{ $progress['ignored'] }} ignorados</span>
        </div>
    </div>

    {{-- Two-column layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- LEFT: Bank Movements --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col" style="max-height: 75vh;">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between shrink-0">
                <h2 class="text-sm font-semibold text-gray-700">Movimientos del Banco <span class="text-gray-400">({{ $movements->count() }})</span></h2>
                <div class="flex gap-2">
                    <select x-model="filterStatus" class="text-xs rounded-lg border-gray-300 py-1 px-2">
                        <option value="">Todos</option>
                        <option value="pending">Pendiente</option>
                        <option value="matched">Conciliado</option>
                        <option value="ignored">Ignorado</option>
                    </select>
                    <select x-model="filterType" class="text-xs rounded-lg border-gray-300 py-1 px-2">
                        <option value="">Todos</option>
                        <option value="debit">Débitos</option>
                        <option value="credit">Créditos</option>
                    </select>
                </div>
            </div>
            <div class="overflow-y-auto flex-1 divide-y divide-gray-100">
                @foreach($movements as $mov)
                <div x-show="filterMovement('{{ $mov->reconciliation_status }}', {{ $mov->debit > 0 ? "'debit'" : "'credit'" }})"
                     class="px-3 py-2 hover:bg-gray-50 cursor-pointer transition-colors"
                     :class="{ 'bg-blue-50 ring-1 ring-blue-300': selectedMovement === {{ $mov->id }} }"
                     @click="selectMovement({{ $mov->id }}, '{{ $mov->reconciliation_status }}', {{ $mov->debit > 0 ? "'debit'" : "'credit'" }})">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <span class="text-xs text-gray-400 shrink-0 w-10">{{ $mov->date->format('d/m') }}</span>
                            <span class="text-sm text-gray-800 truncate">{{ $mov->concept }}</span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-2">
                            @if($mov->category)
                            <span class="hidden sm:inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium {{ $categoryColors[$mov->category] ?? 'bg-gray-100 text-gray-600' }}">{{ $categoryLabels[$mov->category] ?? $mov->category }}</span>
                            @endif
                            @if($mov->debit > 0)
                            <span class="text-sm font-mono text-red-600 w-28 text-right">-$ {{ number_format($mov->debit, 2, ',', '.') }}</span>
                            @else
                            <span class="text-sm font-mono text-green-600 w-28 text-right">+$ {{ number_format($mov->credit, 2, ',', '.') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <div class="flex items-center gap-1">
                            @if($mov->reconciliation_status === 'matched')
                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-700">Conciliado</span>
                            @if($mov->reconciledRecord)
                            <span class="text-[10px] text-green-600">{{ class_basename($mov->reconciled_type) }} #{{ $mov->reconciled_id }}</span>
                            @endif
                            @elseif($mov->reconciliation_status === 'ignored')
                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-yellow-100 text-yellow-700">Ignorado</span>
                            @if($mov->notes)<span class="text-[10px] text-gray-400 truncate max-w-[150px]">{{ $mov->notes }}</span>@endif
                            @else
                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-500">Pendiente</span>
                            @endif
                        </div>
                        <div class="flex gap-1">
                            @can('contabilidad.reconciliation.manual')
                            @if($mov->reconciliation_status === 'pending')
                            <form action="{{ route('accounting.reconciliation.ignore', $mov) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-[10px] px-2 py-0.5 rounded border border-gray-300 text-gray-500 hover:bg-yellow-50 hover:text-yellow-600" onclick="event.stopPropagation()">Ignorar</button>
                            </form>
                            @elseif($mov->reconciliation_status === 'matched')
                            <form action="{{ route('accounting.reconciliation.unlink', $mov) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-[10px] px-2 py-0.5 rounded border border-gray-300 text-gray-500 hover:bg-red-50 hover:text-red-600" onclick="event.stopPropagation()">Desvincular</button>
                            </form>
                            @endif
                            @endcan
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- RIGHT: Internal Records --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col" style="max-height: 75vh;">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between shrink-0">
                <h2 class="text-sm font-semibold text-gray-700">Registros del Sistema
                    <span class="text-gray-400">({{ $paymentOrders->count() + $collectionReceipts->count() }})</span>
                </h2>
                <div class="flex gap-2">
                    <select x-model="filterRecordType" class="text-xs rounded-lg border-gray-300 py-1 px-2">
                        <option value="">Todos</option>
                        <option value="po">Pagos (OP)</option>
                        <option value="cr">Cobros (RC)</option>
                    </select>
                    <input type="text" x-model="searchRecord" placeholder="Buscar..." class="text-xs rounded-lg border-gray-300 py-1 px-2 w-28">
                </div>
            </div>
            <div class="overflow-y-auto flex-1 divide-y divide-gray-100">
                <div x-show="selectedMovement === null" class="px-4 py-8 text-center text-sm text-gray-400">
                    Seleccioná un movimiento pendiente del panel izquierdo para vincular.
                </div>

                {{-- Payment Orders --}}
                @foreach($paymentOrders as $po)
                <div x-show="selectedMovement !== null && filterRecord('po', '{{ addslashes($po->supplier?->name ?? '') }} {{ $po->number }}')"
                     class="px-3 py-2 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-700">OP</span>
                                <span class="text-sm font-medium text-gray-800">{{ $po->number }}</span>
                                <span class="text-xs text-gray-500">{{ $po->date->format('d/m/Y') }}</span>
                            </div>
                            <p class="text-xs text-gray-500 truncate mt-0.5">{{ $po->supplier?->name }} · {{ $po->paymentMethodsLabel() }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-2">
                            <span class="text-sm font-mono text-red-600">$ {{ number_format($po->total, 2, ',', '.') }}</span>
                            @can('contabilidad.reconciliation.manual')
                            <form action="{{ route('accounting.reconciliation.link', ':mid') }}" method="POST" class="inline link-form">
                                @csrf
                                <input type="hidden" name="reconciled_type" value="PaymentOrder">
                                <input type="hidden" name="reconciled_id" value="{{ $po->id }}">
                                <button type="button" @click="submitLink($event, {{ $po->id }}, 'PaymentOrder')"
                                        :disabled="selectedMovement === null || selectedMovementStatus !== 'pending'"
                                        class="text-xs px-3 py-1 rounded-lg bg-zinc-800 text-white hover:bg-zinc-700 disabled:opacity-30 disabled:cursor-not-allowed">
                                    Vincular
                                </button>
                            </form>
                            @endcan
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- Collection Receipts --}}
                @foreach($collectionReceipts as $cr)
                <div x-show="selectedMovement !== null && filterRecord('cr', '{{ addslashes($cr->customer?->name ?? '') }} {{ $cr->number }}')"
                     class="px-3 py-2 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-700">RC</span>
                                <span class="text-sm font-medium text-gray-800">{{ $cr->number }}</span>
                                <span class="text-xs text-gray-500">{{ $cr->date->format('d/m/Y') }}</span>
                            </div>
                            <p class="text-xs text-gray-500 truncate mt-0.5">{{ $cr->customer?->name }} · {{ $cr->payment_method }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-2">
                            <span class="text-sm font-mono text-green-600">$ {{ number_format($cr->total, 2, ',', '.') }}</span>
                            @can('contabilidad.reconciliation.manual')
                            <form action="{{ route('accounting.reconciliation.link', ':mid') }}" method="POST" class="inline link-form">
                                @csrf
                                <input type="hidden" name="reconciled_type" value="CollectionReceipt">
                                <input type="hidden" name="reconciled_id" value="{{ $cr->id }}">
                                <button type="button" @click="submitLink($event, {{ $cr->id }}, 'CollectionReceipt')"
                                        :disabled="selectedMovement === null || selectedMovementStatus !== 'pending'"
                                        class="text-xs px-3 py-1 rounded-lg bg-zinc-800 text-white hover:bg-zinc-700 disabled:opacity-30 disabled:cursor-not-allowed">
                                    Vincular
                                </button>
                            </form>
                            @endcan
                        </div>
                    </div>
                </div>
                @endforeach

                <div x-show="selectedMovement !== null && {{ $paymentOrders->count() + $collectionReceipts->count() }} === 0"
                     class="px-4 py-8 text-center text-sm text-gray-400">
                    No hay registros internos pendientes de vincular.
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom summary --}}
    <div class="mt-4 bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3 flex flex-wrap items-center justify-between gap-4 text-sm">
        <div class="flex gap-6">
            <div><span class="text-gray-400">Saldo banco:</span> <span class="font-mono font-semibold">$ {{ number_format($statement->closing_balance ?? 0, 2, ',', '.') }}</span></div>
            <div><span class="text-gray-400">Créditos:</span> <span class="font-mono text-green-600">$ {{ number_format($statement->total_credits, 2, ',', '.') }}</span></div>
            <div><span class="text-gray-400">Débitos:</span> <span class="font-mono text-red-600">$ {{ number_format($statement->total_debits, 2, ',', '.') }}</span></div>
        </div>
        <div class="flex gap-4">
            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600">{{ $progress['pending'] }} pendientes</span>
            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">{{ $progress['matched'] }} conciliados</span>
            <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700">{{ $progress['ignored'] }} ignorados</span>
        </div>
    </div>
</div>

<script>
function reconciliation() {
    return {
        selectedMovement: null,
        selectedMovementStatus: null,
        selectedMovementType: null,
        filterStatus: '',
        filterType: '',
        filterRecordType: '',
        searchRecord: '',

        selectMovement(id, status, type) {
            if (this.selectedMovement === id) {
                this.selectedMovement = null;
                this.selectedMovementStatus = null;
                this.selectedMovementType = null;
            } else {
                this.selectedMovement = id;
                this.selectedMovementStatus = status;
                this.selectedMovementType = type;
            }
        },

        filterMovement(status, type) {
            if (this.filterStatus && this.filterStatus !== status) return false;
            if (this.filterType && this.filterType !== type) return false;
            return true;
        },

        filterRecord(type, text) {
            if (this.filterRecordType && this.filterRecordType !== type) return false;
            if (this.searchRecord && !text.toLowerCase().includes(this.searchRecord.toLowerCase())) return false;
            return true;
        },

        submitLink(event, recordId, recordType) {
            if (!this.selectedMovement || this.selectedMovementStatus !== 'pending') return;
            const form = event.target.closest('form');
            form.action = form.action.replace(':mid', this.selectedMovement);
            form.submit();
        }
    }
}
</script>
</x-admin-layout>
