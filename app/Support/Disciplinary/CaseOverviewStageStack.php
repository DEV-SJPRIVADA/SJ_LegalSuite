<?php

namespace App\Support\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;

/**
 * Orden de etapas en la pila legacy (pestaña Gestión usa tarjetas A–D).
 * La etapa más reciente va primero; la A (informe FO-GJ-51) siempre al final.
 */
final class CaseOverviewStageStack
{
    /** @return list<string> Claves: d, c, b, a de arriba hacia abajo. */
    public function stagesForCase(DisciplinaryCase $case): array
    {
        $stack = [];

        if ($case->showsDecisionStagePanel()) {
            $stack[] = 'd';
        }

        if ($case->showsDiligenceStagePanel() || $case->showsDiligenceStageReadOnly()) {
            $stack[] = 'c';
        }

        if ($case->current_status !== CaseStatus::COMITE_DISCIPLINARIO
            && $case->current_status !== CaseStatus::DECISION
            && ($case->current_status === CaseStatus::CITACION_PROGRAMADA
                || $case->showsCitationStageReadOnly())) {
            $stack[] = 'b';
        }

        $stack[] = 'a';

        return $stack;
    }
}
