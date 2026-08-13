<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\FoGj03Modality;
use App\Support\Disciplinary\FoGj54RescheduleCause;
use App\Support\Disciplinary\SpanishDateParts;
use App\Support\Disciplinary\WorkerLegalPhrasing;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FoGj54DraftService
{
    public const MODE_JUSTIFICATION = 'justification';

    public const MODE_OPERATIONAL = 'operational';

    public function __construct(
        private readonly FoGj04DraftService $foGj04Drafts,
        private readonly FoGj03DraftService $foGj03Drafts,
    ) {}

    public function isOperationalRescheduleContext(DisciplinaryCase $case): bool
    {
        if ($case->diligence_attendance !== null) {
            return false;
        }

        if ($case->current_status === CaseStatus::DILIGENCIA) {
            return true;
        }

        if ($case->current_status === CaseStatus::REPROGRAMADO) {
            $payload = $case->fo_gj_54_payload ?? [];

            return ($payload['mode'] ?? null) === self::MODE_OPERATIONAL;
        }

        return false;
    }

    public function isJustificationContext(DisciplinaryCase $case): bool
    {
        return $case->current_status === CaseStatus::JUSTIFICACION_PENDIENTE
            && $case->diligence_attendance === DiligenceAttendance::ABSENT;
    }

    public function canEditDraft(DisciplinaryCase $case): bool
    {
        if ($this->isOperationalRescheduleContext($case)) {
            if ($case->current_status === CaseStatus::DILIGENCIA) {
                return true;
            }

            return $case->fo_gj_54_generated_at === null;
        }

        return $this->isJustificationContext($case)
            && $case->fo_gj_54_generated_at === null;
    }

    /**
     * Texto de cargos FO-GJ-03 (fecha informe + formulación) para vista previa / PDF.
     *
     * @return array{informe_report_date: string, informe_report_date_long: string, charges_description: string}
     */
    public function chargesFromFo03(DisciplinaryCase $case): array
    {
        $fo03 = $case->fo_gj_03_payload ?? [];
        $rawInforme = trim((string) ($fo03['informe_report_date'] ?? $this->foGj03Drafts->resolveInformeReportDate($case)));
        $citation = $this->foGj04Drafts->citationDataFromFo03($case);

        return [
            'informe_report_date' => $rawInforme,
            'informe_report_date_long' => $this->formatInformeDateLong($rawInforme),
            'charges_description' => $citation['charges_description'],
        ];
    }

    /** @return array<string, mixed> */
    public function defaultsForCase(DisciplinaryCase $case): array
    {
        $existing = $case->fo_gj_54_payload ?? [];
        $charges = $this->chargesFromFo03($case);

        $originalDateRaw = (string) ($existing['original_hearing_date'] ?? ($case->citation_confirmed_date?->format('Y-m-d') ?? ''));
        $originalParts = ['day' => '', 'month' => '', 'year' => ''];
        if ($originalDateRaw !== '') {
            try {
                $originalParts = SpanishDateParts::fromDate(
                    Carbon::parse($originalDateRaw)->timezone('America/Bogota')
                );
            } catch (\Throwable) {
                // keep empty
            }
        }

        $originalTime = (string) ($existing['original_hearing_time'] ?? $this->confirmedTimeForForm($case));
        $mode = $this->isOperationalRescheduleContext($case)
            ? self::MODE_OPERATIONAL
            : (string) ($existing['mode'] ?? self::MODE_JUSTIFICATION);

        $modality = (string) ($existing['modality'] ?? FoGj03Modality::Presencial->value);

        return [
            'mode' => $mode,
            'reschedule_cause' => (string) ($existing['reschedule_cause'] ?? ''),
            'defer_date_to_planning' => (bool) ($existing['defer_date_to_planning'] ?? false),
            'modality' => $modality,
            'virtual_meeting_link' => (string) ($existing['virtual_meeting_link'] ?? ''),
            'new_hearing_date' => (string) ($existing['new_hearing_date'] ?? ($case->citation_confirmed_date?->format('Y-m-d') ?? '')),
            'new_hearing_time' => (string) ($existing['new_hearing_time'] ?? $this->confirmedTimeForForm($case)),
            'original_hearing_date' => $originalDateRaw,
            'original_hearing_day' => $originalParts['day'],
            'original_hearing_month' => $originalParts['month'],
            'original_hearing_year' => $originalParts['year'],
            'original_hearing_time' => $originalTime,
            'informe_report_date' => $charges['informe_report_date'],
            'informe_report_date_long' => $charges['informe_report_date_long'],
            'charges_description' => $charges['charges_description'],
        ];
    }

    private function confirmedTimeForForm(DisciplinaryCase $case): string
    {
        if (! $case->citation_confirmed_time) {
            return '';
        }

        try {
            return Carbon::parse($case->citation_confirmed_time)->format('H:i');
        } catch (\Throwable) {
            return substr((string) $case->citation_confirmed_time, 0, 5);
        }
    }

    private function formatInformeDateLong(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        try {
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $raw, $m)) {
                $date = Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1], 'America/Bogota');
            } else {
                $date = Carbon::parse($raw)->timezone('America/Bogota');
            }

            $parts = SpanishDateParts::fromDate($date);

            return trim($parts['day'].' de '.$parts['month'].' de '.$parts['year']);
        } catch (\Throwable) {
            return $raw;
        }
    }

    public function hasDraftCompleted(DisciplinaryCase $case): bool
    {
        return $case->fo_gj_54_draft_completed_at !== null
            && is_array($case->fo_gj_54_payload)
            && $case->fo_gj_54_payload !== [];
    }

    /** @return list<string> */
    public function missingDraftRequirements(DisciplinaryCase $case): array
    {
        $missing = [];

        if (! $this->isOperationalRescheduleContext($case) && ! $this->isJustificationContext($case)) {
            $missing[] = 'El expediente no está en un estado válido para FO-GJ-54';
        }

        if (! $this->hasDraftCompleted($case)) {
            $missing[] = 'Borrador FO-GJ-54 diligenciado';
        }

        $charges = $this->chargesFromFo03($case);
        if ($charges['informe_report_date'] === '') {
            $missing[] = 'fecha del informe en FO-GJ-03';
        }
        if ($charges['charges_description'] === '') {
            $missing[] = 'formulación de cargos en FO-GJ-03';
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
        if ((int) $case->assigned_lawyer_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'Solo el abogado titular puede diligenciar el FO-GJ-54.',
            ]);
        }

        if (! $this->canEditDraft($case)) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'El FO-GJ-54 no puede diligenciarse en el estado actual del expediente.',
            ]);
        }

        $operational = $this->isOperationalRescheduleContext($case);
        $deferToPlanning = (bool) ($input['defer_date_to_planning'] ?? false);

        if (! $operational && $case->fo_gj_54_generated_at !== null) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'El FO-GJ-54 ya fue generado; no puede editarse el borrador.',
            ]);
        }

        if ($operational && $case->fo_gj_54_generated_at !== null && $case->fo_gj_54_evidence_uploaded_at === null) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'El FO-GJ-54 ya fue generado. Cargue la evidencia de recibido o espere el avance a diligencia.',
            ]);
        }

        $cause = FoGj54RescheduleCause::tryFrom(trim((string) ($input['reschedule_cause'] ?? '')));
        if ($cause === null) {
            throw ValidationException::withMessages([
                'foGj54RescheduleCause' => 'Seleccione el motivo de la reprogramación.',
            ]);
        }

        $newHearingDate = trim((string) ($input['new_hearing_date'] ?? ''));
        $newHearingTime = trim((string) ($input['new_hearing_time'] ?? ''));
        $modality = FoGj03Modality::tryFrom(trim((string) ($input['modality'] ?? '')));
        $virtualLink = trim((string) ($input['virtual_meeting_link'] ?? ''));

        $planningOnlyStart = $operational && $deferToPlanning && $case->current_status === CaseStatus::DILIGENCIA;

        if (! $planningOnlyStart) {
            if ($modality === null) {
                throw ValidationException::withMessages([
                    'foGj54Modality' => 'Seleccione si la diligencia será presencial o virtual.',
                ]);
            }

            if ($modality === FoGj03Modality::Virtual) {
                if ($virtualLink === '' || ! filter_var($virtualLink, FILTER_VALIDATE_URL)) {
                    throw ValidationException::withMessages([
                        'foGj54VirtualLink' => 'Indique un enlace válido para la reunión virtual.',
                    ]);
                }
            } else {
                $virtualLink = '';
            }

            if ($newHearingDate === '') {
                throw ValidationException::withMessages(['foGj54NewHearingDate' => 'Indique la nueva fecha de diligencia.']);
            }
            if ($newHearingTime === '') {
                throw ValidationException::withMessages(['foGj54NewHearingTime' => 'Indique la nueva hora de diligencia.']);
            }

            $normalizedTime = SpanishDateParts::normalizeTimeForForm($newHearingTime);
            if ($normalizedTime === null) {
                throw ValidationException::withMessages([
                    'foGj54NewHearingTime' => 'La hora no es válida. Use formato HH:MM (ej. 08:00 o 14:30).',
                ]);
            }
            $newHearingTime = $normalizedTime;

            try {
                Carbon::parse($newHearingDate)->timezone('America/Bogota');
            } catch (\Throwable) {
                throw ValidationException::withMessages(['foGj54NewHearingDate' => 'La nueva fecha no es válida.']);
            }

            if (! $actor->hasSignature()) {
                throw ValidationException::withMessages([
                    'fo_gj_54' => 'Suba su firma digital en Mi perfil antes de guardar el FO-GJ-54.',
                ]);
            }

            $charges = $this->chargesFromFo03($case);
            if ($charges['informe_report_date'] === '' || $charges['charges_description'] === '') {
                throw ValidationException::withMessages([
                    'fo_gj_54' => 'El FO-GJ-03 debe tener fecha de informe y formulación de cargos para generar el FO-GJ-54.',
                ]);
            }

            if (! WorkerLegalPhrasing::fromEmployee($case->employee)->hasDefiniteGender()) {
                throw ValidationException::withMessages([
                    'fo_gj_54' => 'Defina el género del trabajador en su ficha para la concordancia del documento.',
                ]);
            }
        } else {
            $modality = FoGj03Modality::Presencial;
            $virtualLink = '';
            $newHearingDate = '';
            $newHearingTime = '';
        }

        $existing = $case->fo_gj_54_payload ?? [];
        $originalHearingDate = (string) ($existing['original_hearing_date'] ?? '');
        $originalHearingTime = (string) ($existing['original_hearing_time'] ?? '');
        if ($originalHearingDate === '' && $case->citation_confirmed_date !== null) {
            $originalHearingDate = $case->citation_confirmed_date->format('Y-m-d');
            $originalHearingTime = $this->confirmedTimeForForm($case);
        }

        $payload = [
            'mode' => $operational ? self::MODE_OPERATIONAL : self::MODE_JUSTIFICATION,
            'reschedule_cause' => $cause->value,
            'defer_date_to_planning' => $operational ? $deferToPlanning : false,
            'modality' => $modality->value,
            'virtual_meeting_link' => $virtualLink,
            'new_hearing_date' => $newHearingDate,
            'new_hearing_time' => $newHearingTime,
            'new_hearing_place' => $modality === FoGj03Modality::Presencial
                ? FoGj03DraftService::PRESENCIAL_LOCATION
                : $virtualLink,
            'original_hearing_date' => $originalHearingDate,
            'original_hearing_time' => $originalHearingTime,
        ];

        $updates = [
            'fo_gj_54_payload' => $payload,
            'fo_gj_54_draft_completed_at' => $planningOnlyStart ? null : now(),
            'fo_gj_54_draft_completed_by' => $planningOnlyStart ? null : $actor->id,
        ];

        if ($operational && $case->current_status === CaseStatus::DILIGENCIA) {
            $updates['fo_gj_54_generated_at'] = null;
            $updates['fo_gj_54_generated_by'] = null;
            $updates['fo_gj_54_evidence_uploaded_at'] = null;
        } elseif ($operational && $case->fo_gj_54_generated_at !== null && $case->fo_gj_54_evidence_uploaded_at !== null) {
            $updates['fo_gj_54_generated_at'] = null;
            $updates['fo_gj_54_generated_by'] = null;
            $updates['fo_gj_54_evidence_uploaded_at'] = null;
        }

        $case->forceFill($updates)->save();

        return $case->fresh();
    }

    /** @return array<string, mixed> */
    public function payloadForPdf(DisciplinaryCase $case): array
    {
        if (! $this->hasDraftCompleted($case)) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'Complete el diligenciamiento del FO-GJ-54 antes de generar o previsualizar el documento.',
            ]);
        }

        return $case->fo_gj_54_payload ?? [];
    }

    public function newCitationAt(DisciplinaryCase $case): Carbon
    {
        $payload = $this->payloadForPdf($case);
        $date = Carbon::parse((string) $payload['new_hearing_date'])->timezone('America/Bogota')->startOfDay();
        $timeRaw = trim((string) ($payload['new_hearing_time'] ?? ''));

        if ($timeRaw !== '') {
            try {
                $parsed = Carbon::parse($timeRaw)->timezone('America/Bogota');
                $date = $date->setTime($parsed->hour, $parsed->minute, 0);
            } catch (\Throwable) {
                // keep date at start of day
            }
        }

        return $date;
    }
}
