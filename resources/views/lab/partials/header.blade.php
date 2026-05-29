@php
    $user = auth()->user();
    $labBranches = \App\Models\LabBranch::active()->orderByDesc('is_central')->orderBy('name')->get();
@endphp

<header class="hidden md:block bg-white shadow-sm border-b sticky top-0 z-30">
    <div class="px-6 py-4">
        <div class="flex items-center justify-between">
            <!-- Título de la página -->
            <div>
                <h1 class="text-lg font-semibold text-gray-900">
                    @if(request()->routeIs('dashboard'))
                        Dashboard
                    @elseif(request()->routeIs('admission.*'))
                        Admisión de Pedidos
                    @elseif(request()->routeIs('patient.*'))
                        Gestión de Pacientes
                    @elseif(request()->routeIs('tests.*'))
                        Análisis Clínicos
                    @elseif(request()->routeIs('group.*'))
                        Grupos de Análisis
                    @elseif(request()->routeIs('insurance.*'))
                        Coberturas Médicas
                    @else
                        Laboratorio
                    @endif
                </h1>
                <p class="text-sm text-gray-500">
                    {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </p>
            </div>

            <!-- Acciones -->
            <div class="flex items-center space-x-4">
                @can('lab-sample-draws.view')
                <div x-data="sampleDrawQueue()" x-init="init()" class="relative">
                    <button type="button" @click="openModal()"
                            class="relative inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-white transition-colors"
                            :class="count > 0 ? 'bg-rose-500 hover:bg-rose-600 animate-pulse' : 'bg-rose-400 hover:bg-rose-500'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        <span>Extracciones</span>
                        <span x-show="count > 0" x-text="count"
                              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 text-xs font-bold bg-white text-rose-600 rounded-full"></span>
                    </button>

                    <div x-show="modalOpen" x-cloak
                         class="fixed inset-0 z-50 flex items-start justify-end"
                         @keydown.escape.window="modalOpen = false">
                        <div class="absolute inset-0 bg-black/40" @click="modalOpen = false"></div>
                        <div class="relative w-full max-w-md h-full bg-white shadow-xl flex flex-col"
                             @click.stop>
                            <div class="px-5 py-4 border-b flex items-center justify-between bg-rose-50">
                                <h2 class="text-lg font-semibold text-rose-900">Extracciones pendientes</h2>
                                <button type="button" @click="modalOpen = false" class="text-gray-500 hover:text-gray-700">✕</button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                                <template x-if="loading">
                                    <p class="text-sm text-gray-500">Cargando...</p>
                                </template>
                                <template x-if="!loading && items.length === 0">
                                    <p class="text-sm text-gray-500 text-center py-8">No hay extracciones pendientes en esta sede.</p>
                                </template>
                                <template x-for="item in items" :key="item.id">
                                    <div class="border border-gray-200 rounded-lg p-3 space-y-2">
                                        <div class="flex justify-between items-start gap-2">
                                            <div>
                                                <p class="font-semibold text-gray-900" x-text="item.protocol_number"></p>
                                                <p class="text-sm text-gray-600" x-text="item.patient_name"></p>
                                                <p class="text-xs text-gray-400" x-text="item.created_at_label"></p>
                                                <p x-show="item.branch_name" class="text-xs text-teal-600" x-text="item.branch_name"></p>
                                            </div>
                                        </div>
                                        <div x-show="mustSelectDrawer">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Tomador de muestra *</label>
                                            <select x-model="selectedDrawer[item.id]"
                                                    class="w-full rounded-lg border-gray-300 text-sm">
                                                <option value="">Seleccionar...</option>
                                                <template x-for="d in drawers" :key="d.id">
                                                    <option :value="d.id" x-text="d.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <button type="button" @click="register(item.id)"
                                                :disabled="registering === item.id"
                                                class="w-full px-3 py-2 bg-rose-600 text-white text-sm font-medium rounded-lg hover:bg-rose-700 disabled:opacity-50">
                                            Confirmar extracción
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                @endcan

                <!-- Notificaciones -->
                <button class="relative p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </button>

                @if($labBranches->count() > 1)
                <!-- Selector de Sede -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open"
                            class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors border border-gray-200">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>{{ active_lab_branch_name() }}</span>
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                        <form action="{{ route('switch-lab-branch') }}" method="POST">
                            @csrf
                            <button type="submit" name="lab_branch_id" value=""
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 {{ !active_lab_branch_id() ? 'font-medium text-teal-600' : 'text-gray-700' }}">
                                Todas las sedes
                            </button>
                            @foreach($labBranches as $branch)
                                <button type="submit" name="lab_branch_id" value="{{ $branch->id }}"
                                        class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 {{ active_lab_branch_id() == $branch->id ? 'font-medium text-teal-600' : 'text-gray-700' }}">
                                    {{ $branch->name }}{{ $branch->city ? ' — ' . $branch->city : '' }}
                                    @if($branch->is_central) <span class="text-xs text-gray-400">(Central)</span> @endif
                                </button>
                            @endforeach
                        </form>
                    </div>
                </div>
                @endif

                <!-- Usuario Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" 
                            class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 bg-teal-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="hidden lg:block text-left">
                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $user->roles->first()->name ?? 'Usuario' }}
                            </p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" 
                         x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         @click.away="open = false"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-1 z-50">
                        
                        <a href="{{ route('profile.show') }}" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Mi Perfil
                        </a>

                        <a href="{{ route('manage.index') }}" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Administración
                        </a>

                        @if($user->employee)
                            <a href="{{ route('portal.dashboard') }}" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Portal del Empleado
                            </a>
                        @endif
                        
                        <div class="border-t my-1"></div>
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" 
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

@can('lab-sample-draws.view')
@push('scripts')
<script>
function sampleDrawQueue() {
    return {
        count: 0,
        modalOpen: false,
        loading: false,
        items: [],
        drawers: [],
        mustSelectDrawer: false,
        selectedDrawer: {},
        registering: null,
        init() {
            this.refreshCount();
            setInterval(() => this.refreshCount(), 60000);
        },
        async refreshCount() {
            try {
                const res = await fetch('{{ route('lab.sample-draws.pending-count') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.count = data.count ?? 0;
                }
            } catch (e) { /* ignore */ }
        },
        async openModal() {
            this.modalOpen = true;
            this.loading = true;
            this.selectedDrawer = {};
            try {
                const res = await fetch('{{ route('lab.sample-draws.pending') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.items = data.items ?? [];
                this.drawers = data.drawers ?? [];
                this.mustSelectDrawer = !!data.must_select_drawer;
            } catch (e) {
                this.items = [];
            }
            this.loading = false;
        },
        async register(admissionId) {
            const body = { _token: '{{ csrf_token() }}' };
            if (this.mustSelectDrawer) {
                const drawerId = this.selectedDrawer[admissionId];
                if (!drawerId) {
                    alert('Debe seleccionar quién realizó la extracción.');
                    return;
                }
                body.sample_drawn_by = drawerId;
            }
            this.registering = admissionId;
            try {
                const res = await fetch(`{{ url('/lab/sample-draws') }}/${admissionId}/register`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(body)
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    alert(err.message || 'No se pudo registrar la extracción.');
                    return;
                }
                this.items = this.items.filter(i => i.id !== admissionId);
                await this.refreshCount();
            } catch (e) {
                alert('Error de conexión.');
            }
            this.registering = null;
        }
    };
}
</script>
@endpush
@endcan

<!-- Header Móvil (espacio para el toggle) -->
<div class="h-14 md:hidden"></div>













