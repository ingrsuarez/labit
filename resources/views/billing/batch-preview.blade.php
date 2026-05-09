<x-admin-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-center gap-2 mb-6">
            <a href="{{ route('billing.uninvoiced') }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="bi bi-arrow-left text-lg"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Previsualización de factura masiva</h1>
                <p class="text-gray-500 text-sm mt-1">Revisá los protocolos. En el siguiente paso vas a poder agregar líneas extras (toma de muestra, flete, etc.) antes de enviar a AFIP.</p>
            </div>
        </div>

        {{-- Resumen --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border p-4">
                <div class="text-sm text-gray-500">Protocolos incluidos</div>
                <div class="text-2xl font-bold text-gray-800">{{ count($protocolIds) }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-4">
                <div class="text-sm text-gray-500">Ítems (determinaciones)</div>
                <div class="text-2xl font-bold text-gray-800">{{ $itemCount }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-4">
                <div class="text-sm text-gray-500">Total estimado (neto)</div>
                <div class="text-2xl font-bold text-teal-600">${{ number_format($total, 2, ',', '.') }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-6">
            <form method="POST" action="{{ route('billing.batch-invoice') }}">
                @csrf
                <input type="hidden" name="protocol_type" value="{{ $protocolType }}">
                @foreach($protocolIds as $pid)
                    <input type="hidden" name="protocol_ids[]" value="{{ $pid }}">
                @endforeach

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                        <select name="customer_id" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Seleccionar cliente...</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" {{ $cust->id == $customerId ? 'selected' : '' }}>
                                    {{ $cust->displayName() }} {{ $cust->cuit ? '(' . $cust->cuit . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Punto de venta *</label>
                        <select name="point_of_sale_id" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                            @foreach($pointsOfSale as $pos)
                                <option value="{{ $pos->id }}">{{ $pos->code }} — {{ $pos->name }} {{ $pos->is_electronic ? '⚡' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de comprobante *</label>
                        <select name="voucher_type" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                            <option value="A" {{ $voucherType === 'A' ? 'selected' : '' }}>Factura A</option>
                            <option value="B" {{ $voucherType === 'B' ? 'selected' : '' }}>Factura B</option>
                            <option value="C" {{ $voucherType === 'C' ? 'selected' : '' }}>Factura C</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de emisión *</label>
                        <input type="date" name="issue_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <input type="text" name="notes" class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500"
                               placeholder="Ej: Facturación mensual marzo 2026">
                    </div>
                </div>

                {{-- Tabla de protocolos --}}
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Protocolos incluidos:</h3>
                <div class="overflow-x-auto mb-6">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Protocolo</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($protocols as $p)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-mono text-xs">{{ $p->protocol_number }}</td>
                                <td class="px-3 py-2 text-gray-600">
                                    @if($protocolType === 'sample')
                                        {{ $p->entry_date?->format('d/m/Y') }}
                                    @else
                                        {{ $p->date?->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-600 text-xs">
                                    @if($protocolType === 'admission')
                                        {{ $p->admissionTests->pluck('test.name')->filter()->implode(', ') }}
                                    @elseif($protocolType === 'sample')
                                        {{ $p->determinations->pluck('test.name')->filter()->implode(', ') }}
                                    @else
                                        {{ $p->vetTests->pluck('test.name')->filter()->implode(', ') }}
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right font-medium">
                                    @if($protocolType === 'admission')
                                        ${{ number_format($p->admissionTests->sum('price'), 2, ',', '.') }}
                                    @elseif($protocolType === 'sample')
                                        ${{ number_format($p->determinations->sum('price'), 2, ',', '.') }}
                                    @else
                                        ${{ number_format($p->vetTests->sum('price'), 2, ',', '.') }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right font-semibold text-gray-700">Total neto:</td>
                                <td class="px-3 py-2 text-right font-bold text-teal-600">${{ number_format($total, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right text-sm text-gray-500">IVA 21%:</td>
                                <td class="px-3 py-2 text-right text-sm text-gray-600">${{ number_format($total * 0.21, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right font-semibold text-gray-700">Total con IVA:</td>
                                <td class="px-3 py-2 text-right font-bold text-gray-800">${{ number_format($total * 1.21, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex justify-end gap-3">
                    <a href="{{ route('billing.uninvoiced') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors"
                            onclick="return confirm('Se creará un borrador con {{ count($protocolIds) }} protocolos. Vas a poder revisar y agregar líneas extras antes de enviar a AFIP. ¿Continuar?')">
                        <i class="bi bi-file-earmark-text mr-1"></i> Crear borrador
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
