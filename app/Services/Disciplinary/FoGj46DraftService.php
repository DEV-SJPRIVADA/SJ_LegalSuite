<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\FoGj03Modality;
use App\Support\Disciplinary\FoGj46HearingLead;
use App\Support\Disciplinary\SpanishDateParts;
use Illuminate\Validation\ValidationException;

/**
 * Borrador FO-GJ-46 (Llamado de atención) — solo amonestación escrita.
 * Persistido en `decision_payload` con `document_code = FO-GJ-46`.
 */
class FoGj46DraftService
{
    public const DOCUMENT_CODE = 'FO-GJ-46';

    public const SUBJECT_FIXED = 'cierre de proceso disciplinario – llamado de atención por escrito.';

    public function __construct(
        private readonly FoGj03DraftService $foGj03Drafts,
        private readonly FoGj04DraftService $foGj04Drafts,
    ) {}

    public function appliesTo(DisciplinaryCase $case): bool
    {
        return $case->decision === Decision::AMONESTACION_ESCRITA;
    }

    /** @return array<string, mixed> */
    public function defaultsForCase(DisciplinaryCase $case): array
    {
        $existing = $case->decision_payload ?? [];
        $fo03 = $case->fo_gj_03_payload ?? [];
        $modality = FoGj03Modality::tryFrom((string) ($fo03['modality'] ?? '')) ?? FoGj03Modality::Presencial;

        $leadDefault = FoGj46HearingLead::tryFrom((string) ($existing['hearing_lead'] ?? ''));
        if ($leadDefault === null) {
            $leadDefault = $case->diligence_attendance === DiligenceAttendance::ABSENT
                ? FoGj46HearingLead::Citado
                : FoGj46HearingLead::Surtida;
        }

        $citation = $this->foGj04Drafts->citationDataFromFo03($case);
        $hearing = $case->citation_confirmed_date;
        $hearingParts = SpanishDateParts::fromDate($hearing?->timezone('America/Bogota'));
        $hearingTime = $case->resolvedDiligenceHearingTimeLabel() ?? '';

        $articles55 = (string) ($existing['articles_55'] ?? $this->defaultArticlesLine($fo03, '55'));
        $articles57 = (string) ($existing['articles_57'] ?? $this->defaultArticlesLine($fo03, '57'));
        $articles60 = (string) ($existing['articles_60'] ?? $this->defaultArticlesLine($fo03, '60'));

        return [
            'document_code' => self::DOCUMENT_CODE,
            'hearing_lead' => $leadDefault->value,
            'facts_narrative' => (string) ($existing['facts_narrative'] ?? ''),
            'articles_55' => $articles55,
            'articles_57' => $articles57,
            'articles_60' => $articles60,
            'signer_name' => (string) ($existing['signer_name'] ?? ''),
            'signer_title' => (string) ($existing['signer_title'] ?? 'DIRECTORA DE GESTIÓN HUMANA'),
            'modality' => $modality->value,
            'modality_label' => $modality === FoGj03Modality::Virtual ? 'virtual' : 'presencial',
            'hearing_day' => $hearingParts['day'],
            'hearing_month' => $hearingParts['month'],
            'hearing_year' => $hearingParts['year'],
            'hearing_time' => $hearingTime,
            'breach_day' => $citation['breach_day'],
            'breach_month' => $citation['breach_month'],
            'breach_year' => $citation['breach_year'],
            'informe_report_date' => (string) ($fo03['informe_report_date'] ?? $this->foGj03Drafts->resolveInformeReportDate($case)),
            'subject' => self::SUBJECT_FIXED,
            'is_fo_gj_46' => true,
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
            $missing[] = 'decisión de llamado de atención (amonestación escrita)';
        }

        if ($case->decision_notification_completed_at === null) {
            $missing[] = 'información de notificación completada por planeación';
        }

        $payload = $case->decision_payload ?? [];
        if (FoGj46HearingLead::tryFrom((string) ($payload['hearing_lead'] ?? '')) === null) {
            $missing[] = 'opción de diligencia (surtida / citado)';
        }

        if (trim((string) ($payload['facts_narrative'] ?? '')) === '') {
            $missing[] = 'relato de los hechos (después de la fecha de incumplimiento)';
        }

        if (trim((string) ($payload['articles_55'] ?? '')) === '') {
            $missing[] = 'numerales artículo 55';
        }
        if (trim((string) ($payload['articles_57'] ?? '')) === '') {
            $missing[] = 'numerales artículo 57';
        }
        if (trim((string) ($payload['articles_60'] ?? '')) === '') {
            $missing[] = 'numerales artículo 60';
        }

        if (trim((string) ($payload['signer_name'] ?? '')) === '') {
            $missing[] = 'nombre del firmante emisor';
        }

        $citation = $this->foGj04Drafts->citationDataFromFo03($case);
        if ($citation['breach_day'] === '') {
            $missing[] = 'fecha de incumplimiento en FO-GJ-03';
        }

        if ($case->citation_confirmed_date === null) {
            $missing[] = 'fecha de diligencia/citación confirmada';
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
                'foGj46HearingLead' => 'El FO-GJ-46 solo aplica a llamado de atención en etapa de decisión.',
            ]);
        }

        if ((int) $case->assigned_lawyer_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'foGj46HearingLead' => 'Solo el abogado titular puede diligenciar el FO-GJ-46.',
            ]);
        }

        if ($case->decision_comunicado_generated_at !== null) {
            throw ValidationException::withMessages([
                'foGj46HearingLead' => 'El FO-GJ-46 ya fue generado; no puede editarse el borrador.',
            ]);
        }

        $lead = FoGj46HearingLead::tryFrom(trim((string) ($input['hearing_lead'] ?? '')));
        if ($lead === null) {
            throw ValidationException::withMessages([
                'foGj46HearingLead' => 'Seleccione si la diligencia se surtió o si solo fue citado.',
            ]);
        }

        $facts = trim((string) ($input['facts_narrative'] ?? ''));
        if ($facts === '') {
            throw ValidationException::withMessages([
                'foGj46FactsNarrative' => 'Diligencie el relato de los hechos después de la fecha de incumplimiento.',
            ]);
        }

        $articles55 = trim((string) ($input['articles_55'] ?? ''));
        $articles57 = trim((string) ($input['articles_57'] ?? ''));
        $articles60 = trim((string) ($input['articles_60'] ?? ''));
        if ($articles55 === '' || $articles57 === '' || $articles60 === '') {
            throw ValidationException::withMessages([
                'foGj46Articles55' => 'Complete los numerales de los artículos 55, 57 y 60.',
            ]);
        }

        $signerName = trim((string) ($input['signer_name'] ?? ''));
        $signerTitle = trim((string) ($input['signer_title'] ?? 'DIRECTORA DE GESTIÓN HUMANA'));
        if ($signerName === '') {
            throw ValidationException::withMessages([
                'foGj46SignerName' => 'Indique el nombre de quien firma el llamado de atención.',
            ]);
        }

        $citation = $this->foGj04Drafts->citationDataFromFo03($case);
        if ($citation['breach_day'] === '') {
            throw ValidationException::withMessages([
                'foGj46FactsNarrative' => 'El FO-GJ-03 debe tener fecha de incumplimiento.',
            ]);
        }

        $defaults = $this->defaultsForCase($case);

        $case->forceFill([
            'decision_payload' => [
                'document_code' => self::DOCUMENT_CODE,
                'hearing_lead' => $lead->value,
                'facts_narrative' => $facts,
                'articles_55' => $articles55,
                'articles_57' => $articles57,
                'articles_60' => $articles60,
                'signer_name' => $signerName,
                'signer_title' => $signerTitle !== '' ? $signerTitle : 'DIRECTORA DE GESTIÓN HUMANA',
                'subject' => self::SUBJECT_FIXED,
                // Snapshot de datos de sistema al guardar
                'modality' => $defaults['modality'],
                'hearing_day' => $defaults['hearing_day'],
                'hearing_month' => $defaults['hearing_month'],
                'hearing_year' => $defaults['hearing_year'],
                'hearing_time' => $defaults['hearing_time'],
                'breach_day' => $defaults['breach_day'],
                'breach_month' => $defaults['breach_month'],
                'breach_year' => $defaults['breach_year'],
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
                'foGj46HearingLead' => 'Complete el diligenciamiento del FO-GJ-46. Falta: '.implode(', ', $missing),
            ]);
        }

        return $case->decision_payload ?? [];
    }

    /**
     * @param  array<string, mixed>  $fo03
     */
    private function defaultArticlesLine(array $fo03, string $articleNumber): string
    {
        $blocks = $fo03['statute_articles'] ?? $fo03['articles'] ?? [];
        if (! is_array($blocks)) {
            return '';
        }

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            $number = trim((string) ($block['article_number'] ?? $block['number'] ?? ''));
            if ($number !== $articleNumber) {
                continue;
            }
            $numerals = $block['numerals'] ?? [];
            if (is_string($numerals)) {
                return trim($numerals);
            }
            if (is_array($numerals)) {
                return implode(', ', array_map('strval', $numerals));
            }
        }

        return '';
    }
}
