<?php

namespace App\Enums\Disciplinary;

enum DocumentType: string
{
    case INFORME = 'informe';
    case CITACION = 'citacion';
    case REPROGRAMACION = 'reprogramacion';
    case JUSTIFICACION = 'justificacion';
    case ACTA_DILIGENCIA = 'acta_diligencia';
    case DECISION = 'decision';
    case APELACION = 'apelacion';
    case SEGUNDA_INSTANCIA = 'segunda_instancia';
    case EVIDENCIA = 'evidencia';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::INFORME => 'Informe (FO-GJ-51)',
            self::CITACION => 'Citación (FO-GJ-03)',
            self::REPROGRAMACION => 'Reprogramación (FO-GJ-54)',
            self::JUSTIFICACION => 'Justificación',
            self::ACTA_DILIGENCIA => 'Acta de diligencia (FO-GJ-42)',
            self::DECISION => 'Decisión',
            self::APELACION => 'Apelación',
            self::SEGUNDA_INSTANCIA => 'Segunda instancia',
            self::EVIDENCIA => 'Evidencia',
            self::OTRO => 'Otro',
        };
    }
}
