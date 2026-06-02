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
        $notification = $this->notification;

        $coordinationStarted = $case->hasCoordinationStarted();
        $lawyerRequested = $case->hasLawyerDiligenceDateRequest();
        $planningReplied = $case->hasAgendaPlanningReply();
        $dateConfirmed = $case->citation_confirmed_date !== null;
        $notificationDone = $notification->hasNotificationInformationCompleted($case);
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
                'key' => 'date_request',
                'label' => 'Solicitud de fechas de diligencia',
                'done' => $lawyerRequested,
                'hint' => 'Envíe la solicitud formal a Planeación.',
            ],
            [
                'key' => 'planning_slots',
                'label' => 'Fechas propuestas por Planeación',
                'done' => $planningReplied,
                'hint' => 'Planeación responde en Coordinaciones con fechas disponibles.',
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

        if ($case->hasAgendaPlanningReply()) {
            return 'Planeación respondió — seleccione fecha definitiva';
        }

        if ($case->hasLawyerDiligenceDateRequest()) {
            return 'Solicitud enviada — pendiente de Planeación';
        }

        if ($case->hasCoordinationStarted()) {
            return 'Pendiente de solicitar fechas';
        }

        return 'Coordinación no iniciada';
    }

    /** @return list<string> */
    public function blockersBeforeClosingCoordination(DisciplinaryCase $case): array
    {
        $blockers = [];

        if ($this->notification->hasPendingNotificationRequest($case)) {
            $blockers[] = 'Hay una solicitud de notificación física pendiente de respuesta de Planeación.';
        }

        if ($case->citation_confirmed_date === null) {
            $blockers[] = 'Aún no se ha confirmado la fecha definitiva de diligencia.';
        }

        if (! $case->hasLawyerDiligenceDateRequest()) {
            $blockers[] = 'Debe enviar la solicitud formal de programación de fechas a Planeación.';
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
}
