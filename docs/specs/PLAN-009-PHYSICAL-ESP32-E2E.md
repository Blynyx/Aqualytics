# PLAN-009 — Plan técnico de integración física ESP32 E2E

## Estado

PLANIFICADO

No implementado. Este documento no autoriza firmware, código Laravel, migraciones ni tests.

## Fuente de verdad

`docs/specs/SPEC-009-PHYSICAL-ESP32-E2E.md` (commit `1e9996b4c31f12a0c9985c310b263d54fa1e716e`).

Estado de la SPEC: PROPUESTO · Versión 0.1.

Si una decisión técnica choca con la SPEC, manda SPEC-009.

Contrato HTTP vigente: `docs/ESP32_API.md`. Sketch de partida: `docs/examples/esp32_http_example.ino`.

## Objetivo del plan

Traducir SPEC-009 a un procedimiento técnico ejecutable para validar físicamente:

```
ESP32 real → Wi-Fi → HTTP → Laravel → Reading → Alert → InternalNotification → Dashboard / historial
```

sin alterar el contrato actual (`POST /api/readings`, `X-Device-Token`, JSON de cuatro variables).

Fuera de alcance (no planificar):

- MQTT, BLE, WebSockets;
- actuadores, IA, sensores de software nuevos;
- rediseño de API o payload;
- TLS en el ESP32;
- buffer offline;
- creación automática de `Incident`;
- modificación de DemoSeeder;
- `docs/testing/ESP32-PHYSICAL-E2E-RESULTS.md` (se crea en la futura implementación);
- cambios a `.gitignore` en esta tarea.

---

## 1. Estrategia: dos fases

| Fase | Nombre SPEC | Objetivo | Valores |
| --- | --- | --- | --- |
| **A** | Nivel A — PRUEBA DE CONECTIVIDAD | ESP32 físico consume `POST /api/readings` en la LAN | Constantes de firmware permitidas |
| **B** | Nivel B — telemetría física | Sensores reales disponibles | Raw → calibración → JSON |

No saltar a sensores si la conectividad ESP32 → Laravel no está demostrada.

Expectativa de backend: **no se necesitan cambios Laravel**. El endpoint ya existe (SPEC-003/004). Solo se toca backend si aparece un defecto reproducible contra esas SPEC: detener, documentar, test RED, TDD, continuar. No adaptar Laravel para ocultar errores de firmware.

---

## 2. Sketch base (Fase A)

Partir de `docs/examples/esp32_http_example.ino`. Conservar:

- `#include <WiFi.h>` y `#include <HTTPClient.h>`
- `POST` al path `/api/readings`
- header `X-Device-Token`
- JSON: `device_uid`, `temperature`, `ph`, `turbidity`, `water_level`
- `WIFI_TIMEOUT_MS = 15000`
- `HTTP_TIMEOUT_MS = 8000`
- intervalo de envío actual: `delay(30000)` (30 s). Configurable; **no** es intervalo productivo óptimo.

Valores de conectividad ya presentes en el sketch (etiqueta **PRUEBA DE CONECTIVIDAD**):

```text
temperature = 25.6
ph = 7.2
turbidity = 34.5
water_level = 82.0
```

No cambiar payload. No introducir MQTT.

Funciones conceptuales futuras (Nivel B, sin arquitectura exagerada): `connectWiFi()`, `readTemperature()`, `readPh()`, `readTurbidity()`, `readWaterLevel()`, `sendReading()`.

Manejo de errores a distinguir en Serial (sin secretos): Wi-Fi no disponible; error de conexión HTTP; HTTP 201; HTTP 401; HTTP 422; otros HTTP. Puede imprimirse el status y, si no hay secretos, una respuesta sanitizada. No imprimir token, password ni body con `X-Device-Token`.

---

## 3. Secretos locales (no Git)

Valores que **nunca** se commitean: `WIFI_SSID`, `WIFI_PASSWORD`, `API_URL`, `DEVICE_UID` (el UID sí puede documentarse en evidencia; el token no), `DEVICE_TOKEN`.

El repositorio conserva solo placeholders (`YOUR_WIFI_SSID`, `YOUR_DEVICE_TOKEN`, `<TOKEN_LOCAL>`, `http://<IP-LAN-PC>:8000/api/readings`).

Opción futura (no implementar ahora): `secrets.h` incluido desde el sketch (`#include "secrets.h"`) con esas macros. Si se adopta:

- `secrets.h` ignorado por Git;
- `secrets.example.h` con placeholders;
- nunca mostrar `DEVICE_TOKEN` real;
- `.gitignore` se modifica **solo en la implementación futura** si es estrictamente necesario, **sin mezclar** cambios Docker pendientes.

Esta tarea **no** toca `.gitignore`.

Checklist previa a cualquier commit futuro: revisar `git diff`, firmware, docs y logs versionados en busca de secretos. No escanear ni pegar valores secretos en reportes.

---

## 4. Laravel y LAN

Antes de flashear:

1. `D:\xampp\php\php.exe artisan migrate:status` — comprobar esquema operativo. **No** migraciones destructivas.
2. Verificar que la aplicación responde (login, dashboard).
3. Arrancar:

```text
D:\xampp\php\php.exe artisan serve --host=0.0.0.0 --port=8000
```

4. En Windows: `ipconfig`. Usar la IPv4 del adaptador que comparte red con el ESP32. **No** `127.0.0.1`. **No** inventar IP.

```text
http://<IP_REAL_PC>:8000/api/readings
```

HTTP sin TLS: solo LAN de desarrollo. Producción requiere HTTPS (R-PHY-SEC-002, pendiente).

### Firewall Windows

Si el ESP32 no alcanza el puerto 8000:

1. Confirmar que Laravel escucha `0.0.0.0:8000`.
2. Confirmar IPv4 correcta.
3. Probar desde otro dispositivo de la misma LAN si es posible.
4. Revisar firewall solo si falla la conectividad.
5. Permitir PHP/puerto **únicamente** en red privada/controlada.

No desactivar el firewall de forma permanente.

### Orden de diagnóstico

Laravel local → curl local → LAN → ESP32 Wi-Fi → HTTP → token → payload → backend → UI.

No modificar varias capas a la vez.

---

## 5. Device de prueba y DemoSeeder

Recomendación conceptual: `ESP32-PHYSICAL-001`. No imponer este UID si ya hay otro registrado.

Requisitos: Pond correcto (HOME pecera o Farm estanque); token vigente; `status` active; `PondThreshold` configurado.

`DemoSeeder` crea devices (`DEMO-FARM-ESP32-001`, `DEMO-HOME-ESP32-001`) **sin** emitir token en claro ni `api_token_hash`. Un device existente en DB **no** autentica por sí solo.

Obtener token real de un solo uso: crear Device desde la UI **o** regenerar token desde la UI (admin de la misma cuenta, SPEC-003). No modificar DemoSeeder en este plan.

Una validación física completa basta en HOME **o** Farm; no duplicar hardware.

---

## 6. Prueba previa con curl (mismo PC)

Separar fallos Laravel/API de Wi-Fi/ESP32. Token real **no** se documenta (`<TOKEN_LOCAL>`).

| Caso | Esperado |
| --- | --- |
| Token válido + payload en rango | `201` |
| Sin `X-Device-Token` | `401` `Dispositivo no autorizado.` |
| Token incorrecto | `401` |
| Payload inválido (p. ej. `ph` 15) | `422` |

Si curl falla, no flashear el ESP32.

---

## 7. Fase A — pruebas físicas de conectividad

Todas las evidencias de esta fase se etiquetan **PRUEBA DE CONECTIVIDAD**.

### A1 — Wi-Fi

ESP32 arranca, conecta al SSID, obtiene IP. Serial permitido: Wi-Fi conectado, IP del ESP32, intento HTTP, HTTP status. No password. No token.

### A2 — HTTP 201 (E2E-001)

Valores controlados **dentro** de validación API y **dentro** de thresholds (p. ej. 25.6 / 7.2 / 34.5 / 82.0 si los umbrales coinciden con el Demo: temperatura 22–28, pH 6.5–8.0, turbidez máx. 50, nivel 60–90). Ajustar si el Pond de prueba tiene otros umbrales.

Esperado: HTTP `201`; `Reading` nuevo; `last_seen_at` nuevo; **sin** `Alert`; dashboard e historial actualizados.

### A3 — Anomalía controlada (E2E-002)

Cambiar **un** valor fuera de `PondThreshold` y **dentro** de validación API. Ejemplo si `ph_max = 8.0`: enviar `ph = 8.5` (`8.5 ≤ 14`).

**No** usar `ph = 15` para alerta: eso es `422` antes de `AlertEvaluationService`.

Esperado: `201`, `Reading`, `Alert` ligada al `Reading` (status inicial `active`, SPEC-005), `InternalNotification` `alert_created` a admin y supervisor (HOME: admin único). Especialista no recibe `alert_created` (SPEC-007). Dashboard refleja la alerta.

### A4 — 401 (E2E-003 / E2E-004)

- **A4.1** sin `X-Device-Token`.
- **A4.2** token incorrecto.

Esperado: `401`; sin `Reading`; sin actualización legítima de `last_seen_at`.

El firmware de la prueba **no** debe conservar el token incorrecto. Reponer el token correcto después.

### A5 — 422 (E2E-005)

Ejemplo: `ph = 15`. Esperado: `422`. Demuestra validación API, no threshold. No interpretarlo como fallo de Wi-Fi.

### A6 — Red no disponible (E2E-006)

Simular de forma segura: detener Laravel **o** desconectar temporalmente la Wi-Fi del ESP32. No dañar infraestructura.

Esperado: firmware informa fallo / status negativo; no hay `Reading` nuevo. Restaurar y comprobar que puede enviar de nuevo. **No** queue offline.

### Incident (opcional, humano)

Tras Alert + Notification, Admin/Supervisor puede crear Incident, asignar Specialist, iniciar y resolver (SPEC-006). Evidencia integral del producto; **no** requisito del firmware.

---

## 8. Fase B — inventario y sensores

No implementar drivers hasta el inventario físico. Registrar:

1. modelo exacto del ESP32;
2. sensor de temperatura;
3. sensor/fuente de pH;
4. sensor/fuente de turbidez;
5. sensor/fuente de nivel;
6. módulos ADC adicionales si existen;
7. alimentación;
8. pines utilizados;
9. librerías requeridas;
10. disponibilidad real.

Por variable: `DISPONIBLE` | `NO DISPONIBLE` | `PENDIENTE DE IDENTIFICAR`. **No inventar modelos.**

### Puerta de decisión

Fase B no se cierra completa hasta conocer el hardware. Si solo hay algunos sensores, implementar los disponibles; el resto queda **PENDIENTE DE VALIDACIÓN FÍSICA**. El payload sigue exigiendo las cuatro variables. En evidencia parcial, etiquetar con claridad, por ejemplo:

```text
SENSOR REAL: temperature
VALOR DE CONECTIVIDAD: ph, turbidity, water_level
```

No presentar el payload como 100 % sensorizado.

### Temperatura

Estrategia: raw → conversión según fabricante → °C → validación razonable → JSON. **No** asumir DS18B20 ni otra marca si no está confirmada.

### pH

ADC/raw → voltaje → calibración → pH. Documentar calibración real. No fórmula universal inventada. No afirmar precisión sin buffers/calibración.

### Turbidez

ADC/raw → voltaje → conversión según sensor → valor numérico. Aqualytics persiste `turbidity` **sin unidad explícita** en el contrato. Decisión pendiente: qué representa el valor físico del sensor usado. **No cambiar la API.**

### water_level

La UI muestra nivel como **%**. Antes de cerrar SPEC-009 hay que definir cómo el sensor pasa a 0–100 % (tipo, altura/rango, geometría). No inventar conversión hasta conocer el hardware.

### ADC ESP32

No asumir ADC ideal. Depende del módulo, atenuación, referencia, ruido y rango. Si hay sensores analógicos: documentar pin, rango esperado, promedio de muestras y conversión.

### Alimentación y seguridad

Comprobar voltaje de cada módulo; lógica 3.3 V del ESP32; no aplicar al ADC voltaje fuera de rango; tierra común cuando corresponda; no sumergir electrónica no diseñada para agua; no dañar sensores para inducir anomalías. No diseñar circuitos hasta conocer el hardware.

---

## 9. Evidencia futura

Archivo a crear **en la implementación**, no ahora: `docs/testing/ESP32-PHYSICAL-E2E-RESULTS.md`.

Campos: fecha/hora; ESP32; HOME/FARM; pond; `device_uid`; IP servidor; IP ESP32; escenario; valores enviados; HTTP status; Reading ID si se consulta; Alert ID; Notification; dashboard; historial; sensor real vs valor controlado; observaciones.

Nunca: password SSID, token, `APP_KEY`.

Checklist de capturas:

1. Serial sanitizado (ESP32 conectado).
2. HTTP `201`.
3. Device `last_seen_at`.
4. Reading en UI.
5. Historial.
6. Dashboard.
7. Alert de anomalía.
8. Notification.
9. `401`.
10. `422`.

No capturar token.

---

## 10. Calidad y seguridad

### ISO/IEC 25010:2023

Alineación (QUALITY-001). **No** certificación ni porcentajes.

| Característica | Evidencia a registrar |
| --- | --- |
| Compatibilidad (principal) | ESP32↔Wi-Fi, ESP32↔HTTP, JSON↔Laravel, Laravel↔MySQL, backend↔navegador. Éxito/fallo por escenario. |
| Adecuación funcional | Mismo pipeline `ReadingIngestionService`; sin canal paralelo. |
| Fiabilidad | Red caída, recuperación, 401, 422, sin lecturas falsas; `last_seen_at` solo en ingesta válida. |
| Seguridad | Token requerido/rechazado; secretos no visibles; tenancy intacto; HTTP solo LAN. |
| Eficiencia | Opcional: t0 antes de POST, t1 al status; visibilidad en UI. Informativo. Sin SLA. No fallar por milisegundos. |
| Interacción | Lectura comprensible en dashboard/historial/alertas sin consultar BD a mano. |

### ISO/IEC 27001:2022

Referencia complementaria (SECURITY-001). Sin SGSI.

| Riesgo | Control |
| --- | --- |
| R-PHY-SEC-001 Suplantación | `X-Device-Token` + HMAC (`api_token_hash`) |
| R-PHY-SEC-002 Intercepción HTTP | Solo LAN desarrollo; HTTPS producción **PENDIENTE** |
| R-PHY-SEC-003 Fuga de secretos | No Serial, no logs, no screenshots, no Git |
| R-PHY-SEC-004 Payload manipulado | Validación 422 + thresholds + autenticación |

### QUALITY-002 y cierre de SPEC

Tras pruebas físicas reales: actualizar `docs/quality/QUALITY-002-ISO25010-TRACEABILITY-MATRIX.md` con evidencia **obtenida** (compatibilidad, fiabilidad, seguridad, interacción, eficiencia informativa).

Cerrar SPEC-009 a IMPLEMENTADO 1.0 **solo** con evidencia física, no porque curl funcione. Mínimo: ESP32 real → Wi-Fi → POST real → `201` → `Reading` → `last_seen_at` → dashboard/historial, más 401, 422 y anomalía → Alert + Notification. Nivel B: documentar honestamente sensor real vs pendiente. **No** cambiar RN/CA.

---

## 11. Tests automatizados

SPEC-009 no sustituye evidencia física con PHPUnit.

Tras cambios relevantes al repositorio (firmware documentado, docs, o un arreglo de backend):

```text
npm run build
D:\xampp\php\php.exe artisan test
npm run cy:run
D:\xampp\php\php.exe artisan dusk
```

en serie. Firmware físico: pruebas manuales E2E.

---

## 12. Orden operativo futuro

1. Inventario de hardware.
2. Seleccionar cuenta HOME o FARM de prueba.
3. Crear/verificar Pond.
4. Configurar thresholds.
5. Crear o regenerar Device token (UI).
6. Obtener IP LAN del PC (`ipconfig`).
7. Iniciar Laravel en `0.0.0.0:8000`.
8. curl `201` / `401` / `422` desde el PC.
9. Configurar sketch Nivel A (placeholders / `secrets.h` local).
10. Flashear ESP32.
11. Verificar Wi-Fi (A1).
12. E2E-001 (A2).
13. E2E-002 (A3).
14. E2E-003/004/005 (A4, A5).
15. E2E-006 (A6).
16. Registrar evidencia sanitizada.
17. Revisar hardware real disponible.
18. Implementar sensores disponibles.
19. Calibrar y documentar.
20. Repetir lectura normal/anómala con origen físico.
21. Regresión del sistema.
22. Actualizar QUALITY-002.
23. Cerrar SPEC-009.

---

## 13. Trazabilidad SPEC → plan

| SPEC | Plan |
| --- | --- |
| RN-PHY-001 | Contrato y sketch sin cambio de payload |
| RN-PHY-002 / Device UI | §5 Device; no DemoSeeder como token |
| RN-PHY-003 / 011 / 012 | §3 secretos; HTTP LAN; HTTPS pendiente |
| RN-PHY-004 / 005 | A2; `ReadingIngestionService` |
| RN-PHY-006 | A3 `ph=8.5` vs A5 `ph=15` |
| RN-PHY-007 / 008 | A4 / A5 |
| RN-PHY-009 | A6 |
| RN-PHY-010 / CA-PHY-014 | Fase A etiquetada conectividad |
| RN-PHY-013 | Sin cambios a thresholds ni estados |
| RN-PHY-014 | Incident humano opcional |
| RN-PHY-015 | Calibración Fase B; sin precisión inventada |
| CA-PHY-001–016 | §7 y criterio de cierre §10 |
| E2E-001–006 | A2–A6 |

---

## Evidencia de esta tarea

Solo planificación. SPEC-009 permanece PROPUESTO 0.1. Sin firmware, sin commit de producto.
