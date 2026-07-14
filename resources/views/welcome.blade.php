<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="SJ LegalSuite — Sistema de gestión jurídica disciplinaria">

        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) }}">
        <link rel="apple-touch-icon" href="{{ \App\Support\Disciplinary\DisciplinaryAssets::logoPublicUrl() }}">

        <title>{{ config('app.name', 'SJ LegalSuite') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/js/app.js'])

        <style>
            @keyframes welcome-fade-up {
                from { opacity: 0; transform: translateY(14px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .welcome-anim {
                animation: welcome-fade-up 0.65s ease-out both;
            }
            .welcome-anim-delay-1 { animation-delay: 0.08s; }
            .welcome-anim-delay-2 { animation-delay: 0.16s; }
            .welcome-anim-delay-3 { animation-delay: 0.26s; }
            .welcome-anim-delay-4 { animation-delay: 0.36s; }
            .welcome-anim-delay-5 { animation-delay: 0.46s; }
            .welcome-anim-delay-6 { animation-delay: 0.56s; }
            @media (prefers-reduced-motion: reduce) {
                .welcome-anim { animation: none; }
            }
        </style>
    </head>
    <body class="antialiased font-sans">
        @php
            // Ancho útil casi full-bleed: sin max-w-7xl. Tope solo en pantallas extremas.
            $shell = 'mx-auto w-full max-w-[100rem] px-5 sm:px-8 md:px-10 lg:px-12 xl:px-16 2xl:px-20';
        @endphp

        <div class="relative flex min-h-dvh flex-col overflow-hidden">

            {{-- Fondo --}}
            <div class="absolute inset-0 -z-10">
                <img src="{{ asset('images/welcome.png') }}"
                     alt=""
                     class="h-full w-full object-cover object-[center_30%] sm:object-center"
                     loading="eager"
                     fetchpriority="high">
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/92 via-slate-950/70 to-slate-950/35 md:via-slate-950/55 md:to-slate-950/20 lg:from-slate-950/90 lg:via-slate-950/45 lg:to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-t from-slate-950/75 to-transparent sm:h-44 lg:h-52"></div>
            </div>

            {{-- Header edge-to-edge útil --}}
            <header class="relative z-10 shrink-0">
                <div class="{{ $shell }} flex items-center justify-between gap-4 py-4 sm:py-5 lg:py-6">
                    <div class="flex min-w-0 items-center gap-2.5 text-white sm:gap-3">
                        <img src="{{ \App\Support\Disciplinary\DisciplinaryAssets::logoPublicUrl() }}"
                             alt="SJ LegalSuite"
                             class="h-10 w-auto drop-shadow-lg sm:h-12">
                        <div class="min-w-0 leading-tight">
                            <p class="truncate text-sm font-semibold tracking-wide sm:text-base">SJ LegalSuite</p>
                            <p class="text-[10px] uppercase tracking-[0.16em] text-white/70 sm:text-[11px]">Gestión jurídica</p>
                        </div>
                    </div>

                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-medium text-white ring-1 ring-white/20 backdrop-blur-sm transition hover:bg-white/20 sm:px-4">
                            Ir al sistema →
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-medium text-white ring-1 ring-white/20 backdrop-blur-sm transition hover:bg-white/20 sm:px-4">
                            Iniciar sesión
                        </a>
                    @endauth
                </div>
            </header>

            <main class="relative z-10 flex min-h-0 flex-1 flex-col">
                <div class="{{ $shell }} flex flex-1 flex-col justify-between gap-8 py-8 sm:gap-10 sm:py-12 md:py-14 lg:gap-12 lg:py-16">

                    {{-- Hero: copy ~50% en lg+; foto respira a la derecha --}}
                    <div class="grid flex-1 items-center gap-8 lg:grid-cols-2 lg:gap-12 xl:gap-16">
                        <div class="max-w-none lg:max-w-[36rem] xl:max-w-[40rem] 2xl:max-w-[44rem]">
                            <span class="welcome-anim inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white ring-1 ring-white/20 backdrop-blur-sm">
                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                                Sistema operativo
                            </span>

                            <h1 class="welcome-anim welcome-anim-delay-1 mt-4 text-[2.125rem] font-extrabold leading-[1.08] tracking-tight text-white sm:mt-5 sm:text-5xl md:text-5xl lg:text-6xl xl:text-[4rem] 2xl:text-[4.25rem]">
                                Gestión jurídica
                                <span class="mt-1 block bg-gradient-to-r from-indigo-300 via-sky-200 to-emerald-300 bg-clip-text text-transparent">
                                    disciplinaria
                                </span>
                            </h1>

                            <p class="welcome-anim welcome-anim-delay-2 mt-4 max-w-prose text-[0.95rem] leading-relaxed text-white/85 sm:mt-5 sm:text-lg lg:max-w-xl xl:text-xl xl:leading-relaxed">
                                Plataforma centralizada para administrar procesos disciplinarios con control de etapas,
                                trazabilidad legal completa y reportes en tiempo real.
                            </p>

                            <div class="welcome-anim welcome-anim-delay-3 mt-7 sm:mt-9">
                                @auth
                                    <a href="{{ route('disciplinary.dashboard') }}"
                                       class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-7 py-3.5 text-base font-semibold text-slate-900 shadow-lg transition hover:bg-slate-100 sm:w-auto sm:px-8 sm:py-4">
                                        Ir al dashboard
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}"
                                       class="group inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-7 py-3.5 text-base font-semibold text-slate-900 shadow-lg transition hover:bg-slate-100 sm:w-auto sm:px-8 sm:py-4">
                                        Ingresar
                                        <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                @endauth
                            </div>
                        </div>

                        <div class="hidden lg:block" aria-hidden="true"></div>
                    </div>

                    {{-- Features: 1 col móvil · 3 cols desde sm · ancho casi completo --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4 md:gap-5 xl:gap-6">
                        <div class="welcome-anim welcome-anim-delay-4 rounded-xl bg-white/[0.08] p-4 ring-1 ring-white/15 backdrop-blur-md transition hover:bg-white/[0.12] sm:p-5 xl:p-6">
                            <div class="flex items-start gap-3 sm:flex-col sm:gap-3.5 lg:flex-row lg:items-start xl:gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-500/25 text-indigo-200 xl:h-11 xl:w-11">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-white sm:text-[15px] xl:text-base">Trazabilidad legal</p>
                                    <p class="mt-1 text-xs leading-snug text-white/70 sm:text-[13px] xl:text-sm">Audit log inmutable en cada etapa del proceso.</p>
                                </div>
                            </div>
                        </div>

                        <div class="welcome-anim welcome-anim-delay-5 rounded-xl bg-white/[0.08] p-4 ring-1 ring-white/15 backdrop-blur-md transition hover:bg-white/[0.12] sm:p-5 xl:p-6">
                            <div class="flex items-start gap-3 sm:flex-col sm:gap-3.5 lg:flex-row lg:items-start xl:gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-500/25 text-sky-200 xl:h-11 xl:w-11">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-white sm:text-[15px] xl:text-base">Reportes en vivo</p>
                                    <p class="mt-1 text-xs leading-snug text-white/70 sm:text-[13px] xl:text-sm">KPIs por abogado, ciudad y etapa del workflow.</p>
                                </div>
                            </div>
                        </div>

                        <div class="welcome-anim welcome-anim-delay-6 rounded-xl bg-white/[0.08] p-4 ring-1 ring-white/15 backdrop-blur-md transition hover:bg-white/[0.12] sm:p-5 xl:p-6">
                            <div class="flex items-start gap-3 sm:flex-col sm:gap-3.5 lg:flex-row lg:items-start xl:gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/25 text-emerald-200 xl:h-11 xl:w-11">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-white sm:text-[15px] xl:text-base">Roles y permisos</p>
                                    <p class="mt-1 text-xs leading-snug text-white/70 sm:text-[13px] xl:text-sm">Control granular por área, cargo y nivel.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="relative z-10 shrink-0 border-t border-white/10 bg-slate-950/25 backdrop-blur-sm">
                <div class="{{ $shell }} flex flex-col items-center justify-between gap-2 py-3.5 text-xs text-white/60 sm:flex-row sm:py-4">
                    <p class="text-center sm:text-left">© {{ now()->year }} SJ Seguridad. Todos los derechos reservados.</p>
                    <p class="font-mono">v{{ Illuminate\Foundation\Application::VERSION }}</p>
                </div>
            </footer>

        </div>
    </body>
</html>
