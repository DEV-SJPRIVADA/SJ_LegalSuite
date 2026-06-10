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

    /** @return array<string, mixed> */
    public function defaultsForCase(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'assignedLawyer', 'informeSubmission']);

        $hearingTime = '';
        if ($case->citation_confirmed_time) {
            try {
                $hearingTime = Carbon::parse($case->citation_confirmed_time)->format('H:i');
            } catch (\Throwable) {
                $hearingTime = substr((string) $case->citation_confirmed_time, 0, 5);
            }
        }

        $existing = $case->fo_gj_03_payload ?? [];

        return [
            'hearing_time' => (string) ($existing['hearing_time'] ?? $hearingTime),
            'modality' => (string) ($existing['modality'] ?? FoGj03Modality::Presencial->value),
            'virtual_meeting_link' => (string) ($existing['virtual_meeting_link'] ?? ''),
            'breach_date' => (string) ($existing['breach_date'] ?? ''),
            'charges_description' => (string) ($existing['charges_description'] ?? ''),
            'article_66_numerals' => (string) ($existing['article_66_numerals'] ?? ''),
            'article_68_numerals' => (string) ($existing['article_68_numerals'] ?? ''),
            'article_76_numerals' => (string) ($existing['article_76_numerals'] ?? ''),
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

        foreach ([
            'article_66_numerals' => 'foGj03Article66Numerals',
            'article_68_numerals' => 'foGj03Article68Numerals',
            'article_76_numerals' => 'foGj03Article76Numerals',
        ] as $key => $errorKey) {
            if (trim((string) ($input[$key] ?? '')) === '') {
                throw ValidationException::withMessages([
                    $errorKey => 'Los numerales del artículo son obligatorios.',
                ]);
            }
        }

        $chargesDescription = trim((string) ($input['charges_description'] ?? ''));
        if ($chargesDescription === '') {
            throw ValidationException::withMessages([
                'foGj03ChargesDescription' => 'Indique el texto obligatorio que continúa después de los dos puntos en la formulación de cargos.',
            ]);
        }

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
            'article_66_numerals' => trim((string) $input['article_66_numerals']),
            'article_68_numerals' => trim((string) $input['article_68_numerals']),
            'article_76_numerals' => trim((string) $input['article_76_numerals']),
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
}
