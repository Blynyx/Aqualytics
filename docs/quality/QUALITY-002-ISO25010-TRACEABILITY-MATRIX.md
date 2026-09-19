# QUALITY-002 — Matriz de trazabilidad ISO/IEC 25010:2023

## Estado

DOCUMENTADO

## Versión

1.0

## Uso

Relaciona características de ISO/IEC 25010:2023 con módulos Aqualytics, SPEC, código y pruebas. **No incluye porcentajes ni índices de cumplimiento.** Los estados son cualitativos y se limitan a evidencia en el repositorio.

Fuente de estados: `docs/quality/QUALITY-001-ISO25010-QUALITY-MODEL.md`.

Abreviaturas de estado: I = IMPLEMENTADO, P = PARCIAL, N = PENDIENTE.

---

## Matriz

| Característica ISO/IEC 25010 | Requisito / módulo Aqualytics | SPEC | Código | Prueba | Métrica / evidencia | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| Adecuación funcional | Cuentas SaaS multi-tenant Home/Farm | SPEC-001 | `FishFarm`, `AuthController`, `User` | `AccountTypeTest`, `FishFarmTenancyTest` | CA de registro y aislamiento; tests GREEN | I |
| Adecuación funcional | Planes y límites de suscripción | SPEC-002 | `Plan`, `Subscription`, `SubscriptionLimitService` | `SubscriptionPlanTest`, `SubscriptionLimitsTest` | Límites Home/Farm rechazados por validación | I |
| Adecuación funcional | Dispositivos IoT y token | SPEC-003 | `Device`, `DeviceController` | `DeviceTest`, `DeviceAuthenticationTest` | Alta de dispositivo y token de un solo uso | I |
| Adecuación funcional | Monitoreo hídrico / telemetría | SPEC-004 | `ReadingIngestionService`, `Reading`, `TelemetrySampleGenerator` | `ReadingIngestionServiceTest`, `IoTSimulatorTest`, `ReadingHistoryTest` | Lectura persistida; historial por plan | I |
| Adecuación funcional | Alertas por umbral | SPEC-005 | `AlertEvaluationService`, `Alert`, `PondThreshold` | `AlertGenerationTest` | Alerta ligada a `reading_id` | I |
| Adecuación funcional | Gestión de incidencias | SPEC-006 | `Incident`, `IncidentController` | `IncidentManagementTest` | Flujo open → closed; una alerta, una incidencia | I |
| Adecuación funcional | Notificaciones internas | SPEC-007, PLAN-007 | `InternalNotification`, `InternalNotificationService` | `NotificationGenerationTest`, `NotificationManagementTest` | Tres eventos v0.1; bandeja y `read_at` | I |
| Eficiencia de desempeño | Historial gráfico acotado | SPEC-004 | `ReadingHistoryService::MAX_POINTS` (500) | `ReadingHistoryTest` (rangos; no hay aserción del tope 500) | Tope en código; sin p95 de latencia | P |
| Eficiencia de desempeño | Ingestión de lecturas | SPEC-004 | `ReadingController`, `ReadingIngestionService` | `ReadingApiTest` | Correctitud funcional; tiempo no medido | P |
| Compatibilidad | ESP32 HTTP/JSON | SPEC-003, SPEC-004 | `POST /api/readings`, `docs/ESP32_API.md` | `ReadingApiTest`, `DeviceAuthenticationTest` | Contrato JSON + token | P |
| Compatibilidad | Stack web Laravel/MySQL/navegador | — | `docs/ENVIRONMENT.md` | Cypress, Dusk, PHPUnit | Entorno documentado; Docker no oficializado | P |
| Capacidad de interacción | Dashboard, roles, Home/Farm | SPEC-001, SPEC-002 | `DashboardController`, `layouts/app.blade.php` | `DashboardTest`, `RoleAuthorizationTest`, Cypress | UI operativa; sin auditoría WCAG | P |
| Capacidad de interacción | Gráficos de telemetría | SPEC-004 | `PondReadingHistoryController`, vistas de estanque | `ReadingHistoryTest`, `pond-history.cy.js` | Rangos 24h–90d según plan | P |
| Capacidad de interacción | Bandeja y campana | SPEC-007 | `InternalNotificationController`, layout | `NotificationManagementTest`, `notifications.cy.js` | Contador de no leídas del usuario | P |
| Fiabilidad | Validación de lecturas y formularios | SPEC-004 | `ReadingController` (422) | `ReadingApiTest` | Rechazo pH/temperatura/turbidez inválidos | P |
| Fiabilidad | Generación determinista de alertas | SPEC-005 | `AlertEvaluationService` | `AlertGenerationTest` | Misma tubería ESP32 y simulador | P |
| Fiabilidad | Regresión automatizada | — | PHPUnit, Cypress, Dusk | 145 PHPUnit (cierre SPEC-007); 6 Cypress; 3 Dusk | Suites verdes; sin SLA | P |
| Seguridad | Aislamiento entre FishFarm | SPEC-001 | Filtros `fish_farm_id`, `404` | `FishFarmTenancyTest`, `IncidentManagementTest` | Recurso ajeno no enumerable | P |
| Seguridad | Autenticación ESP32 | SPEC-003 | `Device::tokenMatches`, `ReadingController` | `DeviceAuthenticationTest` | `401` si token ausente o inválido | P |
| Seguridad | Autenticación y roles web | SPEC-001 | `AuthController`, `EnsureUserHasRole` | `AuthenticationTest`, `RoleAuthorizationTest` | Login, 403 por rol | P |
| Seguridad | Secretos de dispositivo | SPEC-003 | `api_token_hash` HMAC-SHA256 | `DeviceAuthenticationTest`, `DeviceTest` | Token no en texto plano | P |
| Seguridad | HTTPS y copias de seguridad | — | — | — | Ver SECURITY-001 | N |
| Mantenibilidad | Separación ingestión / alertas | SPEC-004, SPEC-005 | `ReadingIngestionService`, `AlertEvaluationService` | `ReadingIngestionServiceTest` | Un punto de ingestión | P |
| Mantenibilidad | Notificaciones como servicio | SPEC-007, PLAN-007 | `InternalNotificationService` | `NotificationGenerationTest` | Destinatarios fuera de controllers | P |
| Mantenibilidad | Spec-Driven + Git | — | `docs/specs/` | SPEC IMPLEMENTADO con evidencia | Trazabilidad commit ↔ SPEC | P |
| Flexibilidad | Segmentos Home y Farm | SPEC-001, SPEC-002 | `account_type`, `PlanSeeder` | `AccountTypeTest`, `SubscriptionLimitsTest` | Límites distintos por plan | P |
| Flexibilidad | Umbrales por unidad | SPEC-005 | `PondThreshold`, `PondThresholdController` | `PondThresholdTest` | Sin umbral no hay alerta | P |
| Flexibilidad | Historial según `history_days` | SPEC-002, SPEC-004 | `ReadingHistoryService` | `ReadingHistoryTest` | Home 7d; Farm 90d | P |
| Seguridad operacional | Aviso de calidad de agua | SPEC-005, SPEC-006, SPEC-007 | Alert, Incident, InternalNotification | `AlertGenerationTest`, `IncidentManagementTest`, `NotificationGenerationTest` | Detección y trabajo humano; sin actuadores | P |

---

## Notas

- La columna «Estado» califica la **característica en ese hilo de evidencia**, no un porcentaje ISO.
- Funciones fuera de SPEC (pagos, MQTT, email, push, WebSockets) no aparecen como IMPLEMENTADO.
- Los números de pruebas (145 PHPUnit, etc.) son una **fotografía** al cierre de SPEC-007; deben actualizarse cuando se vuelva a medir, no se usan como KPI de certificación.
