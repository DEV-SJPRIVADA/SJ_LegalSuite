<?php

namespace App\Support\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Support\Collection;

/**
 * Progreso guiado de Etapa C (diligencia disciplinaria).
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
        $attendance = $case->diligence_attendance;
        $hearingScheduled = $case->citation_confirmed_date !== null;
        $attendanceRegistered = $attendance !== null;

        if ($attendance === DiligenceAttendance::ABSENT) {
            $constanciaDone = $case->fo_gj_44_generated_at !== null
                || $case->latestConstanciaInasistenciaDocument() !== null;
            $justificationDone = $case->current_status !== CaseStatus::DILIGENCIA
                && $case->current_status !== CaseStatus::JUSTIFICACION_PENDIENTE;

            $defs = [
                [
                    'key' => 'attendance',
                    'label' => 'Asistencia',
                    'done' => $attendanceRegistered,
                    'hint' => 'Registre si el trabajador asistió o no a la diligencia programada.',
                ],
                [
                    'key' => 'hearing',
                    'label' => 'Diligencia programada',
                    'done' => $hearingScheduled,
                    'hint' => 'La fecha definitiva quedó registrada en la etapa de citación.',
                ],
                [
                    'key' => 'constancia',
                    'label' => 'Constancia FO-GJ-44',
                    'done' => $constanciaDone,
                    'hint' => 'Diligencie la constancia de inasistencia e incorpórela al expediente.',
                ],
                [
                    'key' => 'justification',
                    'label' => 'Justificación (2 días)',
                    'done' => $justificationDone,
                    'hint' => 'Ventana de 2 días calendario: aceptar justificación y reprogramar, o remitir a comité.',
                ],
            ];
        } else {
            $actaUploaded = $case->fo_gj_04_generated_at !== null
                || $case->latestActaDiligenciaDocument() !== null;

            $defs = [
                [
                    'key' => 'attendance',
                    'label' => 'Asistencia',
                    'done' => $attendanceRegistered,
                    'hint' => 'Registre si el trabajador asistió o no a la diligencia programada.',
                ],
                [
                    'key' => 'hearing',
                    'label' => 'Diligencia programada',
                    'done' => $hearingScheduled,
                    'hint' => 'La fecha definitiva quedó registrada en la etapa de citación.',
                ],
                [
                    'key' => 'acta',
                    'label' => 'Acta FO-GJ-04',
                    'done' => $actaUploaded,
                    'hint' => 'Diligencie el acta, capture la firma del trabajador e incorpórela al expediente.',
                ],
                [
                    'key' => 'decision',
                    'label' => 'Comunicado de decisión',
                    'done' => false,
                    'hint' => 'Avance a la etapa D cuando la diligencia y el acta estén listas.',
                ],
            ];
        }

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
        return 4;
    }

    public function actionBarTitle(string $stepKey): string
    {
        return match ($stepKey) {
            'attendance' => 'Registro de asistencia',
            'hearing' => 'Diligencia programada',
            'acta' => 'Acta de diligencia (FO-GJ-04)',
            'constancia' => 'Constancia de inasistencia (FO-GJ-44)',
            'justification' => 'Justificación de inasistencia',
            'decision' => 'Avance a decisión',
            default => 'Diligencia disciplinaria',
        };
    }
}
