<?php

namespace App\Support\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Support\Collection;

/**
 * Progreso guiado de Etapa D (comunicado de decisión / cierre).
 */
final class DecisionStageProgress
{
    public const STATUS_DONE = 'done';

    public const STATUS_CURRENT = 'current';

    public const STATUS_PENDING = 'pending';

    /** @return Collection<int, array{key: string, label: string, status: string, hint: string}> */
    public function steps(DisciplinaryCase $case): Collection
    {
        $case = $case->fresh(['documents']);
        $branch = DecisionBranch::forDecision($case->decision);
        $needsHr = $branch !== null && DecisionBranch::requiresHrReview($branch);

        $typeDone = $case->decision !== null && $case->decision_coordination_started_at !== null;
        $coordinationDone = $case->decision_notification_completed_at !== null
            && $case->hasDecisionPlanningReply();
        $draftDone = $case->decision_draft_completed_at !== null;
        $comunicadoDone = $case->decision_comunicado_generated_at !== null
            || $case->latestDecisionComunicadoDocument() !== null;
        $notificationDone = $case->decision_notification_completed_at !== null
            && $case->decision_notification_supervisor_user_id !== null;
        $evidenceDone = $case->decision_evidence_uploaded_at !== null;
        $hrDone = ! $needsHr || $case->decision_hr_review_completed_at !== null;
        $closed = $case->current_status !== CaseStatus::DECISION;

        $defs = [
            [
                'key' => 'type',
                'label' => 'Tipo de decisión',
                'done' => $typeDone,
                'hint' => 'Seleccione la sanción o cierre aplicable al trabajador.',
            ],
            [
                'key' => 'coordination',
                'label' => 'Programación',
                'done' => $coordinationDone,
                'hint' => 'Coordine con planeación fechas, turnos y supervisor para la notificación.',
            ],
            [
                'key' => 'draft',
                'label' => match ($case->decision) {
                    Decision::AMONESTACION_ESCRITA => 'FO-GJ-46',
                    Decision::SUSPENSION => 'FO-GJ-47',
                    default => 'Comunicado',
                },
                'done' => $draftDone && $comunicadoDone,
                'hint' => match ($case->decision) {
                    Decision::AMONESTACION_ESCRITA => 'Diligencie el FO-GJ-46 (llamado de atención) y genere el PDF en el expediente.',
                    Decision::SUSPENSION => 'Diligencie el FO-GJ-47 (suspensión) y genere el PDF en el expediente.',
                    default => 'Diligencie el comunicado de decisión y genere el PDF en el expediente.',
                },
            ],
            [
                'key' => 'notification',
                'label' => 'Notificación',
                'done' => $notificationDone && $comunicadoDone,
                'hint' => 'Supervisor asignado; operaciones puede hacer seguimiento.',
            ],
            [
                'key' => 'evidence',
                'label' => 'Firma trabajador',
                'done' => $evidenceDone,
                'hint' => 'El supervisor registra la firma del trabajador o evidencia con testigos.',
            ],
        ];

        if ($needsHr) {
            $defs[] = [
                'key' => 'hr',
                'label' => 'Gestión humana',
                'done' => $hrDone,
                'hint' => 'Área administrativa carga anexos laborales antes de notificar al trabajador.',
            ];
        }

        $defs[] = [
            'key' => 'close',
            'label' => 'Cierre',
            'done' => $closed,
            'hint' => 'Finalice el proceso disciplinario cuando la notificación esté completa.',
        ];

        $currentAssigned = false;

        return collect($defs)->map(function (array $def) use (&$currentAssigned) {
            $status = self::STATUS_DONE;
            if (! $def['done']) {
                if (! $currentAssigned) {
                    $status = self::STATUS_CURRENT;
                    $currentAssigned = true;
                } else {
                    $status = self::STATUS_PENDING;
                }
            }

            return [
                'key' => $def['key'],
                'label' => $def['label'],
                'status' => $status,
                'hint' => $def['hint'],
            ];
        });
    }

    /** @return array{key: string, label: string, status: string, hint: string} */
    public function currentStep(DisciplinaryCase $case): array
    {
        $steps = $this->steps($case);
        $current = $steps->firstWhere('status', self::STATUS_CURRENT);

        if ($current !== null) {
            return $current;
        }

        $pending = $steps->firstWhere('status', self::STATUS_PENDING);

        return $pending ?? [
            'key' => 'close',
            'label' => 'Cierre',
            'status' => self::STATUS_CURRENT,
            'hint' => '',
        ];
    }

    public function currentStepNumber(DisciplinaryCase $case): int
    {
        $steps = $this->steps($case);
        $current = $this->currentStep($case);
        $index = $steps->search(fn (array $step): bool => $step['key'] === $current['key']);

        return $index === false ? 1 : (int) $index + 1;
    }

    public function totalSteps(DisciplinaryCase $case): int
    {
        $branch = DecisionBranch::forDecision($case->decision);

        return $branch !== null && DecisionBranch::requiresHrReview($branch) ? 7 : 6;
    }

    public function actionBarTitle(string $stepKey): string
    {
        return match ($stepKey) {
            'type' => 'Tipo de decisión',
            'coordination' => 'Coordinación con programación',
            'draft' => 'Comunicado de decisión',
            'notification' => 'Notificación al supervisor',
            'evidence' => 'Evidencia de notificación',
            'hr' => 'Gestión humana',
            'close' => 'Cierre del proceso',
            default => 'Decisión disciplinaria',
        };
    }
}
