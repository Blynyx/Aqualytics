# PLAN-007 — Plan técnico de notificaciones internas

## Estado

PLANIFICADO

No implementado. Este documento no autoriza código, migraciones ni tests.

## Fuente de verdad

`docs/specs/SPEC-007-NOTIFICATIONS.md` (commit `d6369402f640bbd162d509f4ec5b691ea6bf2638`).

Estado de la SPEC: PROPUESTO · Versión 0.1.

Si una decisión técnica choca con la SPEC, manda la SPEC.

## Objetivo del plan

Traducir SPEC-007 v0.1 a archivos, puntos de integración y pruebas, sin añadir requisitos.

Fuera de v0.1 (no planificar implementación):

`incident_resolved`, `incident_closed`, email, SMS, WhatsApp, push, WebSockets, colas, preferencias, digest, agrupación, retención automática, `read-all`, `delete`.

---

## 1. Decisión de persistencia

Módulo propio. No usar `Illuminate\Notifications` ni la tabla estándar `notifications`.

| Pieza | Valor |
| --- | --- |
| Modelo | `App\Models\InternalNotification` |
| Tabla | `internal_notifications` |

Motivo (RN-012): `User` ya usa el trait `Notifiable` del framework. Ese mecanismo no es SPEC-007. Una tabla `internal_notifications` evita colisión futura con `notifications` de Laravel y deja el mailer/canal database sin usar.

El nombre conceptual de la SPEC (`Notification`) se mapea a `InternalNotification` solo en código. El contrato funcional no cambia.

No persistir: tokens, contraseñas, `APP_KEY`, `X-Device-Token` ni credenciales.

---

## 2. Esquema previsto

Migración futura (no crear ahora): `create_internal_notifications_table`.

```
internal_notifications
  id
  fish_farm_id      FK → fish_farms
  user_id           FK → users
  type              string
  source_type       string
  source_id         unsignedBigInteger
  title             string
  message           text
  read_at           timestamp nullable
  created_at
  updated_at
```

`type` permitido:

- `alert_created`
- `alert_assigned`
- `incident_assigned`

`source_type` permitido:

- `alert`
- `incident`

Casts previstos: `read_at` → datetime.

Constantes en el modelo:

- `TYPE_ALERT_CREATED`, `TYPE_ALERT_ASSIGNED`, `TYPE_INCIDENT_ASSIGNED`
- `SOURCE_ALERT`, `SOURCE_INCIDENT`

### Índices

Sin sobreoptimizar:

- `fish_farm_id` (FK)
- `user_id` (FK)
- compuesto `user_id + read_at + created_at` — bandeja y contador de no leídas del usuario autenticado

No indexar `type` ni `source_*` en v0.1.

### Relación con el origen (RN-011)

No crear FK de `source_type` / `source_id` hacia `alerts` o `incidents`.

La fila histórica se conserva aunque el origen deje de resolverse en la UI. Retención automática: pendiente (SPEC RN-011). No implementar purge.

---

## 3. Relaciones Eloquent previstas

`FishFarm`:

```php
public function internalNotifications(): HasMany
{
    return $this->hasMany(InternalNotification::class);
}
```

`User`:

```php
public function internalNotifications(): HasMany
{
    return $this->hasMany(InternalNotification::class);
}
```

`InternalNotification`:

```php
public function fishFarm(): BelongsTo
public function user(): BelongsTo
```

Scopes útiles:

- `unread()` → `whereNull('read_at')`
- `latestFirst()` → `latest()` / `orderByDesc('created_at')`

No definir `morphTo` rígido sobre el origen. Resolver `Alert` o `Incident` en lectura de UI cuando haga falta.

Las relaciones conceptuales SPEC `Alert 1 ── * Notification` e `Incident 1 ── * Notification` se cubren con `source_type` + `source_id`, no con FK.

---

## 4. Servicio de dominio

Archivo futuro: `app/Services/InternalNotificationService.php`

El servicio decide destinatarios. Los controllers no duplican esa lógica.

```
notifyAlertCreated(Alert $alert): void
notifyAlertAssigned(Alert $alert, User $specialist): void
notifyIncidentAssigned(Incident $incident, User $specialist): void
```

### Destinatarios (RN-006, RN-007, RN-008)

| Método | type | source | Destinatarios |
| --- | --- | --- | --- |
| `notifyAlertCreated` | `alert_created` | `alert` / `$alert->id` | Admin y supervisor de `$alert->pond->fish_farm_id`. Home: el administrador único. Especialistas: ninguno. |
| `notifyAlertAssigned` | `alert_assigned` | `alert` / `$alert->id` | Solo `$specialist`. Quien asigna: ninguna copia. |
| `notifyIncidentAssigned` | `incident_assigned` | `incident` / `$incident->id` | Solo `$specialist`. Quien asigna: ninguna copia. |

Cada destinatario genera una fila independiente (RN-010).

### Textos (sin datos sensibles)

Construir `title` y `message` en el servicio. Usar `Pond::name` / `Incident::title` y el `message` o parámetro de la alerta. No interpolar tokens, emails ni secretos.

Ejemplos (SPEC):

- `alert_created` — título: «Nueva alerta hídrica». Mensaje: «Temperatura por encima del rango configurado en Estanque Norte.»
- `alert_assigned` — título: «Alerta asignada». Mensaje: «Se te asignó una alerta de temperatura en Estanque Norte.»
- `incident_assigned` — título: «Nueva incidencia asignada». Mensaje: «Se te asignó la incidencia: Revisar temperatura del estanque.»

Etiqueta de unidad: `FishFarm::unitLabel()` / nombre del estanque o pecera.

### Reasignaciones

RN-010: cada evento genera una fila independiente.

Cada `assign()` exitoso puede crear una notificación nueva, aunque el especialista sea el mismo o cambie.

v0.1: sin deduplicación.

---

## 5. Integraciones (únicos tres puntos)

No enganchar `IncidentController::resolve`, `close`, `start` ni `AlertController::resolve`.

### Integración 1 — `alert_created`

Archivo: `app/Services/AlertEvaluationService.php`

Hoy `createAlert()` persiste la alerta y descarta la instancia.

Plan:

1. Persistir la `Alert` exactamente como hoy (mismos campos, `status = active`).
2. Capturar la instancia (`$alert = $reading->alerts()->create([...])`).
3. Llamar `InternalNotificationService::notifyAlertCreated($alert)`.
4. No tocar comparación de umbrales.
5. No cambiar `status` ni el resto de atributos de la alerta.

Inyectar el servicio por constructor. Mantener `evaluate(Reading $reading): void`.

El test de generación debe pasar por el flujo real:

```
Reading → ReadingIngestionService → AlertEvaluationService → Alert → InternalNotification
```

No invocar solo `notifyAlertCreated()` para demostrar esa integración.

### Integración 2 — `alert_assigned`

Archivo: `app/Http/Controllers/AlertController.php` · `assign()`

Hoy: tenancy 404, rol admin/supervisor 403, especialista de la misma `FishFarm`, luego `assigned_to_user_id`, `reported_by_user_id`, `assigned_at`, `status = assigned`.

Después del `update()` exitoso y antes del redirect:

```
InternalNotificationService::notifyAlertAssigned($alert, $specialist)
```

`$specialist` = usuario validado por `specialist_id`. No cambiar permisos ni el redirect a `/ponds/{pond}`.

### Integración 3 — `incident_assigned`

Archivo: `app/Http/Controllers/IncidentController.php` · `assign()`

Hoy: `ensureSameFarm`, `ensureCanManage`, valida especialista, luego `assigned_to` y `status = assigned`.

Después del `update()` exitoso:

```
InternalNotificationService::notifyIncidentAssigned($incident, $specialist)
```

Solo el especialista asignado. Permisos y redirect a `incidents.show` sin cambios.

---

## 6. Controller y rutas

Archivo futuro: `app/Http/Controllers/InternalNotificationController.php`

```
index(Request $request): View
markAsRead(Request $request, InternalNotification $notification): RedirectResponse
```

En el grupo `middleware('auth')` de `routes/web.php`, junto a incidencias:

| Método | Ruta | Nombre |
| --- | --- | --- |
| GET | `/notifications` | `notifications.index` |
| POST | `/notifications/{notification}/read` | `notifications.read` |

No añadir `read-all`, `delete` ni `preferences`.

`index`:

- `user_id = auth()->id()`
- `fish_farm_id = auth()->user()->fish_farm_id`
- `latestFirst()`
- paginación simple (p. ej. 15)
- vista `notifications.index`

### Tenancy y destinatario (RN-002, RN-003)

`markAsRead` (y cualquier show implícito por model binding) exige:

```
$notification->fish_farm_id === $request->user()->fish_farm_id
$notification->user_id === $request->user()->id
```

Si cualquiera falla: `404`, nunca `403`.

Éxito: `read_at = now()`. No borrar la fila ni el origen. Redirect a `notifications.index`.

---

## 7. Bandeja y layout

### Vista

`resources/views/notifications/index.blade.php`

Mostrar, más recientes primero:

- título
- mensaje
- fecha
- leído / no leído (`read_at`)
- tipo
- enlace al origen si se resuelve
- acción «Marcar como leída» (form POST a `notifications.read`) si `read_at` es nulo

Sin preferencias, filtros avanzados ni borrado.

### Enlaces de origen

No guardar URLs. Resolver en lectura:

| source_type | Resolución | Destino |
| --- | --- | --- |
| `alert` | Cargar `Alert` y comprobar `pond.fish_farm_id` = cuenta del usuario | `GET /ponds/{pond}` |
| `incident` | Cargar `Incident` y aplicar la visibilidad ya usada en `IncidentController::ensureVisible` | `incidents.show` |

Si no se resuelve o el usuario no puede verlo: mostrar la fila histórica **sin enlace**.

### Campana

Actualizar `resources/views/layouts/app.blade.php`.

- Enlace «Notificaciones» a `notifications.index` (`data-cy` para Cypress).
- Contador solo de no leídas del usuario autenticado: `user_id = auth()->id()` y `read_at IS NULL`.
- No contar notificaciones de toda la `FishFarm`.

Evitar consultas en Blade. Preferencia:

1. Relación `User::internalNotifications()` + `unread()->count()` en un View Composer del layout autenticado, o
2. View Composer que inyecte `unreadNotificationsCount`.

No WebSockets ni polling. El contador se recalcula en cada petición HTTP autenticada.

---

## 8. TDD

Orden: pruebas RED → implementación GREEN. No crear tests en esta tarea.

### `tests/Feature/NotificationGenerationTest.php`

Generación vía flujo real de lecturas / asignación HTTP.

1. `alert_created_notifies_farm_admin_and_supervisor`
2. `alert_created_does_not_notify_specialist`
3. `home_alert_created_notifies_admin`
4. `alert_assignment_notifies_only_assigned_specialist`
5. `incident_assignment_notifies_only_assigned_specialist`
6. `notification_generation_does_not_change_alert_status`
7. `notification_generation_does_not_change_incident_status`

Para 1–3 y 6: `POST /api/readings` autenticado con token de dispositivo (mismo patrón que `AlertGenerationTest`), no `notifyAlertCreated()` aislado.

Para 4: `POST /alerts/{alert}/assign`. Para 5: `POST /incidents/{incident}/assign`.

### `tests/Feature/NotificationManagementTest.php`

8. `notification_index_only_shows_authenticated_users_notifications`
9. `other_tenant_notification_returns_404`
10. `same_tenant_other_users_notification_returns_404`
11. `notification_can_be_marked_as_read`
12. `marking_read_does_not_delete_notification_or_source`
13. `related_alert_notification_links_to_pond`
14. `related_incident_notification_links_to_incident`

404 sobre `POST /notifications/{id}/read` (y GET index no debe listar filas ajenas). CA-005/CA-006.

Cubrir CA-007, CA-008, CA-010. CA-009: no afirmar envío de mail/push; no mockear mailer.

Reasignación: no test de deduplicación; si se cubre, debe aceptar una segunda fila.

---

## 9. Cypress y Dusk

### Cypress

Escenario pequeño futuro (p. ej. `cypress/e2e/notifications.cy.js`). Semilla E2E existente.

Camino A:

1. Login supervisor o admin.
2. Existe notificación (alerta activa del seeder o lectura del flujo).
3. Campana muestra contador.
4. Abrir `/notifications`.
5. Marcar como leída.
6. El contador disminuye.

Camino B (y/o):

1. Supervisor asigna incidencia.
2. Login especialista.
3. Ve `incident_assigned` y el contador.

No depender de reloj exacto ni pixel-perfect. Usar `data-cy`.

No planificar `read-all`.

### Dusk

No hay prueba Dusk nueva obligatoria. Tras implementar: regresión completa.

---

## 10. Regresión futura (después de GREEN)

En serie, no `npm run build` a la vez que PHPUnit:

```
npm run build
D:\xampp\php\php.exe artisan test
npm run cy:run
D:\xampp\php\php.exe artisan dusk
```

---

## 11. Actualización de SPEC (no en esta tarea)

Cuando la implementación futura esté GREEN:

- Estado: IMPLEMENTADO
- Versión: 1.0
- Evidencia: modelo, migración, servicio, controller, rutas, vistas, tests, commit

No editar `SPEC-007-NOTIFICATIONS.md` ahora.

---

## 12. Orden de implementación futura

1. Tests Feature RED.
2. Migración `internal_notifications` + modelo + relaciones/scopes.
3. `InternalNotificationService`.
4. Integración 1 en `AlertEvaluationService`.
5. Integraciones 2 y 3 en `AlertController::assign` e `IncidentController::assign`.
6. `InternalNotificationController` + rutas.
7. Vista de bandeja + resolución de enlaces.
8. Campana y contador en layout.
9. Cypress.
10. Regresión PHPUnit / Cypress / Dusk.
11. Actualizar SPEC-007 a IMPLEMENTADO 1.0.

---

## 13. Trazabilidad SPEC → plan

| SPEC | Plan |
| --- | --- |
| RN-001 interno, sin canales externos | Sin mailer, push, WebSockets, colas |
| RN-002 / RN-003 tenancy + destinatario | `fish_farm_id` + `user_id`; fallo → 404 |
| RN-004 no alterar alertas/incidencias | Solo hooks posteriores al persistir; tests de status |
| RN-005 tres eventos | Únicos `type` persistidos |
| RN-006 / RN-008 destinatarios `alert_created` | Servicio, no controllers |
| RN-007 asignación | Solo especialista; sin copia al asignador |
| RN-009 `read_at` | Nullable; markAsRead no borra |
| RN-010 sin digest/agrupación | Una fila por destinatario; reasignación = fila nueva |
| RN-011 histórico | Sin FK a origen; enlace opcional |
| RN-012 no `Notifiable` | Tabla `internal_notifications` |
| CA-001…CA-010 | Tests 1–14 |
