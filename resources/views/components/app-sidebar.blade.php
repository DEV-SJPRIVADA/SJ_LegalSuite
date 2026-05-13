@php
    /**
     * Catálogo de módulos del sistema.
     *
     * - Roles «amplio»: admin (gerencia), abogado (dirección jurídica), auditor → ven todos los ítems (habilitados + Próx.).
     * - Resto → un ítem según rol: **Informes**, o **Diciplinarios** para **director** / **operaciones** (sidebar reducido).
     */
    $u = auth()->user();
    $disciplinaryCaseModel = \App\Models\Disciplinary\DisciplinaryCase::class;
    $canDisciplinaryDashboard = $u->can('viewDashboard', $disciplinaryCaseModel);
    $canDisciplinaryCases = $u->can('viewAny', $disciplinaryCaseModel);
    $disciplinaryAvailable = $canDisciplinaryDashboard || $canDisciplinaryCases;
    $disciplinaryRoute = $canDisciplinaryDashboard
        ? route('disciplinary.dashboard')
        : ($canDisciplinaryCases ? route('disciplinary.cases.index') : route('dashboard'));

    $fullAppSidebar = $u->canSeeFullAppSidebar();

    $sidebarBrandHref = $fullAppSidebar ? route('dashboard') : ($disciplinaryAvailable ? $disciplinaryRoute : route('dashboard'));

    if ($fullAppSidebar) {
        $modules = [];

        $modules[] = [
            'key' => 'home',
            'label' => 'Inicio',
            'route' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'icon' => 'home',
            'available' => true,
        ];

        $modules[] = [
            'key' => 'settings-territory',
            'label' => 'Ajustes',
            'route' => route('settings.territory-import'),
            'active' => request()->routeIs('settings.*'),
            'icon' => 'adjustments',
            'available' => $u->can('settings.manage-territory'),
        ];

        $modules[] = [
            'key' => 'disciplinary',
            'label' => $u->isDisciplinaryProgramador() ? 'Mis solicitudes' : 'Disciplinarios',
            'route' => $disciplinaryRoute,
            'active' => request()->routeIs('disciplinary.*'),
            'icon' => 'scale',
            'available' => $disciplinaryAvailable,
        ];

        $modules = array_merge($modules, [
            ['key' => 'licitaciones', 'label' => 'Licitaciones', 'icon' => 'briefcase', 'available' => false],
            ['key' => 'tutelas', 'label' => 'Acciones de tutela', 'icon' => 'shield-check', 'available' => false],
            ['key' => 'demandas', 'label' => 'Demandas', 'icon' => 'document-text', 'available' => false],
            ['key' => 'negociacion', 'label' => 'Negociación colectiva', 'icon' => 'chat-bubbles', 'available' => false],
            ['key' => 'investigaciones', 'label' => 'Investigaciones', 'icon' => 'search', 'available' => false],
            ['key' => 'cartera', 'label' => 'Cartera', 'icon' => 'banknotes', 'available' => false],
            ['key' => 'requisitos', 'label' => 'Requisitos legales', 'icon' => 'clipboard-check', 'available' => false],
            ['key' => 'contratos', 'label' => 'Contratos', 'icon' => 'document-duplicate', 'available' => false],
            ['key' => 'polizas', 'label' => 'Pólizas', 'icon' => 'shield', 'available' => false],
            ['key' => 'auditoria', 'label' => 'Auditoría', 'icon' => 'chart-bar', 'available' => false],
            [
                'key' => 'users',
                'label' => 'Usuarios',
                'route' => route('users.index'),
                'active' => request()->routeIs('users.*'),
                'icon' => 'user-cog',
                'available' => $u->can('viewAny', \App\Models\User::class),
            ],
        ]);
    } else {
        $modules = [
            [
                'key' => 'informes',
                'label' => $u->minimalDisciplinarySidebarLabel(),
                'route' => $disciplinaryRoute,
                'active' => request()->routeIs('disciplinary.*'),
                'icon' => 'document-text',
                'available' => true,
            ],
        ];
    }
@endphp

@props([
    'variant' => 'light',
])

@php
    $isNeon = $variant === 'neon';
    $shell = $isNeon
        ? 'bg-gradient-to-b from-dash-ink via-[#101229] to-dash-void border-r border-white/10 shadow-[inset_-1px_0_0_rgba(255,255,255,0.04)] text-slate-100'
        : 'bg-white border-r border-slate-200 text-slate-800 shadow-sm';
@endphp

<aside x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col transition-transform duration-200
              lg:sticky lg:top-0 lg:h-screen lg:z-auto lg:flex-shrink-0 {{ $shell }}">

    {{-- Logo / branding --}}
    <div class="px-5 py-5 border-b {{ $isNeon ? 'border-white/10' : 'border-slate-200' }} flex items-center gap-3">
        <a href="{{ $sidebarBrandHref }}" wire:navigate class="flex items-center gap-3 group">
            <img src="{{ \App\Support\Disciplinary\DisciplinaryAssets::logoPublicUrl() }}"
                 alt="SJ LegalSuite"
                 class="h-9 w-auto bg-white rounded-md p-1 shadow-sm ring-1 {{ $isNeon ? 'ring-white/10' : 'ring-slate-200' }}">
            <div class="leading-tight">
                <p class="text-sm font-semibold tracking-wide {{ $isNeon ? 'group-hover:text-white text-white' : 'group-hover:text-slate-900 text-slate-900' }}">SJ LegalSuite</p>
                <p class="text-[10px] uppercase tracking-widest {{ $isNeon ? 'text-slate-400' : 'text-slate-500' }}">Gestión jurídica</p>
            </div>
        </a>
    </div>

    {{-- Lista de módulos --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5 text-sm">
        @foreach ($modules as $m)
            @if ($m['available'])
                <a href="{{ $m['route'] }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition
                          {{ ($m['active'] ?? false)
                                ? ($isNeon
                                    ? 'bg-gradient-to-r from-cyan-500/20 to-fuchsia-500/15 text-white font-semibold ring-1 ring-cyan-400/40 shadow-dash-glow-cyan'
                                    : 'bg-indigo-50 text-indigo-800 font-semibold ring-1 ring-indigo-200')
                                : ($isNeon
                                    ? 'text-slate-400 hover:bg-white/5 hover:text-white border border-transparent hover:border-white/10'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-transparent') }}">
                    <x-app-sidebar-icon :name="$m['icon']" class="h-5 w-5 flex-shrink-0" />
                    <span class="truncate">{{ $m['label'] }}</span>
                    @if ($m['active'] ?? false)
                        <span class="ml-auto h-1.5 w-1.5 rounded-full {{ $isNeon ? 'bg-cyan-400 shadow-[0_0_10px_rgba(34,211,238,0.9)]' : 'bg-emerald-500' }}"></span>
                    @endif
                </a>
            @else
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-not-allowed select-none {{ $isNeon ? 'text-slate-500' : 'text-slate-400' }}">
                    <x-app-sidebar-icon :name="$m['icon']" class="h-5 w-5 flex-shrink-0 opacity-70" />
                    <span class="truncate">{{ $m['label'] }}</span>
                    <span class="ml-auto text-[9px] uppercase tracking-wider px-1.5 py-0.5 rounded {{ $isNeon ? 'bg-white/10 text-slate-400' : 'bg-slate-100 text-slate-500' }}">
                        Próx.
                    </span>
                </div>
            @endif
        @endforeach
    </nav>

    {{-- User --}}
    <div class="p-3 border-t {{ $isNeon ? 'border-white/10' : 'border-slate-200' }} flex-shrink-0">
        <div class="flex items-center gap-3 px-2 py-2">
            <div class="h-9 w-9 rounded-full flex items-center justify-center text-sm font-semibold ring-1 {{ $isNeon ? 'bg-indigo-500/30 text-white ring-indigo-500/40' : 'bg-indigo-100 text-indigo-700 ring-indigo-200' }}">
                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim((string) auth()->user()->name), 0, 1)) ?: '?' }}
            </div>
            <div class="leading-tight min-w-0">
                <p class="text-sm font-medium truncate {{ $isNeon ? 'text-white' : 'text-slate-900' }}">{{ auth()->user()->name }}</p>
                <p class="text-[11px] truncate {{ $isNeon ? 'text-slate-400' : 'text-slate-500' }}">
                    {{ auth()->user()->roles->pluck('name')->first() ?? 'usuario' }}
                </p>
            </div>
        </div>
    </div>
</aside>
