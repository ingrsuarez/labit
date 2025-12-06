<x-portal-layout title="Mis Solicitudes">
    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            {{-- Alertas --}}
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-green-800">{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="list-disc list-inside text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Resumen de Vacaciones --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-indigo-100 text-sm">Días de Vacaciones</p>
                            <p class="text-3xl font-bold">{{ $vacationSummary['total_by_law'] }}</p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-indigo-200 text-xs mt-2">{{ $vacationSummary['antiquity_years'] }} años de antigüedad</p>
                </div>
                
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Usados</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $vacationSummary['used'] }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Pendientes</p>
                            <p class="text-3xl font-bold text-amber-600">{{ $vacationSummary['pending'] }}</p>
                        </div>
                        <div class="p-3 bg-amber-100 text-amber-600 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm">Disponibles</p>
                            <p class="text-3xl font-bold">{{ $vacationSummary['available'] }}</p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <a href="{{ route('portal.requests', ['tab' => 'vacations', 'year' => $year]) }}" 
                       class="py-4 px-1 border-b-2 font-medium text-sm {{ $tab === 'vacations' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Vacaciones
                    </a>
                    <a href="{{ route('portal.requests', ['tab' => 'leaves', 'year' => $year]) }}" 
                       class="py-4 px-1 border-b-2 font-medium text-sm {{ $tab === 'leaves' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Otras Licencias
                    </a>
                    <a href="{{ route('portal.requests', ['tab' => 'new', 'year' => $year]) }}" 
                       class="py-4 px-1 border-b-2 font-medium text-sm {{ $tab === 'new' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Nueva Solicitud
                        </span>
                    </a>
                </nav>
            </div>

            {{-- Contenido según tab --}}
            @if($tab === 'vacations')
                {{-- Lista de Vacaciones --}}
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Mis Vacaciones {{ $year }}</h2>
                        <select onchange="window.location.href='{{ route('portal.requests') }}?tab=vacations&year='+this.value" 
                                class="border-gray-300 rounded-lg text-sm">
                            @for($y = now()->year; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="p-6">
                        @if($vacationRequests->count() > 0)
                            <div class="space-y-4">
                                @foreach($vacationRequests as $request)
                                    <div class="flex items-center justify-between p-4 border rounded-xl 
                                        {{ $request->status === 'aprobado' ? 'bg-green-50 border-green-200' : '' }}
                                        {{ $request->status === 'pendiente' ? 'bg-amber-50 border-amber-200' : '' }}
                                        {{ $request->status === 'rechazado' ? 'bg-red-50 border-red-200' : '' }}
                                        {{ $request->status === 'cancelado' ? 'bg-gray-50 border-gray-200' : '' }}">
                                        <div class="flex items-center">
                                            <div class="p-3 rounded-lg mr-4
                                                {{ $request->status === 'aprobado' ? 'bg-green-500 text-white' : '' }}
                                                {{ $request->status === 'pendiente' ? 'bg-amber-500 text-white' : '' }}
                                                {{ $request->status === 'rechazado' ? 'bg-red-500 text-white' : '' }}
                                                {{ $request->status === 'cancelado' ? 'bg-gray-400 text-white' : '' }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900">
                                                    {{ \Carbon\Carbon::parse($request->start)->format('d/m/Y') }} - 
                                                    {{ \Carbon\Carbon::parse($request->end)->format('d/m/Y') }}
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    {{ $request->working_days ?? $request->days }} días hábiles
                                                    @if($request->description)
                                                        · {{ Str::limit($request->description, 50) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                                {{ $request->status === 'aprobado' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $request->status === 'pendiente' ? 'bg-amber-100 text-amber-800' : '' }}
                                                {{ $request->status === 'rechazado' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $request->status === 'cancelado' ? 'bg-gray-100 text-gray-800' : '' }}">
                                                {{ ucfirst($request->status) }}
                                            </span>
                                            @if($request->status === 'pendiente')
                                                <form action="{{ route('portal.requests.cancel', $request) }}" method="POST" 
                                                      onsubmit="return confirm('¿Cancelar esta solicitud?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                        Cancelar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-gray-500 mb-4">No tienes solicitudes de vacaciones en {{ $year }}</p>
                                <a href="{{ route('portal.requests', ['tab' => 'new']) }}" 
                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Solicitar Vacaciones
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            @elseif($tab === 'leaves')
                {{-- Lista de Otras Licencias --}}
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Otras Licencias {{ $year }}</h2>
                    </div>
                    <div class="p-6">
                        @if($otherLeaves->count() > 0)
                            <div class="space-y-4">
                                @foreach($otherLeaves as $leave)
                                    <div class="flex items-center justify-between p-4 border rounded-xl">
                                        <div class="flex items-center">
                                            <div class="p-3 bg-purple-100 text-purple-600 rounded-lg mr-4">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900 capitalize">{{ $leave->type }}</p>
                                                <p class="text-sm text-gray-500">
                                                    {{ \Carbon\Carbon::parse($leave->start)->format('d/m/Y') }} - 
                                                    {{ \Carbon\Carbon::parse($leave->end)->format('d/m/Y') }}
                                                    · {{ $leave->days }} días
                                                </p>
                                                @if($leave->description)
                                                    <p class="text-sm text-gray-400">{{ Str::limit($leave->description, 80) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-sm font-medium
                                            {{ $leave->status === 'aprobado' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $leave->status === 'pendiente' ? 'bg-amber-100 text-amber-800' : '' }}
                                            {{ $leave->status === 'rechazado' ? 'bg-red-100 text-red-800' : '' }}">
                                            {{ ucfirst($leave->status) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-gray-500">No tienes otras licencias registradas en {{ $year }}</p>
                            </div>
                        @endif
                    </div>
                </div>

            @elseif($tab === 'new')
                {{-- Formularios de Nueva Solicitud --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Solicitar Vacaciones --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                                </svg>
                                Solicitar Vacaciones
                            </h2>
                        </div>
                        <form action="{{ route('portal.requests.vacation') }}" method="POST" class="p-6">
                            @csrf
                            <div class="space-y-4">
                                <div class="p-4 bg-indigo-50 rounded-lg mb-4">
                                    <p class="text-sm text-indigo-800">
                                        <strong>Días disponibles:</strong> {{ $vacationSummary['available'] }} de {{ $vacationSummary['total_by_law'] }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="vacation_start" class="block text-sm font-medium text-gray-700 mb-1">
                                            Fecha Inicio <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" name="start" id="vacation_start" required
                                               min="{{ now()->format('Y-m-d') }}"
                                               value="{{ old('start') }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label for="vacation_end" class="block text-sm font-medium text-gray-700 mb-1">
                                            Fecha Fin <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" name="end" id="vacation_end" required
                                               min="{{ now()->format('Y-m-d') }}"
                                               value="{{ old('end') }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>

                                <div>
                                    <label for="vacation_description" class="block text-sm font-medium text-gray-700 mb-1">
                                        Comentario (opcional)
                                    </label>
                                    <textarea name="description" id="vacation_description" rows="2"
                                              placeholder="Agrega algún comentario si es necesario..."
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('description') }}</textarea>
                                </div>
                            </div>

                            <button type="submit" 
                                    class="mt-6 w-full px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center font-medium">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                Enviar Solicitud de Vacaciones
                            </button>
                        </form>
                    </div>

                    {{-- Solicitar Licencia --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Solicitar Otra Licencia
                            </h2>
                        </div>
                        <form action="{{ route('portal.requests.leave') }}" method="POST" class="p-6">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label for="leave_type" class="block text-sm font-medium text-gray-700 mb-1">
                                        Tipo de Licencia <span class="text-red-500">*</span>
                                    </label>
                                    <select name="type" id="leave_type" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white">
                                        <option value="">Seleccionar tipo...</option>
                                        @foreach($leaveTypes as $value => $label)
                                            <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="leave_start" class="block text-sm font-medium text-gray-700 mb-1">
                                            Fecha Inicio <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" name="start" id="leave_start" required
                                               value="{{ old('start') }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    </div>
                                    <div>
                                        <label for="leave_end" class="block text-sm font-medium text-gray-700 mb-1">
                                            Fecha Fin <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" name="end" id="leave_end" required
                                               value="{{ old('end') }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    </div>
                                </div>

                                <div>
                                    <label for="leave_description" class="block text-sm font-medium text-gray-700 mb-1">
                                        Motivo / Descripción <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="description" id="leave_description" rows="3" required
                                              placeholder="Describe el motivo de la licencia..."
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">{{ old('description') }}</textarea>
                                </div>
                            </div>

                            <button type="submit" 
                                    class="mt-6 w-full px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center justify-center font-medium">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                Enviar Solicitud de Licencia
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Info Adicional --}}
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Información Importante
                    </h3>
                    <ul class="text-sm text-blue-800 space-y-2">
                        <li>• Las solicitudes de vacaciones deben hacerse con anticipación</li>
                        <li>• Tu supervisor revisará y aprobará/rechazará la solicitud</li>
                        <li>• Recibirás una notificación cuando tu solicitud sea procesada</li>
                        <li>• Los días de vacaciones se calculan en días hábiles (excluyendo fines de semana y feriados)</li>
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-portal-layout>
