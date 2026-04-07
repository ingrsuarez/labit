<x-admin-layout>
    <div class="p-4 md:p-6">
        @if(session('success'))
            <div class="max-w-2xl mx-auto mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif
        <div class="max-w-2xl mx-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Libro IVA Digital</h1>
                <p class="text-gray-500 mt-1">Generá los archivos TXT para importar en Portal IVA de AFIP (RG 4597)</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <form action="{{ route('libro-iva.preview') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                            <select name="month" required class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ (old('month', now()->month) == $m) ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->locale('es')->isoFormat('MMMM') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                            <select name="year" required class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach(range(now()->year, now()->year - 2) as $y)
                                    <option value="{{ $y }}" {{ (old('year', now()->year) == $y) ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                        Ver resumen del período
                    </button>
                </form>
            </div>

            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                <p class="font-medium mb-1">Archivos que se generan:</p>
                <ul class="list-disc list-inside space-y-1 text-blue-600">
                    <li>Ventas — Cabecera de comprobantes</li>
                    <li>Ventas — Alícuotas de IVA</li>
                    <li>Compras — Cabecera de comprobantes</li>
                    <li>Compras — Alícuotas de IVA</li>
                </ul>
            </div>
        </div>
    </div>
</x-admin-layout>
