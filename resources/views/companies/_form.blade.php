@php $company = $company ?? null; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
        <input type="text" name="name" id="name" value="{{ old('name', $company?->name) }}" required
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 text-sm">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="short_name" class="block text-sm font-medium text-gray-700 mb-1">Nombre Corto</label>
        <input type="text" name="short_name" id="short_name" value="{{ old('short_name', $company?->short_name) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 text-sm"
            placeholder="Para mostrar en el selector">
    </div>

    <div>
        <label for="cuit" class="block text-sm font-medium text-gray-700 mb-1">CUIT *</label>
        <input type="text" name="cuit" id="cuit" value="{{ old('cuit', $company?->cuit) }}" required
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 text-sm font-mono"
            placeholder="XX-XXXXXXXX-X">
        @error('cuit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="tax_condition" class="block text-sm font-medium text-gray-700 mb-1">Condición ante IVA *</label>
        <select name="tax_condition" id="tax_condition" required
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 text-sm">
            <option value="">Seleccionar...</option>
            @foreach(['IVA Responsable Inscripto', 'IVA Exento', 'Monotributista', 'Responsable No Inscripto', 'Sujeto No Categorizado'] as $condition)
                <option value="{{ $condition }}" {{ old('tax_condition', $company?->tax_condition) === $condition ? 'selected' : '' }}>{{ $condition }}</option>
            @endforeach
        </select>
        @error('tax_condition') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Domicilio Fiscal</label>
        <input type="text" name="address" id="address" value="{{ old('address', $company?->address) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 text-sm">
    </div>

    <div>
        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
        <input type="text" name="city" id="city" value="{{ old('city', $company?->city) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 text-sm">
    </div>

    <div>
        <label for="state" class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
        <input type="text" name="state" id="state" value="{{ old('state', $company?->state) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 text-sm">
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
        <input type="text" name="phone" id="phone" value="{{ old('phone', $company?->phone) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 text-sm">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email', $company?->email) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 text-sm">
    </div>

    <div>
        <label for="iibb" class="block text-sm font-medium text-gray-700 mb-1">Ingresos Brutos</label>
        <input type="text" name="iibb" id="iibb" value="{{ old('iibb', $company?->iibb) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 text-sm">
    </div>

    <div>
        <label for="activity_start" class="block text-sm font-medium text-gray-700 mb-1">Inicio de Actividades</label>
        <input type="date" name="activity_start" id="activity_start" value="{{ old('activity_start', $company?->activity_start?->format('Y-m-d')) }}"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 text-sm">
    </div>
</div>
