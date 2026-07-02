<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ComiteDraftService
{
    /** @return array<string, mixed> */
    public function defaultsForCase(DisciplinaryCase $case): array
    {
        $existing = $case->comite_payload ?? [];

        return [
            'decision_narrative' => (string) ($existing['decision_narrative'] ?? ''),
            'attendees' => is_array($existing['attendees'] ?? null) && $existing['attendees'] !== []
              ? $existing['attendees']
              : [
                  ['name' => '', 'cargo' => '', 'signature_data_uri' => null],
                  ['name' => '', 'cargo' => '', 'signature_data_uri' => null],
                  ['name' => '', 'cargo' => '', 'signature_data_uri' => null],
              ],
        ];
    }

    public function hasDraftCompleted(DisciplinaryCase $case): bool
    {
        return $case->comite_draft_completed_at !== null
          && is_array($case->comite_payload)
          && $case->comite_payload !== [];
    }

    /** @return list<string> */
    public function missingDraftRequirements(DisciplinaryCase $case): array
    {
        $missing = [];

        if ($case->current_status !== CaseStatus::COMITE_DISCIPLINARIO) {
            $missing[] = 'expediente en comité disciplinario';
        }

        if ($case->fo_gj_44_generated_at === null) {
            $missing[] = 'FO-GJ-44 generado';
        }

        $payload = $case->comite_payload ?? [];
        $narrative = trim((string) ($payload['decision_narrative'] ?? ''));
        if ($narrative === '') {
            $missing[] = 'decisión o acuerdo del comité';
        }

        $attendees = is_array($payload['attendees'] ?? null) ? $payload['attendees'] : [];
        $filled = collect($attendees)->filter(
            fn (mixed $row): bool => is_array($row)
              && trim((string) ($row['name'] ?? '')) !== ''
              && trim((string) ($row['cargo'] ?? '')) !== '',
        );

        if ($filled->isEmpty()) {
            $missing[] = 'al menos un integrante del comité (nombre y cargo)';
        }

        return $missing;
    }

    public function isReadyForPdf(DisciplinaryCase $case): bool
    {
        return $this->missingDraftRequirements($case) === [];
    }

    /** @return array<string, mixed> */
    public function payloadForPdf(DisciplinaryCase $case): array
    {
        $missing = $this->missingDraftRequirements($case);
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'comiteDecisionNarrative' => 'Complete el diligenciamiento del acta de comité antes de generar o previsualizar el documento.',
            ]);
        }

        return $case->comite_payload ?? [];
    }

    /**
     * @param  array{decision_narrative: string, attendees: array<int, array{name: string, cargo: string, signature_data_uri: ?string}>}  $input
     */
    public function saveDraft(DisciplinaryCase $case, User $actor, array $input): DisciplinaryCase
    {
        if ($case->current_status !== CaseStatus::COMITE_DISCIPLINARIO) {
            throw ValidationException::withMessages([
                'comiteDecisionNarrative' => 'El acta de comité solo se diligencia en estado comité disciplinario.',
            ]);
        }

        if ((int) $case->assigned_lawyer_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'comiteDecisionNarrative' => 'Solo el abogado titular puede diligenciar el acta de comité.',
            ]);
        }

        if ($case->comite_generated_at !== null) {
            throw ValidationException::withMessages([
                'comiteDecisionNarrative' => 'El acta de comité ya fue generada; no puede editarse el borrador.',
            ]);
        }

        $narrative = trim((string) ($input['decision_narrative'] ?? ''));
        if ($narrative === '') {
            throw ValidationException::withMessages([
                'comiteDecisionNarrative' => 'Indique la decisión o acuerdo del comité.',
            ]);
        }

        $attendees = [];
        foreach ($input['attendees'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $cargo = trim((string) ($row['cargo'] ?? ''));
            if ($name === '' && $cargo === '') {
                continue;
            }

            if ($name === '' || $cargo === '') {
                throw ValidationException::withMessages([
                    'comiteAttendees' => 'Cada integrante del comité debe tener nombre y cargo.',
                ]);
            }

            $attendees[] = [
                'name' => $name,
                'cargo' => $cargo,
                'signature_data_uri' => isset($row['signature_data_uri']) && is_string($row['signature_data_uri'])
                  ? $row['signature_data_uri']
                  : null,
            ];
        }

        if ($attendees === []) {
            throw ValidationException::withMessages([
                'comiteAttendees' => 'Registre al menos un integrante del comité.',
            ]);
        }

        $case->forceFill([
            'comite_payload' => [
                'decision_narrative' => $narrative,
                'attendees' => $attendees,
            ],
            'comite_draft_completed_at' => now(),
            'comite_draft_completed_by' => $actor->id,
        ])->save();

        return $case->fresh();
    }
}
