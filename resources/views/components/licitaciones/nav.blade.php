@php
    $isDark = ($uiTheme ?? 'light') === 'dark';
    $model = \App\Models\Licitaciones\Licitacion::class;
    $links = [];
    if (auth()->user()->canSeeFullAppSidebar()) {
        $links[] = ['label' => 'Inicio', 'route' => route('dashboard'), 'active' => request()->routeIs('dashboard')];
    }
    if (auth()->user()->can('viewDashboard', $model)) {
        $links[] = ['label' => 'Dashboard', 'route' => route('licitaciones.dashboard'), 'active' => request()->routeIs('licitaciones.dashboard')];
    }
    $links[] = ['label' => 'Licitaciones', 'route' => route('licitaciones.procesos.index'), 'active' => request()->routeIs('licitaciones.procesos.*')];
    $links[] = ['label' => 'Solicitudes', 'route' => route('licitaciones.solicitudes.index'), 'active' => request()->routeIs('licitaciones.solicitudes.*')];
    if (auth()->user()->can('viewDashboard', $model)) {
        $links[] = ['label' => 'Informes', 'route' => route('licitaciones.informes.index'), 'active' => request()->routeIs('licitaciones.informes.*')];
    }
@endphp
<header class="{{ $isDark ? 'border-b border-white/10 bg-dash-ink/85 backdrop-blur-md sticky top-0 z-20' : 'bg-white border-b border-slate-200 sticky top-0 z-20' }}">
    <div class="flex items-center justify-between gap-2 px-4 lg:px-6 flex-wrap">
        <nav class="min-w-0 flex-1">
            <ul class="flex items-center gap-1 overflow-x-auto text-sm">
                @foreach ($links as $link)
                    <li>
                        <a href="{{ $link['route'] }}" wire:navigate
                           class="inline-flex px-4 py-3 border-b-2 font-medium whitespace-nowrap {{ $link['active'] ? ($isDark ? 'border-cyan-400 text-white' : 'border-indigo-600 text-indigo-700') : ($isDark ? 'border-transparent text-slate-400' : 'border-transparent text-slate-600') }}">
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
        <div class="flex items-center gap-2 py-2">
            @auth
                <livewire:ui.notification-bell />
                <livewire:ui.theme-toggle />
            @endauth
            <livewire:auth.logout-button :variant="$isDark ? 'dark' : 'light'" />
        </div>
    </div>
</header>
