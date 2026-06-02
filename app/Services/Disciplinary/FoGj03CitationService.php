<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FoGj03CitationService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
        private readonly DisciplinaryDocumentService $documents,
        private readonly DisciplinaryCitationNotificationService $notification,
    ) {}

    public function canGenerate(DisciplinaryCase $case): bool
    {
        return $case->citation_confirmed_date !== null
            && $case->assigned_lawyer_id !== null
            && $this->notification->canGenerateFoGj03($case);
    }

    public function buildViewData(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'faults']);

        $date = $case->citation_confirmed_date?->format('d/m/Y') ?? '';
        $time = $case->citation_confirmed_time
            ? substr((string) $case->citation_confirmed_time, 0, 5)
            : '';

        $workerName = trim(($case->employee?->first_name ?? '').' '.($case->employee?->last_name ?? ''));
        $faultSummary = $case->faults->map(fn ($f) => $f->code.' '.$f->name)->join('; ');

        return [
            'fecha' => now()->timezone('America/Bogota')->format('d/m/Y'),
            'expedienteGj' => Str::after($case->case_number, 'DISC-') ?: $case->case_number,
            'workerName' => $workerName,
            'workerDocument' => (string) ($case->employee?->document_number ?? ''),
            'workerPosition' => (string) ($case->employee?->job_title ?? ''),
            'hearingDay' => $date,
            'hearingTime' => $time,
            'conductMonth' => '',
            'conductDays' => '',
            'informeSignedBy' => $case->assignedLawyer?->name ?? '',
            'faultSummary' => $faultSummary,
        ];
    }

    public function downloadPdf(DisciplinaryCase $case, User $actor): string
    {
        return HtmlLetterPdfGenerator::fromView('disciplinary.forms.fo-gj-03-filled-download', array_merge(
            $this->buildViewData($case),
            ['embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri()],
        ));
    }

    public function generateAndStore(DisciplinaryCase $case, User $actor): DisciplinaryCase
    {
        if (! $this->canGenerate($case)) {
            $missing = $this->notification->missingFoGj03GenerationRequirements($case);
            throw ValidationException::withMessages([
                'fo_gj_03' => $missing !== []
                    ? 'No es posible generar FO-GJ-03. Falta: '.implode(', ', $missing)
                    : 'Seleccione la fecha definitiva de citación antes de generar el FO-GJ-03.',
            ]);
        }

        return DB::transaction(function () use ($case, $actor) {
            $binary = $this->downloadPdf($case, $actor);
            $path = tempnam(sys_get_temp_dir(), 'fo03_');
            file_put_contents($path, $binary);

            try {
                $uploaded = new UploadedFile(
                    $path,
                    'FO-GJ-03-citacion-'.$case->case_number.'.pdf',
                    'application/pdf',
                    UPLOAD_ERR_OK,
                    true,
                );

                $stage = $case->stages()
                    ->where('stage_type', StageType::CITACION)
                    ->orderByDesc('sequence')
                    ->first();

                $this->documents->upload(
                    $case,
                    $uploaded,
                    DocumentType::CITACION,
                    $actor,
                    $stage,
                    DisciplinaryCase::NOTE_FO_GJ_03_GENERATED,
                );
            } finally {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $case->forceFill([
                'fo_gj_03_generated_at' => now(),
                'fo_gj_03_generated_by' => $actor->id,
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::FO_GJ_03_GENERADO,
                'FO-GJ-03 generado y almacenado en el expediente.',
            );

            $freshCase = $case->fresh(['employee', 'assignedLawyer', 'informeSubmission']);
            $this->notification->notifyEvidenceUploadEnabled($freshCase);

            return $freshCase;
        });
    }
}
