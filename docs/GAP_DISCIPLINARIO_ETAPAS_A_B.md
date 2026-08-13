# GAP Analysis — Etapas A y B (estado actual vs objetivo)

## Etapa A

| # | Objetivo | Estado anterior | Implementado |
|---|----------|-----------------|--------------|
| A1 | Revisor operaciones obligatorio al enviar FO-GJ-51 | Notificación a todos con `review-inform` | `assigned_reviewer_id` + select en formulario |
| A2 | Solo revisor asignado gestiona (dirección ve todos) | Cualquier revisor | `review-inform` + `review-inform-all` + política |
| A3 | Aprobar / cancelar | Aprobar / rechazar (borra registro) | Igual + auditoría `INFORME_*` |
| A4 | Caso sin abogado → Jurídica (pool) | Ya existía | Se mantiene |
| A5 | Claim atómico abogados | Ya existía | `CASO_ACEPTADO_ABOGADO` |
| A6 | Reasignación solo dirección jurídica | Solo admin `assign` | `disciplinary.assign` + `CASO_REASIGNADO` |

## Etapa B

| # | Objetivo | Estado anterior | Implementado |
|---|----------|-----------------|--------------|
| B1 | Chat no automático al pasar a B | Hilo al primer mensaje | Botón **Iniciar coordinación** |
| B2 | Notificar a planeación al iniciar | Al primer mensaje abogado | Notificación a todos `planeacion` |
| B3 | Cualquier usuario planeación ve/responde | Inbox con mensaje abogado previo | Inbox por `coordination_started_at` |
| B4 | Chat: mensajes, imágenes, PDF | Solo texto abogado; imágenes planeación | PDF en adjuntos planeación |
| B5 | Fechas estructuradas | Texto libre | `proposed_slots` JSON en mensajes |
| B6 | Abogado elige fecha definitiva | Manual en timeline | `confirmCitationSlot` + campos en caso |
| B7 | FO-GJ-03 desde expediente autocompletado | Solo plantilla en blanco | `FoGj03CitationService` + rutas |
| B8 | Evidencia PDF (firmada / testigos) | Sin flujo | Upload con `CitationEvidenceType` |
| B9 | No avanzar desde B sin requisitos | Sin validación UI | `DisciplinaryCitationWorkflowService` en `WorkflowService::transition` |
| B10 | Notificación física **antes** de proponer fechas | Fechas primero, luego notificación | `canPlanningManageNotification` → `canPlanningProposeDiligenceSlots`; stepper `CitationStageProgress` |
| B11 | Plantillas artículo/numeral por falta | Numerales fijos 66/68/76 en blanco | Catálogo `citation_statute_*` + Ajustes · Artículos + `FoGj03CitationArticleResolver` |
| B12 | Redacción FO-GJ por género del trabajador | Texto masculino genérico | `WorkerLegalPhrasing` + checklist `employee_gender` |

## Archivos principales tocados

- Migración: `2026_05_21_120000_disciplinary_workflow_target_state.php`, `2026_08_11_100000_create_citation_statute_tables.php`
- Servicios: `DisciplinaryInformeSubmissionService`, `DisciplinaryAgendaThreadService`, `FoGj03CitationService`, `FoGj03DraftService`, `FoGj03CitationArticleResolver`, `DisciplinaryCitationNotificationService`, `DisciplinaryCitationWorkflowService`, `CitationFaultTemplateService`, `DisciplinaryAuditService`
- Support: `CitationStageProgress`, `WorkerLegalPhrasing`
- Políticas: `InformeSubmissionPolicy`, `DisciplinaryCasePolicy`
- Livewire: `CasesIndex`, `CaseDetail`, `InformesPendientes`, `Coordinations\Index`, `Settings\CitationArticlesIndex`
- Vistas: `fo-gj-51-informe-body`, `stage-b-citation` partial, `fo-gj-03-*`, `settings/citation-articles-index`
- Permisos: `disciplinary.review-inform-all`, `settings.manage-citation-articles`
- Seed: `CitationFaultTemplatesSeeder`
