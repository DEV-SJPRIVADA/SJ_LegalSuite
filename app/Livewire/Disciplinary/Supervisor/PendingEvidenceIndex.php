<?php

namespace App\Livewire\Disciplinary\Supervisor;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CitationEvidenceType;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\DisciplinaryAuditService;
use App\Services\Disciplinary\DisciplinaryCitationWorkflowService;
use App\Services\Disciplinary\DisciplinaryDocumentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Evidencias pendientes')]
class PendingEvidenceIndex extends Component
{
    use WithFileUploads;

    /** @var array<int, string> */
    public array $citationEvidenceTypeByCase = [];

    /** @var array<int, mixed> */
    public array $citationEvidenceFileByCase = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('supervisor'), 403);
    }

    public function uploadCitationEvidence(
        int $caseId,
        DisciplinaryDocumentService $documents,
        DisciplinaryCitationWorkflowService $citationWorkflow,
        DisciplinaryAuditService $audit,
    ): void {
        abort_unless(auth()->user()->hasRole('supervisor'), 403);

        $case = DisciplinaryCase::query()
            ->with(['documents', 'informeSubmission'])
            ->whereKey($caseId)
            ->where('notification_supervisor_user_id', auth()->id())
            ->firstOrFail();

        Gate::authorize('uploadCitationEvidence', $case);
        $citationWorkflow->assertCitationEvidenceUploadAllowed($case, auth()->user());

        $this->validate([
            "citationEvidenceTypeByCase.{$caseId}" => ['required', Rule::in(['signed', 'refused_witnesses'])],
            "citationEvidenceFileByCase.{$caseId}" => ['required', 'file', 'mimes:pdf', 'max:15360'],
        ]);

        $type = CitationEvidenceType::from((string) $this->citationEvidenceTypeByCase[$caseId]);
        $stage = $case->stages()
            ->where('stage_type', StageType::CITACION)
            ->orderByDesc('sequence')
            ->first();

        $uploader = auth()->user();
        $doc = $documents->upload(
            $case,
            $this->citationEvidenceFileByCase[$caseId],
            DocumentType::CITACION,
            $uploader,
            $stage,
            DisciplinaryCase::NOTE_CITATION_EVIDENCE_PREFIX.' - '.$type->label(),
        );

        $case = $citationWorkflow->markEvidenceUploaded($case->fresh(), $type);

        $audit->logCase(
            $case,
            $uploader,
            ActionType::EVIDENCIA_CITACION_CARGADA,
            'Evidencia PDF de citacion cargada.',
            [
                'evidence_type' => $type->value,
                'document_id' => $doc->id,
                'uploaded_by' => $uploader->id,
                'uploaded_at' => now()->toIso8601String(),
                'fo_gj_03_document_id' => $case->primaryFoGj03CitationDocument()?->id,
            ],
        );

        unset($this->citationEvidenceTypeByCase[$caseId], $this->citationEvidenceFileByCase[$caseId]);
        session()->flash('success', "Evidencia cargada para {$case->case_number}.");
    }

    public function render()
    {
        abort_unless(auth()->user()->hasRole('supervisor'), 403);

        $tasks = DisciplinaryCase::query()
            ->where('notification_supervisor_user_id', auth()->id())
            ->whereNotNull('fo_gj_03_generated_at')
            ->whereNull('citation_evidence_uploaded_at')
            ->whereHas('documents', fn ($q) => $q
                ->where('document_type', DocumentType::CITACION)
                ->where('notes', 'like', '%'.DisciplinaryCase::NOTE_FO_GJ_03_GENERATED.'%'))
            ->with(['employee:id,first_name,last_name'])
            ->orderByDesc('fo_gj_03_generated_at')
            ->paginate(12);

        return view('livewire.disciplinary.supervisor.pending-evidence-index', [
            'tasks' => $tasks,
        ]);
    }
}
