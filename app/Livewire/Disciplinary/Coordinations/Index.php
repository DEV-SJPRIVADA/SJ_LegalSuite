<?php

namespace App\Livewire\Disciplinary\Coordinations;

use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryAgendaThreadService;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
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

    /** @var array<int, mixed> */
    public array $agendaPlanningUploads = [];

    public string $notificationDate = '';

    public string $notificationShift = '';

    public string $notificationZone = '';

    public ?int $notificationSupervisorUserId = null;

    public string $notificationNotes = '';

    public bool $showDiligenceModal = false;

    public bool $showNotificationModal = false;

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user->hasRole('planeacion') && ! $user->hasRole('admin') && ! $user->hasPermissionTo('disciplinary.assign')) {
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
        $notificationService = app(DisciplinaryCitationNotificationService::class);
        $pendingNotificationCase = $selectedThreadModel?->case;
        $canPostPlanning = $pendingNotificationCase
            && auth()->user()->can('postAgendaPlanning', $pendingNotificationCase);
        $awaitingDiligenceDates = $pendingNotificationCase
            && $pendingNotificationCase->awaitingPlanningDiligenceSlots();
        $canRegisterNotification = $pendingNotificationCase
            && auth()->user()->can('postNotificationCoordination', $pendingNotificationCase);
        $hasPendingNotification = $canRegisterNotification;
        $liveCaseId = $pendingNotificationCase?->getKey();

        return view('livewire.disciplinary.coordinations.index', [
            'threads' => $threads,
            'selectedThreadModel' => $selectedThreadModel,
            'hasPendingNotification' => $hasPendingNotification,
            'canPostPlanning' => $canPostPlanning,
            'awaitingDiligenceDates' => $awaitingDiligenceDates,
            'canRegisterNotification' => $canRegisterNotification,
            'liveCaseId' => $liveCaseId,
            'supervisorCandidates' => User::query()->role('supervisor')->active()->orderBy('name')->get(['id', 'name']),
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
                'case:id,case_number,employee_id,municipality_code,city,assigned_lawyer_id,notification_requested_at,notification_information_completed_at,citation_confirmed_date,coordination_started_at,current_status',
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
