<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\InformeSubmissionStatus;
use App\Enums\Disciplinary\StageType;
use App\Enums\PlatformLevel;
use App\Models\ColombianMunicipality;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryDocument;
use App\Models\Disciplinary\InformeSubmission;
use App\Models\User;
use App\Notifications\InformeAuthorizedNotification;
use App\Notifications\InformePendingReviewNotification;
use App\Notifications\InformeRejectedNotification;
use App\Support\Disciplinary\FoGj51SnapshotFaultMapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DisciplinaryInformeSubmissionService
{
    public function __construct(
        private readonly DisciplinaryCaseService $cases,
        private readonly DisciplinaryWorkflowService $workflow,
        private readonly DisciplinaryDocumentService $documents,
        private readonly DisciplinaryAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $formSnapshot
     * @param  list<UploadedFile>  $evidenceImages
     */
    public function storePending(
        UploadedFile $file,
        User $submitter,
        int $employeeId,
        int $assignedReviewerId,
        array $formSnapshot = [],
        ?string $summary = null,
        array $evidenceImages = [],
    ): InformeSubmission {
        $reviewer = User::query()->whereKey($assignedReviewerId)->where('is_active', true)->first();
        if (! $reviewer || ! $reviewer->hasPlatformLevel(PlatformLevel::Nivel2)) {
            throw new \InvalidArgumentException('Seleccione un revisor de operaciones válido.');
        }

        return DB::transaction(function () use ($file, $submitter, $employeeId, $assignedReviewerId, $formSnapshot, $summary, $evidenceImages, $reviewer) {
            $submission = InformeSubmission::create([
                'submitted_by' => $submitter->id,
                'assigned_reviewer_id' => $assignedReviewerId,
                'employee_id' => $employeeId,
                'status' => InformeSubmissionStatus::PENDIENTE_REVISION,
                'storage_disk' => 'local',
                'storage_path' => '',
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'checksum_sha256' => hash_file('sha256', $file->getRealPath()),
                'form_snapshot' => $formSnapshot ?: null,
                'evidence_paths' => null,
                'summary' => $summary,
            ]);

            $relativeDir = "disciplinary/informes-pendientes/{$submission->id}";
            $path = Storage::disk('local')->putFile($relativeDir, $file);

            $submission->forceFill(['storage_path' => $path])->save();

            $evidencePaths = [];
            foreach ($evidenceImages as $evidenceImage) {
                if (! $evidenceImage instanceof UploadedFile || ! $evidenceImage->isValid()) {
                    continue;
                }
                $stored = Storage::disk('local')->putFile("{$relativeDir}/evidence", $evidenceImage);
                if ($stored !== false && $stored !== '') {
                    $evidencePaths[] = $stored;
                }
            }

            if ($evidencePaths !== []) {
                $submission->forceFill(['evidence_paths' => $evidencePaths])->save();
            }

            $submission = $submission->fresh(['employee', 'submitter', 'assignedReviewer']);

            $this->audit->logInforme(
                $submission,
                $submitter,
                ActionType::INFORME_ENVIADO,
                "Informe enviado a revisión de {$reviewer->name}.",
                ['assigned_reviewer_id' => $assignedReviewerId],
            );

            Notification::send(
                collect([$reviewer])->filter(),
                new InformePendingReviewNotification($submission),
            );

            $directors = $this->usersWithInformDirectoryVisibility()
                ->whereKeyNot($reviewer->id)
                ->whereKeyNot($submitter->id)
                ->get();

            if ($directors->isNotEmpty()) {
                Notification::send($directors, new InformePendingReviewNotification($submission));
            }

            return $submission;
        });
    }

    public function reject(InformeSubmission $submission, User $reviewer, ?string $notes = null): void
    {
        if ($submission->status !== InformeSubmissionStatus::PENDIENTE_REVISION) {
            throw new \RuntimeException('El informe no está pendiente de revisión.');
        }

        DB::transaction(function () use ($submission, $reviewer, $notes) {
            $submitter = User::query()->find($submission->submitted_by);

            $this->deleteStoredFile($submission);

            $submission->forceFill([
                'status' => InformeSubmissionStatus::RECHAZADO,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'reviewer_notes' => $notes,
                'storage_path' => '',
                'evidence_paths' => null,
            ])->save();

            $this->audit->logInforme(
                $submission,
                $reviewer,
                ActionType::INFORME_CANCELADO,
                'Informe cancelado en revisión de operaciones.',
                ['notes' => $notes],
            );

            if ($submitter instanceof User) {
                Notification::send($submitter, new InformeRejectedNotification($submission->fresh(['employee']), $notes));
            }

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
            $observationsForFaults = Str::limit(trim((string) ($snapshot['fo51_observations'] ?? $submission->summary ?? '')), 900)
                ?: 'Informe disciplinario FO-GJ-51 — detalle en documento adjunto.';

            $faultPivotRows = FoGj51SnapshotFaultMapper::pivotRowsFromSnapshot($snapshot, $observationsForFaults);

            $munCode = isset($snapshot['fo51_municipality_code']) ? trim((string) $snapshot['fo51_municipality_code']) : '';
            $municipalityCode = (preg_match('/^\d{5}$/', $munCode) === 1) ? $munCode : null;
            $cityLabel = null;
            if ($municipalityCode !== null) {
                $cityLabel = ColombianMunicipality::query()
                    ->where('municipality_code', $municipalityCode)
                    ->value('municipality_name');
            }
            if ($cityLabel === null || $cityLabel === '') {
                $legacy = isset($snapshot['fo51_city']) ? trim((string) $snapshot['fo51_city']) : '';
                $cityLabel = $legacy !== '' ? Str::limit($legacy, 100) : null;
            }

            $case = $this->cases->create(
                $submission->submitter,
                [
                    'employee_id' => $submission->employee_id,
                    'assigned_lawyer_id' => null,
                    'city' => $cityLabel,
                    'municipality_code' => $municipalityCode,
                    'sede' => null,
                    'opened_at' => now()->toDateString(),
                    'summary' => Str::limit(trim((string) ($submission->summary ?? $snapshot['fo51_observations'] ?? '')), 5000) ?: null,
                ],
                $faultPivotRows,
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

            foreach (array_values($submission->evidence_paths ?? []) as $evidenceIndex => $rel) {
                $rel = (string) $rel;
                if ($rel === '' || ! Storage::disk($submission->storage_disk)->exists($rel)) {
                    continue;
                }
                $absolute = Storage::disk($submission->storage_disk)->path($rel);
                if (! is_file($absolute)) {
                    continue;
                }
                $basename = basename($rel) ?: 'evidencia';
                $extension = pathinfo($basename, PATHINFO_EXTENSION);
                $friendlyName = DisciplinaryDocument::friendlyEvidenceImageNameForIndex($evidenceIndex, $extension);
                $mime = Storage::disk($submission->storage_disk)->mimeType($rel) ?? 'application/octet-stream';
                $evidenceFile = new UploadedFile(
                    $absolute,
                    $friendlyName,
                    $mime,
                    UPLOAD_ERR_OK,
                    true
                );
                $this->documents->upload(
                    $case,
                    $evidenceFile,
                    DocumentType::EVIDENCIA,
                    $reviewer,
                    $informeStage,
                    DisciplinaryDocument::NOTE_FO51_AUTHORIZED_EVIDENCE,
                );
            }

            $this->deleteStoredFile($submission);

            $submission->forceFill([
                'status' => InformeSubmissionStatus::AUTORIZADO,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'reviewer_notes' => $notes,
                'disciplinary_case_id' => $case->id,
                'storage_path' => '',
                'evidence_paths' => null,
            ])->save();

            $this->audit->logInforme(
                $submission->fresh(),
                $reviewer,
                ActionType::INFORME_APROBADO,
                'Informe aprobado; expediente creado en etapa Informe.',
                ['disciplinary_case_id' => $case->id],
            );

            $submitterModel = User::query()->find($submission->submitted_by);
            if ($submitterModel instanceof User) {
                Notification::send($submitterModel, new InformeAuthorizedNotification($case->fresh(['employee'])));
            }

            return $case->fresh(['employee']);
        });
    }

    /**
     * Usuarios de dirección que ven todos los informes (copia informativa al enviar FO-GJ-51).
     * No usa {@see Builder::permission()} para evitar 500 si el permiso aún no está en BD.
     *
     * @return Builder<User>
     */
    private function usersWithInformDirectoryVisibility(): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereHas('roles', fn (Builder $r) => $r->where('name', 'nivel1'))
                    ->orWhereHas('roles.permissions', fn (Builder $p) => $p
                        ->where('name', 'disciplinary.review-inform-all')
                        ->where('guard_name', 'web'))
                    ->orWhereHas('permissions', fn (Builder $p) => $p
                        ->where('name', 'disciplinary.review-inform-all')
                        ->where('guard_name', 'web'));
            });
    }

    private function deleteStoredFile(InformeSubmission $submission): void
    {
        foreach ($submission->evidence_paths ?? [] as $rel) {
            $rel = (string) $rel;
            if ($rel !== '') {
                Storage::disk('local')->delete($rel);
            }
        }

        if ($submission->storage_path === '') {
            return;
        }

        Storage::disk($submission->storage_disk)->delete($submission->storage_path);

        $dir = dirname($submission->storage_path);
        Storage::disk($submission->storage_disk)->deleteDirectory($dir);
    }
}
