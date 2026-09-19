# QUALITY-001 — Modelo de calidad ISO/IEC 25010:2023

## Estado

DOCUMENTADO

## Versión

1.0

## Alcance y limitaciones

Aqualytics adopta **ISO/IEC 25010:2023** (modelo de calidad del producto) como **norma principal** para evaluar y planificar la calidad del software.

Este documento describe **alineación** con el modelo. **No afirma** que Aqualytics esté certificado, ni que el producto cumpla la norma en su totalidad. Cada característica se califica solo con evidencia observable en el repositorio y en las SPEC vigentes (SPEC-001 a SPEC-007).

Estados usados:

| Estado | Significado |
| --- | --- |
| IMPLEMENTADO | Hay evidencia en código, SPEC o pruebas para el alcance actual del producto. |
| PARCIAL | Existe control o práctica, pero falta medición, cobertura o un control de producción. |
| PENDIENTE | No hay evidencia suficiente en el proyecto. |

A partir de **SPEC-008**, cada SPEC nueva debe incluir la sección **Alineación de calidad** descrita al final de este documento.

Norma complementaria de seguridad de la información: ISO/IEC 27001:2022. Ver `docs/security/SECURITY-001-ISO27001-ALIGNMENT.md`. No es la norma principal del proyecto.

---

## 1. Adecuación funcional

### Definición aplicada a Aqualytics

Capacidad del producto de cubrir las funciones acordadas en las SPEC: cuentas multi-tenant, planes, dispositivos IoT, telemetría, alertas, incidencias y notificaciones internas, dentro del alcance v0.1 de cada módulo.

### Evidencia existente

- SPEC-001 a SPEC-007 en estado IMPLEMENTADO 1.0, con criterios de aceptación.
- Spec-Driven Development y TDD (pruebas Feature antes o junto a la implementación de módulos recientes).
- PHPUnit Feature tests por módulo (`AccountTypeTest`, `SubscriptionLimitsTest`, `DeviceAuthenticationTest`, `ReadingIngestionServiceTest`, `AlertGenerationTest`, `IncidentManagementTest`, `NotificationGenerationTest`, entre otros).

### Métricas posibles

- Porcentaje de CA de cada SPEC cubiertos por prueba automatizada.
- Número de SPEC IMPLEMENTADO vs PROPUESTO.
- Regresión PHPUnit en verde tras cada cambio.

### Estado

IMPLEMENTADO (alcance funcional actual documentado en SPEC-001 a SPEC-007). Pagos, MQTT, email/push y módulos futuros no forman parte de este juicio.

---

## 2. Eficiencia de desempeño

### Definición aplicada a Aqualytics

Comportamiento temporal y uso de recursos al ingerir lecturas, evaluar umbrales y consultar historial gráfico.

### Evidencia existente

- Historial acotado a 500 puntos (`ReadingHistoryService::MAX_POINTS`).
- Consultas filtradas por `pond_id`, `recorded_at` y `fish_farm_id` del usuario autenticado.
- No hay medición formal de latencia, throughput ni perfiles de memoria en CI.

### Métricas posibles

- Tiempo de respuesta de `GET /ponds/{pond}/readings/history` (p95).
- Tiempo de `POST /api/readings` (p95).
- Tamaño de payload del historial.

### Estado

PARCIAL

---

## 3. Compatibilidad

### Definición aplicada a Aqualytics

Capacidad de coexistir e intercambiar datos con ESP32 (HTTP/JSON), navegadores, Laravel, MySQL y el entorno de pruebas (PHPUnit, Cypress, Dusk).

### Evidencia existente

- API REST `POST /api/readings` con JSON y cabecera `X-Device-Token` (`docs/ESP32_API.md`).
- Stack documentado: Laravel 12, PHP 8.2, MySQL, Blade, Vite (`docs/ENVIRONMENT.md`).
- Interfaz web en navegador; pruebas Cypress y Dusk.
- Docker existe en el working tree local pero **no está validado ni versionado como fuente de verdad** en esta alineación.

### Métricas posibles

- Contratos de API estables (código de estado y campos JSON).
- Suites E2E verdes en navegador de referencia.

### Estado

PARCIAL

---

## 4. Capacidad de interacción

### Definición aplicada a Aqualytics

Capacidad de que administradores, supervisores y especialistas (y cuentas Home) operen el producto: dashboard, unidades, gráficos, incidencias, notificaciones y etiquetas Home/Farm.

### Evidencia existente

- Layout responsive (`layouts/app.blade.php`).
- Cuentas HOME/FARM y etiquetas Pecera/Estanque (SPEC-001).
- Dashboard, historial gráfico, bandeja de notificaciones, roles en navegación.
- No hay auditoría WCAG ni pruebas formales de usabilidad.

### Métricas posibles

- Tareas críticas completadas en Cypress/Dusk (login, alta de unidad, marcar notificación).
- Hallazgos de accesibilidad (pendiente de línea base).

### Estado

PARCIAL

---

## 5. Fiabilidad

### Definición aplicada a Aqualytics

Capacidad de operar sin fallos funcionales en el alcance actual: validar entradas, persistir lecturas, generar alertas de forma determinista y no mezclar datos entre cuentas.

### Evidencia existente

- Validación backend (API 422, formularios web).
- PHPUnit (145 pruebas Feature/Unit en la última corrida documentada al cerrar SPEC-007).
- Cypress y Dusk como regresión de interfaz.
- No hay SLA, alta disponibilidad ni copias de seguridad formales.

### Métricas posibles

- Resultado de `php artisan test`, `npm run cy:run` y `php artisan dusk` en serie.
- Tasa de fallos de ingestión (4xx/5xx) cuando exista observabilidad.

### Estado

PARCIAL

---

## 6. Seguridad

### Definición aplicada a Aqualytics

Protección de cuentas, telemetría y secretos de dispositivo: autenticación, autorización por rol, tenancy, token de dispositivo y no exposición de secretos en respuestas.

### Evidencia existente

- Login/registro y sesiones Laravel.
- Roles `admin`, `supervisor`, `specialist` y middleware `role`.
- Tenancy por `fish_farm_id`; recurso ajeno → `404` (SPEC-001, SPEC-006, SPEC-007).
- `X-Device-Token` y `api_token_hash` HMAC-SHA256 (SPEC-003).
- Validación de API de lecturas; hash de token oculto en serialización.
- HTTPS de producción, copias de seguridad y análisis automatizado de dependencias: ver SECURITY-001.

### Métricas posibles

- Pruebas de 401/403/404 de tenancy y dispositivo.
- Secretos ausentes en Git (`git` no rastrea `.env`).

### Estado

PARCIAL

---

## 7. Mantenibilidad

### Definición aplicada a Aqualytics

Facilidad de modificar el producto con SPEC, TDD, servicios de dominio y Git, sin acoplar alertas, incidencias y notificaciones de forma opaca.

### Evidencia existente

- Capas Models / Controllers / Services (`ReadingIngestionService`, `AlertEvaluationService`, `InternalNotificationService`, `SubscriptionLimitService`).
- Spec-Driven Development (`docs/specs/`) y planes técnicos (PLAN-007).
- Historial Git con commits por módulo.
- No hay métricas de complejidad ciclomática ni política formal de deuda técnica.

### Métricas posibles

- Cobertura de pruebas por módulo (cuando se mida).
- Tiempo de localizar un cambio a partir de una SPEC.

### Estado

PARCIAL

---

## 8. Flexibilidad

### Definición aplicada a Aqualytics

Capacidad de adaptarse a Home vs Farm, planes con límites, umbrales por unidad y distintos entornos de ejecución, sin reescribir el núcleo.

### Evidencia existente

- `account_type` home/farm y planes Aqualytics Home / Aqualytics Farm (SPEC-001, SPEC-002).
- Umbrales por estanque/pecera (SPEC-005).
- Límites `max_units`, `max_users`, `max_devices`, `history_days`.
- Documentación de entorno portable (`docs/ENVIRONMENT.md`). Empaquetado Docker aún no adoptado como entrega oficial.

### Métricas posibles

- Cuentas Home y Farm operando con los mismos servicios de ingestión y alertas.
- Límites de plan rechazados por validación (tests de suscripción).

### Estado

PARCIAL

---

## 9. Seguridad operacional

### Definición aplicada a Aqualytics

Capacidad de reducir daño operativo derivado de condiciones hídricas anómalas **dentro del alcance software**: detectar, avisar y gestionar trabajo. Aqualytics **no controla actuadores físicos** (bombas, aireadores, válvulas). No se evalúa como sistema de control industrial ni como SIL/IEC 61508.

### Evidencia existente

- Umbrales y generación automática de alertas desde lecturas (SPEC-005).
- Incidencias asignables y cerrables (SPEC-006).
- Notificaciones internas `alert_created`, `alert_assigned`, `incident_assigned` (SPEC-007).
- No hay corte automático de equipos ni lazos de control.

### Métricas posibles

- Alertas generadas con `reading_id` no nulo.
- Tiempo hasta asignación/lectura de notificación (no medido).

### Estado

PARCIAL (monitoreo y aviso humanos; sin actuación física).

---

## Alineación de calidad en SPEC nuevas (desde SPEC-008)

Toda SPEC a partir de SPEC-008 debe incluir:

```
## Alineación de calidad

### ISO/IEC 25010:2023

- características aplicables
- criterios medibles
- evidencia requerida

### Consideraciones ISO/IEC 27001:2022

cuando exista impacto de seguridad:

- activo
- riesgo
- control
- evidencia
```

Las SPEC-001 a SPEC-007 **no se reescriben** en esta tarea. El requisito aplica hacia adelante.

## Relación con otros documentos

- Matriz: `docs/quality/QUALITY-002-ISO25010-TRACEABILITY-MATRIX.md`
- Seguridad de la información: `docs/security/SECURITY-001-ISO27001-ALIGNMENT.md`
