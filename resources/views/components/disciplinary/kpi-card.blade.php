@props([
    'label' => '',
    'value' => 0,
    'color' => 'slate',
])

@php
    $palette = [
        'slate' => 'bg-slate-50 text-slate-700 ring-slate-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    ];
    $cls = $palette[$color] ?? $palette['slate'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white shadow-sm sm:rounded-lg p-5 ring-1 ring-gray-100']) }}>
    <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">{{ $label }}</p>
    <div class="mt-3 flex items-baseline gap-2">
        <span class="text-3xl font-bold text-gray-900">{{ number_format($value) }}</span>
    </div>
    <span class="mt-3 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ring-1 ring-inset {{ $cls }}">
        {{ $color === 'amber' ? 'Atención' : ($color === 'emerald' ? 'OK' : ($color === 'blue' ? 'Activos' : 'Total')) }}
    </span>
</div>
