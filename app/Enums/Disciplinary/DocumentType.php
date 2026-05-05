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
            self::INFORME => 'Informe disciplinario (FO-GJ-51)',
            self::CITACION => 'Citación a diligencia disciplinaria por escrito (FO-GJ-03)',
            self::REPROGRAMACION => 'Reprogramación a diligencia disciplinaria (FO-GJ-54)',
            self::JUSTIFICACION => 'Justificación de inasistencia',
            self::ACTA_DILIGENCIA => 'Acta de diligencia disciplinaria (FO-GJ-42)',
            self::DECISION => 'Comunicado de decisión de sanción o cierre del proceso',
            self::APELACION => 'Recurso de apelación contra la decisión disciplinaria',
            self::SEGUNDA_INSTANCIA => 'Decisión de segunda instancia',
            self::EVIDENCIA => 'Evidencia',
            self::OTRO => 'Otro',
        };
    }
}
