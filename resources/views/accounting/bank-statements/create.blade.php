<x-admin-layout>
<div class="container mx-auto px-4 py-8 max-w-xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Importar Extracto Bancario</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $bankAccount->display_name }}</p>
    </div>

    @if(session('error'))
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <form action="{{ route('accounting.bank-statements.store', $bankAccount) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf

        <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600 space-y-1">
            <p><strong>Banco:</strong> {{ $bankAccount->bank_name }}</p>
            <p><strong>Cuenta:</strong> {{ $bankAccount->account_number }} ({{ $bankAccount->account_type === 'cuenta_corriente' ? 'Cuenta Corriente' : 'Caja de Ahorro' }})</p>
            <p><strong>Moneda:</strong> {{ $bankAccount->currency }}</p>
        </div>

        <div>
            <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Archivo XLS del banco *</label>
            <input type="file" name="file" id="file" required accept=".xls,.xlsx" class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200">
            <p class="text-xs text-gray-400 mt-2">Subí el archivo XLS descargado desde el homebanking. Formato soportado: BBVA Francés (.xls, .xlsx). Máx. 5 MB.</p>
            @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('accounting.bank-statements.index', $bankAccount) }}" class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="px-4 py-2 text-sm text-white bg-zinc-800 rounded-lg hover:bg-zinc-700">Importar</button>
        </div>
    </form>
</div>
</x-admin-layout>
