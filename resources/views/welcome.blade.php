<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>IPAC Laboratorio - Sistema de Gestión</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --ipac-turquoise: #00BFB3;
            --ipac-dark: #2D3436;
        }
        
        .bg-gradient-ipac {
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f7f5 50%, #d1f4f0 100%);
        }
        
        .text-ipac {
            color: var(--ipac-turquoise);
        }
        
        .bg-ipac {
            background-color: var(--ipac-turquoise);
        }
        
        .border-ipac {
            border-color: var(--ipac-turquoise);
        }
        
        .hover\:bg-ipac-dark:hover {
            background-color: #00a89d;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logo-glow {
            filter: drop-shadow(0 0 30px rgba(0, 191, 179, 0.3));
        }
        
        .fade-in {
            animation: fadeIn 1s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pattern-overlay {
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(0, 191, 179, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(0, 191, 179, 0.1) 0%, transparent 50%);
        }
        
        .glass-effect-light {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 191, 179, 0.2);
        }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen bg-gradient-ipac pattern-overlay">
        {{-- Navbar --}}
        <nav class="fixed top-0 left-0 right-0 z-50 glass-effect-light shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    {{-- Logo --}}
                    <div class="flex items-center">
                        <img src="{{ asset('images/logo_ipac.png') }}" alt="IPAC Laboratorio" class="h-12 w-auto">
                    </div>
                    
                    {{-- Navigation Links --}}
                    @if (Route::has('login'))
                        <div class="flex items-center space-x-4">
                            @auth
                                <a href="{{ url('/dashboard') }}" 
                                   class="px-6 py-2.5 bg-ipac text-white font-medium rounded-lg hover:bg-ipac-dark transition-all duration-300 shadow-lg shadow-[#00BFB3]/20">
                                    Ir al Sistema
                                </a>
                            @else
                                <a href="{{ route('login') }}" 
                                   class="px-6 py-2.5 text-gray-700 font-medium hover:text-ipac transition-colors duration-300">
                                    Ingresar
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" 
                                       class="px-6 py-2.5 bg-ipac text-white font-medium rounded-lg hover:bg-ipac-dark transition-all duration-300 shadow-lg shadow-[#00BFB3]/20">
                                        Registrarse
                                    </a>
                                @endif
                            @endauth
                        </div>
                    @endif
                </div>
            </div>
        </nav>

        {{-- Hero Section --}}
        <div class="flex flex-col items-center justify-center min-h-screen px-4 pt-20">
            <div class="text-center fade-in max-w-4xl mx-auto">
                {{-- Logo grande centrado --}}
                <div class="mb-12">
                    <img src="{{ asset('images/logo_ipac.png') }}" alt="IPAC Laboratorio" 
                         class="h-32 md:h-40 w-auto mx-auto logo-glow">
                </div>
                
                {{-- Título Principal --}}
                <h1 class="text-4xl md:text-6xl font-bold mb-6 tracking-tight">
                    <span class="text-gray-800">Sistema de Gestión</span>
                    <span class="block text-ipac mt-2">Integral</span>
                </h1>
                
                {{-- Subtítulo --}}
                <p class="text-xl md:text-2xl text-gray-600 mb-12 font-light max-w-2xl mx-auto leading-relaxed">
                    Plataforma profesional para la administración de laboratorio, 
                    recursos humanos y gestión de personal.
                </p>
                
                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @auth
                        <a href="{{ url('/dashboard') }}" 
                           class="px-10 py-4 bg-ipac text-white text-lg font-semibold rounded-xl hover:bg-ipac-dark transition-all duration-300 shadow-2xl shadow-[#00BFB3]/30 transform hover:scale-105">
                            Acceder al Sistema
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="px-10 py-4 bg-ipac text-white text-lg font-semibold rounded-xl hover:bg-ipac-dark transition-all duration-300 shadow-2xl shadow-[#00BFB3]/30 transform hover:scale-105">
                            Iniciar Sesión
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" 
                               class="px-10 py-4 bg-transparent text-gray-700 text-lg font-semibold rounded-xl border-2 border-gray-300 hover:border-ipac hover:text-ipac transition-all duration-300">
                                Crear Cuenta
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
            
            {{-- Features Section --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-20 max-w-5xl mx-auto px-4 fade-in" style="animation-delay: 0.3s;">
                <div class="bg-white rounded-2xl p-6 text-center shadow-lg border border-gray-100 hover:border-ipac hover:shadow-xl transition-all duration-300">
                    <div class="w-14 h-14 bg-ipac/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-ipac" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <h3 class="text-gray-800 font-semibold text-lg mb-2">Gestión de Laboratorio</h3>
                    <p class="text-gray-500 text-sm">Administración completa de muestras, protocolos y resultados.</p>
                </div>
                
                <div class="bg-white rounded-2xl p-6 text-center shadow-lg border border-gray-100 hover:border-ipac hover:shadow-xl transition-all duration-300">
                    <div class="w-14 h-14 bg-ipac/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-ipac" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-gray-800 font-semibold text-lg mb-2">Recursos Humanos</h3>
                    <p class="text-gray-500 text-sm">Control de personal, licencias, vacaciones y novedades.</p>
                </div>
                
                <div class="bg-white rounded-2xl p-6 text-center shadow-lg border border-gray-100 hover:border-ipac hover:shadow-xl transition-all duration-300">
                    <div class="w-14 h-14 bg-ipac/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-ipac" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-gray-800 font-semibold text-lg mb-2">Liquidación de Sueldos</h3>
                    <p class="text-gray-500 text-sm">Sistema completo de liquidación según CCT 108/75.</p>
                </div>
            </div>
        </div>
        
        {{-- Footer --}}
        <footer class="py-8 text-center text-gray-600 text-sm">
            <p>&copy; {{ date('Y') }} IPAC Laboratorio. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>