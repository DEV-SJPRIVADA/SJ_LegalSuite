<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Disciplinary\FoGj03DraftService;
use App\Support\Disciplinary\SpanishDateParts;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FoGj54DraftService
{
    public function __construct(
        private readonly FoGj04DraftService $foGj04Drafts,
    ) {}

    /** @return array<string, mixed> */
    public function defaultsForCase(DisciplinaryCase $case): array
    {
        $existing = $case->fo_gj_54_payload ?? [];
        $hearing = $case->citation_confirmed_date;
        $hearingParts = SpanishDateParts::fromDate($hearing?->timezone('America/Bogota'));
        $citation = $this->foGj04Drafts->citationDataFromFo03($case);
        $hearingTime = $case->resolvedDiligenceHearingTimeLabel() ?? '';

        return [
            'client_site' => (string) ($existing['client_site'] ?? ($case->sede ?? '')),
            'shift_start' => (string) ($existing['shift_start'] ?? ''),
            'shift_end' => (string) ($existing['shift_end'] ?? ''),
            'new_hearing_date' => (string) ($existing['new_hearing_date'] ?? ''),
            'new_hearing_time' => (string) ($existing['new_hearing_time'] ?? ''),
            'new_hearing_place' => (string) ($existing['new_hearing_place'] ?? FoGj03DraftService::PRESENCIAL_LOCATION),
            'original_hearing_day' => $hearingParts['day'],
            'original_hearing_month' => $hearingParts['month'],
            'original_hearing_year' => $hearingParts['year'],
            'original_hearing_time' => $hearingTime,
            'facts_day' => $citation['breach_day'],
            'facts_month' => $citation['breach_month'],
        ];
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

        if ($case->current_status !== CaseStatus::JUSTIFICACION_PENDIENTE) {
            $missing[] = 'El expediente debe estar en ventana de justificación';
        }

        if (! $this->hasDraftCompleted($case)) {
            $missing[] = 'Borrador FO-GJ-54 diligenciado';
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

        if ($case->current_status !== CaseStatus::JUSTIFICACION_PENDIENTE) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'El FO-GJ-54 solo se diligencia tras aceptar la justificación.',
            ]);
        }

        if ($case->fo_gj_54_generated_at !== null) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'El FO-GJ-54 ya fue generado; no puede editarse el borrador.',
            ]);
        }

        $clientSite = trim((string) ($input['client_site'] ?? ''));
        $shiftStart = trim((string) ($input['shift_start'] ?? ''));
        $shiftEnd = trim((string) ($input['shift_end'] ?? ''));
        $newHearingDate = trim((string) ($input['new_hearing_date'] ?? ''));
        $newHearingTime = trim((string) ($input['new_hearing_time'] ?? ''));
        $newHearingPlace = trim((string) ($input['new_hearing_place'] ?? ''));

        if ($clientSite === '') {
            throw ValidationException::withMessages(['foGj54ClientSite' => 'Indique las instalaciones del cliente.']);
        }
        if ($shiftStart === '') {
            throw ValidationException::withMessages(['foGj54ShiftStart' => 'Indique la hora de inicio del turno.']);
        }
        if ($shiftEnd === '') {
            throw ValidationException::withMessages(['foGj54ShiftEnd' => 'Indique la hora de fin del turno.']);
        }
        if ($newHearingDate === '') {
            throw ValidationException::withMessages(['foGj54NewHearingDate' => 'Indique la nueva fecha de diligencia.']);
        }
        if ($newHearingTime === '') {
            throw ValidationException::withMessages(['foGj54NewHearingTime' => 'Indique la nueva hora de diligencia.']);
        }
        if ($newHearingPlace === '') {
            throw ValidationException::withMessages(['foGj54NewHearingPlace' => 'Indique el lugar de la nueva diligencia.']);
        }

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

        $payload = [
            'client_site' => $clientSite,
            'shift_start' => $shiftStart,
            'shift_end' => $shiftEnd,
            'new_hearing_date' => $newHearingDate,
            'new_hearing_time' => $newHearingTime,
            'new_hearing_place' => $newHearingPlace,
        ];

        $case->forceFill([
            'fo_gj_54_payload' => $payload,
            'fo_gj_54_draft_completed_at' => now(),
            'fo_gj_54_draft_completed_by' => $actor->id,
        ])->save();

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
