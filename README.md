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
- **Etapas A y B (informe + citación):** revisor de operaciones obligatorio al enviar FO-GJ-51; coordinación explícita con planeación en citación; selección visual de fecha definitiva; avance a diligencia desde la UI con checklist de requisitos (`docs/GAP_DISCIPLINARIO_ETAPAS_A_B.md`). **Evidencia de citación (PDF):** habilitada solo tras generar FO-GJ-03 en el expediente; pueden cargarla el abogado titular, el supervisor reportante del informe, el usuario de operaciones que autorizó el FO-GJ-51 (`reviewed_by`) y dirección jurídica (`admin` / `disciplinary.assign`). El supervisor la carga desde una cola separada **Evidencias pendientes** sin acceso al expediente (`view`/`viewAny` denegados).

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

Sub-nav superior (según permisos y rol): por defecto **Inicio | Dashboard | Disciplinarios | (Revisión informes) | Formatos | Historial**. El enlace *Revisión informes* aparece quienes tienen **`disciplinary.review-inform`** (`InformeSubmissionPolicy::viewAny`). **Rol `planeacion`:** en el sub-nav disciplinario solo **Coordinaciones** (`GET /disciplinary/coordinations`). **Rol `supervisor`:** en el sub-nav disciplinario entra a **Evidencias pendientes** (`GET /disciplinary/evidences-pending`). Ambos perfiles quedan sin acceso al listado/detalle general de expedientes.

| Vista | Contenido |
|---|---|
| **Dashboard** | Encabezado reducido: solo la rúbrica **«Disciplinarios · Dashboard»** y el botón al listado de casos (sin título largo ni descripción). **Casos por etapa**: 7 donas (ApexCharts) — total + **A–F** según `current_stage_type` (centro con % y cantidad; **B** y **C** con agrupaciones acordadas); contenedor y rejilla compactos (`items-start`, sin padding inferior en la caja, altura de canvas ajustada) para limitar el aire bajo las donas; etiqueta corta por columna. ApexCharts se expone desde **Vite** (`resources/js/app.js` → `window.ApexCharts`) para compatibilidad con **`wire:navigate`**; el montaje en Blade **espera ancho de contenedor** antes de `render()` para evitar errores SVG (`NaN`). Entre páginas, **`resources/js/apex-charts-lifecycle.js`** destruye/recicla las instancias al navegar para no duplicar morfos en el DOM. Debajo: barras por **tipo de falta**, **mapa por ciudad** (Leaflet + GeoJSON GADM, tiles Carto; datos vía `disciplinary.map-geo`) y tabla **carga por abogado**. |
| **Disciplinarios** (listado) | 3 tarjetas de vistas rápidas + 7 filtros combinables + tabla paginada. **Rol `planeacion` y `supervisor`:** 403 en `CasesIndex` (no usan este listado). **Coordinaciones** (`planeacion`): bandeja de hilos **abiertos** con el abogado titular; adjuntos inline/descarga; al **cerrar coordinación** el hilo sale de la bandeja de planeación y queda en solo lectura en el expediente. **Bandeja compartida (etapa INFORME):** al autorizar un informe se crea el expediente con `assigned_lawyer_id = null`; todos los **abogados** y el **auditor** lo ven en el listado (columna **Bandeja compartida**). El abogado usa **Gestionar** → modal de confirmación → `DisciplinaryCaseService::claimByLawyer()` (asignación atómica + actuación **`CASO_ACEPTADO_ABOGADO`**); luego deja de estar en el pool para el resto de abogados. El auditor solo **Ver** (sin `claim`). **Etapa A (Informe):** el titular **no** chatea con planeación; tras revisar el caso pasa a citación o archiva. Botones **Nuevo informe (FO-GJ-51)** y **Cargar informe en PDF** abren un **modal** a pantalla completa con el formulario (no navegan a otra página). Enlaces desde catálogo o detalle de caso usan query `?informe_modal=1` (y `cedula` opcional). **FO-GJ-51:** campo obligatorio **Revisor de operaciones** (`fo51_assigned_reviewer_id`) en formulario y en modal de carga PDF; validación en `FoGj51ProcessRequest` y `DisciplinaryInformeSubmissionService`. Búsqueda de trabajador por **cédula** (solo dígitos) contra la BD de empleados (`resources/js/fo51-employee-combobox.js`); al elegir, se autocompletan **nombre** y **cargo** en pantalla. En el PDF generado (`enviar` / vista previa `pdf`), el campo **CARGO** del trabajador sale de **`employees.job_title`** vía `FoGj51InformeController::resolveWorkerCargoForPdf()` (empleado resuelto por id o documento) y la plantilla `fo-gj-51-filled-download` pasa `:worker-cargo` al componente; **turno** y **puesto** (`fo51_shift` / `fo51_position`) se diligencian manualmente y son distintos del cargo en BD. Grilla de **fecha** del informe: 4×2 (FECHA + DD/MM/AAAA). En **CIUDAD**, municipio DIVIPOLA con **búsqueda al escribir** (`fo51-municipality-combobox.js`); catálogo desde **Ajustes → Territorio**. Encabezado del PDF FO-GJ-51 alineado con cartas oficiales (`official-letter-pdf-shell`). |
| **Evidencias pendientes** (supervisor) | Cola mínima de tareas (`Livewire\Disciplinary\Supervisor\PendingEvidenceIndex`) con: N° caso, trabajador, estado **Evidencia de citación pendiente**, fecha FO-GJ-03 y acción **Cargar evidencia**. Aparece solo para casos reportados por ese supervisor, con FO-GJ-03 generado y sin evidencia cargada; al cargar, desaparece de la cola. No expone detalle, historial, actuaciones ni chat del expediente. |
| **Revisión informes** | Cola `InformeSubmission` en estado pendiente de autorización: **vista previa del PDF** en modal (misma ruta con `?inline=1`), **confirmación de autorización** en modal de la aplicación (no diálogo nativo del navegador), acciones **Rechazar** y **Descargar**. El revisor asignado gestiona con `disciplinary.review-inform`; dirección ve todos con `disciplinary.review-inform-all`. Al autorizar se crea el expediente y el PDF pasa como documento del caso. |
| **Detalle del caso** | 4 tabs (Información / Línea de tiempo / Documentos / Actuaciones) + modal de transición. Si el expediente está en **bandeja compartida** (`isInInformePool()`), el abogado ve aviso y **Gestionar caso** (mismo flujo de confirmación que en el listado); la **tarjeta verde Etapa A** solo aparece con titular asignado (en pool se prioriza el banner de gestión). **Reasignar / quitar titular:** solo `admin` o permiso `disciplinary.assign` (`DisciplinaryCasePolicy::assign`); el abogado no devuelve casos al pool. **Tarjeta «Etapa A»** (Información, estado **Informe** y titular asignado): fila 1 **Etapa A** + botón **Ver informe (PDF)**; fila 2 trazabilidad del envío a revisión e incorporación del PDF con **fecha/hora Colombia** (`America/Bogota`); fila 3 **Autorización y creación del caso** — cargo y nombre de quien **autoriza** el FO-GJ-51 y genera el expediente (`InformeSubmission` vía `DisciplinaryCase::informeSubmission()`); fila 4 **Revisión y asignación** — cargo y nombre de quien registra la asignación del titular (última actuación **`CASO_ASIGNADO`** o **`CASO_ACEPTADO_ABOGADO`** según origen), abogado asignado y fecha Colombia. **Tarjeta «Etapa B · Citación»** (estado **citación programada**): checklist de requisitos (`DisciplinaryCitationWorkflowService`); botón **Siguiente etapa** con panel ✓/✗ si falta algo y modal de confirmación al avanzar a **diligencia**; **Iniciar coordinación** explícito; hilo con planeación; **radios** para fechas propuestas + **Confirmar fecha**; generación FO-GJ-03; sección **Evidencia de citación** (visible tras FO-GJ-03 + documento en expediente): formulario de carga o solo lectura según `uploadCitationEvidence` / `viewCitationEvidence`; tipos *citación firmada* y *rechazo con testigos* (PDF); auditoría `EVIDENCIA_CITACION_CARGADA` con `document_id` y usuario. **Cerrar coordinación** (titular o dirección jurídica). Partial: `livewire/disciplinary/cases/partials/stage-b-citation.blade.php`. **Hilo agenda / planeación (FO-GJ-03):** activo solo en **citación** o **reprogramación** (`allowsAgendaThread()`); en Informe, mensajes antiguos del hilo en solo lectura. |
| **Formatos** | Catálogo FO-GJ por etapa A–F; **Plantilla** abre modal con PDF en blanco (iframe `disciplinary.formats.preview`); **Descarga** fuerza descarga del mismo PDF que la vista previa. Para códigos registrados en `OfficialFormsCatalog::htmlBlankPdfRegistry()` (**FO-GJ-51**, **FO-GJ-03**, **FO-GJ-44**, **FO-GJ-54**, **FO-GJ-42**), el PDF se genera desde HTML con **Chrome headless** (Spatie Browsershot), **tamaño carta (Letter)**; esa fuente tiene **prioridad** sobre un PDF estático en `public/formatos/disciplinarios/`. Las cartas oficiales comparten encabezado grilla (`official-letter-pdf-shell`) y campos en blanco con guías grises. En el formulario FO-GJ-51, perfiles **supervisor / operador** no ven el enlace *Catálogo de formatos* en la barra de acciones. `GET /disciplinary/forms/informe-fo-gj-51` redirige al listado con modal salvo **`?vista_completa=1`** (pantalla dedicada). El envío del informe es `POST /disciplinary/forms/informe-fo-gj-51` (`disciplinary.forms.informe.process`: generar PDF, enviar a revisión o cargar PDF externo). Rutas de catálogo: `GET …/formats/preview/{code}`, `GET …/formats/descarga-en-blanco/{code}`. |

**Disciplinario — agenda Etapa B:** `DisciplinaryCase::statusesAllowingAgendaCoordination()` limita el chat abogado ↔ planeación a **citación** y **reprogramación**; `DisciplinaryWorkflowService` no exige respuesta de planeación para pasar de **Informe** a **citación**. Políticas y `DisciplinaryAgendaThreadService` usan `allowsAgendaThread()`.

**Disciplinario — bandeja de abogados (etapa INFORME):** `DisciplinaryCase::scopeInInformePool()` / `isInInformePool()` identifican expedientes en estado **informe** sin titular. El alcance de listados para **abogado** (`forDisciplinaryActor`) une casos propios y pool. Política `claim` autoriza tomar gestión; `view` permite consulta del pool; `update` / `transition` exigen titular asignado. Concurrencia: `claimByLawyer()` actualiza solo si `assigned_lawyer_id` sigue nulo; si falla, `CaseAlreadyClaimedException`. Tests: `tests/Feature/Disciplinary/DisciplinaryLawyerPoolClaimTest.php`.

**Disciplinario — Etapa B (citación):** `coordination_started_at` se marca con **Iniciar coordinación** (no al pasar de Informe a citación). Planeación publica `proposed_slots` en mensajes; el abogado elige fecha con UI visual (`CaseDetail::confirmCitationSlot`). Salida de citación: guards en `DisciplinaryWorkflowService` + botón **Siguiente etapa** en Livewire (mismas reglas que `DisciplinaryCitationWorkflowService`). FO-GJ-03: `FoGj03CitationService`, rutas `cases/{case}/fo-gj-03/pdf` y `POST …/generate`. Migración de campos: `2026_05_21_120000_disciplinary_workflow_target_state.php`.

**Disciplinario — coordinaciones con planeación:** bandeja `Livewire\Disciplinary\Coordinations\Index` para rol `planeacion`; cierre con `coordination_status` / `closed_at` en `disciplinary_agenda_threads` (`2026_06_02_080000_add_closure_fields_to_disciplinary_agenda_threads.php`); actuación `COORDINACION_CERRADA`. Notificaciones a planeación enlazan a `disciplinary.coordinations.index`.

**Disciplinario — evidencia de citación:** `canReceiveCitationEvidence()` exige timestamp FO-GJ-03 y documento con nota `FO-GJ-03 generado desde expediente`. Carga: `uploadCitationEvidence` + `DisciplinaryCitationWorkflowService::assertCitationEvidenceUploadAllowed()`. Quién puede subir: `canUserUploadCitationEvidence()` (titular, `informeSubmission.reviewed_by`, supervisor `submitted_by`/`reporter_id`, dirección jurídica; excluye `planeacion`). El supervisor sube desde la cola `evidences-pending` sin permiso `view`/`viewAny` de expediente.

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

En **crear/editar usuario**: **Área** + **Cargo** (obligatorios salvo «Administrador de la plataforma»); checkbox para **`admin`** desactiva área/cargo en pantalla. Los permisos directos extra para **Operaciones** (FO-GJ-51, notificaciones, PDF) siguen como toggles cuando el ámbito es Operaciones.

## 🏛️ Workflow del proceso disciplinario

Etapas normativas SJ (referencia):

| Etapa | Contenido |
| --- | --- |
| **A** | Falta e informe disciplinario — **FO-GJ-51**. La coordinación de fechas con planeación (**FO-GJ-03**, chat e imágenes) corresponde a la **Etapa B** (citación / reprogramación), no al estado Informe. |
| **B** | Citación a diligencia disciplinaria por escrito — **FO-GJ-03**. Si no asiste: **FO-GJ-44** (constancia de inasistencia) y **2 días calendario** para justificar; si justifica → reprogramación (**FO-GJ-54**); si no → comité disciplinario para decisión |
| **C** | Diligencia disciplinaria y acta — **FO-GJ-42** |
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
DILIGENCIA (FO-GJ-42) ◄──────────────┘
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
    Disciplinary/              OfficialFormsCatalog, DisciplinaryAssets, FoGj51Catalog, …
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
    Controllers/Disciplinary/     Casos (web + API), formatos (preview/descarga), FO-GJ-51 (show/process, PDF pendiente), FO-GJ-03 por caso (`FoGj03CaseController`), GeoJSON mapa (`DisciplinaryGeoJsonController`)
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
    ui/                        Controles UI compartidos (p. ej. selector de tema)
  disciplinary/forms/        FO-GJ-51 (informe); FO-GJ-03/44/54/42: plantillas carta Letter en blanco
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
git clone https://github.com/DEV-SJPRIVADA/SJ_LegalSuite.git
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
| **FO-GJ-42** | Acta de diligencia disciplinaria | `fo-gj-42-blank-download` |

Para esos códigos, la **plantilla HTML tiene prioridad** sobre un PDF estático homónimo en `public/formatos/disciplinarios/`. El iframe de vista previa usa query `rev=` (mtime de la vista) para invalidar caché al editar plantillas.

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
| `planeacion@sjlegalsuite.local` | planeacion | **Coordinaciones** abiertas (no listado/detalle de expedientes); responde en hilo FO-GJ-03; **assign-date**; sin dashboard, formatos ni carga de evidencia de citación |
| `administrativa@sjlegalsuite.local` | administrativa | Crear informes y cargar evidencias |
| `auditor@sjlegalsuite.local` | auditor | Consulta + exportación disciplinaria |
| `operaciones@sjlegalsuite.local` | operaciones | Crear casos + subir evidencias |
| `supervisor@sjlegalsuite.local` | supervisor | Solo FO-GJ-51 + cola **Evidencias pendientes** para cargar evidencia de citación; sin listado ni detalle de expedientes |
| `operador@sjlegalsuite.local` | operador | Casos operativos en campo según políticas del módulo |
| `programador@sjlegalsuite.local` | programador | Programación de fechas (planeación) |

En **Usuarios → crear/editar**, el interruptor **«Puede realizar cambios»** define si el usuario queda en modo solo lectura (`read_only`): no podrá mutar disciplinarios ni gestionar otros usuarios (los admin en solo lectura solo consultan). Los usuarios demo con rol **`admin`** se crean **sin** `organizational_area_id`; el resto lleva **área + `job_position_id`** acorde al catálogo sembrado en la migración de legalsuite.

Si actualizas código y una BD ya tenía migraciones viejas aplicadas, puede faltar una columna nueva: en desarrollo suele bastar **`migrate:fresh --seed`**; en datos reales, revisar que el esquema coincida con las migraciones del repo (no editar migraciones ya ejecutadas en producción sin plan de alter explícito).

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

**Planeación (`planeacion`):** no tiene `view` / `viewAny` sobre expedientes (`DisciplinaryCasePolicy`). Opera en **`GET /disciplinary/coordinations`** (hilos abiertos). No puede `uploadCitationEvidence`. El abogado titular o dirección jurídica puede **cerrar coordinación** (`closeCoordination`).

**Supervisor (`supervisor`):** no tiene `view` / `viewAny` sobre expedientes (`DisciplinaryCasePolicy`) ni acceso al dashboard disciplinario. Puede generar FO-GJ-51 y cargar evidencia FO-GJ-03 únicamente desde **`GET /disciplinary/evidences-pending`**.

**Abogado (`abogado`):** listado y detalle incluyen expedientes **asignados** y **bandeja INFORME** sin titular. Tomar un caso del pool: política `claim` + `claimByLawyer()` (no usa `disciplinary.assign`). Reasignación manual del titular: `assign` (admin o `disciplinary.assign`).

**Auditor (`auditor`):** ve todos los expedientes, incluida la bandeja INFORME; no puede `claim` ni mutar.

## 📊 Endpoints

### Páginas Livewire (UI)

| Ruta | Descripción |
|---|---|
| `GET /dashboard` | **Inicio** (dashboard global con alertas) |
| `GET /disciplinary/dashboard` | Dashboard del módulo disciplinario; Gate `viewDashboard` sobre `DisciplinaryCase` (roles **`planeacion`** y **`supervisor`** sin acceso). |
| `GET /disciplinary/map-geo/{file}` | Sirve GeoJSON GADM (`gadm41_COL_1.json` \| `gadm41_COL_2.json`); sesión iniciada y (`viewDashboard` **o** `viewAny` sobre casos disciplinarios). |
| `GET /settings/territorio` | **Ajustes · Territorio**: importación listado DIVIPOLA; permiso `settings.manage-territory` |
| `GET /disciplinary/cases` | Listado de casos con filtros (roles `planeacion` y `supervisor` → 403) |
| `GET /disciplinary/evidences-pending` | Cola de tareas para supervisor: solo carga de evidencia de citación |
| `GET /disciplinary/coordinations` | Bandeja de coordinaciones abiertas para **planeación** |
| `GET /disciplinary/coordinations/{thread}/attachments/{attachment}` | Descarga de adjunto del hilo de coordinación |
| `GET /disciplinary/coordinations/{thread}/attachments/{attachment}/inline` | Vista inline del adjunto |
| `GET /disciplinary/formats` | Catálogo de formatos oficiales (FO-GJ / etapas A–F) |
| `GET /disciplinary/formats/preview/{code}` | Vista previa inline del PDF en blanco (misma fuente que la descarga): **HTML→PDF Letter** (Browsershot) si el código está en el registro HTML; si no, PDF estático en `public/formatos/disciplinarios/`; Gate `viewOfficialForms`. |
| `GET /disciplinary/formats/descarga-en-blanco/{code}` | Descarga plantilla en blanco en PDF Letter; misma prioridad HTML que la vista previa; Gate `viewOfficialForms`. |
| `GET /disciplinary/forms/informe-fo-gj-51` | Para perfiles con acceso a casos: redirige al listado con query (`informe_modal`, opc. `cargar_pdf`, `nombre`, `cedula`). Para `supervisor`: abre pantalla completa FO-GJ-51 automáticamente (sin pasar por listado de casos). |
| `POST /disciplinary/forms/informe-fo-gj-51` | Procesa el informe (`FoGj51ProcessRequest`): acción `pdf` (descarga Letter con **CARGO** desde BD de empleados), `enviar` (genera el mismo PDF y lo envía a cola de revisión) o `cargar` (PDF externo sin regenerar). Requiere **`fo51_assigned_reviewer_id`** en `enviar` y `cargar`. |
| `GET /disciplinary/cases/{case}/fo-gj-03/pdf` | Vista previa / descarga FO-GJ-03 del expediente en citación. |
| `POST /disciplinary/cases/{case}/fo-gj-03/generate` | Genera y guarda FO-GJ-03 en el caso (titular / políticas). |
| `GET /disciplinary/informes-pendientes` | **Revisión informes** — listado Livewire de `InformeSubmission` pendientes; `disciplinary.review-inform` (revisor asignado) o `disciplinary.review-inform-all` (dirección). |
| `GET /disciplinary/informes-pendientes/{submission}/pdf` | Descarga el PDF almacenado o, con **`?inline=1`**, lo sirve **inline** para iframe (vista previa en modal). |
| `GET /employees` | **BD de empleados** (Livewire); permiso `employees.view` |
| `GET /employees/plantilla` | Descarga plantilla Excel carga masiva; `employees.manage` |
| `GET /api/employees/search` | Autocompletado por documento/nombre (JSON) |
| `GET /users` | Listado de usuarios (Livewire) |
| `GET /users/organizacion` | Catálogo **Organización**: áreas y cargos (`permission_role_name`) |
| `GET /users/{user}` | Detalle de usuario |
| `GET /password/first-login` | Cambio obligatorio de contraseña (primer ingreso o tras reinicio admin) |
| `GET /profile` | Configuración de cuenta |

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
- [ ] Exportación PDF de actuaciones con plantillas FO-GJ (FO-GJ-03, FO-GJ-44, FO-GJ-54 y FO-GJ-42 ya tienen plantilla HTML en blanco; falta diligenciamiento desde el caso)
- [ ] Vista Kanban "Mi pipeline" por abogado
- [ ] Tests Pest reemplazando el `WorkflowSmokeTest`

### Otros módulos del sistema

- [ ] Licitaciones, Acciones de tutela, Demandas
- [ ] Negociación colectiva, Investigaciones
- [ ] Cartera, Requisitos legales
- [ ] Contratos, Pólizas, Auditoría
- [ ] Integración con SJ_Armory vía `employees.external_id`

## 📄 Licencia

Software interno de **SJ Seguridad**. Uso restringido.
