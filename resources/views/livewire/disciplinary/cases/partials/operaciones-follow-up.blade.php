@php
    /** @var \App\Models\Disciplinary\DisciplinaryCase $case */
    $followUp = $case->operacionesFollowUpSummary();
    $letterColors = \App\Support\Disciplinary\WorkflowStageBuckets::letterColorClasses();
    $letterClass = $followUp['stage_letter']
        ? ($letterColors[$followUp['stage_letter']] ?? 'text-slate-400')
        : 'text-slate-400';
@endphp

<div class="space-y-5" data-operaciones-follow-up>
    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5 dark:border-white/10 dark:bg-white/[0.04]">
        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-fuchsia-500/90 dark:text-fuchsia-400/90">
            Seguimiento · Operaciones
        </p>
        <div class="mt-3 flex flex-wrap items-start gap-4">
            @if ($followUp['stage_letter'])
                <span
                    class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-white text-lg font-bold ring-1 ring-slate-200 dark:bg-dash-lift dark:ring-white/15 {{ $letterClass }}"
                    title="Etapa {{ $followUp['stage_letter'] }}"
                >{{ $followUp['stage_letter'] }}</span>
            @endif
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                    {{ $followUp['headline'] }}
                </h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                    {{ $followUp['stage_title'] }}
                </p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Estado del expediente: {{ $followUp['status_label'] }}
                </p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-dash-muted">Disciplinado</p>
        <p class="mt-1 text-base font-semibold text-slate-900 dark:text-white">
            {{ $case->employee?->full_name ?: '—' }}
        </p>
        <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-400">
            @if ($case->employee?->document_number)
                CC {{ $case->employee->document_number }}
            @else
                Sin documento
            @endif
            @if ($case->employee?->position)
                · {{ $case->employee->position }}
            @endif
        </p>
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-dash-muted">N° caso</dt>
                <dd class="mt-0.5 font-mono text-slate-800 dark:text-slate-200">{{ $case->case_number }}</dd>
            </div>
            <div>
                <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-dash-muted">Apertura</dt>
                <dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $case->opened_at?->format('Y-m-d') ?? '—' }}</dd>
            </div>
            @if ($case->city)
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-dash-muted">Ciudad</dt>
                    <dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $case->city }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <p class="text-xs text-slate-500 dark:text-slate-400">
        Vista de seguimiento. El avance del proceso lo gestiona el área jurídica; aquí solo ve el estado de la etapa en curso.
    </p>
</div>
