<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DecisionDraftService
{
    public function __construct(
        private readonly FoGj45DraftService $foGj45Drafts,
        private readonly FoGj46DraftService $foGj46Drafts,
        private readonly FoGj47DraftService $foGj47Drafts,
    ) {}

    /** @return array<string, mixed> */
    public function defaultsForCase(DisciplinaryCase $case): array
    {
        if ($this->foGj46Drafts->appliesTo($case)) {
            return $this->foGj46Drafts->defaultsForCase($case);
        }

        if ($this->foGj47Drafts->appliesTo($case)) {
            return $this->foGj47Drafts->defaultsForCase($case);
        }

        if ($this->foGj45Drafts->appliesTo($case)) {
            return $this->foGj45Drafts->defaultsForCase($case);
        }

        throw ValidationException::withMessages([
            'decisionType' => 'Registre un tipo de decisión válido (llamado de atención, suspensión o terminación).',
        ]);
    }

    public function hasDraftCompleted(DisciplinaryCase $case): bool
    {
        return $case->decision_draft_completed_at !== null
            && is_array($case->decision_payload)
            && $case->decision_payload !== [];
    }

    /** @return list<string> */
    public function missingDraftRequirements(DisciplinaryCase $case): array
    {
        if ($this->foGj46Drafts->appliesTo($case)) {
            return $this->foGj46Drafts->missingDraftRequirements($case);
        }

        if ($this->foGj47Drafts->appliesTo($case)) {
            return $this->foGj47Drafts->missingDraftRequirements($case);
        }

        if ($this->foGj45Drafts->appliesTo($case)) {
            return $this->foGj45Drafts->missingDraftRequirements($case);
        }

        return ['tipo de decisión con formato FO-GJ-45/46/47'];
    }

    public function isReadyForPdf(DisciplinaryCase $case): bool
    {
        return $this->missingDraftRequirements($case) === [];
    }

    /** @return array<string, mixed> */
    public function payloadForPdf(DisciplinaryCase $case): array
    {
        if ($this->foGj46Drafts->appliesTo($case)) {
            return $this->foGj46Drafts->payloadForPdf($case);
        }

        if ($this->foGj47Drafts->appliesTo($case)) {
            return $this->foGj47Drafts->payloadForPdf($case);
        }

        if ($this->foGj45Drafts->appliesTo($case)) {
            return $this->foGj45Drafts->payloadForPdf($case);
        }

        throw ValidationException::withMessages([
            'decisionBodyNarrative' => 'Complete el diligenciamiento del formato FO-GJ correspondiente.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveDraft(DisciplinaryCase $case, User $actor, array $input): DisciplinaryCase
    {
        if ($this->foGj46Drafts->appliesTo($case)) {
            return $this->foGj46Drafts->saveDraft($case, $actor, $input);
        }

        if ($this->foGj47Drafts->appliesTo($case)) {
            return $this->foGj47Drafts->saveDraft($case, $actor, $input);
        }

        if ($this->foGj45Drafts->appliesTo($case)) {
            return $this->foGj45Drafts->saveDraft($case, $actor, $input);
        }

        throw ValidationException::withMessages([
            'decisionBodyNarrative' => 'No hay formato de decisión aplicable a este expediente.',
        ]);
    }
}
