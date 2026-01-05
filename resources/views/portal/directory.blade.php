<x-portal-layout title="Directorio">
    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('portal.dashboard') }}" 
                       class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Directorio</h1>
                        <p class="text-sm text-gray-500">Cumpleaños y vacaciones de compañeros</p>
                    </div>
                </div>
                @if($isSupervisor)
                    <a href="{{ route('portal.team') }}" 
                       class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Mi Equipo
                    </a>
                @endif
            </div>

            {{-- Tabs --}}
            <div class="bg-white rounded-xl shadow mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <a href="{{ route('portal.directory', ['tab' => 'birthdays']) }}" 
                           class="py-4 px-6 border-b-2 font-medium text-sm transition-colors
                               {{ $tab === 'birthdays' 
                                   ? 'border-pink-500 text-pink-600' 
                                   : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A1.5 1.5 0 003 15.546V12a9 9 0 0118 0v3.546zM12 3v2m0 0a2 2 0 100 4 2 2 0 000-4z"/>
                                </svg>
                                Cumpleaños
                            </div>
                        </a>
                        <a href="{{ route('portal.directory', ['tab' => 'vacations']) }}" 
                           class="py-4 px-6 border-b-2 font-medium text-sm transition-colors
                               {{ $tab === 'vacations' 
                                   ? 'border-blue-500 text-blue-600' 
                                   : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                                </svg>
                                Vacaciones
                            </div>
                        </a>
                    </nav>
                </div>
            </div>

            @if($tab === 'birthdays')
                {{-- Cumpleaños de Hoy --}}
                @if($todayBirthdays->count() > 0)
                    <div class="bg-gradient-to-r from-pink-500 to-rose-500 rounded-2xl shadow-lg p-6 mb-6 text-white">
                        <div class="flex items-center mb-4">
                            <span class="text-4xl mr-4">🎂</span>
                            <div>
                                <h2 class="text-xl font-bold">¡Cumpleaños de Hoy!</h2>
                                <p class="text-pink-100">Felicita a tus compañeros</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($todayBirthdays as $emp)
                                <div class="bg-white/20 backdrop-blur rounded-xl p-4 flex items-center">
                                    <div class="w-14 h-14 bg-white/30 rounded-full flex items-center justify-center text-2xl font-bold">
                                        {{ strtoupper(substr($emp->name, 0, 1)) }}{{ strtoupper(substr($emp->lastName, 0, 1)) }}
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-bold text-lg">{{ $emp->name }} {{ $emp->lastName }}</p>
                                        <p class="text-pink-100">{{ $emp->jobs->first()?->name ?? '' }}</p>
                                    </div>
                                    <span class="ml-auto text-3xl">🎉</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Próximos Cumpleaños --}}
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-pink-600 to-rose-600 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Próximos Cumpleaños (30 días)
                        </h2>
                    </div>
                    <div class="p-6">
                        @if($upcomingBirthdays->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($upcomingBirthdays as $emp)
                                    <div class="p-4 rounded-xl border-2 transition-all hover:shadow-md
                                        {{ $emp->days_until_birthday == 0 
                                            ? 'border-pink-400 bg-pink-50' 
                                            : ($emp->days_until_birthday <= 7 ? 'border-pink-200 bg-pink-50/50' : 'border-gray-200') }}">
                                        <div class="flex items-start">
                                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold
                                                {{ $emp->days_until_birthday == 0 ? 'bg-pink-500' : 'bg-gray-400' }}">
                                                {{ strtoupper(substr($emp->name, 0, 1)) }}{{ strtoupper(substr($emp->lastName, 0, 1)) }}
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="font-semibold text-gray-900">{{ $emp->name }} {{ $emp->lastName }}</p>
                                                <p class="text-sm text-gray-500">{{ $emp->jobs->first()?->name ?? '—' }}</p>
                                                <div class="mt-2 flex items-center text-sm">
                                                    @if($emp->days_until_birthday == 0)
                                                        <span class="px-2 py-1 bg-pink-500 text-white rounded-full text-xs font-bold">
                                                            🎂 ¡Hoy!
                                                        </span>
                                                    @elseif($emp->days_until_birthday == 1)
                                                        <span class="px-2 py-1 bg-pink-400 text-white rounded-full text-xs font-bold">
                                                            Mañana
                                                        </span>
                                                    @elseif($emp->days_until_birthday <= 7)
                                                        <span class="px-2 py-1 bg-pink-200 text-pink-800 rounded-full text-xs font-bold">
                                                            En {{ $emp->days_until_birthday }} días
                                                        </span>
                                                    @else
                                                        <span class="text-gray-500">
                                                            {{ $emp->next_birthday->format('d/m') }} ({{ $emp->days_until_birthday }} días)
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-2xl font-bold text-gray-300">{{ $emp->turning_age }}</span>
                                                <p class="text-xs text-gray-400">años</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A1.5 1.5 0 003 15.546V12a9 9 0 0118 0v3.546zM12 3v2m0 0a2 2 0 100 4 2 2 0 000-4z"/>
                                </svg>
                                <p class="text-gray-500">No hay cumpleaños próximos en los siguientes 30 días</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- Tab Vacaciones --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- De Vacaciones Actualmente --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                                </svg>
                                De Vacaciones Ahora
                            </h2>
                        </div>
                        <div class="p-6">
                            @if($currentVacations->count() > 0)
                                <div class="space-y-3">
                                    @foreach($currentVacations as $vac)
                                        @php
                                            $daysLeft = now()->diffInDays(\Carbon\Carbon::parse($vac->end), false);
                                        @endphp
                                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl">
                                            <div class="flex items-start justify-between">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                                                        {{ strtoupper(substr($vac->employee->name, 0, 1)) }}{{ strtoupper(substr($vac->employee->lastName, 0, 1)) }}
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="font-medium text-gray-900">{{ $vac->employee->name }} {{ $vac->employee->lastName }}</p>
                                                        <p class="text-sm text-gray-500">{{ $vac->employee->jobs->first()?->name ?? '—' }}</p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="px-2 py-1 bg-blue-500 text-white rounded text-xs font-bold">
                                                        @if($daysLeft == 0)
                                                            Último día
                                                        @elseif($daysLeft == 1)
                                                            1 día más
                                                        @else
                                                            {{ $daysLeft }} días más
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mt-3 pt-3 border-t border-blue-200 text-sm text-gray-600">
                                                <span class="mr-4">
                                                    📅 {{ \Carbon\Carbon::parse($vac->start)->format('d/m') }} - {{ \Carbon\Carbon::parse($vac->end)->format('d/m') }}
                                                </span>
                                                <span>
                                                    ⏱️ {{ $vac->working_days ?? $vac->days }} días
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                                    </svg>
                                    <p class="text-gray-500">Nadie de vacaciones actualmente</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Próximas Vacaciones --}}
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Próximas Vacaciones
                            </h2>
                        </div>
                        <div class="p-6">
                            @if($upcomingVacations->count() > 0)
                                <div class="space-y-3">
                                    @foreach($upcomingVacations as $vac)
                                        @php
                                            $daysUntil = now()->diffInDays(\Carbon\Carbon::parse($vac->start), false);
                                        @endphp
                                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl">
                                            <div class="flex items-start justify-between">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center text-white font-bold">
                                                        {{ strtoupper(substr($vac->employee->name, 0, 1)) }}{{ strtoupper(substr($vac->employee->lastName, 0, 1)) }}
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="font-medium text-gray-900">{{ $vac->employee->name }} {{ $vac->employee->lastName }}</p>
                                                        <p class="text-sm text-gray-500">{{ $vac->employee->jobs->first()?->name ?? '—' }}</p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs font-bold">
                                                        @if($daysUntil == 1)
                                                            Mañana
                                                        @else
                                                            En {{ $daysUntil }} días
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mt-3 pt-3 border-t border-gray-200 text-sm text-gray-600">
                                                <span class="mr-4">
                                                    📅 {{ \Carbon\Carbon::parse($vac->start)->format('d/m') }} - {{ \Carbon\Carbon::parse($vac->end)->format('d/m') }}
                                                </span>
                                                <span>
                                                    ⏱️ {{ $vac->working_days ?? $vac->days }} días
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-gray-500">No hay vacaciones programadas próximamente</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Resumen Visual --}}
                <div class="mt-6 bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumen</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-xl">
                            <div class="text-3xl font-bold text-blue-600">{{ $currentVacations->count() }}</div>
                            <div class="text-sm text-gray-600">De vacaciones hoy</div>
                        </div>
                        <div class="text-center p-4 bg-indigo-50 rounded-xl">
                            <div class="text-3xl font-bold text-indigo-600">{{ $upcomingVacations->count() }}</div>
                            <div class="text-sm text-gray-600">Vacaciones próximas</div>
                        </div>
                        <div class="text-center p-4 bg-pink-50 rounded-xl">
                            <div class="text-3xl font-bold text-pink-600">{{ $todayBirthdays->count() }}</div>
                            <div class="text-sm text-gray-600">Cumpleaños hoy</div>
                        </div>
                        <div class="text-center p-4 bg-rose-50 rounded-xl">
                            <div class="text-3xl font-bold text-rose-600">{{ $upcomingBirthdays->count() }}</div>
                            <div class="text-sm text-gray-600">Cumpleaños próximos</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-portal-layout>












