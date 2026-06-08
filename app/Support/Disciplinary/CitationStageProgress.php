<?php

namespace App\Support\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
use Illuminate\Support\Collection;

/**
 * Progreso guiado de Etapa B (citación FO-GJ-03) para abogado y planeación.
 */
final class CitationStageProgress
{
    public const STATUS_DONE = 'done';

    public const STATUS_CURRENT = 'current';

    public const STATUS_PENDING = 'pending';

    public const STATUS_BLOCKED = 'blocked';

    public function __construct(
        private readonly DisciplinaryCitationNotificationService $notification,
    ) {}

    /** @return Collection<int, array{key: string, label: string, status: string, hint: string}> */
    public function steps(DisciplinaryCase $case): Collection
    {
        $case = $case->fresh(['agendaThread.messages']);

        $coordinationStarted = $case->hasCoordinationStarted();
        $planningSlots = $case->hasPlanningProposedSlots();
        $dateConfirmed = $case->citation_confirmed_date !== null;
        $notificationDone = $this->notification->hasNotificationInformationCompleted($case);
        $foGj03 = $case->fo_gj_03_generated_at !== null;
        $evidence = $case->citation_evidence_uploaded_at !== null;

        $defs = [
            [
                'key' => 'coordination',
                'label' => 'Coordinación iniciada',
                'done' => $coordinationStarted,
                'hint' => 'Inicie el hilo con Planeación desde este expediente.',
            ],
            [
                'key' => 'planning_slots',
                'label' => 'Fechas propuestas por Planeación',
                'done' => $planningSlots,
                'hint' => 'Planeación registra fechas en Coordinaciones.',
            ],
            [
                'key' => 'definitive_date',
                'label' => 'Fecha definitiva confirmada',
                'done' => $dateConfirmed,
                'hint' => 'Seleccione una de las opciones recibidas.',
            ],
            [
                'key' => 'notification',
                'label' => 'Información de notificación física',
                'done' => $notificationDone,
                'hint' => 'Solicite y complete ingreso, turno, zona y supervisor.',
            ],
            [
                'key' => 'fo_gj_03',
                'label' => 'FO-GJ-03 generado',
                'done' => $foGj03,
                'hint' => 'Genere el formato desde el expediente.',
            ],
            [
                'key' => 'evidence',
                'label' => 'Evidencia PDF cargada',
                'done' => $evidence,
                'hint' => 'Citación firmada o rechazo con testigos.',
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

    public function diligenceDateRequestStatusLabel(DisciplinaryCase $case): string
    {
        if ($case->citation_confirmed_date !== null) {
            return 'Fecha de diligencia confirmada';
        }

        if ($case->hasPlanningProposedSlots()) {
            return 'Planeación propuso fechas — seleccione la definitiva';
        }

        if ($case->hasCoordinationStarted()) {
            return 'En coordinación — espere fechas de Planeación';
        }

        return 'Coordinación no iniciada';
    }

    /** @return list<string> */
    public function blockersBeforeClosingCoordination(DisciplinaryCase $case): array
    {
        $blockers = [];

        if ($this->notification->canPlanningRegisterNotification($case)) {
            $blockers[] = 'Planeación debe registrar la notificación física (ingreso, turno, zona y supervisor) en Coordinaciones.';
        }

        if ($case->citation_confirmed_date === null) {
            $blockers[] = 'Aún no se ha confirmado la fecha definitiva de diligencia.';
        }

        if (! $case->hasPlanningProposedSlots()) {
            $blockers[] = 'Planeación debe publicar fechas de diligencia en el hilo.';
        }

        if ($case->citation_confirmed_date !== null
            && ! $this->notification->hasNotificationInformationCompleted($case)) {
            $blockers[] = 'Complete la coordinación de notificación física (ingreso, turno, zona y supervisor) antes de cerrar.';
        }

        return $blockers;
    }

    public function canSafelyCloseCoordination(DisciplinaryCase $case): bool
    {
        return $this->blockersBeforeClosingCoordination($case) === [];
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
            'key' => 'evidence',
            'label' => 'Evidencia PDF cargada',
            'status' => self::STATUS_DONE,
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
        return 6;
    }

    public function actionBarTitle(string $stepKey): string
    {
        return match ($stepKey) {
            'coordination' => 'Coordinación con Planeación',
            'planning_slots' => 'Fechas propuestas por Planeación',
            'definitive_date' => 'Confirmar fecha de citación',
            'notification' => 'Notificación física del trabajador',
            'fo_gj_03' => 'Generar FO-GJ-03',
            'evidence' => 'Evidencia de notificación (PDF)',
            default => 'Citación a diligencia',
        };
    }

    /**
     * @deprecated La visibilidad del chat la controla el abogado (toggle) mientras el caso permite agenda.
     */
    public function chatIsPrimaryForStep(string $stepKey): bool
    {
        return in_array($stepKey, ['coordination', 'planning_slots', 'definitive_date', 'notification', 'fo_gj_03', 'evidence'], true);
    }
}
