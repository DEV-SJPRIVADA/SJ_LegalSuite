<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Models\Disciplinary\DisciplinaryAgendaMessage;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\SuspensionPeriodCalculator;
use App\Support\Disciplinary\DecisionStatuteArticles;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Borrador FO-GJ-47 (Suspensión disciplinaria).
 * Persistido en `decision_payload` con `document_code = FO-GJ-47`.
 */
class FoGj47DraftService
{
    public const DOCUMENT_CODE = 'FO-GJ-47';

    public const SUBJECT_FIXED = 'cierre de proceso disciplinario – suspensión de contrato laboral';

    public function __construct(
        private readonly FoGj03DraftService $foGj03Drafts,
    ) {}

    public function appliesTo(DisciplinaryCase $case): bool
    {
        return $case->decision === Decision::SUSPENSION;
    }

    /** @return array<string, mixed> */
    public function defaultsForCase(DisciplinaryCase $case): array
    {
        $existing = $case->decision_payload ?? [];
        $fo03 = $case->fo_gj_03_payload ?? [];
        $startRaw = $this->resolveSuspensionStart($case);
        $days = (int) ($existing['suspension_days'] ?? 0);

        $statuteArticles = DecisionStatuteArticles::resolve($fo03, $existing);

        $period = null;
        if ($startRaw !== null && $days >= 1) {
            $period = SuspensionPeriodCalculator::fromStartAndDays($startRaw, $days);
        }

        return [
            'document_code' => self::DOCUMENT_CODE,
            'opening_narrative' => (string) ($existing['opening_narrative'] ?? ''),
            'suspension_days' => $days > 0 ? $days : '',
            'suspension_start' => $startRaw?->toDateString() ?? '',
            'suspension_end' => $period !== null ? $period['end']->toDateString() : (string) ($existing['suspension_end'] ?? ''),
            'suspension_return' => $period !== null ? $period['return_date']->toDateString() : (string) ($existing['suspension_return'] ?? ''),
            'days_phrase' => $period['days_phrase'] ?? '',
            'start_long' => $period['start_long'] ?? '',
            'end_long' => $period['end_long'] ?? '',
            'return_long' => $period['return_long'] ?? '',
            'statute_articles' => $statuteArticles,
            'articles_55' => DecisionStatuteArticles::numeralsFor($statuteArticles, '55'),
            'articles_57' => DecisionStatuteArticles::numeralsFor($statuteArticles, '57'),
            'articles_60' => DecisionStatuteArticles::numeralsFor($statuteArticles, '60'),
            'signer_name' => (string) ($existing['signer_name'] ?? ''),
            'signer_title' => (string) ($existing['signer_title'] ?? 'DIRECTORA DE GESTIÓN HUMANA'),
            'subject' => self::SUBJECT_FIXED,
            'is_fo_gj_47' => true,
            'is_fo_gj_46' => false,
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
            $missing[] = 'decisión de suspensión';
        }

        if ($case->decision_notification_completed_at === null) {
            $missing[] = 'información de notificación completada por planeación';
        }

        $payload = $case->decision_payload ?? [];

        if (trim((string) ($payload['opening_narrative'] ?? '')) === '') {
            $missing[] = 'párrafo introductorio del FO-GJ-47';
        }

        $days = (int) ($payload['suspension_days'] ?? 0);
        if ($days < 1) {
            $missing[] = 'cantidad de días de suspensión';
        }

        if ($this->resolveSuspensionStart($case) === null) {
            $missing[] = 'fecha de inicio de suspensión (planeación)';
        }

        $statuteMissing = DecisionStatuteArticles::missingRequirements(
            DecisionStatuteArticles::resolve($case->fo_gj_03_payload ?? [], $payload),
        );
        foreach ($statuteMissing as $item) {
            $missing[] = $item;
        }

        if (trim((string) ($payload['signer_name'] ?? '')) === '') {
            $missing[] = 'nombre del firmante de Gestión Humana';
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
                'foGj47OpeningNarrative' => 'El FO-GJ-47 solo aplica a suspensión en etapa de decisión.',
            ]);
        }

        if ((int) $case->assigned_lawyer_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'foGj47OpeningNarrative' => 'Solo el abogado titular puede diligenciar el FO-GJ-47.',
            ]);
        }

        if ($case->decision_comunicado_generated_at !== null) {
            throw ValidationException::withMessages([
                'foGj47OpeningNarrative' => 'El FO-GJ-47 ya fue generado; no puede editarse el borrador.',
            ]);
        }

        $opening = trim((string) ($input['opening_narrative'] ?? ''));
        if ($opening === '') {
            throw ValidationException::withMessages([
                'foGj47OpeningNarrative' => 'Diligencie el párrafo introductorio del comunicado.',
            ]);
        }

        $days = (int) ($input['suspension_days'] ?? 0);
        if ($days < 1 || $days > 90) {
            throw ValidationException::withMessages([
                'foGj47SuspensionDays' => 'Indique la cantidad de días de suspensión (1 a 90).',
            ]);
        }

        $start = $this->resolveSuspensionStart($case, trim((string) ($input['suspension_start'] ?? '')));
        if ($start === null) {
            throw ValidationException::withMessages([
                'foGj47SuspensionStart' => 'Falta la fecha de inicio de suspensión. Coordine con planeación o indíquela.',
            ]);
        }

        $statuteArticles = DecisionStatuteArticles::normalizeInput(
            is_array($input['statute_articles'] ?? null) ? $input['statute_articles'] : [],
        );
        if ($statuteArticles === []) {
            $statuteArticles = DecisionStatuteArticles::resolve($case->fo_gj_03_payload ?? [], []);
        }
        $statuteMissing = DecisionStatuteArticles::missingRequirements($statuteArticles);
        if ($statuteMissing !== []) {
            throw ValidationException::withMessages([
                'decisionStatuteArticles' => 'Complete los numerales de todos los artículos (mismos del FO-GJ-03). Falta: '.implode(', ', $statuteMissing),
            ]);
        }

        $signerName = trim((string) ($input['signer_name'] ?? ''));
        $signerTitle = trim((string) ($input['signer_title'] ?? 'DIRECTORA DE GESTIÓN HUMANA'));
        if ($signerName === '') {
            throw ValidationException::withMessages([
                'foGj47SignerName' => 'Indique el nombre de quien firma por Gestión Humana.',
            ]);
        }

        $period = SuspensionPeriodCalculator::fromStartAndDays($start, $days);

        $case->forceFill([
            'decision_payload' => [
                'document_code' => self::DOCUMENT_CODE,
                'opening_narrative' => $opening,
                'suspension_days' => $days,
                'suspension_start' => $period['start']->toDateString(),
                'suspension_end' => $period['end']->toDateString(),
                'suspension_return' => $period['return_date']->toDateString(),
                'days_phrase' => $period['days_phrase'],
                'start_long' => $period['start_long'],
                'end_long' => $period['end_long'],
                'return_long' => $period['return_long'],
                'statute_articles' => $statuteArticles,
                'articles_55' => DecisionStatuteArticles::numeralsFor($statuteArticles, '55'),
                'articles_57' => DecisionStatuteArticles::numeralsFor($statuteArticles, '57'),
                'articles_60' => DecisionStatuteArticles::numeralsFor($statuteArticles, '60'),
                'signer_name' => $signerName,
                'signer_title' => $signerTitle !== '' ? $signerTitle : 'DIRECTORA DE GESTIÓN HUMANA',
                'subject' => self::SUBJECT_FIXED,
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
                'foGj47OpeningNarrative' => 'Complete el diligenciamiento del FO-GJ-47. Falta: '.implode(', ', $missing),
            ]);
        }

        return $case->decision_payload ?? [];
    }

    public function resolveSuspensionStart(DisciplinaryCase $case, ?string $override = null): ?Carbon
    {
        $candidates = array_filter([
            $override,
            $case->decision_payload['suspension_start'] ?? null,
            $this->latestPlanningSuspensionStart($case),
        ], static fn ($v) => filled($v));

        foreach ($candidates as $raw) {
            try {
                return Carbon::parse((string) $raw)->timezone('America/Bogota')->startOfDay();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function latestPlanningSuspensionStart(DisciplinaryCase $case): ?string
    {
        $case->loadMissing('agendaThread.messages');
        $thread = $case->agendaThread;
        if ($thread === null) {
            return null;
        }

        $messages = $thread->relationLoaded('messages')
            ? $thread->messages
            : $thread->messages()->orderByDesc('id')->get();

        /** @var DisciplinaryAgendaMessage|null $hit */
        $hit = $messages
            ->sortByDesc('id')
            ->first(function (DisciplinaryAgendaMessage $message): bool {
                $payload = $message->notification_payload;

                return is_array($payload) && filled($payload['suspension_start'] ?? null);
            });

        if ($hit === null) {
            return null;
        }

        return (string) ($hit->notification_payload['suspension_start'] ?? '');
    }
}
