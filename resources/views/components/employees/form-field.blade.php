@props([
    'label',
    'required' => false,
    'hint' => null,
    'for' => null,
    'class' => '',
])

<div {{ $attributes->merge(['class' => $class]) }}>
    <label
        @if ($for) for="{{ $for }}" @endif
        class="mb-1.5 flex flex-wrap items-baseline gap-x-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
    >
        <span>{{ $label }}</span>
        @if ($required)
            <span class="text-red-500 dark:text-red-400" aria-hidden="true">*</span>
        @endif
    </label>
    @if ($hint)
        <p class="mb-1.5 text-[11px] leading-snug text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
    {{ $slot }}
</div>
