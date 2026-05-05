@props([
    'label' => '',
    'value' => 0,
    'accent' => 'cyan',
    'badge' => null,
])

@php
    $styles = [
        'cyan' => [
            'blob' => 'bg-cyan-100/90 dark:bg-cyan-400/25',
            'value' => 'text-cyan-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-cyan-300 dark:to-sky-400',
            'ring' => 'ring-slate-200 shadow-sm dark:ring-cyan-400/35 dark:shadow-dash-glow-cyan',
            'badge' => 'text-cyan-800 bg-cyan-100 ring-cyan-200 dark:text-cyan-200/90 dark:bg-cyan-500/15 dark:ring-cyan-400/30',
            'shell' => 'border-slate-200 bg-gradient-to-br from-white to-slate-50 dark:border-white/10 dark:from-white/[0.09] dark:to-white/[0.02]',
        ],
        'fuchsia' => [
            'blob' => 'bg-fuchsia-100/90 dark:bg-fuchsia-400/25',
            'value' => 'text-fuchsia-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-fuchsia-300 dark:to-pink-400',
            'ring' => 'ring-slate-200 shadow-sm dark:ring-fuchsia-400/35 dark:shadow-dash-glow-fuchsia',
            'badge' => 'text-fuchsia-800 bg-fuchsia-100 ring-fuchsia-200 dark:text-fuchsia-200/90 dark:bg-fuchsia-500/15 dark:ring-fuchsia-400/30',
            'shell' => 'border-slate-200 bg-gradient-to-br from-white to-slate-50 dark:border-white/10 dark:from-white/[0.09] dark:to-white/[0.02]',
        ],
        'orange' => [
            'blob' => 'bg-orange-100/90 dark:bg-orange-400/25',
            'value' => 'text-orange-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-orange-300 dark:to-amber-400',
            'ring' => 'ring-slate-200 shadow-sm dark:ring-orange-400/35 dark:shadow-dash-glow-orange',
            'badge' => 'text-orange-900 bg-orange-100 ring-orange-200 dark:text-orange-200/90 dark:bg-orange-500/15 dark:ring-orange-400/30',
            'shell' => 'border-slate-200 bg-gradient-to-br from-white to-slate-50 dark:border-white/10 dark:from-white/[0.09] dark:to-white/[0.02]',
        ],
        'emerald' => [
            'blob' => 'bg-emerald-100/90 dark:bg-emerald-400/20',
            'value' => 'text-emerald-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-emerald-300 dark:to-teal-400',
            'ring' => 'ring-slate-200 shadow-sm dark:ring-emerald-400/30',
            'badge' => 'text-emerald-800 bg-emerald-100 ring-emerald-200 dark:text-emerald-200/90 dark:bg-emerald-500/15 dark:ring-emerald-400/30',
            'shell' => 'border-slate-200 bg-gradient-to-br from-white to-slate-50 dark:border-white/10 dark:from-white/[0.09] dark:to-white/[0.02]',
        ],
    ];
    $s = $styles[$accent] ?? $styles['cyan'];
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl border p-5 ring-1 '.$s['shell'].' '.$s['ring']]) }}>
    <div class="pointer-events-none absolute -right-6 -top-10 h-28 w-28 rounded-full blur-2xl {{ $s['blob'] }}"></div>
    <p class="relative text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-dash-muted">{{ $label }}</p>
    <p class="relative mt-2 text-3xl font-bold tabular-nums {{ $s['value'] }}">{{ number_format((int) $value) }}</p>
    @if ($badge)
        <span class="relative mt-3 inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $s['badge'] }}">
            {{ $badge }}
        </span>
    @endif
</div>
