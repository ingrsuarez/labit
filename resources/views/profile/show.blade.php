<x-app-layout>
    {{-- <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot> --}}

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')

                <x-section-border />
            @endif

            <!-- Firma Digital -->
            <x-section-border />

            <div class="mt-10 sm:mt-0">
                <x-action-section>
                    <x-slot name="title">
                        Firma Digital
                    </x-slot>

                    <x-slot name="description">
                        Subí tu firma para que aparezca en los protocolos que validás. Formatos: JPG, PNG o GIF. Máximo 2MB.
                    </x-slot>

                    <x-slot name="content">
                        @if(session('success'))
                            <div class="mb-4 text-sm text-green-600">{{ session('success') }}</div>
                        @endif

                        @if(auth()->user()->signature_path)
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-2">Firma actual:</p>
                                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 inline-block">
                                    <img src="{{ auth()->user()->signature_url }}"
                                         alt="Firma"
                                         class="max-h-24 max-w-xs">
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('user.signature.update') }}" method="POST" enctype="multipart/form-data"
                              x-data="{ preview: null }" class="space-y-4">
                            @csrf

                            <div>
                                <input type="file" name="signature" accept="image/png,image/jpeg,image/gif"
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                                              file:rounded-lg file:border-0 file:text-sm file:font-semibold
                                              file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
                                       x-on:change="
                                           const reader = new FileReader();
                                           reader.onload = (e) => { preview = e.target.result; };
                                           reader.readAsDataURL($event.target.files[0]);
                                       ">
                                @error('signature')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div x-show="preview" x-cloak class="border border-teal-200 rounded-lg p-4 bg-teal-50 inline-block">
                                <p class="text-sm text-teal-700 mb-2">Vista previa:</p>
                                <img :src="preview" class="max-h-24 max-w-xs">
                            </div>

                            <div class="flex items-center gap-3">
                                <x-button type="submit">
                                    {{ auth()->user()->signature_path ? 'Reemplazar firma' : 'Subir firma' }}
                                </x-button>
                            </div>
                        </form>

                        @if(auth()->user()->signature_path)
                            <form action="{{ route('user.signature.destroy') }}" method="POST" class="mt-4">
                                @csrf
                                @method('DELETE')
                                <x-danger-button type="submit"
                                                 onclick="return confirm('¿Estás seguro de eliminar tu firma?')">
                                    Eliminar firma
                                </x-danger-button>
                            </form>
                        @endif
                    </x-slot>
                </x-action-section>
            </div>

            @can('lab.section')
            <!-- Sede de laboratorio por defecto -->
            <x-section-border />

            <div class="mt-10 sm:mt-0">
                <x-action-section>
                    <x-slot name="title">
                        Sede de Laboratorio
                    </x-slot>

                    <x-slot name="description">
                        Configurá tu sede de laboratorio por defecto. Los listados de protocolos se filtrarán automáticamente por esta sede.
                    </x-slot>

                    <x-slot name="content">
                        @if(session('success'))
                            <div class="mb-4 text-sm text-green-600">{{ session('success') }}</div>
                        @endif

                        @php
                            $labBranches = \App\Models\LabBranch::active()->orderByDesc('is_central')->orderBy('name')->get();
                        @endphp

                        <form action="{{ route('user.default-branch') }}" method="POST" class="flex items-end gap-3">
                            @csrf
                            @method('PUT')
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sede por defecto</label>
                                <select name="default_lab_branch_id"
                                        class="w-full rounded-lg border-gray-300 focus:ring-teal-500 focus:border-teal-500">
                                    <option value="">Sin sede por defecto (ver todas)</option>
                                    @foreach($labBranches as $branch)
                                        <option value="{{ $branch->id }}" {{ auth()->user()->default_lab_branch_id == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}{{ $branch->city ? ' — ' . $branch->city : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <x-button type="submit">
                                Guardar
                            </x-button>
                        </form>
                    </x-slot>
                </x-action-section>
            </div>
            @endcan

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.update-password-form')
                </div>

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.two-factor-authentication-form')
                </div>

                <x-section-border />
            @endif

            <div class="mt-10 sm:mt-0">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <x-section-border />

                <div class="mt-10 sm:mt-0">
                    @livewire('profile.delete-user-form')
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
