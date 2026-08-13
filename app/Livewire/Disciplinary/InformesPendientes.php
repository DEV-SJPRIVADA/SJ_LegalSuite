<?php

namespace App\Livewire\Disciplinary;

use App\Models\Disciplinary\InformeSubmission;
use App\Services\Disciplinary\DisciplinaryInformeSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Revisión informes · SJ LegalSuite')]
class InformesPendientes extends Component
{
    use WithPagination;

    public ?int $rejectId = null;

    public string $rejectNotes = '';

    /** Solicitud pendiente para confirmación en modal (autorizar). */
    public ?int $approveConfirmId = null;

    /** Modal de vista previa del PDF. */
    public ?int $previewSubmissionId = null;

    /** Evidencias asociadas al envío actualmente en vista previa. */
    public array $previewEvidencePaths = [];

    public string $search = '';

    public int $perPage = 20;

    public function mount(): void
    {
        Gate::authorize('viewAny', InformeSubmission::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
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
        $this->previewSubmissionId = null;
        $this->previewEvidencePaths = [];
        $this->rejectId = $id;
        $this->rejectNotes = '';
        $this->resetErrorBag();
    }

    public function cancelReject(): void
    {
        $this->rejectId = null;
        $this->rejectNotes = '';
        $this->resetErrorBag();
    }

    public function openApproveConfirm(int $id): void
    {
        $submission = InformeSubmission::pendingReview()->findOrFail($id);
        Gate::authorize('review', $submission);

        $this->previewSubmissionId = null;
        $this->previewEvidencePaths = [];
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
        $this->rejectId = null;
        $this->rejectNotes = '';
        $this->previewSubmissionId = $id;
        $this->previewEvidencePaths = $submission->evidence_paths ?? [];
    }

    public function closePdfPreview(): void
    {
        $this->previewSubmissionId = null;
        $this->previewEvidencePaths = [];
    }

    #[Computed]
    public function pendingCount(): int
    {
        return $this->basePendingQuery()->count();
    }

    public function rejectTarget(): ?InformeSubmission
    {
        if ($this->rejectId === null) {
            return null;
        }

        return InformeSubmission::query()
            ->pendingReview()
            ->with(['employee:id,first_name,last_name,document_number'])
            ->find($this->rejectId);
    }

    public function approveTarget(): ?InformeSubmission
    {
        if ($this->approveConfirmId === null) {
            return null;
        }

        return InformeSubmission::query()
            ->pendingReview()
            ->with(['employee:id,first_name,last_name,document_number'])
            ->find($this->approveConfirmId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Disciplinary\InformeSubmission>
     */
    private function basePendingQuery()
    {
        $query = InformeSubmission::query()->pendingReview();

        $user = auth()->user();
        if (! $user->hasRole('nivel1') && ! $user->can('disciplinary.review-inform-all')) {
            $query->where('assigned_reviewer_id', $user->id);
        }

        return $query;
    }

    public function render(): View
    {
        $query = $this->basePendingQuery()
            ->with([
                'submitter:id,name,email',
                'assignedReviewer:id,name',
                'employee:id,first_name,last_name,document_number',
            ]);

        if ($this->search !== '') {
            $term = '%'.str_replace(' ', '%', trim($this->search)).'%';
            $query->where(function ($q) use ($term): void {
                $q->whereHas('employee', function ($employee) use ($term): void {
                    $employee->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('document_number', 'like', $term);
                })->orWhereHas('submitter', function ($user) use ($term): void {
                    $user->where('name', 'like', $term);
                });
            });
        }

        $pending = $query->orderByDesc('created_at')->paginate($this->perPage);

        return view('livewire.disciplinary.informes-pendientes', [
            'pending' => $pending,
            'pendingCount' => $this->pendingCount,
            'rejectTarget' => $this->rejectTarget(),
            'approveTarget' => $this->approveTarget(),
        ]);
    }
}
