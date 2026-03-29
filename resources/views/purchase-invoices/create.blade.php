<x-admin-layout>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <div class="p-4 md:p-6" x-data="invoiceForm()">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Nueva Factura de Compra</h1>
                <p class="text-gray-500 text-sm mt-1">Registrar factura de proveedor</p>
            </div>
            <a href="{{ route('purchase-invoices.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Volver</a>
        </div>

        @if($deliveryNote)
            <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-blue-800">
                        Generando factura a partir del Remito
                        <a href="{{ route('delivery-notes.show', $deliveryNote) }}" class="font-semibold underline hover:text-blue-900">{{ $deliveryNote->number }}</a>
                        — Proveedor: {{ $deliveryNote->supplier->name }}
                    </p>
                </div>
            </div>
        @endif

        @if($purchaseOrder)
            <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-blue-800">
                        Vinculada a la Orden de Compra
                        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="font-semibold underline hover:text-blue-900">{{ $purchaseOrder->number }}</a>
                    </p>
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

        <form method="POST" action="{{ route('purchase-invoices.store') }}">
            @csrf

            @if($deliveryNote)
                <input type="hidden" name="delivery_note_id" value="{{ $deliveryNote->id }}">
            @endif
            @if($purchaseOrder)
                <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
            @endif

            <input type="hidden" name="cae" :value="qrData?.codAut || ''">
            <input type="hidden" name="cuit_emisor" :value="qrData?.cuit || ''">
            <input type="hidden" name="qr_data" :value="qrData ? JSON.stringify(qrData) : ''">

            {{-- Escáner QR AFIP --}}
            @unless($deliveryNote)
            <div class="mb-5 bg-gray-50 border border-gray-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-gray-700 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        Escanear QR de factura recibida
                    </h3>
                    <span class="text-xs text-gray-400">(opcional)</span>
                </div>

                <div class="flex gap-3 mb-3">
                    <button type="button" @click="startCamera()" x-show="!cameraActive"
                            class="inline-flex items-center px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Usar cámara
                    </button>
                    <button type="button" @click="stopCamera()" x-show="cameraActive" x-cloak
                            class="inline-flex items-center px-3 py-2 text-sm bg-red-50 border border-red-300 text-red-700 rounded-lg hover:bg-red-100">
                        Detener cámara
                    </button>
                    <label class="inline-flex items-center px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Subir imagen
                        <input type="file" accept="image/*" @change="scanFromFile($event)" class="hidden">
                    </label>
                </div>

                <div id="qr-reader" x-show="cameraActive" x-cloak class="mb-3 max-w-sm"></div>
                <div id="qr-reader-file" style="display:none"></div>

                <div x-show="scanResult" x-cloak class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm">
                    <div class="flex items-center gap-2 text-green-700 font-medium mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        QR leído correctamente
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-gray-600">
                        <div>CUIT: <span class="font-medium" x-text="scanResult?.cuit"></span></div>
                        <div>Tipo: <span class="font-medium" x-text="scanResult?.tipoCmpLabel"></span></div>
                        <div>PV-Nro: <span class="font-medium" x-text="scanResult?.ptoVta + '-' + scanResult?.nroCmp"></span></div>
                        <div>Total: <span class="font-medium" x-text="'$' + scanResult?.importe"></span></div>
                        <div>Fecha: <span class="font-medium" x-text="scanResult?.fecha"></span></div>
                        <div>CAE: <span class="font-medium" x-text="scanResult?.codAut"></span></div>
                    </div>
                    <button type="button" @click="applyToForm()"
                            class="mt-3 inline-flex items-center px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        Aplicar al formulario
                    </button>
                </div>

                <div x-show="scanError" x-cloak class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    <span x-text="scanError"></span>
                </div>
            </div>
            @endunless

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos del Comprobante</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Comprobante <span class="text-red-500">*</span></label>
                        <select name="voucher_type" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            <option value="A" {{ old('voucher_type') === 'A' ? 'selected' : '' }}>Factura A</option>
                            <option value="B" {{ old('voucher_type') === 'B' ? 'selected' : '' }}>Factura B</option>
                            <option value="C" {{ old('voucher_type') === 'C' ? 'selected' : '' }}>Factura C</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Punto de Venta</label>
                        <input type="text" name="point_of_sale" value="{{ old('point_of_sale') }}"
                               placeholder="Ej: 00001"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N° Factura <span class="text-red-500">*</span></label>
                        <input type="text" name="invoice_number" value="{{ old('invoice_number') }}" required
                               placeholder="Ej: 00012345"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <select name="supplier_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                            <option value="">Seleccionar...</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ old('supplier_id', $selectedSupplierId ?? $deliveryNote?->supplier_id ?? $purchaseOrder?->supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Emisión <span class="text-red-500">*</span></label>
                        <input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Percepciones</label>
                        <input type="number" name="percepciones" x-model.number="percepciones" value="{{ old('percepciones', 0) }}" min="0" step="0.01"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Otros Impuestos</label>
                        <input type="number" name="otros_impuestos" x-model.number="otrosImpuestos" value="{{ old('otros_impuestos', 0) }}" min="0" step="0.01"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500"
                                  placeholder="Observaciones opcionales">{{ old('notes') }}</textarea>
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
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-40">Insumo</th>
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
                                        <input type="hidden" :name="'items[' + index + '][supply_id]'" :value="item.supply_id || ''">
                                        <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                                        <input type="hidden" :name="'items[' + index + '][unit_price]'" :value="item.unit_price">
                                        <input type="hidden" :name="'items[' + index + '][iva_rate]'" :value="item.iva_rate">
                                        <input type="hidden" :name="'items[' + index + '][lot_number]'" :value="item.lot_number || ''">
                                        <input type="hidden" :name="'items[' + index + '][expiration_date]'" :value="item.expiration_date || ''">
                                        <input type="text" x-model="item.description" required placeholder="Descripción del ítem"
                                               class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-1">
                                            <select x-model="item.supply_id" @change="onSupplyChange(item)"
                                                    class="w-full rounded border-gray-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                                                <option value="">Ninguno</option>
                                                <template x-for="supply in supplies" :key="supply.id">
                                                    <option :value="supply.id" x-text="supply.name" :selected="supply.id == item.supply_id"></option>
                                                </template>
                                            </select>
                                            <button type="button" @click="showNewSupplyModal = true; newSupplyForRow = index"
                                                    class="p-1.5 text-teal-600 hover:text-teal-800 shrink-0" title="Crear insumo nuevo">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <template x-if="getSupplyTracksLot(item.supply_id)">
                                            <div class="flex gap-2 mt-1">
                                                <input type="text" x-model="item.lot_number" placeholder="Lote"
                                                       class="w-1/2 rounded border-gray-300 text-xs focus:border-zinc-500 focus:ring-zinc-500">
                                                <input type="date" x-model="item.expiration_date"
                                                       class="w-1/2 rounded border-gray-300 text-xs focus:border-zinc-500 focus:ring-zinc-500">
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model.number="item.quantity" min="0.01" step="0.01" required
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
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Subtotal (Neto Gravado)</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(subtotal)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva105 > 0">
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 10,5%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva105)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva21 > 0">
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 21%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva21)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="iva27 > 0">
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">IVA 27%</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(iva27)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="percepciones > 0">
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Percepciones</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(percepciones)"></td>
                                <td></td>
                            </tr>
                            <tr x-show="otrosImpuestos > 0">
                                <td colspan="6" class="px-3 py-2 text-right text-sm font-medium text-gray-600">Otros Impuestos</td>
                                <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800" x-text="'$' + formatMoney(otrosImpuestos)"></td>
                                <td></td>
                            </tr>
                            <tr class="border-t-2 border-gray-300">
                                <td colspan="6" class="px-3 py-3 text-right text-sm font-bold text-gray-800">TOTAL</td>
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

            <div class="flex justify-end">
                <button type="submit" :disabled="items.length === 0"
                        class="px-6 py-3 bg-zinc-700 text-white font-semibold rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm shadow-sm">
                    Guardar Factura
                </button>
            </div>
        </form>

        {{-- Modal crear insumo --}}
        <div x-show="showNewSupplyModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             @keydown.escape.window="showNewSupplyModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6" @click.outside="showNewSupplyModal = false">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Crear Insumo Nuevo</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" x-model="newSupply.name" required
                               class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500"
                               placeholder="Nombre del insumo">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select x-model="newSupply.supply_category_id"
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Sin categoría</option>
                            @foreach(\App\Models\SupplyCategory::active()->orderBy('name')->get() as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unidad *</label>
                        <select x-model="newSupply.unit" required
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                            <option value="unidad">Unidad</option>
                            <option value="litro">Litro</option>
                            <option value="kg">Kilogramo</option>
                            <option value="caja">Caja</option>
                            <option value="pack">Pack</option>
                            <option value="metro">Metro</option>
                            <option value="rollo">Rollo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                        <input type="text" x-model="newSupply.brand"
                               class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500"
                               placeholder="Opcional">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock mínimo</label>
                        <input type="number" x-model.number="newSupply.min_stock" min="0" step="1"
                               class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" x-model="newSupply.tracks_lot" id="newSupplyTracksLot"
                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        <label for="newSupplyTracksLot" class="text-sm text-gray-700">¿Controla lote/vencimiento?</label>
                    </div>
                </div>
                <div x-show="newSupplyError" class="mt-3 text-sm text-red-600" x-text="newSupplyError"></div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" @click="showNewSupplyModal = false"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                    <button type="button" @click="createSupply()" :disabled="newSupplySaving"
                            class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50">
                        <span x-show="!newSupplySaving">Crear Insumo</span>
                        <span x-show="newSupplySaving">Creando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
        $deliveryNoteItemsJson = $deliveryNote
            ? $deliveryNote->items->map(function ($i) {
                return [
                    'description' => $i->supply ? $i->supply->name : ('Ítem remito - Cant: ' . $i->quantity_received),
                    'supply_id' => $i->supply_id,
                    'quantity' => floatval($i->quantity_received),
                    'unit_price' => $i->supply ? floatval($i->supply->last_price ?? 0) : 0,
                    'iva_rate' => '21',
                ];
            })->values()
            : collect();
    @endphp
    <script>
        function invoiceForm() {
            const deliveryNoteItems = @json($deliveryNoteItemsJson);

            return {
                items: deliveryNoteItems.length > 0 ? [...deliveryNoteItems.map(i => ({...i, lot_number: '', expiration_date: ''}))] : [],
                percepciones: {{ old('percepciones', 0) }},
                otrosImpuestos: {{ old('otros_impuestos', 0) }},
                supplies: @json(\App\Models\Supply::active()->orderBy('name')->get()->map(function ($s) { return ['id' => $s->id, 'name' => $s->code . ' - ' . $s->name, 'tracks_lot' => $s->tracks_lot]; })),

                // QR Scanner
                cameraActive: false,
                scanner: null,
                scanResult: null,
                scanError: null,
                qrData: null,

                decodeAfipQr(decodedText) {
                    this.scanError = null;
                    try {
                        let base64Data;
                        if (decodedText.includes('afip.gob.ar/fe/qr')) {
                            const url = new URL(decodedText);
                            base64Data = url.searchParams.get('p');
                        } else {
                            base64Data = decodedText;
                        }
                        if (!base64Data) throw new Error('Sin datos');
                        const json = JSON.parse(atob(base64Data));
                        const tipoMap = {1:'A',2:'A',3:'A',6:'B',7:'B',8:'B',11:'C',12:'C',13:'C'};
                        const tipoCmpLabels = {1:'Factura A',2:'ND A',3:'NC A',6:'Factura B',7:'ND B',8:'NC B',11:'Factura C',12:'ND C',13:'NC C'};
                        this.scanResult = {
                            ...json,
                            tipoCmpLabel: tipoCmpLabels[json.tipoCmp] || ('Tipo ' + json.tipoCmp),
                            voucherType: tipoMap[json.tipoCmp] || 'A',
                            ptoVta: String(json.ptoVta).padStart(5, '0'),
                            nroCmp: String(json.nroCmp).padStart(8, '0'),
                        };
                        this.qrData = this.scanResult;
                    } catch (e) {
                        this.scanError = 'No se pudo leer el QR. Asegurate de que sea el QR de una factura electrónica AFIP.';
                        this.scanResult = null;
                    }
                },

                startCamera() {
                    this.cameraActive = true;
                    this.$nextTick(() => {
                        this.scanner = new Html5QrcodeScanner('qr-reader', {
                            fps: 10, qrbox: { width: 250, height: 250 }, rememberLastUsedCamera: true,
                        });
                        this.scanner.render(
                            (text) => { this.decodeAfipQr(text); this.stopCamera(); },
                            () => {}
                        );
                    });
                },

                stopCamera() {
                    if (this.scanner) { this.scanner.clear().catch(() => {}); this.scanner = null; }
                    this.cameraActive = false;
                },

                async scanFromFile(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    try {
                        const html5Qr = new Html5Qrcode('qr-reader-file');
                        const result = await html5Qr.scanFile(file, true);
                        this.decodeAfipQr(result);
                        html5Qr.clear();
                    } catch (e) {
                        this.scanError = 'No se encontró un QR válido en la imagen.';
                    }
                    event.target.value = '';
                },

                applyToForm() {
                    if (!this.scanResult) return;
                    const r = this.scanResult;
                    const setVal = (name, val) => {
                        const el = document.querySelector('[name="' + name + '"]');
                        if (el) { el.value = val; el.dispatchEvent(new Event('input', {bubbles:true})); el.dispatchEvent(new Event('change', {bubbles:true})); }
                    };
                    setVal('voucher_type', r.voucherType);
                    setVal('point_of_sale', r.ptoVta);
                    setVal('invoice_number', r.nroCmp);
                    if (r.fecha) setVal('issue_date', r.fecha);
                    this.lookupSupplierByCuit(r.cuit);
                },

                async lookupSupplierByCuit(cuit) {
                    try {
                        const resp = await fetch('{{ url("suppliers/by-cuit") }}/' + cuit);
                        if (resp.ok) {
                            const supplier = await resp.json();
                            const el = document.querySelector('[name="supplier_id"]');
                            if (el && supplier.id) { el.value = supplier.id; el.dispatchEvent(new Event('change', {bubbles:true})); }
                        }
                    } catch (e) {}
                },

                showNewSupplyModal: false,
                newSupplyForRow: null,
                newSupplySaving: false,
                newSupplyError: '',
                newSupply: { name: '', supply_category_id: '', unit: 'unidad', brand: '', min_stock: 0, tracks_lot: false },

                addItem() {
                    this.items.push({ description: '', supply_id: '', quantity: 1, unit_price: 0, iva_rate: '21', lot_number: '', expiration_date: '' });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                onSupplyChange(item) {
                    if (!this.getSupplyTracksLot(item.supply_id)) {
                        item.lot_number = '';
                        item.expiration_date = '';
                    }
                },

                getSupplyTracksLot(supplyId) {
                    if (!supplyId) return false;
                    const s = this.supplies.find(s => s.id == supplyId);
                    return s ? s.tracks_lot : false;
                },

                async createSupply() {
                    if (!this.newSupply.name.trim()) {
                        this.newSupplyError = 'El nombre es obligatorio.';
                        return;
                    }
                    this.newSupplySaving = true;
                    this.newSupplyError = '';
                    try {
                        const resp = await fetch('{{ route("supplies.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(this.newSupply),
                        });
                        if (!resp.ok) {
                            const err = await resp.json();
                            this.newSupplyError = err.message || 'Error al crear el insumo.';
                            return;
                        }
                        const data = await resp.json();
                        this.supplies.push({ id: data.id, name: data.code + ' - ' + data.name, tracks_lot: data.tracks_lot });
                        if (this.newSupplyForRow !== null && this.items[this.newSupplyForRow]) {
                            this.items[this.newSupplyForRow].supply_id = data.id;
                            this.items[this.newSupplyForRow].description = this.items[this.newSupplyForRow].description || data.name;
                        }
                        this.showNewSupplyModal = false;
                        this.newSupply = { name: '', supply_category_id: '', unit: 'unidad', brand: '', min_stock: 0, tracks_lot: false };
                    } catch (e) {
                        this.newSupplyError = 'Error de red al crear el insumo.';
                    } finally {
                        this.newSupplySaving = false;
                    }
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
