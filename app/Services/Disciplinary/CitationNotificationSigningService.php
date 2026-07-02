<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CitationEvidenceType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Validation\ValidationException;

class CitationNotificationSigningService
{
    public function __construct(
        private readonly FoGj03CitationService $foGj03,
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
        $evidenceType = (string) ($input['evidence_type'] ?? '');
        if (! in_array($evidenceType, [CitationEvidenceType::SIGNED->value, CitationEvidenceType::REFUSED_WITNESSES->value], true)) {
            throw ValidationException::withMessages([
                'notificationEvidenceType' => 'Seleccione el tipo de evidencia.',
            ]);
        }

        if ($evidenceType === CitationEvidenceType::SIGNED->value) {
            return [
                'evidence_type' => $evidenceType,
                'worker_signature' => $this->assertValidSignatureDataUri(
                    $input['worker_signature'] ?? null,
                    'workerSignature',
                    'Capture la firma del trabajador antes de cargar el documento firmado.',
                ),
                'witnesses' => [],
            ];
        }

        $witnesses = [];
        foreach ([1, 2] as $index) {
            $witnessInput = $input['witnesses'][$index - 1] ?? [];
            $name = trim((string) ($witnessInput['name'] ?? ''));
            $document = trim((string) ($witnessInput['document'] ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    "witness{$index}Name" => "Indique el nombre del testigo {$index}.",
                ]);
            }

            if ($document === '' || ! preg_match('/^\d{5,15}$/', $document)) {
                throw ValidationException::withMessages([
                    "witness{$index}Document" => "Indique una cédula válida para el testigo {$index}.",
                ]);
            }

            $witnesses[] = [
                'signature_data_uri' => $this->assertValidSignatureDataUri(
                    $witnessInput['signature'] ?? null,
                    "witness{$index}Signature",
                    "Capture la firma del testigo {$index}.",
                ),
                'name' => $name,
                'document' => $document,
            ];
        }

        return [
            'evidence_type' => $evidenceType,
            'worker_signature' => null,
            'witnesses' => $witnesses,
        ];
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
            $this->foGj03->buildViewData($case),
            [
                'embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri(),
                'evidenceType' => $payload['evidence_type'],
                'workerSignatureDataUri' => $payload['worker_signature'],
                'witnesses' => array_map(static fn (array $witness): array => [
                    'signatureDataUri' => $witness['signature_data_uri'],
                    'name' => $witness['name'],
                    'document' => $witness['document'],
                ], $payload['witnesses']),
            ],
        );

        return HtmlLetterPdfGenerator::fromView('disciplinary.forms.fo-gj-03-signed-notification-download', $viewData);
    }

    public function assertValidWorkerSignatureDataUri(?string $dataUri): string
    {
        return $this->assertValidSignatureDataUri(
            $dataUri,
            'workerSignature',
            'Capture la firma del trabajador antes de cargar el documento firmado.',
        );
    }

    public function assertValidSignatureDataUri(?string $dataUri, string $field, string $emptyMessage): string
    {
        $dataUri = trim((string) $dataUri);
        if ($dataUri === '' || ! preg_match('#^data:image/png;base64,[A-Za-z0-9+/=\s]+$#', $dataUri)) {
            throw ValidationException::withMessages([
                $field => $emptyMessage,
            ]);
        }

        $raw = base64_decode(preg_replace('#\s+#', '', substr($dataUri, 22)) ?: '', true);
        if ($raw === false || strlen($raw) < 80 || strlen($raw) > 600_000) {
            throw ValidationException::withMessages([
                $field => 'La firma capturada no es válida. Intente nuevamente.',
            ]);
        }

        return $dataUri;
    }

    /** @deprecated Use renderNotificationPdf() */
    public function renderSignedNotificationPdf(DisciplinaryCase $case, string $workerSignatureDataUri): string
    {
        return $this->renderNotificationPdf($case, $this->validateNotificationPayload([
            'evidence_type' => CitationEvidenceType::SIGNED->value,
            'worker_signature' => $workerSignatureDataUri,
        ]));
    }
}
