@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $variantClass = match ($variant) {
        'secondary' => 'sj-btn--secondary',
        'teal' => 'sj-btn--teal',
        'teal-light' => 'sj-btn--teal-light',
        'success' => 'sj-btn--success',
        'danger' => 'sj-btn--danger',
        'danger-soft' => 'sj-btn--danger-soft',
        'ghost' => 'sj-btn--ghost',
        'dark' => 'sj-btn--dark',
        'indigo-soft' => 'sj-btn--indigo-soft',
        'emerald-soft' => 'sj-btn--emerald-soft',
        'muted' => 'sj-btn--muted',
        'warning' => 'sj-btn--warning',
        default => 'sj-btn--primary',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "sj-btn {$variantClass}"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "sj-btn {$variantClass}"]) }}>
        {{ $slot }}
    </button>
@endif
