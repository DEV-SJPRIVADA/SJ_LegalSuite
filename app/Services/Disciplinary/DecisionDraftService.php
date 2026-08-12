<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\DecisionBranch;
use Illuminate\Validation\ValidationException;

class DecisionDraftService
{
    public function __construct(
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

        $existing = $case->decision_payload ?? [];
        $branch = DecisionBranch::forDecision($case->decision);

        return [
            'subject' => (string) ($existing['subject'] ?? $this->defaultSubject($case)),
            'body_narrative' => (string) ($existing['body_narrative'] ?? ''),
            'suspension_start' => (string) ($existing['suspension_start'] ?? ''),
            'suspension_end' => (string) ($existing['suspension_end'] ?? ''),
            'relief_notes' => (string) ($existing['relief_notes'] ?? ''),
            'requires_suspension_dates' => $branch !== null && DecisionBranch::requiresSuspensionDates($branch),
            'requires_relief' => $branch === DecisionBranch::TERMINATION,
            'is_fo_gj_46' => false,
            'is_fo_gj_47' => false,
        ];
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

        $missing = [];

        if ($case->current_status !== CaseStatus::DECISION) {
            $missing[] = 'expediente en etapa de decisión';
        }

        if ($case->decision === null) {
            $missing[] = 'tipo de decisión registrado';
        }

        if ($case->decision_notification_completed_at === null) {
            $missing[] = 'información de notificación completada por planeación';
        }

        $payload = $case->decision_payload ?? [];
        $subject = trim((string) ($payload['subject'] ?? ''));
        if ($subject === '') {
            $missing[] = 'asunto del comunicado';
        }

        $body = trim((string) ($payload['body_narrative'] ?? ''));
        if ($body === '') {
            $missing[] = 'cuerpo del comunicado';
        }

        $branch = DecisionBranch::forDecision($case->decision);
        if ($branch !== null && DecisionBranch::requiresSuspensionDates($branch)) {
            if (trim((string) ($payload['suspension_start'] ?? '')) === '') {
                $missing[] = 'fecha inicio de suspensión';
            }
            if (trim((string) ($payload['suspension_end'] ?? '')) === '') {
                $missing[] = 'fecha fin de suspensión';
            }
        }

        if ($branch === DecisionBranch::TERMINATION && trim((string) ($payload['relief_notes'] ?? '')) === '') {
            $missing[] = 'observaciones de relevo';
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
        if ($this->foGj46Drafts->appliesTo($case)) {
            return $this->foGj46Drafts->payloadForPdf($case);
        }

        if ($this->foGj47Drafts->appliesTo($case)) {
            return $this->foGj47Drafts->payloadForPdf($case);
        }

        $missing = $this->missingDraftRequirements($case);
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'decisionBodyNarrative' => 'Complete el diligenciamiento del comunicado antes de generar o previsualizar el documento.',
            ]);
        }

        return $case->decision_payload ?? [];
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

        if ($case->current_status !== CaseStatus::DECISION) {
            throw ValidationException::withMessages([
                'decisionBodyNarrative' => 'El comunicado solo se diligencia en etapa de decisión.',
            ]);
        }

        if ((int) $case->assigned_lawyer_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'decisionBodyNarrative' => 'Solo el abogado titular puede diligenciar el comunicado.',
            ]);
        }

        if ($case->decision_comunicado_generated_at !== null) {
            throw ValidationException::withMessages([
                'decisionBodyNarrative' => 'El comunicado ya fue generado; no puede editarse el borrador.',
            ]);
        }

        $subject = trim((string) ($input['subject'] ?? ''));
        $body = trim((string) ($input['body_narrative'] ?? ''));

        if ($subject === '') {
            throw ValidationException::withMessages(['decisionSubject' => 'Indique el asunto del comunicado.']);
        }

        if ($body === '') {
            throw ValidationException::withMessages(['decisionBodyNarrative' => 'Indique el cuerpo del comunicado.']);
        }

        $payload = [
            'subject' => $subject,
            'body_narrative' => $body,
        ];

        $branch = DecisionBranch::forDecision($case->decision);
        if ($branch !== null && DecisionBranch::requiresSuspensionDates($branch)) {
            $start = trim((string) ($input['suspension_start'] ?? ''));
            $end = trim((string) ($input['suspension_end'] ?? ''));
            if ($start === '' || $end === '') {
                throw ValidationException::withMessages([
                    'decisionSuspensionStart' => 'Indique las fechas de suspensión.',
                ]);
            }
            $payload['suspension_start'] = $start;
            $payload['suspension_end'] = $end;
        }

        if ($branch === DecisionBranch::TERMINATION) {
            $relief = trim((string) ($input['relief_notes'] ?? ''));
            if ($relief === '') {
                throw ValidationException::withMessages([
                    'decisionReliefNotes' => 'Indique las observaciones de relevo.',
                ]);
            }
            $payload['relief_notes'] = $relief;
        }

        $case->forceFill([
            'decision_payload' => $payload,
            'decision_draft_completed_at' => now(),
            'decision_draft_completed_by' => $actor->id,
        ])->save();

        return $case->fresh();
    }

    private function defaultSubject(DisciplinaryCase $case): string
    {
        $label = $case->decision?->label() ?? 'Decisión disciplinaria';

        return 'Comunicado de '.$label;
    }
}
