<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'IPAC') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gray-300">
        <x-banner />

        <div class="min-h-fit h-max bg-gray-300">
            @livewire('navigation-menu')
            
            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="h-screen">

                    <div>@livewire('manage-side-bar')</div>
                    <div class="relative h-fit md:absolute md:top-20 md:ml-64 mx-2 w-fit lg:w-3/4">{{ $slot }}</div>
                    
                {{-- </div> --}}
                
            </main>
        </div>

        @stack('modals')
        <script src="/livewire/livewire.js" data-turbo-eval="false" data-turbolinks-eval="false"></script>
        {{-- @livewireScripts --}}
    </body>
</html>
