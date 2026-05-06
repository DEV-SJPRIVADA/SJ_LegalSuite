@php
    $isDark = ($uiTheme ?? 'light') === 'dark';
    $disciplinaryCaseModel = \App\Models\Disciplinary\DisciplinaryCase::class;

    $links = [];

    if (! auth()->user()->isMinimalDisciplinaryPortalUser()) {
        $links[] = ['key' => 'home', 'label' => 'Inicio', 'route' => route('dashboard'), 'active' => request()->routeIs('dashboard')];
    }

    if (auth()->user()->can('viewDashboard', $disciplinaryCaseModel)) {
        $links[] = ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => route('disciplinary.dashboard'), 'active' => request()->routeIs('disciplinary.dashboard')];
    }

    $links[] = [
        'key' => 'cases',
        'label' => auth()->user()->isDisciplinaryProgramador() ? 'Mis solicitudes' : 'Disciplinarios',
        'route' => route('disciplinary.cases.index'),
        'active' => request()->routeIs('disciplinary.cases.*'),
    ];

    $informeSubmissionModel = \App\Models\Disciplinary\InformeSubmission::class;

    if (auth()->user()->can('viewAny', $informeSubmissionModel)) {
        $links[] = [
            'key' => 'informes-pend',
            'label' => 'Revisión informes',
            'route' => route('disciplinary.informes-pendientes.index'),
            'active' => request()->routeIs('disciplinary.informes-pendientes.*'),
        ];
    }

    if (auth()->user()->can('viewOfficialForms', $disciplinaryCaseModel)) {
        $links[] = ['key' => 'formats', 'label' => 'Formatos', 'route' => route('disciplinary.formats.index'), 'active' => request()->routeIs('disciplinary.formats.*'), 'soon' => false];
    }

    $links[] = ['key' => 'history', 'label' => 'Historial', 'route' => null, 'active' => false, 'soon' => true];

    $header = $isDark
        ? 'border-b border-white/10 bg-dash-ink/85 backdrop-blur-md sticky top-0 z-20'
        : 'bg-white border-b border-slate-200 sticky top-0 z-20';

    $burger = $isDark
        ? 'text-slate-200 hover:bg-white/10'
        : 'text-slate-700 hover:bg-slate-100';

    $linkActive = $isDark
        ? 'border-cyan-400 text-white shadow-[0_0_18px_-4px_rgba(34,211,238,0.45)]'
        : 'border-indigo-600 text-indigo-700';

    $linkIdle = $isDark
        ? 'border-transparent text-slate-400 hover:text-white hover:border-white/20'
        : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300';

    $soonBadge = $isDark
        ? 'bg-white/10 text-slate-300'
        : 'bg-slate-200 text-slate-600';

    $soonText = $isDark ? 'text-slate-500' : 'text-slate-400';

    $profile = $isDark
        ? 'text-dash-muted hover:text-white'
        : 'text-slate-600 hover:text-slate-900';

    $logoutVariant = $isDark ? 'dark' : 'light';
@endphp

<header class="{{ $header }}">
    <div class="flex items-center justify-between gap-2 px-4 lg:px-6 flex-wrap">

        <div class="flex items-center gap-1 min-w-0 flex-1">
            <button x-on:click="sidebarOpen = true"
                    class="lg:hidden p-2 -ml-2 rounded-lg {{ $burger }} flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <nav class="min-w-0">
                <ul class="flex items-center gap-1 overflow-x-auto text-sm">
                    @foreach ($links as $link)
                        <li>
                            @if ($link['route'] && empty($link['soon']))
                                <a href="{{ $link['route'] }}" wire:navigate
                                   class="inline-flex items-center px-4 py-3 border-b-2 transition whitespace-nowrap font-medium
                                          {{ $link['active'] ? $linkActive : $linkIdle }}">
                                    {{ $link['label'] }}
                                </a>
                            @else
                                <span class="inline-flex items-center gap-2 px-4 py-3 border-b-2 border-transparent {{ $soonText }} whitespace-nowrap font-medium cursor-not-allowed">
                                    {{ $link['label'] }}
                                    <span class="text-[9px] uppercase tracking-wider px-1.5 py-0.5 rounded {{ $soonBadge }}">Próx.</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>

        <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0 py-2 lg:py-0">
            @auth
                <livewire:ui.theme-toggle />
            @endauth
            <a href="{{ route('profile') }}" wire:navigate
               class="hidden sm:block text-sm {{ $profile }}">
                Mi perfil
            </a>
            <livewire:auth.logout-button :variant="$logoutVariant" />
        </div>

    </div>
</header>
