<?php

namespace App\Support\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;

/**
 * Orden de etapas en la pestaña Información del detalle del caso.
 * La etapa más reciente va primero; la A (informe FO-GJ-51) siempre al final.
 */
final class CaseOverviewStageStack
{
    /** @return list<string> Claves: c, b, a (y futuras d, e, f) de arriba hacia abajo. */
    public function stagesForCase(DisciplinaryCase $case): array
    {
        $stack = [];

        if ($case->isDiligenciaStageActive()) {
            $stack[] = 'c';
        }

        if ($case->current_status === CaseStatus::CITACION_PROGRAMADA
            || $case->showsCitationStageReadOnly()) {
            $stack[] = 'b';
        }

        $stack[] = 'a';

        return $stack;
    }
}
