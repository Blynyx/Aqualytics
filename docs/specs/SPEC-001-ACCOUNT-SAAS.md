# SPEC-001 — Gestión de cuentas SaaS

## Estado

IMPLEMENTADO

## Versión

1.0

## Objetivo

Definir la arquitectura multi-tenant de Aqualytics: cada cuenta es un `FishFarm` aislado, con tipo Home o Farm, y con usuarios que solo pueden operar sobre los datos de su propia cuenta.

## Actores

- **Administrador:** primer usuario creado al registrar la cuenta. En Farm puede crear estanques, dispositivos, umbrales y usuarios adicionales. En Home es el único usuario permitido por el plan.
- **Supervisor:** rol de Farm. Consulta unidades y alertas de su cuenta. No crea estanques, dispositivos ni umbrales.
- **Especialista:** rol de Farm. Consulta unidades de su cuenta y atiende trabajo asignado.

Visitantes no autenticados solo acceden a login y registro.

## Reglas de negocio

- **RN-001.** Cada cuenta es un tenant representado por `FishFarm`.
- **RN-002.** Todo usuario pertenece a exactamente una cuenta (`users.fish_farm_id`).
- **RN-003.** Los datos de estanques, dispositivos, lecturas, alertas, incidencias y usuarios no se mezclan entre cuentas.
- **RN-004.** El acceso a un recurso de otra cuenta responde `404`, no `403`, para no revelar existencia.
- **RN-005.** El tipo de cuenta se elige en el registro: `account_type = home` o `account_type = farm`.
- **RN-006.** Home representa un usuario doméstico y monitoreo de pecera. Las unidades se persisten como `unit_type = aquarium` aunque el cliente envíe otro valor.
- **RN-007.** Farm representa una piscigranja con múltiples usuarios y múltiples estanques. Las unidades se persisten como `unit_type = pond`.
- **RN-008.** Las cuentas existentes sin tipo explícito se tratan como Farm (`account_type` por defecto `farm`).
- **RN-009.** El registro crea la cuenta, un administrador y asigna la suscripción por defecto del tipo elegido.
- **RN-010.** Los límites operativos (unidades, usuarios, dispositivos e historial) dependen del plan de la suscripción, no del tipo de cuenta por sí solo. Ver SPEC-002.

## Modelo conceptual

- **FishFarm:** tenant. Campos relevantes: `name`, `status`, `account_type` (`home` | `farm`).
- **User:** actor autenticado. Campos relevantes: `fish_farm_id`, `name`, `email`, `password`, `role` (`admin` | `supervisor` | `specialist`).
- **Pond:** unidad de monitoreo de la cuenta (pecera o estanque). Siempre pertenece a un `FishFarm`.
- **Subscription / Plan:** vínculo comercial de la cuenta. Detalle en SPEC-002.

Relaciones implementadas:

```
FishFarm 1 ── * User
FishFarm 1 ── * Pond
FishFarm 1 ── 1 Subscription
FishFarm 1 ── * Incident
User     1 ── * Pond (como creador)
```

## Flujo funcional

```
Visitante
    ↓  GET/POST /register
Elige account_type (home | farm) y nombre de cuenta
    ↓
Crea FishFarm (status = active)
    ↓
Crea User administrador de esa cuenta
    ↓
Asigna suscripción por defecto (Home o Farm)
    ↓
Inicia sesión y redirige a /dashboard
    ↓
Consultas y mutaciones posteriores
    ↓
Filtro por fish_farm_id del usuario autenticado
    ↓
Recurso de otra cuenta → 404
```

## Criterios de aceptación

- **CA-001.** El registro con `account_type = home` crea una cuenta Home y un usuario administrador vinculado.
- **CA-002.** El registro con `account_type = farm` crea una cuenta Farm y un usuario administrador vinculado.
- **CA-003.** Un `account_type` inválido se rechaza y no crea usuario ni cuenta.
- **CA-004.** Un usuario solo ve en el dashboard las unidades de su `FishFarm`.
- **CA-005.** Un usuario de la misma cuenta puede abrir un estanque compartido.
- **CA-006.** Un usuario no puede abrir un estanque de otra cuenta (`404`).
- **CA-007.** En Home, crear una unidad guarda `unit_type = aquarium`.
- **CA-008.** En Farm, crear una unidad guarda `unit_type = pond`.
- **CA-009.** La interfaz usa las etiquetas «Pecera(s)» en Home y «Estanque(s)» en Farm.

## Evidencia de implementación

### Código relacionado

- `app/Models/FishFarm.php` — constantes `TYPE_HOME`, `TYPE_FARM`; helpers `isHome()`, `isFarm()`, `unitsLabel()`, `unitLabel()`, `assignDefaultSubscription()`.
- `app/Models/User.php` — constantes de rol y relación `fishFarm()`.
- `app/Models/Pond.php` — constantes `TYPE_AQUARIUM`, `TYPE_POND`.
- `app/Http/Controllers/AuthController.php` — registro transaccional de cuenta + admin + suscripción.
- `app/Http/Controllers/PondController.php` — listado y alta acotados al `fishFarm` del usuario.
- `app/Http/Controllers/DashboardController.php` — métricas filtradas por cuenta.
- `app/Http/Middleware/EnsureUserHasRole.php` — autorización por rol (`403` si el rol no coincide).
- `resources/views/auth/register.blade.php` — selección Home / Farm.
- `database/migrations/2026_09_13_223000_create_fish_farms_table.php`
- `database/migrations/2026_09_13_223001_add_fish_farm_tenancy_to_users_and_ponds.php`
- `database/migrations/2026_09_18_180000_add_account_type_to_fish_farms_table.php`
- `database/migrations/2026_09_18_180100_add_unit_type_to_ponds_table.php`

### Tests relacionados

- `tests/Feature/AccountTypeTest.php`
- `tests/Feature/SubscriptionLimitsTest.php`
- `tests/Feature/FishFarmTenancyTest.php`
- `tests/Feature/SubscriptionPlanTest.php`
- `tests/Feature/RoleAuthorizationTest.php`

### Estado actual

Implementado y cubierto por pruebas. Pagos, app móvil, MQTT e IA están **fuera de alcance actual**.
