<x-lab-layout title="Protocolo {{ $vetAdmission->protocol_number }}">
    <div class="py-6 px-4 md:px-6 lg:px-8 mt-14 md:mt-0" x-data="{ showEmailModal: false }">
        <div class="mb-6">
            <a href="{{ route('vet.admissions.index') }}" class="text-amber-600 hover:text-amber-800 text-sm flex items-center mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver a Protocolos
            </a>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Protocolo {{ $vetAdmission->protocol_number }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $vetAdmission->status_color }}-100 text-{{ $vetAdmission->status_color }}-800 mt-1">
                        {{ $vetAdmission->status_label }}
                    </span>
                </div>
                <div class="flex gap-2 flex-wrap">
                    @if($vetAdmission->vetTests->where('is_validated', true)->count() > 0)
                        <a href="{{ route('vet.admissions.viewPdf', $vetAdmission) }}" target="_blank"
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                            Ver PDF
                        </a>
                        <a href="{{ route('vet.admissions.downloadPdf', $vetAdmission) }}"
                           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                            Descargar PDF
                        </a>
                        <button @click="showEmailModal = true" type="button"
                                class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium">
                            Enviar por Email
                        </button>
                    @endif
                    <form action="{{ route('vet.admissions.validateAll', $vetAdmission) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-medium">
                            Validar Todos
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif

        {{-- Info del protocolo --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-amber-700 mb-3 uppercase tracking-wider">Datos del Animal</h3>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-gray-500">Animal</dt>
                    <dd class="font-medium text-gray-900">{{ $vetAdmission->animal_name }}</dd>
                    <dt class="text-gray-500">Especie</dt>
                    <dd><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">{{ $vetAdmission->species->name ?? '-' }}</span></dd>
                    <dt class="text-gray-500">Raza</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->breed ?? '-' }}</dd>
                    <dt class="text-gray-500">Edad</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->age ?? '-' }}</dd>
                    <dt class="text-gray-500">Dueño</dt>
                    <dd class="font-medium text-gray-900">{{ $vetAdmission->owner_name }}</dd>
                    <dt class="text-gray-500">Tel. Dueño</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->owner_phone ?? '-' }}</dd>
                    @if($vetAdmission->owner_email)
                        <dt class="text-gray-500">Email</dt>
                        <dd><a href="mailto:{{ $vetAdmission->owner_email }}" class="text-amber-600 hover:text-amber-800">{{ $vetAdmission->owner_email }}</a></dd>
                    @endif
                </dl>
                @php
                    $historyCount = \App\Models\VetAdmission::where('owner_name', $vetAdmission->owner_name)
                        ->where('animal_name', $vetAdmission->animal_name)
                        ->count();
                @endphp
                @if($historyCount > 1)
                    <a href="{{ route('vet.admissions.index', ['animal' => $vetAdmission->animal_name, 'owner' => $vetAdmission->owner_name]) }}"
                       class="inline-flex items-center text-sm text-amber-600 hover:text-amber-800 mt-3">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Ver historial ({{ $historyCount }} protocolos)
                    </a>
                @endif
                </dl>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-amber-700 mb-3 uppercase tracking-wider">Datos del Protocolo</h3>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-gray-500">Fecha</dt>
                    <dd class="font-medium text-gray-900">{{ $vetAdmission->date->format('d/m/Y') }}</dd>
                    <dt class="text-gray-500">Veterinaria</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->customer->name ?? '-' }}</dd>
                    <dt class="text-gray-500">Derivante</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->veterinarian->name ?? 'Sin derivante' }}</dd>
                    <dt class="text-gray-500">Total</dt>
                    <dd class="font-bold text-amber-700">${{ number_format($vetAdmission->total_price, 2, ',', '.') }}</dd>
                    <dt class="text-gray-500">Creado por</dt>
                    <dd class="text-gray-900">{{ $vetAdmission->creator->name ?? '-' }}</dd>
                </dl>
                @if($vetAdmission->observations)
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 font-medium mb-1">Observaciones</p>
                        <p class="text-sm text-gray-700">{{ $vetAdmission->observations }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Resultados --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 text-white px-5 py-4 flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-semibold">Resultados</h2>
                    <p class="text-amber-100 text-sm">{{ $vetAdmission->vetTests->count() }} determinaciones</p>
                </div>
            </div>

            <form action="{{ route('vet.admissions.loadResults', $vetAdmission) }}" method="POST">
                @csrf
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Determinación</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-36">Resultado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Unidad</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-40">Val. Referencia</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">Método</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Estado</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Validar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($vetAdmission->vetTests->sortBy('test.code') as $idx => $vt)
                                @php
                                    $isChild = $vt->test->parentTests && $vt->test->parentTests->count() > 0;
                                    $disabled = $vt->is_validated;
                                @endphp
                                <tr class="{{ $isChild ? 'bg-gray-50/50' : '' }} {{ $vt->is_validated ? 'bg-green-50/40' : '' }}">
                                    <td class="px-4 py-2">
                                        <div class="flex items-center">
                                            @if($isChild)<span class="text-gray-300 mr-2">↳</span>@endif
                                            <div>
                                                <span class="text-xs font-mono text-gray-400">{{ $vt->test->code }}</span>
                                                <span class="text-sm font-medium text-gray-900 ml-1">{{ $vt->test->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="hidden" name="results[{{ $idx }}][id]" value="{{ $vt->id }}">
                                        <input type="text" name="results[{{ $idx }}][result]" value="{{ $vt->result }}"
                                               class="w-full border-gray-300 rounded text-sm {{ $disabled ? 'bg-gray-100' : '' }}"
                                               {{ $disabled ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" name="results[{{ $idx }}][unit]" value="{{ $vt->unit }}"
                                               class="w-full border-gray-300 rounded text-sm {{ $disabled ? 'bg-gray-100' : '' }}"
                                               {{ $disabled ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" name="results[{{ $idx }}][reference_value]" value="{{ $vt->reference_value }}"
                                               class="w-full border-gray-300 rounded text-sm {{ $disabled ? 'bg-gray-100' : '' }}"
                                               {{ $disabled ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" name="results[{{ $idx }}][method]" value="{{ $vt->method }}"
                                               class="w-full border-gray-300 rounded text-sm {{ $disabled ? 'bg-gray-100' : '' }}"
                                               {{ $disabled ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        @if($vt->is_validated)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Validado</span>
                                        @elseif($vt->result)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Completo</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        @if($vt->is_validated)
                                            <form action="{{ route('vet.admissions.unvalidateTest', [$vetAdmission, $vt]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium" title="Desvalidar">✕</button>
                                            </form>
                                        @elseif($vt->result)
                                            <form action="{{ route('vet.admissions.validateTest', [$vetAdmission, $vt]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium" title="Validar">✓</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-medium">
                        Guardar Resultados
                    </button>
                </div>
            </form>
        </div>

        {{-- Modal de envío de email --}}
        <div x-show="showEmailModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             @keydown.escape.window="showEmailModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6" @click.outside="showEmailModal = false">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Enviar Resultados por Email</h3>
                <form action="{{ route('vet.admissions.sendEmail', $vetAdmission) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email destinatario *</label>
                            <input type="email" name="email" required
                                   value="{{ $vetAdmission->owner_email ?? $vetAdmission->customer->email ?? '' }}"
                                   class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                   placeholder="email@ejemplo.com">
                            @if($vetAdmission->owner_email || $vetAdmission->customer->email || $vetAdmission->veterinarian?->email)
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if($vetAdmission->owner_email)
                                        <button type="button"
                                                onclick="this.closest('form').querySelector('input[name=email]').value='{{ $vetAdmission->owner_email }}'"
                                                class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded hover:bg-gray-200">
                                            Dueño: {{ $vetAdmission->owner_email }}
                                        </button>
                                    @endif
                                    @if($vetAdmission->customer->email)
                                        <button type="button"
                                                onclick="this.closest('form').querySelector('input[name=email]').value='{{ $vetAdmission->customer->email }}'"
                                                class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded hover:bg-gray-200">
                                            {{ $vetAdmission->customer->name }}: {{ $vetAdmission->customer->email }}
                                        </button>
                                    @endif
                                    @if($vetAdmission->veterinarian?->email)
                                        <button type="button"
                                                onclick="this.closest('form').querySelector('input[name=email]').value='{{ $vetAdmission->veterinarian->email }}'"
                                                class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded hover:bg-gray-200">
                                            {{ $vetAdmission->veterinarian->name }}: {{ $vetAdmission->veterinarian->email }}
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje personalizado</label>
                            <textarea name="message" rows="3"
                                      class="w-full border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                      placeholder="Mensaje opcional para incluir en el email..."></textarea>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="showEmailModal = false"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                            Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-lab-layout>
