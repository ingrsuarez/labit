<x-admin-layout>
    @php
        $isAfipDraft = $invoice->status === 'pendiente' && $invoice->is_electronic && ! $invoice->cae;
        $isAfipRejected = $invoice->is_electronic && ! $invoice->cae && $invoice->afip_result === 'R';
    @endphp
    <div class="p-4 md:p-6" x-data="invoiceEditForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ $isAfipDraft ? 'Editar borrador — pendiente AFIP' : 'Editar '.$invoice->full_number }}
                </h1>
                <p class="text-gray-500 text-sm mt-1">
                    {{ $isAfipDraft ? 'Borrador editable: agregá líneas extras y enviá a AFIP cuando esté listo.' : 'Modificar factura de venta' }}
                </p>
            </div>
            <a href="{{ route('sales-invoices.show', $invoice) }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        @if($isAfipDraft)
            <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-amber-900">
                            {{ $isAfipRejected ? 'Borrador — AFIP rechazó el envío anterior' : 'Borrador — pendiente de envío a AFIP' }}
                        </h3>
                        <p class="text-sm text-amber-800 mt-1">
                            Esta factura todavía no fue emitida ante AFIP. Podés editar items, agregar líneas extras
                            (toma de muestra, flete, descuentos, etc.) o cambiar datos. Cuando confirmes, se enviará
                            automáticamente y se obtendrá el CAE.
                        </p>
                        @if($isAfipRejected && !empty($invoice->afip_response))
                            @php
                                $editObs = $invoice->afip_response['FeDetResp']['FECAEDetResponse']['Observaciones']['Obs'] ?? null;
                                if ($editObs && !isset($editObs[0])) $editObs = [$editObs];
                            @endphp
                            @if(isset($invoice->afip_response['error']))
                                <p class="text-sm text-red-700 mt-2 font-medium">Error: {{ $invoice->afip_response['error'] }}</p>
                            @endif
                            @if($editObs)
                                <div class="mt-2 text-sm text-red-700">
                                    <p class="font-medium">Observaciones AFIP:</p>
                                    <ul class="list-disc list-inside mt-1">
                                        @foreach($editObs as $ob)
                                            <li><strong>{{ $ob['Code'] ?? '' }}</strong>: {{ $ob['Msg'] ?? '' }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <details class="mt-2 text-xs text-amber-700">
                                <summary class="cursor-pointer underline">Ver respuesta completa AFIP (JSON)</summary>
                                <pre class="mt-1 whitespace-pre-wrap break-words">{{ json_encode($invoice->afip_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="invoice-edit-form" method="POST" action="{{ route('sales-invoices.update', $invoice) }}">
            @csrf
            @method('PUT')

            @if($invoice->quote_id)
                <input type="hidden" name="quote_id" value="{{ $invoice->quote_id }}">
            @endif
            @if($invoice->admission_id)
                <input type="hidden" name="admission_id" value="{{ $invoice->admission_id }}">
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Comprobante</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 [&>div]:min-w-0">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Comprobante <span class="text-red-500">*</span></label>
                        <select name="voucher_type" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="A" {{ old('voucher_type', $invoice->voucher_type) === 'A' ? 'selected' : '' }}>Factura A</option>
                            <option value="B" {{ old('voucher_type', $invoice->voucher_type) === 'B' ? 'selected' : '' }}>Factura B</option>
                            <option value="C" {{ old('voucher_type', $invoice->voucher_type) === 'C' ? 'selected' : '' }}>Factura C</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Punto de Venta <span class="text-red-500">*</span></label>
                        <select name="point_of_sale_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($pointsOfSale as $pos)
                                <option value="{{ $pos->id }}" {{ old('point_of_sale_id', $invoice->point_of_sale_id) == $pos->id ? 'selected' : '' }}>{{ $pos->code }} - {{ $pos->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            N° Factura @unless($isAfipDraft)<span class="text-red-500">*</span>@endunless
                        </label>
                        @if($isAfipDraft)
                            <input type="text" value="{{ $invoice->invoice_number }}" readonly
                                   class="w-full rounded-lg border-gray-200 bg-gray-50 text-sm text-gray-500">
                            <p class="text-[11px] text-gray-500 mt-1">AFIP asignará el número al enviar.</p>
                        @else
                            <input type="text" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" required
                                   placeholder="Ej: 00012345"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
                        <select name="customer_id" required class="w-full min-w-0 rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" {{ old('customer_id', $invoice->customer_id) == $cust->id ? 'selected' : '' }}>{{ $cust->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Emisión <span class="text-red-500">*</span></label>
                        <input type="date" name="issue_date" value="{{ old('issue_date', $invoice->issue_date->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
                        <input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Percepciones</label>
                        <input type="number" name="percepciones" x-model.number="percepciones" value="{{ old('percepciones', $invoice->percepciones) }}" min="0" step="0.01"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Otros Impuestos</label>
                        <input type="number" name="otros_impuestos" x-model.number="otrosImpuestos" value="{{ old('otros_impuestos', $invoice->otros_impuestos) }}" min="0" step="0.01"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">{{ old('notes', $invoice->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Ítems de la Factura</h2>
                    <button type="button" @click="addItem()"
                            class="inline-flex items-center px-3 py-1.5 bg-zinc-700 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar Ítem
                    </button>
                </div>

                <div x-show="items.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descripción</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Cantidad</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Precio Unit.</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Tasa IVA</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">IVA</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Total</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2">
                                        <input type="hidden" :name="'items[' + index + '][description]'" :value="item.description">
                                        <input type="hidden" :name="'items[' + index + '][test_id]'" :value="item.test_id || ''">
                                        <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                                        <input type="hidden" :name="'items[' + index + '][unit_price]'" :value="item.unit_price">
                                        <input type="hidden" :name="'items[' + index + '][iva_rate]'" :value="item.iva_rate">
                                        <input type="text" x-model="item.description" required placeholder="Descripción del ítem"
                                               class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="item.quantity" min="1" step="1" required
                                               class="w-24 rounded border-gray-300 text-sm text-center focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="item.unit_price" min="0" step="0.01" required
                                               class="w-32 rounded border-gray-300 text-sm text-right focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <select x-model="item.iva_rate" required
                                                class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                            <option value="0">0%</option>
                                            <option value="10.5">10,5%</option>
                                            <option value="21">21%</option>
                                            <option value="27">27%</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-700">
                                        <span x-text="'$' + formatMoney(itemIva(item))"></span>
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-medium text-gray-800">
                                        <span x-text="'$' + formatMoney(itemTotal(item))"></span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Subtotal (Neto Gravado)</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(subtotal)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva105 > 0">
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 10,5%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva105)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva21 > 0">
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 21%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva21)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva27 > 0">
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 27%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva27)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="percepciones > 0">
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Percepciones</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(percepciones)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="otrosImpuestos > 0">
                                <td colspan="5" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Otros Impuestos</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(otrosImpuestos)"></td>
                                <td></td>
                            </tr>
                            <tr class="border-t-2 border-gray-300">
                                <td colspan="5" class="px-3 py-3 text-right text-sm font-bold text-gray-800">TOTAL</td>
                                <td class="px-3 py-3 text-right text-base font-bold text-gray-900" x-text="'$' + formatMoney(grandTotal)"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div x-show="items.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Agregá ítems a la factura usando el botón de arriba
                </div>
            </div>

        </form>

        <div class="flex flex-wrap justify-end items-center gap-3">
            <a href="{{ route('sales-invoices.show', $invoice) }}" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 text-sm">Cancelar</a>

            <button type="submit" form="invoice-edit-form" :disabled="items.length === 0"
                    class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                Guardar cambios
            </button>

            @if($invoice->is_electronic && ! $invoice->cae)
                @php
                    $isRetry = $invoice->afip_result === 'R';
                    $btnLabel = $isRetry ? 'Reintentar AFIP' : 'Enviar a AFIP';
                @endphp
                <form method="POST" action="{{ route('sales-invoices.retry-afip', $invoice) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="px-6 py-3 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-700 transition-colors text-sm shadow-sm inline-flex items-center gap-2"
                            onclick="return confirm('Se enviará la factura a AFIP. Asegurate de haber guardado los cambios. ¿Continuar?')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        {{ $btnLabel }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    @php
        $invoiceItems = $invoice->items->map(fn($i) => [
            'description' => $i->description,
            'test_id' => $i->test_id,
            'quantity' => floatval($i->quantity),
            'unit_price' => floatval($i->unit_price),
            'iva_rate' => strval(floatval($i->iva_rate)),
        ])->values();
    @endphp
    <script>
        function invoiceEditForm() {
            return {
                items: @json($invoiceItems),
                percepciones: {{ old('percepciones', $invoice->percepciones) }},
                otrosImpuestos: {{ old('otros_impuestos', $invoice->otros_impuestos) }},

                addItem() {
                    this.items.push({ description: '', test_id: '', quantity: 1, unit_price: 0, iva_rate: '21' });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                itemIva(item) {
                    return Math.round(parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0) * parseFloat(item.iva_rate || 0) / 100 * 100) / 100;
                },

                itemTotal(item) {
                    const net = parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0);
                    return net + this.itemIva(item);
                },

                get subtotal() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0)), 0);
                },

                get iva105() {
                    return this.items.filter(i => parseFloat(i.iva_rate) === 10.5)
                        .reduce((sum, i) => sum + this.itemIva(i), 0);
                },

                get iva21() {
                    return this.items.filter(i => parseFloat(i.iva_rate) === 21)
                        .reduce((sum, i) => sum + this.itemIva(i), 0);
                },

                get iva27() {
                    return this.items.filter(i => parseFloat(i.iva_rate) === 27)
                        .reduce((sum, i) => sum + this.itemIva(i), 0);
                },

                get totalIva() {
                    return this.iva105 + this.iva21 + this.iva27;
                },

                get grandTotal() {
                    return this.subtotal + this.totalIva + parseFloat(this.percepciones || 0) + parseFloat(this.otrosImpuestos || 0);
                },

                formatMoney(val) {
                    return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
                }
            }
        }
    </script>
</x-admin-layout>
