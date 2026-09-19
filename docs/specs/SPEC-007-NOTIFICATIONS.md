# SPEC-007 — Notificaciones internas

## Estado

IMPLEMENTADO

## Versión

1.0

## Objetivo

Avisar dentro de la aplicación web a los usuarios de una cuenta cuando ocurre un evento operativo relevante (alerta IoT nueva o asignación de trabajo), sin canales externos y sin alterar la generación de alertas ni el ciclo de incidencias.

## Actores

- **Sistema:** crea la notificación al persistir el evento de origen. No envía correo, SMS ni push.
- **Administrador:** consulta las notificaciones de su cuenta y las marca como leídas. En Home es el único destinatario posible.
- **Supervisor:** consulta las notificaciones de su cuenta (alertas nuevas) y las marca como leídas.
- **Especialista:** recibe notificaciones únicamente cuando se le asigna una alerta o una incidencia de su cuenta.

Visitantes no autenticados no tienen bandeja.

## Reglas de negocio

- **RN-001.** Las notificaciones son internas a la aplicación web. Correo, SMS, push, Slack, WhatsApp y WebSockets quedan **fuera de alcance** de esta versión.
- **RN-002.** Toda notificación pertenece a un `fish_farm_id` y a un `user_id` destinatario. Los datos no se mezclan entre cuentas.
- **RN-003.** El acceso a una notificación de otra cuenta o de otro usuario responde `404`, no `403`.
- **RN-004.** Este módulo no modifica SPEC-005 ni SPEC-006: no cambia la evaluación de umbrales, no borra alertas y no altera estados de incidencias.
- **RN-005.** Eventos de v0.1:
  - `alert_created` — se creó una `Alert` desde una lectura.
  - `alert_assigned` — un administrador o supervisor asignó la alerta a un especialista.
  - `incident_assigned` — un administrador o supervisor asignó la incidencia a un especialista.
- **RN-006.** Destinatarios de `alert_created`: administradores y supervisores de la misma `FishFarm`. En Home, el administrador único.
- **RN-007.** Destinatarios de `alert_assigned` e `incident_assigned`: solo el especialista indicado en `assigned_to` / `assigned_to_user_id`. Quien asigna no recibe copia de ese evento.
- **RN-008.** El especialista no recibe `alert_created`. Solo entra a la bandeja por asignación.
- **RN-009.** Una notificación no leída se marca con `read_at = null`. Marcarla como leída guarda `read_at` y no elimina el registro.
- **RN-010.** No hay preferencias por usuario, digest ni agrupación. Cada evento genera filas independientes por destinatario.
- **RN-011.** Si el evento de origen desapareciera de la UI, la notificación histórica se conserva. **Pendiente** definir retención.
- **RN-012.** Laravel `Notifiable` en `User` es infraestructura del framework, no este módulo. No se usa mailer ni canal database de Laravel hasta una versión posterior.

## Modelo conceptual

### Notification (propuesto)

Campos previstos:

| Campo | Uso |
| --- | --- |
| `fish_farm_id` | Tenant dueño |
| `user_id` | Destinatario |
| `type` | `alert_created` \| `alert_assigned` \| `incident_assigned` |
| `source_type` | `alert` \| `incident` |
| `source_id` | Identificador del origen |
| `title` | Título corto |
| `message` | Texto visible en bandeja |
| `read_at` | Nulo si no leída |

Relaciones previstas:

```
FishFarm 1 ── * Notification
User     1 ── * Notification
Alert    1 ── * Notification   (source_type = alert)
Incident 1 ── * Notification   (source_type = incident)
```

No forma parte de v0.1: plantillas, prioridad, canal, `broadcast`, adjuntos.

## Flujo funcional

```
Reading → Alert (SPEC-005)
    ↓  type = alert_created
NotificationService
    ↓
Filas para admin y supervisor de la cuenta

Alert asignada (AlertController)     Incident asignada (SPEC-006)
    ↓  type = alert_assigned              ↓  type = incident_assigned
NotificationService ──────────────────────┘
    ↓
Fila para el especialista asignado
    ↓
GET /notifications  (usuario autenticado)
    ↓
Lista de su cuenta y de su user_id
    ↓
POST /notifications/{notification}/read
    ↓
read_at = now()
```

Rutas web previstas (no implementadas):

- `GET /notifications`
- `POST /notifications/{notification}/read`

La bandeja se consultará en la petición HTTP autenticada (layout). No hay Echo, polling dedicado ni workers.

## Criterios de aceptación

- **CA-001.** Al crearse una alerta, existen notificaciones `alert_created` para el administrador y el supervisor de esa `FishFarm`.
- **CA-002.** El especialista de la misma cuenta no recibe `alert_created`.
- **CA-003.** Al asignar una incidencia, el especialista asignado recibe `incident_assigned`.
- **CA-004.** Al asignar una alerta, el especialista asignado recibe `alert_assigned`.
- **CA-005.** Un usuario de otra cuenta no ve esas notificaciones (`404` al abrirlas).
- **CA-006.** Un usuario no ve notificaciones dirigidas a otro usuario de su misma cuenta.
- **CA-007.** Marcar como leída persiste `read_at` y no borra la fila ni la alerta o incidencia de origen.
- **CA-008.** Crear notificaciones no cambia el `status` de la `Alert` ni de la `Incident`.
- **CA-009.** No se envía correo ni se escribe en un canal push.
- **CA-010.** En Home, `alert_created` llega al único administrador de la cuenta.

## Evidencia de implementación

### Código relacionado

- `app/Models/InternalNotification.php` — constantes de tipo/origen, scopes `unread()` y `latestFirst()`, resolución de enlace.
- `database/migrations/2026_09_19_150000_create_internal_notifications_table.php`
- `app/Services/InternalNotificationService.php` — destinatarios de `alert_created`, `alert_assigned` e `incident_assigned`.
- `app/Http/Controllers/InternalNotificationController.php` — bandeja y marcar como leída.
- `routes/web.php` — `GET /notifications`, `POST /notifications/{notification}/read`.
- `resources/views/notifications/index.blade.php`
- `resources/views/layouts/app.blade.php` — campana y contador del usuario autenticado.
- `app/Providers/AppServiceProvider.php` — View Composer del contador.
- Integraciones: `AlertEvaluationService::createAlert()`, `AlertController::assign()`, `IncidentController::assign()`.
- `app/Models/User.php` y `app/Models/FishFarm.php` — `internalNotifications()`. El trait `Notifiable` permanece y no se usa para este módulo.
- `database/seeders/E2ETestSeeder.php` — notificación determinista para Cypress.

### Tests relacionados

- `tests/Feature/NotificationGenerationTest.php`
- `tests/Feature/NotificationManagementTest.php`
- `cypress/e2e/notifications.cy.js`

### Estado actual

Implementado en v0.1. Commit de producto: `5d4d50cecdac113cb89e3469d989b538cb1f230d`.

Fuera de alcance actual: email, SMS, push, Slack, WhatsApp, WebSockets, colas asíncronas, preferencias, digest, agrupación, `read-all`, `delete`, SLA y escalamiento automático.
