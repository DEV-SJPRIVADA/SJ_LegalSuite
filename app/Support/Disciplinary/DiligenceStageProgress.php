<?php

namespace App\Support\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Support\Collection;

/**
 * Progreso guiado de Etapa C (diligencia disciplinaria — FO-GJ-42).
 */
final class DiligenceStageProgress
{
    public const STATUS_DONE = 'done';

    public const STATUS_CURRENT = 'current';

    public const STATUS_PENDING = 'pending';

    /** @return Collection<int, array{key: string, label: string, status: string, hint: string}> */
    public function steps(DisciplinaryCase $case): Collection
    {
        $case = $case->fresh(['documents']);

        $hearingScheduled = $case->citation_confirmed_date !== null;
        $actaUploaded = $case->latestActaDiligenciaDocument() !== null;

        $defs = [
            [
                'key' => 'hearing',
                'label' => 'Diligencia programada',
                'done' => $hearingScheduled,
                'hint' => 'La fecha definitiva quedó registrada en la etapa de citación.',
            ],
            [
                'key' => 'acta',
                'label' => 'Acta FO-GJ-42',
                'done' => $actaUploaded,
                'hint' => 'Diligencie el acta y incorpórela al expediente cuando esté lista.',
            ],
            [
                'key' => 'decision',
                'label' => 'Comunicado de decisión',
                'done' => false,
                'hint' => 'Avance a la etapa D cuando la diligencia y el acta estén listas.',
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
            'key' => 'decision',
            'label' => 'Comunicado de decisión',
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

    public function totalSteps(): int
    {
        return 3;
    }

    public function actionBarTitle(string $stepKey): string
    {
        return match ($stepKey) {
            'hearing' => 'Diligencia programada',
            'acta' => 'Acta de diligencia (FO-GJ-42)',
            'decision' => 'Avance a decisión',
            default => 'Diligencia disciplinaria',
        };
    }
}
