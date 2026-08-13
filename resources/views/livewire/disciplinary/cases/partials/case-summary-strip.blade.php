@php
    $employeeName = trim(($case->employee?->first_name ?? '').' '.($case->employee?->last_name ?? ''));
@endphp

<div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-white/10 dark:bg-white/[0.03]">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-fuchsia-400/90">Expediente</p>
            <p class="mt-0.5 truncate text-sm font-semibold text-slate-900 dark:text-white">
                {{ $employeeName !== '' ? $employeeName : 'Sin trabajador vinculado' }}
            </p>
            <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                {{ $case->employee?->document_type }} {{ $case->employee?->document_number ?? '—' }}
                <span class="text-slate-400 dark:text-slate-500" aria-hidden="true"> · </span>
                {{ $case->employee?->job_title ?? '—' }}
                <span class="text-slate-400 dark:text-slate-500" aria-hidden="true"> · </span>
                {{ $case->sede ?? '—' }}
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <button type="button" wire:click="$toggle('showCaseDetailsExpanded')"
                class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-slate-200 dark:ring-white/20">
                {{ $showCaseDetailsExpanded ? 'Ocultar ficha' : 'Ver ficha completa' }}
            </button>
        </div>
    </div>

    @if ($showCaseDetailsExpanded)
        <div class="mt-4 grid grid-cols-1 gap-6 border-t border-slate-200 pt-4 md:grid-cols-2 xl:grid-cols-3 dark:border-white/10">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-dash-muted">Ciudad</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $case->city ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-dash-muted">Reportado por</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $case->reporter?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="sr-only">Abogado titular del caso</dt>
                    <dd class="text-gray-900 dark:text-white">
                        @can('assign', $case)
                            <select wire:model="assignedLawyerId" wire:change="onLawyerSelectChanged"
                                class="w-full max-w-md rounded-md border-gray-300 text-sm shadow-sm dark:border-white/15 dark:bg-dash-lift dark:text-slate-100">
                                <option value="">Seleccionar abogado</option>
                                @foreach ($lawyerCandidates as $law)
                                    <option value="{{ $law->id }}">{{ $law->name }}</option>
                                @endforeach
                            </select>
                            @error('assignedLawyerId')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @else
                            <span class="font-medium">{{ $case->assignedLawyer?->name ?? 'Sin abogado asignado' }}</span>
                        @endcan
                    </dd>
                </div>
            </dl>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-dash-muted">Apertura</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $case->opened_at?->format('Y-m-d') }}</dd>
                </div>
                @if ($case->closed_at)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-dash-muted">Cierre</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $case->closed_at?->format('Y-m-d') }}</dd>
                    </div>
                @endif
                @if ($case->decision)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-dash-muted">Decisión</dt>
                        <dd class="font-semibold text-gray-900 dark:text-white">{{ $case->decision->label() }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-dash-muted">Resumen</dt>
                    <dd class="whitespace-pre-line text-gray-700 dark:text-slate-300">{{ $case->summary ?? '—' }}</dd>
                </div>
            </dl>
            <div class="md:col-span-2 xl:col-span-1">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-dash-muted">Faltas imputadas</h4>
                <div class="flex flex-wrap gap-2">
                    @forelse ($case->faults as $f)
                        <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/35 dark:text-rose-300 dark:ring-rose-500/30">
                            {{ $f->code }} · {{ $f->name }}
                            @if ($f->pivot->extra_info)
                                <span class="ml-1 text-rose-500">({{ $f->pivot->extra_info }})</span>
                            @endif
                        </span>
                    @empty
                        <span class="text-sm text-gray-500 dark:text-slate-400">Sin faltas registradas.</span>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
