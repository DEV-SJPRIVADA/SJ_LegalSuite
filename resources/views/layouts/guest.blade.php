<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) }}">
        <link rel="apple-touch-icon" href="{{ \App\Support\Disciplinary\DisciplinaryAssets::logoPublicUrl() }}">

        <title>{{ config('app.name', 'SJ LegalSuite') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/js/app.js'])

        <style>
            @keyframes guest-fade-up {
                from { opacity: 0; transform: translateY(12px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .guest-anim {
                animation: guest-fade-up 0.55s ease-out both;
            }
            .guest-anim-delay-1 { animation-delay: 0.08s; }
            .guest-anim-delay-2 { animation-delay: 0.16s; }
            @media (prefers-reduced-motion: reduce) {
                .guest-anim { animation: none; }
            }
        </style>
    </head>
    <body class="font-sans antialiased text-slate-900">
        @php
            $shell = 'mx-auto w-full max-w-[100rem] px-5 sm:px-8 md:px-10 lg:px-12 xl:px-16 2xl:px-20';
        @endphp

        <div class="relative flex min-h-dvh flex-col overflow-hidden">
            {{-- Mismo fondo que welcome --}}
            <div class="absolute inset-0 -z-10">
                <img src="{{ asset('images/welcome.png') }}"
                     alt=""
                     class="h-full w-full object-cover object-[center_30%] sm:object-center"
                     loading="eager"
                     fetchpriority="high">
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/92 via-slate-950/72 to-slate-950/40 md:via-slate-950/60 md:to-slate-950/25 lg:from-slate-950/90 lg:via-slate-950/50 lg:to-slate-950/20"></div>
                <div class="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-t from-slate-950/70 to-transparent sm:h-44"></div>
            </div>

            <header class="relative z-10 shrink-0">
                <div class="{{ $shell }} flex items-center justify-between gap-4 py-4 sm:py-5">
                    <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-2.5 text-white sm:gap-3" wire:navigate>
                        <img src="{{ \App\Support\Disciplinary\DisciplinaryAssets::logoPublicUrl() }}"
                             alt="SJ LegalSuite"
                             class="h-10 w-auto drop-shadow-lg sm:h-11">
                        <div class="min-w-0 leading-tight">
                            <p class="truncate text-sm font-semibold tracking-wide sm:text-base">SJ LegalSuite</p>
                            <p class="text-[10px] uppercase tracking-[0.16em] text-white/70 sm:text-[11px]">Gestión jurídica</p>
                        </div>
                    </a>
                    <a href="{{ url('/') }}"
                       wire:navigate
                       class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-medium text-white ring-1 ring-white/20 backdrop-blur-sm transition hover:bg-white/20 sm:px-4">
                        ← Volver
                    </a>
                </div>
            </header>

            <main class="relative z-10 flex min-h-0 flex-1 flex-col">
                <div class="{{ $shell }} flex flex-1 flex-col justify-center gap-8 py-8 sm:py-10 lg:grid lg:grid-cols-2 lg:items-center lg:gap-12 xl:gap-16 lg:py-14">

                    {{-- Columna marca / copy --}}
                    <div class="guest-anim max-w-xl text-white lg:max-w-none">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium ring-1 ring-white/20 backdrop-blur-sm">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            Acceso seguro
                        </span>
                        <h1 class="guest-anim guest-anim-delay-1 mt-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl lg:text-5xl xl:text-[3.25rem]">
                            Ingresa a
                            <span class="mt-1 block bg-gradient-to-r from-indigo-300 via-sky-200 to-emerald-300 bg-clip-text text-transparent">
                                SJ LegalSuite
                            </span>
                        </h1>
                        <p class="guest-anim guest-anim-delay-2 mt-4 max-w-md text-sm leading-relaxed text-white/80 sm:text-base lg:text-lg">
                            Gestión jurídica disciplinaria con trazabilidad, control de etapas y reportes en tiempo real.
                        </p>
                    </div>

                    {{-- Panel formulario --}}
                    <div class="guest-anim guest-anim-delay-1 w-full lg:justify-self-end">
                        <div class="w-full max-w-md rounded-2xl bg-white/95 p-6 shadow-2xl ring-1 ring-white/40 backdrop-blur-md sm:p-8 lg:ml-auto lg:max-w-lg">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </main>

            <footer class="relative z-10 shrink-0 border-t border-white/10 bg-slate-950/25 backdrop-blur-sm">
                <div class="{{ $shell }} flex flex-col items-center justify-between gap-2 py-3.5 text-xs text-white/60 sm:flex-row sm:py-4">
                    <p>© {{ now()->year }} SJ Seguridad. Todos los derechos reservados.</p>
                    <p class="font-mono">v{{ Illuminate\Foundation\Application::VERSION }}</p>
                </div>
            </footer>
        </div>
    </body>
</html>
