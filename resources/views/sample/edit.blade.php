<x-lab-layout>
    <div class="py-6 px-4 md:px-6">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('sample.show', $sample) }}" class="text-teal-600 hover:text-teal-800 text-sm flex items-center mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver al Protocolo
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Editar Protocolo {{ $sample->protocol_number }}</h1>
        </div>

        <!-- Errores -->
        @if($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('sample.update', $sample) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Datos de la Muestra</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Tipo de Muestra -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Muestra *</label>
                        <select name="sample_type" required
                                class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="agua" {{ old('sample_type', $sample->sample_type) == 'agua' ? 'selected' : '' }}>Agua</option>
                            <option value="alimento" {{ old('sample_type', $sample->sample_type) == 'alimento' ? 'selected' : '' }}>Alimento</option>
                        </select>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                        <select name="status" required
                                class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="pending" {{ old('status', $sample->status) == 'pending' ? 'selected' : '' }}>Pendiente</option>
                            <option value="in_progress" {{ old('status', $sample->status) == 'in_progress' ? 'selected' : '' }}>En Proceso</option>
                            <option value="completed" {{ old('status', $sample->status) == 'completed' ? 'selected' : '' }}>Completado</option>
                            <option value="cancelled" {{ old('status', $sample->status) == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                        </select>
                    </div>

                    <!-- Fecha de Ingreso -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Ingreso *</label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', $sample->entry_date->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <!-- Fecha de Toma -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Toma de Muestra *</label>
                        <input type="date" name="sampling_date" value="{{ old('sampling_date', $sample->sampling_date->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <!-- Cliente -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                        <select name="customer_id" required
                                class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Seleccionar cliente...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $sample->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->taxId }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Lugar -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lugar de Toma *</label>
                        <input type="text" name="location" value="{{ old('location', $sample->location) }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <!-- Dirección -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="address" value="{{ old('address', $sample->address) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <!-- Nombre del Producto (para alimentos) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto</label>
                        <input type="text" name="product_name" value="{{ old('product_name', $sample->product_name) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <!-- Lote (para alimentos) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lote</label>
                        <input type="text" name="batch" value="{{ old('batch', $sample->batch) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea name="observations" rows="3"
                              class="w-full rounded-lg border-gray-300 focus:border-teal-500 focus:ring-teal-500">{{ old('observations', $sample->observations) }}</textarea>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('sample.show', $sample) }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</x-lab-layout>
