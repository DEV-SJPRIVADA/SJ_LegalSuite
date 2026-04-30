<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) }}">
        <link rel="apple-touch-icon" href="{{ asset('images/logo solo.png') }}">

        <title>{{ config('app.name', 'SJ LegalSuite') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50">
        <div x-data="{ sidebarOpen: false }"
             x-on:sidebar-toggle.window="sidebarOpen = !sidebarOpen"
             class="min-h-screen flex">

            {{-- Backdrop móvil --}}
            <div x-show="sidebarOpen"
                 x-transition.opacity
                 x-on:click="sidebarOpen = false"
                 class="fixed inset-0 z-30 bg-black/50 lg:hidden"
                 style="display: none;"></div>

            {{-- Sidebar --}}
            <x-app-sidebar />

            {{-- Main content --}}
            <div class="flex-1 flex flex-col min-w-0">

                @php
                    /**
                     * Si la vista del componente Livewire registró un sub-nav vía @push('module-nav'),
                     * ese sub-nav reemplaza al topbar genérico (incluye links del módulo + acciones de usuario).
                     * Si no, mostramos el topbar genérico (Inicio global, Profile, etc.).
                     */
                    $hasModuleNav = ! empty(trim($__env->yieldPushContent('module-nav')));
                @endphp

                @if ($hasModuleNav)
                    {{-- El sub-nav del módulo es la única barra superior --}}
                    @stack('module-nav')
                @else
                    {{-- Topbar genérico para vistas sin sub-nav (Inicio global, Profile) --}}
                    <header class="bg-white border-b border-slate-200 sticky top-0 z-20">
                        <div class="flex items-center justify-between px-4 lg:px-6 py-3">
                            <button x-on:click="sidebarOpen = true"
                                    class="lg:hidden p-2 -ml-2 rounded-md text-slate-700 hover:bg-slate-100">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </button>

                            <div class="flex-1"></div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('profile') }}" wire:navigate
                                   class="hidden sm:block text-sm text-slate-600 hover:text-slate-900">
                                    Mi perfil
                                </a>
                                <livewire:auth.logout-button />
                            </div>
                        </div>
                    </header>
                @endif

                {{-- Page content --}}
                <main class="flex-1 overflow-y-auto">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
