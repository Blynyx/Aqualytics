# SPEC-008 — Dashboard operativo por rol

## Estado

IMPLEMENTADO

## Versión

1.0

## Objetivo

Definir un dashboard contextual para cada tipo de cuenta y rol, priorizando la información operativa relevante para el usuario autenticado.

El dashboard **no crea** procesos de negocio nuevos. Reutiliza entidades existentes: `FishFarm`, `Pond`, `Device`, `Reading`, `Alert`, `Incident`, `InternalNotification`, `Subscription` y `Plan`.

Mantiene la ruta `GET /dashboard`. La contextualización ocurre en el flujo actual, no en rutas `/dashboard/admin`, `/dashboard/supervisor` ni `/dashboard/specialist`.

## Actores

### HOME

- **Administrador / propietario:** único usuario previsto por el plan Home. Consulta el estado de su pecera.

### FARM

- **Administrador:** visión global de la cuenta (SPEC-001).
- **Supervisor:** operación y seguimiento de alertas e incidencias de su `FishFarm` (SPEC-005, SPEC-006).
- **Especialista:** trabajo asignado a él (SPEC-006, SPEC-007).

No se crean tipos de cuenta ni roles adicionales.

## Contexto actual

`GET /dashboard` permanece como única ruta. `DashboardController` delega en `DashboardDataService::forUser`. El shell Blade selecciona el partial según `dashboardContext` (`home`, `farm_admin`, `farm_supervisor`, `farm_specialist`).

HOME ignora el rol almacenado. Farm especializa agregados por rol. Permisos backend de SPEC-001 a SPEC-007 no se relajan. No hay entidad `Dashboard` ni rutas `/dashboard/admin|supervisor|specialist`.

## Reglas de negocio

- **RN-DASH-001.** El dashboard se determina primero por `account_type` y, si es Farm, por `role`.
- **RN-DASH-002.** Una cuenta HOME recibe exclusivamente el dashboard HOME.
- **RN-DASH-003.** El administrador Farm recibe información agregada solo de su `FishFarm`.
- **RN-DASH-004.** El supervisor Farm recibe información operativa de su `FishFarm`.
- **RN-DASH-005.** El especialista Farm prioriza únicamente incidencias con `assigned_to` igual a su `user_id`.
- **RN-DASH-006.** Ninguna métrica mezcla datos de `FishFarm` distintas. Todo indicador se calcula con `auth()->user()->fish_farm_id`. No basta filtrar por id de recurso sin validar el tenant.
- **RN-DASH-007.** Abrir o refrescar el dashboard no cambia estados de `Alert`, `Incident`, `Device`, `Reading` ni `InternalNotification`.
- **RN-DASH-008.** La ausencia de datos se muestra con estados vacíos, no con excepciones.
- **RN-DASH-009.** Las listas recientes están acotadas. El historial completo permanece en SPEC-004 (`GET /ponds/{pond}/readings/history`).
- **RN-DASH-010.** La visibilidad del dashboard no sustituye las autorizaciones backend (roles, tenancy, `404`/`403`).
- **RN-DASH-011.** SPEC-008 no crea roles, estados ni entidades nuevas.
- **RN-DASH-012.** Planes, pagos y upgrades siguen **fuera de alcance** (SPEC-002: sin pasarela de pago). El dashboard solo muestra plan y cupos ya existentes.

## Modelo conceptual

No existe entidad `Dashboard`. Es una vista de lectura agregada.

```
Authenticated User
       ↓
FishFarm
       ↓
account_type
       ↓
HOME → Dashboard HOME

FARM → role
        ┌─────────┼──────────┐
      admin   supervisor  specialist
        ↓         ↓            ↓
     global    operación    trabajo propio
```

Estados de incidencia (SPEC-006, sin ampliar): `open`, `assigned`, `in_progress`, `resolved`, `closed`.

Estados de alerta usados hoy (SPEC-005, sin ampliar): `active`, `assigned`, `resolved`. No se introducen `critical`, `emergency` ni `high-risk`. No se redefine `AlertEvaluationService`.

## Definición de métricas (estados exactos)

| Indicador | Qué cuenta |
| --- | --- |
| Alertas activas | `Alert.status = active` de la `FishFarm` (vía estanques de la cuenta) |
| Alertas asignadas | `Alert.status = assigned` de la `FishFarm` |
| Incidencias abiertas | `Incident.status = open` de la `FishFarm` |
| Incidencias asignadas (cuenta) | `Incident.status = assigned` de la `FishFarm` |
| Incidencias en progreso (cuenta) | `Incident.status = in_progress` de la `FishFarm` |
| Trabajo pendiente de incidencias (cuenta) | `open` + `assigned` + `in_progress` |
| Mis incidencias (especialista) | `assigned_to = usuario` y `status` ∈ {`assigned`, `in_progress`} |
| Mis incidencias resueltas recientes (especialista) | `assigned_to = usuario` y `status = resolved`, lista acotada |
| Notificaciones no leídas | `InternalNotification.user_id = auth()->id()` y `read_at IS NULL` (SPEC-007). No se cuenta la `FishFarm` completa. |

`resolved` y `closed` no entran en “trabajo pendiente”.

## Dashboard HOME

Objetivo: estado actual de la pecera.

Priorizar:

- nombre de la pecera (`Pond`);
- estado del dispositivo (`Device.status`);
- última conexión (`Device.last_seen_at`);
- última lectura (`Reading`: temperatura, pH, turbidez, nivel);
- comparación con `PondThreshold` si existe (dentro / fuera de rango, usando umbrales ya persistidos);
- alertas `active`;
- contador de notificaciones no leídas (SPEC-007);
- enlace al historial gráfico de esa unidad (SPEC-004);
- plan y uso (SPEC-002).

No mostrar:

- bloques de equipo Farm (usuarios, incidencias de varios especialistas);
- gestión de usuarios como elemento operativo;
- recomendaciones de IA;
- control de actuadores (QUALITY-001: sin actuación física).

Si no hay pecera, lecturas, alertas o notificaciones: empty state (RN-DASH-008).

## Dashboard Farm — Administrador

Visión global de su `FishFarm`:

- número de estanques;
- dispositivos registrados;
- usuarios de la cuenta;
- lecturas registradas;
- alertas `active`;
- incidencias pendientes (`open` + `assigned` + `in_progress`);
- notificaciones no leídas del administrador;
- uso del plan;
- estado general de estanques (listado acotado o resumen de la cuenta);
- últimas lecturas (tope, p. ej. el límite 10 ya usado);
- alertas recientes `active` (mismo tope acotado).

Accesos (rutas existentes, sin nuevos permisos): estanques, incidencias, usuarios, notificaciones.

## Dashboard Farm — Supervisor

Prioridad: operación y seguimiento de su `FishFarm`.

Mostrar:

- alertas `active`;
- alertas `assigned`;
- incidencias `open`;
- incidencias `assigned`;
- incidencias `in_progress`;
- unidades que tienen al menos una alerta `active`;
- últimas lecturas acotadas;
- notificaciones no leídas del supervisor.

Navegación a estanque, ficha de estanque (alerta), incidencia y notificaciones, según reglas actuales de SPEC-005/006/007.

No presentar gestión de usuarios como función principal del dashboard. No cambiar permisos de asignar/crear incidencias.

## Dashboard Farm — Especialista

Prioridad: su trabajo.

Mostrar:

- incidencias `assigned` donde `assigned_to` es él;
- incidencias `in_progress` donde `assigned_to` es él;
- incidencias `resolved` recientes donde `assigned_to` es él (lista acotada);
- notificaciones no leídas suyas (SPEC-007);
- acceso a unidades ligadas a **esas** incidencias (a través de la alerta/estanque de la incidencia).

El bloque «Mis incidencias» no incluye incidencias de otros especialistas.

Las alertas generales de la granja **no** se presentan como trabajo asignado al especialista. SPEC-008 no restringe `GET /ponds` ni otras lecturas ya permitidas: solo define la prioridad del dashboard (RN-DASH-010).

## Lecturas

El dashboard no carga historiales 24h/7d/30d/90d. Solo un conjunto acotado de lecturas recientes para resúmenes. El detalle sigue en SPEC-004.

## Notificaciones

Reutilizar el contador de SPEC-007 (usuario autenticado, `read_at` nulo). Sin `read-all`, polling, WebSockets ni push.

## Flujo funcional

```
GET /dashboard
      ↓
Usuario autenticado
      ↓
FishFarm (fish_farm_id)
      ↓
¿account_type = home?
 ├─ Sí → Dashboard HOME
 └─ No → FARM
          ↓
         role
   ┌──────┼──────────┐
 admin supervisor specialist
   ↓        ↓           ↓
global   operación   trabajo propio
```

Ruta existente: `GET /dashboard` (`dashboard`). Sin rutas nuevas.

## Criterios de aceptación

- **CA-DASH-001.** Cuenta HOME: ve información de su pecera y no bloques de gestión Farm (usuarios, trabajo de equipo).
- **CA-DASH-002.** Admin Farm: indicadores globales solo de su `FishFarm`.
- **CA-DASH-003.** Supervisor Farm: ve alertas e incidencias operativas de su `FishFarm`.
- **CA-DASH-004.** Especialista Farm: el trabajo prioritario son sus incidencias asignadas.
- **CA-DASH-005.** Una incidencia de otro especialista no aparece en «Mis incidencias».
- **CA-DASH-006.** Datos de otra `FishFarm` no alteran ningún contador.
- **CA-DASH-007.** Cuenta sin readings: empty state válido, sin excepción.
- **CA-DASH-008.** Cuenta sin alertas: cero o empty state operativo, sin excepción.
- **CA-DASH-009.** Cuenta sin incidencias: el dashboard funciona.
- **CA-DASH-010.** El contador de notificaciones coincide con las no leídas del usuario autenticado.
- **CA-DASH-011.** Abrir el dashboard no modifica estados de dominio.
- **CA-DASH-012.** La interfaz se adapta a móvil y escritorio.
- **CA-DASH-013.** Los enlaces llevan solo a recursos que el usuario ya puede consultar.
- **CA-DASH-014.** HOME muestra plan/uso y no funcionalidades Farm irrelevantes (gestión de usuarios, visión de equipo).

## Alineación de calidad

### ISO/IEC 25010:2023

Alineación con el modelo de producto (QUALITY-001). **No** se afirma cumplimiento total ni certificación.

Características aplicables: adecuación funcional, eficiencia de desempeño, capacidad de interacción, fiabilidad, seguridad, mantenibilidad, flexibilidad.

**Adecuación funcional.** El contenido corresponde al objetivo de cada rol. Evidencia: `DashboardByRoleTest` (contextos `home`, `farm_admin`, `farm_supervisor`, `farm_specialist`) y `cypress/e2e/dashboard-by-role.cy.js`.

**Eficiencia de desempeño.** No se carga el historial completo; listas con límites centralizados; eager load en lecturas, alertas e incidencias. Línea base local observada (SQLite de PHPUnit, no contractual): HOME 13 queries; Farm admin 24; Farm supervisor 20; Farm specialist 11. Tiempo local aproximado 12–19 ms por `GET /dashboard` en ese entorno. Un test anti-N+1 comprueba que varias incidencias del especialista no añaden una query por fila.

**Capacidad de interacción.** Información prioritaria por partial; empty states; `data-cy` estables. Evidencia: Cypress (escritorio y viewport 375×667) y Dusk de regresión (login/dashboard admin).

**Fiabilidad.** Cero readings, alerts, incidents o notifications no lanza excepción. Evidencia: Feature tests de empty states y regresión PHPUnit 162, Cypress 11, Dusk 3.

**Seguridad.** Indicadores con `fish_farm_id` / pondIds del tenant y `assigned_to` en especialista. La UI no reemplaza autorización. Evidencia: tests de tenancy Farm A vs B y especialista A vs B.

**Mantenibilidad.** `DashboardDataService` prepara datasets; `DashboardController` solo delega; Blade no consulta Eloquent.

**Flexibilidad.** Una sola ruta `/dashboard` para HOME, Farm/admin, Farm/supervisor y Farm/specialist.

### Consideraciones ISO/IEC 27001:2022

ISO/IEC 27001 es **referencia complementaria** (SECURITY-001). **No** hay certificación ni SGSI.

- **Activo:** información operativa de cada cuenta (unidades, lecturas, alertas, incidencias, uso de plan).
- **R-DASH-SEC-001.** Exposición accidental de otra `FishFarm`. Control: `fish_farm_id`, consultas restringidas, autorización backend, tests multi-tenant.
- **R-DASH-SEC-002.** Mostrar al especialista trabajo que no es suyo como si lo fuera. Control: filtros `assigned_to`, roles; el backend de incidencias (SPEC-006) no se relaja.
- **Disponibilidad:** ausencia de datos sin fallar (empty states).

No se añaden controles 27001 ajenos a esta SPEC.

## Trazabilidad

| Documento | Relación |
| --- | --- |
| SPEC-001 | `account_type`, roles, tenancy |
| SPEC-002 | plan y uso |
| SPEC-003 | dispositivos y `last_seen_at` |
| SPEC-004 | lecturas e historial (fuera del dashboard) |
| SPEC-005 | alertas y umbrales |
| SPEC-006 | incidencias y estados |
| SPEC-007 | notificaciones no leídas del usuario |
| QUALITY-001 | modelo ISO/IEC 25010:2023 |
| QUALITY-002 | matriz de trazabilidad |
| SECURITY-001 | referencia ISO/IEC 27001:2022 |

## Evidencia de implementación

Commit de producto: `a8fd6772fd5f1231b3c572c2c0c40bf6b5f023df` (`feat: implementar dashboard operativo por rol`).

| Pieza | Ubicación |
| --- | --- |
| Servicio de lectura | `app/Services/DashboardDataService.php` (`forUser`) |
| Controller | `app/Http/Controllers/DashboardController.php` |
| Shell + partials | `resources/views/dashboard.blade.php`, `resources/views/dashboard/{home,farm-admin,farm-supervisor,farm-specialist}.blade.php` |
| Feature tests | `tests/Feature/DashboardByRoleTest.php` (15 CA + anti-N+1 + línea base de queries) |
| Cypress | `cypress/e2e/dashboard-by-role.cy.js` |
| Seeder E2E | `database/seeders/E2ETestSeeder.php` (HOME, admin, supervisor, especialista A/B) |

Regresión al cierre: `npm run build` OK; PHPUnit 162 passed; Cypress Electron 11/11 (aviso de deprecación de Electron 146, sin fallo); Dusk 3 passed.

Línea base local de queries (`DB::enableQueryLog`, SQLite testing, no benchmark contractual): HOME 13; admin 24; supervisor 20; specialist 11. Tiempos locales aproximados 12–19 ms.

Sin porcentajes de calidad. Sin certificación ISO/IEC 25010 ni 27001.

Fuera de alcance: rutas por rol, IA, actuadores, pagos, `read-all`, polling, WebSockets, push, nuevos estados de alerta o incidencia.
