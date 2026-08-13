@props([
    'label' => '',
    'value' => 0,
    'accent' => 'cyan',
    'active' => false,
    'subtitle' => null,
    'compact' => false,
])

@php
    $styles = [
        'cyan' => [
            'value' => 'text-cyan-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-cyan-300 dark:to-sky-400',
            'ring' => 'ring-slate-200 dark:ring-cyan-400/35',
            'shell' => 'border-slate-200 bg-gradient-to-br from-white to-slate-50 dark:border-white/10 dark:from-white/[0.09] dark:to-white/[0.02]',
            'active' => 'border-cyan-400/60 ring-2 ring-cyan-400/40 dark:border-cyan-400/50',
        ],
        'emerald' => [
            'value' => 'text-emerald-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-emerald-300 dark:to-teal-400',
            'ring' => 'ring-slate-200 dark:ring-emerald-400/30',
            'shell' => 'border-slate-200 bg-gradient-to-br from-white to-slate-50 dark:border-white/10 dark:from-white/[0.09] dark:to-white/[0.02]',
            'active' => 'border-emerald-400/60 ring-2 ring-emerald-400/40 dark:border-emerald-400/50',
        ],
        'amber' => [
            'value' => 'text-amber-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-amber-300 dark:to-orange-400',
            'ring' => 'ring-slate-200 dark:ring-amber-400/30',
            'shell' => 'border-slate-200 bg-gradient-to-br from-white to-slate-50 dark:border-white/10 dark:from-white/[0.09] dark:to-white/[0.02]',
            'active' => 'border-amber-400/60 ring-2 ring-amber-400/40 dark:border-amber-400/50',
        ],
        'indigo' => [
            'value' => 'text-indigo-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-indigo-300 dark:to-violet-400',
            'ring' => 'ring-slate-200 dark:ring-indigo-400/30',
            'shell' => 'border-slate-200 bg-gradient-to-br from-white to-slate-50 dark:border-white/10 dark:from-white/[0.09] dark:to-white/[0.02]',
            'active' => 'border-indigo-400/60 ring-2 ring-indigo-400/40 dark:border-indigo-400/50',
        ],
        'fuchsia' => [
            'value' => 'text-fuchsia-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-fuchsia-300 dark:to-pink-400',
            'ring' => 'ring-slate-200 dark:ring-fuchsia-400/30',
            'shell' => 'border-slate-200 bg-gradient-to-br from-white to-slate-50 dark:border-white/10 dark:from-white/[0.09] dark:to-white/[0.02]',
            'active' => 'border-fuchsia-400/60 ring-2 ring-fuchsia-400/40 dark:border-fuchsia-400/50',
        ],
    ];
    $s = $styles[$accent] ?? $styles['cyan'];
    $pad = $compact ? 'px-2.5 py-2 rounded-xl' : 'p-4 rounded-2xl';
    $valueSize = $compact ? 'text-lg leading-none' : 'mt-1.5 text-2xl font-bold tabular-nums leading-none sm:text-3xl';
@endphp

<button type="button"
    {{ $attributes->merge([
        'class' => 'relative w-full overflow-hidden border text-left ring-1 transition hover:brightness-[1.03] focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50 '
            .$pad.' '.$s['shell'].' '.$s['ring'].($active ? ' '.$s['active'] : ''),
    ]) }}>
    <p class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-dash-muted">{{ $label }}</p>
    <p @class(['font-bold tabular-nums', $valueSize, $s['value'], 'mt-0.5' => $compact])>{{ number_format((int) $value) }}</p>
    @if ($subtitle && ! $compact)
        <p class="mt-1.5 truncate text-[10px] text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
    @endif
</button>
