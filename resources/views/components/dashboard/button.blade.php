@props([
    'href' => null,
    'variant' => 'primary',
])

@php
    $cls = match ($variant) {
        'ghost' => 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 hover:border-slate-400 dark:border-white/15 dark:bg-white/5 dark:text-slate-100 dark:hover:bg-white/10 dark:hover:border-cyan-400/40',
        default => 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-md hover:brightness-105 dark:from-cyan-500 dark:via-fuchsia-500 dark:to-orange-400 dark:text-dash-void dark:shadow-dash-glow-cyan dark:hover:brightness-110',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition '.$cls]) }}
       wire:navigate>
        {{ $slot }}
    </a>
@else
    <button type="button" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition '.$cls]) }}>
        {{ $slot }}
    </button>
@endif
