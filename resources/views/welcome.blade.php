<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="SJ LegalSuite — Sistema de gestión jurídica disciplinaria">

        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) }}">
        <link rel="apple-touch-icon" href="{{ asset('images/logo solo.png') }}">

        <title>{{ config('app.name', 'SJ LegalSuite') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans">
        <div class="relative min-h-screen overflow-hidden">

            {{-- Imagen de fondo --}}
            <div class="absolute inset-0 -z-10">
                <img src="{{ asset('images/welcome.png') }}"
                     alt="SJ LegalSuite"
                     class="h-full w-full object-cover object-center"
                     loading="eager"
                     fetchpriority="high">
                {{-- Overlay para legibilidad del texto --}}
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/85 via-slate-950/55 to-slate-950/30"></div>
            </div>

            {{-- Header --}}
            <header class="relative z-10">
                <div class="max-w-7xl mx-auto px-6 lg:px-8 py-6 flex items-center justify-between">
                    <div class="flex items-center gap-3 text-white">
                        <img src="{{ asset('images/logo solo.png') }}"
                             alt="SJ LegalSuite"
                             class="h-12 w-auto drop-shadow-lg">
                        <div class="leading-tight">
                            <p class="text-sm font-semibold tracking-wide">SJ LegalSuite</p>
                            <p class="text-[11px] text-white/70 uppercase tracking-widest">Gestión jurídica</p>
                        </div>
                    </div>

                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/20 hover:bg-white/20 backdrop-blur-sm transition">
                            Ir al sistema →
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/20 hover:bg-white/20 backdrop-blur-sm transition">
                            Iniciar sesión
                        </a>
                    @endauth
                </div>
            </header>

            {{-- Hero --}}
            <main class="relative z-10">
                <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 lg:py-32">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white ring-1 ring-white/20 backdrop-blur-sm">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Sistema operativo
                        </span>

                        <h1 class="mt-6 text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight tracking-tight">
                            Gestión jurídica
                            <span class="block bg-gradient-to-r from-indigo-300 via-sky-200 to-emerald-300 bg-clip-text text-transparent">
                                disciplinaria
                            </span>
                        </h1>

                        <p class="mt-6 text-lg text-white/85 max-w-xl leading-relaxed">
                            Plataforma centralizada para administrar procesos disciplinarios con control de etapas,
                            trazabilidad legal completa y reportes en tiempo real.
                        </p>

                        <div class="mt-10 flex flex-col sm:flex-row gap-4">
                            @auth
                                <a href="{{ route('disciplinary.dashboard') }}"
                                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-8 py-4 text-base font-semibold text-slate-900 shadow-lg hover:bg-slate-100 transition">
                                    Ir al dashboard
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                   class="group inline-flex items-center justify-center gap-2 rounded-lg bg-white px-8 py-4 text-base font-semibold text-slate-900 shadow-lg hover:bg-slate-100 transition">
                                    Ingresar
                                    <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            @endauth
                        </div>

                        {{-- Mini features --}}
                        <div class="mt-14 grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl">
                            <div class="rounded-lg bg-white/5 backdrop-blur-sm ring-1 ring-white/10 p-4">
                                <div class="h-9 w-9 rounded-md bg-indigo-500/20 flex items-center justify-center text-indigo-300">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-white">Trazabilidad legal</p>
                                <p class="text-xs text-white/70 mt-1">Audit log inmutable</p>
                            </div>
                            <div class="rounded-lg bg-white/5 backdrop-blur-sm ring-1 ring-white/10 p-4">
                                <div class="h-9 w-9 rounded-md bg-sky-500/20 flex items-center justify-center text-sky-300">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-white">Reportes en vivo</p>
                                <p class="text-xs text-white/70 mt-1">KPIs por abogado y ciudad</p>
                            </div>
                            <div class="rounded-lg bg-white/5 backdrop-blur-sm ring-1 ring-white/10 p-4">
                                <div class="h-9 w-9 rounded-md bg-emerald-500/20 flex items-center justify-center text-emerald-300">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-white">Roles y permisos</p>
                                <p class="text-xs text-white/70 mt-1">Control granular por área</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            {{-- Footer --}}
            <footer class="absolute bottom-0 inset-x-0 z-10">
                <div class="max-w-7xl mx-auto px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-white/60">
                    <p>© {{ now()->year }} SJ Seguridad. Todos los derechos reservados.</p>
                    <p class="font-mono">v{{ Illuminate\Foundation\Application::VERSION }}</p>
                </div>
            </footer>

        </div>
    </body>
</html>
