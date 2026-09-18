# SPEC-004 — Telemetría IoT

## Estado

IMPLEMENTADO

## Versión

1.0

## Objetivo

Ingerir lecturas de calidad de agua desde un ESP32 autenticado o desde el simulador interno, persistirlas como `Reading` y exponer un historial gráfico acotado por el plan.

## Actores

- **Dispositivo ESP32:** envía lecturas por `POST /api/readings` (SPEC-003).
- **Operador / desarrollador:** ejecuta `php artisan aqualytics:simulate` sobre un `device_uid` existente.
- **Administrador, Supervisor y Especialista:** consultan el historial de una unidad de su cuenta.
- **Sistema:** `ReadingIngestionService` unifica persistencia, `last_seen_at` y evaluación de alertas.

## Reglas de negocio

- **RN-001.** Toda lectura persistida pasa por `ReadingIngestionService::ingest()`, tanto la API como el simulador.
- **RN-002.** El simulador no crea dispositivos, no inserta alertas directo y no usa HTTP ni `X-Device-Token`.
- **RN-003.** Parámetros persistidos: temperatura, pH, turbidez y nivel de agua, más `recorded_at`.
- **RN-004.** Validación de API: `temperature` 0–60, `ph` 0–14, `turbidity` ≥ 0, `water_level` ≥ 0.
- **RN-005.** Tras ingerir, se actualiza `devices.last_seen_at`.
- **RN-006.** Si el estanque tiene umbrales, la ingestión dispara evaluación de alertas (SPEC-005).
- **RN-007.** El historial se consulta en `GET /ponds/{pond}/readings/history?range=`.
- **RN-008.** Rangos implementados: `24h`, `7d`, `30d`, `90d`. El permitido depende de `plan.history_days`.
- **RN-009.** Home (`history_days = 7`) permite `24h` y `7d`. Farm (`history_days = 90`) permite los cuatro.
- **RN-010.** Un rango fuera del plan responde 422: `Tu plan no incluye este periodo de historial.`
- **RN-011.** El historial no borra lecturas antiguas; solo limita la consulta.
- **RN-012.** La serie gráfica se recorta a un máximo de 500 puntos (`ReadingHistoryService::MAX_POINTS`).
- **RN-013.** El historial de otra cuenta responde `404`.

## Modelo conceptual

- **Reading:** `device_id`, `pond_id`, `temperature`, `ph`, `turbidity`, `water_level`, `recorded_at`.
- **ReadingIngestionService:** crea la lectura, actualiza el dispositivo y llama a `AlertEvaluationService`.
- **TelemetrySampleGenerator:** genera muestras `normal`, `anomaly` o `mixed` para el comando Artisan.
- **ReadingHistoryService:** resuelve el rango, aplica `history_days` y devuelve puntos en orden cronológico.
- **PondReadingHistoryController:** endpoint JSON del historial.

## Flujo funcional

```
ESP32 (HTTP + token)          artisan aqualytics:simulate
            \                        /
             \                      /
              ReadingIngestionService
                        ↓
                    Reading
                        ↓
              last_seen_at del Device
                        ↓
              AlertEvaluationService (SPEC-005)
                        ↓
              GET /ponds/{pond}/readings/history
                        ↓
              Filtro por range ∩ history_days
                        ↓
                    Historial gráfico
```

Modos del simulador: `--mode=normal|anomaly|mixed`, `--count`, `--interval`, `--anomaly-rate`. Documentación operativa: `docs/IOT_SIMULATOR.md`.

## Criterios de aceptación

- **CA-001.** Una lectura API válida se guarda y actualiza `last_seen_at`.
- **CA-002.** Valores fuera de validación (pH > 14, temperatura ausente, turbidez negativa) se rechazan con 422.
- **CA-003.** El simulador crea exactamente el número de lecturas solicitado sobre un dispositivo existente.
- **CA-004.** En modo normal, los valores caen dentro de los umbrales y no generan alertas.
- **CA-005.** En modo anomaly se genera al menos un valor fuera de rango y, si hay umbrales, la alerta nace por el servicio de ingestión (`reading_id` no nulo).
- **CA-006.** Un `device_uid` inexistente en el simulador no crea lecturas.
- **CA-007.** El historial de 24h devuelve solo lecturas de esa ventana, en orden cronológico.
- **CA-008.** Home puede pedir 7d y no puede pedir 30d.
- **CA-009.** Farm puede pedir 90d.
- **CA-010.** Un rango inválido se rechaza.
- **CA-011.** La respuesta de historial no expone `fish_farm_id`, credenciales ni `APP_KEY`.

## Evidencia de implementación

### Código relacionado

- `app/Models/Reading.php`
- `app/Services/ReadingIngestionService.php`
- `app/Services/ReadingIngestionResult.php`
- `app/Services/TelemetrySampleGenerator.php`
- `app/Services/ReadingHistoryService.php`
- `app/Http/Controllers/Api/ReadingController.php`
- `app/Http/Controllers/PondReadingHistoryController.php`
- `app/Console/Commands/SimulateIoTTelemetryCommand.php`
- `docs/IOT_SIMULATOR.md`

### Tests relacionados

- `tests/Feature/IoTSimulatorTest.php`
- `tests/Feature/ReadingHistoryTest.php`
- `tests/Feature/ReadingIngestionServiceTest.php`
- `tests/Feature/ReadingApiTest.php`

### Estado actual

Implementado. No hay MQTT ni retención destructiva de lecturas. El tope de 500 puntos existe en código; no hay una aserción de test dedicada a ese recorte.
