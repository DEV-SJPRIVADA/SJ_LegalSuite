@props([
    'count' => 0,
    'title' => '',
    'subtitle' => '',
    'icon' => 'bell',
    'color' => 'slate',
    'items' => [],
])

@php
    $palette = [
        'rose' => ['bg' => 'bg-rose-50', 'icon' => 'bg-rose-100 text-rose-600', 'text' => 'text-rose-700', 'ring' => 'ring-rose-200'],
        'amber' => ['bg' => 'bg-amber-50', 'icon' => 'bg-amber-100 text-amber-600', 'text' => 'text-amber-700', 'ring' => 'ring-amber-200'],
        'indigo' => ['bg' => 'bg-indigo-50', 'icon' => 'bg-indigo-100 text-indigo-600', 'text' => 'text-indigo-700', 'ring' => 'ring-indigo-200'],
        'sky' => ['bg' => 'bg-sky-50', 'icon' => 'bg-sky-100 text-sky-600', 'text' => 'text-sky-700', 'ring' => 'ring-sky-200'],
        'emerald' => ['bg' => 'bg-emerald-50', 'icon' => 'bg-emerald-100 text-emerald-600', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-200'],
        'slate' => ['bg' => 'bg-slate-50', 'icon' => 'bg-slate-100 text-slate-600', 'text' => 'text-slate-700', 'ring' => 'ring-slate-200'],
    ];
    $c = $palette[$color] ?? $palette['slate'];
@endphp

<div class="bg-white rounded-lg shadow-sm ring-1 ring-slate-200 p-5 flex flex-col">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($count) }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
        </div>
        <div class="h-10 w-10 rounded-lg {{ $c['icon'] }} flex items-center justify-center flex-shrink-0">
            <x-app-sidebar-icon :name="$icon" class="h-5 w-5" />
        </div>
    </div>

    @if (count($items) > 0)
        @php
            $visible = array_slice($items, 0, 5);
        @endphp
        <ul class="mt-4 space-y-1.5 text-xs flex-1 border-t border-slate-100 pt-3">
            @foreach ($visible as $item)
                <li>
                    <a href="{{ $item['route'] ?? '#' }}"
                       wire:navigate
                       class="block truncate text-slate-600 hover:{{ $c['text'] }} hover:underline">
                        @if (! empty($item['due_at']))
                            <span class="font-mono text-[10px] {{ $c['text'] }}">{{ $item['due_at'] }}</span>
                        @endif
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
        @if ($count > count($visible))
            <p class="mt-2 text-[11px] text-slate-400">y {{ $count - count($visible) }} más…</p>
        @endif
    @else
        <div class="mt-4 flex-1 flex items-center justify-center text-xs text-slate-400 border-t border-slate-100 pt-3">
            Sin alertas
        </div>
    @endif
</div>
