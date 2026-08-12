@php
    use App\Enums\Disciplinary\Decision;
    use App\Support\Disciplinary\DecisionBranch;
    use App\Support\Disciplinary\DecisionStageProgress;
    use App\Services\Disciplinary\DecisionCoordinationService;

    $stageSteps = $decisionStageSteps ?? collect();
    $currentStep = $decisionCurrentStep ?? ['key' => 'type', 'label' => '', 'status' => 'current', 'hint' => ''];
    $currentStepKey = (string) ($currentStep['key'] ?? 'type');
    $stepNumber = $decisionCurrentStepNumber ?? 1;
    $totalSteps = $decisionTotalSteps ?? 5;
    $decisionDoc = $case->latestDecisionComunicadoDocument();
    $branch = $decisionBranch ?? DecisionBranch::forDecision($case->decision);
    $isFoGj46 = $case->decision === Decision::AMONESTACION_ESCRITA;
    $isFoGj47 = $case->decision === Decision::SUSPENSION;
    $isFoGj45 = $case->decision === Decision::TERMINACION_CONTRATO;
    $stageProgressHelper = app(DecisionStageProgress::class);
    $coordination = app(DecisionCoordinationService::class);
    $actionTitle = match (true) {
        $currentStepKey === 'draft' && $isFoGj46 => 'FO-GJ-46 · Llamado de atención',
        $currentStepKey === 'draft' && $isFoGj47 => 'FO-GJ-47 · Suspensión disciplinaria',
        $currentStepKey === 'draft' && $isFoGj45 => 'FO-GJ-45 · Acta de archivo',
        default => $stageProgressHelper->actionBarTitle($currentStepKey),
    };
    $typeSelected = $case->decision !== null && $case->decision_coordination_started_at !== null;
    $hasOptions = $coordination->hasOpenOptions($case);
    $notificationConfirmed = $coordination->hasConfirmedNotification($case);
    $draftCompleted = $case->decision_draft_completed_at !== null;
    $comunicadoGenerated = $case->decision_comunicado_generated_at !== null || $decisionDoc !== null;
    $evidenceUploaded = $case->decision_evidence_uploaded_at !== null;
    $hrCompleted = $case->decision_hr_review_completed_at !== null || $case->hasDecisionHrAnnex();
    $needsPackage = $branch !== null && DecisionBranch::requiresLawyerTerminationPackage($branch);
    $canSelectType = auth()->user()->can('selectDecisionType', $case);
    $canEditDraft = auth()->user()->can('editDecisionDraft', $case);
    $canPreview = auth()->user()->can('previewDecisionComunicado', $case);
    $canGenerate = auth()->user()->can('generateDecisionComunicado', $case);
    $canFinalize = auth()->user()->can('finalizeDecisionCase', $case);
    $isAssignedLawyer = (int) $case->assigned_lawyer_id === (int) auth()->id();
    $canPostAgenda = auth()->user()->can('postAgendaLawyer', $case);
    $decisionReadOnly = $decisionReadOnly ?? false;
    $showStageD = ($showsDecisionStagePanel ?? false) || $decisionReadOnly;
    $canConfirmOption = ! $decisionReadOnly
        && $isAssignedLawyer
        && $coordination->canLawyerConfirm($case, auth()->user())
        && ($selectedDecisionSlotKey ?? '') !== '';
    $canRequestNewOptions = ! $decisionReadOnly
        && $isAssignedLawyer
        && $coordination->canLawyerRequestNewOptions($case, auth()->user());
    $draftButtonLabel = match (true) {
        $isFoGj46 => $draftCompleted ? 'Editar FO-GJ-46' : 'Diligenciar FO-GJ-46',
        $isFoGj47 => $draftCompleted ? 'Editar FO-GJ-47' : 'Diligenciar FO-GJ-47',
        $isFoGj45 => $draftCompleted ? 'Editar FO-GJ-45' : 'Diligenciar FO-GJ-45',
        default => $draftCompleted ? 'Editar comunicado' : 'Diligenciar comunicado',
    };
    $generateButtonLabel = match (true) {
        $isFoGj46 => 'Generar FO-GJ-46',
        $isFoGj47 => 'Generar FO-GJ-47',
        $isFoGj45 => 'Generar FO-GJ-45',
        default => 'Generar y guardar',
    };
    $previewButtonLabel = match (true) {
        $isFoGj46 => 'Consultar FO-GJ-46 (PDF)',
        $isFoGj47 => 'Consultar FO-GJ-47 (PDF)',
        $isFoGj45 => 'Consultar FO-GJ-45 (PDF)',
        default => 'Consultar comunicado (PDF)',
    };
@endphp

@if ($showStageD)
    <div class="overflow-hidden rounded-xl border shadow-sm ring-1 dark:shadow-dash-card {{ ($insideStageModal ?? false) ? '' : 'md:col-span-2 xl:col-span-3' }}
        {{ $decisionReadOnly
            ? 'border-slate-200 bg-slate-50/80 ring-slate-200/80 dark:border-white/10 dark:bg-slate-900/25 dark:ring-white/10'
            : 'border-violet-200 bg-white ring-violet-100 dark:border-violet-400/25 dark:bg-violet-950/15 dark:ring-violet-500/20' }}"
        data-stage-block="d">

        <div class="flex flex-col gap-3 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-white/10
            {{ $decisionReadOnly
                ? 'border-slate-200/80 bg-slate-100/60 dark:bg-slate-900/40'
                : 'border-violet-200/80 bg-violet-50/60 dark:bg-violet-950/35' }}">
            <div class="min-w-0 shrink-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wider {{ $decisionReadOnly ? 'text-slate-700 dark:text-slate-300' : 'text-violet-900 dark:text-violet-200' }}">
                        Etapa D · Comunicado de decisión
                    </h4>
                    @if ($decisionReadOnly)
                        <span class="inline-flex items-center rounded-full bg-slate-200/90 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-700 ring-1 ring-slate-300/80 dark:bg-white/10 dark:text-slate-300 dark:ring-white/15">
                            Completada · Solo lectura
                        </span>
                    @endif
                </div>
                <p class="mt-0.5 text-[11px] text-slate-600 dark:text-slate-400">
                    @if ($decisionReadOnly)
                        Etapa cerrada — {{ $totalSteps }} pasos completados
                    @else
                        Paso {{ $stepNumber }} de {{ $totalSteps }}
                    @endif
                    @if ($case->decision)
                        · <strong>{{ $case->decision->label() }}</strong>
                    @endif
                </p>
            </div>

            <nav aria-label="Progreso decisión" class="min-w-0 flex-1">
                <ol class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1.5 text-[10px] sm:text-xs">
                    @foreach ($stageSteps as $step)
                        @php
                            $isCurrent = $step['status'] === DecisionStageProgress::STATUS_CURRENT;
                            $isDone = $step['status'] === DecisionStageProgress::STATUS_DONE;
                            $dotClass = $isDone
                                ? 'bg-emerald-500 ring-emerald-500/30'
                                : ($isCurrent ? 'bg-violet-500 ring-violet-400/40' : 'bg-slate-300 dark:bg-white/20');
                            $textClass = $isDone
                                ? 'text-emerald-800 dark:text-emerald-300'
                                : ($isCurrent ? 'font-semibold text-violet-900 dark:text-violet-100' : 'text-slate-500 dark:text-slate-500');
                        @endphp
                        <li class="flex items-center gap-1.5 {{ $textClass }}" @if($isCurrent) aria-current="step" @endif>
                            <span class="h-2 w-2 shrink-0 rounded-full ring-2 ring-offset-1 ring-offset-transparent dark:ring-offset-violet-950 {{ $dotClass }}"></span>
                            <span class="hidden lg:inline">{{ $step['label'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </nav>

            @if ($canFinalize && ! $decisionReadOnly)
                @can('transition', $case)
                    <button type="button" wire:click="requestFinalizeDecision"
                        class="shrink-0 inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-violet-800 ring-1 ring-violet-300 hover:bg-violet-50 dark:bg-white/10 dark:text-violet-100 dark:ring-violet-400/40 dark:hover:bg-white/15">
                        Finalizar proceso →
                    </button>
                @endcan
            @endif
        </div>

        <div class="flex flex-col gap-3 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-white/10
            {{ $decisionReadOnly ? 'border-slate-200/60 bg-slate-100/50 dark:bg-slate-900/50' : 'border-violet-200/60 bg-violet-100/50 dark:bg-violet-950/50' }}">
            <div class="min-w-0 space-y-0.5">
                <p class="text-sm font-bold text-slate-900 dark:text-white">Paso {{ $stepNumber }} · {{ $actionTitle }}</p>
                @if ($currentStep['hint'] ?? '')
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $currentStep['hint'] }}</p>
                @endif
                @if ($notificationConfirmed && $case->decision_notification_date)
                    <p class="text-sm text-slate-700 dark:text-slate-300">
                        Notificación: <strong>{{ $case->decision_notification_date->format('d/m/Y') }}</strong>
                        · Turno: <strong>{{ $case->decision_notification_shift }}</strong>
                        · Zona: <strong>{{ $case->decision_notification_zone }}</strong>
                        · Supervisor: <strong>{{ $case->decision_notification_supervisor_name }}</strong>
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if ($decisionReadOnly && $comunicadoGenerated && $canPreview)
                    <button type="button" wire:click="openDecisionPdfPreview"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-slate-200 dark:ring-white/20">
                        {{ $previewButtonLabel }}
                    </button>
                @elseif (! $decisionReadOnly)
                    @if (! $typeSelected && $canSelectType && $isAssignedLawyer)
                        <button type="button" wire:click="openDecisionTypeModal"
                            class="inline-flex items-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">
                            Registrar tipo de decisión
                        </button>
                    @endif

                    @if ($canConfirmOption)
                        <button type="button" wire:click="confirmDecisionSlot"
                            class="inline-flex items-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">
                            Confirmar opción
                        </button>
                    @endif

                    @if ($canRequestNewOptions)
                        <button type="button" wire:click="requestNewDecisionOptions"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-violet-800 ring-1 ring-violet-300 hover:bg-violet-50 dark:bg-white/10 dark:text-violet-100 dark:ring-violet-400/40">
                            Solicitar otras fechas
                        </button>
                    @endif

                    @if ($typeSelected && $notificationConfirmed && $currentStepKey === 'draft' && $isAssignedLawyer && ! $comunicadoGenerated)
                        @if ($canEditDraft)
                            <button type="button" wire:click="openDecisionDraftModal"
                                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-violet-800 ring-1 ring-violet-300 hover:bg-violet-50 dark:bg-white/10 dark:text-violet-100 dark:ring-violet-400/40">
                                {{ $draftButtonLabel }}
                            </button>
                        @endif
                        @if ($canPreview)
                            <button type="button" wire:click="openDecisionPdfPreview"
                                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-violet-800 ring-1 ring-violet-300 hover:bg-violet-50 dark:bg-white/10 dark:text-violet-100 dark:ring-violet-400/40">
                                Vista previa PDF
                            </button>
                        @endif
                        @if ($canGenerate)
                            <button type="button" wire:click="generateDecisionComunicado"
                                class="inline-flex items-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">
                                {{ $generateButtonLabel }}
                            </button>
                        @endif
                    @elseif ($comunicadoGenerated && $canPreview)
                        <button type="button" wire:click="openDecisionPdfPreview"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-violet-800 ring-1 ring-violet-300 hover:bg-violet-50 dark:bg-white/10 dark:text-violet-100 dark:ring-violet-400/40">
                            {{ $previewButtonLabel }}
                        </button>
                    @endif
                @endif
            </div>
        </div>

        <div class="px-4 py-4 space-y-4">
            @error('selectedDecisionSlotKey')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('decisionCoordination')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            @if ($typeSelected && $currentStepKey === 'coordination' && ! $decisionReadOnly)
                <div class="rounded-lg border border-violet-200/80 bg-violet-50/40 p-4 dark:border-white/10 dark:bg-violet-950/25">
                    <p class="text-sm text-slate-700 dark:text-slate-300">
                        @if (! $hasOptions)
                            Coordinación abierta. Planeación debe publicar <strong>una o más opciones</strong> (fecha, turno, zona y supervisor) en
                            <strong>Coordinaciones</strong> o desde el chat.
                        @elseif (! $notificationConfirmed)
                            Hay opciones en el chat. Seleccione una con el radio y pulse <strong>Confirmar opción</strong>.
                        @else
                            Programación confirmada.
                        @endif
                    </p>
                </div>
            @endif

            @if ($typeSelected && $canPostAgenda && ! $decisionReadOnly)
                <div class="rounded-lg border border-violet-200/80 bg-violet-50/40 p-4 dark:border-white/10 dark:bg-violet-950/25">
                    <p class="text-sm text-slate-700 dark:text-slate-300">
                        Use <strong>Chat planeación</strong> para ver opciones, confirmar o solicitar otras fechas.
                    </p>
                </div>
            @endif

            @if ($comunicadoGenerated && ! $evidenceUploaded && ! $decisionReadOnly && ! $isFoGj45)
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    El supervisor asignado debe cargar la evidencia de notificación en <strong>Evidencias pendientes</strong>.
                    Luego use <strong>Finalizar proceso</strong> para escribir la conclusión y cerrar el caso.
                </p>
            @endif

            @if ($isFoGj45 && $comunicadoGenerated && ! $hrCompleted && ! $decisionReadOnly)
                <div class="rounded-lg border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-500/30 dark:bg-amber-950/30">
                    <p class="text-sm font-medium text-amber-900 dark:text-amber-100">Paquete PDF de terminación</p>
                    <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                        Cargue un solo PDF con los documentos firmados (certificado laboral, orden de egreso, seguridad social, liquidación, etc.).
                    </p>
                    <div class="mt-3 flex flex-wrap items-end gap-2">
                        <input type="file" wire:model="terminationPackageFile" accept="application/pdf" class="block w-full max-w-sm text-sm text-slate-700 dark:text-slate-200" />
                        <button type="button" wire:click="uploadTerminationPackage" class="inline-flex items-center rounded-md bg-violet-600 px-3 py-2 text-sm font-semibold text-white hover:bg-violet-700">
                            Subir paquete
                        </button>
                    </div>
                    @error('terminationPackageFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="terminationPackageFile,uploadTerminationPackage" class="mt-2 text-xs text-slate-500">Cargando…</div>
                </div>
            @endif

            @if ($evidenceUploaded)
                <p class="text-sm text-emerald-800 dark:text-emerald-300">
                    Evidencia de notificación registrada
                    @if ($case->decision_evidence_type)
                        ({{ \App\Enums\Disciplinary\CitationEvidenceType::tryFrom($case->decision_evidence_type)?->label() ?? $case->decision_evidence_type }}).
                    @endif
                    Puede cerrar el caso con una breve conclusión.
                </p>
            @endif

            @if ($isFoGj45 && $hrCompleted)
                <p class="text-sm text-emerald-800 dark:text-emerald-300">
                    Paquete de terminación cargado. Puede cerrar el caso con una breve conclusión.
                </p>
            @endif
        </div>
    </div>
@endif
