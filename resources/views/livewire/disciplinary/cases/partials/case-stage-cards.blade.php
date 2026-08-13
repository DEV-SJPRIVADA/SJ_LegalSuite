@php
    use App\Support\Disciplinary\CaseStageCardState;
@endphp

<div class="grid grid-cols-2 gap-3 lg:grid-cols-4" data-case-stage-cards>
    @foreach ($stageCards as $card)
        @php
            $letter = $card['letter'];
            $state = $card['state'];
            $color = $stageLetterColors[$letter] ?? 'text-slate-400';
            $isActive = $state === CaseStageCardState::ACTIVE;
            $isCompleted = $state === CaseStageCardState::COMPLETED;
            $isLocked = $state === CaseStageCardState::LOCKED;
            $ringClass = $isActive
                ? 'ring-2 ring-fuchsia-400/60 border-fuchsia-300/50 dark:ring-fuchsia-400/40'
                : ($isCompleted
                    ? 'ring-1 ring-emerald-400/40 border-emerald-300/30 dark:ring-emerald-500/30'
                    : 'ring-1 ring-slate-200/80 border-slate-200/80 opacity-75 dark:ring-white/10 dark:border-white/10');
            $statusLabel = $isActive ? 'Activa' : ($isCompleted ? 'Tramitada' : 'Bloqueada');
        @endphp
        <button type="button"
            wire:click="openStageCard('{{ $card['key'] }}')"
            data-stage-card="{{ $card['key'] }}"
            class="group flex flex-col rounded-xl border bg-white p-4 text-left shadow-sm transition hover:shadow-md dark:bg-white/[0.04] {{ $ringClass }}">
            <div class="flex items-start justify-between gap-2">
                <span class="text-2xl font-black tabular-nums {{ $color }}">{{ $letter }}</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide
                    {{ $isActive ? 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-500/20 dark:text-fuchsia-200' : ($isCompleted ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200' : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-400') }}">
                    {{ $statusLabel }}
                </span>
            </div>
            <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $card['short'] }}</p>
            <p class="mt-0.5 line-clamp-2 text-xs text-slate-600 dark:text-slate-400">{{ $card['title'] }}</p>
            <p class="mt-3 text-[10px] font-semibold uppercase tracking-wide text-slate-400 group-hover:text-fuchsia-500 dark:group-hover:text-fuchsia-300">
                {{ $isLocked ? 'No disponible' : 'Gestionar etapa →' }}
            </p>
        </button>
    @endforeach
</div>

@if ($stageCardAlert)
    <div class="mt-3 flex items-start justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-950/40" role="alert">
        <p class="text-sm text-amber-950 dark:text-amber-100">{{ $stageCardAlert }}</p>
        <button type="button" wire:click="dismissStageCardAlert" class="shrink-0 text-xs font-semibold text-amber-900 underline dark:text-amber-200">Cerrar</button>
    </div>
@endif
