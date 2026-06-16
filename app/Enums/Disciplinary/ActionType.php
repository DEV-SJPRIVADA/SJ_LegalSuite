<?php

namespace App\Enums\Disciplinary;

/**
 * Tipos de actuación que se registran en el log de auditoría
 * (tabla disciplinary_actions). Cada cambio relevante en el caso
 * deja un rastro inmutable para trazabilidad legal.
 */
enum ActionType: string
{
    case INFORME_ENVIADO = 'informe_enviado';
    case INFORME_APROBADO = 'informe_aprobado';
    case INFORME_CANCELADO = 'informe_cancelado';
    case CASO_CREADO = 'caso_creado';
    case CASO_ASIGNADO = 'caso_asignado';
    case CASO_ACEPTADO_ABOGADO = 'caso_aceptado_abogado';
    case CASO_REASIGNADO = 'caso_reasignado';
    case COORDINACION_INICIADA = 'coordinacion_iniciada';
    case COORDINACION_CERRADA = 'coordinacion_cerrada';
    case PLANEACION_RESPONDIO = 'planeacion_respondio';
    case FECHA_CITACION_SELECCIONADA = 'fecha_citacion_seleccionada';
    case FO_GJ_03_GENERADO = 'fo_gj_03_generado';
    case FO_GJ_04_GENERADO = 'fo_gj_04_generado';
    case FO_GJ_44_GENERADO = 'fo_gj_44_generado';
    case FO_GJ_54_GENERADO = 'fo_gj_54_generado';
    case DILIGENCIA_ASISTENCIA_REGISTRADA = 'diligencia_asistencia_registrada';
    case NOTIFICACION_COORDINADA = 'notificacion_coordinada';
    case SUPERVISOR_NOTIFICADOR_ASIGNADO = 'supervisor_notificador_asignado';
    case SUPERVISOR_NOTIFICADOR_REASIGNADO = 'supervisor_notificador_reasignado';
    case EVIDENCIA_CITACION_CARGADA = 'evidencia_citacion_cargada';
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
    case FECHA_ETAPA_ACTUALIZADA = 'fecha_etapa_actualizada';
}
