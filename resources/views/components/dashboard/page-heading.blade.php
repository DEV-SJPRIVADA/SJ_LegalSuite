@props([
    'eyebrow' => '',
    'title' => '',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-4 mb-8']) }}>
    <div>
        @if ($eyebrow)
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-fuchsia-400/90">{{ $eyebrow }}</p>
        @endif
        @if ($title)
            <h1 class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $title }}</h1>
        @endif
        @if ($description)
            <p class="mt-2 text-sm text-slate-600 max-w-2xl dark:text-dash-muted">{{ $description }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
