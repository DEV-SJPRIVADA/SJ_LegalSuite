@php
    $isDark = ($uiTheme ?? 'light') === 'dark';

    $links = [
        ['key' => 'home', 'label' => 'Inicio', 'route' => route('dashboard'), 'active' => false],
        ['key' => 'index', 'label' => 'Usuarios', 'route' => route('users.index'), 'active' => request()->routeIs('users.index') || request()->routeIs('users.show')],
        ['key' => 'organization', 'label' => 'Organización', 'route' => route('users.organization'), 'active' => request()->routeIs('users.organization')],
        ['key' => 'roles', 'label' => 'Roles', 'route' => null, 'active' => false, 'soon' => true],
        ['key' => 'audit', 'label' => 'Auditoría de accesos', 'route' => null, 'active' => false, 'soon' => true],
    ];

    $header = $isDark
        ? 'border-b border-white/10 bg-dash-ink/85 backdrop-blur-md sticky top-0 z-20'
        : 'bg-white border-b border-slate-200 sticky top-0 z-20';

    $burger = $isDark
        ? 'text-slate-200 hover:bg-white/10'
        : 'text-slate-700 hover:bg-slate-100';

    $linkActive = $isDark
        ? 'border-cyan-400/90 text-white'
        : 'border-indigo-600 text-indigo-700';

    $linkIdle = $isDark
        ? 'border-transparent text-slate-400 hover:border-white/15 hover:text-white'
        : 'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900';

    $soonBadge = $isDark
        ? 'bg-white/10 text-slate-300'
        : 'bg-slate-200 text-slate-600';

    $soonText = $isDark ? 'text-slate-500' : 'text-slate-400';

    $profile = $isDark
        ? 'text-dash-muted hover:bg-white/10 hover:text-white'
        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';

    $logoutVariant = $isDark ? 'dark' : 'light';

    $links = array_values(array_filter($links, function (array $l): bool {
        if (($l['key'] ?? '') === 'organization') {
            return auth()->user()?->can('create', \App\Models\User::class) ?? false;
        }

        return true;
    }));
@endphp

<header class="{{ $header }}">
    <div class="flex items-center gap-3 px-4 lg:px-6">
        <div class="flex min-w-0 flex-1 items-center gap-1">
            <button type="button" x-on:click="sidebarOpen = true"
                    class="lg:hidden -ml-2 shrink-0 rounded-lg p-2 {{ $burger }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <nav class="min-w-0 flex-1" aria-label="Módulo usuarios">
                <ul class="flex items-center gap-0.5 overflow-x-auto text-sm [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($links as $link)
                        <li class="shrink-0">
                            @if ($link['route'] && empty($link['soon']))
                                <a href="{{ $link['route'] }}" wire:navigate
                                   class="inline-flex items-center border-b-2 px-3 py-3 font-medium transition whitespace-nowrap sm:px-4
                                          {{ $link['active'] ? $linkActive : $linkIdle }}">
                                    {{ $link['label'] }}
                                </a>
                            @else
                                <span class="inline-flex cursor-not-allowed items-center gap-2 border-b-2 border-transparent px-3 py-3 font-medium whitespace-nowrap sm:px-4 {{ $soonText }}">
                                    {{ $link['label'] }}
                                    <span class="rounded px-1.5 py-0.5 text-[9px] uppercase tracking-wider {{ $soonBadge }}">Próx.</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>

        <div class="flex shrink-0 items-center gap-1 sm:gap-1.5">
            @auth
                <livewire:ui.notification-bell />
                <livewire:ui.theme-toggle />
            @endauth
            <a href="{{ route('profile') }}" wire:navigate
               class="hidden h-9 items-center rounded-lg px-2.5 text-sm font-medium transition sm:inline-flex {{ $profile }}">
                Mi perfil
            </a>
            <livewire:auth.logout-button :variant="$logoutVariant" />
        </div>
    </div>
</header>
