<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\DecisionBranch;
use Illuminate\Validation\ValidationException;

/**
 * Borrador FO-GJ-45 (Acta de archivo) — rama TERMINATION.
 * Persistido en `decision_payload` con `document_code = FO-GJ-45`.
 */
class FoGj45DraftService
{
    public const DOCUMENT_CODE = 'FO-GJ-45';

    public const SUBJECT_FIXED = 'acta de archivo';

    public const DEFAULT_RESOLUTIVE_FIRST = 'TERMINAR EL CONTRATO DE TRABAJO';

    public const DEFAULT_RESOLUTIVE_SECOND = 'ARCHIVAR el presente proceso';

    public const DEFAULT_SIGNER_TITLE = 'DIRECTORA GESTIÓN HUMANA';

    public function appliesTo(DisciplinaryCase $case): bool
    {
        return DecisionBranch::forDecision($case->decision) === DecisionBranch::TERMINATION;
    }

    /** @return array<string, mixed> */
    public function defaultsForCase(DisciplinaryCase $case): array
    {
        $existing = $case->decision_payload ?? [];

        return [
            'document_code' => self::DOCUMENT_CODE,
            'body_paragraph' => (string) ($existing['body_paragraph'] ?? ''),
            'resolutive_first' => (string) ($existing['resolutive_first'] ?? self::DEFAULT_RESOLUTIVE_FIRST),
            'resolutive_second' => (string) ($existing['resolutive_second'] ?? self::DEFAULT_RESOLUTIVE_SECOND),
            'signer_name' => (string) ($existing['signer_name'] ?? ''),
            'signer_title' => (string) ($existing['signer_title'] ?? self::DEFAULT_SIGNER_TITLE),
            'subject' => self::SUBJECT_FIXED,
            'is_fo_gj_45' => true,
            'is_fo_gj_46' => false,
            'is_fo_gj_47' => false,
        ];
    }

    /** @return list<string> */
    public function missingDraftRequirements(DisciplinaryCase $case): array
    {
        $missing = [];

        if ($case->current_status !== CaseStatus::DECISION) {
            $missing[] = 'expediente en etapa de decisión';
        }

        if (! $this->appliesTo($case)) {
            $missing[] = 'decisión de terminación de contrato';
        }

        if ($case->decision_notification_completed_at === null) {
            $missing[] = 'información de notificación completada por planeación';
        }

        $payload = $case->decision_payload ?? [];

        if (trim((string) ($payload['body_paragraph'] ?? '')) === '') {
            $missing[] = 'párrafo del acta de archivo';
        }

        if (trim((string) ($payload['resolutive_first'] ?? '')) === '') {
            $missing[] = 'resolutivo PRIMERO';
        }

        if (trim((string) ($payload['resolutive_second'] ?? '')) === '') {
            $missing[] = 'resolutivo SEGUNDO';
        }

        if (trim((string) ($payload['signer_name'] ?? '')) === '') {
            $missing[] = 'nombre del firmante emisor';
        }

        return $missing;
    }

    public function isReadyForPdf(DisciplinaryCase $case): bool
    {
        return $this->missingDraftRequirements($case) === [];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveDraft(DisciplinaryCase $case, User $actor, array $input): DisciplinaryCase
    {
        if ($case->current_status !== CaseStatus::DECISION || ! $this->appliesTo($case)) {
            throw ValidationException::withMessages([
                'foGj45BodyParagraph' => 'El FO-GJ-45 solo aplica a terminación de contrato en etapa de decisión.',
            ]);
        }

        if ((int) $case->assigned_lawyer_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'foGj45BodyParagraph' => 'Solo el abogado titular puede diligenciar el FO-GJ-45.',
            ]);
        }

        if ($case->decision_comunicado_generated_at !== null) {
            throw ValidationException::withMessages([
                'foGj45BodyParagraph' => 'El FO-GJ-45 ya fue generado; no puede editarse el borrador.',
            ]);
        }

        $body = trim((string) ($input['body_paragraph'] ?? ''));
        if ($body === '') {
            throw ValidationException::withMessages([
                'foGj45BodyParagraph' => 'Diligencie el párrafo completo del acta de archivo.',
            ]);
        }

        $first = trim((string) ($input['resolutive_first'] ?? ''));
        if ($first === '') {
            throw ValidationException::withMessages([
                'foGj45ResolutiveFirst' => 'Indique el resolutivo PRIMERO.',
            ]);
        }

        $second = trim((string) ($input['resolutive_second'] ?? ''));
        if ($second === '') {
            throw ValidationException::withMessages([
                'foGj45ResolutiveSecond' => 'Indique el resolutivo SEGUNDO.',
            ]);
        }

        $signerName = trim((string) ($input['signer_name'] ?? ''));
        $signerTitle = trim((string) ($input['signer_title'] ?? self::DEFAULT_SIGNER_TITLE));
        if ($signerName === '') {
            throw ValidationException::withMessages([
                'foGj45SignerName' => 'Indique el nombre de quien firma.',
            ]);
        }

        $case->forceFill([
            'decision_payload' => [
                'document_code' => self::DOCUMENT_CODE,
                'body_paragraph' => $body,
                'resolutive_first' => $first,
                'resolutive_second' => $second,
                'signer_name' => $signerName,
                'signer_title' => $signerTitle !== '' ? $signerTitle : self::DEFAULT_SIGNER_TITLE,
                'subject' => self::SUBJECT_FIXED,
                'decision_label' => $case->decision?->label() ?? '',
                'decision_value' => $case->decision instanceof Decision ? $case->decision->value : '',
            ],
            'decision_draft_completed_at' => now(),
            'decision_draft_completed_by' => $actor->id,
        ])->save();

        return $case->fresh();
    }

    /** @return array<string, mixed> */
    public function payloadForPdf(DisciplinaryCase $case): array
    {
        $missing = $this->missingDraftRequirements($case);
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'foGj45BodyParagraph' => 'Complete el diligenciamiento del FO-GJ-45. Falta: '.implode(', ', $missing),
            ]);
        }

        return $case->decision_payload ?? [];
    }
}
