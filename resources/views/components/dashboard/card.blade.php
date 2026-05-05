@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-200 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-dash-card dark:backdrop-blur-sm dark:ring-0']) }}>
    @if ($title || $subtitle)
        <div class="px-5 pt-5 pb-3 border-b border-slate-100 dark:border-white/[0.06]">
            @if ($title)
                <h3 class="text-sm font-semibold tracking-tight text-slate-900 dark:text-white">{{ $title }}</h3>
            @endif
            @if ($subtitle)
                <p class="text-xs text-slate-500 mt-0.5 dark:text-dash-muted">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="p-5">
            {{ $slot }}
        </div>
    @else
        <div class="p-5">
            {{ $slot }}
        </div>
    @endif
</div>
