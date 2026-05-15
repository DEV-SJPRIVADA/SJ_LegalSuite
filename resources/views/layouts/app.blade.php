<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ ($uiTheme ?? 'light') === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ @filemtime(public_path('favicon.ico')) }}">
        <link rel="apple-touch-icon" href="{{ \App\Support\Disciplinary\DisciplinaryAssets::logoPublicUrl() }}">

        <title>{{ config('app.name', 'SJ LegalSuite') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @auth
            @if (\App\Support\Broadcasting\PusherBroadcasting::isEnabled())
                <script>
                    window.__appBroadcasting = {
                        userId: {{ auth()->id() }},
                        key: @json((string) config('broadcasting.connections.pusher.key')),
                        cluster: @json((string) config('broadcasting.connections.pusher.options.cluster', 'mt1')),
                        forceTls: @json((bool) (config('broadcasting.connections.pusher.options.useTLS') ?? true)),
                    };
                </script>
            @endif
        @endauth

        @vite(['resources/js/app.js'])
    </head>
    @php
        $sidebarVariant = ($uiTheme ?? 'light') === 'dark' ? 'neon' : 'light';
        $logoutVariant = ($uiTheme ?? 'light') === 'dark' ? 'dark' : 'light';
    @endphp
    <body class="font-sans antialiased bg-slate-50 text-slate-900 dark:bg-dash-void dark:text-slate-100">
        <div x-data="{ sidebarOpen: false }"
             x-on:sidebar-toggle.window="sidebarOpen = !sidebarOpen"
             class="min-h-screen flex">

            {{-- Backdrop móvil --}}
            <div x-show="sidebarOpen"
                 x-transition.opacity
                 x-on:click="sidebarOpen = false"
                 class="fixed inset-0 z-30 bg-black/50 lg:hidden dark:bg-black/60"
                 style="display: none;"></div>

            <x-app-sidebar :variant="$sidebarVariant" />

            <div class="flex-1 flex flex-col min-w-0">

                @php
                    $hasModuleNav = ! empty(trim($__env->yieldPushContent('module-nav')));
                    $informesOnlyNav = auth()->check()
                        && ! auth()->user()->canSeeFullAppSidebar()
                        && ! request()->routeIs('password.force-change');
                @endphp

                @if ($hasModuleNav)
                    @stack('module-nav')
                @elseif ($informesOnlyNav)
                    <x-disciplinary.nav />
                @else
                    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white dark:border-white/10 dark:bg-dash-ink/90 dark:backdrop-blur-md">
                        <div class="flex items-center justify-between px-4 lg:px-6 py-3 gap-3">
                            <button x-on:click="sidebarOpen = true"
                                    class="lg:hidden p-2 -ml-2 rounded-lg text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </button>

                            <div class="flex-1"></div>

                            <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                                @auth
                                    <livewire:ui.notification-bell />
                                    <livewire:ui.theme-toggle />
                                @endauth
                                <a href="{{ route('profile') }}" wire:navigate
                                   class="hidden sm:block text-sm text-slate-600 hover:text-slate-900 dark:text-dash-muted dark:hover:text-white">
                                    Mi perfil
                                </a>
                                <livewire:auth.logout-button :variant="$logoutVariant" />
                            </div>
                        </div>
                    </header>
                @endif

                <main class="flex-1 overflow-y-auto relative">
                    <div class="pointer-events-none absolute inset-0 hidden bg-[radial-gradient(ellipse_120%_80%_at_50%_-20%,rgba(217,70,239,0.18),transparent_55%),radial-gradient(ellipse_90%_60%_at_100%_50%,rgba(34,211,238,0.12),transparent_45%),radial-gradient(ellipse_70%_50%_at_0%_80%,rgba(251,146,60,0.08),transparent_40%)] dark:block"></div>
                    <div class="relative">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
