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

    public function postPlanningReply(DisciplinaryAgendaThreadService $agenda): void
    {
        $thread = $this->selectedThreadModel();
        if (! $thread instanceof DisciplinaryAgendaThread) {
            $this->addError('agendaPlanningBody', 'Seleccione una coordinación abierta.');

            return;
        }

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
                $thread->case()->firstOrFail(),
                auth()->user(),
                $body,
                $this->planningSlots,
                $files,
            );
        } catch (\Throwable $e) {
            $this->addError('agendaPlanningBody', $e->getMessage());

            return;
        }

        $this->resetComposer();
        session()->flash('success', 'Respuesta publicada en la coordinación.');
    }

    public function postNotificationCoordination(DisciplinaryCitationNotificationService $notification): void
    {
        $thread = $this->selectedThreadModel();
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

        $this->resetNotificationForm();
        session()->flash('success', 'Información de notificación registrada.');
    }

    public function render()
    {
        $threads = $this->openThreads();

        if ($this->selectedThread === '' && $threads->isNotEmpty()) {
            $this->selectedThread = (string) $threads->first()->id;
        }

        $selectedThreadModel = $this->selectedThreadModel();
        $notificationService = app(DisciplinaryCitationNotificationService::class);
        $pendingNotificationCase = $selectedThreadModel?->case;
        $hasPendingNotification = $pendingNotificationCase
            && $notificationService->hasPendingNotificationRequest($pendingNotificationCase);

        return view('livewire.disciplinary.coordinations.index', [
            'threads' => $threads,
            'selectedThreadModel' => $selectedThreadModel,
            'hasPendingNotification' => $hasPendingNotification,
            'supervisorCandidates' => User::query()->role('supervisor')->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function resetComposer(): void
    {
        $this->reset('agendaPlanningBody', 'agendaPlanningUploads');
        $this->planningSlots = [['date' => '', 'time' => '', 'notes' => '']];
        $this->resetNotificationForm();
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
                'case:id,case_number,employee_id,municipality_code,city,assigned_lawyer_id,notification_requested_at,notification_information_completed_at,citation_confirmed_date',
                'case.employee:id,first_name,last_name,document_number',
                'case.municipality:municipality_code,municipality_name',
                'messages.author:id,name',
                'messages.attachments',
            ])
            ->orderByDesc('coordination_started_at')
            ->get();
    }

    private function selectedThreadModel(): ?DisciplinaryAgendaThread
    {
        $threadId = (int) $this->selectedThread;
        if ($threadId <= 0) {
            return null;
        }

        $thread = $this->openThreads()->firstWhere('id', $threadId);
        if (! $thread instanceof DisciplinaryAgendaThread) {
            return null;
        }

        $case = $thread->case;
        if (! $case) {
            return null;
        }
        Gate::authorize('postAgendaPlanning', $case);

        return $thread;
    }
}
