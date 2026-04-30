@php
    $links = [
        ['key' => 'home', 'label' => 'Inicio', 'route' => route('dashboard'), 'active' => false],
        ['key' => 'index', 'label' => 'Usuarios', 'route' => route('users.index'), 'active' => request()->routeIs('users.*')],
        ['key' => 'roles', 'label' => 'Roles', 'route' => null, 'active' => false, 'soon' => true],
        ['key' => 'audit', 'label' => 'Auditoría de accesos', 'route' => null, 'active' => false, 'soon' => true],
    ];
@endphp

<header class="bg-white border-b border-slate-200 sticky top-0 z-20">
    <div class="flex items-center justify-between gap-2 px-4 lg:px-6">

        <div class="flex items-center gap-1 min-w-0">
            <button x-on:click="sidebarOpen = true"
                    class="lg:hidden p-2 -ml-2 rounded-md text-slate-700 hover:bg-slate-100 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <nav>
                <ul class="flex items-center gap-1 overflow-x-auto text-sm">
                    @foreach ($links as $link)
                        <li>
                            @if ($link['route'] && empty($link['soon']))
                                <a href="{{ $link['route'] }}" wire:navigate
                                   class="inline-flex items-center px-4 py-3 border-b-2 transition whitespace-nowrap font-medium
                                          {{ $link['active']
                                                ? 'border-indigo-600 text-indigo-700'
                                                : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                                    {{ $link['label'] }}
                                </a>
                            @else
                                <span class="inline-flex items-center gap-2 px-4 py-3 border-b-2 border-transparent text-slate-400 whitespace-nowrap font-medium cursor-not-allowed">
                                    {{ $link['label'] }}
                                    <span class="text-[9px] uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-200 text-slate-500">Próx.</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>

        <div class="flex items-center gap-3 flex-shrink-0">
            <a href="{{ route('profile') }}" wire:navigate
               class="hidden sm:block text-sm text-slate-600 hover:text-slate-900">
                Mi perfil
            </a>
            <livewire:auth.logout-button />
        </div>

    </div>
</header>
