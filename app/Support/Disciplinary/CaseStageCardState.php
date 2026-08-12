<?php

namespace App\Support\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;

/**
 * Estado de las tarjetas A–D en la pestaña Gestión del expediente.
 */
final class CaseStageCardState
{
    public const ACTIVE = 'active';

    public const COMPLETED = 'completed';

    public const LOCKED = 'locked';

    /**
     * @return list<array{key: string, letter: string, title: string, short: string}>
     */
    public function cardDefinitions(): array
    {
        return [
            ['key' => 'a', 'letter' => 'A', 'title' => 'Informe disciplinario', 'short' => 'Informe'],
            ['key' => 'b', 'letter' => 'B', 'title' => 'Citación a diligencia', 'short' => 'Citación'],
            ['key' => 'c', 'letter' => 'C', 'title' => 'Diligencia y acta', 'short' => 'Diligencia'],
            ['key' => 'd', 'letter' => 'D', 'title' => 'Comunicado de decisión', 'short' => 'Decisión'],
        ];
    }

    public function stateFor(DisciplinaryCase $case, string $key): string
    {
        return match (strtolower($key)) {
            'a' => $this->stateForA($case),
            'b' => $this->stateForB($case),
            'c' => $this->stateForC($case),
            'd' => $this->stateForD($case),
            default => self::LOCKED,
        };
    }

    public function lockedAlertMessage(string $key): string
    {
        return match (strtolower($key)) {
            'a' => 'Esta etapa está cerrada: el proceso aún no avanza hasta este punto.',
            default => 'Esta etapa está cerrada: el proceso aún no avanza hasta este punto.',
        };
    }

    private function stateForA(DisciplinaryCase $case): string
    {
        if ($case->current_status === CaseStatus::BORRADOR) {
            return self::LOCKED;
        }

        if ($case->current_status === CaseStatus::INFORME) {
            return $case->assigned_lawyer_id ? self::ACTIVE : self::LOCKED;
        }

        return self::COMPLETED;
    }

    private function stateForB(DisciplinaryCase $case): string
    {
        if ($case->showsCitationStageReadOnly()) {
            return self::COMPLETED;
        }

        if (in_array($case->current_status, [
            CaseStatus::CITACION_PROGRAMADA,
            CaseStatus::CITACION_NO_ASISTIO,
            CaseStatus::JUSTIFICACION_PENDIENTE,
        ], true)) {
            return self::ACTIVE;
        }

        if ($case->current_status === CaseStatus::REPROGRAMADO) {
            // Reprogramación operativa: la Etapa B (FO-GJ-03) ya quedó; el trámite sigue en C.
            if ($case->isOperationalReschedulePending()) {
                return self::COMPLETED;
            }

            return self::ACTIVE;
        }

        if ($case->hasCoordinationStarted()
            || $case->fo_gj_03_generated_at !== null
            || $case->citation_confirmed_date !== null) {
            return self::COMPLETED;
        }

        return self::LOCKED;
    }

    private function stateForC(DisciplinaryCase $case): string
    {
        if ($case->showsDiligenceStageReadOnly()) {
            return self::COMPLETED;
        }

        if ($case->showsDiligenceStagePanel()) {
            return self::ACTIVE;
        }

        if ($case->fo_gj_04_generated_at !== null
            || $case->comite_generated_at !== null
            || $case->latestActaDiligenciaDocument() !== null) {
            return self::COMPLETED;
        }

        return self::LOCKED;
    }

    private function stateForD(DisciplinaryCase $case): string
    {
        if ($case->showsDecisionStageReadOnly()) {
            return self::COMPLETED;
        }

        if ($case->showsDecisionStagePanel()) {
            return self::ACTIVE;
        }

        return self::LOCKED;
    }
}
