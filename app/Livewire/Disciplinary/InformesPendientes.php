<?php

namespace App\Livewire\Disciplinary;

use App\Models\Disciplinary\InformeSubmission;
use App\Services\Disciplinary\DisciplinaryInformeSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Informes pendientes')]
class InformesPendientes extends Component
{
    public ?int $rejectId = null;

    public string $rejectNotes = '';

    /** Solicitud pendiente para confirmación en modal (autorizar). */
    public ?int $approveConfirmId = null;

    /** Modal de vista previa del PDF. */
    public ?int $previewSubmissionId = null;

    /** Evidencias asociadas al envío actualmente en vista previa. */
    public array $previewEvidencePaths = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', InformeSubmission::class);
    }

    public function approve(int $id, DisciplinaryInformeSubmissionService $service): void
    {
        $submission = InformeSubmission::pendingReview()->findOrFail($id);
        Gate::authorize('review', $submission);

        $case = $service->authorizeAndCreateCase($submission, auth()->user());

        session()->flash('success', 'Informe autorizado. Se creó el expediente en etapa Informe disciplinario.');

        $this->redirect(route('disciplinary.cases.show', $case), navigate: true);
    }

    public function reject(DisciplinaryInformeSubmissionService $service): void
    {
        $this->validate([
            'rejectId' => ['required', 'integer'],
            'rejectNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $submission = InformeSubmission::pendingReview()->findOrFail((int) $this->rejectId);
        Gate::authorize('review', $submission);

        $service->reject($submission, auth()->user(), $this->rejectNotes !== '' ? $this->rejectNotes : null);

        $this->reset(['rejectId', 'rejectNotes']);
        session()->flash('success', 'El informe fue rechazado; el archivo se eliminó del sistema.');
    }

    public function openReject(int $id): void
    {
        $submission = InformeSubmission::pendingReview()->findOrFail($id);
        Gate::authorize('review', $submission);

        $this->approveConfirmId = null;
        $this->rejectId = $id;
        $this->rejectNotes = '';
    }

    public function cancelReject(): void
    {
        $this->rejectId = null;
        $this->rejectNotes = '';
    }

    public function openApproveConfirm(int $id): void
    {
        $submission = InformeSubmission::pendingReview()->findOrFail($id);
        Gate::authorize('review', $submission);

        $this->previewSubmissionId = null;
        $this->rejectId = null;
        $this->rejectNotes = '';
        $this->approveConfirmId = $id;
    }

    public function cancelApproveConfirm(): void
    {
        $this->approveConfirmId = null;
    }

    public function confirmApprove(DisciplinaryInformeSubmissionService $service): void
    {
        if ($this->approveConfirmId === null) {
            return;
        }

        $id = $this->approveConfirmId;
        $this->approveConfirmId = null;

        $this->approve($id, $service);
    }

    public function openPdfPreview(int $id): void
    {
        $submission = InformeSubmission::pendingReview()->findOrFail($id);
        Gate::authorize('review', $submission);

        if ($submission->storage_path === '') {
            abort(404);
        }

        $this->approveConfirmId = null;
        $this->previewSubmissionId = $id;
        $this->previewEvidencePaths = $submission->evidence_paths ?? [];
    }

    public function closePdfPreview(): void
    {
        $this->previewSubmissionId = null;
        $this->previewEvidencePaths = [];
    }

    public function render(): View
    {
        $pending = InformeSubmission::query()
            ->pendingReview()
            ->with([
                'submitter:id,name,email',
                'employee:id,first_name,last_name,document_number',
            ])
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.disciplinary.informes-pendientes', [
            'pending' => $pending,
        ]);
    }
}
