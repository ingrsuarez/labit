<x-admin-layout>
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Nueva Cuenta Bancaria</h1>
        <p class="text-gray-500 text-sm mt-1">Registrar una cuenta bancaria para importar extractos</p>
    </div>

    <form action="{{ route('accounting.bank-accounts.store') }}" method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1">Empresa *</label>
            <select name="company_id" id="company_id" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-zinc-500 focus:border-zinc-500">
                @foreach($companies as $company)
                <option value="{{ $company->id }}" {{ old('company_id', session('current_company_id')) == $company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                </option>
                @endforeach
            </select>
            @error('company_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Banco *</label>
                <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}" required maxlength="100" placeholder="ej: BBVA Francés" class="w-full rounded-lg border-gray-300 text-sm focus:ring-zinc-500 focus:border-zinc-500">
                @error('bank_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="account_number" class="block text-sm font-medium text-gray-700 mb-1">N° de Cuenta *</label>
                <input type="text" name="account_number" id="account_number" value="{{ old('account_number') }}" required maxlength="50" placeholder="ej: 217-012516/1" class="w-full rounded-lg border-gray-300 text-sm focus:ring-zinc-500 focus:border-zinc-500">
                @error('account_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="account_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cuenta *</label>
                <select name="account_type" id="account_type" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-zinc-500 focus:border-zinc-500">
                    <option value="cuenta_corriente" {{ old('account_type') === 'cuenta_corriente' ? 'selected' : '' }}>Cuenta Corriente</option>
                    <option value="caja_ahorro" {{ old('account_type') === 'caja_ahorro' ? 'selected' : '' }}>Caja de Ahorro</option>
                </select>
            </div>
            <div>
                <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Moneda</label>
                <select name="currency" id="currency" class="w-full rounded-lg border-gray-300 text-sm focus:ring-zinc-500 focus:border-zinc-500">
                    <option value="ARS" {{ old('currency', 'ARS') === 'ARS' ? 'selected' : '' }}>ARS (Pesos)</option>
                    <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD (Dólares)</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="cbu" class="block text-sm font-medium text-gray-700 mb-1">CBU</label>
                <input type="text" name="cbu" id="cbu" value="{{ old('cbu') }}" maxlength="22" placeholder="22 dígitos" class="w-full rounded-lg border-gray-300 text-sm focus:ring-zinc-500 focus:border-zinc-500 font-mono">
                @error('cbu') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="alias" class="block text-sm font-medium text-gray-700 mb-1">Alias CBU</label>
                <input type="text" name="alias" id="alias" value="{{ old('alias') }}" maxlength="50" placeholder="ej: mi.cuenta.pesos" class="w-full rounded-lg border-gray-300 text-sm focus:ring-zinc-500 focus:border-zinc-500">
            </div>
        </div>

        <div>
            <label for="accounting_account_id" class="block text-sm font-medium text-gray-700 mb-1">Cuenta Contable Vinculada</label>
            <select name="accounting_account_id" id="accounting_account_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-zinc-500 focus:border-zinc-500">
                <option value="">— Sin vincular —</option>
                @foreach($accountingAccounts as $acc)
                <option value="{{ $acc->id }}" {{ old('accounting_account_id') == $acc->id ? 'selected' : '' }}>
                    {{ $acc->code }} — {{ $acc->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('accounting.bank-accounts.index') }}" class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="px-4 py-2 text-sm text-white bg-zinc-800 rounded-lg hover:bg-zinc-700">Guardar</button>
        </div>
    </form>
</div>
</x-admin-layout>
