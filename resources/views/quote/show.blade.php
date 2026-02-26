<x-lab-layout title="Presupuesto {{ $quote->quote_number }}">
    <div class="p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-800">{{ $quote->quote_number }}</h1>
                    @php
                        $colors = [
                            'draft' => 'bg-gray-100 text-gray-700',
                            'sent' => 'bg-blue-100 text-blue-700',
                            'accepted' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $colors[$quote->status] }}">
                        {{ $quote->status_label }}
                    </span>
                </div>
                <p class="text-gray-500 text-sm mt-1">Creado el {{ $quote->created_at->format('d/m/Y H:i') }} por {{ $quote->creator->name ?? 'Sistema' }}</p>
            </div>
            <div class="mt-3 md:mt-0">
                <a href="{{ route('quotes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                    &larr; Volver al listado
                </a>
            </div>
        </div>

        <!-- Mensajes -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Acciones -->
        <div class="flex flex-wrap gap-2 mb-6">
            @if($quote->status === 'draft')
                <a href="{{ route('quotes.edit', $quote) }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                </a>
            @endif

            <a href="{{ route('quotes.pdf', $quote) }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Descargar PDF
            </a>

            @if($quote->customer_email)
                <form method="POST" action="{{ route('quotes.sendEmail', $quote) }}" class="inline"
                      onsubmit="return confirm('¿Enviar presupuesto a {{ $quote->customer_email }}?')">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Enviar por Email
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('quotes.duplicate', $quote) }}" class="inline">
                @csrf
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Duplicar
                </button>
            </form>

            <!-- Cambiar estado -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    Cambiar Estado
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                    @foreach(['draft' => 'Borrador', 'sent' => 'Enviado', 'accepted' => 'Aceptado', 'rejected' => 'Rechazado'] as $status => $label)
                        @if($status !== $quote->status)
                            <form method="POST" action="{{ route('quotes.updateStatus', $quote) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $status }}">
                                <button type="submit" class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 transition-colors {{ $loop->first ? 'rounded-t-lg' : '' }} {{ $loop->last ? 'rounded-b-lg' : '' }}">
                                    {{ $label }}
                                </button>
                            </form>
                        @endif
                    @endforeach
                </div>
            </div>

            @if($quote->status === 'draft')
                <form method="POST" action="{{ route('quotes.destroy', $quote) }}" class="inline"
                      onsubmit="return confirm('¿Eliminar este presupuesto?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Eliminar
                    </button>
                </form>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Datos del cliente -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Cliente</h2>
                <div class="space-y-2">
                    <p class="text-gray-800 font-semibold">{{ $quote->customer_name }}</p>
                    @if($quote->customer_email)
                        <p class="text-sm text-gray-500 flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ $quote->customer_email }}
                        </p>
                    @endif
                    @if($quote->customer && $quote->customer->phone)
                        <p class="text-sm text-gray-500 flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ $quote->customer->phone }}
                        </p>
                    @endif
                </div>
            </div>

            <!-- Detalles -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Detalles</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Fecha</span>
                        <span class="text-gray-800">{{ $quote->created_at->format('d/m/Y') }}</span>
                    </div>
                    @if($quote->valid_until)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Válido hasta</span>
                            <span class="text-gray-800 {{ $quote->valid_until->isPast() ? 'text-red-600 font-medium' : '' }}">
                                {{ $quote->valid_until->format('d/m/Y') }}
                            </span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-500">Ítems</span>
                        <span class="text-gray-800">{{ $quote->items->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Totales -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Totales</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="text-gray-800">${{ number_format($quote->subtotal, 2, ',', '.') }}</span>
                    </div>
                    @if($quote->tax_rate > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-500">IVA ({{ $quote->tax_rate }}%)</span>
                            <span class="text-gray-800">${{ number_format($quote->tax_amount, 2, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="border-t border-gray-200 pt-2 flex justify-between">
                        <span class="text-gray-800 font-semibold">Total</span>
                        <span class="text-xl font-bold text-teal-700">${{ number_format($quote->total, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de ítems -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mt-5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-8">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">Cant.</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">P. Unitario</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($quote->items as $index => $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-400">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800">{{ $item->description }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 text-center">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 text-right">${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800 font-semibold text-right">${{ number_format($item->total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right text-sm font-medium text-gray-600">Subtotal</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800">${{ number_format($quote->subtotal, 2, ',', '.') }}</td>
                        </tr>
                        @if($quote->tax_rate > 0)
                            <tr>
                                <td colspan="4" class="px-4 py-2 text-right text-sm font-medium text-gray-600">IVA ({{ $quote->tax_rate }}%)</td>
                                <td class="px-4 py-2 text-right text-sm font-semibold text-gray-800">${{ number_format($quote->tax_amount, 2, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr class="border-t-2 border-gray-300">
                            <td colspan="4" class="px-4 py-3 text-right text-sm font-bold text-gray-800">TOTAL</td>
                            <td class="px-4 py-3 text-right text-lg font-bold text-teal-700">${{ number_format($quote->total, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Notas -->
        @if($quote->notes)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mt-5">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Notas</h2>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $quote->notes }}</p>
            </div>
        @endif
    </div>
</x-lab-layout>
