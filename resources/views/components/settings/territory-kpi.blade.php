@props([
    'label' => '',
    'value' => '',
    'hint' => null,
    'accent' => 'cyan',
    'status' => null,
    'file' => null,
])

@php
    $valueColors = [
        'cyan' => 'text-cyan-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-cyan-300 dark:to-sky-400',
        'emerald' => 'text-emerald-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-emerald-300 dark:to-teal-400',
        'indigo' => 'text-indigo-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-indigo-300 dark:to-violet-400',
        'amber' => 'text-amber-700 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-amber-300 dark:to-orange-400',
    ];
    $rings = [
        'cyan' => 'ring-slate-200 dark:ring-cyan-400/35',
        'emerald' => 'ring-slate-200 dark:ring-emerald-400/30',
        'indigo' => 'ring-slate-200 dark:ring-indigo-400/30',
        'amber' => 'ring-slate-200 dark:ring-amber-400/30',
    ];
    $valueClass = $valueColors[$accent] ?? $valueColors['cyan'];
    $ringClass = $rings[$accent] ?? $rings['cyan'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 px-2.5 py-2 ring-1 dark:border-white/10 dark:from-white/[0.09] dark:to-white/[0.02 '.$ringClass]) }}>
    <p class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-dash-muted">{{ $label }}</p>
    <p class="mt-0.5 truncate text-lg font-bold leading-none tabular-nums {{ $valueClass }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 truncate text-[10px] text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
    @if ($status)
        <p @class([
            'mt-1 text-[10px] font-semibold',
            'text-emerald-600 dark:text-emerald-300' => $status === 'ok',
            'text-amber-600 dark:text-amber-300' => $status === 'warn',
        ])>{{ $status === 'ok' ? 'Catálogo completo' : 'Revisar cobertura' }}</p>
    @endif
</div>
