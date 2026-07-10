<?php

namespace App\Livewire\Disciplinary\Administrativa;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\DisciplinaryDecisionWorkflowService;
use App\Support\Disciplinary\DecisionBranch;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Gestión humana · Decisiones')]
class PendingDecisionHrIndex extends Component
{
    use WithFileUploads;

    /** @var array<int, mixed> */
    public array $hrAnnexFileByCase = [];

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user->hasRole('nivel4') && ! $user->hasRole('nivel1')) {
            abort(403);
        }
    }

    public function uploadHrAnnex(int $caseId, DisciplinaryDecisionWorkflowService $workflow): void
    {
        $case = DisciplinaryCase::query()
            ->with('documents')
            ->whereKey($caseId)
            ->where('current_status', CaseStatus::DECISION)
            ->whereNull('decision_hr_review_completed_at')
            ->firstOrFail();

        $this->authorize('uploadDecisionHrAnnex', $case);

        $this->validate([
            "hrAnnexFileByCase.{$caseId}" => ['required', 'file', 'mimes:pdf', 'max:15360'],
        ]);

        try {
            $workflow->uploadHrAnnex($case, auth()->user(), $this->hrAnnexFileByCase[$caseId]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? 'Archivo no válido.');
            }

            return;
        }

        unset($this->hrAnnexFileByCase[$caseId]);
        session()->flash('success', "Anexo laboral cargado para {$case->case_number}.");
    }

    public function completeHrReview(int $caseId, DisciplinaryDecisionWorkflowService $workflow): void
    {
        $case = DisciplinaryCase::query()
            ->with('documents')
            ->whereKey($caseId)
            ->where('current_status', CaseStatus::DECISION)
            ->whereNull('decision_hr_review_completed_at')
            ->firstOrFail();

        $this->authorize('completeDecisionHrReview', $case);

        try {
            $workflow->completeHrReview($case, auth()->user());
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('success', "Gestión humana completada para {$case->case_number}.");
    }

    public function render()
    {
        $tasks = DisciplinaryCase::query()
            ->where('current_status', CaseStatus::DECISION)
            ->whereNull('decision_hr_review_completed_at')
            ->whereNotNull('decision_comunicado_generated_at')
            ->whereIn('decision', collect(DecisionBranch::choicesForBranch(DecisionBranch::TERMINATION))->map->value)
            ->with(['employee:id,first_name,last_name,document_number', 'documents'])
            ->orderByDesc('decision_comunicado_generated_at')
            ->get();

        return view('livewire.disciplinary.administrativa.pending-decision-hr-index', [
            'tasks' => $tasks,
        ]);
    }
}
