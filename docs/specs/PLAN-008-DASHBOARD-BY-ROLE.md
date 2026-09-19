# PLAN-008 — Plan técnico de dashboard por rol

## Estado

PLANIFICADO

No implementado. Este documento no autoriza código, migraciones ni tests.

## Fuente de verdad

`docs/specs/SPEC-008-DASHBOARD-BY-ROLE.md` (commit `faa88b4293e8cf6d5e677e7a0b6b0a1e8a1c9b31`).

Estado de la SPEC: PROPUESTO · Versión 0.1.

Si una decisión técnica choca con la SPEC, manda SPEC-008.

## Objetivo del plan

Traducir SPEC-008 a servicio, vistas, límites de consulta y pruebas, sin requisitos nuevos.

Fuera de alcance (no planificar):

- modelo/tabla `Dashboard`;
- rutas `/dashboard/admin|supervisor|specialist`;
- cambios a `AlertEvaluationService`;
- nuevos estados o severidades de alerta;
- pagos, upgrades, IA, actuadores;
- `read-all`, polling, WebSockets, push;
- Debugbar u otras dependencias npm/composer solo para medir queries.

---

## 1. Arquitectura

| Pieza | Responsabilidad |
| --- | --- |
| `DashboardController` | Autenticación ya aplicada; llama al servicio; devuelve `view('dashboard', $data)`. |
| `DashboardDataService` | Calcula contexto y datasets. Sin escrituras. |
| Blade | Representa. Sin Eloquent. |

Una sola ruta: `GET /dashboard` (`dashboard`). Un controller. Sin cuatro controllers.

Firma prevista (equivalente Laravel aceptable):

```php
public function index(Request $request, DashboardDataService $dashboard): View
{
    return view('dashboard', $dashboard->forUser($request->user()));
}
```

---

## 2. DashboardDataService

Archivo futuro: `app/Services/DashboardDataService.php`

```
forUser(User $user): array
```

Interno:

```
contextFor(User $user): string   // home | farm_admin | farm_supervisor | farm_specialist
forHome(User $user): array
forFarmAdmin(User $user): array
forFarmSupervisor(User $user): array
forFarmSpecialist(User $user): array
```

Regla (RN-DASH-001 / 002):

1. `$user->fishFarm` (tenant).
2. Si `isHome()` → `home` (ignorar rol para el partial).
3. Si Farm → `admin` / `supervisor` / `specialist`.

Todo array incluye al menos:

- `dashboardContext`
- `account` (`FishFarm`)
- claves propias del contexto (nullable / colecciones vacías, nunca null sin empty state)

`forUser` **no** persiste ni llama `update()`/`create()`.

---

## 3. Tenancy

Punto de partida: `$user->fishFarm` y `fish_farm_id`.

Reutilizar el patrón actual: `pondIds` = ids de `$fishFarm->ponds()`.

Alertas: `Alert::whereIn('pond_id', $pondIds)` (nunca `Alert::count()` global).

Incidencias: `Incident::where('fish_farm_id', $fishFarm->id)` (nunca `Incident::count()` global).

Dispositivos y lecturas: a través de esos `pondIds`.

Usuarios (solo admin): `$fishFarm->users()->count()`.

Especialista: además `assigned_to = $user->id`.

No filtrar solo por `pond_id` / `incident.id` de otra cuenta.

---

## 4. Vistas

Shell: `resources/views/dashboard.blade.php`

Partials:

- `resources/views/dashboard/home.blade.php`
- `resources/views/dashboard/farm-admin.blade.php`
- `resources/views/dashboard/farm-supervisor.blade.php`
- `resources/views/dashboard/farm-specialist.blade.php`

El shell hace `@include` según `dashboardContext` ya calculado. Blade no consulta BD ni decide el contexto.

Reutilizar: `x-page-header`, `x-empty-state`, `x-status-badge`, tarjetas `surface-card` existentes. Nuevos componentes solo si hay repetición real.

Responsive: 1 columna móvil; 2 en tablet cuando aplique; grid de escritorio. Sin pixel-perfect.

`data-cy` previstos: `dashboard-context`, métricas, `mis-incidencias`, empty states.

### Prioridad visual (SPEC)

| Contexto | Pregunta | Orden |
| --- | --- | --- |
| HOME | ¿Cómo está mi pecera? | estado hídrico → métricas → alertas → dispositivo/`last_seen_at` → historial → plan |
| Admin | ¿Cómo está mi operación? | alertas/incidencias → unidades/dispositivos → lecturas recientes → uso/usuarios/plan |
| Supervisor | ¿Qué requiere atención? | alertas `active` → incidencias open/assigned/in_progress → estanques con alerta → lecturas → notificaciones |
| Especialista | ¿Qué tengo que atender? | assigned → in_progress → enlaces → resolved recientes → notificaciones |

HOME: el plan **no** va encima del estado hídrico. Admin: no es panel comercial (sin upgrade). Supervisor/especialista: **no** mostrar plan/uso (SPEC no lo define como bloque suyo).

---

## 5. Límites de listas

Constantes en `DashboardDataService` (un solo lugar):

```
RECENT_READINGS_LIMIT = 10
RECENT_ALERTS_LIMIT = 10
RECENT_INCIDENTS_LIMIT = 10
PONDS_SUMMARY_LIMIT = 12
```

`COUNT()` para agregados. `limit()` + `latest()` para tablas. No cargar todos los `readings` para pintar 10 filas.

El 10 replica el tope actual del controller; no es un KPI ISO.

---

## 6. Dataset HOME

Desde `FishFarm` Home. Pecera: primera/única de `ponds()` si existe (`pond` nullable).

| Clave | Origen | Null-safe |
| --- | --- | --- |
| `account` | FishFarm | no |
| `plan` / `usage` | `subscription.plan`, `SubscriptionLimitService::usage` | plan nullable |
| `pond` | ponds de la cuenta | sí |
| `device` | primer dispositivo del pond, o null | sí |
| `latestReading` | última `Reading` del pond | sí |
| `threshold` | `pond.threshold` | sí |
| `parameterStates` | array en memoria (no entidad) | ver abajo |
| `activeAlertCount` | COUNT alert `active` de pondIds | 0 |
| `activeAlerts` | lista `active` limitada | vacía |
| `historyUrl` | `ponds.readings.history` si hay pond | sí |

`parameterStates` (no persistir): para `temperature`, `ph`, `turbidity`, `water_level`:

- sin `latestReading` o sin umbral aplicable → `unevaluated`;
- dentro de min/max existentes → `ok`;
- fuera → `out_of_range`.

Misma lógica que SPEC-005 (p. ej. turbidez solo máximo). No clonar `AlertEvaluationService`; solo lectura de umbrales.

Empty states: sin pecera, sin device, sin reading, sin threshold (no evaluable, no 500), sin alertas.

---

## 7. Dataset Farm admin

| Clave | Definición |
| --- | --- |
| `pondCount` | COUNT ponds del tenant |
| `deviceCount` | COUNT devices de pondIds |
| `userCount` | COUNT users del FishFarm |
| `readingCount` | COUNT readings de pondIds |
| `activeAlertCount` | COUNT alert `active` |
| `pendingIncidentCount` | COUNT incident `status IN (open, assigned, in_progress)` del `fish_farm_id` |
| `plan` / `usage` | existentes |
| `ponds` | resumen limitado |
| `latestReadings` | límite 10, `with(['pond','device'])` |
| `recentActiveAlerts` | `active`, límite 10, `with('pond')` |

Accesos: rutas `ponds.index`, `incidents.index`, `users.index`, `notifications.index` (permisos actuales: usuarios solo admin).

---

## 8. Dataset Farm supervisor

Incidencias y alertas de **toda** la `FishFarm`, no las creadas por él.

| Clave | Definición |
| --- | --- |
| `activeAlertCount` | `active` |
| `assignedAlertCount` | `assigned` |
| `openIncidentCount` | `open` |
| `assignedIncidentCount` | `assigned` |
| `inProgressIncidentCount` | `in_progress` |
| `pondsWithActiveAlertsCount` | COUNT distinct `pond_id` con alerta `active` en pondIds |
| `latestRelevantReadings` | límite 10, eager load |
| `activeAlerts` | recientes `active` |
| `operationalIncidents` | recientes `open\|assigned\|in_progress`, límite 10, `with(['alert.pond','assignee'])` |

Sin bloque de gestión de usuarios. Sin plan/uso como protagonista (omitir).

---

## 9. Dataset Farm specialist

Solo trabajo propio: `assigned_to = $user->id` y `fish_farm_id` del usuario.

| Clave | Definición |
| --- | --- |
| `assignedIncidentCount` | COUNT `assigned` |
| `inProgressIncidentCount` | COUNT `in_progress` |
| `myActiveIncidents` | `status IN (assigned, in_progress)`, eager `alert.pond` |
| `recentResolvedIncidents` | `status = resolved`, límite 10 |
| `relatedPonds` | ponds distintos derivados de esas incidencias (`alert.pond`); enlaces `ponds.show` |

No incluir `open` sin asignar en «Mis incidencias». No listar incidencias de otro especialista. No presentar alertas generales de la granja como trabajo asignado.

---

## 10. Notificaciones (SPEC-007)

El layout ya inyecta `unreadNotificationsCount` (View Composer: `user_id` + `unread()`).

**Reutilizar esa variable** en los partials. `DashboardDataService` **no** vuelve a contar no leídas.

Si en un test Feature se renderiza `dashboard` con layout, el composer cubre CA-DASH-010. No cambiar el composer ni SPEC-007.

---

## 11. Empty states (sin 500 / null access)

HOME: sin pecera, sin device, sin readings.  
Farm: sin ponds.  
Todos: sin alerts, sin incidents, especialista sin asignaciones, sin notifications.

Blade: `optional` / `@if` / colecciones vacías. No `count(null)` ni `->name` sobre null.

---

## 12. Eficiencia (ISO/IEC 25010)

- `with()` en lecturas, alertas, incidencias.
- `count()` / `count(distinct)` para métricas; no `get()->count()`.
- Reutilizar `$pondIds`.
- Un dataset por request; no repetir la misma COUNT por tarjeta.

Medición en implementación (sin Debugbar, sin dependencia nueva):

1. En un test Feature, `DB::listen` / `DB::connection()->getQueryLog()` alrededor de `GET /dashboard` por contexto.
2. Registrar **línea base real** (número de queries y tiempo local aproximado) en evidencia posterior. No fijar ms ni máximo ISO ahora.
3. Un test anti-N+1: N incidencias eager-loaded no debe crecer linealmente en queries de `alert.pond`.

---

## 13. Seguridad (ISO/IEC 27001, referencia)

No se afirma cumplimiento 27001.

| Riesgo SPEC | Control en el servicio | Prueba |
| --- | --- | --- |
| R-DASH-SEC-001 | Solo tenant de `$user->fishFarm` | Farm A vs Farm B: contadores de A ignoran B |
| R-DASH-SEC-002 | `assigned_to = $user->id` en especialista | Specialist A no ve incident de B en «Mis incidencias» |

Autorización de rutas de destino no se relaja (RN-DASH-010, SPEC-006).

---

## 14. TDD futuro

`tests/Feature/DashboardByRoleTest.php` (RED → GREEN en la tarea de implementación, no ahora).

1. `home_dashboard_shows_home_context`
2. `home_dashboard_handles_missing_pond`
3. `home_dashboard_handles_missing_reading`
4. `home_dashboard_does_not_show_farm_team_blocks`
5. `farm_admin_dashboard_shows_own_farm_aggregates`
6. `farm_admin_dashboard_ignores_other_farm_data`
7. `farm_supervisor_dashboard_shows_operational_metrics`
8. `farm_supervisor_dashboard_ignores_other_farm_data`
9. `farm_specialist_dashboard_shows_only_assigned_work`
10. `farm_specialist_does_not_include_other_specialists_incidents`
11. `dashboard_handles_zero_alerts`
12. `dashboard_handles_zero_incidents`
13. `dashboard_unread_count_matches_authenticated_user`
14. `dashboard_request_does_not_change_domain_state`
15. `dashboard_lists_are_limited`

Más: instrumentación de queries (línea base), no umbral de milisegundos como CA.

Asserts útiles: `assertSee`/`assertDontSee` de bloques; `dashboardContext`; `assertDatabaseHas` sin cambios de status; `assertOk` en vacíos.

---

## 15. Cypress

Futuro: `cypress/e2e/dashboard-by-role.cy.js`. Seeder E2E existente (Farm). Home: usuario Home en seeder o alta de prueba, sin secretos.

- HOME: login → `data-cy` contexto home.
- Admin: indicadores globales.
- Supervisor: bloques operativos.
- Especialista: «Mis incidencias»; no el título de la incidencia del otro.
- Un viewport móvil estructural (p. ej. 375×667), sin comparar pixels.

Dusk: no obligatorio. Regresión completa; test Dusk nuevo solo si cubre algo distinto.

---

## 16. Orden de implementación futura

1. Tests Feature RED.
2. `DashboardDataService` + adelgazar `DashboardController`.
3. Shell + four partials + empty states + `data-cy`.
4. Cypress.
5. Medir queries; anotar línea base.
6. `npm run build` → `php artisan test` → `npm run cy:run` → `php artisan dusk` (serie).
7. Commit de producto.
8. Actualizar `docs/quality/QUALITY-002-ISO25010-TRACEABILITY-MATRIX.md` con código, tests, interacción, tenancy y mediciones **reales**.
9. Cerrar SPEC-008: IMPLEMENTADO 1.0, evidencia y hash. **No cambiar RN/CA.**

---

## 17. Trazabilidad SPEC → plan

| SPEC | Plan |
| --- | --- |
| RN-DASH-001/002 | `contextFor` |
| RN-DASH-003–005 | datasets admin / supervisor / specialist |
| RN-DASH-006 | pondIds + `fish_farm_id` |
| RN-DASH-007 / CA-011 | servicio de solo lectura |
| RN-DASH-008 / CA-007–009 | empty states |
| RN-DASH-009 | constantes de límite |
| RN-DASH-010 / CA-013 | sin nuevas rutas ni permisos |
| CA-DASH-010 | View Composer SPEC-007 |
| CA-DASH-012 | grid responsive |
| CA-DASH-014 | HOME sin bloques Farm |
| R-DASH-SEC-001/002 | tests 6, 8, 10 |
