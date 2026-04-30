<h1 align="center">SJ LegalSuite</h1>

<p align="center">
  <strong>Sistema de gestión jurídica disciplinaria</strong><br>
  Plataforma centralizada para administrar procesos disciplinarios con control de etapas,
  trazabilidad legal completa y reportes en tiempo real.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Livewire-3-FB70A9?logo=livewire&logoColor=white" alt="Livewire 3">
  <img src="https://img.shields.io/badge/Tailwind-4-06B6D4?logo=tailwindcss&logoColor=white" alt="TailwindCSS 4">
</p>

---

## ✨ Características principales

- **Módulo Disciplinario** como núcleo del sistema, con workflow estricto y validado.
- **Trazabilidad legal completa**: cada cambio en un caso queda registrado en un audit log inmutable.
- **Workflow centralizado**: 13 estados, transiciones controladas, plazos legales automáticos
  (ej: 2 días hábiles para justificar inasistencia a citación).
- **Roles y permisos granulares** (Spatie Permission v6): admin, jurídico, gerencia, auditor, operaciones.
- **Dashboard analítico** con KPIs, distribución por falta, por ciudad y carga por abogado en una sola query.
- **Listado de casos** con 7 filtros combinables y paginación, optimizado para alto volumen.
- **Documentos por etapa** con verificación de integridad (SHA-256) y vinculación a formatos oficiales (FO-GJ-XX).

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

## 📁 Estructura del módulo

```
app/
  Enums/Disciplinary/        Enums del dominio (CaseStatus, StageType, etc.)
  Exceptions/Disciplinary/   InvalidStateTransitionException
  Workflow/Disciplinary/     TransitionMap (única fuente de verdad de transiciones)
  Models/Disciplinary/       Models del agregado disciplinario
  Services/Disciplinary/     CaseService / WorkflowService / DashboardService / DocumentService
  Policies/                  DisciplinaryCasePolicy (autorización)
  Livewire/Disciplinary/     Componentes Livewire (Dashboard, CasesIndex, CaseDetail)
  Http/Controllers/          Controllers (web + API JSON)
  Http/Requests/             FormRequests con autorización delegada al Policy

database/
  migrations/                8 migraciones del módulo + Spatie tables
  seeders/                   RolesAndPermissions, FaultsCatalog, DemoUsers, WorkflowSmokeTest

resources/views/
  livewire/disciplinary/     Vistas de los componentes Livewire
  components/disciplinary/   kpi-card, status-badge

docs/
  ARCHITECTURE.md            Documentación detallada de arquitectura
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
3. **Vistas** — `@can()` controla qué se renderiza.

## 📊 Endpoints

### Páginas Livewire (UI)

| Ruta | Descripción |
|---|---|
| `GET /disciplinary/dashboard` | Dashboard con KPIs y gráficas |
| `GET /disciplinary/cases` | Listado con filtros |
| `GET /disciplinary/cases/{case}` | Detalle del caso |

### API JSON (programática)

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/disciplinary/dashboard` | KPIs en JSON |
| `GET` | `/api/disciplinary/cases` | Listado con filtros |
| `POST` | `/api/disciplinary/cases` | Crear caso |
| `GET` | `/api/disciplinary/cases/{case}` | Detalle |
| `GET` | `/api/disciplinary/cases/{case}/transitions` | Transiciones permitidas |
| `POST` | `/api/disciplinary/cases/{case}/transition` | Aplicar transición |

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

## 🧭 Roadmap (siguientes fases)

- [ ] Wizard de creación de caso (formulario con autocompletado de personal)
- [ ] Subida de documentos desde la UI (el `DocumentService` ya está listo en backend)
- [ ] Notificaciones por email cuando un plazo está próximo a vencer
- [ ] Exportación PDF de actuaciones con plantillas FO-GJ
- [ ] Integración con SJ_Armory vía `personnel.external_id`
- [ ] Vista Kanban "Mi pipeline" por abogado
- [ ] Tests Pest reemplazando el `WorkflowSmokeTest`

## 📄 Licencia

Software interno de **SJ Seguridad**. Uso restringido.
