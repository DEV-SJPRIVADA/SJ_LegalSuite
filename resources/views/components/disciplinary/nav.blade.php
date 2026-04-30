@php
    $links = [
        ['key' => 'home', 'label' => 'Inicio', 'route' => route('dashboard'), 'active' => false],
        ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => route('disciplinary.dashboard'), 'active' => request()->routeIs('disciplinary.dashboard')],
        ['key' => 'cases', 'label' => 'Disciplinarios', 'route' => route('disciplinary.cases.index'), 'active' => request()->routeIs('disciplinary.cases.*')],
        ['key' => 'formats', 'label' => 'Formatos', 'route' => null, 'active' => false, 'soon' => true],
        ['key' => 'history', 'label' => 'Historial', 'route' => null, 'active' => false, 'soon' => true],
    ];
@endphp

<div class="bg-white border-b border-slate-200 sticky top-[57px] z-10">
    <nav class="px-4 lg:px-6">
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
