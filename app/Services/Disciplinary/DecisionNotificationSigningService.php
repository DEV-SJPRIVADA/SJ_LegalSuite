<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CitationEvidenceType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Validation\ValidationException;

class DecisionNotificationSigningService
{
    public function __construct(
        private readonly DecisionComunicadoService $comunicado,
        private readonly FoGj45DraftService $foGj45Drafts,
        private readonly FoGj46DraftService $foGj46Drafts,
        private readonly FoGj47DraftService $foGj47Drafts,
        private readonly CitationNotificationSigningService $citationSigning,
    ) {}

    /**
     * @param  array{
     *     evidence_type: string,
     *     worker_signature?: string|null,
     *     witnesses?: array<int, array{signature?: string|null, name?: string, document?: string}>
     * }  $input
     * @return array{
     *     evidence_type: string,
     *     worker_signature: string|null,
     *     witnesses: array<int, array{signature_data_uri: string, name: string, document: string}>
     * }
     */
    public function validateNotificationPayload(array $input): array
    {
        return $this->citationSigning->validateNotificationPayload($input);
    }

    /**
     * @param  array{
     *     evidence_type: string,
     *     worker_signature: string|null,
     *     witnesses: array<int, array{signature_data_uri: string, name: string, document: string}>
     * }  $payload
     */
    public function renderNotificationPdf(DisciplinaryCase $case, array $payload): string
    {
        $viewData = array_merge(
            $this->comunicado->buildViewData($case),
            [
                'embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri(),
                'blankForDownload' => false,
                'evidenceType' => $payload['evidence_type'],
                'workerSignatureDataUri' => $payload['worker_signature'],
                'witnesses' => array_map(static fn (array $witness): array => [
                    'signatureDataUri' => $witness['signature_data_uri'],
                    'name' => $witness['name'],
                    'document' => $witness['document'],
                ], $payload['witnesses']),
            ],
        );

        $view = match (true) {
            $this->foGj47Drafts->appliesTo($case) => 'disciplinary.forms.fo-gj-47-signed-notification-download',
            $this->foGj46Drafts->appliesTo($case) => 'disciplinary.forms.fo-gj-46-signed-notification-download',
            $this->foGj45Drafts->appliesTo($case) => 'disciplinary.forms.fo-gj-45-signed-notification-download',
            default => 'disciplinary.forms.decision-comunicado-signed-notification-download',
        };

        return HtmlLetterPdfGenerator::fromView($view, $viewData);
    }

    public function assertValidSignatureDataUri(?string $dataUri, string $field, string $emptyMessage): string
    {
        return $this->citationSigning->assertValidSignatureDataUri($dataUri, $field, $emptyMessage);
    }
}
