# SPEC-008 — Dashboard operativo por rol

## Estado

PROPUESTO

## Versión

0.1

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

`DashboardController` entrega una vista general de la `FishFarm` del usuario autenticado:

- recuentos de unidades, dispositivos, lecturas y alertas `active`;
- listado de unidades;
- últimas 10 lecturas;
- hasta 10 alertas `active`;
- plan y uso (`max_units`, `max_devices`, `max_users`).

La vista distingue HOME/FARM principalmente por textos (`Pecera` / `Estanque`). Los roles Farm **comparten el mismo conjunto de datos**. SPEC-008 especializa consultas y bloques visuales sin cambiar permisos backend de SPEC-001 a SPEC-007.

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

**Adecuación funcional.** El contenido corresponde al objetivo de cada rol. Evidencia futura: Feature tests por `account_type` y `role`.

**Eficiencia de desempeño.** No cargar historiales completos; listas con límites; evitar N+1 y consultas redundantes por tarjeta. Métricas futuras (sin umbrales inventados): número razonable de queries y tiempo local documentado cuando exista línea base.

**Capacidad de interacción.** Información prioritaria visible; responsive; empty states; etiquetas HOME/FARM. Evidencia futura: Cypress y Dusk.

**Fiabilidad.** Cero readings, alerts, incidents o notifications no debe lanzar excepción. Evidencia: Feature tests y regresión.

**Seguridad.** Indicadores con `fish_farm_id` y reglas de rol. La UI no reemplaza autorización. Evidencia: tests de tenancy.

**Mantenibilidad.** No concentrar la lógica de consultas en Blade. Separar preparación de datos y vista. La clase concreta queda para PLAN-008.

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

## Evidencias futuras de implementación

Cuando se implemente (fuera de esta tarea):

- Feature tests del dashboard por contexto/rol y tenancy;
- Cypress (rol / responsive);
- regresión PHPUnit, Cypress y Dusk;
- `npm run build`;
- revisión de consultas si corresponde.

Sin porcentajes de calidad. Sin commit de producto en esta SPEC.

## Evidencia de implementación

Pendiente. SPEC-008 no está implementada.

Fuera de alcance: rutas por rol, IA, actuadores, pagos, `read-all`, polling, WebSockets, push, nuevos estados de alerta o incidencia.
