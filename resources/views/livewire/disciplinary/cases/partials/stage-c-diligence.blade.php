@php
    use App\Support\Disciplinary\DiligenceStageProgress;
    use App\Support\Disciplinary\OfficialFormsCatalog;

    $stageSteps = $diligenceStageSteps ?? collect();
    $currentStep = $diligenceCurrentStep ?? ['key' => 'hearing', 'label' => '', 'status' => 'current', 'hint' => ''];
    $currentStepKey = (string) ($currentStep['key'] ?? 'hearing');
    $stepNumber = $diligenceCurrentStepNumber ?? 1;
    $totalSteps = $diligenceTotalSteps ?? 3;
    $stageProgressHelper = app(DiligenceStageProgress::class);
    $actionTitle = $stageProgressHelper->actionBarTitle($currentStepKey);
    $actaDoc = $case->latestActaDiligenciaDocument();
    $foGj04PreviewUrl = OfficialFormsCatalog::hasBlankPdf('FO-GJ-04')
        ? route('disciplinary.formats.preview', ['code' => 'FO-GJ-04'])
        : null;
    $canEditFoGj04Draft = auth()->user()->can('editFoGj04Draft', $case);
    $canPreviewFoGj04 = auth()->user()->can('previewFoGj04', $case);
    $canGenerateFoGj04 = auth()->user()->can('generateFoGj04', $case);
    $foGj04DraftCompleted = $case->fo_gj_04_draft_completed_at !== null;
    $isAssignedLawyer = (int) $case->assigned_lawyer_id === (int) auth()->id();
@endphp

@if ($isDiligenciaActive ?? false)
    <div class="md:col-span-2 xl:col-span-3 overflow-hidden rounded-xl border border-teal-200 bg-white shadow-sm ring-1 ring-teal-100 dark:border-teal-400/25 dark:bg-teal-950/15 dark:ring-teal-500/20 dark:shadow-dash-card">

        <div class="flex flex-col gap-3 border-b border-teal-200/80 bg-teal-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-teal-950/35">
            <div class="min-w-0 shrink-0">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-teal-900 dark:text-teal-200">
                    Etapa C · Diligencia disciplinaria (FO-GJ-04)
                </h4>
                <p class="mt-0.5 text-[11px] text-slate-600 dark:text-slate-400">
                    Paso {{ $stepNumber }} de {{ $totalSteps }}
                </p>
            </div>

            <nav aria-label="Progreso diligencia" class="min-w-0 flex-1">
                <ol class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1.5 text-[10px] sm:text-xs">
                    @foreach ($stageSteps as $step)
                        @php
                            $isCurrent = $step['status'] === DiligenceStageProgress::STATUS_CURRENT;
                            $isDone = $step['status'] === DiligenceStageProgress::STATUS_DONE;
                            $dotClass = $isDone
                                ? 'bg-emerald-500 ring-emerald-500/30'
                                : ($isCurrent ? 'bg-teal-500 ring-teal-400/40' : 'bg-slate-300 dark:bg-white/20');
                            $textClass = $isDone
                                ? 'text-emerald-800 dark:text-emerald-300'
                                : ($isCurrent ? 'font-semibold text-teal-900 dark:text-teal-100' : 'text-slate-500 dark:text-slate-500');
                        @endphp
                        <li class="flex items-center gap-1.5 {{ $textClass }}" @if($isCurrent) aria-current="step" @endif>
                            <span class="h-2 w-2 shrink-0 rounded-full ring-2 ring-offset-1 ring-offset-transparent dark:ring-offset-teal-950 {{ $dotClass }}"></span>
                            <span class="hidden lg:inline">{{ $step['label'] }}</span>
                            <span class="lg:hidden">{{ $isCurrent || $isDone ? $step['label'] : '' }}</span>
                        </li>
                    @endforeach
                </ol>
            </nav>

            @can('transition', $case)
                <button type="button" wire:click="requestAdvanceFromDiligencia"
                    class="shrink-0 inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40 dark:hover:bg-white/15">
                    Siguiente etapa →
                </button>
            @endcan
        </div>

        <div class="flex flex-col gap-3 border-b border-teal-200/60 bg-teal-100/50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-teal-950/50">
            <div class="min-w-0 space-y-0.5">
                <p class="text-sm font-bold text-slate-900 dark:text-white">Paso {{ $stepNumber }} · {{ $actionTitle }}</p>
                @if ($case->citation_confirmed_date)
                    <p class="text-sm text-slate-700 dark:text-slate-300">
                        Diligencia programada:
                        <strong class="tabular-nums">{{ $case->citation_confirmed_date->format('d/m/Y') }}</strong>
                        @if ($case->citation_confirmed_time)
                            <span class="text-slate-400" aria-hidden="true"> · </span>
                            <strong>{{ $diligenceSlotDisplay['time'] ?? '—' }}</strong>
                        @endif
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if ($foGj04PreviewUrl)
                    <a href="{{ $foGj04PreviewUrl }}" target="_blank" rel="noopener"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                        Plantilla FO-GJ-04
                    </a>
                @endif

                @if ($currentStepKey === 'acta' && $case->citation_confirmed_date && $isAssignedLawyer && ! $case->fo_gj_04_generated_at)
                    @if ($canEditFoGj04Draft)
                        <button type="button" wire:click="openFoGj04DraftModal"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            {{ $foGj04DraftCompleted ? 'Editar FO-GJ-04' : 'Diligenciar FO-GJ-04' }}
                        </button>
                    @endif
                    @if ($canPreviewFoGj04)
                        <button type="button" wire:click="openFoGj04PdfPreview"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            Vista previa PDF
                        </button>
                    @endif
                    @if ($canGenerateFoGj04)
                        <button type="button" wire:click="generateFoGj04"
                            class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                            Generar y guardar
                        </button>
                    @endif
                @elseif ($case->fo_gj_04_generated_at && $canPreviewFoGj04)
                    <button type="button" wire:click="openFoGj04PdfPreview"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                        Consultar FO-GJ-04 (PDF)
                    </button>
                @endif
            </div>
        </div>

        @error('fo_gj_04')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('diligenceAdvance')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <div class="space-y-4 px-4 py-4">
            @if ($currentStepKey === 'acta' || $actaDoc || $case->fo_gj_04_generated_at)
                <div class="rounded-lg border border-slate-200 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Acta FO-GJ-04</p>
                    @if ($actaDoc)
                        <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">
                            Documento en expediente:
                            <a href="{{ route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $actaDoc, 'download' => 1]) }}"
                                class="font-semibold text-teal-700 underline dark:text-teal-300" target="_blank" rel="noopener">
                                {{ $actaDoc->original_name }}
                            </a>
                            @if ($case->fo_gj_04_generated_at)
                                <span class="text-slate-500 dark:text-slate-400"> · generado {{ $case->fo_gj_04_generated_at->timezone('America/Bogota')->format('d/m/Y H:i') }}</span>
                            @endif
                        </p>
                    @elseif ($foGj04DraftCompleted)
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            Borrador diligenciado. Use <strong>Vista previa PDF</strong> o <strong>Generar y guardar</strong> para incorporar el acta al expediente.
                        </p>
                    @else
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            Diligencie el acta con el cuestionario y la manifestación del trabajador. Mientras tanto puede consultar la plantilla en blanco FO-GJ-04.
                        </p>
                    @endif
                </div>
            @endif

            @if ($currentStepKey === 'decision')
                <div class="rounded-lg border border-teal-200 bg-teal-50/70 px-4 py-3 text-sm dark:border-teal-500/30 dark:bg-teal-950/30">
                    <p class="font-semibold text-teal-950 dark:text-teal-100">Listo para avanzar a decisión</p>
                    <p class="mt-1 text-teal-900/90 dark:text-teal-100/85">
                        Cuando la diligencia y el acta estén completas, use <strong>Siguiente etapa</strong> para pasar al comunicado de decisión (etapa D).
                    </p>
                </div>
            @endif
        </div>
    </div>

    @include('livewire.disciplinary.cases.partials.stage-c-diligence-modals', ['case' => $case])

    @if ($showDiligenceAdvanceConfirm ?? false)
        <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-900/50" wire:key="diligence-advance-confirm">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Confirmar avance de etapa</h2>
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                    ¿Pasar el expediente a <strong>{{ $diligenceAdvanceTargetLabel ?? 'comunicado de decisión' }}</strong>?
                </p>
                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="closeDiligenceAdvanceConfirm" class="px-4 py-2 text-sm font-semibold text-slate-700 rounded-md ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">Cancelar</button>
                    <button type="button" wire:click="confirmAdvanceFromDiligencia" class="px-4 py-2 text-sm font-semibold text-white bg-teal-600 rounded-md hover:bg-teal-700">Confirmar y avanzar</button>
                </div>
            </div>
        </div>
    @endif
@endif
