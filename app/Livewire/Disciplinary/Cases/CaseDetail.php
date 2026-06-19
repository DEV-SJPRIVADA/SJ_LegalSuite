<?php

namespace App\Livewire\Disciplinary\Cases;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\CitationEvidenceType;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Exceptions\Disciplinary\CaseAlreadyClaimedException;
use App\Exceptions\Disciplinary\InvalidStateTransitionException;
use App\Models\Disciplinary\DisciplinaryAction;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryStage;
use App\Models\OrganizationalArea;
use App\Models\User;
use App\Services\Disciplinary\CitationNotificationSigningService;
use App\Services\Disciplinary\ComiteActaService;
use App\Services\Disciplinary\ComiteDraftService;
use App\Services\Disciplinary\DiligenceAttendanceService;
use App\Services\Disciplinary\DisciplinaryAgendaThreadService;
use App\Services\Disciplinary\DisciplinaryAuditService;
use App\Services\Disciplinary\DisciplinaryCaseService;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
use App\Services\Disciplinary\DisciplinaryCitationWorkflowService;
use App\Services\Disciplinary\DisciplinaryDiligenceWorkflowService;
use App\Services\Disciplinary\DisciplinaryDocumentService;
use App\Services\Disciplinary\DisciplinaryWorkflowService;
use App\Services\Disciplinary\FoGj03CitationService;
use App\Services\Disciplinary\FoGj03DraftService;
use App\Services\Disciplinary\FoGj04DiligenceActaService;
use App\Services\Disciplinary\FoGj04DraftService;
use App\Services\Disciplinary\FoGj44ConstanciaService;
use App\Services\Disciplinary\FoGj44DraftService;
use App\Services\Disciplinary\FoGj54DraftService;
use App\Services\Disciplinary\FoGj54ReprogramacionService;
use App\Support\Disciplinary\CaseOverviewStageStack;
use App\Support\Disciplinary\CitationStageProgress;
use App\Support\Disciplinary\DiligenceStageProgress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Detalle del caso')]
class CaseDetail extends Component
{
    use WithFileUploads;

    public DisciplinaryCase $case;

    public string $activeTab = 'overview';

    /** Confirmación A → B (citación) desde etapa Informe. */
    public bool $showAdvanceStageConfirm = false;

    /** Confirmación archivar desde etapa Informe. */
    public bool $showArchiveConfirm = false;

    /* Modal programación de fechas (Planeación / Jurídico) */
    public bool $showScheduleModal = false;

    public ?int $scheduleStageId = null;

    public string $scheduleAt = '';

    public string $scheduleDeadline = '';

    public string $scheduleNote = '';

    public ?int $assignedLawyerId = null;

    /** Confirmación antes de persistir titular (asignar / cambiar / quitar). */
    public bool $showLawyerConfirmModal = false;

    public ?int $lawyerConfirmPendingId = null;

    /** assign | change | clear */
    public string $lawyerConfirmKind = '';

    public string $lawyerConfirmTargetName = '';

    /** Confirmación al tomar caso de bandeja INFORME. */
    public bool $showClaimConfirm = false;

    /** Coordinación citación FO-GJ-03 — solicitud abogado ↔ planeación */
    public string $agendaLawyerBody = '';

    /** @var array<int, mixed> */
    public array $agendaLawyerUploads = [];

    /** Respuesta planeación (campo aparte para no chocar con admin que ve ambos formularios) */
    public string $agendaPlanningBody = '';

    /** @var array<int, array{date: string, time: string, notes: string}> */
    public array $planningSlots = [
        ['date' => '', 'time' => '', 'notes' => ''],
    ];

    /** @var array<int, mixed> */
    public array $agendaPlanningUploads = [];

    /** Clave compuesta messageId-slotIndex para selección visual de fecha. */
    public string $selectedCitationSlotKey = '';

    public bool $showCitationAdvanceValidation = false;

    public bool $showCitationAdvanceConfirm = false;

    public bool $showDiligenceAdvanceConfirm = false;

    /** Abogado muestra u oculta el panel de chat (no cierra el hilo; cierre definitivo al cambiar de etapa). */
    public bool $coordinationChatVisible = true;

    public string $citationEvidenceType = '';

    public $citationEvidenceFile = null;

    public bool $showReassignSupervisorModal = false;

    public ?int $reassignSupervisorUserId = null;

    public string $reassignSupervisorReason = '';

    /** Vista previa del PDF FO-GJ-51 ya incorporado al expediente. */
    public ?int $fo51PdfPreviewDocumentId = null;

    public bool $showFoGj03DraftModal = false;

    public string $foGj03HearingTime = '';

    public string $foGj03Modality = 'presencial';

    public string $foGj03VirtualLink = '';

    public string $foGj03BreachDate = '';

    public string $foGj03ChargesDescription = '';

    public string $foGj03Article66Numerals = '';

    public string $foGj03Article68Numerals = '';

    public string $foGj03Article76Numerals = '';

    public string $foGj03InformeReportDate = '';

    public bool $showFoGj03PdfPreviewModal = false;

    public bool $showFoGj04DraftModal = false;

    public string $foGj04WorkerManifestation = '';

    public string $foGj04OpeningTime = '';

    public string $foGj04ClosingTime = '';

    /** @var array<int, array{question: string, answer: string}> */
    public array $foGj04Questions = [];

    public bool $showFoGj04PdfPreviewModal = false;

    public bool $showDiligenceAttendanceConfirm = false;

    public string $diligenceAttendancePending = '';

    public bool $showFoGj44DraftModal = false;

    public string $foGj44SignTime = '';

    public string $foGj44SignDay = '';

    public string $foGj44SignMonth = '';

    public string $foGj44SignYearSuffix = '';

    public string $foGj44Witness1Name = '';

    public string $foGj44Witness1Cargo = '';

    public string $foGj44Witness1Date = '';

    public string $foGj44Witness2Name = '';

    public string $foGj44Witness2Cargo = '';

    public string $foGj44Witness2Date = '';

    public bool $showFoGj44PdfPreviewModal = false;

    public bool $showFoGj54DraftModal = false;

    public string $foGj54ClientSite = '';

    public string $foGj54ShiftStart = '';

    public string $foGj54ShiftEnd = '';

    public string $foGj54NewHearingDate = '';

    public string $foGj54NewHearingTime = '';

    public string $foGj54NewHearingPlace = '';

    public bool $showFoGj54PdfPreviewModal = false;

    public bool $showFoGj04SignaturePadModal = false;

    public ?string $foGj04WorkerSignatureDataUri = null;

    public bool $showJustificationRejectConfirm = false;

    public string $justificationRejectNote = '';

    public bool $showComiteDraftModal = false;

    public string $comiteDecisionNarrative = '';

    /** @var array<int, array{name: string, cargo: string, signature_data_uri: ?string}> */
    public array $comiteAttendees = [];

    public bool $showComitePdfPreviewModal = false;

    public ?int $comiteSignatureAttendeeIndex = null;

    public ?string $comiteSignaturePendingDataUri = null;

    public ?int $documentPreviewId = null;

    public function mount(DisciplinaryCase $case): void
    {
        Gate::authorize('view', $case);
        $this->case = $case;
        $this->assignedLawyerId = $case->assigned_lawyer_id;

        if (auth()->user()->isDisciplinaryProgramador()) {
            $this->activeTab = 'timeline';
        }
    }

    #[On('agenda-thread-refresh')]
    public function refreshAgendaThreadFromBroadcast(): void
    {
        $this->syncCaseFromDb();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openClaimConfirm(): void
    {
        Gate::authorize('claim', $this->case);
        $this->showClaimConfirm = true;
    }

    public function cancelClaimConfirm(): void
    {
        $this->showClaimConfirm = false;
    }

    public function confirmClaimCase(DisciplinaryCaseService $cases): void
    {
        Gate::authorize('claim', $this->case);
        if (! $this->showClaimConfirm) {
            return;
        }

        try {
            $this->case = $cases->claimByLawyer($this->case->fresh(), auth()->user());
        } catch (CaseAlreadyClaimedException) {
            $this->showClaimConfirm = false;
            $this->case = $this->case->fresh();
            session()->flash('error', 'Otro abogado ya tomó este expediente.');

            return;
        }

        $this->showClaimConfirm = false;
        $this->assignedLawyerId = $this->case->assigned_lawyer_id;
        session()->flash('success', 'Expediente asignado. Ya puede gestionarlo con normalidad.');
    }

    public function openAdvanceStageConfirm(): void
    {
        Gate::authorize('transition', $this->case);
        $this->ensureInformeStageForTransition();
        $this->showArchiveConfirm = false;
        $this->showAdvanceStageConfirm = true;
    }

    public function closeAdvanceStageConfirm(): void
    {
        $this->showAdvanceStageConfirm = false;
    }

    public function confirmAdvanceStage(DisciplinaryWorkflowService $workflow): void
    {
        Gate::authorize('transition', $this->case);
        $this->ensureInformeStageForTransition();

        try {
            $this->applyCaseTransition(
                $workflow,
                CaseStatus::CITACION_PROGRAMADA,
                'Avance a etapa B (citación) por decisión del titular.',
            );
            $this->showAdvanceStageConfirm = false;
            session()->flash('success', 'El caso pasó a etapa B: citación a diligencia disciplinaria.');
        } catch (InvalidStateTransitionException $e) {
            $this->addError('advanceStage', $e->getMessage());
        }
    }

    public function openArchiveConfirm(): void
    {
        Gate::authorize('transition', $this->case);
        $this->ensureInformeStageForTransition();
        $this->showAdvanceStageConfirm = false;
        $this->showArchiveConfirm = true;
    }

    public function closeArchiveConfirm(): void
    {
        $this->showArchiveConfirm = false;
    }

    public function confirmArchive(DisciplinaryWorkflowService $workflow): void
    {
        Gate::authorize('transition', $this->case);
        $this->ensureInformeStageForTransition();

        try {
            $this->applyCaseTransition(
                $workflow,
                CaseStatus::ARCHIVADO,
                'Expediente archivado desde etapa de informe.',
            );
            $this->showArchiveConfirm = false;
            session()->flash('success', 'El expediente fue archivado.');
        } catch (InvalidStateTransitionException $e) {
            $this->addError('archiveCase', $e->getMessage());
        }
    }

    public function openScheduleStage(int $stageId): void
    {
        Gate::authorize('assignDate', $this->case);
        $stage = $this->case->stages()->findOrFail($stageId);
        $this->scheduleStageId = $stage->id;
        $this->scheduleAt = $stage->scheduled_at?->format('Y-m-d\TH:i') ?? '';
        $this->scheduleDeadline = $stage->deadline_at?->format('Y-m-d') ?? '';
        $this->scheduleNote = '';
        $this->resetErrorBag();
        $this->showScheduleModal = true;
    }

    public function closeScheduleModal(): void
    {
        $this->showScheduleModal = false;
    }

    public function saveSchedule(DisciplinaryWorkflowService $workflow): void
    {
        Gate::authorize('assignDate', $this->case);

        $this->validate([
            'scheduleAt' => ['nullable', 'date'],
            'scheduleDeadline' => ['nullable', 'date'],
            'scheduleNote' => ['nullable', 'string', 'max:1000'],
        ]);

        $stage = DisciplinaryStage::query()
            ->where('disciplinary_case_id', $this->case->id)
            ->findOrFail($this->scheduleStageId);

        $workflow->updateStageSchedule(
            $this->case->fresh(),
            $stage,
            auth()->user(),
            $this->scheduleAt !== '' ? Carbon::parse($this->scheduleAt) : null,
            $this->scheduleDeadline !== '' ? Carbon::parse($this->scheduleDeadline)->startOfDay() : null,
            $this->scheduleNote !== '' ? $this->scheduleNote : null,
        );

        $this->case = $this->case->fresh();
        $this->showScheduleModal = false;
        session()->flash('success', 'Fechas de la etapa actualizadas.');
    }

    public function onLawyerSelectChanged(): void
    {
        Gate::authorize('assign', $this->case);

        $newId = $this->normalizeLawyerId($this->assignedLawyerId);
        $currentId = $this->normalizeLawyerId($this->case->assigned_lawyer_id);

        if ($newId === $currentId) {
            return;
        }

        $this->lawyerConfirmPendingId = $newId;
        if ($newId === null) {
            $this->lawyerConfirmKind = 'clear';
            $this->lawyerConfirmTargetName = '';
        } elseif ($currentId === null) {
            $this->lawyerConfirmKind = 'assign';
            $lawyer = User::query()->find($newId);
            $this->lawyerConfirmTargetName = $lawyer?->name ?? '';
        } else {
            $this->lawyerConfirmKind = 'change';
            $lawyer = User::query()->find($newId);
            $this->lawyerConfirmTargetName = $lawyer?->name ?? '';
        }

        $this->showLawyerConfirmModal = true;
        $this->assignedLawyerId = $currentId;
    }

    public function confirmLawyerAssignment(DisciplinaryCaseService $cases): void
    {
        Gate::authorize('assign', $this->case);
        if (! $this->showLawyerConfirmModal) {
            return;
        }

        $pending = $this->lawyerConfirmPendingId;
        $this->showLawyerConfirmModal = false;
        $this->lawyerConfirmPendingId = null;
        $this->lawyerConfirmKind = '';
        $this->lawyerConfirmTargetName = '';

        $this->assignedLawyerId = $pending;
        $this->saveLawyerAssignment($cases);
    }

    public function cancelLawyerAssignment(): void
    {
        $this->showLawyerConfirmModal = false;
        $this->lawyerConfirmPendingId = null;
        $this->lawyerConfirmKind = '';
        $this->lawyerConfirmTargetName = '';
    }

    public function saveLawyerAssignment(DisciplinaryCaseService $cases): void
    {
        Gate::authorize('assign', $this->case);

        $this->validate([
            'assignedLawyerId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $newLawyerId = $this->normalizeLawyerId($this->assignedLawyerId);
        $currentLawyerId = $this->normalizeLawyerId($this->case->assigned_lawyer_id);
        if ($newLawyerId === $currentLawyerId) {
            return;
        }

        if ($newLawyerId !== null) {
            $lawyer = User::query()->find($newLawyerId);
            if (! $lawyer || ! $lawyer->hasRole('abogado')) {
                $this->addError('assignedLawyerId', 'Seleccione un usuario con rol abogado.');
                $this->assignedLawyerId = $currentId;

                return;
            }

            $this->case = $cases->assignLawyer($this->case->fresh(), $lawyer, auth()->user());
        } else {
            DB::transaction(function () {
                $c = $this->case->fresh();
                $c->forceFill(['assigned_lawyer_id' => null])->save();
                DisciplinaryAction::create([
                    'disciplinary_case_id' => $c->id,
                    'user_id' => auth()->id(),
                    'action_type' => ActionType::CASO_ASIGNADO,
                    'description' => 'Abogado desasignado del expediente',
                    'metadata' => ['lawyer_id' => null],
                    'performed_at' => now(),
                ]);
            });
            $this->case = $this->case->fresh();
        }

        $this->syncCaseFromDb();
        session()->flash('success', 'Abogado asignado actualizado.');
    }

    private function normalizeLawyerId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    public function startCoordination(DisciplinaryAgendaThreadService $agenda): void
    {
        Gate::authorize('startCoordination', $this->case);

        try {
            $this->case = $agenda->startCoordination($this->case->fresh(['agendaThread']), auth()->user());
        } catch (\Throwable $e) {
            $this->addError('coordination', $e->getMessage());

            return;
        }

        $this->coordinationChatVisible = true;
        $this->syncCaseFromDb();
        session()->flash('success', 'Coordinación iniciada. Planeación fue notificada.');
    }

    public function showCoordinationChat(): void
    {
        if (! $this->case->hasCoordinationStarted() || ! $this->case->allowsAgendaThread()) {
            return;
        }

        $this->coordinationChatVisible = true;
    }

    public function hideCoordinationChat(): void
    {
        $this->coordinationChatVisible = false;
    }

    public function postAgendaLawyer(DisciplinaryAgendaThreadService $agenda): void
    {
        Gate::authorize('postAgendaLawyer', $this->case);

        $this->validate([
            'agendaLawyerBody' => ['nullable', 'string', 'max:8000'],
            'agendaLawyerUploads' => ['nullable', 'array', 'max:6'],
            'agendaLawyerUploads.*' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
        ]);

        $body = trim($this->agendaLawyerBody);
        $files = array_values(array_filter($this->agendaLawyerUploads));

        if ($body === '' && $files === []) {
            $this->addError('agendaLawyerBody', 'Escriba un mensaje o adjunte al menos un archivo.');

            return;
        }

        try {
            $agenda->postLawyerMessage(
                $this->case->fresh(['agendaThread']),
                auth()->user(),
                $body,
                $files,
            );
        } catch (\Throwable $e) {
            $this->addError('agendaLawyerBody', $e->getMessage());

            return;
        }

        $this->reset('agendaLawyerBody', 'agendaLawyerUploads');
        $this->syncCaseFromDb();
        session()->flash('success', 'Mensaje enviado a Planeación.');
    }

    public function removeAgendaLawyerUploadAt(int $index): void
    {
        Gate::authorize('postAgendaLawyer', $this->case);

        $files = $this->agendaLawyerUploads;
        if (! is_array($files) || ! isset($files[$index])) {
            return;
        }

        $upload = $files[$index];
        if ($upload instanceof TemporaryUploadedFile) {
            $upload->delete();
        }

        unset($files[$index]);
        $this->agendaLawyerUploads = array_values($files);
    }

    public function addPlanningSlotRow(): void
    {
        if (count($this->planningSlots) >= 5) {
            return;
        }
        $this->planningSlots[] = ['date' => '', 'time' => '', 'notes' => ''];
    }

    public function postAgendaPlanning(DisciplinaryAgendaThreadService $agenda): void
    {
        Gate::authorize('postAgendaPlanning', $this->case);

        $this->validate([
            'agendaPlanningBody' => ['nullable', 'string', 'max:8000'],
            'planningSlots' => ['nullable', 'array', 'max:5'],
            'planningSlots.*.date' => ['nullable', 'date'],
            'planningSlots.*.time' => ['nullable', 'date_format:H:i'],
            'planningSlots.*.notes' => ['nullable', 'string', 'max:500'],
            'agendaPlanningUploads' => ['nullable', 'array', 'max:6'],
            'agendaPlanningUploads.*' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
        ]);

        $body = trim($this->agendaPlanningBody);
        $files = array_values(array_filter($this->agendaPlanningUploads));

        try {
            $agenda->postPlanningMessage(
                $this->case->fresh(['agendaThread']),
                auth()->user(),
                $body,
                $this->planningSlots,
                $files,
            );
        } catch (\Throwable $e) {
            $this->addError('agendaPlanningBody', $e->getMessage());

            return;
        }

        $this->reset('agendaPlanningBody', 'agendaPlanningUploads');
        $this->planningSlots = [['date' => '', 'time' => '', 'notes' => '']];
        $this->syncCaseFromDb();
        session()->flash('success', 'Respuesta publicada en el hilo de agenda.');
    }

    public function confirmCitationSlot(DisciplinaryAgendaThreadService $agenda): void
    {
        Gate::authorize('postAgendaLawyer', $this->case);

        $this->validate([
            'selectedCitationSlotKey' => ['required', 'string', 'regex:/^\d+-\d+$/'],
        ]);

        [$messageId, $slotIndex] = $this->parseCitationSlotKey($this->selectedCitationSlotKey);

        try {
            $this->case = $agenda->confirmCitationSlot(
                $this->case->fresh(),
                auth()->user(),
                $messageId,
                $slotIndex,
            );
        } catch (\Throwable $e) {
            $this->addError('selectedCitationSlotKey', $e->getMessage());

            return;
        }

        $this->syncCaseFromDb();
        session()->flash('success', 'Fecha definitiva de citación registrada. Complete la coordinación de notificación física antes de generar el FO-GJ-03.');
    }

    public function requestNotificationCoordination(DisciplinaryCitationNotificationService $notification): void
    {
        Gate::authorize('requestNotificationCoordination', $this->case);

        try {
            $notification->requestNotificationInformation(
                $this->case->fresh(['agendaThread']),
                auth()->user(),
            );
        } catch (\Throwable $e) {
            $this->addError('notification', $e->getMessage());

            return;
        }

        $this->syncCaseFromDb();
        session()->flash('success', 'Solicitud de información de notificación enviada a Planeación.');
    }

    public function openReassignSupervisorModal(): void
    {
        Gate::authorize('reassignNotificationSupervisor', $this->case);
        $this->reassignSupervisorUserId = null;
        $this->reassignSupervisorReason = '';
        $this->showReassignSupervisorModal = true;
    }

    public function closeReassignSupervisorModal(): void
    {
        $this->showReassignSupervisorModal = false;
    }

    public function confirmReassignNotificationSupervisor(DisciplinaryCitationNotificationService $notification): void
    {
        Gate::authorize('reassignNotificationSupervisor', $this->case);

        $this->validate([
            'reassignSupervisorUserId' => ['required', 'integer', 'exists:users,id'],
            'reassignSupervisorReason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->case = $notification->reassignNotificationSupervisor(
                $this->case->fresh(),
                auth()->user(),
                (int) $this->reassignSupervisorUserId,
                $this->reassignSupervisorReason,
            );
        } catch (\Throwable $e) {
            $this->addError('reassignSupervisor', $e->getMessage());

            return;
        }

        $this->showReassignSupervisorModal = false;
        $this->reset('reassignSupervisorUserId', 'reassignSupervisorReason');
        $this->syncCaseFromDb();
        session()->flash('success', 'Supervisor de notificación reasignado correctamente.');
    }

    public function requestAdvanceFromCitacion(DisciplinaryCitationWorkflowService $citation): void
    {
        Gate::authorize('transition', $this->case);

        if ($this->case->current_status !== CaseStatus::CITACION_PROGRAMADA) {
            $this->addError('citationAdvance', 'Esta acción solo está disponible en etapa de citación.');

            return;
        }

        $this->showCitationAdvanceConfirm = false;

        if (! $citation->allRequirementsMet($this->case->fresh())) {
            $this->showCitationAdvanceValidation = true;

            return;
        }

        $this->showCitationAdvanceValidation = false;
        $this->showCitationAdvanceConfirm = true;
    }

    public function closeCitationAdvanceValidation(): void
    {
        $this->showCitationAdvanceValidation = false;
    }

    public function closeCitationAdvanceConfirm(): void
    {
        $this->showCitationAdvanceConfirm = false;
    }

    public function requestAdvanceFromDiligencia(DisciplinaryDiligenceWorkflowService $diligence): void
    {
        Gate::authorize('transition', $this->case);

        if (! in_array($this->case->current_status, [CaseStatus::DILIGENCIA, CaseStatus::COMITE_DISCIPLINARIO], true)) {
            $this->addError('diligenceAdvance', 'Esta acción solo está disponible en diligencia o comité disciplinario.');

            return;
        }

        try {
            $diligence->assertCanAdvanceToDecision($this->case->fresh());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'No puede avanzar a decisión.');
            }

            return;
        }

        $this->showDiligenceAdvanceConfirm = true;
    }

    public function closeDiligenceAdvanceConfirm(): void
    {
        $this->showDiligenceAdvanceConfirm = false;
    }

    public function confirmAdvanceFromDiligencia(
        DisciplinaryWorkflowService $workflow,
        DisciplinaryDiligenceWorkflowService $diligence,
    ): void {
        Gate::authorize('transition', $this->case);
        $this->showDiligenceAdvanceConfirm = false;

        try {
            $freshCase = $this->case->fresh();
            $diligence->assertCanAdvanceToDecision($freshCase);
            $this->applyCaseTransition(
                $workflow,
                CaseStatus::DECISION,
                $diligence->advanceNoteFor($freshCase),
            );
            session()->flash('success', 'El expediente pasó a etapa D: comunicado de decisión.');
        } catch (InvalidStateTransitionException $e) {
            $this->addError('diligenceAdvance', $e->getMessage());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'No puede avanzar a decisión.');
            }
        }
    }

    public function requestRegisterDiligenceAttendance(string $attendance): void
    {
        Gate::authorize('registerDiligenceAttendance', $this->case);

        if (! in_array($attendance, [DiligenceAttendance::ATTENDED->value, DiligenceAttendance::ABSENT->value], true)) {
            $this->addError('diligenceAttendance', 'Seleccione una opción de asistencia válida.');

            return;
        }

        $this->diligenceAttendancePending = $attendance;
        $this->showDiligenceAttendanceConfirm = true;
    }

    public function closeDiligenceAttendanceConfirm(): void
    {
        $this->showDiligenceAttendanceConfirm = false;
        $this->diligenceAttendancePending = '';
    }

    public function confirmRegisterDiligenceAttendance(DiligenceAttendanceService $attendance): void
    {
        Gate::authorize('registerDiligenceAttendance', $this->case);
        $this->showDiligenceAttendanceConfirm = false;

        $enum = DiligenceAttendance::tryFrom($this->diligenceAttendancePending);
        $this->diligenceAttendancePending = '';

        if ($enum === null) {
            $this->addError('diligenceAttendance', 'Seleccione una opción de asistencia válida.');

            return;
        }

        try {
            $this->case = $attendance->register($this->case->fresh(), auth()->user(), $enum);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'No fue posible registrar la asistencia.');
            }

            return;
        }

        $this->syncCaseFromDb();
        session()->flash('success', 'Asistencia registrada: '.$enum->label().'. Esta decisión no puede modificarse.');
    }

    public function confirmAdvanceFromCitacion(
        DisciplinaryWorkflowService $workflow,
        DisciplinaryCitationWorkflowService $citation,
        DisciplinaryAgendaThreadService $agenda,
    ): void {
        Gate::authorize('transition', $this->case);
        $this->showCitationAdvanceConfirm = false;

        try {
            $citation->assertCanLeaveCitacionStage($this->case->fresh());
            $this->applyCaseTransition(
                $workflow,
                CaseStatus::DILIGENCIA,
                'Avance a diligencia disciplinaria tras completar la etapa de citación.',
            );
            $this->closeCoordinationThreadIfOpen($agenda);
            session()->flash('success', 'El expediente pasó a etapa C: diligencia disciplinaria.');
        } catch (InvalidStateTransitionException $e) {
            $this->addError('citationAdvance', $e->getMessage());
        } catch (ValidationException) {
            $this->showCitationAdvanceValidation = true;
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseCitationSlotKey(string $key): array
    {
        if (! preg_match('/^(\d+)-(\d+)$/', $key, $m)) {
            throw new \InvalidArgumentException('Seleccione una fecha propuesta válida.');
        }

        return [(int) $m[1], (int) $m[2]];
    }

    public function openFoGj03DraftModal(FoGj03DraftService $drafts): void
    {
        Gate::authorize('editFoGj03Draft', $this->case);
        $this->syncCaseFromDb();

        $defaults = $drafts->defaultsForCase($this->case);
        $this->foGj03HearingTime = (string) ($defaults['hearing_time'] ?? '');
        $this->foGj03Modality = (string) ($defaults['modality'] ?? 'presencial');
        $this->foGj03VirtualLink = (string) ($defaults['virtual_meeting_link'] ?? '');
        $this->foGj03BreachDate = (string) ($defaults['breach_date'] ?? '');
        $this->foGj03ChargesDescription = (string) ($defaults['charges_description'] ?? '');
        $this->foGj03Article66Numerals = (string) ($defaults['article_66_numerals'] ?? '');
        $this->foGj03Article68Numerals = (string) ($defaults['article_68_numerals'] ?? '');
        $this->foGj03Article76Numerals = (string) ($defaults['article_76_numerals'] ?? '');
        $this->foGj03InformeReportDate = (string) ($defaults['informe_report_date'] ?? '');
        $this->showFoGj03DraftModal = true;
    }

    public function closeFoGj03DraftModal(): void
    {
        $this->showFoGj03DraftModal = false;
    }

    public function saveFoGj03Draft(FoGj03DraftService $drafts): void
    {
        Gate::authorize('editFoGj03Draft', $this->case);

        try {
            $this->case = $drafts->saveDraft($this->case->fresh(), auth()->user(), [
                'hearing_time' => $this->foGj03HearingTime,
                'modality' => $this->foGj03Modality,
                'virtual_meeting_link' => $this->foGj03VirtualLink,
                'breach_date' => $this->foGj03BreachDate,
                'charges_description' => $this->foGj03ChargesDescription,
                'article_66_numerals' => $this->foGj03Article66Numerals,
                'article_68_numerals' => $this->foGj03Article68Numerals,
                'article_76_numerals' => $this->foGj03Article76Numerals,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Error de validación.');
            }

            return;
        }

        $this->showFoGj03DraftModal = false;
        $this->syncCaseFromDb();
        session()->flash('success', 'FO-GJ-03 diligenciado. Ya puede previsualizar o generar el documento.');
    }

    public function generateFoGj03(FoGj03CitationService $fo03): void
    {
        Gate::authorize('generateFoGj03', $this->case);

        try {
            $this->case = $fo03->generateAndStore($this->case->fresh(), auth()->user());
        } catch (\Throwable $e) {
            $this->addError('fo_gj_03', $e->getMessage());

            return;
        }

        $this->syncCaseFromDb();
        session()->flash('success', 'FO-GJ-03 generado y almacenado en el expediente.');
    }

    public function uploadCitationEvidence(
        DisciplinaryDocumentService $documents,
        DisciplinaryCitationWorkflowService $citationWorkflow,
        DisciplinaryAuditService $audit,
    ): void {
        Gate::authorize('uploadCitationEvidence', $this->case);
        $citationWorkflow->assertCitationEvidenceUploadAllowed($this->case->fresh(['documents', 'informeSubmission']), auth()->user());

        $this->validate([
            'citationEvidenceType' => ['required', 'in:signed,refused_witnesses'],
            'citationEvidenceFile' => ['required', 'file', 'mimes:pdf', 'max:15360'],
        ]);

        $type = CitationEvidenceType::from($this->citationEvidenceType);
        $stage = $this->case->stages()
            ->where('stage_type', StageType::CITACION)
            ->orderByDesc('sequence')
            ->first();

        $uploader = auth()->user();
        $doc = $documents->upload(
            $this->case,
            $this->citationEvidenceFile,
            DocumentType::CITACION,
            $uploader,
            $stage,
            DisciplinaryCase::NOTE_CITATION_EVIDENCE_PREFIX.' — '.$type->label(),
        );

        $this->case = $citationWorkflow->markEvidenceUploaded($this->case->fresh(), $type);

        $audit->logCase(
            $this->case,
            $uploader,
            ActionType::EVIDENCIA_CITACION_CARGADA,
            'Evidencia PDF de citación cargada.',
            [
                'evidence_type' => $type->value,
                'document_id' => $doc->id,
                'uploaded_by' => $uploader->id,
                'uploaded_at' => now()->toIso8601String(),
                'fo_gj_03_document_id' => $this->case->primaryFoGj03CitationDocument()?->id,
            ],
        );

        $this->reset('citationEvidenceFile', 'citationEvidenceType');
        $this->syncCaseFromDb();
        session()->flash('success', 'Evidencia de citación cargada correctamente.');
    }

    public function removeAgendaPlanningUploadAt(int $index): void
    {
        Gate::authorize('postAgendaPlanning', $this->case);

        $files = $this->agendaPlanningUploads;
        if (! is_array($files) || ! isset($files[$index])) {
            return;
        }

        $upload = $files[$index];
        if ($upload instanceof TemporaryUploadedFile) {
            $upload->delete();
        }

        unset($files[$index]);
        $this->agendaPlanningUploads = array_values($files);
    }

    public function openFo51PdfPreview(): void
    {
        Gate::authorize('viewFo51InformePdf', $this->case);
        $this->case->load('documents');
        $doc = $this->case->primaryFo51InformeDocument();
        if (! $doc || $doc->path === '') {
            $this->addError('fo51', 'No hay PDF del informe FO-GJ-51 guardado en este expediente.');

            return;
        }

        $this->resetErrorBag();
        $this->fo51PdfPreviewDocumentId = $doc->id;
    }

    public function closeFo51PdfPreview(): void
    {
        $this->fo51PdfPreviewDocumentId = null;
    }

    public function openFoGj03PdfPreview(): void
    {
        Gate::authorize('previewFoGj03', $this->case);
        $this->resetErrorBag('fo_gj_03');
        $this->showFoGj03PdfPreviewModal = true;
    }

    public function closeFoGj03PdfPreview(): void
    {
        $this->showFoGj03PdfPreviewModal = false;
    }

    public function openFoGj04DraftModal(FoGj04DraftService $drafts): void
    {
        Gate::authorize('editFoGj04Draft', $this->case);
        $defaults = $drafts->defaultsForCase($this->case->fresh(['employee', 'assignedLawyer']));
        $this->foGj04WorkerManifestation = (string) ($defaults['worker_manifestation'] ?? '');
        $this->foGj04OpeningTime = (string) ($defaults['opening_time'] ?? '');
        $this->foGj04ClosingTime = (string) ($defaults['closing_time'] ?? '');
        $this->foGj04Questions = $defaults['questions'] ?? [];
        $this->showFoGj04DraftModal = true;
    }

    public function closeFoGj04DraftModal(): void
    {
        $this->showFoGj04DraftModal = false;
    }

    public function addFoGj04Question(): void
    {
        $this->foGj04Questions[] = ['question' => '', 'answer' => ''];
    }

    public function removeFoGj04Question(int $index): void
    {
        if (! isset($this->foGj04Questions[$index])) {
            return;
        }

        unset($this->foGj04Questions[$index]);
        $this->foGj04Questions = array_values($this->foGj04Questions);
    }

    public function saveFoGj04Draft(FoGj04DraftService $drafts): void
    {
        Gate::authorize('editFoGj04Draft', $this->case);

        try {
            $this->case = $drafts->saveDraft($this->case->fresh(), auth()->user(), [
                'worker_manifestation' => $this->foGj04WorkerManifestation,
                'opening_time' => $this->foGj04OpeningTime,
                'closing_time' => $this->foGj04ClosingTime,
                'questions' => $this->foGj04Questions,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Error de validación.');
            }

            return;
        }

        $this->showFoGj04DraftModal = false;
        $this->syncCaseFromDb();
        session()->flash('success', 'FO-GJ-04 diligenciado. Ya puede previsualizar o generar el acta.');
    }

    public function generateFoGj04(FoGj04DiligenceActaService $fo04): void
    {
        Gate::authorize('generateFoGj04', $this->case);

        try {
            $this->case = $fo04->generateAndStore($this->case->fresh(), auth()->user());
        } catch (\Throwable $e) {
            $this->addError('fo_gj_04', $e->getMessage());

            return;
        }

        $this->syncCaseFromDb();
        session()->flash('success', 'FO-GJ-04 generado y almacenado en el expediente.');
    }

    public function openFoGj04PdfPreview(): void
    {
        Gate::authorize('previewFoGj04', $this->case);
        $this->resetErrorBag('fo_gj_04');
        $this->showFoGj04PdfPreviewModal = true;
    }

    public function closeFoGj04PdfPreview(): void
    {
        $this->showFoGj04PdfPreviewModal = false;
    }

    public function openFoGj04WorkerSignaturePad(): void
    {
        Gate::authorize('captureFoGj04WorkerSignature', $this->case);
        $payload = $this->case->fo_gj_04_payload ?? [];
        $this->foGj04WorkerSignatureDataUri = $payload['worker_signature_data_uri'] ?? null;
        $this->showFoGj04SignaturePadModal = true;
    }

    public function closeFoGj04WorkerSignaturePad(): void
    {
        $this->showFoGj04SignaturePadModal = false;
    }

    public function saveFoGj04WorkerSignature(string $dataUri, CitationNotificationSigningService $signing, FoGj04DraftService $drafts): void
    {
        Gate::authorize('captureFoGj04WorkerSignature', $this->case);

        try {
            $valid = $signing->assertValidWorkerSignatureDataUri($dataUri);
            $this->case = $drafts->saveWorkerSignature($this->case->fresh(), auth()->user(), $valid);
            $this->foGj04WorkerSignatureDataUri = $valid;
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Firma no válida.');
            }

            return;
        }

        $this->showFoGj04SignaturePadModal = false;
        $this->syncCaseFromDb();
        session()->flash('success', 'Firma del trabajador registrada en el acta.');
    }

    public function clearFoGj04WorkerSignature(FoGj04DraftService $drafts): void
    {
        Gate::authorize('captureFoGj04WorkerSignature', $this->case);

        if ($this->case->fo_gj_04_generated_at !== null) {
            return;
        }

        $payload = $this->case->fo_gj_04_payload ?? [];
        unset($payload['worker_signature_data_uri']);
        $this->case->forceFill(['fo_gj_04_payload' => $payload])->save();
        $this->foGj04WorkerSignatureDataUri = null;
        $this->syncCaseFromDb();
    }

    public function openFoGj44DraftModal(FoGj44DraftService $drafts): void
    {
        Gate::authorize('editFoGj44Draft', $this->case);
        $defaults = $drafts->defaultsForCase($this->case->fresh(['employee', 'assignedLawyer']));
        $this->foGj44SignTime = (string) ($defaults['sign_time'] ?? '');
        $this->foGj44SignDay = (string) ($defaults['sign_day'] ?? '');
        $this->foGj44SignMonth = (string) ($defaults['sign_month'] ?? '');
        $this->foGj44SignYearSuffix = (string) ($defaults['sign_year_suffix'] ?? '');
        $this->foGj44Witness1Name = (string) ($defaults['witness1_name'] ?? '');
        $this->foGj44Witness1Cargo = (string) ($defaults['witness1_cargo'] ?? '');
        $this->foGj44Witness1Date = (string) ($defaults['witness1_date'] ?? '');
        $this->foGj44Witness2Name = (string) ($defaults['witness2_name'] ?? '');
        $this->foGj44Witness2Cargo = (string) ($defaults['witness2_cargo'] ?? '');
        $this->foGj44Witness2Date = (string) ($defaults['witness2_date'] ?? '');
        $this->showFoGj44DraftModal = true;
    }

    public function closeFoGj44DraftModal(): void
    {
        $this->showFoGj44DraftModal = false;
    }

    public function saveFoGj44Draft(FoGj44DraftService $drafts): void
    {
        Gate::authorize('editFoGj44Draft', $this->case);

        try {
            $this->case = $drafts->saveDraft($this->case->fresh(), auth()->user(), [
                'sign_time' => $this->foGj44SignTime,
                'sign_day' => $this->foGj44SignDay,
                'sign_month' => $this->foGj44SignMonth,
                'sign_year_suffix' => $this->foGj44SignYearSuffix,
                'witness1_name' => $this->foGj44Witness1Name,
                'witness1_cargo' => $this->foGj44Witness1Cargo,
                'witness1_date' => $this->foGj44Witness1Date,
                'witness2_name' => $this->foGj44Witness2Name,
                'witness2_cargo' => $this->foGj44Witness2Cargo,
                'witness2_date' => $this->foGj44Witness2Date,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Error de validación.');
            }

            return;
        }

        $this->showFoGj44DraftModal = false;
        $this->syncCaseFromDb();
        session()->flash('success', 'FO-GJ-44 diligenciado. Ya puede previsualizar o generar la constancia.');
    }

    public function generateFoGj44(FoGj44ConstanciaService $fo44): void
    {
        Gate::authorize('generateFoGj44', $this->case);

        try {
            $this->case = $fo44->generateAndStore($this->case->fresh(), auth()->user());
        } catch (\Throwable $e) {
            $this->addError('fo_gj_44', $e->getMessage());

            return;
        }

        $this->syncCaseFromDb();
        session()->flash('success', 'FO-GJ-44 generado. Se abrió la ventana de 2 días para justificación.');
    }

    public function openFoGj44PdfPreview(): void
    {
        Gate::authorize('previewFoGj44', $this->case);
        $this->resetErrorBag('fo_gj_44');
        $this->showFoGj44PdfPreviewModal = true;
    }

    public function closeFoGj44PdfPreview(): void
    {
        $this->showFoGj44PdfPreviewModal = false;
    }

    public function openFoGj54DraftModal(FoGj54DraftService $drafts): void
    {
        Gate::authorize('editFoGj54Draft', $this->case);
        $defaults = $drafts->defaultsForCase($this->case->fresh(['employee', 'assignedLawyer']));
        $this->foGj54ClientSite = (string) ($defaults['client_site'] ?? '');
        $this->foGj54ShiftStart = (string) ($defaults['shift_start'] ?? '');
        $this->foGj54ShiftEnd = (string) ($defaults['shift_end'] ?? '');
        $this->foGj54NewHearingDate = (string) ($defaults['new_hearing_date'] ?? '');
        $this->foGj54NewHearingTime = (string) ($defaults['new_hearing_time'] ?? '');
        $this->foGj54NewHearingPlace = (string) ($defaults['new_hearing_place'] ?? '');
        $this->showFoGj54DraftModal = true;
    }

    public function closeFoGj54DraftModal(): void
    {
        $this->showFoGj54DraftModal = false;
    }

    public function saveFoGj54Draft(FoGj54DraftService $drafts): void
    {
        Gate::authorize('editFoGj54Draft', $this->case);

        try {
            $this->case = $drafts->saveDraft($this->case->fresh(), auth()->user(), [
                'client_site' => $this->foGj54ClientSite,
                'shift_start' => $this->foGj54ShiftStart,
                'shift_end' => $this->foGj54ShiftEnd,
                'new_hearing_date' => $this->foGj54NewHearingDate,
                'new_hearing_time' => $this->foGj54NewHearingTime,
                'new_hearing_place' => $this->foGj54NewHearingPlace,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Error de validación.');
            }

            return;
        }

        $this->showFoGj54DraftModal = false;
        $this->syncCaseFromDb();
        session()->flash('success', 'FO-GJ-54 diligenciado. Ya puede previsualizar o generar la reprogramación.');
    }

    public function generateFoGj54AndAcceptJustification(FoGj54ReprogramacionService $fo54): void
    {
        Gate::authorize('generateFoGj54', $this->case);

        try {
            $this->case = $fo54->generateAcceptJustificationAndStore($this->case->fresh(), auth()->user());
        } catch (\Throwable $e) {
            $this->addError('fo_gj_54', $e->getMessage());

            return;
        }

        $this->syncCaseFromDb();
        session()->flash('success', 'Justificación aceptada. FO-GJ-54 generado y diligencia reprogramada.');
    }

    public function openFoGj54PdfPreview(): void
    {
        Gate::authorize('previewFoGj54', $this->case);
        $this->resetErrorBag('fo_gj_54');
        $this->showFoGj54PdfPreviewModal = true;
    }

    public function closeFoGj54PdfPreview(): void
    {
        $this->showFoGj54PdfPreviewModal = false;
    }

    public function requestRejectDiligenceJustification(): void
    {
        Gate::authorize('manageDiligenceJustification', $this->case);
        $this->showJustificationRejectConfirm = true;
    }

    public function closeJustificationRejectConfirm(): void
    {
        $this->showJustificationRejectConfirm = false;
        $this->justificationRejectNote = '';
    }

    public function confirmRejectDiligenceJustification(DisciplinaryWorkflowService $workflow): void
    {
        Gate::authorize('manageDiligenceJustification', $this->case);
        $this->showJustificationRejectConfirm = false;

        try {
            $note = trim($this->justificationRejectNote) !== ''
                ? trim($this->justificationRejectNote)
                : 'Justificación rechazada o no presentada dentro del plazo.';
            $this->case = $workflow->rejectJustification($this->case->fresh(), auth()->user(), $note);
            $this->justificationRejectNote = '';
            $this->syncCaseFromDb();
            session()->flash('success', 'El expediente fue remitido a comité disciplinario.');
        } catch (InvalidStateTransitionException $e) {
            $this->addError('justification', $e->getMessage());
        }
    }

    public function openComiteDraftModal(ComiteDraftService $drafts): void
    {
        Gate::authorize('editComiteDraft', $this->case);
        $defaults = $drafts->defaultsForCase($this->case);
        $this->comiteDecisionNarrative = (string) ($defaults['decision_narrative'] ?? '');
        $this->comiteAttendees = is_array($defaults['attendees'] ?? null) ? $defaults['attendees'] : [];
        $this->resetErrorBag(['comiteDecisionNarrative', 'comiteAttendees']);
        $this->showComiteDraftModal = true;
    }

    public function closeComiteDraftModal(): void
    {
        $this->showComiteDraftModal = false;
    }

    public function saveComiteDraft(ComiteDraftService $drafts): void
    {
        Gate::authorize('editComiteDraft', $this->case);

        try {
            $this->case = $drafts->saveDraft($this->case->fresh(), auth()->user(), [
                'decision_narrative' => $this->comiteDecisionNarrative,
                'attendees' => $this->comiteAttendees,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Error de validación.');
            }

            return;
        }

        $this->showComiteDraftModal = false;
        $this->syncCaseFromDb();
        session()->flash('success', 'Acta de comité diligenciada. Ya puede previsualizar o generar el PDF.');
    }

    public function addComiteAttendee(): void
    {
        $this->comiteAttendees[] = ['name' => '', 'cargo' => '', 'signature_data_uri' => null];
    }

    public function removeComiteAttendee(int $index): void
    {
        if (! isset($this->comiteAttendees[$index])) {
            return;
        }

        unset($this->comiteAttendees[$index]);
        $this->comiteAttendees = array_values($this->comiteAttendees);
    }

    public function openComiteAttendeeSignaturePad(int $index): void
    {
        Gate::authorize('editComiteDraft', $this->case);
        if (! isset($this->comiteAttendees[$index])) {
            return;
        }

        $this->comiteSignatureAttendeeIndex = $index;
        $this->comiteSignaturePendingDataUri = $this->comiteAttendees[$index]['signature_data_uri'] ?? null;
    }

    public function closeComiteAttendeeSignaturePad(): void
    {
        $this->comiteSignatureAttendeeIndex = null;
        $this->comiteSignaturePendingDataUri = null;
    }

    public function saveComiteAttendeeSignature(string $dataUri, CitationNotificationSigningService $signing): void
    {
        Gate::authorize('editComiteDraft', $this->case);
        $index = $this->comiteSignatureAttendeeIndex;
        if ($index === null || ! isset($this->comiteAttendees[$index])) {
            return;
        }

        try {
            $valid = $signing->assertValidWorkerSignatureDataUri($dataUri);
            $this->comiteAttendees[$index]['signature_data_uri'] = $valid;
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Firma no válida.');
            }

            return;
        }

        $this->closeComiteAttendeeSignaturePad();
    }

    public function generateComiteActa(ComiteActaService $comite): void
    {
        Gate::authorize('generateComite', $this->case);

        try {
            $this->case = $comite->generateAndStore($this->case->fresh(), auth()->user());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Error de validación.');
            }

            return;
        } catch (\Throwable $e) {
            $this->addError('comiteDecisionNarrative', $e->getMessage());

            return;
        }

        $this->syncCaseFromDb();
        session()->flash('success', 'Acta de comité generada y guardada en el expediente.');
    }

    public function openComitePdfPreview(): void
    {
        Gate::authorize('previewComite', $this->case);
        $this->resetErrorBag('comiteDecisionNarrative');
        $this->showComitePdfPreviewModal = true;
    }

    public function closeComitePdfPreview(): void
    {
        $this->showComitePdfPreviewModal = false;
    }

    public function openDocumentPreview(int $documentId): void
    {
        Gate::authorize('view', $this->case);
        $this->case->loadMissing('documents');
        $doc = $this->case->documents->firstWhere('id', $documentId);
        if (! $doc || $doc->path === '') {
            $this->addError('documents', 'No se encontró el documento en el expediente.');

            return;
        }

        $this->resetErrorBag('documents');
        $this->documentPreviewId = $documentId;
    }

    public function closeDocumentPreview(): void
    {
        $this->documentPreviewId = null;
    }

    private function syncCaseFromDb(): void
    {
        $this->case = $this->case->fresh([
            'employee',
            'reporter:id,name,job_position_id,position',
            'reporter.jobPosition:id,name',
            'assignedLawyer:id,name,signature_path,signature_disk,job_position_id,position',
            'assignedLawyer.jobPosition:id,name',
            'informeSubmission.submitter:id,name,job_position_id,position',
            'informeSubmission.submitter.jobPosition:id,name',
            'informeSubmission.reviewer:id,name,job_position_id,position',
            'informeSubmission.reviewer.jobPosition:id,name',
            'faults',
            'stages.performer:id,name',
            'documents.uploader:id,name,job_position_id,position',
            'documents.uploader.jobPosition:id,name',
            'actions.user:id,name,job_position_id,position',
            'actions.user.jobPosition:id,name',
            'actions.stage:id,stage_type',
            'agendaThread.organizationalArea:id,name,slug',
            'agendaThread.messages.author:id,name',
            'agendaThread.messages.attachments',
            'notificationSupervisor:id,name',
        ]) ?? $this->case;

        $this->assignedLawyerId = $this->case->assigned_lawyer_id;
    }

    public function render()
    {
        if ($this->fo51PdfPreviewDocumentId !== null) {
            $this->case->loadMissing('documents');
            $previewDoc = $this->case->documents->firstWhere('id', $this->fo51PdfPreviewDocumentId);
            if (! $previewDoc || $previewDoc->path === '') {
                $this->fo51PdfPreviewDocumentId = null;
            }
        }

        if ($this->documentPreviewId !== null) {
            $this->case->loadMissing('documents');
            $previewDoc = $this->case->documents->firstWhere('id', $this->documentPreviewId);
            if (! $previewDoc || $previewDoc->path === '') {
                $this->documentPreviewId = null;
            }
        }

        $this->case->load([
            'employee',
            'reporter:id,name,job_position_id,position',
            'reporter.jobPosition:id,name',
            'assignedLawyer:id,name,signature_path,signature_disk,job_position_id,position',
            'assignedLawyer.jobPosition:id,name',
            'informeSubmission.submitter:id,name,job_position_id,position',
            'informeSubmission.submitter.jobPosition:id,name',
            'informeSubmission.reviewer:id,name,job_position_id,position',
            'informeSubmission.reviewer.jobPosition:id,name',
            'faults',
            'stages.performer:id,name',
            'documents.uploader:id,name,job_position_id,position',
            'documents.uploader.jobPosition:id,name',
            'actions.user:id,name,job_position_id,position',
            'actions.user.jobPosition:id,name',
            'actions.stage:id,stage_type',
            'agendaThread.organizationalArea:id,name,slug',
            'agendaThread.messages.author:id,name',
            'agendaThread.messages.attachments',
            'notificationSupervisor:id,name',
        ]);

        $agendaAreas = OrganizationalArea::query()
            ->where('is_active', true)
            ->where('slug', 'planeacion')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        if ($agendaAreas->isEmpty()) {
            $agendaAreas = OrganizationalArea::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        }

        $citationWorkflow = app(DisciplinaryCitationWorkflowService::class);
        $notificationService = app(DisciplinaryCitationNotificationService::class);
        $stageProgress = app(CitationStageProgress::class);
        $diligenceStageProgress = app(DiligenceStageProgress::class);
        $citationSlotChoices = $this->buildCitationSlotChoices();
        $citationReadOnly = $this->case->showsCitationStageReadOnly();
        $showsDiligenceStagePanel = $this->case->showsDiligenceStagePanel();

        if ($citationReadOnly) {
            $citationStageSteps = $stageProgress->completedSteps();
            $citationCurrentStep = $citationStageSteps->last() ?? $stageProgress->currentStep($this->case);
            $citationCurrentStepNumber = $stageProgress->totalSteps();
        } else {
            $citationStageSteps = $stageProgress->steps($this->case);
            $citationCurrentStep = $stageProgress->currentStep($this->case);
            $citationCurrentStepNumber = $stageProgress->currentStepNumber($this->case);
        }

        $overviewStageStack = app(CaseOverviewStageStack::class)->stagesForCase($this->case);

        return view('livewire.disciplinary.cases.show', [
            'overviewStageStack' => $overviewStageStack,
            'advanceStageLabel' => StageType::CITACION->label(),
            'relatedCases' => $this->relatedCasesSameDocument(),
            'lawyerCandidates' => Gate::allows('assign', $this->case)
                ? User::query()->role('abogado')->active()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'supervisorCandidates' => User::query()->role('supervisor')->active()->orderBy('name')->get(['id', 'name']),
            'organizationalAreasForAgenda' => $agendaAreas,
            'citationReadiness' => $citationWorkflow->readinessChecklist($this->case),
            'citationMissing' => $citationWorkflow->missingRequirements($this->case),
            'citationRequirementLabels' => DisciplinaryCitationWorkflowService::requirementLabels(),
            'citationSlotChoices' => $citationSlotChoices,
            'citationAdvanceTargetLabel' => StageType::DILIGENCIA->label(),
            'foGj03GenerationChecklist' => $notificationService->foGj03GenerationChecklist($this->case),
            'foGj03GenerationLabels' => DisciplinaryCitationNotificationService::foGj03GenerationLabels(),
            'notificationPending' => $notificationService->hasPendingNotificationRequest($this->case),
            'notificationCompleted' => $notificationService->hasNotificationInformationCompleted($this->case),
            'citationReadOnly' => $citationReadOnly,
            'citationStageSteps' => $citationStageSteps,
            'citationCurrentStep' => $citationCurrentStep,
            'citationCurrentStepNumber' => $citationCurrentStepNumber,
            'citationTotalSteps' => $stageProgress->totalSteps(),
            'isDiligenciaActive' => $showsDiligenceStagePanel,
            'showsDiligenceStagePanel' => $showsDiligenceStagePanel,
            'diligenceStageSteps' => $showsDiligenceStagePanel ? $diligenceStageProgress->steps($this->case) : collect(),
            'diligenceCurrentStep' => $showsDiligenceStagePanel ? $diligenceStageProgress->currentStep($this->case) : null,
            'diligenceCurrentStepNumber' => $showsDiligenceStagePanel ? $diligenceStageProgress->currentStepNumber($this->case) : null,
            'diligenceTotalSteps' => $diligenceStageProgress->totalSteps($this->case),
            'diligenceAdvanceTargetLabel' => StageType::DECISION->label(),
            'diligenceSlotDisplay' => $this->resolveDiligenceSlotDisplay(),
            'notificationSlotDisplay' => $this->resolveNotificationSlotDisplay(),
            'diligenceDateRequestStatus' => $stageProgress->diligenceDateRequestStatusLabel($this->case),
        ]);
    }

    /**
     * Fecha/hora mostrada en la barra de Etapa B (confirmada o selección pendiente en chat).
     *
     * @return array{date: string, time: string, confirmed: bool}
     */
    public function resolveDiligenceSlotDisplay(): array
    {
        if ($this->case->citation_confirmed_date) {
            return [
                'date' => $this->case->citation_confirmed_date->format('d/m/Y'),
                'time' => $this->case->resolvedDiligenceHearingTimeLabel() ?? '—',
                'confirmed' => true,
            ];
        }

        if (preg_match('/^(\d+)-(\d+)$/', $this->selectedCitationSlotKey, $matches) === 1) {
            $messageId = (int) $matches[1];
            $slotIndex = (int) $matches[2];
            $this->case->loadMissing('agendaThread.messages');

            foreach ($this->case->agendaThread?->messages ?? [] as $message) {
                if ((int) $message->id !== $messageId) {
                    continue;
                }

                $slot = $message->normalizedProposedSlots()[$slotIndex] ?? null;
                if ($slot === null) {
                    break;
                }

                $date = (string) ($slot['date'] ?? '');
                $timeRaw = (string) ($slot['time'] ?? '09:00');

                try {
                    $dt = \Illuminate\Support\Carbon::parse($date.' '.$timeRaw);

                    return [
                        'date' => $dt->format('d/m/Y'),
                        'time' => $dt->format('h:i A'),
                        'confirmed' => false,
                    ];
                } catch (\Throwable) {
                    return [
                        'date' => $date !== '' ? $date : '—',
                        'time' => $timeRaw !== '' ? $timeRaw : '—',
                        'confirmed' => false,
                    ];
                }
            }
        }

        return [
            'date' => '—',
            'time' => '—',
            'confirmed' => false,
        ];
    }

    /**
     * Notificación física en la barra de Etapa B (registrada por Planeación).
     *
     * @return array{date: string, shift: string, zone: string, supervisor: string, completed: bool}
     */
    public function resolveNotificationSlotDisplay(): array
    {
        $empty = [
            'date' => '—',
            'shift' => '—',
            'zone' => '—',
            'supervisor' => '—',
            'completed' => false,
        ];

        if (! app(DisciplinaryCitationNotificationService::class)->hasNotificationInformationCompleted($this->case)) {
            return $empty;
        }

        return [
            'date' => $this->case->notification_date?->format('d/m/Y') ?? '—',
            'shift' => filled($this->case->notification_shift) ? (string) $this->case->notification_shift : '—',
            'zone' => filled($this->case->notification_zone) ? (string) $this->case->notification_zone : '—',
            'supervisor' => filled($this->case->notification_supervisor_name)
                ? (string) $this->case->notification_supervisor_name
                : '—',
            'completed' => true,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{key: string, label: string, notes: string|null}>
     */
    private function buildCitationSlotChoices(): \Illuminate\Support\Collection
    {
        $this->case->loadMissing('agendaThread.messages');
        $choices = collect();

        foreach ($this->case->agendaThread?->messages ?? [] as $message) {
            foreach ($message->normalizedProposedSlots() as $index => $slot) {
                $date = (string) ($slot['date'] ?? '');
                if ($date === '') {
                    continue;
                }
                $time = isset($slot['time']) && $slot['time'] !== '' ? (string) $slot['time'] : '09:00';
                try {
                    $dt = \Illuminate\Support\Carbon::parse($date.' '.$time);
                    $label = $dt->format('d/m/Y').' - '.$dt->format('h:i A');
                } catch (\Throwable) {
                    $label = trim($date.' '.$time);
                }
                $choices->push([
                    'key' => $message->id.'-'.$index,
                    'label' => $label,
                    'notes' => isset($slot['notes']) && $slot['notes'] !== '' ? (string) $slot['notes'] : null,
                ]);
            }
        }

        return $choices;
    }

    /**
     * Otros procesos disciplinarios con el mismo número de documento (misma persona en el sistema).
     *
     * @return Collection<int, DisciplinaryCase>
     */
    private function ensureInformeStageForTransition(): void
    {
        if ($this->case->current_status !== CaseStatus::INFORME) {
            throw new InvalidStateTransitionException('Esta acción solo está disponible en etapa A (informe disciplinario).');
        }
    }

    private function applyCaseTransition(
        DisciplinaryWorkflowService $workflow,
        CaseStatus $to,
        ?string $note = null,
    ): void {
        $this->case = $workflow->transition(
            $this->case->fresh(),
            $to,
            auth()->user(),
            $note,
        );
        $this->syncCaseFromDb();
    }

    private function closeCoordinationThreadIfOpen(DisciplinaryAgendaThreadService $agenda): void
    {
        $case = $this->case->fresh(['agendaThread']);
        if ($case->agendaThread === null || $case->agendaThread->isClosed()) {
            return;
        }

        try {
            $this->case = $agenda->closeCoordination($case, auth()->user());
            $this->coordinationChatVisible = false;
            $this->syncCaseFromDb();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function relatedCasesSameDocument()
    {
        $this->case->loadMissing('employee');
        $doc = $this->case->employee?->document_number;
        if (! filled($doc)) {
            return collect();
        }

        return DisciplinaryCase::query()
            ->forDisciplinaryActor(auth()->user())
            ->with(['employee:id,first_name,last_name,document_number', 'assignedLawyer:id,name'])
            ->where('disciplinary_cases.id', '!=', $this->case->getKey())
            ->whereHas('employee', fn ($q) => $q->where('document_number', $doc))
            ->orderByDesc('opened_at')
            ->limit(50)
            ->get();
    }
}
