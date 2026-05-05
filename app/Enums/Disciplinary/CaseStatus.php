<?php

namespace App\Enums\Disciplinary;

/**
 * Estado actual del proceso disciplinario.
 *
 * Es el estado "denormalizado" cacheado en la tabla disciplinary_cases.current_status
 * para que las consultas del dashboard sean instantáneas (evitamos calcularlo desde
 * disciplinary_stages cada vez).
 */
enum CaseStatus: string
{
    case BORRADOR = 'borrador';
    case INFORME = 'informe';
    case CITACION_PROGRAMADA = 'citacion_programada';
    case CITACION_NO_ASISTIO = 'citacion_no_asistio';
    case JUSTIFICACION_PENDIENTE = 'justificacion_pendiente';
    case REPROGRAMADO = 'reprogramado';
    case COMITE_DISCIPLINARIO = 'comite_disciplinario';
    case DILIGENCIA = 'diligencia';
    case DECISION = 'decision';
    case APELACION = 'apelacion';
    case SEGUNDA_INSTANCIA = 'segunda_instancia';
    case FINALIZADO = 'finalizado';
    case ARCHIVADO = 'archivado';

    public function label(): string
    {
        return match ($this) {
            self::BORRADOR => 'Borrador',
            self::INFORME => 'Informe disciplinario',
            self::CITACION_PROGRAMADA => 'Citación a diligencia disciplinaria',
            self::CITACION_NO_ASISTIO => 'Inasistencia a citación',
            self::JUSTIFICACION_PENDIENTE => 'Justificación de inasistencia',
            self::REPROGRAMADO => 'Reprogramación de diligencia',
            self::COMITE_DISCIPLINARIO => 'Comité disciplinario',
            self::DILIGENCIA => 'Diligencia disciplinaria',
            self::DECISION => 'Decisión / comunicación de sanción o cierre',
            self::APELACION => 'Recurso de apelación',
            self::SEGUNDA_INSTANCIA => 'Decisión segunda instancia',
            self::FINALIZADO => 'Finalizado',
            self::ARCHIVADO => 'Archivado',
        };
    }

    /**
     * Bucket de alto nivel usado por los KPIs del dashboard.
     */
    public function bucket(): CaseBucket
    {
        return match ($this) {
            self::BORRADOR, self::INFORME => CaseBucket::PENDIENTE,
            self::FINALIZADO, self::ARCHIVADO => CaseBucket::FINALIZADO,
            default => CaseBucket::EN_PROCESO,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::FINALIZADO, self::ARCHIVADO], true);
    }
}
