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

Además del catálogo jurídico, existe el módulo de **administración de usuarios** en el sidebar (permisos `users.view` / `users.manage`): listado con filtros, alta/edición, activación y reinicio de contraseña con contraseña provisional generada automáticamente.

Quienes tengan **`settings.manage-territory`** ven **Ajustes** en el sidebar: pantalla **`/settings/territorio`** para importar el listado **DIVIPOLA** (municipios con código oficial y coordenadas). Ese catálogo alimenta los **pins del mapa** en el dashboard disciplinario y la vinculación por municipio en los expedientes.

## ✨ Características principales (módulo Disciplinario)

- **Workflow estricto y validado**: 13 estados, transiciones controladas, plazo de **2 días calendario** para justificar inasistencia a citación (tras constancia).
- **Trazabilidad legal completa**: cada cambio en un caso queda registrado en un audit log inmutable.
- **Roles y permisos granulares** (Spatie Permission v6): paquetes de permisos técnicos (`admin`, `abogado`, `planeacion`, etc.). En negocio, el **área** es el ámbito organizacional (Jurídica, Operaciones…); **dentro del área** el usuario tiene un **cargo** (supervisor, operador, programador…). Cada cargo enlaza a un rol Spatie vía **`job_positions.permission_role_name`** (configurable en **Usuarios → Organización**). El perfil **`admin`** es aparte: «Administrador de la plataforma» en el formulario de usuario. Más el flag **solo lectura** por usuario.
- **Dashboard analítico**: donas por **etapa del flujo** (total + **A–F** según `current_stage_type`, vía `DisciplinaryDashboardService::workflowStageDonuts`), más distribución por **tipo de falta**, **mapa Leaflet de Colombia** (límites GADM por departamento y, al acercar zoom, municipios; pins con total de casos por municipio según DIVIPOLA en catálogo) y **carga por abogado** (consultas agregadas eficientes).
- **Listado de casos** con 7 filtros combinables y paginación, optimizado para alto volumen.
- **Documentos por etapa** con verificación de integridad (SHA-256) y vinculación a formatos oficiales (FO-GJ-XX).

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

### Vista de Inicio (Dashboard global)

Al iniciar sesión, el usuario ve un resumen de toda la operación:

- **4 tarjetas de alertas** (cada una con sus 5 items críticos linkeados):
  - 🔴 Plazos vencidos (etapas con deadline pasado)
  - 🟡 Próximos a vencer (plazo en 3 días o menos)
  - 🟦 Sin abogado asignado
  - 🩵 Pendientes de decisión
- **Gráfica de tendencia** mensual de casos abiertos (últimos 6 meses)
- **Acceso rápido** a los módulos disponibles

`AlertsService` es el agregador global y está preparado para sumar alertas de los demás módulos
cuando se vayan creando.

### Módulo Disciplinario

Sub-nav superior (según permisos): **Inicio | Dashboard | Disciplinarios | (Revisión informes) | Formatos | Historial**. El enlace *Revisión informes* aparece quienes tienen **`disciplinary.review-inform`** (`InformeSubmissionPolicy::viewAny`).

| Vista | Contenido |
|---|---|
| **Dashboard** | Encabezado reducido: solo la rúbrica **«Disciplinarios · Dashboard»** y el botón al listado de casos (sin título largo ni descripción). **Casos por etapa**: 7 donas (ApexCharts) — total + **A–F** según `current_stage_type` (centro con % y cantidad; **B** y **C** con agrupaciones acordadas); contenedor y rejilla compactos (`items-start`, sin padding inferior en la caja, altura de canvas ajustada) para limitar el aire bajo las donas; etiqueta corta por columna. Debajo: barras por **tipo de falta**, **mapa por ciudad** (Leaflet + GeoJSON GADM, tiles Carto; datos vía `disciplinary.map-geo`) y tabla **carga por abogado**. |
| **Disciplinarios** (listado) | 3 tarjetas de vistas rápidas + 7 filtros combinables + tabla paginada. Botones **Nuevo informe (FO-GJ-51)** y **Cargar informe en PDF** abren un **modal** a pantalla completa con el formulario (no navegan a otra página). Enlaces desde catálogo o detalle de caso usan query `?informe_modal=1` (y `nombre`/`cedula` opcionales). |
| **Revisión informes** | Cola `InformeSubmission` en estado pendiente de autorización: **vista previa del PDF** en modal (misma ruta con `?inline=1`), **confirmación de autorización** en modal de la aplicación (no diálogo nativo del navegador), acciones **Rechazar** y **Descargar**. Al autorizar se crea el expediente y el PDF pasa como documento del caso. |
| **Detalle del caso** | 4 tabs (Información / Línea de tiempo / Documentos / Actuaciones) + modal de transición |
| **Formatos** | Catálogo FO-GJ por etapa A–F; **Plantilla** abre modal con PDF en blanco (iframe `disciplinary.formats.preview`); **Descarga** fuerza descarga del mismo PDF que la vista previa. Si existe archivo estático en `public/formatos/disciplinarios/{código}.pdf`, tiene prioridad; si no (p. ej. **FO-GJ-51**), el PDF se genera desde HTML con **Chrome headless** (Spatie Browsershot), **tamaño carta (Letter)**. En el formulario FO-GJ-51, perfiles **supervisor / operador** no ven el enlace *Catálogo de formatos* en la barra de acciones. `GET /disciplinary/forms/informe-fo-gj-51` redirige al listado con modal salvo **`?vista_completa=1`** (pantalla dedicada). El envío del informe es `POST /disciplinary/forms/informe-fo-gj-51` (`disciplinary.forms.informe.process`: generar PDF, enviar a revisión o cargar PDF externo). Rutas de catálogo: `GET …/formats/preview/{code}`, `GET …/formats/descarga-en-blanco/{code}`. |

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
| **A** | Falta e informe disciplinario — **FO-GJ-51** |
| **B** | Citación a diligencia disciplinaria por escrito — **FO-GJ-03**. Si no asiste: constancia de inasistencia y **2 días calendario** para justificar; si justifica → reprogramación (**FO-GJ-54**); si no → comité disciplinario para decisión |
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
   │   │   └─► CITACION_NO_ASISTIO
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
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS, ApexCharts, Leaflet (mapa Colombia en dashboard disciplinario)
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
    Disciplinary/
      OfficialFormsCatalog.php   Catálogo FO-GJ / etapas A–F + códigos con PDF HTML (Letter)
      DisciplinaryAssets.php    Ruta única del logo público (`images/logo solo.png`)
      FoGj51Catalog.php          Textos fijos FO-GJ-51 (validación + vista)
    Pdf/
      HtmlLetterPdfGenerator.php HTML → PDF tamaño Letter (Browsershot)
      BrowsershotBinaryResolver.php Detección Node/npm/Chrome (p. ej. Laragon)
      EmbeddedPublicAsset.php    Data URI para assets en PDF (logo embebido)
  Models/
    User.php / Personnel.php / OrganizationalArea.php / JobPosition.php / Role.php (Spatie)
    ColombianMunicipality.php   Catálogo DIVIPOLA (código, nombre, lat/lon) para mapa y expedientes
    Disciplinary/              Models del agregado disciplinario + InformeSubmission (cola pre-expediente FO-GJ-51)
  Services/
    AlertsService.php          Agregador global de alertas para Inicio
    UserService.php            Alta/edición usuarios, reinicio provisional de contraseña
    Disciplinary/              CaseService / WorkflowService / DashboardService / DocumentService / DisciplinaryInformeSubmissionService (cola FO-GJ-51)
    Settings/                  ColombianMunicipalityImportService (Excel/CSV DIVIPOLA)
    Personnel/                 Resolución de personal desde identidad del informe
  Policies/                    DisciplinaryCasePolicy, UserPolicy, InformeSubmissionPolicy, PersonnelPolicy
  Livewire/
    Home.php                   Componente del dashboard global
    Auth/                      ForcePasswordChange, LogoutButton
    Users/                     UsersIndex, UserDetail, OrganizationCatalog
    Disciplinary/              Dashboard, CasesIndex, CaseDetail, FormatsCatalog, InformesPendientes; FO-GJ-51 parcial/modal
    Settings/                  TerritoryImport (importación DIVIPOLA / municipios)
    Ui/                        ThemeToggle (preferencia tema usuario)
  Http/
    Middleware/                must-change-password, ShareUiTheme, ForceRequestRootUrl (URLs con host/puerto de la petición)
    Controllers/Disciplinary/     Casos (web + API), formatos (preview/descarga), FO-GJ-51 (show/process, PDF pendiente inline/descarga), GeoJSON mapa (`DisciplinaryGeoJsonController`)
    Requests/Disciplinary/     FormRequests (casos + FO-GJ-51: FoGj51ProcessRequest, StoreFoGj51InformePdfRequest)
    Requests/Users/            FormRequests del módulo usuarios

database/
  migrations/                  Disciplinario + Spatie + extensión `users` (contacto, `read_only`, `must_change_password`, `theme`, soft deletes, FK a áreas/cargos), tablas **`organizational_areas`** y **`job_positions`** (columna **`permission_role_name`**), notificaciones, etc.
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
  disciplinary/forms/        FO-GJ-51: plantilla en blanco/rellena para PDF; parciales reutilizados en modal y pantalla `vista_completa`
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

# 5. Frontend (Vite + Tailwind). Si npm no resuelve en la terminal:
#    & "C:\laragon\bin\nodejs\node-v18\npm.cmd" install
#    & "C:\laragon\bin\nodejs\node-v18\npm.cmd" run build
npm install
npm run build
```

### PDF disciplinarios (HTML → tamaño carta / Letter)

Las plantillas que no tienen archivo estático en `public/formatos/disciplinarios/` (p. ej. **FO-GJ-51**) se convierten de HTML a PDF con **Spatie Browsershot** y **Puppeteer** (Chromium). La salida es siempre **Letter**.

1. Después de `composer install`, ejecute **`npm install`** en la raíz del proyecto (trae la dependencia **puppeteer**).
2. Verifique el entorno con **`php artisan disciplinary:pdf-check`** (Node/npm/Chrome y logo legible en disco).
3. Opcional en `.env`: `NODE_BINARY`, `NPM_BINARY`, `PDF_CHROME_PATH`, `PDF_BROWSER_TIMEOUT` (detalle en `.env.example`). En Windows suele bastar la detección automática (Laragon en `C:\laragon\bin\nodejs\…`, Chrome en Program Files).

El logo para interfaz y para incrustar en el PDF debe estar en **`public/images/logo solo.png`** (referencia única: `App\Support\Disciplinary\DisciplinaryAssets::LOGO_RELATIVE_PATH`).

### Mapa Colombia (dashboard disciplinario)

1. Descargue los GeoJSON GADM al árbol público del proyecto:

   ```bash
   php artisan geo:download-colombia-gadm
   ```

   Dejarán de existir (o actualizarse) **`public/geo/gadm41_COL_1.json`** (departamentos) y **`public/geo/gadm41_COL_2.json`** (municipios).

2. El navegador **no** lee esos archivos solo por ruta estática en todos los despliegues: la aplicación los expone autenticada en **`GET /disciplinary/map-geo/{file}`** (`disciplinary.map-geo`), con lista blanca de los dos nombres anteriores y la misma autorización que ver el dashboard o el listado de casos.

3. El bundle Vite incluye **`resources/js/disciplinary-colombia-map.js`** (Leaflet). El montaje evita inicializar el mapa dos veces en paralelo (p. ej. al refrescar la página). Tras tocar JS o estilos del mapa, ejecute **`npm run build`**.

4. Para **pins** en el mapa hace falta que los expedientes tengan código de municipio acorde al catálogo y que existan coordenadas en **`colombian_municipalities`** (importación en **Ajustes · Territorio**).

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
| `abogado@sjlegalsuite.local` | abogado | Solo casos donde figura como abogado asignado |
| `planeacion@sjlegalsuite.local` | planeacion | Ver disciplinarios y programar fechas en etapas, sin mover estados |
| `administrativa@sjlegalsuite.local` | administrativa | Crear informes y cargar evidencias |
| `auditor@sjlegalsuite.local` | auditor | Consulta + exportación disciplinaria |
| `operaciones@sjlegalsuite.local` | operaciones | Crear casos + subir evidencias |
| `supervisor@sjlegalsuite.local` | supervisor | Casos asignados en campo; FO-GJ-51 + informes (sin enlace catálogo formatos en el formulario) |
| `operador@sjlegalsuite.local` | operador | Mismo alcance operativo que supervisor |
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

La IP (`172.16.16.90` en el ejemplo) es la del **equipo donde corre Laragon**; si DHCP asigna otra, use esa IP con **`:8082`**. El `.env` de LegalSuite usa **`APP_URL=http://172.16.16.90:8082`**, paralelo a Armory (`APP_URL=http://172.16.16.90`). Con **`APP_USE_REQUEST_URL=true`**, si entran con otro host/IP válido, Laravel genera enlaces con esa misma base.

**Importante:** incluya **`http://`** y **`:8082`** para LegalSuite. En **Android**, el nombre `SJPCANAOPE1` puede **no resolverse**; use la **IP** como cuando abren Armory.

Apache (misma forma que `00-aaa-sj_armory.conf`, otro puerto): `C:\laragon\etc\apache2\sites-enabled\00-aac-sj_legalsuite.conf`. **`SESSION_DOMAIN`** vacío, como en Armory.

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
disciplinary.review-inform
settings.manage-territory
personnel.view / .manage          users.view / .manage
```

La autorización se evalúa en 3 capas:

1. **Policies** (`DisciplinaryCasePolicy`, `UserPolicy`, `InformeSubmissionPolicy`, `PersonnelPolicy`) — rol, permisos Spatie y flag **`read_only`** del usuario.
2. **FormRequests** — `authorize()` delega al Policy.
3. **Vistas** — `@can()` controla qué se renderiza (incluyendo enlaces del sidebar).

## 📊 Endpoints

### Páginas Livewire (UI)

| Ruta | Descripción |
|---|---|
| `GET /dashboard` | **Inicio** (dashboard global con alertas) |
| `GET /disciplinary/dashboard` | Dashboard del módulo disciplinario |
| `GET /disciplinary/map-geo/{file}` | Sirve GeoJSON GADM (`gadm41_COL_1.json` \| `gadm41_COL_2.json`); sesión + `viewDashboard` o `viewAny` sobre casos disciplinarios |
| `GET /settings/territorio` | **Ajustes · Territorio**: importación listado DIVIPOLA; permiso `settings.manage-territory` |
| `GET /disciplinary/cases` | Listado de casos con filtros |
| `GET /disciplinary/formats` | Catálogo de formatos oficiales (FO-GJ / etapas A–F) |
| `GET /disciplinary/formats/preview/{code}` | Vista previa inline del PDF en blanco (misma fuente que la descarga): archivo en disco o **HTML→PDF Letter** (Browsershot) para códigos como FO-GJ-51; Gate `viewOfficialForms`. |
| `GET /disciplinary/formats/descarga-en-blanco/{code}` | Descarga plantilla en blanco en PDF Letter; si existe PDF estático en `public/formatos/disciplinarios/`, ese archivo tiene prioridad sobre la plantilla HTML; Gate `viewOfficialForms`. |
| `GET /disciplinary/forms/informe-fo-gj-51` | Por defecto **redirige** al listado de casos con query (`informe_modal`, opc. `cargar_pdf`, `nombre`, `cedula`). Con **`?vista_completa=1`** devuelve la pantalla completa de diligenciamiento FO-GJ-51. |
| `POST /disciplinary/forms/informe-fo-gj-51` | Procesa el informe (`FoGj51ProcessRequest`): acción `pdf` (descarga Letter), `enviar` (cola de revisión) o `cargar` (PDF externo). |
| `GET /disciplinary/informes-pendientes` | **Revisión informes** — listado Livewire de `InformeSubmission` pendientes de autorización; permiso `disciplinary.review-inform`. |
| `GET /disciplinary/informes-pendientes/{submission}/pdf` | Descarga el PDF almacenado o, con **`?inline=1`**, lo sirve **inline** para iframe (vista previa en modal). |
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
- **Assets frontend**: tras cambios en vistas Blade (clases Tailwind), `resources/css` o JS del bundle, ejecutar **`npm run build`** (o `vite build`) para actualizar `public/build/`.

## 📚 Documentación adicional

Ver [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) para:

- Diagrama completo del modelo de datos
- Decisiones de diseño (denormalización del estado, soft deletes, índices compuestos)
- Cómo agregar una nueva etapa al workflow
- Estrategia de auditoría legal
- Próximos pasos sugeridos

## 🧭 Roadmap

### Módulo Disciplinario — siguientes fases

- [ ] Wizard de creación de caso (formulario con autocompletado de personal)
- [ ] Subida de documentos desde la UI (`DocumentService` ya listo en backend)
- [ ] Notificaciones por email cuando un plazo está próximo a vencer
- [ ] Exportación PDF de actuaciones con plantillas FO-GJ
- [ ] Vista Kanban "Mi pipeline" por abogado
- [ ] Tests Pest reemplazando el `WorkflowSmokeTest`

### Otros módulos del sistema

- [ ] Licitaciones, Acciones de tutela, Demandas
- [ ] Negociación colectiva, Investigaciones
- [ ] Cartera, Requisitos legales
- [ ] Contratos, Pólizas, Auditoría
- [ ] Integración con SJ_Armory vía `personnel.external_id`

## 📄 Licencia

Software interno de **SJ Seguridad**. Uso restringido.
