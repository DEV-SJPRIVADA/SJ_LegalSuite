@props([
    'count' => 0,
    'title' => '',
    'subtitle' => '',
    'icon' => 'bell',
    'color' => 'rose',
])

@php
    $shell = match ($color) {
        'amber' => 'border-amber-200 bg-amber-50/90 ring-amber-100 dark:border-amber-500/25 dark:bg-white/[0.04] dark:ring-white/5',
        'indigo' => 'border-indigo-200 bg-indigo-50/90 ring-indigo-100 dark:border-indigo-500/20 dark:bg-white/[0.04] dark:ring-white/5',
        'sky' => 'border-sky-200 bg-sky-50/90 ring-sky-100 dark:border-sky-500/25 dark:bg-white/[0.04] dark:ring-white/5',
        default => 'border-rose-200 bg-rose-50/90 ring-rose-100 dark:border-rose-500/25 dark:bg-white/[0.04] dark:ring-white/5',
    };
    $iconBox = match ($color) {
        'amber' => 'bg-amber-100 text-amber-600 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-400/30',
        'indigo' => 'bg-indigo-100 text-indigo-600 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-200 dark:ring-indigo-400/30',
        'sky' => 'bg-sky-100 text-sky-600 ring-sky-200 dark:bg-sky-500/15 dark:text-sky-200 dark:ring-sky-400/30',
        default => 'bg-rose-100 text-rose-600 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-400/30',
    };
    $countClass = match ($color) {
        'amber' => 'text-amber-800 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-amber-300 dark:to-yellow-300',
        'indigo' => 'text-indigo-800 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-indigo-300 dark:to-cyan-300',
        'sky' => 'text-sky-800 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-sky-300 dark:to-cyan-300',
        default => 'text-rose-800 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-rose-300 dark:to-orange-300',
    };
@endphp

<button type="button"
    {{ $attributes->merge([
        'class' => "group relative flex min-w-0 flex-1 items-center gap-3 rounded-xl border px-3 py-2.5 text-left ring-1 backdrop-blur-sm transition hover:brightness-[1.02] {$shell}",
    ]) }}>
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ring-1 {{ $iconBox }}">
        <x-app-sidebar-icon :name="$icon" class="h-4 w-4" />
    </div>
    <div class="min-w-0 flex-1">
        <p class="truncate text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-dash-muted">{{ $title }}</p>
        <p class="text-xl font-bold tabular-nums leading-tight {{ $countClass }}">{{ number_format($count) }}</p>
        <p class="truncate text-[10px] text-slate-500">{{ $subtitle }}</p>
    </div>
    @if ($count > 0)
        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-fuchsia-500 shadow-[0_0_8px_rgba(217,70,239,0.7)] motion-safe:animate-pulse dark:bg-fuchsia-400" aria-hidden="true"></span>
    @endif
</button>
