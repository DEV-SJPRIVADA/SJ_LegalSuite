<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\InformeSubmissionStatus;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\Fault;
use App\Models\Disciplinary\InformeSubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DisciplinaryInformeSubmissionService
{
    public function __construct(
        private readonly DisciplinaryCaseService $cases,
        private readonly DisciplinaryWorkflowService $workflow,
        private readonly DisciplinaryDocumentService $documents,
    ) {}

    /**
     * @param  array<string, mixed>  $formSnapshot
     */
    public function storePending(
        UploadedFile $file,
        User $submitter,
        int $personnelId,
        array $formSnapshot = [],
        ?string $summary = null,
    ): InformeSubmission {
        return DB::transaction(function () use ($file, $submitter, $personnelId, $formSnapshot, $summary) {
            $submission = InformeSubmission::create([
                'submitted_by' => $submitter->id,
                'personnel_id' => $personnelId,
                'status' => InformeSubmissionStatus::PENDIENTE_REVISION,
                'storage_disk' => 'local',
                'storage_path' => '',
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'checksum_sha256' => hash_file('sha256', $file->getRealPath()),
                'form_snapshot' => $formSnapshot ?: null,
                'summary' => $summary,
            ]);

            $relativeDir = "disciplinary/informes-pendientes/{$submission->id}";
            $path = Storage::disk('local')->putFile($relativeDir, $file);

            $submission->forceFill(['storage_path' => $path])->save();

            return $submission->fresh(['personnel', 'submitter']);
        });
    }

    public function reject(InformeSubmission $submission, User $reviewer, ?string $notes = null): void
    {
        if ($submission->status !== InformeSubmissionStatus::PENDIENTE_REVISION) {
            throw new \RuntimeException('El informe no está pendiente de revisión.');
        }

        DB::transaction(function () use ($submission, $reviewer, $notes) {
            $this->deleteStoredFile($submission);

            $submission->forceFill([
                'status' => InformeSubmissionStatus::RECHAZADO,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'reviewer_notes' => $notes,
                'storage_path' => '',
            ])->save();

            $submission->delete();
        });
    }

    public function authorizeAndCreateCase(InformeSubmission $submission, User $reviewer, ?string $notes = null): DisciplinaryCase
    {
        if ($submission->status !== InformeSubmissionStatus::PENDIENTE_REVISION) {
            throw new \RuntimeException('El informe no está pendiente de revisión.');
        }

        return DB::transaction(function () use ($submission, $reviewer, $notes) {
            $submission->loadMissing('submitter');

            $snapshot = $submission->form_snapshot ?? [];
            $extra = $snapshot['fo51_observations'] ?? $submission->summary;
            $extra = Str::limit(trim((string) $extra), 900) ?: 'Informe disciplinario FO-GJ-51 — detalle en documento adjunto.';

            $fault = Fault::query()->where('code', 'F-006')->firstOrFail();

            $case = $this->cases->create(
                $submission->submitter,
                [
                    'personnel_id' => $submission->personnel_id,
                    'assigned_lawyer_id' => null,
                    'city' => isset($snapshot['fo51_city']) ? Str::limit((string) $snapshot['fo51_city'], 100) : null,
                    'sede' => null,
                    'opened_at' => now()->toDateString(),
                    'summary' => Str::limit(trim((string) ($submission->summary ?? $snapshot['fo51_observations'] ?? '')), 5000) ?: null,
                ],
                [
                    ['fault_id' => $fault->id, 'extra_info' => $extra],
                ],
            );

            $case = $this->workflow->transition(
                $case,
                CaseStatus::INFORME,
                $reviewer,
                $notes ?? 'Informe disciplinario autorizado por dirección; caso creado en etapa de informe.',
                ['informe_submission_id' => $submission->id],
            );

            $informeStage = $case->stages()
                ->where('stage_type', StageType::INFORME)
                ->orderByDesc('sequence')
                ->first();

            $absolute = Storage::disk($submission->storage_disk)->path($submission->storage_path);
            if (! is_file($absolute)) {
                throw new \RuntimeException('No se encuentra el PDF del informe en almacenamiento.');
            }

            $filename = str_replace(["\r", "\n", '"'], '', (string) ($submission->original_filename ?: 'FO-GJ-51-informe.pdf'))
                ?: 'FO-GJ-51-informe.pdf';

            $uploaded = new UploadedFile(
                $absolute,
                basename($filename),
                $submission->mime_type ?? 'application/pdf',
                UPLOAD_ERR_OK,
                true
            );

            $this->documents->upload(
                $case,
                $uploaded,
                DocumentType::INFORME,
                $reviewer,
                $informeStage,
                'FO-GJ-51 autorizado por dirección',
            );

            $this->deleteStoredFile($submission);

            $submission->forceFill([
                'status' => InformeSubmissionStatus::AUTORIZADO,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'reviewer_notes' => $notes,
                'disciplinary_case_id' => $case->id,
                'storage_path' => '',
            ])->save();

            return $case->fresh(['personnel']);
        });
    }

    private function deleteStoredFile(InformeSubmission $submission): void
    {
        if ($submission->storage_path === '') {
            return;
        }

        Storage::disk($submission->storage_disk)->delete($submission->storage_path);

        $dir = dirname($submission->storage_path);
        Storage::disk($submission->storage_disk)->deleteDirectory($dir);
    }
}
