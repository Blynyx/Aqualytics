# SPEC-002 — Suscripciones y planes

## Estado

IMPLEMENTADO

## Versión

1.0

## Objetivo

Asociar cada cuenta a un plan que define límites operativos (unidades, usuarios, dispositivos e historial). No hay cobro ni pasarela de pagos.

## Actores

- **Administrador:** registra la cuenta (se le asigna el plan automáticamente) y choca con los límites al crear unidades, dispositivos o usuarios.
- **Supervisor / Especialista:** no gestionan planes. Operan dentro de los límites ya asignados a la cuenta.
- **Sistema:** crea la suscripción por defecto al registrar la cuenta y evalúa los límites en cada alta.

## Reglas de negocio

- **RN-001.** Cada `FishFarm` tiene como máximo una `Subscription` (`fish_farm_id` único).
- **RN-002.** El registro Home asigna el plan `home` (Aqualytics Home). El registro Farm asigna el plan `farm` (Aqualytics Farm).
- **RN-003.** Los límites vigentes salen del `Plan` de la suscripción: `max_units`, `max_users`, `max_devices`, `history_days`.
- **RN-004.** Si no hay plan o no se puede resolver, `SubscriptionLimitService` no autoriza nuevas altas.
- **RN-005.** Superar un límite se rechaza con error de validación; no se borra información existente.
- **RN-006.** No existen pagos reales. `monthly_price` está presente y se siembra en `0.00`.
- **RN-007.** El campo `ends_at` existe en la suscripción pero **no se evalúa** en controladores ni servicios. El ciclo de vida (renovación, vencimiento, cancelación) está **pendiente**.
- **RN-008.** No hay interfaz de cambio o upgrade de plan. **Fuera de alcance actual.**

## Modelo conceptual

### Plan

Campos implementados:

| Campo | Uso actual |
| --- | --- |
| `code` | `home` o `farm` |
| `name` | Nombre comercial |
| `account_type` | Tipo de cuenta al que aplica |
| `monthly_price` | Precio informativo; hoy `0.00` |
| `max_units` | Máximo de peceras/estanques |
| `max_users` | Máximo de usuarios |
| `max_devices` | Máximo de dispositivos en toda la cuenta |
| `history_days` | Días de historial gráfico permitidos |
| `is_active` | Plan disponible para asignación |

### Subscription

Campos implementados:

| Campo | Uso actual |
| --- | --- |
| `fish_farm_id` | Cuenta dueña (único) |
| `plan_id` | Plan vigente |
| `status` | `active` o `inactive` |
| `starts_at` | Inicio |
| `ends_at` | Presente; no se usa para bloquear acceso |

### Planes actuales (semilla)

| code | name | account_type | monthly_price | max_units | max_users | max_devices | history_days |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `home` | Aqualytics Home | `home` | `0.00` | 1 | 1 | 1 | 7 |
| `farm` | Aqualytics Farm | `farm` | `0.00` | 10 | 10 | 10 | 90 |

Relaciones:

```
FishFarm 1 ── 1 Subscription ── * Plan (lado Plan: 1 ── * Subscription)
```

## Flujo funcional

```
Registro de cuenta (SPEC-001)
    ↓
FishFarm.assignDefaultSubscription()
    ↓
Busca Plan activo por code (home | farm)
    ↓
Crea Subscription status = active, starts_at = now()
    ↓
Altas posteriores
    ↓
SubscriptionLimitService
    ├─ crear pecera/estanque → canCreateUnit()
    ├─ crear usuario         → canCreateUser()
    ├─ crear dispositivo     → canCreateDevice()
    └─ historial gráfico     → history_days (SPEC-004)
    ↓
Si excede el plan → error de validación
```

## Criterios de aceptación

- **CA-001.** Una cuenta Home queda con plan Aqualytics Home.
- **CA-002.** Una cuenta Farm queda con plan Aqualytics Farm.
- **CA-003.** Home puede crear su primera pecera y su primer dispositivo.
- **CA-004.** Home no puede crear una segunda pecera, un segundo dispositivo ni un usuario adicional.
- **CA-005.** Farm puede crear varias unidades mientras no supere `max_units`.
- **CA-006.** Los límites de dos cuentas Home son independientes.
- **CA-007.** El dashboard muestra el plan y el uso actual de cupos.
- **CA-008.** No hay checkout, Stripe ni captura de medios de pago.

## Evidencia de implementación

### Código relacionado

- `app/Models/Plan.php`
- `app/Models/Subscription.php`
- `app/Models/FishFarm.php` — `assignDefaultSubscription()`
- `app/Services/SubscriptionLimitService.php`
- `app/Http/Controllers/PondController.php` — tope de unidades.
- `app/Http/Controllers/DeviceController.php` — tope de dispositivos.
- `app/Http/Controllers/UserController.php` — tope de usuarios.
- `app/Services/ReadingHistoryService.php` — tope de `history_days`.
- `app/Http/Controllers/DashboardController.php` — uso del plan.
- `database/seeders/PlanSeeder.php`
- `database/migrations/2026_09_18_180200_create_plans_table.php`
- `database/migrations/2026_09_18_180300_create_subscriptions_table.php`
- `database/migrations/2026_09_18_180400_assign_farm_subscriptions_to_existing_accounts.php`

### Tests relacionados

- `tests/Feature/SubscriptionPlanTest.php`
- `tests/Feature/SubscriptionLimitsTest.php`
- `tests/Feature/ReadingHistoryTest.php` (rangos acotados por `history_days`)

### Estado actual

Implementado como catálogo de planes + límites, sin facturación. Pagos, upgrades y vencimiento automático están **pendientes / fuera de alcance actual**.
