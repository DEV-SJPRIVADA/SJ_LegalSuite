@php
    use App\Enums\Disciplinary\CaseStatus;
    use App\Support\Disciplinary\CitationStageProgress;
    use App\Services\Disciplinary\DisciplinaryCitationWorkflowService;

    $isCitacion = $case->current_status === CaseStatus::CITACION_PROGRAMADA;
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
    $showChatPanel = $coordinationChatAvailable && ($coordinationChatVisible ?? true);
    $canGenerateFoGj03 = auth()->user()->can('generateFoGj03', $case);
    $isAssignedLawyer = (int) $case->assigned_lawyer_id === (int) auth()->id();

    $diligenceSlotDisplay = $diligenceSlotDisplay ?? ['date' => '—', 'time' => '—', 'confirmed' => false];
    $notificationSlotDisplay = $notificationSlotDisplay ?? ['date' => '—', 'shift' => '—', 'zone' => '—', 'supervisor' => '—', 'completed' => false];
    $useDiligenceDateActionBar = $case->hasCoordinationStarted();
    $canSelectCitationSlot = ! $coordinationIsClosed
        && ! ($diligenceSlotDisplay['confirmed'] ?? false)
        && auth()->user()->can('postAgendaLawyer', $case);
    $showConfirmCitationSlot = $canSelectCitationSlot
        && ($selectedCitationSlotKey ?? '') !== ''
        && in_array($currentStepKey, ['planning_slots', 'definitive_date'], true);

    $showCitationStepPanel = ($currentStepKey === 'fo_gj_03' && $case->citation_confirmed_date && $isAssignedLawyer && ! $canGenerateFoGj03)
        || ($currentStepKey === 'evidence' && auth()->user()->can('viewCitationEvidence', $case));
@endphp

@if ($isCitacion)
    <div class="md:col-span-2 xl:col-span-3 overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-sm ring-1 ring-indigo-100 dark:border-indigo-400/25 dark:bg-indigo-950/15 dark:ring-indigo-500/20 dark:shadow-dash-card">

        {{-- Cabecera: título + stepper + avanzar etapa --}}
        <div class="flex flex-col gap-3 border-b border-indigo-200/80 bg-indigo-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-indigo-950/35">
            <div class="min-w-0 shrink-0">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-indigo-900 dark:text-indigo-200">
                    Etapa B · Citación a diligencia (FO-GJ-03)
                </h4>
                <p class="mt-0.5 text-[11px] text-slate-600 dark:text-slate-400">
                    Paso {{ $stepNumber }} de {{ $totalSteps }}
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

            @can('transition', $case)
                <button type="button" wire:click="requestAdvanceFromCitacion"
                    class="shrink-0 inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:bg-white/10 dark:text-indigo-100 dark:ring-indigo-400/40 dark:hover:bg-white/15">
                    Siguiente etapa →
                </button>
            @endcan
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
                            {{ $notificationSlotDisplay['supervisor'] }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
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

                    @if ($currentStepKey === 'fo_gj_03' && $case->citation_confirmed_date && $isAssignedLawyer)
                        <a href="{{ route('disciplinary.cases.fo-gj-03.pdf', $case) }}" target="_blank" rel="noopener"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:bg-white/10 dark:text-indigo-100 dark:ring-indigo-400/40">
                            Vista previa PDF
                        </a>
                        @can('generateFoGj03', $case)
                            <button type="button" wire:click="generateFoGj03"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Generar y guardar
                            </button>
                        @endcan
                    @endif

                    @if ($coordinationChatAvailable && $isAssignedLawyer)
                        @if ($showChatPanel)
                            <button type="button" wire:click="hideCoordinationChat"
                                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-slate-200 dark:ring-white/20">
                                Ocultar chat
                            </button>
                        @else
                            <button type="button" wire:click="showCoordinationChat"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Mostrar chat
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
        @else
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

        @if ($showCitationAdvanceValidation)
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

        {{-- Zona de trabajo --}}
        <div class="flex flex-col" x-data="window.sjAgendaAttachmentLightbox()" x-on:open-agenda-lightbox="openAgendaAttachment($event.detail)">
            @if ($showChatPanel)
                <div class="flex min-h-[14rem] max-h-[22rem] flex-col bg-white dark:bg-dash-lift/40"
                    wire:poll.visible.10s>
                    <div class="flex-1 overflow-y-auto px-4 py-3">
                        @if ($coordinationIsClosed)
                            <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">Coordinación cerrada — historial de solo lectura.</p>
                        @else
                            <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">
                                Planeación responde desde <strong class="font-semibold text-slate-700 dark:text-slate-300">Coordinaciones</strong>.
                            </p>
                        @endif

                        @if ($case->agendaThread && $case->agendaThread->messages->isNotEmpty())
                            <ul class="space-y-2">
                                @foreach ($case->agendaThread->messages as $msg)
                                    <x-disciplinary.agenda-message
                                        :message="$msg"
                                        :case="$case"
                                        :selectable-slots="$canSelectCitationSlot"
                                        wire:key="agenda-msg-{{ $msg->id }}" />
                                @endforeach
                            </ul>
                        @else
                            <p class="py-8 text-center text-sm italic text-slate-400 dark:text-slate-500">Sin mensajes aún. Escriba abajo para iniciar el diálogo.</p>
                        @endif
                    </div>

                    @can('postAgendaLawyer', $case)
                        @if (! $coordinationIsClosed)
                            <x-disciplinary.agenda-chat-composer
                                body-model="agendaLawyerBody"
                                uploads-property="agendaLawyerUploads"
                                send-action="postAgendaLawyer"
                                remove-upload-method="removeAgendaLawyerUploadAt"
                                :uploads="$agendaLawyerUploads ?? []"
                                placeholder="Escriba un mensaje para Planeación…"
                                :input-id="'agenda-lawyer-body-'.$case->id"
                                error-field="agendaLawyerBody" />
                        @endif
                    @endcan

                </div>
            @elseif ($case->hasCoordinationStarted() && ! $showChatPanel)
                <div class="border-b border-slate-200 px-4 py-2 dark:border-white/10" x-data="{ historyOpen: {{ $coordinationIsClosed ? 'true' : 'false' }} }">
                    @if ($coordinationIsClosed)
                        <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">Coordinación finalizada al avanzar de etapa — historial de solo lectura.</p>
                    @elseif ($coordinationChatAvailable && $isAssignedLawyer)
                        <button type="button" wire:click="showCoordinationChat"
                            class="mb-2 text-xs font-semibold text-indigo-700 underline dark:text-indigo-300">
                            Mostrar chat con Planeación
                        </button>
                    @endif
                    <button type="button" @click="historyOpen = !historyOpen"
                        class="text-xs font-semibold text-indigo-700 underline dark:text-indigo-300">
                        <span x-text="historyOpen ? 'Ocultar historial de coordinación' : 'Ver historial de coordinación ({{ $case->agendaThread?->messages->count() ?? 0 }} mensajes)'"></span>
                    </button>
                    <div x-show="historyOpen" x-cloak class="mt-2 max-h-48 overflow-y-auto" wire:poll.visible.15s>
                        @if ($case->agendaThread && $case->agendaThread->messages->isNotEmpty())
                            <ul class="space-y-2">
                                @foreach ($case->agendaThread->messages as $msg)
                                    <x-disciplinary.agenda-message :message="$msg" :case="$case" wire:key="agenda-msg-collapsed-{{ $msg->id }}" />
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Panel del paso activo (solo si hay formulario o aviso técnico; sin texto duplicado bajo el chat) --}}
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
                        @php $citationEvidenceDoc = $case->latestCitationEvidenceDocument(); @endphp
                        <div class="rounded-lg border border-slate-200 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                            <p class="text-xs text-slate-600 dark:text-slate-400">Citación firmada o acta de rechazo con testigos (PDF).</p>
                            @if ($case->citation_evidence_uploaded_at)
                                <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                    <div><dt class="text-xs text-slate-500">Tipo</dt><dd class="font-medium dark:text-white">{{ $case->citation_evidence_type?->label() ?? '—' }}</dd></div>
                                    <div><dt class="text-xs text-slate-500">Cargada</dt><dd class="font-medium dark:text-white">{{ $case->citation_evidence_uploaded_at->format('d/m/Y H:i') }}</dd></div>
                                    @if ($citationEvidenceDoc)
                                        <div class="sm:col-span-2">
                                            <a href="{{ route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $citationEvidenceDoc, 'download' => 1]) }}"
                                                class="text-sm font-semibold text-indigo-700 underline dark:text-indigo-300" target="_blank" rel="noopener">
                                                {{ $citationEvidenceDoc->original_name }}
                                            </a>
                                        </div>
                                    @endif
                                </dl>
                            @elseif (! $case->fo_gj_03_generated_at)
                                <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">Genere primero el FO-GJ-03.</p>
                            @endif
                            @can('uploadCitationEvidence', $case)
                                <div class="mt-3 space-y-2 border-t border-slate-200 pt-3 dark:border-white/10">
                                    <select wire:model="citationEvidenceType" class="w-full max-w-md rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                        <option value="">— Tipo de evidencia —</option>
                                        <option value="signed">Citación firmada por el trabajador</option>
                                        <option value="refused_witnesses">Rechazo con firma de jefe y dos testigos</option>
                                    </select>
                                    @error('citationEvidenceType')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                    <input type="file" wire:model="citationEvidenceFile" accept="application/pdf" class="text-sm">
                                    @error('citationEvidenceFile')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                    <button type="button" wire:click="uploadCitationEvidence"
                                        class="inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                        Cargar evidencia PDF
                                    </button>
                                </div>
                            @endcan
                        </div>
                    @endif
                @endcan
            </div>
            @endif

            <x-disciplinary.agenda-attachment-lightbox-modal />
        </div>
    </div>

    @include('livewire.disciplinary.cases.partials.stage-b-citation-modals', [
        'case' => $case,
        'citationAdvanceTargetLabel' => $citationAdvanceTargetLabel ?? null,
        'supervisorCandidates' => $supervisorCandidates ?? collect(),
    ])
@endif
