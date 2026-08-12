<?php

namespace App\Services\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\FoGj03Modality;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FoGj03DraftService
{
    public const PRESENCIAL_LOCATION = 'en las instalaciones de la empresa SJ Seguridad Privada Ltda. en Cali en la dirección Av. 4 Nte. #26N - 39 B/ San Vicente';

    public function __construct(
        private readonly FoGj03CitationArticleResolver $articles,
    ) {}

    /** @return array<string, mixed> */
    public function defaultsForCase(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'assignedLawyer', 'informeSubmission', 'faults']);

        $hearingTime = '';
        if ($case->citation_confirmed_time) {
            try {
                $hearingTime = Carbon::parse($case->citation_confirmed_time)->format('H:i');
            } catch (\Throwable) {
                $hearingTime = substr((string) $case->citation_confirmed_time, 0, 5);
            }
        }

        $existing = $case->fo_gj_03_payload ?? [];
        $hasSavedDraft = $case->fo_gj_03_draft_completed_at !== null && is_array($existing) && $existing !== [];

        $statuteArticles = $hasSavedDraft
            ? $this->articles->blocksFromPayload($existing)
            : $this->articles->resolveForCase($case);

        return [
            'hearing_time' => (string) ($existing['hearing_time'] ?? $hearingTime),
            'modality' => (string) ($existing['modality'] ?? FoGj03Modality::Presencial->value),
            'virtual_meeting_link' => (string) ($existing['virtual_meeting_link'] ?? ''),
            'breach_date' => (string) ($existing['breach_date'] ?? ''),
            'charges_description' => (string) ($existing['charges_description'] ?? ''),
            'statute_articles' => $statuteArticles,
            'evidence_items' => $this->normalizeEvidenceItems($existing['evidence_items'] ?? []),
            'informe_report_date' => $this->resolveInformeReportDate($case),
        ];
    }

    public function resolveInformeReportDate(DisciplinaryCase $case): string
    {
        $case->loadMissing('informeSubmission');
        $submission = $case->informeSubmission;

        if ($submission === null) {
            return '';
        }

        $snap = $submission->form_snapshot ?? [];
        $day = trim((string) ($snap['fo51_report_dd'] ?? ''));
        $month = trim((string) ($snap['fo51_report_mm'] ?? ''));
        $year = trim((string) ($snap['fo51_report_yyyy'] ?? ''));

        if ($day !== '' && $month !== '' && $year !== '') {
            return sprintf('%s/%s/%s', $day, $month, $year);
        }

        $reference = $submission->reviewed_at ?? $submission->created_at;
        if ($reference !== null) {
            return $reference->timezone('America/Bogota')->format('d/m/Y');
        }

        return '';
    }

    public function hasDraftCompleted(DisciplinaryCase $case): bool
    {
        return $case->fo_gj_03_draft_completed_at !== null
            && is_array($case->fo_gj_03_payload)
            && $case->fo_gj_03_payload !== [];
    }

    /** @return list<string> */
    public function missingDraftRequirements(DisciplinaryCase $case, ?User $lawyer = null): array
    {
        if (! $this->hasDraftCompleted($case)) {
            return ['Diligenciamiento del FO-GJ-03'];
        }

        $lawyer ??= $case->assignedLawyer;
        if ($lawyer === null || ! $lawyer->hasSignature()) {
            return ['Firma digital en Mi perfil'];
        }

        return [];
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
        if ((int) $case->assigned_lawyer_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'fo_gj_03' => 'Solo el abogado titular puede diligenciar el FO-GJ-03.',
            ]);
        }

        if ($case->citation_confirmed_date === null) {
            throw ValidationException::withMessages([
                'fo_gj_03' => 'Confirme la fecha de diligencia antes de diligenciar el FO-GJ-03.',
            ]);
        }

        $modality = FoGj03Modality::tryFrom((string) ($input['modality'] ?? ''));
        if ($modality === null) {
            throw ValidationException::withMessages([
                'foGj03Modality' => 'Seleccione si la diligencia es presencial o virtual.',
            ]);
        }

        $hearingTime = trim((string) ($input['hearing_time'] ?? ''));
        if ($hearingTime === '' || ! preg_match('/^\d{2}:\d{2}$/', $hearingTime)) {
            throw ValidationException::withMessages([
                'foGj03HearingTime' => 'Indique la hora de la diligencia en formato HH:MM.',
            ]);
        }

        $breachDate = trim((string) ($input['breach_date'] ?? ''));
        if ($breachDate === '') {
            throw ValidationException::withMessages([
                'foGj03BreachDate' => 'La fecha del incumplimiento es obligatoria.',
            ]);
        }

        try {
            $breachFormatted = Carbon::parse($breachDate)->timezone('America/Bogota')->format('d/m/Y');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'foGj03BreachDate' => 'La fecha del incumplimiento no es válida.',
            ]);
        }

        $virtualLink = trim((string) ($input['virtual_meeting_link'] ?? ''));
        if ($modality === FoGj03Modality::Virtual) {
            if ($virtualLink === '' || ! filter_var($virtualLink, FILTER_VALIDATE_URL)) {
                throw ValidationException::withMessages([
                    'foGj03VirtualLink' => 'Indique un enlace válido para la reunión virtual.',
                ]);
            }
        }

        $statuteArticles = $this->normalizeInputStatuteArticles($input['statute_articles'] ?? []);
        if ($statuteArticles === []) {
            throw ValidationException::withMessages([
                'foGj03StatuteArticles' => 'Agregue al menos un artículo con sus numerales.',
            ]);
        }

        foreach ($statuteArticles as $index => $block) {
            if (trim((string) ($block['numerals'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    "foGj03StatuteArticles.{$index}.numerals" => 'Los numerales del artículo '.($block['article_number'] ?? '').' son obligatorios.',
                ]);
            }
        }

        $chargesDescription = trim((string) ($input['charges_description'] ?? ''));
        if ($chargesDescription === '') {
            throw ValidationException::withMessages([
                'foGj03ChargesDescription' => 'Indique el texto obligatorio que continúa después de los dos puntos en la formulación de cargos.',
            ]);
        }

        $evidenceItems = $this->normalizeEvidenceItems($input['evidence_items'] ?? []);

        if (! $actor->hasSignature()) {
            throw ValidationException::withMessages([
                'fo_gj_03' => 'Suba su firma digital en Mi perfil antes de guardar el FO-GJ-03.',
            ]);
        }

        $payload = [
            'hearing_time' => $hearingTime,
            'modality' => $modality->value,
            'virtual_meeting_link' => $modality === FoGj03Modality::Virtual ? $virtualLink : '',
            'breach_date' => $breachDate,
            'breach_date_display' => $breachFormatted,
            'charges_description' => $chargesDescription,
            'statute_articles' => $statuteArticles,
            'evidence_items' => $evidenceItems,
            'informe_report_date' => $this->resolveInformeReportDate($case),
        ];

        $case->forceFill([
            'fo_gj_03_payload' => $payload,
            'fo_gj_03_draft_completed_at' => now(),
            'fo_gj_03_draft_completed_by' => $actor->id,
        ])->save();

        return $case->fresh();
    }

    /** @return array<string, mixed> */
    public function payloadForPdf(DisciplinaryCase $case): array
    {
        if (! $this->hasDraftCompleted($case)) {
            throw ValidationException::withMessages([
                'fo_gj_03' => 'Complete el diligenciamiento del FO-GJ-03 antes de generar o previsualizar el documento.',
            ]);
        }

        return $case->fo_gj_03_payload ?? [];
    }

    /**
     * @param  mixed  $raw
     * @return list<array{article_number: string, numerals: string}>
     */
    private function normalizeInputStatuteArticles(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $blocks = [];
        foreach ($raw as $block) {
            if (! is_array($block)) {
                continue;
            }

            $articleNumber = trim((string) ($block['article_number'] ?? ''));
            if ($articleNumber === '') {
                continue;
            }

            $blocks[] = [
                'article_number' => $articleNumber,
                'numerals' => trim((string) ($block['numerals'] ?? '')),
            ];
        }

        return $blocks;
    }

    /**
     * Elementos probatorios adicionales (además del informe disciplinario automático).
     *
     * @param  mixed  $raw
     * @return list<string>
     */
    public function normalizeEvidenceItems(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $row) {
            if (is_array($row)) {
                $text = trim((string) ($row['text'] ?? ''));
            } else {
                $text = trim((string) $row);
            }

            if ($text === '') {
                continue;
            }

            $items[] = $text;
        }

        return array_values($items);
    }
}
