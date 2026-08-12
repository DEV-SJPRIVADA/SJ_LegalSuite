<?php

namespace App\Enums\Disciplinary;

enum DocumentType: string
{
    case INFORME = 'informe';
    case CITACION = 'citacion';
    case REPROGRAMACION = 'reprogramacion';
    case JUSTIFICACION = 'justificacion';
    case ACTA_DILIGENCIA = 'acta_diligencia';
    case CONSTANCIA_INASISTENCIA = 'constancia_inasistencia';
    case ACTA_COMITE = 'acta_comite';
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
            self::ACTA_DILIGENCIA => 'Acta de diligencia disciplinaria (FO-GJ-04)',
            self::CONSTANCIA_INASISTENCIA => 'Constancia de inasistencia a diligencia (FO-GJ-44)',
            self::ACTA_COMITE => 'Acta de comité disciplinario',
            self::DECISION => 'Comunicado de decisión / FO-GJ-46 llamado de atención',
            self::APELACION => 'Recurso de apelación contra la decisión disciplinaria',
            self::SEGUNDA_INSTANCIA => 'Decisión de segunda instancia',
            self::EVIDENCIA => 'Evidencia',
            self::OTRO => 'Otro',
        };
    }
}
