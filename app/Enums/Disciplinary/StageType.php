<?php

namespace App\Enums\Disciplinary;

/**
 * Etapas del workflow disciplinario y su formato oficial asociado.
 */
enum StageType: string
{
    case INFORME = 'informe';
    case CITACION = 'citacion';
    case REPROGRAMACION = 'reprogramacion';
    case JUSTIFICACION = 'justificacion';
    case COMITE = 'comite';
    case DILIGENCIA = 'diligencia';
    case DECISION = 'decision';
    case APELACION = 'apelacion';
    case SEGUNDA_INSTANCIA = 'segunda_instancia';

    public function label(): string
    {
        return match ($this) {
            self::INFORME => 'Informe disciplinario',
            self::CITACION => 'Citación',
            self::REPROGRAMACION => 'Reprogramación',
            self::JUSTIFICACION => 'Justificación',
            self::COMITE => 'Comité disciplinario',
            self::DILIGENCIA => 'Diligencia',
            self::DECISION => 'Decisión',
            self::APELACION => 'Apelación',
            self::SEGUNDA_INSTANCIA => 'Segunda instancia',
        };
    }

    /**
     * Código oficial del formato (FO-GJ-XX) asociado a la etapa.
     */
    public function formCode(): ?string
    {
        return match ($this) {
            self::INFORME => 'FO-GJ-51',
            self::CITACION => 'FO-GJ-03',
            self::REPROGRAMACION => 'FO-GJ-54',
            self::DILIGENCIA => 'FO-GJ-42',
            default => null,
        };
    }
}
