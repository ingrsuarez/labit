<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $supplier->name }}</h1>
                <p class="text-gray-500 text-sm mt-1">{{ $supplier->code }} {{ $supplier->business_name ? '- ' . $supplier->business_name : '' }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('suppliers.edit', $supplier) }}"
                   class="inline-flex items-center px-4 py-2 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">
                    Editar
                </a>
                <a href="{{ route('suppliers.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                    &larr; Volver
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Datos Fiscales -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos Fiscales</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">CUIT</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->tax_id ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Condición IVA</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->tax_condition_label }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Estado</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $supplier->status === 'activo' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($supplier->status) }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Contacto -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Contacto</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Email</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->email ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Teléfono</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->phone ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Persona de contacto</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->contact_name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Tel. contacto</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->contact_phone ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Dirección -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Dirección</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Dirección</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->address ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Ciudad</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->city ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Provincia</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->state ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">País</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->country ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Datos Bancarios -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos Bancarios</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">CBU</dt>
                        <dd class="text-sm font-medium text-gray-800 font-mono">{{ $supplier->cbu ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Alias</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->bank_alias ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Banco</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $supplier->bank_name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        @if($supplier->notes)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mt-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Notas</h2>
                <p class="text-sm text-gray-600">{{ $supplier->notes }}</p>
            </div>
        @endif
    </div>
</x-admin-layout>
