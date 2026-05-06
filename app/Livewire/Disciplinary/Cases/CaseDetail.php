<?php

namespace App\Livewire\Disciplinary\Cases;

use App\Enums\Disciplinary\CaseStatus;
use App\Exceptions\Disciplinary\InvalidStateTransitionException;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryStage;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryWorkflowService;
use App\Workflow\Disciplinary\TransitionMap;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detalle del caso')]
class CaseDetail extends Component
{
    public DisciplinaryCase $case;

    public string $activeTab = 'overview';

    /* Modal de transición */
    public bool $showTransition = false;

    public string $newStatus = '';

    public string $note = '';

    public string $scheduledAt = '';

    public string $deadlineAt = '';

    /* Modal programación de fechas (Planeación / Jurídico) */
    public bool $showScheduleModal = false;

    public ?int $scheduleStageId = null;

    public string $scheduleAt = '';

    public string $scheduleDeadline = '';

    public string $scheduleNote = '';

    public ?int $assignedOperatorId = null;

    public ?int $assignedPlannerId = null;

    public function mount(DisciplinaryCase $case): void
    {
        Gate::authorize('view', $case);
        $this->case = $case;
        $this->assignedOperatorId = $case->assigned_operator_id;
        $this->assignedPlannerId = $case->assigned_planner_id;

        if (auth()->user()->isDisciplinaryProgramador()) {
            $this->activeTab = 'timeline';
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openTransition(): void
    {
        Gate::authorize('transition', $this->case);
        $this->reset(['newStatus', 'note', 'scheduledAt', 'deadlineAt']);
        $this->showTransition = true;
    }

    public function closeTransition(): void
    {
        $this->showTransition = false;
    }

    public function saveTransition(DisciplinaryWorkflowService $workflow): void
    {
        Gate::authorize('transition', $this->case);

        $this->validate([
            'newStatus' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:2000'],
            'scheduledAt' => ['nullable', 'date'],
            'deadlineAt' => ['nullable', 'date'],
        ]);

        try {
            $to = CaseStatus::from($this->newStatus);
            $this->case = $workflow->transition(
                $this->case->fresh(),
                $to,
                auth()->user(),
                $this->note ?: null,
                [],
                scheduledAt: $this->scheduledAt ? Carbon::parse($this->scheduledAt) : null,
                deadlineAt: $this->deadlineAt ? Carbon::parse($this->deadlineAt) : null,
            );

            $this->showTransition = false;
            session()->flash('success', "Caso transicionado a: {$to->label()}");
        } catch (InvalidStateTransitionException $e) {
            $this->addError('newStatus', $e->getMessage());
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

    public function saveFieldOperatorAssignment(): void
    {
        Gate::authorize('assignFieldOperator', $this->case);

        $this->validate([
            'assignedOperatorId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($this->assignedOperatorId !== null) {
            $candidate = User::query()->find($this->assignedOperatorId);
            if (! $candidate || ! $candidate->hasAnyRole(['supervisor', 'operador'])) {
                $this->addError('assignedOperatorId', 'Seleccione un usuario con rol supervisor u operador.');

                return;
            }
        }

        $this->case->forceFill([
            'assigned_operator_id' => $this->assignedOperatorId,
        ])->save();

        $this->syncCaseFromDb();
        session()->flash('success', 'Responsable de campo actualizado.');
    }

    public function savePlannerAssignment(): void
    {
        Gate::authorize('assignPlanner', $this->case);

        $this->validate([
            'assignedPlannerId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($this->assignedPlannerId !== null) {
            $candidate = User::query()->find($this->assignedPlannerId);
            if (! $candidate || ! $candidate->hasRole('programador')) {
                $this->addError('assignedPlannerId', 'Seleccione un usuario con rol programador.');

                return;
            }
        }

        $this->case->forceFill([
            'assigned_planner_id' => $this->assignedPlannerId,
        ])->save();

        $this->syncCaseFromDb();
        session()->flash('success', 'Programador asignado para este proceso.');
    }

    private function syncCaseFromDb(): void
    {
        $this->case = $this->case->fresh([
            'personnel',
            'reporter:id,name',
            'assignedLawyer:id,name',
            'assignedOperator:id,name',
            'assignedPlanner:id,name',
            'faults',
            'stages.performer:id,name',
            'documents.uploader:id,name',
            'actions.user:id,name',
            'actions.stage:id,stage_type',
        ]) ?? $this->case;

        $this->assignedOperatorId = $this->case->assigned_operator_id;
        $this->assignedPlannerId = $this->case->assigned_planner_id;
    }

    public function render()
    {
        $this->case->load([
            'personnel',
            'reporter:id,name',
            'assignedLawyer:id,name',
            'assignedOperator:id,name',
            'assignedPlanner:id,name',
            'faults',
            'stages.performer:id,name',
            'documents.uploader:id,name',
            'actions.user:id,name',
            'actions.stage:id,stage_type',
        ]);

        $allowed = TransitionMap::allowedFrom($this->case->current_status);

        return view('livewire.disciplinary.cases.show', [
            'allowedTransitions' => $allowed,
            'relatedCases' => $this->relatedCasesSameDocument(),
            'fieldOperatorCandidates' => Gate::allows('assignFieldOperator', $this->case)
                ? User::query()->role(['supervisor', 'operador'])->active()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'plannerCandidates' => Gate::allows('assignPlanner', $this->case)
                ? User::query()->role('programador')->active()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    /**
     * Otros procesos disciplinarios con el mismo número de documento (misma persona en el sistema).
     *
     * @return Collection<int, DisciplinaryCase>
     */
    private function relatedCasesSameDocument()
    {
        $this->case->loadMissing('personnel');
        $doc = $this->case->personnel?->document_number;
        if (! filled($doc)) {
            return collect();
        }

        return DisciplinaryCase::query()
            ->forDisciplinaryActor(auth()->user())
            ->with(['personnel:id,first_name,last_name,document_number', 'assignedLawyer:id,name'])
            ->where('disciplinary_cases.id', '!=', $this->case->getKey())
            ->whereHas('personnel', fn ($q) => $q->where('document_number', $doc))
            ->orderByDesc('opened_at')
            ->limit(50)
            ->get();
    }
}
