@props([
    'active' => 'territory',
])

@php
    $isDark = ($uiTheme ?? 'light') === 'dark';
    $canTerritory = auth()->user()?->can('settings.manage-territory') ?? false;
    $canArticles = auth()->user()?->can('settings.manage-citation-articles') ?? false;
    $canQuestions = auth()->user()?->can('settings.manage-diligence-questions') ?? false;

    $tabs = [];
    if ($canTerritory) {
        $tabs['territory'] = [
            'label' => 'Territorio',
            'route' => route('settings.territory-import'),
        ];
    }
    if ($canArticles) {
        $tabs['articles'] = [
            'label' => 'Artículos',
            'route' => route('settings.citation-articles'),
        ];
    }
    if ($canQuestions) {
        $tabs['questions'] = [
            'label' => 'Preguntas',
            'route' => route('settings.diligence-questions'),
        ];
    }

    $burger = $isDark
        ? 'text-slate-200 hover:bg-white/10'
        : 'text-white/90 hover:bg-white/10';

    $profile = $isDark
        ? 'text-slate-200 hover:bg-white/10 hover:text-white'
        : 'text-white/90 hover:bg-white/10 hover:text-white';

    $logoutVariant = $isDark ? 'dark' : 'light';
@endphp

<header class="sticky top-0 z-20 border-b border-black/80 bg-[#2f5f6e] dark:bg-[#1e4a57]">
    <div class="flex items-end justify-between gap-3 px-4 lg:px-6">
        <div class="flex min-w-0 flex-1 items-end gap-2">
            <button type="button" x-on:click="sidebarOpen = true"
                    class="mb-1.5 -ml-2 shrink-0 rounded-lg p-2 lg:hidden {{ $burger }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            @if (count($tabs) > 0)
                <nav class="min-w-0 flex-1 overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    aria-label="Ajustes del sistema">
                    <div class="flex items-end gap-1 pt-2" role="tablist">
                        @foreach ($tabs as $key => $tab)
                            @php $isActive = $active === $key; @endphp
                            <a href="{{ $tab['route'] }}"
                                wire:navigate
                                role="tab"
                                aria-selected="{{ $isActive ? 'true' : 'false' }}"
                                @class([
                                    'settings-nav-tab',
                                    'settings-nav-tab-active' => $isActive,
                                ])>
                                {{ $tab['label'] }}
                            </a>
                        @endforeach
                    </div>
                </nav>
            @else
                <p class="py-3 text-sm font-semibold text-white">Ajustes</p>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-1 pb-2 sm:gap-1.5">
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
