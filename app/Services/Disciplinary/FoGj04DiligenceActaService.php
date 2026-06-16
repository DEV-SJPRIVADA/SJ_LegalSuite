<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Users\UserSignatureService;
use App\Support\Disciplinary\FoGj04PagePlanner;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FoGj04DiligenceActaService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
        private readonly DisciplinaryDocumentService $documents,
        private readonly FoGj04DraftService $drafts,
        private readonly UserSignatureService $signatures,
    ) {}

    public function canGenerate(DisciplinaryCase $case): bool
    {
        return $case->current_status === CaseStatus::DILIGENCIA
            && $case->diligence_attendance === DiligenceAttendance::ATTENDED
            && $case->assigned_lawyer_id !== null
            && $case->fo_gj_04_generated_at === null
            && $this->drafts->isReadyForPdf($case)
            && $this->drafts->hasWorkerSignature($case);
    }

    /** @return array<string, mixed> */
    public function buildViewData(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'assignedLawyer.jobPosition']);
        $payload = $this->drafts->payloadForPdf($case);
        $lawyer = $case->assignedLawyer;
        $hearingDate = $case->citation_confirmed_date;

        $workerName = trim(($case->employee?->first_name ?? '').' '.($case->employee?->last_name ?? ''));
        $citation = $this->drafts->citationDataFromFo03($case);
        $questionItems = $this->normalizeQuestions($payload['questions'] ?? []);
        $questionPages = app(FoGj04PagePlanner::class)->plan($questionItems, false);

        return [
            'workerName' => $workerName,
            'workerDocument' => (string) ($case->employee?->document_number ?? ''),
            'workerPosition' => (string) ($case->employee?->job_title ?? ''),
            'openingDay' => $hearingDate ? (string) $hearingDate->day : '',
            'openingMonth' => $hearingDate ? $this->spanishMonthName($hearingDate) : '',
            'openingYear' => $hearingDate ? (string) $hearingDate->year : '',
            'openingTime' => (string) ($payload['opening_time'] ?? ''),
            'lawyerName' => $lawyer?->name ?? '',
            'lawyerRole' => $lawyer?->displayJobTitle() ?? 'Analista de relaciones laborales y cumplimiento SJ Seguridad Privada Ltda.',
            'breachDay' => $citation['breach_day'],
            'breachMonth' => $citation['breach_month'],
            'breachYear' => $citation['breach_year'],
            'chargesDescription' => $citation['charges_description'],
            'workerManifestation' => (string) ($payload['worker_manifestation'] ?? ''),
            'closingTime' => (string) ($payload['closing_time'] ?? ''),
            'questions' => $questionItems,
            'questionPages' => $questionPages,
            'signatureDataUri' => $lawyer ? $this->signatures->dataUriForPdf($lawyer) : null,
            'workerSignatureDataUri' => (string) ($payload['worker_signature_data_uri'] ?? '') ?: null,
        ];
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return list<array{question: string, answer: string}>
     */
    private function normalizeQuestions(array $raw): array
    {
        return collect($raw)->map(function ($q) {
            if (! is_array($q)) {
                return null;
            }

            $question = trim((string) ($q['question'] ?? $q['text'] ?? ''));
            if ($question === '') {
                return null;
            }

            return [
                'question' => $question,
                'answer' => trim((string) ($q['answer'] ?? '')),
            ];
        })->filter()->values()->all();
    }

    public function downloadPdf(DisciplinaryCase $case, User $actor): string
    {
        $this->drafts->payloadForPdf($case);

        return HtmlLetterPdfGenerator::fromView('disciplinary.forms.fo-gj-04-filled-download', array_merge(
            $this->buildViewData($case),
            ['embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri()],
        ));
    }

    public function generateAndStore(DisciplinaryCase $case, User $actor): DisciplinaryCase
    {
        if (! $this->canGenerate($case)) {
            $missing = $this->drafts->missingDraftRequirements($case);
            throw ValidationException::withMessages([
                'fo_gj_04' => $missing !== []
                    ? 'No es posible generar FO-GJ-04. Falta: '.implode(', ', $missing)
                    : 'Complete el diligenciamiento del FO-GJ-04 antes de generar el documento.',
            ]);
        }

        return DB::transaction(function () use ($case, $actor) {
            $binary = $this->downloadPdf($case, $actor);
            $path = tempnam(sys_get_temp_dir(), 'fo04_');
            file_put_contents($path, $binary);

            try {
                $uploaded = new UploadedFile(
                    $path,
                    'FO-GJ-04-acta-'.$case->case_number.'.pdf',
                    'application/pdf',
                    UPLOAD_ERR_OK,
                    true,
                );

                $stage = $case->stages()
                    ->where('stage_type', StageType::DILIGENCIA)
                    ->orderByDesc('sequence')
                    ->first();

                $this->documents->upload(
                    $case,
                    $uploaded,
                    DocumentType::ACTA_DILIGENCIA,
                    $actor,
                    $stage,
                    DisciplinaryCase::NOTE_FO_GJ_04_GENERATED,
                );
            } finally {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $case->forceFill([
                'fo_gj_04_generated_at' => now(),
                'fo_gj_04_generated_by' => $actor->id,
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::FO_GJ_04_GENERADO,
                'FO-GJ-04 generado y almacenado en el expediente.',
            );

            return $case->fresh(['employee', 'assignedLawyer']);
        });
    }

    private function spanishMonthName(Carbon $date): string
    {
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return $months[(int) $date->month] ?? $date->format('F');
    }
}
