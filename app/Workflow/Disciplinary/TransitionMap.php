<?php

namespace App\Workflow\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;

/**
 * Mapa de transiciones permitidas entre estados del caso disciplinario.
 *
 * Alineación con etapas del proceso disciplinario SJ:
 * **A.** Falta e informe disciplinario (FO-GJ-51).
 * **B.** Citación a diligencia disciplinaria por escrito (FO-GJ-03). Si no asiste: constancia de inasistencia
 *     y **2 días calendario** para justificar; si justifica → reprogramación (FO-GJ-54); si no → comité disciplinario para decisión.
 * **C.** Diligencia disciplinaria y levantamiento de acta (FO-GJ-04).
 * **D.** Comunicado de decisión de sanción o cierre del proceso (`DECISION`).
 * **E.** Recurso de apelación (`APELACION`).
 * **F.** Decisión de segunda instancia (`SEGUNDA_INSTANCIA`).
 *
 * Cada clave es el estado origen, y su valor es el listado de estados destino válidos.
 * Esta tabla es la única fuente de verdad del workflow: si una transición no está aquí,
 * no se permite. El WorkflowService consulta este mapa antes de aplicar cualquier cambio.
 */
final class TransitionMap
{
    /**
     * @return array<string, list<CaseStatus>>
     */
    public static function map(): array
    {
        return [
            CaseStatus::BORRADOR->value => [
                CaseStatus::INFORME,
                CaseStatus::ARCHIVADO,
            ],
            CaseStatus::INFORME->value => [
                CaseStatus::CITACION_PROGRAMADA,
                CaseStatus::ARCHIVADO,
            ],
            CaseStatus::CITACION_PROGRAMADA->value => [
                CaseStatus::DILIGENCIA,             // asistió
                CaseStatus::CITACION_NO_ASISTIO,    // no asistió
                CaseStatus::REPROGRAMADO,
            ],
            CaseStatus::CITACION_NO_ASISTIO->value => [
                CaseStatus::JUSTIFICACION_PENDIENTE,
                CaseStatus::COMITE_DISCIPLINARIO,
            ],
            CaseStatus::JUSTIFICACION_PENDIENTE->value => [
                CaseStatus::REPROGRAMADO,           // justificación aceptada
                CaseStatus::COMITE_DISCIPLINARIO,   // justificación rechazada o no llegó
            ],
            CaseStatus::REPROGRAMADO->value => [
                CaseStatus::CITACION_PROGRAMADA,
            ],
            CaseStatus::COMITE_DISCIPLINARIO->value => [
                CaseStatus::DILIGENCIA,
                CaseStatus::DECISION,
            ],
            CaseStatus::DILIGENCIA->value => [
                CaseStatus::DECISION,
                CaseStatus::JUSTIFICACION_PENDIENTE,
            ],
            CaseStatus::DECISION->value => [
                CaseStatus::APELACION,
                CaseStatus::FINALIZADO,
            ],
            CaseStatus::APELACION->value => [
                CaseStatus::SEGUNDA_INSTANCIA,
                CaseStatus::FINALIZADO,
            ],
            CaseStatus::SEGUNDA_INSTANCIA->value => [
                CaseStatus::FINALIZADO,
            ],
            CaseStatus::FINALIZADO->value => [
                CaseStatus::ARCHIVADO,
            ],
            CaseStatus::ARCHIVADO->value => [],
        ];
    }

    public static function canTransition(CaseStatus $from, CaseStatus $to): bool
    {
        $allowed = self::map()[$from->value] ?? [];

        return in_array($to, $allowed, true);
    }

    /**
     * @return list<CaseStatus>
     */
    public static function allowedFrom(CaseStatus $from): array
    {
        return self::map()[$from->value] ?? [];
    }
}
