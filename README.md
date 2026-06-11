<h1 align="center">SJ LegalSuite</h1>

<p align="center">
  <strong>Plataforma jurídica integral para SJ Seguridad</strong><br>
  Sistema centralizado para administrar todos los procesos del área jurídica con
  control de etapas, trazabilidad legal completa y reportes en tiempo real.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Livewire-3-FB70A9?logo=livewire&logoColor=white" alt="Livewire 3">
  <img src="https://img.shields.io/badge/Tailwind-4-06B6D4?logo=tailwindcss&logoColor=white" alt="TailwindCSS 4">
</p>

---

## 📦 Módulos del sistema

SJ LegalSuite está diseñado como una suite de **12 módulos jurídicos**. La construcción
es incremental: el módulo Disciplinario es el núcleo y ya está operativo. Los demás
aparecen en el sidebar como placeholders ("Próx.") hasta que se desarrollen.

| # | Módulo | Estado |
|---|---|---|
| 1 | 🏠 **Inicio** (Dashboard global con alertas) | ✅ Disponible |
| 2 | ⚖️ **Disciplinarios** | ✅ Disponible |
| 3 | 💼 Licitaciones | 🚧 Próximamente |
| 4 | 🛡️ Acciones de tutela | 🚧 Próximamente |
| 5 | 📄 Demandas | 🚧 Próximamente |
| 6 | 👥 Negociación colectiva | 🚧 Próximamente |
| 7 | 🔍 Investigaciones | 🚧 Próximamente |
| 8 | 💰 Cartera | 🚧 Próximamente |
| 9 | 📋 Requisitos legales | 🚧 Próximamente |
| 10 | 📑 Contratos | 🚧 Próximamente |
| 11 | 🛡️ Pólizas | 🚧 Próximamente |
| 12 | 📊 Auditoría | 🚧 Próximamente |

Además del catálogo jurídico, existen en el sidebar:

- **Empleados** (`employees.view` / `employees.manage`): **BD DE EMPLEADOS SJ** — alta/edición, carga masiva Excel (`.xlsx`) con plantilla descargable, loader con icono de mazo (`public/images/Mazo_juez.jpg`) y contador de tiempo. Tabla `employees` (sustituye el antiguo `personnel`).
- **Usuarios** (`users.view` / `users.manage`): listado con filtros, alta/edición, activación y reinicio de contraseña con contraseña provisional generada automáticamente.

Quienes tengan **`settings.manage-territory`** ven **Ajustes** en el sidebar: pantalla **`/settings/territorio`** para importar el listado **DIVIPOLA** (municipios con código oficial y coordenadas). Ese catálogo alimenta los **pins del mapa** en el dashboard disciplinario y la vinculación por municipio en los expedientes.

## ✨ Características principales (módulo Disciplinario)

- **Workflow estricto y validado**: 13 estados, transiciones controladas, plazo de **2 días calendario** para justificar inasistencia a citación (tras constancia).
- **Trazabilidad legal completa**: cada cambio en un caso queda registrado en un audit log inmutable.
- **Roles y permisos granulares** (Spatie Permission v6): paquetes de permisos técnicos (`admin`, `abogado`, `planeacion`, etc.). En negocio, el **área** es el ámbito organizacional (Jurídica, Operaciones…); **dentro del área** el usuario tiene un **cargo** (supervisor, operador, programador…). Cada cargo enlaza a un rol Spatie vía **`job_positions.permission_role_name`** (configurable en **Usuarios → Organización**). El perfil **`admin`** es aparte: «Administrador de la plataforma» en el formulario de usuario. Más el flag **solo lectura** por usuario.
- **Dashboard analítico**: donas por **etapa del flujo** (total + **A–F** según `current_stage_type`, vía `DisciplinaryDashboardService::workflowStageDonuts`), más distribución por **tipo de falta**, **mapa Leaflet de Colombia** (límites GADM por departamento y, al acercar zoom, municipios; pins con total de casos por municipio según DIVIPOLA en catálogo) y **carga por abogado** (consultas agregadas eficientes).
- **Listado de casos** con 7 filtros combinables y paginación, optimizado para alto volumen.
- **Documentos por etapa** con verificación de integridad (SHA-256) y vinculación a formatos oficiales (FO-GJ-XX).
- **Etapa C (diligencia y acta):** **FO-GJ-04** reemplaza al código histórico FO-GJ-42; acta HTML→PDF con textos legales fijos y **paginación híbrida** (`FoGj04PagePlanner`: reparte el cuestionario por capacidad de línea y reserva cierre + firmas en la última hoja); fecha del incumplimiento y cargos heredados del **FO-GJ-03**; modal con cabecera/pie fijos y cuerpo con scroll; cuestionario dinámico (vacío al inicio) con **pregunta + respuesta obligatoria** del trabajador; manifestación **SI QUIERO RESPONDER** / **NO DESEA RESPONDER**; vista previa y generación al expediente (`FoGj04DraftService`, `FoGj04DiligenceActaService`); firma del trabajador en blanco (captura en pantalla pendiente).
- **Etapas A y B (informe + citación):** revisor de operaciones obligatorio al enviar FO-GJ-51; **número de expediente** `GJ-PD:NNNNNN` (consecutivo global en casos nuevos); coordinación explícita con planeación en citación; selección visual de fecha definitiva; **coordinación de notificación física (B.2)** — al publicar fechas de diligencia, planeación registra ingreso/turno/zona/supervisor sin solicitud manual del abogado (`canPlanningRegisterNotification`); barra compacta en expediente con **fecha de diligencia** y **datos de notificación**; chat con adjuntos (clip, pegar, arrastrar) y lightbox con zoom; **diligenciamiento FO-GJ-03** en modal (`FoGj03DraftService`: hora editable, presencial/virtual + enlace, fecha de incumplimiento, numerales art. 66/68/76, descripción de hechos; fecha del informe y datos del trabajador automáticos); **firma digital** del abogado en **Mi perfil** (`UserSignatureService`) incrustada en el PDF; vista previa y generación bloqueadas hasta completar borrador + firma; avance a diligencia desde la UI con checklist de requisitos (`docs/GAP_DISCIPLINARIO_ETAPAS_A_B.md`). **Evidencia de citación:** habilitada solo tras generar FO-GJ-03; tipos `signed` (citación firmada) o `refused_witnesses` (rechazo con dos testigos). Pueden cargarla el abogado titular, el **supervisor asignado para notificación** (`notification_supervisor_user_id`), el usuario de operaciones que autorizó el FO-GJ-51 (`reviewed_by`), dirección de operaciones (`disciplinary.review-inform-all`) y dirección jurídica (`admin` / `disciplinary.assign`). El supervisor opera desde **Evidencias pendientes** sin acceso al expediente: **PDF escaneado** (vista previa + confirmación) o **notificación en pantalla** (HTML carta + firma táctil del trabajador o rechazo con testigos).

### Gestión de usuarios y contraseñas

- **Alta**: contraseña provisional aleatoria; modal único para copiar y enviar por canal seguro; el usuario debe cambiarla en el **primer ingreso** (`must_change_password`).
- **Reinicio por administrador** (icono de llave en listado o detalle): se genera una nueva provisional; **Cancelar** descarta; **Aceptar** persiste el cambio y mantiene la obligación de cambiar contraseña al iniciar sesión.
- **Middleware `must-change-password`**: redirige a `/password/first-login` hasta que el usuario define una contraseña definitiva (compatible con el resto de rutas autenticadas).

## 🖥️ Interfaz de usuario

### Layout global

- **Sidebar lateral fijo** con los 12 módulos del sistema. Los disponibles tienen acceso directo;
  los demás aparecen deshabilitados con badge "Próx." para que el cliente vea el alcance completo
  desde el primer día.
- **Topbar** con acceso a perfil y botón de salir.
- **Sub-nav contextual** por módulo (sticky bajo el topbar).
- **Responsive**: en móvil el sidebar se oculta y se accede con un botón hamburguesa.

### Botones de acción (`sj-btn` / `<x-ui.btn>`)

Los botones de acción del módulo disciplinario comparten altura y tipografía vía clases en `resources/css/app.css` (`.sj-btn`, altura fija `h-8`) y el componente Blade **`components/ui/btn.blade.php`**. Cada variante solo cambia el color (`primary`, `secondary`, `teal`, `ghost`, `dark`, `success`, `danger`, `muted`, `warning`, etc.). El badge de estado (`status-badge`) admite `size="md"` para alinearse con los botones en barras compactas (p. ej. encabezado del detalle del caso). Migración gradual: detalle del caso, listado disciplinario y modal de vista previa de documentos ya usan `<x-ui.btn>`; el resto de vistas pueden adoptarlo sin cambiar la lógica Livewire.

### Tema claro u oscuro

- Cada usuario puede elegir **tema claro** u **oscuro** desde el interruptor en la barra superior (Livewire `ThemeToggle`).
- La preferencia se guarda en la columna **`users.theme`** (`light` | `dark`). Es necesario ejecutar las migraciones (`php artisan migrate`) para crear esa columna.
- El middleware **`ShareUiTheme`** comparte `$uiTheme` con las vistas; el layout aplica la clase `dark` en `<html>` cuando corresponde (Tailwind `darkMode: 'class'`).

Las pantallas principales usan variantes `dark:` para mantener contraste y legibilidad en ambos modos.

### Livewire `wire:navigate`, Vite y consola del navegador

Muchas vistas usan **`wire:navigate`**. Eso evita recargar la página completa, pero el navegador puede mostrar avisos repetidos si los assets no encajan con ese modo.

| Tema | Qué hace el proyecto |
|------|------------------------|
| **Preload de CSS (avisos amarillos)** | Laravel **@vite** inserta `<link rel="preload" as="style">` además del `<link rel="stylesheet">`. Chrome a veces avisa *«preloaded but not used»* al navegar. En **`AppServiceProvider`** se omite el preload **solo para CSS** con `Vite::usePreloadTagAttributes` (los **modulepreload** de JS se mantienen). El callback debe tipar el primer argumento como **`?string $src`**: el framework puede pasar `null` en algunos chunks. |
| **ApexCharts + SVG** | **`resources/js/apex-charts-lifecycle.js`** destruye instancias al salir de la vista (`livewire:navigating` y hook `morph.removing`) para que no queden SVG huérfanos que Livewire intente actualizar (errores de `radialGradient` / `path` en consola). |
| **Mapa Colombia (Leaflet)** | **`resources/js/disciplinary-colombia-map.js`** comprueba que exista `bringToFront` antes de llamarlo (no todas las capas lo exponen en todos los contextos). |
| **Campanita de notificaciones** | Componente **`livewire:ui.notification-bell`**: **`wire:poll.visible.5s`** para no disparar tantas peticiones Livewire con la pestaña en segundo plano. |
| **`APP_KEY` y 500 intermitente** | Si el log muestra `MissingAppKeyException`, **`public/index.php`** intenta cargar de nuevo el `.env` con **Dotenv** cuando `APP_KEY` no está en el entorno antes del bootstrap (útil si el fichero se guarda mientras Apache atiende). Sigue siendo obligatorio tener **`APP_KEY=`** en `.env` y no publicar sin clave. |
| **Pestaña Issues (avisos “verdes”)** | Son sugerencias de **accesibilidad** de Chrome (p. ej. `label` sin `for`, campos sin `id`/`name`). En el **listado de casos disciplinarios**, los filtros enlazan etiqueta y controles con `for` + `id` + `name`. Otras pantallas se pueden alinear con el mismo criterio. |

### Vista de Inicio (Dashboard global)

Al iniciar sesión, el usuario ve un resumen de toda la operación:

- **4 tarjetas de alertas** (cada una con sus 5 items críticos linkeados):
  - 🔴 Plazos vencidos (etapas con deadline pasado)
  - 🟡 Próximos a vencer (plazo en 3 días o menos)
  - 🟦 Sin abogado asignado (incluye expedientes en **bandeja compartida** etapa INFORME, `assigned_lawyer_id` nulo)
  - 🩵 Pendientes de decisión
- **Gráfica de tendencia** mensual de casos abiertos (últimos 6 meses)
- **Acceso rápido** a los módulos disponibles

`AlertsService` es el agregador global y está preparado para sumar alertas de los demás módulos
cuando se vayan creando.

### Módulo Disciplinario

Sub-nav superior (según permisos y rol): por defecto **Inicio | Dashboard | Disciplinarios | (Revisión informes) | Formatos | Historial**. El enlace *Revisión informes* aparece quienes tienen **`disciplinary.review-inform`** (`InformeSubmissionPolicy::viewAny`). **Rol `abogado`:** `GET /disciplinary` y el ítem **Dashboard** llevan al tablero disciplinario; el ítem **Disciplinarios** (y el módulo en sidebar) usa `User::disciplinaryCasesNavUrl()` → **listado** `GET /disciplinary/cases`. **Rol `planeacion`:** en el sub-nav disciplinario solo **Coordinaciones** (`GET /disciplinary/coordinations`). **Rol `supervisor`:** en el sub-nav disciplinario entra a **Evidencias pendientes** (`GET /disciplinary/evidences-pending`). Planeación y supervisor quedan sin acceso al listado/detalle general de expedientes.

| Vista | Contenido |
|---|---|
| **Dashboard** | Encabezado reducido: solo la rúbrica **«Disciplinarios · Dashboard»** y el botón al listado de casos (sin título largo ni descripción). **Casos por etapa**: 7 donas (ApexCharts) — total + **A–F** según `current_stage_type` (centro con % y cantidad; **B** y **C** con agrupaciones acordadas); contenedor y rejilla compactos (`items-start`, sin padding inferior en la caja, altura de canvas ajustada) para limitar el aire bajo las donas; etiqueta corta por columna. ApexCharts se expone desde **Vite** (`resources/js/app.js` → `window.ApexCharts`) para compatibilidad con **`wire:navigate`**; el montaje en Blade **espera ancho de contenedor** antes de `render()` para evitar errores SVG (`NaN`). Entre páginas, **`resources/js/apex-charts-lifecycle.js`** destruye/recicla las instancias al navegar para no duplicar morfos en el DOM. Debajo: barras por **tipo de falta**, **mapa por ciudad** (Leaflet + GeoJSON GADM, tiles Carto; datos vía `disciplinary.map-geo`) y tabla **carga por abogado**. |
| **Disciplinarios** (listado) | 3 tarjetas de vistas rápidas + 7 filtros combinables + tabla paginada. **Rol `planeacion` y `supervisor`:** 403 en `CasesIndex` (no usan este listado). **Coordinaciones** (`planeacion`): bandeja de hilos **abiertos** con el abogado titular; el hilo sale de la bandeja al **avanzar el caso a diligencia** (cierre automático del hilo). **Bandeja compartida (etapa INFORME):** al autorizar un informe se crea el expediente con `assigned_lawyer_id = null`; todos los **abogados** y el **auditor** lo ven en el listado (columna **Bandeja compartida**). El abogado usa **Gestionar** → modal de confirmación → `DisciplinaryCaseService::claimByLawyer()` (asignación atómica + actuación **`CASO_ACEPTADO_ABOGADO`**); luego deja de estar en el pool para el resto de abogados. El auditor solo **Ver** (sin `claim`). **Etapa A (Informe):** el titular **no** chatea con planeación; tras revisar el caso pasa a citación o archiva. Botones **Nuevo informe (FO-GJ-51)** y **Cargar informe en PDF** abren un **modal** a pantalla completa con el formulario (no navegan a otra página). Enlaces desde catálogo o detalle de caso usan query `?informe_modal=1` (y `cedula` opcional). **FO-GJ-51:** campo obligatorio **Revisor de operaciones** (`fo51_assigned_reviewer_id`) en formulario y en modal de carga PDF; validación en `FoGj51ProcessRequest` y `DisciplinaryInformeSubmissionService`. Búsqueda de trabajador por **cédula** (solo dígitos) contra la BD de empleados (`resources/js/fo51-employee-combobox.js`); al elegir, se autocompletan **nombre** y **cargo** en pantalla. En el PDF generado (`enviar` / vista previa `pdf`), el campo **CARGO** del trabajador sale de **`employees.job_title`** vía `FoGj51InformeController::resolveWorkerCargoForPdf()` (empleado resuelto por id o documento) y la plantilla `fo-gj-51-filled-download` pasa `:worker-cargo` al componente; **turno** y **puesto** (`fo51_shift` / `fo51_position`) se diligencian manualmente y son distintos del cargo en BD. Grilla de **fecha** del informe: 4×2 (FECHA + DD/MM/AAAA). En **CIUDAD**, municipio DIVIPOLA con **búsqueda al escribir** (`fo51-municipality-combobox.js`); catálogo desde **Ajustes → Territorio**. Encabezado del PDF FO-GJ-51 alineado con cartas oficiales (`official-letter-pdf-shell`). |
| **Evidencias pendientes** (supervisor) | Cola mínima (`PendingEvidenceIndex`) filtrada por `notification_supervisor_user_id`: N° caso, trabajador, estado **Evidencia de citación pendiente**, fecha FO-GJ-03 y dos acciones por fila. **Cargar evidencia PDF** (fase A): selector oculto → modal de **vista previa** → tipo **Citación firmada** o **Rechazo con testigos** → **Confirmar y cargar**. **Notificación** (fase B): modal con hoja **carta** (`8.5in × 11in`, escala responsive) y HTML oficial FO-GJ-03 (misma maquetación de firmas que el PDF: hueco alineado y líneas horizontales a la misma altura); selector **Firmada / Rechazo con testigos**. Firmada: firma del trabajador en «Recibido por» (lienzo táctil con recorte al trazo). Rechazada: **Se niega a firmar** + dos **Testigo** (firma, nombre, cédula); **Cargar firmado** → Browsershot (`CitationNotificationSigningService`). Política `viewFoGj03NotificationForSupervisor`. Tests: `PendingEvidenceUploadTest.php`. |
| **Revisión informes** | Cola `InformeSubmission` en estado pendiente de autorización: **vista previa del PDF** en modal (misma ruta con `?inline=1`), **confirmación de autorización** en modal de la aplicación (no diálogo nativo del navegador), acciones **Rechazar** y **Descargar**. El revisor asignado gestiona con `disciplinary.review-inform`; dirección ve todos con `disciplinary.review-inform-all`. Al autorizar se crea el expediente y el PDF pasa como documento del caso. |
| **Detalle del caso** | **Encabezado en una línea:** número de caso a la izquierda; a la derecha **← Volver al listado**, badge de estado (`size="md"`) y acciones de etapa Informe si aplican (nombre/CC del trabajador solo en pestaña Información). 4 tabs (Información / Línea de tiempo / Documentos / Actuaciones) + modal de transición. Si el expediente está en **bandeja compartida** (`isInInformePool()`), el abogado ve aviso y **Gestionar caso** (mismo flujo de confirmación que en el listado); la **tarjeta verde Etapa A** solo aparece con titular asignado (en pool se prioriza el banner de gestión). **Reasignar / quitar titular:** solo `admin` o permiso `disciplinary.assign` (`DisciplinaryCasePolicy::assign`); el abogado no devuelve casos al pool. **Tarjeta «Etapa A»** (Información, estado **Informe** y titular asignado): fila 1 **Etapa A** + botón **Ver informe (PDF)**; fila 2 trazabilidad del envío a revisión e incorporación del PDF con **fecha/hora Colombia** (`America/Bogota`); fila 3 **Autorización y creación del caso** — cargo y nombre de quien **autoriza** el FO-GJ-51 y genera el expediente (`InformeSubmission` vía `DisciplinaryCase::informeSubmission()`); fila 4 **Revisión y asignación** — cargo y nombre de quien registra la asignación del titular (última actuación **`CASO_ASIGNADO`** o **`CASO_ACEPTADO_ABOGADO`** según origen), abogado asignado y fecha Colombia. Bloque **FO-GJ-51** (azul/teal) cuando el informe no está fusionado en la tarjeta verde. **Tarjeta «Etapa B · Citación»** (estado **citación programada**, activa): stepper 6 pasos (`CitationStageProgress`); **barra de acción** con **Fecha para diligencia** y **Fecha y usuario para notificación**; **Iniciar coordinación**; **chat** con composer (`agenda-chat-composer`); selección de slot + **Confirmar fecha**; FO-GJ-03 y evidencia PDF; **Siguiente etapa → Diligencia**. En estado **diligencia**, la misma tarjeta B queda **Completada · Solo lectura** (`showsCitationStageReadOnly()`): stepper completo, barra con fechas, botones **Consultar FO-GJ-03 (PDF)** y **Evidencia de citación (PDF)** e historial de coordinación (sin composer, sin paneles duplicados de FO-GJ-03/evidencia). **Hora en «Fecha para diligencia»:** `DisciplinaryCase::resolvedDiligenceHearingTimeLabel()` — prioridad `fo_gj_03_payload.hearing_time` (hora editada en el formato) → `citation_confirmed_time` → slot del hilo (`citation_selected_message_id`). **Tarjeta «Etapa C · Diligencia»** (estado **diligencia**, activa): stepper 3 pasos (`DiligenceStageProgress`), fecha/hora programada, acciones **Diligenciar / Editar**, **Vista previa PDF** y **Generar y guardar** (modal: horas, manifestación SI/NO, cuestionario dinámico; fecha/cargos en solo lectura desde FO-GJ-03); la **plantilla en blanco** solo en **Formatos** (no en esta tarjeta); panel inferior del acta **solo** si ya hay documento en expediente (`latestActaDiligenciaDocument()` / `fo_gj_04_generated_at`), **Siguiente etapa → Decisión**. Orden en Información: pila **más reciente primero, Etapa A al final** (`CaseOverviewStageStack`: p. ej. diligencia → C → B → A). Partials: `overview-stage-stack.blade.php`, `stage-a-informe.blade.php`, `stage-b-citation.blade.php`, `stage-b-citation-modals.blade.php`, `stage-c-diligence.blade.php`, `stage-c-diligence-modals.blade.php`. Tests: `CaseDetailStageViewsTest.php`, `FoGj04DraftTest.php`, `DiligenceHearingTimeDisplayTest.php`. Refresco: `wire:poll` + Echo `disciplinary.case.{id}`. |
| **Formatos** | Catálogo FO-GJ por etapa A–F; **Plantilla** abre modal con PDF en blanco (iframe `disciplinary.formats.preview`); **Descarga** fuerza descarga del mismo PDF que la vista previa. Para códigos registrados en `OfficialFormsCatalog::htmlBlankPdfRegistry()` (**FO-GJ-51**, **FO-GJ-03**, **FO-GJ-44**, **FO-GJ-54**, **FO-GJ-04**), el PDF se genera desde HTML con **Chrome headless** (Spatie Browsershot), **tamaño carta (Letter)**; esa fuente tiene **prioridad** sobre un PDF estático en `public/formatos/disciplinarios/`. Las cartas oficiales comparten encabezado grilla (`official-letter-pdf-shell`), estilos `official-letter-pdf-styles` con **escala tipográfica unificada** (`--ogj-font-body` 12px, `--ogj-font-meta` 11px, `--ogj-font-title` 13px, `--ogj-font-micro` 10px) y campos en blanco con guías grises. En el formulario FO-GJ-51, perfiles **supervisor / operador** no ven el enlace *Catálogo de formatos* en la barra de acciones. `GET /disciplinary/forms/informe-fo-gj-51` redirige al listado con modal salvo **`?vista_completa=1`** (pantalla dedicada). El envío del informe es `POST /disciplinary/forms/informe-fo-gj-51` (`disciplinary.forms.informe.process`: generar PDF, enviar a revisión o cargar PDF externo). Rutas de catálogo: `GET …/formats/preview/{code}`, `GET …/formats/descarga-en-blanco/{code}`. |

**Disciplinario — agenda Etapa B:** `DisciplinaryCase::statusesAllowingAgendaCoordination()` limita el chat abogado ↔ planeación a **citación** y **reprogramación**; `DisciplinaryWorkflowService` no exige respuesta de planeación para pasar de **Informe** a **citación**. Políticas y `DisciplinaryAgendaThreadService` usan `allowsAgendaThread()`.

**Disciplinario — bandeja de abogados (etapa INFORME):** `DisciplinaryCase::scopeInInformePool()` / `isInInformePool()` identifican expedientes en estado **informe** sin titular. El alcance de listados para **abogado** (`forDisciplinaryActor`) une casos propios y pool. Política `claim` autoriza tomar gestión; `view` permite consulta del pool; `update` / `transition` exigen titular asignado. Concurrencia: `claimByLawyer()` actualiza solo si `assigned_lawyer_id` sigue nulo; si falla, `CaseAlreadyClaimedException`. Tests: `tests/Feature/Disciplinary/DisciplinaryLawyerPoolClaimTest.php`.

**Disciplinario — Etapa B (citación):** chat libre abogado ↔ planeación (`AgendaMessageKind::GENERAL`); adjuntos en mensajes (imágenes/PDF) con miniaturas en el hilo y lightbox (`agenda-attachment-lightbox.js`). Planeación publica fechas con **`proposed_slots`** (`PLANNING_RESPONSE`) — bloque **Fechas propuestas** en `agenda-message.blade.php`. Orden: (1) coordinación + chat, (2) modal **Proponer fechas de diligencia** en coordinaciones, (3) abogado confirma slot en el hilo, (4) al publicar slots se habilita **Registrar notificación y supervisor** (`canPlanningRegisterNotification`, sin botón del abogado), (5) datos de notificación en la barra del expediente, (6) **diligenciar FO-GJ-03** (`fo_gj_03_payload`, políticas `editFoGj03Draft` / `previewFoGj03` / `generateFoGj03`), (7) vista previa y generación PDF (`FoGj03CitationService`), (8) evidencia. El chat permanece visible durante FO-GJ-03/evidencia (no depende del paso del stepper). Migraciones: `2026_06_03_120000_reclassify_informal_agenda_messages_as_general.php`, `2026_06_04_100000_fo_gj_03_draft_and_user_signature.php`. Tests: `DisciplinaryCitationStageFlowTest.php`, `DisciplinaryCitationNotificationTest.php`, `FoGj03DraftTest.php`, `DisciplinaryCoordinationsIndexTest.php`, `DisciplinaryOperacionesCaseScopeTest.php`.

**Disciplinario — FO-GJ-03 (citación por escrito):** plantilla `fo-gj-03-body.blade.php` + `FoGj03DraftService`. Campos automáticos: fecha del documento (hoy), trabajador (nombre/cédula/cargo), fecha de diligencia confirmada, fecha del informe (`InformeSubmission` / `form_snapshot`). Campos del abogado (modal antes de vista previa): hora (prellenada del slot, editable), modalidad **presencial** (dirección Cali) o **virtual** (URL), fecha del incumplimiento, descripción de hechos, numerales art. 66/68/76. Firma del abogado: imagen en `users.signature_path` vía **Mi perfil** (`GET /profile/signature`). Bloque de firmas: `.ogj-03-signature-block` + `.ogj-03-signature-slot-area` (hueco fijo 44px, líneas horizontales alineadas entre columnas) + línea; columna **Recibido por** con firma del trabajador, o texto **Se niega a firmar** + dos bloques **Testigo** (firma, nombre, cédula) cuando la evidencia es `refused_witnesses`. Lienzo supervisor: `worker-signature-pad.js` recorta el PNG al trazo antes de incrustarlo. PDF firmado en pantalla: `fo-gj-03-signed-notification-download` + `CitationNotificationSigningService` (Browsershot). Expedientes legacy conservan `DISC-…`; los nuevos usan **`GJ-PD:NNNNNN`** (`DisciplinaryCaseService::nextCaseNumber`).

**Disciplinario — FO-GJ-04 (acta de diligencia):** sustituye **FO-GJ-42** en catálogo y código (`OfficialFormsCatalog`, sin alias). Plantilla multipágina `fo-gj-04-body.blade.php` / `fo-gj-04-filled-download` con parciales `fo-gj-04-intro`, `fo-gj-04-question-item`, `fo-gj-04-closing-signatures` y textos legales fijos (citación previa, términos 1–5, manifestación, cierre antes del cuestionario). En el intro, las líneas de partes **EN REPRESENTACIÓN EL EMPLEADOR** y **EL TRABAJADOR** van sangradas con viñeta **•**; tras la del empleador hay salto de línea extra antes del párrafo «De otra parte…». **Fecha del incumplimiento** y **formulación de cargos** se leen del FO-GJ-03 diligenciado (`fo_gj_03_payload.breach_date`, `charges_description`); el modal no duplica esos campos. Diligenciamiento: `FoGj04DraftService` (`fo_gj_04_payload`, `fo_gj_04_draft_completed_at`); cada ítem del cuestionario es `{ question, answer }` con normalización `¿…?` y **respuesta obligatoria**; generación: `FoGj04DiligenceActaService` + `FoGj04PagePlanner` (paginación por unidades de línea, numeración «Página X de Y», cierre + firmas en la última hoja con espacio) → `DocumentType::ACTA_DILIGENCIA` + `fo_gj_04_generated_at`. Modal Etapa C: cabecera/pie fijos, cuerpo con scroll, hora inicio/fin, manifestación **SI QUIERO RESPONDER** / **NO DESEA RESPONDER**, cuestionario dinámico (vacío al abrir). En PDF, la respuesta se imprime en la misma línea que `R:` (`.ogj-04-answer-inline`). Firma del abogado en PDF; hueco del trabajador en blanco (captura en pantalla pendiente). Ruta vista previa: `GET /disciplinary/cases/{case}/fo-gj-04/pdf` (`FoGj04CaseController`). Políticas: `editFoGj04Draft`, `previewFoGj04`, `generateFoGj04`. Migración: `2026_06_10_100000_fo_gj_04_diligence_acta_draft`. Tests: `FoGj04DraftTest.php`, `FoGj04PagePlannerTest.php`.

**Disciplinario — coordinaciones con planeación:** `Coordinations\Index`: mismo **composer de chat** que el abogado; botón **Proponer fechas de diligencia** (modal → `postPlanningMessage` con slots); **Registrar notificación y supervisor** cuando `canPlanningRegisterNotification`. Badge **Fechas pendientes** si `awaitingPlanningDiligenceSlots()`. `data-live-case-id` + `wire:poll`. Cierre del hilo al avanzar a **diligencia**; en expediente el abogado **oculta/muestra** el panel sin cerrar el hilo. Políticas: `postAgendaPlanning`, `postNotificationCoordination`.

**Disciplinario — composer y adjuntos de agenda (front):** `resources/js/disciplinary-agenda-composer.js` (clip, paste, drag-drop, `$uploadMultiple` Livewire); componentes Blade `agenda-chat-composer`, `agenda-attachment-lightbox-modal`; props Livewire `agendaLawyerUploads` / `agendaPlanningUploads`. Previews pendientes y mensajes publicados abren el mismo modal (imagen con zoom; PDF en iframe).

**Disciplinario — evidencia de citación:** `canReceiveCitationEvidence()` exige FO-GJ-03 generado y documento asociado. Carga vía `uploadCitationEvidence` (PDF escaneado) o `uploadSignedNotification` (HTML firmado en pantalla). Matriz en `canUserUploadCitationEvidence()`: titular, `informeSubmission.reviewed_by`, `disciplinary.review-inform-all`, `notification_supervisor_user_id`, dirección jurídica; excluye planeación y supervisores no asignados. Cola `evidences-pending` sin `view`/`viewAny` de expediente; el supervisor ve la notificación FO-GJ-03 solo en modal (`viewFoGj03NotificationForSupervisor`). Tests: `DisciplinaryCitationNotificationTest.php`, `PendingEvidenceUploadTest.php`.

### Módulo Empleados

Ruta: **`GET /employees`** · permisos `employees.view` / `employees.manage`

| Vista / acción | Contenido |
|---|---|
| **Listado** | Encabezado **BD DE EMPLEADOS SJ**; búsqueda y filtro activo/inactivo; tabla con documento, nombre, cargo, ciudad, estado |
| **Crear / Editar** | Modal en 4 bloques: datos personales (**nombre completo**), contacto, laboral (fecha fin de contrato solo si tipo = término fijo), emergencias |
| **Carga masiva** | Excel `.xlsx` fila 1 = encabezados; plantilla en **`GET /employees/plantilla`**. Columnas esperadas: nombre completo, tipo/número documento (solo dígitos), fechas, género, dirección, municipio (código DIVIPOLA o nombre), teléfono, correo, contrato, cargo, área, salario, terminación, contacto emergencia. Import vía `EmployeeBulkImportService` (PhpSpreadsheet). Overlay de carga con mazo + punto girando + **Cargando…** y tiempo (`bulk-import-elapsed-timer.js`) |
| **API búsqueda** | `GET /api/employees/search?q=` — autocompletado FO-GJ-51 y otros consumidores |

Los expedientes disciplinarios referencian **`employee_id`** (antes `personnel_id`). Resolver: `App\Services\Employees\EmployeeResolver`.

> Tras cambios de esquema o permisos, en desarrollo conviene **`php artisan migrate:fresh --seed`** (destruye datos locales). Los permisos base se crean en la migración `create_permission_tables` y los roles en **`RolesAndPermissionsSeeder`**; tras actualizar permisos en producción: **`php artisan permission:cache-reset`**.

### Módulo Usuarios

Sub-nav: **Inicio | Usuarios | Organización**

| Vista | Contenido |
|---|---|
| **Usuarios** (listado) | Búsqueda; filtros por **perfil de permisos (técnico)**, área y estado; tabla con **área** y **cargo** (los admins muestran etiqueta *Admin plataforma*). Acciones: editar, reinicio de contraseña, activar/desactivar, eliminar |
| **Organización** | Catálogo de **áreas** activas y **cargos** por área; cada cargo define el **perfil de permisos (Spatie)** que recibirán los usuarios asignados a ese cargo (`permission_role_name`) |
| **Detalle** | Datos del usuario, casos disciplinarios asignados, mismas acciones administrativas permitidas por política |
| **Mi perfil** (`GET /profile`) | Datos de cuenta, contraseña y **firma digital** (imagen PNG/JPG/WebP; solo el usuario dueño; usada en FO-GJ-03 y documentos que requieran firma del titular) |

En **crear/editar usuario**: **Área** + **Cargo** (obligatorios salvo «Administrador de la plataforma»); checkbox para **`admin`** desactiva área/cargo en pantalla. Los permisos directos extra para **Operaciones** (FO-GJ-51, notificaciones, PDF) siguen como toggles cuando el ámbito es Operaciones.

## 🏛️ Workflow del proceso disciplinario

Etapas normativas SJ (referencia):

| Etapa | Contenido |
| --- | --- |
| **A** | Falta e informe disciplinario — **FO-GJ-51**. La coordinación de fechas con planeación (**FO-GJ-03**, chat e imágenes) corresponde a la **Etapa B** (citación / reprogramación), no al estado Informe. |
| **B** | Citación a diligencia disciplinaria por escrito — **FO-GJ-03**. Si no asiste: **FO-GJ-44** (constancia de inasistencia) y **2 días calendario** para justificar; si justifica → reprogramación (**FO-GJ-54**); si no → comité disciplinario para decisión |
| **C** | Diligencia disciplinaria y acta — **FO-GJ-04** |
| **D** | Comunicado de la decisión de sanción o cierre del proceso |
| **E** | Recurso de apelación contra la decisión disciplinaria |
| **F** | Decisión de segunda instancia |

```
BORRADOR
   ↓
INFORME (FO-GJ-51) ────────────────► ARCHIVADO
   ↓
CITACION_PROGRAMADA (FO-GJ-03) ─┐
   │   │   │                    │
   │   │   └─► CITACION_NO_ASISTIO (FO-GJ-44)
   │   │             ↓
   │   │      JUSTIFICACION_PENDIENTE (deadline 2 días calendario)
   │   │           │            │
   │   │           ↓            ↓
   │   └──► REPROGRAMADO (FO-GJ-54)   COMITE_DISCIPLINARIO
   │           │                      │
   ↓           ↓                      ↓
DILIGENCIA (FO-GJ-04) ◄──────────────┘
   ↓
DECISION (comunicado sanción / cierre)
         │  │
         │  └──► APELACION ──► SEGUNDA_INSTANCIA
         ↓                            │
      FINALIZADO ──────────► ARCHIVADO
```

Toda transición pasa por `DisciplinaryWorkflowService::transition()` que garantiza atómicamente:

1. La transición está permitida (`TransitionMap`).
2. Se crea automáticamente la etapa correspondiente.
3. Se registra la actuación en el audit log.
4. Se actualiza el estado denormalizado en la tabla `disciplinary_cases`.

## 🛠️ Stack técnico

- **Backend**: Laravel 12, PHP 8.2+, MySQL 8
- **Autorización**: Spatie Laravel Permission v6
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS, ApexCharts (global vía Vite), Leaflet (mapa Colombia en dashboard disciplinario)
- **Broadcasting**: [Pusher Channels](https://pusher.com/channels) + Laravel Echo (`resources/js/echo-notification-bell.js`); `BROADCAST_CONNECTION=pusher` y credenciales `PUSHER_*` en `.env` para campanita y canales privados de agenda (sin servidor WebSocket propio en Laragon).
- **PDF desde HTML**: Spatie Browsershot + Puppeteer (salida **Letter**); el paquete `barryvdh/laravel-dompdf` permanece disponible para otros usos si se requiere.
- **Auth**: Laravel Breeze (stack Livewire)
- **Servidor**: Apache (Laragon en desarrollo)

## 📁 Estructura del proyecto

```
app/
  Enums/
    UserArea.php
    Disciplinary/              Enums del dominio disciplinario
  Exceptions/Disciplinary/     InvalidStateTransitionException
  Workflow/Disciplinary/       TransitionMap (única fuente de verdad)
  Support/
    Disciplinary/              OfficialFormsCatalog, DisciplinaryAssets, FoGj51Catalog, FoGj04PagePlanner, …
    Broadcasting/              PusherBroadcasting (Echo solo si `BROADCAST_CONNECTION=pusher` y credenciales completas)
    Notifications/             Trait BroadcastsInAppDatabaseNotification (canal `broadcast` además de `database` cuando Pusher está activo)
    Pdf/
      HtmlLetterPdfGenerator.php HTML → PDF tamaño Letter (Browsershot)
      BrowsershotBinaryResolver.php Detección Node/npm/Chrome (p. ej. Laragon)
      EmbeddedPublicAsset.php    Data URI para assets en PDF (logo embebido)
  Models/
    User.php / Employee.php / OrganizationalArea.php / JobPosition.php / Role.php (Spatie)
    ColombianMunicipality.php   Catálogo DIVIPOLA (código, nombre, lat/lon) para mapa y expedientes
    Disciplinary/              Models del agregado disciplinario + InformeSubmission (cola pre-expediente FO-GJ-51); `DisciplinaryCase::informeSubmission()` enlaza el envío autorizado al expediente
  Services/
    AlertsService.php          Agregador global de alertas para Inicio
    UserService.php            Alta/edición usuarios, reinicio provisional de contraseña
    Disciplinary/              CaseService, WorkflowService, DashboardService, DocumentService, InformeSubmissionService, AgendaThreadService, CitationWorkflowService, FoGj03CitationService, DisciplinaryAuditService
    Settings/                  ColombianMunicipalityImportService (Excel/CSV DIVIPOLA)
    Employees/                 EmployeeBulkImportService, EmployeeResolver
  Policies/                    DisciplinaryCasePolicy, UserPolicy, InformeSubmissionPolicy, EmployeePolicy
  Livewire/
    Employees/                 EmployeesIndex (CRUD + carga masiva)
    Home.php                   Componente del dashboard global
    Auth/                      ForcePasswordChange, LogoutButton
    Users/                     UsersIndex, UserDetail, OrganizationCatalog
    Employees/                 EmployeesIndex
    Disciplinary/              Dashboard, CasesIndex, CaseDetail, FormatsCatalog, InformesPendientes; FO-GJ-51 parcial/modal
    Settings/                  TerritoryImport (importación DIVIPOLA / municipios)
    Ui/                        ThemeToggle (preferencia tema usuario)
  Http/
    Middleware/                must-change-password, ShareUiTheme, ForceRequestRootUrl (URLs con host/puerto de la petición)
    Controllers/Disciplinary/     Casos (web + API), formatos (preview/descarga), FO-GJ-51 (show/process, PDF pendiente), FO-GJ-03/FO-GJ-04 por caso (`FoGj03CaseController`, `FoGj04CaseController`), GeoJSON mapa (`DisciplinaryGeoJsonController`)
    Requests/Disciplinary/     FormRequests (casos + FO-GJ-51: FoGj51ProcessRequest, StoreFoGj51InformePdfRequest)
    Requests/Users/            FormRequests del módulo usuarios

routes/
  channels.php               Canales privados broadcasting (`App.Models.User.*`, `disciplinary.case.*`)

database/
  migrations/                  Disciplinario + Spatie + extensión `users` (contacto, `read_only`, `must_change_password`, `theme`, soft deletes, FK a áreas/cargos), tablas **`organizational_areas`** y **`job_positions`** (columna **`permission_role_name`**), notificaciones, etc. En **`disciplinary_cases`**, el código DIVIPOLA del municipio está en **`municipality_code`** (misma migración de creación de la tabla en el repo).
  seeders/                     RolesAndPermissions, FaultsCatalog, DemoUsers, WorkflowSmokeTest

resources/views/
  layouts/app.blade.php        Layout principal con sidebar + topbar + sub-nav
  livewire/
    home.blade.php             Vista del dashboard global
    settings/                  Ajustes (importación territorio DIVIPOLA)
    disciplinary/              Vistas del módulo + catálogo de formatos (`formats-catalog`)
    users/                     Listado, detalle y catálogo de organización (áreas/cargos)
    auth/                      force-password-change (primer login)
    ui/                        Controles UI compartidos (`btn` con variantes `sj-btn`, selector de tema)
  disciplinary/forms/        FO-GJ-51 (informe); FO-GJ-03/44/54/04: plantillas carta Letter en blanco
                               (`fo-gj-*-blank-download.blade.php` + parciales `fo-gj-*-body.blade.php`);
                               shell compartido `official-letter-pdf-shell` y estilos `official-letter-pdf-styles`
  components/
    app-sidebar.blade.php      Sidebar de módulos (con catálogo de los 12)
    app-sidebar-icon.blade.php Heroicons inlineados (sin dependencia externa)
    disciplinary/              kpi-card, status-badge, nav (sub-nav); `forms/` (vista previa FO-GJ-51)
docs/
  ARCHITECTURE.md              Documentación detallada de arquitectura
```

## 🚀 Instalación

### Requisitos

- PHP 8.2+
- Composer 2
- MySQL 8 (o MariaDB compatible)
- Node.js 18+ (recomendado 20 LTS). **Laragon** suele incluir Node en  
  `C:\laragon\bin\nodejs\node-v18\` — si `npm` no está en el PATH del IDE, use esa ruta o añádala al PATH del usuario.
- Apache o Nginx

### Pasos

```bash
git clone https://github.com/wilder1994/SJ_LegalSuite.git
cd SJ_LegalSuite

# 1. Dependencias PHP
composer install

# 2. Variables de entorno
cp .env.example .env
php artisan key:generate

# 3. Configurar BD en .env (DB_DATABASE=sj_legalsuite, etc.)
#    Crear la base de datos vacía en MySQL antes de migrar

# 4. Migrar y sembrar datos
php artisan migrate --seed
#    Entorno local desde cero (borra todas las tablas):
# php artisan migrate:fresh --seed

# 5. Frontend (Vite: un solo entry `resources/js/app.js`, Tailwind vía `import '../css/app.css'`). Si npm no resuelve en la terminal:
#    & "C:\laragon\bin\nodejs\node-v18\npm.cmd" install
#    & "C:\laragon\bin\nodejs\node-v18\npm.cmd" run build
npm install
npm run build
```

### Broadcasting (Pusher Channels, opcional)

Para **notificaciones en la campanita** y eventos en canales privados (`App.Models.User.{id}`, `disciplinary.case.{id}`) sin Reverb ni puertos WebSocket locales:

1. Cree una app en el [panel de Pusher](https://dashboard.pusher.com/) y copie **App ID**, **Key**, **Secret** y **Cluster** (en `.env`, `PUSHER_APP_CLUSTER` debe coincidir con el del panel, p. ej. `us2`).
2. En `.env`: `BROADCAST_CONNECTION=pusher` y `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`.
3. En la app de Pusher, autorice el **origen** desde el que se usa la suite (p. ej. `http://172.16.16.90:8082`) para que `POST /broadcasting/auth` no falle por CORS.
4. Sin credenciales puede usar `BROADCAST_CONNECTION=log` o `null`: la UI sigue con **polling** de respaldo en la campanita.

Canales privados: `routes/channels.php` (registro en `bootstrap/app.php`).

### PDF disciplinarios (HTML → tamaño carta / Letter)

Las plantillas registradas en **`OfficialFormsCatalog::htmlBlankPdfRegistry()`** se convierten de HTML a PDF con **Spatie Browsershot** y **Puppeteer** (Chromium). La salida es siempre **Letter** (`HtmlLetterPdfGenerator` + `@page { size: Letter }` en las vistas).

| Código | Documento | Vista en blanco |
|--------|-----------|-----------------|
| **FO-GJ-51** | Informe disciplinario | `fo-gj-51-blank-download` (+ flujo de diligenciamiento) |
| **FO-GJ-03** | Citación a diligencia disciplinaria | `fo-gj-03-blank-download` |
| **FO-GJ-44** | Constancia de inasistencia a diligencia | `fo-gj-44-blank-download` |
| **FO-GJ-54** | Reprogramación a diligencia disciplinaria | `fo-gj-54-blank-download` |
| **FO-GJ-04** | Acta de diligencia disciplinaria (2 páginas) | `fo-gj-04-blank-download` + `fo-gj-04-filled-download` (Etapa C: modal, cargos desde FO-GJ-03) |

Para esos códigos, la **plantilla HTML tiene prioridad** sobre un PDF estático homónimo en `public/formatos/disciplinarios/`. El iframe de vista previa usa query `rev=` (mtime de la vista) para invalidar caché al editar plantillas. La tipografía de los formatos carta comparte variables CSS en `official-letter-pdf-styles.blade.php` (cuerpo 12px, meta 11px, título 13px, micro 10px); el planificador FO-GJ-04 (`FoGj04PagePlanner`) está calibrado para esa escala.

1. Después de `composer install`, ejecute **`npm install`** en la raíz del proyecto (trae la dependencia **puppeteer**).
2. Verifique el entorno con **`php artisan disciplinary:pdf-check`** (Node/npm/Chrome y logo legible en disco).
3. Opcional en `.env`: `NODE_BINARY`, `NPM_BINARY`, `PDF_CHROME_PATH`, `PDF_BROWSER_TIMEOUT` (detalle en `.env.example`). En Windows suele bastar la detección automática (Laragon en `C:\laragon\bin\nodejs\…`, Chrome en Program Files).
4. Tras cambiar vistas Blade o CSS de formatos, ejecute **`npm run build`** y, si la vista previa no refleja cambios, **`php artisan view:clear`**.

El logo para interfaz y para incrustar en el PDF debe estar en **`public/images/logo solo.png`** (referencia única: `App\Support\Disciplinary\DisciplinaryAssets::LOGO_RELATIVE_PATH`).

### Mapa Colombia (dashboard disciplinario)

1. Descargue los GeoJSON GADM al árbol público del proyecto:

   ```bash
   php artisan geo:download-colombia-gadm
   ```

   Dejarán de existir (o actualizarse) **`public/geo/gadm41_COL_1.json`** (departamentos) y **`public/geo/gadm41_COL_2.json`** (municipios).

2. El navegador **no** lee esos archivos solo por ruta estática en todos los despliegues: la aplicación los expone autenticada en **`GET /disciplinary/map-geo/{file}`** (`disciplinary.map-geo`), con lista blanca de los dos nombres anteriores y la misma autorización que ver el dashboard o el listado de casos.

3. El bundle Vite incluye **`resources/js/disciplinary-colombia-map.js`** (Leaflet). El montaje evita inicializar el mapa dos veces en paralelo (p. ej. al refrescar la página). Tras tocar JS o estilos del mapa, ejecute **`npm run build`**.

4. Para **pins** en el mapa hace falta que los expedientes tengan código de municipio acorde al catálogo y que existan coordenadas en **`colombian_municipalities`**. Cargue el archivo oficial DIVIPOLA en **Ajustes → Territorio** (Excel/CSV); hasta entonces el select **CIUDAD** del FO-GJ-51 quedará sin opciones.

### Probar el workflow end-to-end

```bash
php artisan db:seed --class="Database\Seeders\WorkflowSmokeTest"
```

Esto crea un caso ficticio y lo recorre por las 8 transiciones del workflow, validando que todo funciona.

## 👥 Usuarios demo (entorno local)

| Email | Rol | Capacidades |
|---|---|---|
| `admin@sjlegalsuite.local` | admin | Control total del sistema |
| `admin.consulta@sjlegalsuite.local` | admin | Misma visión que admin pero **solo lectura** (consulta sin cambios) |
| `abogado@sjlegalsuite.local` | abogado | Casos asignados + bandeja **INFORME** sin titular (botón **Gestionar** con confirmación) |
| `planeacion@sjlegalsuite.local` | planeacion | **Coordinaciones** abiertas (B.1 fechas + B.2 notificación con asignación de supervisor); sin listado/detalle de expedientes; **assign-date**; sin dashboard, formatos ni carga de evidencia |
| `administrativa@sjlegalsuite.local` | administrativa | Crear informes y cargar evidencias |
| `auditor@sjlegalsuite.local` | auditor | Consulta + exportación disciplinaria |
| `operaciones@sjlegalsuite.local` | operaciones | Crear casos, revisar FO-GJ-51, **reasignar supervisor de notificación** en expedientes que aprobó |
| `supervisor@sjlegalsuite.local` | supervisor | FO-GJ-51 + cola **Evidencias pendientes** (PDF escaneado o notificación firmada/rechazo con testigos; solo casos donde `notification_supervisor_user_id` coincide); sin listado ni detalle de expedientes |
| `operador@sjlegalsuite.local` | operador | Casos operativos en campo según políticas del módulo |
| `programador@sjlegalsuite.local` | programador | Programación de fechas (planeación) |

En **Usuarios → crear/editar**, el interruptor **«Puede realizar cambios»** define si el usuario queda en modo solo lectura (`read_only`): no podrá mutar disciplinarios ni gestionar otros usuarios (los admin en solo lectura solo consultan). Los usuarios demo con rol **`admin`** se crean **sin** `organizational_area_id`; el resto lleva **área + `job_position_id`** acorde al catálogo sembrado en la migración de legalsuite.

Si actualizas código y una BD ya tenía migraciones viejas aplicadas, ejecuta **`php artisan migrate`** (p. ej. `2026_06_03_100000_add_citation_notification_fields` para Etapa B.2; `2026_06_04_100000_fo_gj_03_draft_and_user_signature` para borrador FO-GJ-03 y firma en `users`; `2026_06_10_100000_fo_gj_04_diligence_acta_draft` para borrador/generación FO-GJ-04). En desarrollo suele bastar **`migrate:fresh --seed`**; en datos reales, no editar migraciones ya ejecutadas sin plan de alter explícito.

> Contraseña por defecto: **`SJseguridad2026`**. Cambiarla antes de cualquier deploy productivo.

## 🌐 Acceso en red local (Laragon) — mismo criterio que **SJ_Armory**

En el **mismo PC Laragon**, **SJ_Armory** atiende el **puerto 80** y **SJ_LegalSuite** el **8082**, para que convivan sin mezclar `DocumentRoot`.

| App | Puerto | URL típica en LAN (misma IP del servidor) |
|-----|--------|------------------------------------------|
| **SJ_Armory** | 80 | `http://172.16.16.90` |
| **SJ_LegalSuite** | 8082 | `http://172.16.16.90:8082` |

En el mismo Laragon suelen existir otros proyectos en **8080** y **8081**; el tiempo casi real con **Pusher** no requiere abrir un puerto WebSocket adicional en el PC (el navegador se conecta a la nube de Pusher).

La IP (`172.16.16.90` en el ejemplo) es la del **equipo donde corre Laragon**; si DHCP asigna otra, use esa IP con **`:8082`**. El `.env` de LegalSuite usa **`APP_URL=http://172.16.16.90:8082`**, paralelo a Armory (`APP_URL=http://172.16.16.90`). Con **`APP_USE_REQUEST_URL=true`**, si entran con otro host/IP válido, Laravel genera enlaces con esa misma base. Zona horaria recomendada en Colombia: **`APP_TIMEZONE=America/Bogota`** (usada en trazabilidad de fechas del módulo disciplinario).

**Importante:** incluya **`http://`** y **`:8082`** para LegalSuite. En **Android**, el nombre `SJPCANAOPE1` puede **no resolverse**; use la **IP** como cuando abren Armory.

Apache (misma forma que `00-aaa-sj_armory.conf`, otro puerto): `C:\laragon\etc\apache2\sites-enabled\00-aac-sj_legalsuite.conf` (HTTP de esta app en **8082**). **`SESSION_DOMAIN`** vacío, como en Armory.

### Si no carga desde otro equipo o el móvil

1. **`ERR_CONNECTION_TIMED_OUT` en `http://IP:8082`** — casi siempre el **Firewall de Windows** bloquea el **8082**. El **80** de Armory suele estar permitido; LegalSuite necesita regla aparte. En el **PC servidor**, PowerShell **como administrador**:

   ```powershell
   New-NetFirewallRule -DisplayName "Laragon HTTP — SJ LegalSuite 8082" -Direction Inbound -Action Allow -Protocol TCP -LocalPort 8082 -Profile Private, Domain
   ```

   O ejecute el script del repo (también como admin):  
   `scripts/windows/open-firewall-port-8082.ps1`

   Compruebe que la Wi‑Fi/Ethernet del servidor esté como red **Privada**, no **Pública** (Configuración → Red).

2. En el servidor, pruebe **`http://127.0.0.1:8082`** en el navegador; si ahí funciona pero desde el móvil no, confirma firewall/red.

3. **`ERR_NAME_NOT_RESOLVED`** — use **`http://IP-del-servidor:8082`** con **`http://`**, no solo el nombre del PC en Android.

4. **Misma red / VLAN** que para Armory.

> En producción con dominio HTTPS fijo: **`APP_USE_REQUEST_URL=false`** y **`APP_URL`** definitivo.

## 🔒 Modelo de autorización

Permisos disponibles:

```
disciplinary.view                  disciplinary.transition
disciplinary.view-dashboard       disciplinary.assign
disciplinary.create               disciplinary.assign-date
disciplinary.update               disciplinary.upload-document
disciplinary.delete               disciplinary.export
disciplinary.review-inform          disciplinary.review-inform-all
disciplinary.generate-inform        disciplinary.assign-planner
disciplinary.upload-notification    disciplinary.download-pdf
settings.manage-territory
employees.view / .manage          users.view / .manage
```

La autorización se evalúa en 3 capas:

1. **Policies** (`DisciplinaryCasePolicy`, `UserPolicy`, `InformeSubmissionPolicy`, `EmployeePolicy`) — rol, permisos Spatie y flag **`read_only`** del usuario.
2. **FormRequests** — `authorize()` delega al Policy.
3. **Vistas** — `@can()` controla qué se renderiza (incluyendo enlaces del sidebar y del sub-nav disciplinario).

**Planeación (`planeacion`):** no tiene `view` / `viewAny` sobre expedientes. Opera en **`GET /disciplinary/coordinations`**: chat con adjuntos, propone fechas de diligencia y completa la **notificación física** (`postNotificationCoordination` / `canPlanningRegisterNotification` tras slots en el hilo). No puede `uploadCitationEvidence` ni `reassignNotificationSupervisor`. El abogado **oculta o muestra** el chat en expediente; el hilo se **cierra al avanzar a diligencia** (`closeCoordination` automático en `confirmAdvanceFromCitacion`).

**Supervisor (`supervisor`):** sin `view` / `viewAny` ni dashboard disciplinario. Carga evidencia FO-GJ-03 solo si figura como **`notification_supervisor_user_id`** del caso, desde **`GET /disciplinary/evidences-pending`** (no por `reporter_id` ni autor del informe). Política `viewFoGj03NotificationForSupervisor` habilita el modal de notificación (FO-GJ-03 en HTML carta, sin abrir el expediente). Front: `worker-signature-pad.js` (lienzo táctil); previsualización PDF temporal vía Livewire (`config/livewire.php` → `preview_mimes` incluye `pdf`).

**Abogado (`abogado`):** `disciplinaryPortalUrl()` → dashboard; `disciplinaryCasesNavUrl()` → listado. Listado y detalle incluyen expedientes **asignados** y **bandeja INFORME** sin titular. Tomar un caso del pool: política `claim` + `claimByLawyer()` (no usa `disciplinary.assign`). Reasignación manual del titular: `assign` (admin o `disciplinary.assign`).

**Auditor (`auditor`):** ve todos los expedientes, incluida la bandeja INFORME; no puede `claim` ni mutar.

## 📊 Endpoints

### Páginas Livewire (UI)

| Ruta | Descripción |
|---|---|
| `GET /dashboard` | **Inicio** (dashboard global con alertas) |
| `GET /disciplinary` | Redirige al portal disciplinario según rol (`disciplinaryPortalUrl`: abogado → dashboard, planeación → coordinaciones, etc.) |
| `GET /disciplinary/dashboard` | Dashboard del módulo disciplinario; Gate `viewDashboard` sobre `DisciplinaryCase` (roles **`planeacion`** y **`supervisor`** sin acceso). |
| `GET /disciplinary/map-geo/{file}` | Sirve GeoJSON GADM (`gadm41_COL_1.json` \| `gadm41_COL_2.json`); sesión iniciada y (`viewDashboard` **o** `viewAny` sobre casos disciplinarios). |
| `GET /settings/territorio` | **Ajustes · Territorio**: importación listado DIVIPOLA; permiso `settings.manage-territory` |
| `GET /disciplinary/cases` | Listado de casos con filtros (roles `planeacion` y `supervisor` → 403) |
| `GET /disciplinary/evidences-pending` | Cola supervisor (`PendingEvidenceIndex`): **Cargar evidencia PDF** (escaneado con vista previa) o **Notificación** (HTML carta + firma/rechazo con testigos → PDF Browsershot). Sin acceso al expediente. |
| `GET /disciplinary/coordinations` | Bandeja planeación: chat con adjuntos (composer), modal fechas de diligencia (`proposed_slots`) y modal notificación/supervisor |
| `GET /disciplinary/coordinations/{thread}/attachments/{attachment}` | Descarga de adjunto del hilo de coordinación |
| `GET /disciplinary/coordinations/{thread}/attachments/{attachment}/inline` | Vista inline del adjunto |
| `GET /disciplinary/formats` | Catálogo de formatos oficiales (FO-GJ / etapas A–F) |
| `GET /disciplinary/formats/preview/{code}` | Vista previa inline del PDF en blanco (misma fuente que la descarga): **HTML→PDF Letter** (Browsershot) si el código está en el registro HTML; si no, PDF estático en `public/formatos/disciplinarios/`; Gate `viewOfficialForms`. |
| `GET /disciplinary/formats/descarga-en-blanco/{code}` | Descarga plantilla en blanco en PDF Letter; misma prioridad HTML que la vista previa; Gate `viewOfficialForms`. |
| `GET /disciplinary/forms/informe-fo-gj-51` | Para perfiles con acceso a casos: redirige al listado con query (`informe_modal`, opc. `cargar_pdf`, `nombre`, `cedula`). Para `supervisor`: abre pantalla completa FO-GJ-51 automáticamente (sin pasar por listado de casos). |
| `POST /disciplinary/forms/informe-fo-gj-51` | Procesa el informe (`FoGj51ProcessRequest`): acción `pdf` (descarga Letter con **CARGO** desde BD de empleados), `enviar` (genera el mismo PDF y lo envía a cola de revisión) o `cargar` (PDF externo sin regenerar). Requiere **`fo51_assigned_reviewer_id`** en `enviar` y `cargar`. |
| `GET /disciplinary/cases/{case}/fo-gj-03/pdf` | Vista previa / descarga FO-GJ-03 diligenciado (`previewFoGj03`: notificación B.2 completa, borrador guardado y firma en perfil). |
| `POST /disciplinary/cases/{case}/fo-gj-03/generate` | Genera y almacena FO-GJ-03 (`generateFoGj03`: mismos requisitos que la vista previa + checklist `DisciplinaryCitationNotificationService`). Notifica al supervisor, operaciones aprobador y dirección de operaciones. |
| `GET /disciplinary/informes-pendientes` | **Revisión informes** — listado Livewire de `InformeSubmission` pendientes; `disciplinary.review-inform` (revisor asignado) o `disciplinary.review-inform-all` (dirección). |
| `GET /disciplinary/informes-pendientes/{submission}/pdf` | Descarga el PDF almacenado o, con **`?inline=1`**, lo sirve **inline** para iframe (vista previa en modal). |
| `GET /employees` | **BD de empleados** (Livewire); permiso `employees.view` |
| `GET /employees/plantilla` | Descarga plantilla Excel carga masiva; `employees.manage` |
| `GET /api/employees/search` | Autocompletado por documento/nombre (JSON) |
| `GET /users` | Listado de usuarios (Livewire) |
| `GET /users/organizacion` | Catálogo **Organización**: áreas y cargos (`permission_role_name`) |
| `GET /users/{user}` | Detalle de usuario |
| `GET /password/first-login` | Cambio obligatorio de contraseña (primer ingreso o tras reinicio admin) |
| `GET /profile` | Configuración de cuenta y **firma digital** |
| `GET /profile/signature` | Imagen de firma del usuario autenticado (solo si tiene `signature_path`) |

### API JSON (programática)

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/disciplinary/dashboard` | `kpis`, `workflow_donuts` (total + etapas A–F), `by_fault`, `by_city`, `lawyer_workload` en JSON |
| `GET` | `/api/disciplinary/cases` | Listado con filtros |
| `POST` | `/api/disciplinary/cases` | Crear caso |
| `GET` | `/api/disciplinary/cases/{case}` | Detalle |
| `GET` | `/api/disciplinary/cases/{case}/transitions` | Transiciones permitidas |
| `POST` | `/api/disciplinary/cases/{case}/transition` | Aplicar transición |

## 📐 Diseño responsive

| Breakpoint Tailwind | Ancho | Comportamiento |
|---|---|---|
| Móvil | < 1024px | Sidebar oculto (botón hamburguesa) — todo en 1 columna |
| `lg` | ≥ 1024px | Sidebar fijo + contenido en grid 2-3 columnas |
| `xl` | ≥ 1280px | Filtros en una sola fila (8 col), detalle en 3 col |
| `2xl` | ≥ 1536px | Aprovecha hasta `max-w-[1600px]` con margen estético |

## 📝 Convenciones del repositorio

- **Migraciones**: nombre con timestamp `YYYY_MM_DD_HHMMSS`, comentarios en español, índices explícitos.
- **Servicios**: una sola responsabilidad por servicio; las transacciones son responsabilidad del servicio.
- **Audit log**: nunca editar `DisciplinaryAction`; ante un error, registrar otra actuación correctiva.
- **Estados**: nunca asignar `current_status` directamente; usar siempre `WorkflowService::transition()`.
- **Comentarios**: se permiten para explicar *por qué*, no *qué* hace el código.
- **Assets frontend**: entrada única de Vite `resources/js/app.js` (el CSS global se importa ahí con `import '../css/app.css'`). Tras cambios en vistas Blade (clases Tailwind), `resources/css` o JS del bundle, ejecutar **`npm run build`** (o `vite build`) para actualizar `public/build/`.
- **Botones de acción**: preferir `<x-ui.btn variant="…">` (clases `.sj-btn` en `app.css`) para altura y padding uniformes; el badge disciplinario usa `size="md"` cuando comparte fila con botones.

## 📚 Documentación adicional

- [`docs/GAP_DISCIPLINARIO_ETAPAS_A_B.md`](docs/GAP_DISCIPLINARIO_ETAPAS_A_B.md) — matriz de requisitos Etapas A y B, permisos y archivos tocados.

Ver [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) para:

- Diagrama completo del modelo de datos
- Decisiones de diseño (denormalización del estado, soft deletes, índices compuestos)
- Cómo agregar una nueva etapa al workflow
- Estrategia de auditoría legal
- Próximos pasos sugeridos

## 🧭 Roadmap

### Módulo Disciplinario — siguientes fases

- [ ] Wizard de creación de caso (autocompletado desde BD de empleados)
- [ ] Subida de documentos desde la UI (`DocumentService` ya listo en backend)
- [ ] Notificaciones por email cuando un plazo está próximo a vencer
- [x] FO-GJ-03 diligenciado desde expediente (modal + PDF con firma; FO-GJ-44 y FO-GJ-54 siguen solo plantilla en blanco)
- [x] Etapa C en detalle del caso (diligencia): FO-GJ-04 (reemplaza FO-GJ-42), plantilla oficial multipágina con paginación híbrida, escala tipográfica unificada en PDF, cargos desde FO-GJ-03, modal con cuestionario pregunta+respuesta y manifestación SI/NO, pila C→B→A, Etapa B solo lectura compacta
- [ ] Captura de firma del trabajador en acta FO-GJ-04 (hueco en blanco hoy)
- [ ] Exportación PDF de actuaciones con plantillas FO-GJ restantes desde el caso
- [ ] Vista Kanban "Mi pipeline" por abogado
- [ ] Tests Pest ampliados (parcial: `DisciplinaryCitationNotificationTest`, `FoGj03DraftTest`, `FoGj04DraftTest`, `DisciplinaryLawyerPoolClaimTest`)

### Otros módulos del sistema

- [ ] Licitaciones, Acciones de tutela, Demandas
- [ ] Negociación colectiva, Investigaciones
- [ ] Cartera, Requisitos legales
- [ ] Contratos, Pólizas, Auditoría
- [ ] Integración con SJ_Armory vía `employees.external_id`

## 📄 Licencia

Software interno de **SJ Seguridad**. Uso restringido.
