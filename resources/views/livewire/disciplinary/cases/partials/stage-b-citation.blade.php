@php
    use App\Enums\Disciplinary\CaseStatus;
    use App\Support\Disciplinary\CitationStageProgress;
    use App\Support\Disciplinary\WorkerLegalPhrasing;
    use App\Services\Disciplinary\DisciplinaryCitationWorkflowService;
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

    $citationReadOnly = $citationReadOnly ?? false;
    $isCitacion = $case->current_status === CaseStatus::CITACION_PROGRAMADA;
    $showStageB = $isCitacion || $citationReadOnly;
    $requirementLabels = $citationRequirementLabels ?? DisciplinaryCitationWorkflowService::requirementLabels();
    $agendaThread = $case->agendaThread;
    $coordinationIsClosed = $agendaThread?->isClosed() ?? false;
    $foGj03Labels = $foGj03GenerationLabels ?? [];
    $foGj03Checklist = $foGj03GenerationChecklist ?? collect();
    $stageSteps = $citationStageSteps ?? collect();
    $currentStep = $citationCurrentStep ?? ['key' => 'coordination', 'label' => '', 'status' => 'current', 'hint' => ''];
    $currentStepKey = (string) ($currentStep['key'] ?? 'coordination');
    $currentStepHint = (string) ($currentStep['hint'] ?? '');
    $stepNumber = $citationCurrentStepNumber ?? 1;
    $totalSteps = $citationTotalSteps ?? 6;
    $stageProgressHelper = app(CitationStageProgress::class);
    $actionTitle = $stageProgressHelper->actionBarTitle($currentStepKey);
    $coordinationChatAvailable = $case->hasCoordinationStarted() && $case->allowsAgendaThread() && ! $coordinationIsClosed;
    $canGenerateFoGj03 = auth()->user()->can('generateFoGj03', $case);
    $canPreviewFoGj03 = auth()->user()->can('previewFoGj03', $case);
    $canEditFoGj03Draft = auth()->user()->can('editFoGj03Draft', $case);
    $foGj03DraftCompleted = $case->fo_gj_03_draft_completed_at !== null;
    $isAssignedLawyer = (int) $case->assigned_lawyer_id === (int) auth()->id();

    $diligenceSlotDisplay = $diligenceSlotDisplay ?? ['date' => '—', 'time' => '—', 'confirmed' => false];
    $notificationSlotDisplay = $notificationSlotDisplay ?? ['date' => '—', 'shift' => '—', 'zone' => '—', 'nivel7' => '—', 'completed' => false];
    $useDiligenceDateActionBar = $case->hasCoordinationStarted();
    $canSelectCitationSlot = ! $coordinationIsClosed
        && ! ($diligenceSlotDisplay['confirmed'] ?? false)
        && auth()->user()->can('postAgendaLawyer', $case);
    $showConfirmCitationSlot = $canSelectCitationSlot
        && ($selectedCitationSlotKey ?? '') !== ''
        && in_array($currentStepKey, ['planning_slots', 'definitive_date'], true);

    if ($citationReadOnly) {
        $coordinationIsClosed = true;
        $canSelectCitationSlot = false;
        $showConfirmCitationSlot = false;
        $useDiligenceDateActionBar = $case->hasCoordinationStarted();
    }

    $showCitationStepPanel = ! $citationReadOnly
        && (
            ($currentStepKey === 'fo_gj_03' && $case->citation_confirmed_date && $isAssignedLawyer && ! $canGenerateFoGj03)
            || ($currentStepKey === 'evidence' && auth()->user()->can('viewCitationEvidence', $case))
        );
    $employeeGenderReady = $case->employee
        ? WorkerLegalPhrasing::fromEmployee($case->employee)->hasDefiniteGender()
        : false;
@endphp

@if ($showStageB)
    <div class="overflow-hidden rounded-xl border shadow-sm ring-1 dark:shadow-dash-card {{ ($insideStageModal ?? false) ? '' : 'md:col-span-2 xl:col-span-3' }}
        {{ $citationReadOnly
            ? 'border-slate-200 bg-slate-50/80 ring-slate-200/80 dark:border-white/10 dark:bg-slate-900/25 dark:ring-white/10'
            : 'border-indigo-200 bg-white ring-indigo-100 dark:border-indigo-400/25 dark:bg-indigo-950/15 dark:ring-indigo-500/20' }}"
        data-stage-block="b">

        @if (! $citationReadOnly && $case->employee && ! $employeeGenderReady)
            <div class="mx-4 mt-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-950/40" role="alert">
                <p class="text-sm font-semibold text-amber-950 dark:text-amber-100">Complete el género del trabajador</p>
                <p class="mt-1 text-sm text-amber-900 dark:text-amber-200">
                    Los formatos FO-GJ-03, FO-GJ-04 y FO-GJ-54 requieren género <strong>Masculino</strong> o <strong>Femenino</strong> en el catálogo de empleados para la redacción legal correcta.
                    Actualice la ficha de <strong>{{ $case->employee->displayName() }}</strong> antes de generar documentos.
                </p>
                <a href="{{ route('employees.index') }}"
                    class="mt-2 inline-flex text-xs font-semibold text-amber-900 underline dark:text-amber-200">
                    Ir a empleados
                </a>
            </div>
        @endif

        {{-- Cabecera: título + stepper + avanzar etapa --}}
        <div class="flex flex-col gap-3 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-white/10
            {{ $citationReadOnly
                ? 'border-slate-200/80 bg-slate-100/60 dark:bg-slate-900/40'
                : 'border-indigo-200/80 bg-indigo-50/60 dark:bg-indigo-950/35' }}">
            <div class="min-w-0 shrink-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wider {{ $citationReadOnly ? 'text-slate-700 dark:text-slate-300' : 'text-indigo-900 dark:text-indigo-200' }}">
                        Etapa B · Citación a diligencia (FO-GJ-03)
                    </h4>
                    @if ($citationReadOnly)
                        <span class="inline-flex items-center rounded-full bg-slate-200/90 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-700 ring-1 ring-slate-300/80 dark:bg-white/10 dark:text-slate-300 dark:ring-white/15">
                            Completada · Solo lectura
                        </span>
                    @endif
                </div>
                <p class="mt-0.5 text-[11px] text-slate-600 dark:text-slate-400">
                    @if ($citationReadOnly)
                        Etapa cerrada — {{ $totalSteps }} pasos completados
                    @else
                        Paso {{ $stepNumber }} de {{ $totalSteps }}
                    @endif
                </p>
            </div>

            <nav aria-label="Progreso citación" class="min-w-0 flex-1">
                <ol class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1.5 text-[10px] sm:text-xs">
                    @foreach ($stageSteps as $step)
                        @php
                            $isCurrent = $step['status'] === CitationStageProgress::STATUS_CURRENT;
                            $isDone = $step['status'] === CitationStageProgress::STATUS_DONE;
                            $dotClass = $isDone
                                ? 'bg-emerald-500 ring-emerald-500/30'
                                : ($isCurrent ? 'bg-indigo-500 ring-indigo-400/40' : 'bg-slate-300 dark:bg-white/20');
                            $textClass = $isDone
                                ? 'text-emerald-800 dark:text-emerald-300'
                                : ($isCurrent ? 'font-semibold text-indigo-900 dark:text-indigo-100' : 'text-slate-500 dark:text-slate-500');
                        @endphp
                        <li class="flex items-center gap-1.5 {{ $textClass }}" @if($isCurrent) aria-current="step" @endif>
                            <span class="h-2 w-2 shrink-0 rounded-full ring-2 ring-offset-1 ring-offset-transparent dark:ring-offset-indigo-950 {{ $dotClass }}"></span>
                            <span class="hidden lg:inline">{{ $step['label'] }}</span>
                            <span class="lg:hidden">{{ $isCurrent || $isDone ? $step['label'] : '' }}</span>
                        </li>
                    @endforeach
                </ol>
            </nav>

            @if (! $citationReadOnly)
                @can('transition', $case)
                    <button type="button" wire:click="requestAdvanceFromCitacion"
                        class="shrink-0 inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:bg-white/10 dark:text-indigo-100 dark:ring-indigo-400/40 dark:hover:bg-white/15">
                        Siguiente etapa →
                    </button>
                @endcan
            @endif
        </div>

        {{-- Barra de acción --}}
        @if ($useDiligenceDateActionBar)
            <div class="flex flex-col gap-3 border-b border-indigo-200/60 bg-indigo-100/50 px-4 py-3 sm:flex-row sm:items-end sm:justify-between dark:border-white/10 dark:bg-indigo-950/50">
                <div class="flex min-w-0 flex-1 flex-col gap-4 sm:flex-row sm:items-end sm:gap-8 lg:gap-12">
                    <div class="min-w-0 space-y-0.5">
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Fecha para diligencia</p>
                        <p class="text-sm font-bold tabular-nums text-slate-900 dark:text-white">
                            {{ $diligenceSlotDisplay['date'] }}
                            <span class="font-normal text-slate-400 dark:text-slate-500" aria-hidden="true"> · </span>
                            {{ $diligenceSlotDisplay['time'] }}
                        </p>
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Fecha y usuario para notificación</p>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">
                            {{ $notificationSlotDisplay['date'] }}
                            <span class="font-normal text-slate-400 dark:text-slate-500" aria-hidden="true"> · </span>
                            {{ $notificationSlotDisplay['shift'] }}
                            <span class="font-normal text-slate-400 dark:text-slate-500" aria-hidden="true"> · </span>
                            {{ $notificationSlotDisplay['zone'] }}
                            <span class="font-normal text-slate-400 dark:text-slate-500" aria-hidden="true"> · </span>
                            {{ $notificationSlotDisplay['nivel7'] }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    @if ($citationReadOnly)
                        @if ($canPreviewFoGj03 && $case->fo_gj_03_generated_at)
                            <button type="button" wire:click="openFoGj03PdfPreview"
                                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-slate-200 dark:ring-white/20">
                                Consultar FO-GJ-03 (PDF)
                            </button>
                        @endif
                        @can('viewCitationEvidence', $case)
                            @if ($case->citation_evidence_uploaded_at && ($citationEvidenceDocReadonly = $case->latestCitationEvidenceDocument()))
                                <button type="button" wire:click="openDocumentPreview({{ $citationEvidenceDocReadonly->id }})"
                                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-slate-200 dark:ring-white/20">
                                    Ver evidencia
                                </button>
                                <a href="{{ route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $citationEvidenceDocReadonly, 'download' => 1]) }}"
                                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-slate-200 dark:ring-white/20"
                                    download>
                                    Descargar evidencia
                                </a>
                            @endif
                        @endcan
                    @else
                        @can('reassignNotificationSupervisor', $case)
                            @if ($notificationSlotDisplay['completed'] ?? false)
                                <button type="button" wire:click="openReassignSupervisorModal"
                                    class="inline-flex rounded-md bg-white px-3 py-2 text-sm font-semibold text-amber-900 ring-1 ring-amber-400 hover:bg-amber-50 dark:bg-white/10 dark:text-amber-100">
                                    Reasignar supervisor
                                </button>
                            @endif
                        @endcan
                        @if ($showConfirmCitationSlot)
                            <button type="button" wire:click="confirmCitationSlot"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Confirmar fecha
                            </button>
                        @endif

                        @if ($currentStepKey === 'fo_gj_03' && $case->citation_confirmed_date && $isAssignedLawyer && ! $case->fo_gj_03_generated_at)
                            @if ($canEditFoGj03Draft)
                                <button type="button" wire:click="openFoGj03DraftModal"
                                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:bg-white/10 dark:text-indigo-100 dark:ring-indigo-400/40">
                                    {{ $foGj03DraftCompleted ? 'Editar FO-GJ-03' : 'Diligenciar FO-GJ-03' }}
                                </button>
                            @endif
                            @if ($canPreviewFoGj03)
                                <button type="button" wire:click="openFoGj03PdfPreview"
                                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:bg-white/10 dark:text-indigo-100 dark:ring-indigo-400/40">
                                    Vista previa PDF
                                </button>
                            @endif
                            @can('generateFoGj03', $case)
                                <button type="button" wire:click="generateFoGj03"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Generar y guardar
                                </button>
                            @endcan
                        @endif

                        @if ($coordinationChatAvailable && $isAssignedLawyer && ! $citationReadOnly)
                            <button type="button" wire:click="openPlanningChatModal"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Chat planeación
                            </button>
                        @endif
                    @endif
                </div>
            </div>
            @error('selectedCitationSlotKey')
                <p class="px-4 pt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('notification')
                <p class="px-4 pt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('reassignSupervisor')
                <p class="px-4 pt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        @elseif (! $citationReadOnly)
            <div class="flex flex-col gap-3 border-b border-indigo-200/60 bg-indigo-100/50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-indigo-950/50">
                <p class="text-sm font-bold text-slate-900 dark:text-white">Paso {{ $stepNumber }} · {{ $actionTitle }}</p>
                @can('startCoordination', $case)
                    <button type="button" wire:click="startCoordination"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        Iniciar coordinación
                    </button>
                @endcan
            </div>
        @endif

        @error('citationAdvance')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('coordination')
            <p class="px-4 pt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        @if ($showCitationAdvanceValidation && ! $citationReadOnly)
            <div class="mx-4 mt-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-950/40" role="alert">
                <p class="text-sm font-semibold text-amber-950 dark:text-amber-100">No es posible avanzar a diligencia.</p>
                <ul class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
                    @foreach ($citationReadiness as $key => $done)
                        <li class="flex items-center gap-2 {{ $done ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-950 dark:text-amber-100' }}">
                            <span>{{ $done ? '✓' : '✗' }}</span>
                            {{ $requirementLabels[$key] ?? $key }}
                        </li>
                    @endforeach
                </ul>
                <button type="button" wire:click="closeCitationAdvanceValidation" class="mt-3 text-xs font-semibold text-amber-900 underline dark:text-amber-200">Cerrar</button>
            </div>
        @endif

        {{-- Panel del paso activo --}}
        @if ($showCitationStepPanel)
            <div class="space-y-4 px-4 py-4">
                @if ($currentStepKey === 'fo_gj_03' && $case->citation_confirmed_date && $isAssignedLawyer && ! $canGenerateFoGj03)
                    <div class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm dark:border-amber-500/40 dark:bg-amber-950/30">
                        <p class="font-semibold text-amber-950 dark:text-amber-100">Requisitos pendientes para FO-GJ-03:</p>
                        <ul class="mt-1 space-y-0.5">
                            @foreach ($foGj03Checklist as $key => $done)
                                @unless ($done)
                                    <li class="text-amber-950 dark:text-amber-100">· {{ $foGj03Labels[$key] ?? $key }}</li>
                                @endunless
                            @endforeach
                        </ul>
                    </div>
                    @error('fo_gj_03')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif

                @can('viewCitationEvidence', $case)
                    @if ($currentStepKey === 'evidence')
                        @php
                            $citationEvidenceDoc = $case->latestCitationEvidenceDocument();
                            $pendingEvidenceFile = $citationEvidenceFile instanceof TemporaryUploadedFile ? $citationEvidenceFile : null;
                            $pendingEvidenceKb = $pendingEvidenceFile
                                ? max(1, (int) round($pendingEvidenceFile->getSize() / 1024))
                                : null;
                        @endphp
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="border-b border-slate-200 px-4 py-3 dark:border-white/10">
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-dash-muted">Paso 6 · Evidencia</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-white">Carga de evidencia de citación</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Citación firmada o acta de rechazo con testigos (PDF).</p>
                            </div>

                            <div class="space-y-4 p-4">
                                @if ($case->citation_evidence_uploaded_at)
                                    <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/60 p-3.5 dark:border-emerald-500/30 dark:bg-emerald-950/25">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex rounded-md bg-emerald-600/15 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200">
                                                Cargada
                                            </span>
                                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                                {{ $case->citation_evidence_uploaded_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ $case->citation_evidence_type?->label() ?? 'Evidencia de citación' }}
                                        </p>
                                        @if ($citationEvidenceDoc)
                                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-[10px] font-bold text-red-600 ring-1 ring-slate-200 dark:bg-dash-lift dark:text-red-400 dark:ring-white/15">
                                                    PDF
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $citationEvidenceDoc->original_name }}</p>
                                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Documento en el expediente</p>
                                                </div>
                                                <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                                                    <button type="button"
                                                        wire:click="openDocumentPreview({{ $citationEvidenceDoc->id }})"
                                                        class="inline-flex h-8 items-center rounded-lg bg-indigo-600 px-2.5 text-xs font-semibold text-white hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                                                        Ver
                                                    </button>
                                                    <a href="{{ route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $citationEvidenceDoc, 'download' => 1]) }}"
                                                        class="inline-flex h-8 items-center rounded-lg px-2.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-white dark:text-slate-200 dark:ring-white/20 dark:hover:bg-white/10"
                                                        download>
                                                        Descargar
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @elseif (! $case->fo_gj_03_generated_at)
                                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-200 dark:ring-amber-500/30">
                                        Genere primero el FO-GJ-03 para habilitar la carga de evidencia.
                                    </p>
                                @endif

                                @can('uploadCitationEvidence', $case)
                                    <div class="space-y-4" @if ($case->citation_evidence_uploaded_at) aria-label="Reemplazar evidencia" @endif>
                                        @if ($case->citation_evidence_uploaded_at)
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Reemplazar evidencia</p>
                                        @endif

                                        <div>
                                            <p class="mb-2 text-xs font-semibold text-slate-700 dark:text-slate-300">1. Tipo de evidencia</p>
                                            <div class="grid gap-2 sm:grid-cols-2" role="radiogroup" aria-label="Tipo de evidencia">
                                                <button type="button"
                                                    wire:click="$set('citationEvidenceType', 'signed')"
                                                    @class([
                                                        'rounded-xl border px-3 py-3 text-left transition',
                                                        'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-400/40 dark:border-indigo-400 dark:bg-indigo-500/15' => $citationEvidenceType === 'signed',
                                                        'border-slate-200 hover:border-slate-300 dark:border-white/10 dark:hover:border-white/20' => $citationEvidenceType !== 'signed',
                                                    ])>
                                                    <span class="flex items-start gap-2.5">
                                                        <span @class([
                                                            'mt-0.5 inline-flex h-3.5 w-3.5 shrink-0 rounded-full border-2',
                                                            'border-indigo-600 bg-indigo-600 dark:border-indigo-400 dark:bg-indigo-400' => $citationEvidenceType === 'signed',
                                                            'border-slate-300 dark:border-white/25' => $citationEvidenceType !== 'signed',
                                                        ])></span>
                                                        <span>
                                                            <span class="block text-sm font-semibold text-slate-900 dark:text-white">Firmada por el trabajador</span>
                                                            <span class="mt-0.5 block text-[11px] text-slate-500 dark:text-slate-400">FO-GJ-03 con firma</span>
                                                        </span>
                                                    </span>
                                                </button>
                                                <button type="button"
                                                    wire:click="$set('citationEvidenceType', 'refused_witnesses')"
                                                    @class([
                                                        'rounded-xl border px-3 py-3 text-left transition',
                                                        'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-400/40 dark:border-indigo-400 dark:bg-indigo-500/15' => $citationEvidenceType === 'refused_witnesses',
                                                        'border-slate-200 hover:border-slate-300 dark:border-white/10 dark:hover:border-white/20' => $citationEvidenceType !== 'refused_witnesses',
                                                    ])>
                                                    <span class="flex items-start gap-2.5">
                                                        <span @class([
                                                            'mt-0.5 inline-flex h-3.5 w-3.5 shrink-0 rounded-full border-2',
                                                            'border-indigo-600 bg-indigo-600 dark:border-indigo-400 dark:bg-indigo-400' => $citationEvidenceType === 'refused_witnesses',
                                                            'border-slate-300 dark:border-white/25' => $citationEvidenceType !== 'refused_witnesses',
                                                        ])></span>
                                                        <span>
                                                            <span class="block text-sm font-semibold text-slate-900 dark:text-white">Rechazo con testigos</span>
                                                            <span class="mt-0.5 block text-[11px] text-slate-500 dark:text-slate-400">Acta con dos testigos</span>
                                                        </span>
                                                    </span>
                                                </button>
                                            </div>
                                            @error('citationEvidenceType')
                                                <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <p class="mb-2 text-xs font-semibold text-slate-700 dark:text-slate-300">2. Archivo PDF</p>
                                            <div
                                                x-data="{ dragging: false }"
                                                x-on:dragover.prevent="dragging = true"
                                                x-on:dragleave.prevent="dragging = false"
                                                x-on:drop.prevent="
                                                    dragging = false;
                                                    const file = $event.dataTransfer.files?.[0];
                                                    if (file) $wire.upload('citationEvidenceFile', file);
                                                "
                                                @class([
                                                    'rounded-xl border border-dashed transition',
                                                    'border-indigo-400 bg-indigo-50/50 dark:border-indigo-400/60 dark:bg-indigo-500/10' => (bool) $pendingEvidenceFile,
                                                    'border-slate-300 bg-slate-50 dark:border-white/15 dark:bg-white/[0.03]' => ! $pendingEvidenceFile,
                                                ])
                                                :class="dragging ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/15' : ''"
                                            >
                                                @if ($pendingEvidenceFile)
                                                    <div class="flex flex-wrap items-center gap-3 px-3 py-3">
                                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-[10px] font-bold text-red-600 ring-1 ring-slate-200 dark:bg-dash-lift dark:text-red-400 dark:ring-white/15">
                                                            PDF
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <p class="truncate text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $pendingEvidenceFile->getClientOriginalName() }}</p>
                                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ number_format($pendingEvidenceKb) }} KB · listo para cargar</p>
                                                        </div>
                                                        <button type="button"
                                                            wire:click="$set('citationEvidenceFile', null)"
                                                            class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">
                                                            Quitar
                                                        </button>
                                                    </div>
                                                @else
                                                    <label for="citation-evidence-file-{{ $case->id }}" class="flex cursor-pointer flex-col items-center px-4 py-7 text-center">
                                                        <span class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-white text-[10px] font-bold text-indigo-600 ring-1 ring-slate-200 dark:bg-dash-lift dark:text-indigo-300 dark:ring-white/15">PDF</span>
                                                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Arrastre el PDF aquí</span>
                                                        <span class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">o haga clic para elegir · solo PDF</span>
                                                    </label>
                                                @endif
                                                <input
                                                    id="citation-evidence-file-{{ $case->id }}"
                                                    type="file"
                                                    wire:model="citationEvidenceFile"
                                                    accept="application/pdf"
                                                    class="sr-only"
                                                >
                                            </div>
                                            <div wire:loading wire:target="citationEvidenceFile" class="mt-1.5 text-[11px] text-indigo-600 dark:text-indigo-300">
                                                Preparando archivo…
                                            </div>
                                            @error('citationEvidenceFile')
                                                <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3 dark:border-white/10">
                                            <p class="mr-auto text-[11px] text-slate-500 dark:text-slate-400">Se adjuntará al expediente · Etapa B</p>
                                            <button type="button"
                                                wire:click="uploadCitationEvidence"
                                                wire:loading.attr="disabled"
                                                wire:target="uploadCitationEvidence"
                                                class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                                                <span wire:loading.remove wire:target="uploadCitationEvidence">Cargar evidencia</span>
                                                <span wire:loading wire:target="uploadCitationEvidence">Subiendo…</span>
                                            </button>
                                        </div>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    @endif
                @endcan
            </div>
        @endif
    </div>
@endif
