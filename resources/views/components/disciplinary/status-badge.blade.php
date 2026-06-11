@props([
    'status',
    /** sm: tablas y listados · md: barra de acciones (misma altura que sj-btn) */
    'size' => 'sm',
])

@php
    use App\Enums\Disciplinary\CaseBucket;
    use App\Enums\Disciplinary\CaseStatus;

    $enum = $status instanceof CaseStatus ? $status : CaseStatus::tryFrom($status);
    $palette = match ($enum?->bucket()) {
        CaseBucket::PENDIENTE => 'bg-amber-50 text-amber-700 ring-amber-200',
        CaseBucket::EN_PROCESO => 'bg-blue-50 text-blue-700 ring-blue-200',
        CaseBucket::FINALIZADO => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        default => 'bg-gray-50 text-gray-700 ring-gray-200',
    };

    $sizeClass = match ($size) {
        'md' => 'sj-status-md',
        default => 'inline-flex items-center rounded px-2 py-0.5 text-xs font-medium',
    };
@endphp

<span {{ $attributes->merge(['class' => "{$sizeClass} ring-1 ring-inset whitespace-nowrap {$palette}"]) }}>
    {{ $enum?->label() ?? '—' }}
</span>
