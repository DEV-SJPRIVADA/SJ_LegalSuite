@props([
    'count' => 0,
    'title' => '',
    'subtitle' => '',
    'icon' => 'bell',
    'color' => 'slate',
    'items' => [],
    'variant' => 'light',
])

@php
    $palette = [
        'rose' => [
            'light' => ['bg' => 'bg-rose-50', 'icon' => 'bg-rose-100 text-rose-600', 'text' => 'text-rose-700', 'ring' => 'ring-rose-200', 'count' => 'text-rose-400'],
            'dash' => ['shell' => 'border-rose-500/25', 'icon' => 'bg-rose-500/15 text-rose-300 ring-rose-400/30', 'text' => 'text-rose-300/95', 'ring' => 'ring-rose-500/25', 'count' => 'text-transparent bg-clip-text bg-gradient-to-r from-rose-300 to-orange-300'],
        ],
        'amber' => [
            'light' => ['bg' => 'bg-amber-50', 'icon' => 'bg-amber-100 text-amber-600', 'text' => 'text-amber-700', 'ring' => 'ring-amber-200', 'count' => 'text-amber-400'],
            'dash' => ['shell' => 'border-amber-500/25', 'icon' => 'bg-amber-500/15 text-amber-200 ring-amber-400/30', 'text' => 'text-amber-200/95', 'ring' => 'ring-amber-500/25', 'count' => 'text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-yellow-300'],
        ],
        'indigo' => [
            'light' => ['bg' => 'bg-indigo-50', 'icon' => 'bg-indigo-100 text-indigo-600', 'text' => 'text-indigo-700', 'ring' => 'ring-indigo-200', 'count' => 'text-indigo-400'],
            'dash' => ['shell' => 'border-indigo-500/20', 'icon' => 'bg-indigo-500/15 text-indigo-200 ring-indigo-400/30', 'text' => 'text-indigo-200/95', 'ring' => 'ring-indigo-500/20', 'count' => 'text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-cyan-300'],
        ],
        'sky' => [
            'light' => ['bg' => 'bg-sky-50', 'icon' => 'bg-sky-100 text-sky-600', 'text' => 'text-sky-700', 'ring' => 'ring-sky-200', 'count' => 'text-sky-400'],
            'dash' => ['shell' => 'border-sky-500/25', 'icon' => 'bg-sky-500/15 text-sky-200 ring-sky-400/30', 'text' => 'text-sky-200/95', 'ring' => 'ring-sky-500/25', 'count' => 'text-transparent bg-clip-text bg-gradient-to-r from-sky-300 to-cyan-300'],
        ],
        'emerald' => [
            'light' => ['bg' => 'bg-emerald-50', 'icon' => 'bg-emerald-100 text-emerald-600', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-200', 'count' => 'text-emerald-400'],
            'dash' => ['shell' => 'border-emerald-500/25', 'icon' => 'bg-emerald-500/15 text-emerald-200 ring-emerald-400/30', 'text' => 'text-emerald-200/95', 'ring' => 'ring-emerald-500/25', 'count' => 'text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-teal-300'],
        ],
        'slate' => [
            'light' => ['bg' => 'bg-slate-50', 'icon' => 'bg-slate-100 text-slate-600', 'text' => 'text-slate-700', 'ring' => 'ring-slate-200', 'count' => 'text-slate-400'],
            'dash' => ['shell' => 'border-white/10', 'icon' => 'bg-white/10 text-slate-200 ring-white/15', 'text' => 'text-slate-300/95', 'ring' => 'ring-white/10', 'count' => 'text-white'],
        ],
    ];
    $v = $variant === 'dash' ? 'dash' : 'light';
    $c = $palette[$color][$v] ?? $palette['slate'][$v];
@endphp

@if ($variant === 'dash')
    <div class="rounded-2xl border bg-white/[0.04] backdrop-blur-sm p-5 flex flex-col shadow-dash-card ring-1 ring-white/5 {{ $c['shell'] }}">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] uppercase tracking-[0.14em] font-bold text-dash-muted">{{ $title }}</p>
                <p class="mt-2 text-3xl font-bold tabular-nums {{ $c['count'] }}">{{ number_format($count) }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
            </div>
            <div class="h-10 w-10 rounded-xl ring-1 flex items-center justify-center flex-shrink-0 {{ $c['icon'] }}">
                <x-app-sidebar-icon :name="$icon" class="h-5 w-5" />
            </div>
        </div>

        @if (count($items) > 0)
            @php
                $visible = array_slice($items, 0, 5);
            @endphp
            <ul class="mt-4 space-y-1.5 text-xs flex-1 border-t border-white/10 pt-3">
                @foreach ($visible as $item)
                    <li>
                        <a href="{{ $item['route'] ?? '#' }}"
                           wire:navigate
                           class="block truncate text-slate-400 hover:text-cyan-300 hover:underline transition">
                            @if (! empty($item['due_at']))
                                <span class="font-mono text-[10px] text-fuchsia-400/90">{{ $item['due_at'] }}</span>
                            @endif
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($count > count($visible))
                <p class="mt-2 text-[11px] text-slate-500">y {{ $count - count($visible) }} más…</p>
            @endif
        @else
            <div class="mt-4 flex-1 flex items-center justify-center text-xs text-slate-500 border-t border-white/10 pt-3">
                Sin alertas
            </div>
        @endif
    </div>
@else
    @php
        $cl = $palette[$color]['light'] ?? $palette['slate']['light'];
    @endphp
    <div class="bg-white rounded-lg shadow-sm ring-1 ring-slate-200 p-5 flex flex-col">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ $title }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($count) }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
            </div>
            <div class="h-10 w-10 rounded-lg {{ $cl['icon'] }} flex items-center justify-center flex-shrink-0">
                <x-app-sidebar-icon :name="$icon" class="h-5 w-5" />
            </div>
        </div>

        @if (count($items) > 0)
            @php
                $visible = array_slice($items, 0, 5);
            @endphp
            <ul class="mt-4 space-y-1.5 text-xs flex-1 border-t border-slate-100 pt-3">
                @foreach ($visible as $item)
                    <li>
                        <a href="{{ $item['route'] ?? '#' }}"
                           wire:navigate
                           class="block truncate text-slate-600 hover:{{ $cl['text'] }} hover:underline">
                            @if (! empty($item['due_at']))
                                <span class="font-mono text-[10px] {{ $cl['text'] }}">{{ $item['due_at'] }}</span>
                            @endif
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($count > count($visible))
                <p class="mt-2 text-[11px] text-slate-400">y {{ $count - count($visible) }} más…</p>
            @endif
        @else
            <div class="mt-4 flex-1 flex items-center justify-center text-xs text-slate-400 border-t border-slate-100 pt-3">
                Sin alertas
            </div>
        @endif
    </div>
@endif
