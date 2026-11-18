# Recepcion de empleados desde SJ StatFlow

## Objetivo

Mantener el catalogo de `employees` de SJ LegalSuite sincronizado con SJ StatFlow (sistema maestro de Gestion Humana), sin compartir base de datos ni exponer el servidor interno.

StatFlow **empuja** (push saliente por HTTPS) lotes de empleados. LegalSuite solo recibe, valida y hace upsert.

## Alcance

- Destino: tabla `employees` (upsert por `document_number`).
- Fuera de alcance: datos de emergencia, area/departamento, documentos, nomina.
- El receptor **nunca borra** filas, nunca hace `forceDelete` y nunca cambia `employees.id` (hay FKs de casos disciplinarios apuntando a ese id).

## Endpoints

| Metodo | Ruta | Auth | Proposito |
| --- | --- | --- | --- |
| `GET` | `/api/integraciones/ping` | publica | salud: `{ "ok": true, "ts": "..." }` |
| `POST` | `/api/integraciones/empleados` | firma HMAC | recibe un lote |

Ambos quedan fuera de `auth` y de CSRF (grupo `api`) y con `throttle:60,1`.

## Seguridad del transporte

Headers obligatorios:

| Header | Validacion |
| --- | --- |
| `X-Integration-Key` | se compara con `hash_equals` contra `INTEGRATION_STATFLOW_KEY` |
| `X-Timestamp` | unix en segundos; se rechaza si difiere mas de `INTEGRATION_TIMESTAMP_TOLERANCE` (300 s por defecto) |
| `X-Signature` | `hash_hmac('sha256', $bodyCrudo, INTEGRATION_STATFLOW_SECRET)` en hex |

Respuestas de error en JSON: `401` token invalido, `422` timestamp ausente/fuera de ventana o firma invalida, `503` integracion sin configurar en el servidor.

Middleware: `App\Http\Middleware\VerifyIntegrationSignature` (alias `integration.signature`).

Generar credenciales:

```bash
php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"
php -r "echo bin2hex(random_bytes(48)).PHP_EOL;"
```

El primer valor va en `INTEGRATION_STATFLOW_KEY` (LegalSuite) y en `INTEGRATION_LEGALSUITE_TOKEN` (StatFlow). El segundo, en `INTEGRATION_STATFLOW_SECRET` / `INTEGRATION_LEGALSUITE_SECRET`.

## Contrato del lote

```json
{
  "run_id": "uuid",
  "mode": "incremental",
  "batch": 1,
  "total_batches": 9,
  "sent_at": "2026-11-18T10:00:00-05:00",
  "records": [ { "id": 2, "document_number": "12910432", "...": "..." } ]
}
```

Campos por registro que se procesan (el resto se ignora): `id`, `document_number`, `document_type`, `first_name`, `last_name`, `gender`, `birth_date`, `address`, `residence_municipality_code`, `residence_department_code`, `municipality_code`, `work_department_code`, `phone`, `email`, `hired_at`, `contract_type`, `job_title`, `position_code`, `base_salary`, `is_active`, `termination_at`.

Respuesta:

```json
{
  "created": 1,
  "updated": 2,
  "skipped": 0,
  "errors": [ { "document_number": "123", "reason": "..." } ],
  "ok": true
}
```

`207` cuando algun item fallo. Un error por item no aborta el lote.

## Reglas de upsert

- Busca con `Employee::withTrashed()->where('document_number', ...)`.
- Si existe: `fill($data)` y `save()` **solo si `isDirty()`**. Asi se actualiza unicamente lo que difiere, sin columna de control.
- Si no existe: crea con `first_name`, `last_name`, `document_number` e `is_active`.
- `external_id` = `id` del payload (trazabilidad).
- Documento soft-deleted: se restaura solo si el payload trae `is_active = true`; si viene inactivo se deja como estaba.
- `phone`, `email` y `base_salary` vacios **no** se envian al modelo (no borran el valor existente). Las fechas si reflejan el origen.
- `termination_at` se ignora si el anio es `>= 9000` (StatFlow usa `9999-12-31` como centinela).
- `employment_status = activo` -> `is_active = true`; cualquier otro -> `false` (desactiva ~1.303 empleados en la primera carga, comportamiento esperado).

## Municipios (DIVIPOLA)

`residence_municipality_code` y `municipality_code` se validan contra `colombian_municipalities`. Si el codigo no existe se guarda `null` en el municipio y en su `*_department_code` (evita violar la FK). Si existe, el departamento se **deriva** de la tabla, ignorando el que venga en el payload.

StatFlow no tiene el catalogo DANE, por eso envia `*_department_code = null` y LegalSuite lo resuelve.

## Tipos de documento

`employees.document_type` es `varchar(8)`. Se mantienen los codigos historicos (`CC`, `CE`, `PA`, `PPT`) y se agregan los de StatFlow (`CEDULA`, `C`, `NIT`, `N`, `TI`, `PT`).

Los dos codigos largos de StatFlow no caben y se colapsan a `CE`:

| Codigo StatFlow | Destino |
| --- | --- |
| `CEDULA` | `CEDULA` |
| `C` | `C` |
| `CEDULA EXTRANJERIA` | `CE` |
| `TARJETA EXTRANJERIA` | `CE` |
| `CE` | `CE` |
| `NIT` | `NIT` |
| `N` | `N` |
| `TI` | `TI` |
| `PT` | `PT` |
| `null` o desconocido | `CEDULA` |

Mapeo en `EmployeeDocumentType::fromStatflowCode()`. Una futura ampliacion de la columna permitiria distinguir los dos casos largos sin cambiar el contrato.

## Catalogo de cargos

- `job_title` se guarda tal como llega (hasta 120 caracteres).
- El cargo se resuelve por nombre normalizado con `EmployeeJobPosition::normalizeName()`; si no existe se **crea** (nunca se borra ni renombra).
- Al crear, la clasificacion se deriva del nombre:
  - `is_guarda = true` si contiene `GUARDA`, `VIGILANTE`, `ESCOLTA`, `MOTORIZADO` o `CANINO`.
  - `employee_scope = 'operativo'` si es guarda o si contiene `SUPERVISOR`, `OPERADOR` o `PROGRAMADOR`; si no, `'administrativo'`.
- Si el cargo ya existe **no se sobrescribe** su `is_guarda` / `employee_scope` (se respeta la clasificacion manual hecha en Ajustes/Usuarios).
- `employees.employee_scope` se asigna con el scope del cargo solo cuando el empleado aun no tiene uno.

## Monitoreo

```bash
php artisan integracion:estado
```

Lee `storage/app/integraciones/last-run.json` (no se permiten tablas nuevas en este repo) y muestra el ultimo lote: fecha, `run_id`, modo, lote/total, creados, actualizados, sin cambios y errores. Ese archivo lo escribe el controlador en cada lote recibido.

Logs: `storage/logs/laravel.log` (errores del middleware y de la aplicacion).

## Pruebas

```bash
php artisan test --compact tests/Feature/Integration
```

Cubren: rechazo sin token, firma invalida, timestamp viejo, alta y actualizacion, idempotencia, `isDirty` (payload identico no genera UPDATE), id estable y sin borrado, municipio inexistente -> null, restauracion de soft-deleted, y creacion de cargos con clasificacion derivada.

## Troubleshooting

| Sintoma | Causa probable | Accion |
| --- | --- | --- |
| `503 Integracion no configurada` | faltan `INTEGRATION_STATFLOW_*` en el `.env` del servidor | configurar y `php artisan config:cache` |
| `401 Token de integracion invalido` | token distinto al de StatFlow | comparar ambos `.env` |
| `422 fuera de la ventana de tiempo` | reloj desfasado | sincronizar NTP en ambos servidores |
| `422 Firma HMAC invalida` | secret distinto | comparar `INTEGRATION_*_SECRET` |
| `integracion:estado` sin datos | aun no llego ningun lote | revisar conectividad y el comando en StatFlow |

## Activacion en Hostinger

1. Definir `INTEGRATION_STATFLOW_KEY`, `INTEGRATION_STATFLOW_SECRET` y `INTEGRATION_TIMESTAMP_TOLERANCE` en el `.env` de produccion.
2. `php artisan config:cache`.
3. Verificar `GET https://<dominio>/api/integraciones/ping`.
4. Confirmar que el certificado TLS es valido (StatFlow solo envia por HTTPS).