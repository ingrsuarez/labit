<x-admin-layout>
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Cuentas Bancarias</h1>
            <p class="text-gray-500 text-sm mt-1">Administración de cuentas bancarias por empresa</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('accounting.section') }}" class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                ← Contabilidad
            </a>
            @can('contabilidad.bank_accounts.create')
            <a href="{{ route('accounting.bank-accounts.create') }}" class="px-4 py-2 text-sm text-white bg-zinc-800 rounded-lg hover:bg-zinc-700">
                + Nueva Cuenta
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Banco</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Cuenta</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CBU</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Moneda</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cta. Contable</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Extractos</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($accounts as $account)
                <tr class="{{ !$account->is_active ? 'opacity-50' : '' }}">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $account->bank_name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 font-mono">{{ $account->account_number }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $account->account_type === 'cuenta_corriente' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                            {{ $account->account_type === 'cuenta_corriente' ? 'CC' : 'CA' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ $account->cbu ?: '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $account->currency }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $account->accountingAccount?->code }} {{ $account->accountingAccount?->name }}</td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $account->statements_count }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($account->is_active)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Activa</span>
                        @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactiva</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('accounting.bank-statements.index', $account) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50" title="Ver extractos">
                                Extractos
                            </a>
                            <a href="{{ route('accounting.bank-statements.create', $account) }}" class="text-xs px-3 py-1.5 rounded-lg bg-zinc-100 text-zinc-700 hover:bg-zinc-200" title="Importar XLS">
                                Importar
                            </a>
                            @can('contabilidad.bank_accounts.edit')
                            <a href="{{ route('accounting.bank-accounts.edit', $account) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                                Editar
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-400">No hay cuentas bancarias registradas.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-admin-layout>
