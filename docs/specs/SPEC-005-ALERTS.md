# SPEC-005 — Alertas IoT

## Estado

IMPLEMENTADO

## Versión

1.0

## Objetivo

Generar alertas automáticamente cuando una lectura supera los umbrales configurados del estanque o pecera, con independencia de si la lectura llegó por ESP32 o por el simulador.

## Actores

- **Sistema:** evalúa cada lectura ingestida contra `PondThreshold`.
- **Administrador y Supervisor:** asignan una alerta activa a un especialista de la misma cuenta (flujo de alerta en la ficha del estanque).
- **Administrador y Especialista asignado:** registran notas y marcan la alerta como resuelta.
- **Especialista no asignado / Supervisor:** no resuelven alertas en este flujo.

## Reglas de negocio

- **RN-001.** Las alertas nacen solo desde `AlertEvaluationService`, invocado por `ReadingIngestionService`.
- **RN-002.** La generación no depende del simulador. ESP32 real y simulador usan el mismo pipeline.
- **RN-003.** Sin umbral configurado en el estanque no se crea ninguna alerta, aunque los valores estén fuera de un rango esperado.
- **RN-004.** Cada regla incumplida crea una fila de alerta. No hay deduplicación. **Pendiente** consolidar alertas repetidas.
- **RN-005.** La alerta conserva referencia a `reading_id`, `pond_id` y `device_id`.
- **RN-006.** Estado inicial: `active`. Severidad actual de generación: `warning`.
- **RN-007.** Parámetros evaluados: temperatura, pH, turbidez y nivel de agua.
- **RN-008.** La lectura se guarda aunque genere alerta.
- **RN-009.** Asignar o resolver una alerta no elimina el registro original.
- **RN-010.** No se puede asignar ni resolver una alerta de otra cuenta (`404`).
- **RN-011.** Solo se asigna a un especialista de la misma `FishFarm`.
- **RN-012.** El especialista solo resuelve la alerta que tiene asignada; el administrador puede resolver con notas.

## Modelo conceptual

### Alert

Campos relevantes: `pond_id`, `device_id`, `reading_id`, `parameter`, `value`, `min_threshold`, `max_threshold`, `severity`, `status`, `message`, `detected_at`, `assigned_to_user_id`, `reported_by_user_id`, `resolved_by_user_id`, `assigned_at`, `resolved_at`, `resolution_notes`.

Estados usados en este flujo de alerta: `active` → `assigned` → `resolved`.

Relación adicional: `incident()` (HasOne). La capa de incidencias gestionables se documenta en SPEC-006 y no altera la generación.

### PondThreshold

Origen de los mínimos y máximos. Si no existe, no hay evaluación.

## Flujo funcional

```
Reading
    ↓
Threshold Evaluation (AlertEvaluationService)
    ↓
¿Existe PondThreshold?
    ├─ No → no crea alertas
    └─ Sí → compara cada parámetro
            ↓
         Alert (status = active, reading_id presente)
            ↓
         (opcional) Asignar / resolver en ficha del estanque
            ↓
         (opcional) Convertir en Incident (SPEC-006)
```

Reglas evaluadas:

| Condición | parameter | Mensaje |
| --- | --- | --- |
| temperatura < mínimo | `temperature` | Temperatura por debajo del rango configurado |
| temperatura > máximo | `temperature` | Temperatura por encima del rango configurado |
| pH < mínimo | `ph` | pH por debajo del rango configurado |
| pH > máximo | `ph` | pH por encima del rango configurado |
| turbidez > máximo | `turbidity` | Turbidez por encima del máximo configurado |
| nivel < mínimo | `water_level` | Nivel de agua por debajo del rango configurado |
| nivel > máximo | `water_level` | Nivel de agua por encima del rango configurado |

## Criterios de aceptación

- **CA-001.** Un pH por debajo del mínimo crea una alerta `active` ligada a la lectura.
- **CA-002.** Una temperatura por encima del máximo crea alerta.
- **CA-003.** Una turbidez por encima del máximo crea alerta.
- **CA-004.** Valores dentro de rango no crean alerta.
- **CA-005.** La lectura se persiste aunque se cree alerta.
- **CA-006.** Sin umbrales no se crea alerta.
- **CA-007.** Supervisor o administrador pueden asignar un especialista de la misma cuenta.
- **CA-008.** Un especialista no puede asignar alertas.
- **CA-009.** Resolver exige notas (`min:10`) y deja la alerta en `resolved` sin borrarla.

## Evidencia de implementación

### Código relacionado

- `app/Models/Alert.php`
- `app/Services/AlertEvaluationService.php`
- `app/Services/ReadingIngestionService.php`
- `app/Http/Controllers/AlertController.php` — `POST /alerts/{alert}/assign`, `POST /alerts/{alert}/resolve`.
- `app/Http/Controllers/PondThresholdController.php`
- `resources/views/ponds/show.blade.php` — bloque de alertas e incidencias de alerta.
- `database/migrations/2026_09_13_205801_create_alerts_table.php`
- `database/migrations/2026_09_13_231500_add_incident_tracking_to_alerts_table.php`

### Tests relacionados

- `tests/Feature/AlertGenerationTest.php`
- `tests/Feature/AlertAssignmentTest.php`
- `tests/Feature/AlertResolutionTest.php`
- `tests/Feature/IncidentResolutionTest.php` (resolución a nivel de `Alert`, no del modelo `Incident`)
- `tests/Feature/ReadingIngestionServiceTest.php`
- `tests/Feature/IoTSimulatorTest.php`

### Estado actual

La generación automática está implementada y no debe modificarse para el módulo de incidencias. Deduplicación de alertas repetidas, severidades distintas de `warning` e IA predictiva están **fuera de alcance actual**.
