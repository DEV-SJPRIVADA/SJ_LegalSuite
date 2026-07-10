<?php

namespace App\Livewire\Disciplinary\Coordinations;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryAgendaThreadService;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
use App\Services\Disciplinary\DisciplinaryDecisionNotificationService;
use App\Support\Disciplinary\DecisionBranch;
use App\Support\Disciplinary\FieldDisciplinaryScopeService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Coordinaciones de Planeación')]
class Index extends Component
{
    use WithFileUploads;

    #[Url(as: 'thread')]
    public string $selectedThread = '';

    public string $agendaPlanningBody = '';

    /** @var array<int, array{date: string, time: string, notes: string}> */
    public array $planningSlots = [
        ['date' => '', 'time' => '', 'notes' => ''],
    ];

    /** @var array<int, array{date: string, time: string, notes: string, zone: string, supervisor_user_id: int|string|null}> */
    public array $decisionNotificationSlots = [
        ['date' => '', 'time' => '', 'notes' => '', 'zone' => '', 'supervisor_user_id' => null],
    ];

    /** @var array<int, mixed> */
    public array $agendaPlanningUploads = [];

    public string $notificationDate = '';

    public string $notificationShift = '';

    public string $notificationZone = '';

    public ?int $notificationSupervisorUserId = null;

    public string $notificationNotes = '';

    public bool $showDiligenceModal = false;

    public bool $showNotificationModal = false;

    public bool $showDecisionPlanningModal = false;

    public string $decisionSuspensionStart = '';

    public string $decisionSuspensionEnd = '';

    public string $decisionReliefNotes = '';

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user->hasRole('nivel3') && ! $user->hasRole('nivel1') && ! $user->hasPermissionTo('disciplinary.assign')) {
            abort(403);
        }
    }

    #[On('agenda-thread-refresh')]
    public function refreshFromBroadcast(): void
    {
        $ids = $this->openThreads()->pluck('id')->all();
        if ($this->selectedThread !== '' && ! in_array((int) $this->selectedThread, $ids, true)) {
            $this->selectedThread = '';
        }
    }

    public function selectThread(int $threadId): void
    {
        $this->selectedThread = (string) $threadId;
        $this->resetComposer();
        $this->showDiligenceModal = false;
        $this->showNotificationModal = false;
        $this->showDecisionPlanningModal = false;
    }

    public function openDecisionPlanningModal(): void
    {
        $this->showDecisionPlanningModal = true;
        $this->showDiligenceModal = false;
        $this->showNotificationModal = false;
        $this->decisionNotificationSlots = [
            ['date' => '', 'time' => '', 'notes' => '', 'zone' => '', 'supervisor_user_id' => null],
        ];
    }

    public function closeDecisionPlanningModal(): void
    {
        $this->showDecisionPlanningModal = false;
        $this->reset('decisionSuspensionStart', 'decisionSuspensionEnd', 'decisionReliefNotes');
        $this->decisionNotificationSlots = [
            ['date' => '', 'time' => '', 'notes' => '', 'zone' => '', 'supervisor_user_id' => null],
        ];
    }

    public function submitDecisionPlanningModal(DisciplinaryAgendaThreadService $agenda): void
    {
        $thread = $this->resolveSelectedThread();
        if (! $thread instanceof DisciplinaryAgendaThread) {
            $this->addError('decisionPlanningModal', 'Seleccione una coordinación abierta.');

            return;
        }

        $case = $thread->case()->firstOrFail();
        Gate::authorize('postAgendaPlanning', $case);

        $this->validate([
            'agendaPlanningBody' => ['nullable', 'string', 'max:8000'],
            'decisionNotificationSlots' => ['required', 'array', 'min:1', 'max:5'],
            'decisionNotificationSlots.*.date' => ['required', 'date'],
            'decisionNotificationSlots.*.time' => ['nullable', 'date_format:H:i'],
            'decisionNotificationSlots.*.notes' => ['required', 'string', 'max:80'],
            'decisionNotificationSlots.*.zone' => ['required', 'string', 'max:120'],
            'decisionNotificationSlots.*.supervisor_user_id' => ['required', 'integer', 'exists:users,id'],
            'decisionSuspensionStart' => ['nullable', 'date'],
            'decisionSuspensionEnd' => ['nullable', 'date', 'after_or_equal:decisionSuspensionStart'],
            'decisionReliefNotes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'decisionNotificationSlots.*.date' => 'fecha de notificación',
            'decisionNotificationSlots.*.notes' => 'turno',
            'decisionNotificationSlots.*.zone' => 'zona',
            'decisionNotificationSlots.*.supervisor_user_id' => 'supervisor de turno',
        ]);

        $branch = DecisionBranch::forDecision($case->decision);
        if ($branch !== null && DecisionBranch::requiresSuspensionDates($branch)) {
            $this->validate([
                'decisionSuspensionStart' => ['required', 'date'],
                'decisionSuspensionEnd' => ['required', 'date', 'after_or_equal:decisionSuspensionStart'],
            ]);
        }

        try {
            $agenda->postDecisionPlanningMessage(
                $case->fresh(['agendaThread']),
                auth()->user(),
                trim($this->agendaPlanningBody),
                $this->decisionNotificationSlots,
                array_filter([
                    'suspension_start' => $this->decisionSuspensionStart ?: null,
                    'suspension_end' => $this->decisionSuspensionEnd ?: null,
                    'relief_notes' => $this->decisionReliefNotes !== '' ? $this->decisionReliefNotes : null,
                ]),
                array_values(array_filter($this->agendaPlanningUploads)),
            );
        } catch (\Throwable $e) {
            $this->addError('decisionPlanningModal', $e->getMessage());

            return;
        }

        $this->closeDecisionPlanningModal();
        $this->resetComposer();
        session()->flash('success', 'Programación de decisión publicada en el chat.');
    }

    public function submitDecisionNotificationModal(DisciplinaryDecisionNotificationService $notification): void
    {
        $thread = $this->resolveSelectedThread();
        if (! $thread instanceof DisciplinaryAgendaThread) {
            $this->addError('notificationDate', 'Seleccione una coordinación abierta.');

            return;
        }

        $case = $thread->case()->firstOrFail();
        Gate::authorize('postDecisionNotificationCoordination', $case);

        $this->validate([
            'notificationDate' => ['required', 'date'],
            'notificationShift' => ['required', 'string', 'max:80'],
            'notificationZone' => ['required', 'string', 'max:120'],
            'notificationSupervisorUserId' => ['required', 'integer', 'exists:users,id'],
            'notificationNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $notification->completeNotificationInformation($case->fresh(['agendaThread']), auth()->user(), [
                'notification_date' => $this->notificationDate,
                'notification_shift' => $this->notificationShift,
                'notification_zone' => $this->notificationZone,
                'notification_supervisor_user_id' => (int) $this->notificationSupervisorUserId,
                'notification_notes' => $this->notificationNotes !== '' ? $this->notificationNotes : null,
            ]);
        } catch (\Throwable $e) {
            $this->addError('notificationDate', $e->getMessage());

            return;
        }

        $this->showNotificationModal = false;
        $this->resetNotificationForm();
        session()->flash('success', 'Notificación de decisión registrada.');
    }

    public function openDiligenceModal(): void
    {
        $this->showDiligenceModal = true;
        $this->showNotificationModal = false;
    }

    public function closeDiligenceModal(): void
    {
        $this->showDiligenceModal = false;
    }

    public function openNotificationModal(): void
    {
        $this->showNotificationModal = true;
        $this->showDiligenceModal = false;
    }

    public function closeNotificationModal(): void
    {
        $this->showNotificationModal = false;
    }

    public function addPlanningSlotRow(): void
    {
        if (count($this->planningSlots) >= 5) {
            return;
        }
        $this->planningSlots[] = ['date' => '', 'time' => '', 'notes' => ''];
    }

    public function addDecisionNotificationSlotRow(): void
    {
        if (count($this->decisionNotificationSlots) >= 5) {
            return;
        }
        $this->decisionNotificationSlots[] = [
            'date' => '',
            'time' => '',
            'notes' => '',
            'zone' => '',
            'supervisor_user_id' => null,
        ];
    }

    public function removeAgendaPlanningUploadAt(int $index): void
    {
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

    public function postPlanningChat(DisciplinaryAgendaThreadService $agenda): void
    {
        $thread = $this->resolveSelectedThread();
        if (! $thread instanceof DisciplinaryAgendaThread) {
            $this->addError('agendaPlanningBody', 'Seleccione una coordinación abierta.');

            return;
        }

        $case = $thread->case()->firstOrFail();
        Gate::authorize('postAgendaPlanning', $case);

        $this->validate([
            'agendaPlanningBody' => ['nullable', 'string', 'max:8000'],
            'agendaPlanningUploads' => ['nullable', 'array', 'max:6'],
            'agendaPlanningUploads.*' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
        ]);

        $body = trim($this->agendaPlanningBody);
        $files = array_values(array_filter($this->agendaPlanningUploads));

        if ($body === '' && $files === []) {
            $this->addError('agendaPlanningBody', 'Escriba un mensaje o adjunte al menos un archivo.');

            return;
        }

        try {
            $agenda->postPlanningMessage(
                $thread->case()->firstOrFail(),
                auth()->user(),
                $body,
                [],
                $files,
            );
        } catch (\Throwable $e) {
            $this->addError('agendaPlanningBody', $e->getMessage());

            return;
        }

        $this->reset('agendaPlanningBody', 'agendaPlanningUploads');
        session()->flash('success', 'Mensaje publicado en el chat.');
    }

    public function submitDiligenceModal(DisciplinaryAgendaThreadService $agenda): void
    {
        $thread = $this->resolveSelectedThread();
        if (! $thread instanceof DisciplinaryAgendaThread) {
            $this->addError('diligenceModal', 'Seleccione una coordinación abierta.');

            return;
        }

        $case = $thread->case()->firstOrFail();
        Gate::authorize('postAgendaPlanning', $case);

        $this->validate([
            'agendaPlanningBody' => ['nullable', 'string', 'max:8000'],
            'planningSlots' => ['required', 'array', 'min:1', 'max:5'],
            'planningSlots.*.date' => ['required', 'date'],
            'planningSlots.*.time' => ['nullable', 'date_format:H:i'],
            'planningSlots.*.notes' => ['nullable', 'string', 'max:500'],
            'agendaPlanningUploads' => ['nullable', 'array', 'max:6'],
            'agendaPlanningUploads.*' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
        ], [], [
            'planningSlots.*.date' => 'fecha de diligencia',
        ]);

        $body = trim($this->agendaPlanningBody);
        $files = array_values(array_filter($this->agendaPlanningUploads));

        try {
            $agenda->postPlanningMessage(
                $thread->case()->firstOrFail(),
                auth()->user(),
                $body,
                $this->planningSlots,
                $files,
            );
        } catch (\Throwable $e) {
            $this->addError('diligenceModal', $e->getMessage());

            return;
        }

        $this->showDiligenceModal = false;
        $this->resetComposer();
        session()->flash('success', 'Fechas de diligencia publicadas en el chat.');
    }

    public function submitNotificationModal(DisciplinaryCitationNotificationService $notification): void
    {
        $thread = $this->resolveSelectedThread();
        if (! $thread instanceof DisciplinaryAgendaThread) {
            $this->addError('notificationDate', 'Seleccione una coordinación abierta.');

            return;
        }

        $case = $thread->case()->firstOrFail();
        Gate::authorize('postNotificationCoordination', $case);

        $this->validate([
            'notificationDate' => ['required', 'date'],
            'notificationShift' => ['required', 'string', 'max:80'],
            'notificationZone' => ['required', 'string', 'max:120'],
            'notificationSupervisorUserId' => ['required', 'integer', 'exists:users,id'],
            'notificationNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $notification->completeNotificationInformation($case->fresh(['agendaThread']), auth()->user(), [
                'notification_date' => $this->notificationDate,
                'notification_shift' => $this->notificationShift,
                'notification_zone' => $this->notificationZone,
                'notification_supervisor_user_id' => (int) $this->notificationSupervisorUserId,
                'notification_notes' => $this->notificationNotes !== '' ? $this->notificationNotes : null,
            ]);
        } catch (\Throwable $e) {
            $this->addError('notificationDate', $e->getMessage());

            return;
        }

        $this->showNotificationModal = false;
        $this->resetNotificationForm();
        session()->flash('success', 'Información de notificación registrada en el chat y en el expediente.');
    }

    public function render()
    {
        $threads = $this->openThreads();

        if ($this->selectedThread === '' && $threads->isNotEmpty()) {
            $this->selectedThread = (string) $threads->first()->id;
        }

        $selectedThreadModel = $this->resolveSelectedThread();
        $citationNotificationService = app(DisciplinaryCitationNotificationService::class);
        $decisionNotificationService = app(DisciplinaryDecisionNotificationService::class);
        $pendingNotificationCase = $selectedThreadModel?->case;
        $isDecisionCase = $pendingNotificationCase?->current_status === CaseStatus::DECISION;
        $canPostPlanning = $pendingNotificationCase
            && auth()->user()->can('postAgendaPlanning', $pendingNotificationCase);
        $awaitingDiligenceDates = $pendingNotificationCase
            && ! $isDecisionCase
            && $pendingNotificationCase->awaitingPlanningDiligenceSlots();
        $awaitingDecisionPlanning = $pendingNotificationCase
            && $isDecisionCase
            && $pendingNotificationCase->awaitingDecisionPlanningSlots();
        $canRegisterNotification = $pendingNotificationCase
            && ! $isDecisionCase
            && auth()->user()->can('postNotificationCoordination', $pendingNotificationCase);
        $canRegisterDecisionNotification = $pendingNotificationCase
            && $isDecisionCase
            && auth()->user()->can('postDecisionNotificationCoordination', $pendingNotificationCase);
        $hasPendingNotification = $canRegisterNotification || $canRegisterDecisionNotification;
        $decisionBranch = $pendingNotificationCase?->decision
            ? DecisionBranch::forDecision($pendingNotificationCase->decision)
            : null;
        $liveCaseId = $pendingNotificationCase?->getKey();

        $scope = app(FieldDisciplinaryScopeService::class);
        $municipalityCode = $pendingNotificationCase?->employee?->municipality_code;
        $supervisorCandidates = $scope->applySupervisorCandidatesForMunicipality(
            User::query(),
            $municipalityCode,
        )->orderBy('name')->get(['id', 'name']);

        return view('livewire.disciplinary.coordinations.index', [
            'threads' => $threads,
            'selectedThreadModel' => $selectedThreadModel,
            'hasPendingNotification' => $hasPendingNotification,
            'canPostPlanning' => $canPostPlanning,
            'awaitingDiligenceDates' => $awaitingDiligenceDates,
            'awaitingDecisionPlanning' => $awaitingDecisionPlanning,
            'canRegisterNotification' => $canRegisterNotification,
            'canRegisterDecisionNotification' => $canRegisterDecisionNotification,
            'isDecisionCase' => $isDecisionCase,
            'decisionBranch' => $decisionBranch,
            'liveCaseId' => $liveCaseId,
            'supervisorCandidates' => $supervisorCandidates,
        ]);
    }

    private function resetComposer(): void
    {
        $this->reset('agendaPlanningBody', 'agendaPlanningUploads');
        $this->planningSlots = [['date' => '', 'time' => '', 'notes' => '']];
    }

    private function resetNotificationForm(): void
    {
        $this->reset(
            'notificationDate',
            'notificationShift',
            'notificationZone',
            'notificationSupervisorUserId',
            'notificationNotes',
        );
    }

    private function openThreads()
    {
        return DisciplinaryAgendaThread::query()
            ->where('coordination_status', 'open')
            ->with([
                'case:id,case_number,employee_id,municipality_code,city,assigned_lawyer_id,notification_requested_at,notification_information_completed_at,citation_confirmed_date,coordination_started_at,current_status,decision,decision_coordination_started_at,decision_notification_completed_at',
                'case.employee:id,first_name,last_name,document_number',
                'case.municipality:municipality_code,municipality_name',
                'messages.author:id,name',
                'messages.attachments',
            ])
            ->orderByDesc('coordination_started_at')
            ->get();
    }

    private function resolveSelectedThread(): ?DisciplinaryAgendaThread
    {
        $threadId = (int) $this->selectedThread;
        if ($threadId <= 0) {
            return null;
        }

        $thread = $this->openThreads()->firstWhere('id', $threadId);
        if (! $thread instanceof DisciplinaryAgendaThread) {
            return null;
        }

        return $thread->case ? $thread : null;
    }
}
