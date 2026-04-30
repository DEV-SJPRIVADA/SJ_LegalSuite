@php
    /**
     * Catálogo de módulos del sistema. El campo `available` controla si está
     * habilitado. El resto se renderiza como "Próximamente" para que el cliente
     * vea el alcance completo del sistema desde el primer día.
     */
    $modules = [
        [
            'key' => 'home',
            'label' => 'Inicio',
            'route' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'icon' => 'home',
            'available' => true,
        ],
        [
            'key' => 'disciplinary',
            'label' => 'Disciplinarios',
            'route' => route('disciplinary.dashboard'),
            'active' => request()->routeIs('disciplinary.*'),
            'icon' => 'scale',
            'available' => auth()->user()->can('viewDashboard', \App\Models\Disciplinary\DisciplinaryCase::class),
        ],
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
            'available' => auth()->user()->can('viewAny', \App\Models\User::class),
        ],
    ];
@endphp

<aside x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-800 text-slate-100 flex flex-col transition-transform duration-200
              lg:sticky lg:top-0 lg:h-screen lg:z-auto lg:flex-shrink-0">

    {{-- Logo / branding --}}
    <div class="px-5 py-5 border-b border-slate-700/60 flex items-center gap-3">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 group">
            <img src="{{ asset('images/logo solo.png') }}"
                 alt="SJ LegalSuite"
                 class="h-9 w-auto bg-white rounded-md p-1 shadow-sm">
            <div class="leading-tight">
                <p class="text-sm font-semibold tracking-wide group-hover:text-white">SJ LegalSuite</p>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest">Gestión jurídica</p>
            </div>
        </a>
    </div>

    {{-- Lista de módulos --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5 text-sm">
        @foreach ($modules as $m)
            @if ($m['available'])
                <a href="{{ $m['route'] }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2.5 rounded-md transition
                          {{ ($m['active'] ?? false)
                                ? 'bg-slate-700 text-white font-semibold ring-1 ring-slate-600'
                                : 'text-slate-300 hover:bg-slate-700/60 hover:text-white' }}">
                    <x-app-sidebar-icon :name="$m['icon']" class="h-5 w-5 flex-shrink-0" />
                    <span class="truncate">{{ $m['label'] }}</span>
                    @if ($m['active'] ?? false)
                        <span class="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    @endif
                </a>
            @else
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-md text-slate-500 cursor-not-allowed select-none">
                    <x-app-sidebar-icon :name="$m['icon']" class="h-5 w-5 flex-shrink-0" />
                    <span class="truncate">{{ $m['label'] }}</span>
                    <span class="ml-auto text-[9px] uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-700/60 text-slate-400">
                        Próx.
                    </span>
                </div>
            @endif
        @endforeach
    </nav>

    {{-- User --}}
    <div class="p-3 border-t border-slate-700/60 flex-shrink-0">
        <div class="flex items-center gap-3 px-2 py-2">
            <div class="h-9 w-9 rounded-full bg-indigo-500/30 flex items-center justify-center text-sm font-semibold text-white ring-1 ring-indigo-500/40">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="leading-tight min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-[11px] text-slate-400 truncate">
                    {{ auth()->user()->roles->pluck('name')->first() ?? 'usuario' }}
                </p>
            </div>
        </div>
    </div>
</aside>
