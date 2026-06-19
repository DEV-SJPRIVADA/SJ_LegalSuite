@php
    use App\Enums\Disciplinary\CaseStatus;
    use App\Enums\Disciplinary\DiligenceAttendance;
    use App\Services\Disciplinary\FoGj04DraftService;
    use App\Support\Disciplinary\DiligenceStageProgress;

    $stageSteps = $diligenceStageSteps ?? collect();
    $currentStep = $diligenceCurrentStep ?? ['key' => 'attendance', 'label' => '', 'status' => 'current', 'hint' => ''];
    $currentStepKey = (string) ($currentStep['key'] ?? 'attendance');
    $stepNumber = $diligenceCurrentStepNumber ?? 1;
    $totalSteps = $diligenceTotalSteps ?? 4;
    $stageProgressHelper = app(DiligenceStageProgress::class);
    $actionTitle = $stageProgressHelper->actionBarTitle($currentStepKey);
    $actaDoc = $case->latestActaDiligenciaDocument();
    $constanciaDoc = $case->latestConstanciaInasistenciaDocument();
    $attendance = $case->diligence_attendance;
    $attendanceRegistered = $attendance !== null;
    $workerAttended = $attendance === DiligenceAttendance::ATTENDED;
    $workerAbsent = $attendance === DiligenceAttendance::ABSENT;
    $canRegisterAttendance = auth()->user()->can('registerDiligenceAttendance', $case);
    $canEditFoGj04Draft = auth()->user()->can('editFoGj04Draft', $case);
    $canPreviewFoGj04 = auth()->user()->can('previewFoGj04', $case);
    $canGenerateFoGj04 = auth()->user()->can('generateFoGj04', $case);
    $canCaptureWorkerSignature = auth()->user()->can('captureFoGj04WorkerSignature', $case);
    $foGj04DraftCompleted = $case->fo_gj_04_draft_completed_at !== null;
    $foGj04HasWorkerSignature = app(FoGj04DraftService::class)->hasWorkerSignature($case);
    $canEditFoGj44Draft = auth()->user()->can('editFoGj44Draft', $case);
    $canPreviewFoGj44 = auth()->user()->can('previewFoGj44', $case);
    $canGenerateFoGj44 = auth()->user()->can('generateFoGj44', $case);
    $foGj44DraftCompleted = $case->fo_gj_44_draft_completed_at !== null;
    $canManageJustification = auth()->user()->can('manageDiligenceJustification', $case);
    $canEditFoGj54Draft = auth()->user()->can('editFoGj54Draft', $case);
    $canPreviewFoGj54 = auth()->user()->can('previewFoGj54', $case);
    $canGenerateFoGj54 = auth()->user()->can('generateFoGj54', $case);
    $foGj54DraftCompleted = $case->fo_gj_54_draft_completed_at !== null;
    $canEditComiteDraft = auth()->user()->can('editComiteDraft', $case);
    $canPreviewComite = auth()->user()->can('previewComite', $case);
    $canGenerateComite = auth()->user()->can('generateComite', $case);
    $comiteDraftCompleted = $case->comite_draft_completed_at !== null;
    $comiteDoc = $case->latestComiteActaDocument();
    $justificationStage = $case->activeJustificationStage();
    $isAssignedLawyer = (int) $case->assigned_lawyer_id === (int) auth()->id();
    $isComitePanel = $case->current_status === CaseStatus::COMITE_DISCIPLINARIO;
    $panelTitle = match (true) {
        $isComitePanel => 'Etapa C · Comité disciplinario',
        $case->current_status === CaseStatus::JUSTIFICACION_PENDIENTE => 'Etapa C · Justificación de inasistencia',
        default => 'Etapa C · Diligencia disciplinaria (FO-GJ-04)',
    };
@endphp

@if ($showsDiligenceStagePanel ?? $isDiligenciaActive ?? false)
    <div class="md:col-span-2 xl:col-span-3 overflow-hidden rounded-xl border border-teal-200 bg-white shadow-sm ring-1 ring-teal-100 dark:border-teal-400/25 dark:bg-teal-950/15 dark:ring-teal-500/20 dark:shadow-dash-card">

        <div class="flex flex-col gap-3 border-b border-teal-200/80 bg-teal-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-teal-950/35">
            <div class="min-w-0 shrink-0">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-teal-900 dark:text-teal-200">
                    {{ $panelTitle }}
                </h4>
                <p class="mt-0.5 text-[11px] text-slate-600 dark:text-slate-400">
                    Paso {{ $stepNumber }} de {{ $totalSteps }}
                    @if ($attendanceRegistered)
                        · Asistencia: <strong>{{ $attendance->label() }}</strong>
                    @endif
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

            @if ($case->current_status === CaseStatus::DILIGENCIA && $workerAttended)
                @can('transition', $case)
                    <button type="button" wire:click="requestAdvanceFromDiligencia"
                        class="shrink-0 inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40 dark:hover:bg-white/15">
                        Siguiente etapa →
                    </button>
                @endcan
            @endif
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
                @if ($justificationStage?->deadline_at)
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        Plazo justificación: <strong>{{ $justificationStage->deadline_at->format('d/m/Y') }}</strong>
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if (! $attendanceRegistered && $canRegisterAttendance && $isAssignedLawyer)
                    <button type="button" wire:click="requestRegisterDiligenceAttendance('attended')"
                        class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Asistió
                    </button>
                    <button type="button" wire:click="requestRegisterDiligenceAttendance('absent')"
                        class="inline-flex items-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                        No asistió
                    </button>
                @elseif ($workerAttended && $currentStepKey === 'acta' && $case->citation_confirmed_date && $isAssignedLawyer && ! $case->fo_gj_04_generated_at)
                    @if ($canEditFoGj04Draft)
                        <button type="button" wire:click="openFoGj04DraftModal"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            {{ $foGj04DraftCompleted ? 'Editar FO-GJ-04' : 'Diligenciar FO-GJ-04' }}
                        </button>
                    @endif
                    @if ($canCaptureWorkerSignature)
                        <button type="button" wire:click="openFoGj04WorkerSignaturePad"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            {{ $foGj04HasWorkerSignature ? 'Editar firma trabajador' : 'Firma del trabajador' }}
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
                @elseif ($workerAttended && $case->fo_gj_04_generated_at && $canPreviewFoGj04)
                    <button type="button" wire:click="openFoGj04PdfPreview"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                        Consultar FO-GJ-04 (PDF)
                    </button>
                @elseif ($workerAbsent && $case->current_status === CaseStatus::DILIGENCIA && $currentStepKey === 'constancia' && $isAssignedLawyer && ! $case->fo_gj_44_generated_at)
                    @if ($canEditFoGj44Draft)
                        <button type="button" wire:click="openFoGj44DraftModal"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            {{ $foGj44DraftCompleted ? 'Editar FO-GJ-44' : 'Diligenciar FO-GJ-44' }}
                        </button>
                    @endif
                    @if ($canPreviewFoGj44)
                        <button type="button" wire:click="openFoGj44PdfPreview"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            Vista previa PDF
                        </button>
                    @endif
                    @if ($canGenerateFoGj44)
                        <button type="button" wire:click="generateFoGj44"
                            class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                            Generar y guardar
                        </button>
                    @endif
                @elseif ($workerAbsent && $case->fo_gj_44_generated_at && $canPreviewFoGj44 && $case->current_status === CaseStatus::DILIGENCIA)
                    <button type="button" wire:click="openFoGj44PdfPreview"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                        Consultar FO-GJ-44 (PDF)
                    </button>
                @elseif ($canManageJustification && $isAssignedLawyer)
                    @if ($canEditFoGj54Draft)
                        <button type="button" wire:click="openFoGj54DraftModal"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            {{ $foGj54DraftCompleted ? 'Editar FO-GJ-54' : 'Diligenciar FO-GJ-54' }}
                        </button>
                    @endif
                    @if ($canPreviewFoGj54)
                        <button type="button" wire:click="openFoGj54PdfPreview"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            Vista previa FO-GJ-54
                        </button>
                    @endif
                    @if ($canGenerateFoGj54)
                        <button type="button" wire:click="generateFoGj54AndAcceptJustification"
                            class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Aceptar y reprogramar
                        </button>
                    @endif
                    <button type="button" wire:click="requestRejectDiligenceJustification"
                        class="inline-flex items-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                        Rechazar / Comité
                    </button>
                @elseif ($isComitePanel && $isAssignedLawyer)
                    @if ($canEditComiteDraft)
                        <button type="button" wire:click="openComiteDraftModal"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            {{ $comiteDraftCompleted ? 'Editar acta de comité' : 'Diligenciar comité' }}
                        </button>
                    @endif
                    @if ($canPreviewComite)
                        <button type="button" wire:click="openComitePdfPreview"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            Vista previa PDF
                        </button>
                    @endif
                    @if ($canGenerateComite)
                        <button type="button" wire:click="generateComiteActa"
                            class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                            Generar y guardar
                        </button>
                    @endif
                    @if ($case->fo_gj_44_generated_at && auth()->user()->can('previewFoGj44', $case))
                        <button type="button" wire:click="openFoGj44PdfPreview"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            Consultar FO-GJ-44 (PDF)
                        </button>
                    @endif
                @endif
            </div>
        </div>

        @error('fo_gj_04')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('fo_gj_44')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('fo_gj_54')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('diligenceAdvance')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('diligenceAttendance')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('justification')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('comiteDecisionNarrative')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('comiteAttendees')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <div class="space-y-4 px-4 py-4">
            @if (! $attendanceRegistered && $currentStepKey === 'attendance')
                <div class="rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm dark:border-amber-500/30 dark:bg-amber-950/25">
                    <p class="font-semibold text-amber-950 dark:text-amber-100">Primer paso obligatorio</p>
                    <p class="mt-1 text-amber-900/90 dark:text-amber-100/85">
                        Registre si el trabajador <strong>asistió</strong> o <strong>no asistió</strong> a la diligencia programada.
                        Esta decisión queda guardada en el expediente y <strong>no puede modificarse</strong> después.
                    </p>
                </div>
            @endif

            @if ($actaDoc)
                <div class="rounded-lg border border-slate-200 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Acta FO-GJ-04</p>
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
                </div>
            @endif

            @if ($constanciaDoc)
                <div class="rounded-lg border border-slate-200 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Constancia FO-GJ-44</p>
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">
                        Documento en expediente:
                        <a href="{{ route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $constanciaDoc, 'download' => 1]) }}"
                            class="font-semibold text-teal-700 underline dark:text-teal-300" target="_blank" rel="noopener">
                            {{ $constanciaDoc->original_name }}
                        </a>
                        @if ($case->fo_gj_44_generated_at)
                            <span class="text-slate-500 dark:text-slate-400"> · generado {{ $case->fo_gj_44_generated_at->timezone('America/Bogota')->format('d/m/Y H:i') }}</span>
                        @endif
                    </p>
                </div>
            @endif

            @if ($workerAttended && $currentStepKey === 'decision')
                <div class="rounded-lg border border-teal-200 bg-teal-50/70 px-4 py-3 text-sm dark:border-teal-500/30 dark:bg-teal-950/30">
                    <p class="font-semibold text-teal-950 dark:text-teal-100">Listo para avanzar a decisión</p>
                    <p class="mt-1 text-teal-900/90 dark:text-teal-100/85">
                        Cuando el acta FO-GJ-04 esté generada con la firma del trabajador, use <strong>Siguiente etapa</strong> para pasar al comunicado de decisión (etapa D).
                    </p>
                </div>
            @endif

            @if ($canManageJustification && $currentStepKey === 'justification')
                <div class="rounded-lg border border-amber-200 bg-amber-50/70 px-4 py-3 text-sm dark:border-amber-500/30 dark:bg-amber-950/30">
                    <p class="font-semibold text-amber-950 dark:text-amber-100">Ventana de justificación (2 días calendario)</p>
                    <p class="mt-1 text-amber-900/90 dark:text-amber-100/85">
                        Si la justificación es aceptada, diligencie el FO-GJ-54 y reprograme la diligencia.
                        Si no hay justificación o es rechazada, remita el caso a <strong>comité disciplinario</strong>.
                    </p>
                </div>
            @endif

            @if ($isComitePanel && $currentStepKey === 'comite')
                <div class="rounded-lg border border-teal-200 bg-teal-50/70 px-4 py-3 text-sm dark:border-teal-500/30 dark:bg-teal-950/30">
                    <p class="font-semibold text-teal-950 dark:text-teal-100">Comité disciplinario</p>
                    <p class="mt-1 text-teal-900/90 dark:text-teal-100/85">
                        Diligencie el acta del comité, previsualice el PDF y genérelo para incorporarlo al expediente.
                    </p>
                </div>
            @endif

            @if ($comiteDoc)
                <div class="rounded-lg border border-slate-200 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Acta de comité</p>
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">
                        Documento en expediente:
                        <a href="{{ route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $comiteDoc, 'download' => 1]) }}"
                            class="font-semibold text-teal-700 underline dark:text-teal-300" target="_blank" rel="noopener">
                            {{ $comiteDoc->original_name }}
                        </a>
                        @if ($case->comite_generated_at)
                            <span class="text-slate-500 dark:text-slate-400"> · generado {{ $case->comite_generated_at->timezone('America/Bogota')->format('d/m/Y H:i') }}</span>
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    @include('livewire.disciplinary.cases.partials.stage-c-diligence-modals', ['case' => $case])

    @if ($showDiligenceAttendanceConfirm ?? false)
        <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-900/50" wire:key="diligence-attendance-confirm">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Confirmar asistencia</h2>
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                    ¿Registrar que el trabajador
                    <strong>{{ $diligenceAttendancePending === 'attended' ? 'asistió' : 'no asistió' }}</strong>
                    a la diligencia programada?
                </p>
                <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">Esta decisión no podrá modificarse después.</p>
                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="closeDiligenceAttendanceConfirm" class="px-4 py-2 text-sm font-semibold text-slate-700 rounded-md ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">Cancelar</button>
                    <button type="button" wire:click="confirmRegisterDiligenceAttendance" class="px-4 py-2 text-sm font-semibold text-white bg-teal-600 rounded-md hover:bg-teal-700">Confirmar</button>
                </div>
            </div>
        </div>
    @endif

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

    @if ($showJustificationRejectConfirm ?? false)
        <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-900/50" wire:key="justification-reject-confirm">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Rechazar justificación</h2>
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                    El expediente pasará a <strong>comité disciplinario</strong>. Opcionalmente indique el motivo:
                </p>
                <textarea wire:model="justificationRejectNote" rows="3" class="mt-3 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white" placeholder="Motivo del rechazo (opcional)"></textarea>
                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="closeJustificationRejectConfirm" class="px-4 py-2 text-sm font-semibold text-slate-700 rounded-md ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">Cancelar</button>
                    <button type="button" wire:click="confirmRejectDiligenceJustification" class="px-4 py-2 text-sm font-semibold text-white bg-amber-600 rounded-md hover:bg-amber-700">Confirmar rechazo</button>
                </div>
            </div>
        </div>
    @endif
@endif
