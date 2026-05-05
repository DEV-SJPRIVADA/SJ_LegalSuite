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
            self::INFORME => 'Informe disciplinario (FO-GJ-51)',
            self::CITACION => 'Citación a diligencia disciplinaria por escrito (FO-GJ-03)',
            self::REPROGRAMACION => 'Reprogramación a diligencia disciplinaria (FO-GJ-54)',
            self::JUSTIFICACION => 'Justificación de inasistencia a citación',
            self::COMITE => 'Comité disciplinario',
            self::DILIGENCIA => 'Diligencia disciplinaria — acta (FO-GJ-42)',
            self::DECISION => 'Comunicado de decisión de sanción o cierre del proceso',
            self::APELACION => 'Recurso de apelación contra la decisión disciplinaria',
            self::SEGUNDA_INSTANCIA => 'Decisión de segunda instancia',
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
