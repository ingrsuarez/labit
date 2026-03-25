<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('lab.section.configuracion') }}" class="hover:text-teal-600">Configuración</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span>Correos</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Configuración de Correos</h1>
            <p class="text-gray-600 mt-1">Defina los remitentes y firmas para los emails del laboratorio</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('lab.email-settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Sección 1: Correo de Resultados -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b flex items-center gap-2">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Correo de Resultados
                </h2>
                <p class="text-sm text-gray-500 mb-4">Configuración del remitente para envío de informes de resultados (PDF) a clientes</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="results_email" class="block text-sm font-medium text-gray-700 mb-1">Email remitente *</label>
                        <input type="email" name="results_email" id="results_email" required
                               value="{{ old('results_email', $settings['results_email']) }}"
                               placeholder="resultados@laboratorio.com"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div>
                        <label for="results_from_name" class="block text-sm font-medium text-gray-700 mb-1">Nombre del remitente *</label>
                        <input type="text" name="results_from_name" id="results_from_name" required
                               value="{{ old('results_from_name', $settings['results_from_name']) }}"
                               placeholder="Laboratorio - Resultados"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="results_default_subject" class="block text-sm font-medium text-gray-700 mb-1">Asunto por defecto *</label>
                        <input type="text" name="results_default_subject" id="results_default_subject" required
                               value="{{ old('results_default_subject', $settings['results_default_subject']) }}"
                               placeholder="Informe de Resultados - Protocolo {protocol}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                        <p class="text-xs text-gray-500 mt-1">Usá <code class="bg-gray-100 px-1 rounded">{protocol}</code> para incluir el número de protocolo</p>
                    </div>

                    <div class="md:col-span-2" x-data="{ html: '' }" x-init="html = $refs.resultsTextarea.value">
                        <label for="results_signature" class="block text-sm font-medium text-gray-700 mb-1">Firma HTML</label>
                        <textarea name="results_signature" id="results_signature" rows="6"
                                  x-model="html" x-ref="resultsTextarea"
                                  placeholder="<p><strong>Laboratorio</strong><br>Dirección - Tel: ...</p>"
                                  class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 font-mono text-sm">{{ old('results_signature', $settings['results_signature']) }}</textarea>
                        <div x-show="html" class="mt-2 border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <p class="text-xs text-gray-400 mb-2">Vista previa:</p>
                            <div x-html="html" class="text-sm"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 2: Correo de Notificaciones -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b flex items-center gap-2">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    Correo de Notificaciones
                </h2>
                <p class="text-sm text-gray-500 mb-4">Configuración del remitente para avisos generales del laboratorio (feriados, novedades, etc.)</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="notifications_email" class="block text-sm font-medium text-gray-700 mb-1">Email remitente *</label>
                        <input type="email" name="notifications_email" id="notifications_email" required
                               value="{{ old('notifications_email', $settings['notifications_email']) }}"
                               placeholder="laboratorio@laboratorio.com"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div>
                        <label for="notifications_from_name" class="block text-sm font-medium text-gray-700 mb-1">Nombre del remitente *</label>
                        <input type="text" name="notifications_from_name" id="notifications_from_name" required
                               value="{{ old('notifications_from_name', $settings['notifications_from_name']) }}"
                               placeholder="Laboratorio"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="notifications_default_subject" class="block text-sm font-medium text-gray-700 mb-1">Asunto por defecto *</label>
                        <input type="text" name="notifications_default_subject" id="notifications_default_subject" required
                               value="{{ old('notifications_default_subject', $settings['notifications_default_subject']) }}"
                               placeholder="Aviso del Laboratorio"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div class="md:col-span-2" x-data="{ html: '' }" x-init="html = $refs.notifTextarea.value">
                        <label for="notifications_signature" class="block text-sm font-medium text-gray-700 mb-1">Firma HTML</label>
                        <textarea name="notifications_signature" id="notifications_signature" rows="6"
                                  x-model="html" x-ref="notifTextarea"
                                  placeholder="<p><strong>Laboratorio</strong><br>Dirección - Tel: ...</p>"
                                  class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 font-mono text-sm">{{ old('notifications_signature', $settings['notifications_signature']) }}</textarea>
                        <div x-show="html" class="mt-2 border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <p class="text-xs text-gray-400 mb-2">Vista previa:</p>
                            <div x-html="html" class="text-sm"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón Guardar -->
            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</x-lab-layout>
