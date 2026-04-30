<?php

namespace App\Enums\Disciplinary;

/**
 * Tipos de actuación que se registran en el log de auditoría
 * (tabla disciplinary_actions). Cada cambio relevante en el caso
 * deja un rastro inmutable para trazabilidad legal.
 */
enum ActionType: string
{
    case CASO_CREADO = 'caso_creado';
    case CASO_ASIGNADO = 'caso_asignado';
    case ESTADO_TRANSICIONADO = 'estado_transicionado';
    case ETAPA_INICIADA = 'etapa_iniciada';
    case ETAPA_COMPLETADA = 'etapa_completada';
    case DOCUMENTO_CARGADO = 'documento_cargado';
    case DOCUMENTO_ELIMINADO = 'documento_eliminado';
    case FALTA_AGREGADA = 'falta_agregada';
    case FALTA_REMOVIDA = 'falta_removida';
    case CITACION_PROGRAMADA = 'citacion_programada';
    case CITACION_INASISTENCIA = 'citacion_inasistencia';
    case JUSTIFICACION_RECIBIDA = 'justificacion_recibida';
    case JUSTIFICACION_ACEPTADA = 'justificacion_aceptada';
    case JUSTIFICACION_RECHAZADA = 'justificacion_rechazada';
    case REPROGRAMADO = 'reprogramado';
    case COMITE_REMITIDO = 'comite_remitido';
    case DECISION_TOMADA = 'decision_tomada';
    case APELACION_INTERPUESTA = 'apelacion_interpuesta';
    case SEGUNDA_INSTANCIA_RESUELTA = 'segunda_instancia_resuelta';
    case CASO_FINALIZADO = 'caso_finalizado';
    case CASO_ARCHIVADO = 'caso_archivado';
    case COMENTARIO = 'comentario';
}
