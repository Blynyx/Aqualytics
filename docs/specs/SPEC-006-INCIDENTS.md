# SPEC-006 — Gestión de incidencias

## Estado

IMPLEMENTADO

## Versión

1.0

## Objetivo

Convertir una alerta IoT en una incidencia gestionable: asignable, atendible y cerrable, sin alterar la generación automática de alertas ni borrar la alerta original.

## Actores

- **Administrador:** ve las incidencias de su cuenta, asigna especialistas y puede cerrar una incidencia ya resuelta.
- **Supervisor:** ve alertas, crea incidencias desde una alerta de su cuenta y asigna especialistas. No cierra.
- **Especialista:** solo ve las incidencias que tiene asignadas, inicia el trabajo y registra la solución (`resolved`).

## Reglas de negocio

- **RN-001.** Arquitectura: Reading → Alert → Incident → Resolution. La generación de Alert no cambia (SPEC-005).
- **RN-002.** Una alerta tiene como máximo una incidencia (`alert_id` único).
- **RN-003.** Crear una incidencia no elimina ni modifica el estado de la alerta original (permanece `active` al crearse el incidente).
- **RN-004.** Toda incidencia pertenece a un `fish_farm_id`. El acceso a otra cuenta es `404`.
- **RN-005.** El especialista no ve ni abre incidencias que no tenga en `assigned_to`.
- **RN-006.** Solo administrador y supervisor crean incidencias. El especialista recibe `403` si intenta crearlas.
- **RN-007.** Solo administrador y supervisor asignan especialistas de la misma cuenta.
- **RN-008.** Solo el especialista asignado puede pasar a `in_progress` y a `resolved`.
- **RN-009.** Resolver exige `resolution` (mínimo 10 caracteres) y guarda `resolved_at`.
- **RN-010.** Solo el administrador cierra (`closed`). El supervisor recibe `403`.
- **RN-011.** El flujo de asignar/resolver **Alert** en la ficha del estanque (`AlertController`) sigue existiendo en paralelo. No sustituye a este módulo.

## Modelo conceptual

### Incident

Campos implementados:

- `alert_id`
- `fish_farm_id`
- `created_by`
- `assigned_to`
- `title`
- `description`
- `status`
- `resolution`
- `resolved_at`

Estados:

| status | Significado |
| --- | --- |
| `open` | Creada, sin especialista |
| `assigned` | Especialista asignado |
| `in_progress` | El especialista inició el trabajo |
| `resolved` | Hay solución documentada |
| `closed` | El administrador confirmó el cierre |

Relaciones: `alert()`, `fishFarm()`, `creator()`, `assignee()`.

## Flujo funcional

```
Alert (generada automáticamente)
    ↓  POST /alerts/{alert}/incidents
Incident (status = open)
    ↓  POST /incidents/{incident}/assign
Incident (status = assigned)
    ↓  POST /incidents/{incident}/start
Incident (status = in_progress)
    ↓  POST /incidents/{incident}/resolve
Incident (status = resolved + resolution + resolved_at)
    ↓  POST /incidents/{incident}/close   (solo admin)
Incident (status = closed)
```

Rutas web autenticadas:

- `GET /incidents`
- `GET /incidents/{incident}`
- `POST /alerts/{alert}/incidents`
- `POST /incidents/{incident}/assign`
- `POST /incidents/{incident}/start`
- `POST /incidents/{incident}/resolve`
- `POST /incidents/{incident}/close`

## Criterios de aceptación

- **CA-001.** Un supervisor crea una incidencia desde una alerta y queda en `open`.
- **CA-002.** Tras crear la incidencia, la alerta original sigue existiendo y en `active`.
- **CA-003.** Un administrador ve las incidencias de su cuenta.
- **CA-004.** Un especialista solo ve las asignadas y recibe `404` al abrir otra.
- **CA-005.** No se puede acceder a una incidencia de otra `FishFarm`.
- **CA-006.** Una segunda incidencia sobre la misma alerta se rechaza.
- **CA-007.** El especialista asignado puede iniciar y resolver con texto de solución.
- **CA-008.** El administrador puede asignar un especialista y cerrar una incidencia resuelta.
- **CA-009.** El especialista no puede crear incidencias.
- **CA-010.** El supervisor no puede cerrar incidencias.

## Evidencia de implementación

### Código relacionado

- `app/Models/Incident.php`
- `app/Models/Alert.php` — `incident()`
- `app/Models/User.php` — `createdIncidents()`, `assignedIncidents()`
- `app/Models/FishFarm.php` — `incidents()`
- `app/Http/Controllers/IncidentController.php`
- `app/Http/Controllers/PondController.php` — carga `incident` en alertas activas.
- `routes/web.php`
- `resources/views/incidents/index.blade.php`
- `resources/views/incidents/show.blade.php`
- `resources/views/ponds/show.blade.php` — alta de incidencia desde alerta.
- `database/migrations/2026_09_18_230000_create_incidents_table.php`

### Tests relacionados

- `tests/Feature/IncidentManagementTest.php`

### Estado actual

Implementado. No hay SLA, escalamiento automático, notificaciones push ni eliminación de alertas al resolver. Esos puntos están **fuera de alcance actual**.
