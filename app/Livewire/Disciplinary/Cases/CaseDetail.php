<?php

namespace App\Livewire\Disciplinary\Cases;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\StageType;
use App\Exceptions\Disciplinary\CaseAlreadyClaimedException;
use App\Exceptions\Disciplinary\InvalidStateTransitionException;
use App\Models\Disciplinary\DisciplinaryAction;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryStage;
use App\Models\OrganizationalArea;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryAgendaThreadService;
use App\Services\Disciplinary\DisciplinaryCaseService;
use App\Services\Disciplinary\DisciplinaryWorkflowService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    public ?int $agendaOrganizationalAreaId = null;

    /** Respuesta planeación (campo aparte para no chocar con admin que ve ambos formularios) */
    public string $agendaPlanningBody = '';

    /** @var array<int, mixed> */
    public array $agendaPlanningUploads = [];

    /** Vista previa del PDF FO-GJ-51 ya incorporado al expediente. */
    public ?int $fo51PdfPreviewDocumentId = null;

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

    public function postAgendaLawyer(DisciplinaryAgendaThreadService $agenda): void
    {
        Gate::authorize('postAgendaLawyer', $this->case);

        $rules = [
            'agendaLawyerBody' => ['required', 'string', 'max:8000'],
        ];
        if ($this->case->agendaThread === null) {
            $rules['agendaOrganizationalAreaId'] = ['required', 'integer', 'exists:organizational_areas,id'];
        }

        $this->validate($rules);

        try {
            $agenda->postLawyerMessage(
                $this->case->fresh(['agendaThread']),
                auth()->user(),
                $this->agendaLawyerBody,
                $this->case->agendaThread ? null : $this->agendaOrganizationalAreaId,
                [],
            );
        } catch (\Throwable $e) {
            $this->addError('agendaLawyerBody', $e->getMessage());

            return;
        }

        $this->reset('agendaLawyerBody', 'agendaOrganizationalAreaId');
        $this->syncCaseFromDb();
        session()->flash('success', 'Solicitud enviada a planeación (hilo de citación FO-GJ-03).');
    }

    public function postAgendaPlanning(DisciplinaryAgendaThreadService $agenda): void
    {
        Gate::authorize('postAgendaPlanning', $this->case);

        $this->validate([
            'agendaPlanningBody' => ['nullable', 'string', 'max:8000'],
            'agendaPlanningUploads' => ['nullable', 'array', 'max:6'],
            'agendaPlanningUploads.*' => ['nullable', 'file', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);

        $body = trim($this->agendaPlanningBody);
        $files = array_values(array_filter($this->agendaPlanningUploads));

        if ($body === '' && $files === []) {
            $this->addError('agendaPlanningBody', 'Escriba un mensaje o adjunte al menos una imagen.');

            return;
        }

        try {
            $agenda->postPlanningMessage(
                $this->case->fresh(['agendaThread']),
                auth()->user(),
                $body,
                $files,
            );
        } catch (\Throwable $e) {
            $this->addError('agendaPlanningBody', $e->getMessage());

            return;
        }

        $this->reset('agendaPlanningBody', 'agendaPlanningUploads');
        $this->syncCaseFromDb();
        session()->flash('success', 'Respuesta publicada en el hilo de agenda.');
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

    private function syncCaseFromDb(): void
    {
        $this->case = $this->case->fresh([
            'employee',
            'reporter:id,name,job_position_id,position',
            'reporter.jobPosition:id,name',
            'assignedLawyer:id,name',
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

        $this->case->load([
            'employee',
            'reporter:id,name,job_position_id,position',
            'reporter.jobPosition:id,name',
            'assignedLawyer:id,name',
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

        return view('livewire.disciplinary.cases.show', [
            'advanceStageLabel' => StageType::CITACION->label(),
            'relatedCases' => $this->relatedCasesSameDocument(),
            'lawyerCandidates' => Gate::allows('assign', $this->case)
                ? User::query()->role('abogado')->active()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'organizationalAreasForAgenda' => $agendaAreas,
        ]);
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
