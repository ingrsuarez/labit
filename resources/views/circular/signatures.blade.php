<x-admin-layout>
    <div class="max-w-6xl mx-auto py-8 px-4">
        {{-- Breadcrumb --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <a href="{{ route('circular.show', $circular) }}" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver a {{ $circular->code }}
                </a>
            </div>
        </div>

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Seguimiento de Firmas</h1>
            <p class="text-sm text-gray-600 mt-1">
                {{ $circular->code }} - {{ $circular->title }}
            </p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Empleados</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $allEmployees->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Firmaron</p>
                        <p class="text-2xl font-bold text-green-600">{{ $signedEmployees->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-amber-100">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Solo Leyeron</p>
                        <p class="text-2xl font-bold text-amber-600">{{ $readOnly->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Pendientes</p>
                        <p class="text-2xl font-bold text-red-600">{{ $pendingEmployees->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        @php
            $percentage = $allEmployees->count() > 0 
                ? round(($signedEmployees->count() / $allEmployees->count()) * 100) 
                : 0;
        @endphp
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Progreso de firmas</span>
                <span class="text-sm font-bold text-gray-900">{{ $percentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-500" 
                     style="width: {{ $percentage }}%"></div>
            </div>
        </div>

        {{-- Tabs para las diferentes listas --}}
        <div x-data="{ tab: 'signed' }" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button @click="tab = 'signed'" 
                            :class="tab === 'signed' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                        Firmaron ({{ $signedEmployees->count() }})
                    </button>
                    <button @click="tab = 'read'" 
                            :class="tab === 'read' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                        Solo Leyeron ({{ $readOnly->count() }})
                    </button>
                    <button @click="tab = 'pending'" 
                            :class="tab === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 border-b-2 font-medium text-sm transition-colors">
                        Pendientes ({{ $pendingEmployees->count() }})
                    </button>
                </nav>
            </div>

            {{-- Firmaron --}}
            <div x-show="tab === 'signed'" class="p-6">
                @if($signedEmployees->isEmpty())
                    <p class="text-gray-500 text-center py-8">Ningún empleado ha firmado aún.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Empleado</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Puesto</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Leyó</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Firmó</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">IP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($signedEmployees as $signature)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-sm font-bold">
                                                    {{ strtoupper(substr($signature->employee->name ?? '', 0, 1)) }}
                                                </div>
                                                <div class="ml-3">
                                                    <p class="font-medium text-gray-900 capitalize">
                                                        {{ $signature->employee->lastName }} {{ $signature->employee->name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $signature->employee->jobs->first()->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $signature->read_at?->format('d/m/Y H:i') ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <span class="inline-flex items-center">
                                                <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                {{ $signature->signed_at->format('d/m/Y H:i') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500 font-mono">
                                            {{ $signature->ip_address ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Solo Leyeron --}}
            <div x-show="tab === 'read'" class="p-6">
                @if($readOnly->isEmpty())
                    <p class="text-gray-500 text-center py-8">Ningún empleado ha leído sin firmar.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Empleado</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Puesto</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Leyó</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($readOnly as $signature)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 text-sm font-bold">
                                                    {{ strtoupper(substr($signature->employee->name ?? '', 0, 1)) }}
                                                </div>
                                                <div class="ml-3">
                                                    <p class="font-medium text-gray-900 capitalize">
                                                        {{ $signature->employee->lastName }} {{ $signature->employee->name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $signature->employee->jobs->first()->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $signature->read_at?->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                Pendiente de firma
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Pendientes --}}
            <div x-show="tab === 'pending'" class="p-6">
                @if($pendingEmployees->isEmpty())
                    <p class="text-gray-500 text-center py-8">¡Todos los empleados han visto la circular!</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Empleado</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Puesto</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($pendingEmployees as $employee)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-700 text-sm font-bold">
                                                    {{ strtoupper(substr($employee->name ?? '', 0, 1)) }}
                                                </div>
                                                <div class="ml-3">
                                                    <p class="font-medium text-gray-900 capitalize">
                                                        {{ $employee->lastName }} {{ $employee->name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $employee->jobs->first()->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                No ha visto
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>

