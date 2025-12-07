<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-start md:justify-between mb-6">
            <div>
                <a href="{{ route('sample.index') }}" class="text-teal-600 hover:text-teal-800 text-sm flex items-center mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver a Protocolos
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Protocolo {{ $sample->protocol_number }}</h1>
                <div class="flex items-center gap-3 mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $sample->sample_type == 'agua' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                        {{ ucfirst($sample->sample_type) }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @switch($sample->status)
                            @case('pending') bg-yellow-100 text-yellow-800 @break
                            @case('in_progress') bg-blue-100 text-blue-800 @break
                            @case('completed') bg-green-100 text-green-800 @break
                            @case('cancelled') bg-red-100 text-red-800 @break
                        @endswitch">
                        {{ $sample->status_label }}
                    </span>
                </div>
            </div>
            <a href="{{ route('sample.edit', $sample) }}" 
               class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            </a>
        </div>

        <!-- Alertas -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Información Principal -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Información de la Muestra</h2>
                    
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500">Cliente</dt>
                            <dd class="text-gray-900 font-medium">{{ $sample->customer->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Fecha de Ingreso</dt>
                            <dd class="text-gray-900">{{ $sample->entry_date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Fecha de Toma</dt>
                            <dd class="text-gray-900">{{ $sample->sampling_date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Lugar</dt>
                            <dd class="text-gray-900">{{ $sample->location }}</dd>
                        </div>
                        @if($sample->address)
                        <div>
                            <dt class="text-sm text-gray-500">Dirección</dt>
                            <dd class="text-gray-900">{{ $sample->address }}</dd>
                        </div>
                        @endif
                        @if($sample->isFood())
                            @if($sample->product_name)
                            <div>
                                <dt class="text-sm text-gray-500">Producto</dt>
                                <dd class="text-gray-900">{{ $sample->product_name }}</dd>
                            </div>
                            @endif
                            @if($sample->batch)
                            <div>
                                <dt class="text-sm text-gray-500">Lote</dt>
                                <dd class="text-gray-900">{{ $sample->batch }}</dd>
                            </div>
                            @endif
                        @endif
                        @if($sample->observations)
                        <div>
                            <dt class="text-sm text-gray-500">Observaciones</dt>
                            <dd class="text-gray-900">{{ $sample->observations }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm text-gray-500">Registrado por</dt>
                            <dd class="text-gray-900">{{ $sample->creator->name ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Determinaciones -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Determinaciones</h2>
                        
                        <!-- Agregar Determinación -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" type="button"
                                    class="inline-flex items-center px-3 py-1.5 bg-teal-100 text-teal-700 rounded-lg hover:bg-teal-200 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Agregar
                            </button>
                            
                            <div x-show="open" @click.outside="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border z-10 p-4">
                                <form action="{{ route('sample.addDetermination', $sample) }}" method="POST">
                                    @csrf
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Determinación</label>
                                    <select name="test_id" required class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500 mb-3">
                                        <option value="">Seleccionar...</option>
                                        @foreach(\App\Models\Test::orderBy('name')->get() as $test)
                                            @if(!$sample->determinations->contains('test_id', $test->id))
                                                <option value="{{ $test->id }}">{{ $test->code }} - {{ $test->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <button type="submit" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                                        Agregar Determinación
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    @if($sample->determinations->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Determinación</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Resultado</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unidad</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($sample->determinations as $det)
                                        <tr class="hover:bg-gray-50" x-data="{ editing: false }">
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $det->test->code ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $det->test->name ?? 'N/A' }}</td>
                                            
                                            <!-- Modo Ver -->
                                            <template x-if="!editing">
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $det->result ?? '-' }}</td>
                                            </template>
                                            <template x-if="!editing">
                                                <td class="px-4 py-3 text-sm text-gray-500">{{ $det->unit ?? '-' }}</td>
                                            </template>
                                            <template x-if="!editing">
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                        @switch($det->status)
                                                            @case('pending') bg-yellow-100 text-yellow-800 @break
                                                            @case('in_progress') bg-blue-100 text-blue-800 @break
                                                            @case('completed') bg-green-100 text-green-800 @break
                                                        @endswitch">
                                                        {{ $det->status_label }}
                                                    </span>
                                                </td>
                                            </template>
                                            <template x-if="!editing">
                                                <td class="px-4 py-3 text-right text-sm">
                                                    <button @click="editing = true" class="text-indigo-600 hover:text-indigo-900 mr-2">
                                                        Cargar
                                                    </button>
                                                    <form action="{{ route('sample.removeDetermination', [$sample, $det]) }}" method="POST" class="inline"
                                                          onsubmit="return confirm('¿Eliminar esta determinación?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>
                                                    </form>
                                                </td>
                                            </template>

                                            <!-- Modo Editar -->
                                            <template x-if="editing">
                                                <td colspan="4" class="px-4 py-3">
                                                    <form action="{{ route('sample.updateDetermination', $det) }}" method="POST" class="flex items-center gap-2">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="text" name="result" value="{{ $det->result }}" placeholder="Resultado"
                                                               class="w-24 text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                                                        <input type="text" name="reference_value" value="{{ $det->reference_value }}" placeholder="Ref."
                                                               class="w-20 text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                                                        <select name="status" class="text-sm rounded border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                                                            <option value="pending" {{ $det->status == 'pending' ? 'selected' : '' }}>Pendiente</option>
                                                            <option value="in_progress" {{ $det->status == 'in_progress' ? 'selected' : '' }}>En Proceso</option>
                                                            <option value="completed" {{ $det->status == 'completed' ? 'selected' : '' }}>Completado</option>
                                                        </select>
                                                        <button type="submit" class="px-3 py-1 bg-teal-600 text-white rounded text-sm hover:bg-teal-700">
                                                            Guardar
                                                        </button>
                                                        <button type="button" @click="editing = false" class="px-3 py-1 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">
                                                            Cancelar
                                                        </button>
                                                    </form>
                                                </td>
                                            </template>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="mt-2">No hay determinaciones asignadas</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-lab-layout>
