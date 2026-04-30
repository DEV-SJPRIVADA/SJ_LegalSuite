<?php

namespace App\Livewire\Disciplinary\Cases;

use App\Enums\Disciplinary\CaseStatus;
use App\Exceptions\Disciplinary\InvalidStateTransitionException;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\DisciplinaryWorkflowService;
use App\Workflow\Disciplinary\TransitionMap;
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

    public function mount(DisciplinaryCase $case): void
    {
        Gate::authorize('view', $case);
        $this->case = $case;
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

    public function render()
    {
        $this->case->load([
            'personnel',
            'reporter:id,name',
            'assignedLawyer:id,name',
            'faults',
            'stages.performer:id,name',
            'documents.uploader:id,name',
            'actions.user:id,name',
            'actions.stage:id,stage_type',
        ]);

        $allowed = TransitionMap::allowedFrom($this->case->current_status);

        return view('livewire.disciplinary.cases.show', [
            'allowedTransitions' => $allowed,
        ]);
    }
}
