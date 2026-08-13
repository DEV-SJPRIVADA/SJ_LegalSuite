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
    $canUploadFoGj04Signed = auth()->user()->can('uploadFoGj04Signed', $case);
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
    $canUploadFoGj54Evidence = auth()->user()->can('uploadFoGj54Evidence', $case);
    $foGj54DraftCompleted = $case->fo_gj_54_draft_completed_at !== null;
    $isOperationalReschedule = $case->isOperationalReschedulePending();
    $foGj54OperationalGenerated = $isOperationalReschedule && $case->fo_gj_54_generated_at !== null;
    $foGj54OperationalAwaitingEvidence = $foGj54OperationalGenerated && $case->fo_gj_54_evidence_uploaded_at === null;
    $canEditComiteDraft = auth()->user()->can('editComiteDraft', $case);
    $canPreviewComite = auth()->user()->can('previewComite', $case);
    $canGenerateComite = auth()->user()->can('generateComite', $case);
    $comiteDraftCompleted = $case->comite_draft_completed_at !== null;
    $comiteDoc = $case->latestComiteActaDocument();
    $isComitePanel = $case->current_status === CaseStatus::COMITE_DISCIPLINARIO;
    $diligenceReadOnly = $diligenceReadOnly ?? false;
    $comiteActaGenerated = $case->comite_generated_at !== null || $comiteDoc !== null;
    $canAdvanceToDecision = ($case->current_status === CaseStatus::DILIGENCIA && $workerAttended)
        || ($isComitePanel && $comiteActaGenerated);
    $justificationStage = $case->activeJustificationStage();
    $isAssignedLawyer = (int) $case->assigned_lawyer_id === (int) auth()->id();
    $panelTitle = match (true) {
        $isComitePanel => 'Etapa C · Comité disciplinario',
        $isOperationalReschedule => 'Etapa C · Reprogramación operativa (FO-GJ-54)',
        $case->current_status === CaseStatus::JUSTIFICACION_PENDIENTE => 'Etapa C · Justificación de inasistencia',
        default => 'Etapa C · Diligencia disciplinaria (FO-GJ-04)',
    };
@endphp

@if ($showsDiligenceStagePanel ?? $isDiligenciaActive ?? false)
    <div class="overflow-hidden rounded-xl border border-teal-200 bg-white shadow-sm ring-1 ring-teal-100 dark:border-teal-400/25 dark:bg-teal-950/15 dark:ring-teal-500/20 dark:shadow-dash-card {{ ($insideStageModal ?? false) ? '' : 'md:col-span-2 xl:col-span-3' }}"
        data-stage-block="c">

        <div class="flex flex-col gap-3 border-b border-teal-200/80 bg-teal-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-teal-950/35">
            <div class="min-w-0 shrink-0">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-teal-900 dark:text-teal-200">
                    {{ $panelTitle }}
                </h4>
                <p class="mt-0.5 text-[11px] text-slate-600 dark:text-slate-400">
                    Paso {{ $stepNumber }} de {{ $totalSteps }}
                    @if ($diligenceReadOnly)
                        · <strong>Completada · Solo lectura</strong>
                    @elseif ($attendanceRegistered)
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

            @if ($canAdvanceToDecision && ! $diligenceReadOnly)
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
                @if ($workerAttended && $currentStepKey === 'acta' && $canUploadFoGj04Signed && ! $case->fo_gj_04_generated_at)
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Firma digital en pantalla o imprima la vista previa y cargue el PDF escaneado con la firma del trabajador.
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if ($diligenceReadOnly)
                    {{-- Solo lectura: sin acciones --}}
                @elseif ($isOperationalReschedule && $isAssignedLawyer)
                    @if (! $foGj54OperationalGenerated && $canEditFoGj54Draft)
                        <button type="button" wire:click="openFoGj54DraftModal"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            {{ $foGj54DraftCompleted ? 'Editar FO-GJ-54' : 'Diligenciar FO-GJ-54' }}
                        </button>
                    @endif
                    @if ($canPreviewFoGj54 && $foGj54DraftCompleted)
                        <button type="button" wire:click="openFoGj54PdfPreview"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                            Vista previa / descargar FO-GJ-54
                        </button>
                    @endif
                    @if ($canGenerateFoGj54)
                        <button type="button" wire:click="generateFoGj54AndAcceptJustification"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Generar FO-GJ-54
                        </button>
                    @endif
                    @if ($foGj54OperationalAwaitingEvidence && $canUploadFoGj54Evidence)
                        <input type="file" id="fo-gj-54-evidence-{{ $case->id }}" class="sr-only" accept="application/pdf"
                            wire:model.live="foGj54EvidenceFile">
                        <label for="fo-gj-54-evidence-{{ $case->id }}"
                            class="inline-flex cursor-pointer items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                            Cargar evidencia recibido (PDF)
                        </label>
                        @if ($foGj54EvidenceFile)
                            <button type="button" wire:click="uploadFoGj54ReceiptEvidence"
                                class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                                Confirmar y volver a diligencia
                            </button>
                        @endif
                        @error('foGj54EvidenceFile')
                            <p class="w-full basis-full text-right text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                @elseif (! $attendanceRegistered && $canRegisterAttendance && $isAssignedLawyer)
                    @php
                        $foGj54OperationalDraftPending = $foGj54DraftCompleted
                            && ($case->fo_gj_54_payload['mode'] ?? null) === \App\Services\Disciplinary\FoGj54DraftService::MODE_OPERATIONAL
                            && $case->fo_gj_54_generated_at === null;
                    @endphp
                    @if ($foGj54OperationalDraftPending)
                        @if ($canEditFoGj54Draft)
                            <button type="button" wire:click="openFoGj54DraftModal"
                                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                                Editar FO-GJ-54
                            </button>
                        @endif
                        @if ($canPreviewFoGj54)
                            <button type="button" wire:click="openFoGj54PdfPreview"
                                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                                Vista previa / descargar FO-GJ-54
                            </button>
                        @endif
                        @if ($canGenerateFoGj54)
                            <button type="button" wire:click="generateFoGj54AndAcceptJustification"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Generar FO-GJ-54
                            </button>
                        @endif
                    @else
                        <button type="button" wire:click="requestRegisterDiligenceAttendance('attended')"
                            class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Asistió
                        </button>
                        <button type="button" wire:click="requestRegisterDiligenceAttendance('absent')"
                            class="inline-flex items-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                            No asistió
                        </button>
                        @if ($canEditFoGj54Draft)
                            <button type="button" wire:click="openFoGj54DraftModal"
                                class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-slate-100 dark:ring-white/20 dark:hover:bg-white/15">
                                Reprogramar diligencia
                            </button>
                        @endif
                    @endif
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
                    @if ($canUploadFoGj04Signed)
                        <input type="file"
                            id="fo-gj-04-signed-upload-{{ $case->id }}"
                            class="sr-only"
                            accept="application/pdf"
                            wire:model.live="foGj04SignedUploadFile">
                        <label for="fo-gj-04-signed-upload-{{ $case->id }}"
                            class="inline-flex cursor-pointer items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                            Cargar acta firmada (PDF)
                        </label>
                    @endif
                    @if ($canGenerateFoGj04)
                        <button type="button" wire:click="generateFoGj04"
                            class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                            Generar y guardar
                        </button>
                    @endif
                    @error('foGj04SignedUploadFile')
                        <p class="w-full basis-full text-right text-xs text-red-600">{{ $message }}</p>
                    @enderror
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
            @if ($isOperationalReschedule)
                <div class="rounded-lg border border-indigo-200 bg-indigo-50/80 px-4 py-3 text-sm dark:border-indigo-500/30 dark:bg-indigo-950/25">
                    <p class="font-semibold text-indigo-950 dark:text-indigo-100">Reprogramación operativa (FO-GJ-03 se conserva)</p>
                    <p class="mt-1 text-indigo-900/90 dark:text-indigo-100/85">
                        1) Defina la nueva fecha (usted o planeación) ·
                        2) Genere y descargue el FO-GJ-54 ·
                        3) Notifique al trabajador ·
                        4) Cargue el PDF firmado como evidencia de recibido ·
                        5) El expediente vuelve a Etapa C para registrar asistencia.
                    </p>
                    @if (! $case->citation_confirmed_date)
                        <p class="mt-2 text-amber-800 dark:text-amber-200">
                            Pendiente: confirmar nueva fecha con planeación (chat) o diligenciarla en el FO-GJ-54.
                        </p>
                    @endif
                    @if ($foGj54OperationalAwaitingEvidence)
                        <p class="mt-2 font-medium text-indigo-950 dark:text-indigo-100">
                            FO-GJ-54 generado. Cargue la evidencia de recibido para volver a diligencia.
                        </p>
                    @endif
                </div>
            @elseif (! $attendanceRegistered && $currentStepKey === 'attendance')
                @php
                    $foGj54OperationalDraftPendingHint = $foGj54DraftCompleted
                        && ($case->fo_gj_54_payload['mode'] ?? null) === \App\Services\Disciplinary\FoGj54DraftService::MODE_OPERATIONAL
                        && $case->fo_gj_54_generated_at === null;
                @endphp
                @if ($foGj54OperationalDraftPendingHint)
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50/80 px-4 py-3 text-sm dark:border-indigo-500/30 dark:bg-indigo-950/25">
                        <p class="font-semibold text-indigo-950 dark:text-indigo-100">FO-GJ-54 diligenciado</p>
                        <p class="mt-1 text-indigo-900/90 dark:text-indigo-100/85">
                            Use <strong>Generar FO-GJ-54</strong> para incorporar el documento al expediente y continuar la reprogramación.
                            Luego notifique al trabajador y cargue la evidencia de recibido.
                        </p>
                    </div>
                @else
                    <div class="rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm dark:border-amber-500/30 dark:bg-amber-950/25">
                        <p class="font-semibold text-amber-950 dark:text-amber-100">Primer paso obligatorio</p>
                        <p class="mt-1 text-amber-900/90 dark:text-amber-100/85">
                            Registre si el trabajador <strong>asistió</strong> o <strong>no asistió</strong> a la diligencia programada.
                            Esta decisión queda guardada en el expediente y <strong>no puede modificarse</strong> después.
                        </p>
                        <p class="mt-2 text-amber-900/90 dark:text-amber-100/85">
                            Si por fuerza mayor debe aplazarse <strong>antes</strong> de la comparecencia,
                            use <strong>Reprogramar diligencia</strong> (FO-GJ-54). No regenera FO-GJ-03.
                        </p>
                    </div>
                @endif
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
                        @if ($comiteActaGenerated)
                            El acta de comité está en el expediente. Use <strong>Siguiente etapa</strong> para pasar al comunicado de decisión (etapa D).
                        @else
                            Diligencie el acta del comité, previsualice el PDF y genérelo para incorporarlo al expediente.
                        @endif
                    </p>
                </div>
            @endif

            @if ($isComitePanel && $currentStepKey === 'decision')
                <div class="rounded-lg border border-teal-200 bg-teal-50/70 px-4 py-3 text-sm dark:border-teal-500/30 dark:bg-teal-950/30">
                    <p class="font-semibold text-teal-950 dark:text-teal-100">Avance a decisión</p>
                    <p class="mt-1 text-teal-900/90 dark:text-teal-100/85">
                        Use <strong>Siguiente etapa</strong> para pasar al comunicado de decisión de sanción o cierre del proceso (etapa D).
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
@endif
