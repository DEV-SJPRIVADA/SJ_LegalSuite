<?php

namespace App\Support\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\DecisionCoordinationService;
use Illuminate\Support\Collection;

/**
 * Progreso guiado de Etapa D (5 pasos): tipo → programación → documento → entrega → cierre.
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
        $needsPackage = $branch !== null && DecisionBranch::requiresLawyerTerminationPackage($branch);
        $coordination = app(DecisionCoordinationService::class);

        $typeDone = $case->decision !== null && $case->decision_coordination_started_at !== null;
        $coordinationDone = $coordination->hasConfirmedNotification($case);
        $draftDone = $case->decision_draft_completed_at !== null;
        $comunicadoDone = $case->decision_comunicado_generated_at !== null
            || $case->latestDecisionComunicadoDocument() !== null;
        $evidenceDone = $case->decision_evidence_uploaded_at !== null;
        $packageDone = ! $needsPackage || $case->decision_hr_review_completed_at !== null || $case->hasDecisionHrAnnex();
        $closed = $case->current_status !== CaseStatus::DECISION;

        $coordinationHint = match (true) {
            ! $typeDone => 'Coordine con planeación fechas, turnos y supervisor para la notificación.',
            ! $coordination->hasOpenOptions($case) => 'Espere opciones de planeación (fecha, turno, zona y supervisor).',
            ! $coordinationDone => 'Seleccione una opción en el chat y confírmela.',
            default => 'Programación confirmada.',
        };

        $defs = [
            [
                'key' => 'type',
                'label' => 'Tipo de decisión',
                'done' => $typeDone,
                'hint' => 'Seleccione la sanción aplicable al trabajador.',
            ],
            [
                'key' => 'coordination',
                'label' => 'Programación',
                'done' => $coordinationDone,
                'hint' => $coordinationHint,
            ],
            [
                'key' => 'draft',
                'label' => match ($case->decision) {
                    Decision::AMONESTACION_ESCRITA => 'FO-GJ-46',
                    Decision::SUSPENSION => 'FO-GJ-47',
                    Decision::TERMINACION_CONTRATO => 'FO-GJ-45',
                    default => 'Documento',
                },
                'done' => $draftDone && $comunicadoDone,
                'hint' => match ($case->decision) {
                    Decision::AMONESTACION_ESCRITA => 'Diligencie el FO-GJ-46 y genere el PDF en el expediente.',
                    Decision::SUSPENSION => 'Diligencie el FO-GJ-47 y genere el PDF en el expediente.',
                    Decision::TERMINACION_CONTRATO => 'Diligencie el FO-GJ-45 y genere el PDF en el expediente.',
                    default => 'Diligencie el documento de decisión y genere el PDF.',
                },
            ],
            [
                'key' => 'delivery',
                'label' => $needsPackage ? 'Paquete terminación' : 'Firma trabajador',
                'done' => $needsPackage ? $packageDone : $evidenceDone,
                'hint' => $needsPackage
                    ? 'Cargue un solo PDF con los anexos firmados de terminación.'
                    : 'El supervisor registra la firma del trabajador o evidencia con testigos.',
            ],
            [
                'key' => 'close',
                'label' => 'Cierre',
                'done' => $closed,
                'hint' => 'Escriba una breve conclusión y finalice el proceso.',
            ],
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
        return 5;
    }

    public function actionBarTitle(string $stepKey): string
    {
        return match ($stepKey) {
            'type' => 'Tipo de decisión',
            'coordination' => 'Programación con planeación',
            'draft' => 'Comunicado de decisión',
            'delivery' => 'Entrega / evidencia',
            'evidence' => 'Entrega / evidencia',
            'close' => 'Cierre del proceso',
            default => 'Decisión disciplinaria',
        };
    }
}
