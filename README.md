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

## ✨ Características principales (módulo Disciplinario)

- **Workflow estricto y validado**: 13 estados, transiciones controladas, plazos legales automáticos
  (ej: 2 días hábiles para justificar inasistencia a citación).
- **Trazabilidad legal completa**: cada cambio en un caso queda registrado en un audit log inmutable.
- **Roles y permisos granulares** (Spatie Permission v6): admin, jurídico, gerencia, auditor, operaciones.
- **Dashboard analítico** con KPIs, distribución por falta, por ciudad y carga por abogado en una sola query.
- **Listado de casos** con 7 filtros combinables y paginación, optimizado para alto volumen.
- **Documentos por etapa** con verificación de integridad (SHA-256) y vinculación a formatos oficiales (FO-GJ-XX).

## 🖥️ Interfaz de usuario

### Layout global

- **Sidebar lateral fijo** con los 12 módulos del sistema. Los disponibles tienen acceso directo;
  los demás aparecen deshabilitados con badge "Próx." para que el cliente vea el alcance completo
  desde el primer día.
- **Topbar** con acceso a perfil y botón de salir.
- **Sub-nav contextual** por módulo (sticky bajo el topbar).
- **Responsive**: en móvil el sidebar se oculta y se accede con un botón hamburguesa.

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

Sub-nav superior: **Inicio | Dashboard | Disciplinarios | Formatos | Historial**

| Vista | Contenido |
|---|---|
| **Dashboard** | 4 KPIs (total/pendientes/en proceso/finalizados), gráfica por falta, por ciudad y carga por abogado |
| **Disciplinarios** (listado) | 3 tarjetas de vistas rápidas + 7 filtros combinables + tabla paginada |
| **Detalle del caso** | 4 tabs (Información / Línea de tiempo / Documentos / Actuaciones) + modal de transición |

## 🏛️ Workflow del proceso disciplinario

```
BORRADOR
   ↓
INFORME (FO-GJ-51) ────────────────► ARCHIVADO
   ↓
CITACION_PROGRAMADA (FO-GJ-03) ─┐
   │   │   │                    │
   │   │   └─► CITACION_NO_ASISTIO
   │   │             ↓
   │   │      JUSTIFICACION_PENDIENTE (deadline 2 días hábiles)
   │   │           │            │
   │   │           ↓            ↓
   │   └──► REPROGRAMADO   COMITE_DISCIPLINARIO
   │           │
   ↓           ↓
DILIGENCIA (FO-GJ-42) ──► DECISION
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
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS, ApexCharts
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
  Models/
    User.php / Personnel.php
    Disciplinary/              Models del agregado disciplinario
  Services/
    AlertsService.php          Agregador global de alertas para Inicio
    Disciplinary/              CaseService / WorkflowService / DashboardService / DocumentService
  Policies/                    DisciplinaryCasePolicy (autorización)
  Livewire/
    Home.php                   Componente del dashboard global
    Auth/LogoutButton.php
    Disciplinary/              Componentes del módulo (Dashboard, CasesIndex, CaseDetail)
  Http/
    Controllers/Disciplinary/  Controllers (web + API JSON)
    Requests/Disciplinary/     FormRequests con autorización delegada al Policy

database/
  migrations/                  8 migraciones del módulo + Spatie tables
  seeders/                     RolesAndPermissions, FaultsCatalog, DemoUsers, WorkflowSmokeTest

resources/views/
  layouts/app.blade.php        Layout principal con sidebar + topbar + sub-nav
  livewire/
    home.blade.php             Vista del dashboard global
    disciplinary/              Vistas del módulo
    auth/
  components/
    app-sidebar.blade.php      Sidebar de módulos (con catálogo de los 12)
    app-sidebar-icon.blade.php Heroicons inlineados (sin dependencia externa)
    disciplinary/              kpi-card, status-badge, nav (sub-nav del módulo)
    home/                      alert-card

docs/
  ARCHITECTURE.md              Documentación detallada de arquitectura
```

## 🚀 Instalación

### Requisitos

- PHP 8.2+
- Composer 2
- MySQL 8 (o MariaDB compatible)
- Node.js 18+ (recomendado 20 LTS)
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

# 5. Frontend
npm install
npm run build
```

### Probar el workflow end-to-end

```bash
php artisan db:seed --class="Database\Seeders\WorkflowSmokeTest"
```

Esto crea un caso ficticio y lo recorre por las 8 transiciones del workflow, validando que todo funciona.

## 👥 Usuarios demo (entorno local)

| Email | Rol | Capacidades |
|---|---|---|
| `admin@sjlegalsuite.local` | admin | Todo |
| `juridico@sjlegalsuite.local` | juridico | Control total del módulo disciplinario |
| `gerencia@sjlegalsuite.local` | gerencia | Ver casos + dashboard + exportar |
| `auditor@sjlegalsuite.local` | auditor | Solo lectura |
| `operaciones@sjlegalsuite.local` | operaciones | Crear casos + subir evidencias |

> Contraseña por defecto: **`SJseguridad2026`**. Cambiarla antes de cualquier deploy productivo.

## 🌐 Acceso en red local (Laragon)

El proyecto está configurado para escuchar en el puerto **8082** sin afectar otros sitios:

- Local: http://localhost:8082
- LAN: http://172.16.16.90:8082 (sustituir por la IP del servidor)
- Por hostname: http://SJPCANAOPE1:8082

Configuración Apache: `C:\laragon\etc\apache2\sites-enabled\00-aac-sj_legalsuite.conf`

## 🔒 Modelo de autorización

Permisos disponibles:

```
disciplinary.view              disciplinary.transition
disciplinary.view-dashboard    disciplinary.assign
disciplinary.create            disciplinary.upload-document
disciplinary.update            disciplinary.export
disciplinary.delete            personnel.view / .manage
                               users.view / .manage
```

La autorización se evalúa en 3 capas:

1. **Policies** (`DisciplinaryCasePolicy`) — reglas finas por rol, permiso y *ownership*.
2. **FormRequests** — `authorize()` delega al Policy.
3. **Vistas** — `@can()` controla qué se renderiza (incluyendo enlaces del sidebar).

## 📊 Endpoints

### Páginas Livewire (UI)

| Ruta | Descripción |
|---|---|
| `GET /dashboard` | **Inicio** (dashboard global con alertas) |
| `GET /disciplinary/dashboard` | Dashboard del módulo disciplinario |
| `GET /disciplinary/cases` | Listado de casos con filtros |
| `GET /disciplinary/cases/{case}` | Detalle del caso (4 tabs + modal de transición) |
| `GET /profile` | Configuración de cuenta |

### API JSON (programática)

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/disciplinary/dashboard` | KPIs en JSON |
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
