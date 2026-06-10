<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Users\UserSignatureService;
use App\Support\Disciplinary\FoGj03Modality;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FoGj03CitationService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
        private readonly DisciplinaryDocumentService $documents,
        private readonly DisciplinaryCitationNotificationService $notification,
        private readonly FoGj03DraftService $drafts,
        private readonly UserSignatureService $signatures,
    ) {}

    public function canGenerate(DisciplinaryCase $case): bool
    {
        return $case->citation_confirmed_date !== null
            && $case->assigned_lawyer_id !== null
            && $this->notification->canGenerateFoGj03($case)
            && $this->drafts->isReadyForPdf($case);
    }

    public function buildViewData(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'faults', 'assignedLawyer.jobPosition', 'informeSubmission.reviewer']);

        $payload = $this->drafts->payloadForPdf($case);
        $lawyer = $case->assignedLawyer;

        $hearingDate = $case->citation_confirmed_date?->format('d/m/Y') ?? '';
        $hearingTimeRaw = (string) ($payload['hearing_time'] ?? '');
        $hearingTime = $hearingTimeRaw;
        try {
            $hearingTime = Carbon::parse($hearingTimeRaw)->format('h:i A');
        } catch (\Throwable) {
            // keep raw HH:MM
        }

        $modality = FoGj03Modality::tryFrom((string) ($payload['modality'] ?? '')) ?? FoGj03Modality::Presencial;
        $locationText = $modality === FoGj03Modality::Virtual
            ? (string) ($payload['virtual_meeting_link'] ?? '')
            : FoGj03DraftService::PRESENCIAL_LOCATION;

        $workerName = trim(($case->employee?->first_name ?? '').' '.($case->employee?->last_name ?? ''));
        $informeSigner = $case->informeSubmission?->reviewer?->name
            ?? $lawyer?->name
            ?? '';

        return [
            'fecha' => now()->timezone('America/Bogota')->format('d/m/Y'),
            'caseNumber' => (string) $case->case_number,
            'workerName' => $workerName,
            'workerDocument' => (string) ($case->employee?->document_number ?? ''),
            'workerPosition' => (string) ($case->employee?->job_title ?? ''),
            'hearingDay' => $hearingDate,
            'hearingTime' => $hearingTime,
            'modality' => $modality->value,
            'locationText' => $locationText,
            'informeReportDate' => (string) ($payload['informe_report_date'] ?? $this->drafts->resolveInformeReportDate($case)),
            'breachDate' => (string) ($payload['breach_date_display'] ?? ''),
            'chargesDescription' => (string) ($payload['charges_description'] ?? ''),
            'article66Numerals' => (string) ($payload['article_66_numerals'] ?? ''),
            'article68Numerals' => (string) ($payload['article_68_numerals'] ?? ''),
            'article76Numerals' => (string) ($payload['article_76_numerals'] ?? ''),
            'informeSignedBy' => $informeSigner,
            'signerName' => $lawyer?->name ?? '',
            'signerRole' => $lawyer?->displayJobTitle() ?? 'Analista de Relaciones Laborales',
            'signatureDataUri' => $lawyer ? $this->signatures->dataUriForPdf($lawyer) : null,
        ];
    }

    public function downloadPdf(DisciplinaryCase $case, User $actor): string
    {
        $this->drafts->payloadForPdf($case);

        return HtmlLetterPdfGenerator::fromView('disciplinary.forms.fo-gj-03-filled-download', array_merge(
            $this->buildViewData($case),
            ['embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri()],
        ));
    }

    public function generateAndStore(DisciplinaryCase $case, User $actor): DisciplinaryCase
    {
        if (! $this->canGenerate($case)) {
            $missing = array_merge(
                $this->notification->missingFoGj03GenerationRequirements($case),
                $this->drafts->missingDraftRequirements($case),
            );
            throw ValidationException::withMessages([
                'fo_gj_03' => $missing !== []
                    ? 'No es posible generar FO-GJ-03. Falta: '.implode(', ', $missing)
                    : 'Complete el diligenciamiento del FO-GJ-03 antes de generar el documento.',
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
