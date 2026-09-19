# SPEC-009 — Integración física ESP32 y validación E2E IoT

## Estado

PROPUESTO

## Versión

0.1

## Objetivo

Validar que un ESP32 físico pueda enviar telemetría a una instancia **local** de Aqualytics mediante Wi-Fi/HTTP y recorrer el pipeline **ya implementado** (SPEC-003 a SPEC-008):

```
ESP32 físico
    ↓
POST /api/readings
    ↓
autenticación X-Device-Token
    ↓
Reading
    ↓
Device.last_seen_at
    ↓
PondThreshold
    ↓
Alert
    ↓
InternalNotification
    ↓
Incident (acción humana, SPEC-006)
    ↓
Dashboard / historial
```

SPEC-009 **no redefine** dispositivos, telemetría, alertas, incidencias, notificaciones ni dashboard. Su objetivo es demostrar **integración física** y **compatibilidad E2E** con el contrato vigente.

Esta SPEC **no autoriza** firmware nuevo, código Laravel, tests, migraciones ni cambios de endpoints. Eso se decidirá en PLAN-009.

## Actores

- **ESP32 físico:** cliente HTTP en la LAN de desarrollo. No es un Device creado por sí mismo.
- **Administrador:** registra el Device, conserva el token de un solo uso y consulta dashboard/historial.
- **Supervisor (Farm):** recibe `alert_created` y puede crear/asignar Incident (SPEC-006, SPEC-007).
- **Especialista (Farm):** no recibe `alert_created`; entra al flujo si se le asigna alerta o incidencia.
- **Operador de prueba:** ejecuta los escenarios E2E y produce evidencia sanitizada.

## Alcance

Incluye:

- ESP32 físico;
- conexión Wi-Fi local;
- Laravel en un PC de la misma LAN (`php artisan serve --host=0.0.0.0 --port=8000`);
- HTTP controlado **únicamente** en entorno de desarrollo;
- autenticación del dispositivo (`X-Device-Token`);
- envío de telemetría (`POST /api/readings`);
- actualización de `last_seen_at`;
- persistencia de `Reading`;
- evaluación de `PondThreshold`;
- generación de `Alert` cuando corresponda (SPEC-005);
- `InternalNotification` según SPEC-007;
- visualización en dashboard (SPEC-008) e historial (SPEC-004);
- validación de errores `401` y `422`;
- captura de evidencia E2E fechada.

No incluye:

- MQTT;
- Bluetooth/BLE;
- WebSockets;
- control de actuadores;
- IA;
- nuevos sensores de software (`dissolved_oxygen`, `ammonia`, `conductivity`);
- rediseño de API o payload;
- despliegue productivo;
- certificados TLS dentro del ESP32;
- cambios de arquitectura;
- buffer offline persistente en v0.1;
- creación automática de `Incident`.

## Contrato HTTP existente

Fuente: `docs/ESP32_API.md`, SPEC-003 y SPEC-004. **Sin cambios.**

```text
POST /api/readings
```

Headers:

```text
Content-Type: application/json
Accept: application/json
X-Device-Token: <TOKEN>
```

Body:

```json
{
    "device_uid": "...",
    "temperature": 25.6,
    "ph": 7.2,
    "turbidity": 34.5,
    "water_level": 82.0
}
```

| HTTP | Significado |
| --- | --- |
| 201 | Lectura aceptada. Se crea `Reading`, se actualiza `last_seen_at` y se evalúan alertas. |
| 401 | `{"message":"Dispositivo no autorizado."}` Token ausente, incorrecto, de otro dispositivo, o device sin hash. |
| 422 | Validación fallida (p. ej. UID inexistente o sensores fuera de rango de API). |

Validación de API vigente (SPEC-004): `temperature` 0–60, `ph` 0–14, `turbidity` ≥ 0, `water_level` ≥ 0.

No agregar campos. No cambiar nombres. No enviar el token en JSON, query string ni URL.

## Red local

El ESP32 físico **no** puede utilizar `127.0.0.1`: esa dirección representa al propio microcontrolador.

Laravel se expone en desarrollo con:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

El firmware usa:

```text
http://<IP-LAN-PC>:8000/api/readings
```

La IP LAN real se determina **en el momento de la prueba**. Esta SPEC no hardcodea una IP.

PC y ESP32 deben estar en una red con conectividad directa entre ambos.

## Entorno de desarrollo y HTTPS

HTTP sin TLS está permitido **solo** para la validación controlada en LAN de desarrollo.

Producción requiere HTTPS.

Esto es un **control pendiente para despliegue productivo**. HTTP en LAN **no** equivale a seguridad productiva (R-PHY-SEC-002).

## Dispositivo

Debe existir previamente un `Device` registrado en Aqualytics (SPEC-003):

- `pond_id` válido (pecera HOME o estanque Farm);
- `device_uid` coincidente con el firmware;
- `status` apropiado (p. ej. `active`);
- `api_token_hash` (HMAC-SHA256 del token con `APP_KEY`);
- token en claro disponible **únicamente** durante la configuración, una vez al registrar o regenerar.

El ESP32 **no** crea el Device. El `device_uid` físico debe ser el registrado.

## Token

El token:

- se configura en el firmware local;
- se envía únicamente mediante `X-Device-Token`;
- no se imprime en Serial;
- no se incluye en screenshots;
- no se incluye en logs;
- no se commitea;
- no se incluye en documentación.

Placeholder permitido: `<TOKEN_LOCAL>`.

## Datos de telemetría

Variables **obligatorias** del contrato actual:

- `temperature`
- `ph`
- `turbidity`
- `water_level`

No sustituir ninguna por `dissolved_oxygen`, `ammonia` ni `conductivity`.

## Fuente física de los datos

Hay **dos niveles** de prueba. No se mezclan en la evidencia.

### Nivel A — Conectividad física (PRUEBA DE CONECTIVIDAD)

ESP32 físico conectado a Wi-Fi. Puede enviar valores representativos definidos en firmware para comprobar únicamente:

- Wi-Fi;
- HTTP;
- token;
- JSON;
- endpoint;
- persistencia;
- pipeline Laravel.

Esta prueba **no** demuestra medición real de sensores. Debe etiquetarse **PRUEBA DE CONECTIVIDAD**.

### Nivel B — Telemetría física

Para considerar completa la validación física final, los valores deben originarse en:

- sensores físicos conectados al ESP32; o
- una entrada física/controlada de hardware **claramente documentada**.

No presentar valores constantes generados en firmware como si fueran mediciones de sensores reales.

Si un sensor todavía no está disponible, marcar esa variable como **PENDIENTE DE VALIDACIÓN FÍSICA**, sin inventar evidencia.

## Sensores

SPEC-009 no impone marcas ni modelos si aún no están definidos físicamente.

El contrato requiere, de forma conceptual:

- sensor de temperatura;
- sensor/fuente de pH;
- sensor/fuente de turbidez;
- sensor/fuente de nivel de agua.

PLAN-009 podrá documentar los módulos reales disponibles. Esta SPEC no inventa un modelo de sensor.

## Calibración

La integración física diferencia:

```
lectura eléctrica/raw
    ↓
conversión/calibración
    ↓
valor enviado a Aqualytics
```

La calibración específica depende del sensor físico. SPEC-009 **no** afirma precisión científica si no existe calibración documentada.

Para cada sensor real se registrará posteriormente: modelo, rango, método de conversión, calibración realizada, unidades y limitaciones.

## Escenarios E2E

### E2E-001 — Lectura normal

Precondiciones: Laravel activo; PC y ESP32 en LAN; Device válido; token válido; thresholds configurados; valores **dentro** de rango operativo **y** de validación API.

Cuando el ESP32 envía `POST /api/readings`:

1. API responde `201`.
2. Se crea `Reading`.
3. `device_id` corresponde al ESP32 registrado.
4. `pond_id` corresponde a su unidad.
5. `last_seen_at` se actualiza.
6. No se crea alerta por valores dentro de thresholds.
7. Dashboard e historial reflejan la lectura.

### E2E-002 — Lectura anómala

Precondiciones: las de E2E-001.

Enviar al menos un parámetro **fuera del threshold** configurado, induciendo la anomalía de forma controlada. No dañar sensores ni hardware.

Entonces:

1. API responde `201` (la lectura no se rechaza por estar fuera del threshold si sigue dentro de los límites de validación API).
2. `Reading` se persiste.
3. `last_seen_at` se actualiza.
4. `AlertEvaluationService` detecta el incumplimiento.
5. Se crea `Alert` ligada al `Reading` (`reading_id` no nulo).
6. La alerta mantiene el estado definido por SPEC-005 (inicial `active`, severidad de generación `warning`).
7. Se genera la `InternalNotification` de tipo `alert_created` según SPEC-007 (admins y supervisors de la misma `FishFarm`; en HOME, el administrador único). El especialista **no** recibe `alert_created`.
8. Dashboard refleja la situación.

Diferenciar **validación de API** (422) vs **threshold operativo** (201 + Alert).

### E2E-003 — Token ausente

Request sin `X-Device-Token`. Resultado: `401`, mensaje `Dispositivo no autorizado.` No se crea `Reading`.

### E2E-004 — Token incorrecto

Header presente con token inválido. Resultado: `401`. No `Reading`. No actualización **legítima** de `last_seen_at`.

### E2E-005 — Payload inválido

Ejemplo controlado: `ph` > 14, o `temperature` ausente, o `turbidity` negativa.

Resultado: `422`. No se persiste una lectura válida. Un `422` **no** se interpreta como fallo de Wi-Fi.

### E2E-006 — Dispositivo no disponible / red

Si el ESP32 no puede alcanzar la API:

- no inventar confirmación;
- el firmware debe detectar error HTTP/conexión;
- no afirmar que Laravel recibió datos;
- no crear datos ficticios en backend para ocultar el fallo.

Una reconexión posterior puede reanudar envíos. SPEC-009 **no** exige buffer offline persistente en v0.1.

## Incident

Una `Alert` **no** crea obligatoriamente un `Incident` de forma automática. SPEC-006 exige acción de administrador o supervisor.

La validación E2E puede demostrar:

```
ESP32 → Alert → Notification
```

y, posteriormente, el flujo humano:

```
Supervisor/Admin → crea Incident → asigna Specialist → Specialist resuelve
```

SPEC-009 no automatiza esa creación.

## Notificaciones

Respetar SPEC-007. Evento aplicable a la ingestión física: `alert_created` → administradores y supervisores de la misma `FishFarm`. No enviar al especialista por defecto.

No agregar email, SMS, WhatsApp, push ni WebSockets.

## HOME y FARM

La misma integración física puede usarse con una cuenta HOME:

```
ESP32 → pecera → readings → alertas → notificaciones → dashboard HOME
```

No se exigen varios usuarios.

En FARM:

```
ESP32 → estanque → reading → alert → notificación admin/supervisor → incident opcional → specialist
```

Una sola validación física completa puede realizarse sobre HOME **o** FARM. No se exige duplicar hardware para ambos segmentos.

## Reglas de negocio

- **RN-PHY-001.** SPEC-009 utiliza exclusivamente el contrato HTTP existente de SPEC-003 y SPEC-004.
- **RN-PHY-002.** El ESP32 físico debe corresponder a un `Device` registrado.
- **RN-PHY-003.** El token solo viaja mediante `X-Device-Token`.
- **RN-PHY-004.** Una lectura válida del ESP32 usa `ReadingIngestionService` y el mismo pipeline que cualquier lectura API.
- **RN-PHY-005.** Una lectura física válida actualiza `last_seen_at`.
- **RN-PHY-006.** Valores fuera del threshold pueden generar `Alert`, pero solo si siguen siendo válidos para la API.
- **RN-PHY-007.** Un error `401` no crea `Reading`.
- **RN-PHY-008.** Un error `422` no crea una lectura válida.
- **RN-PHY-009.** Una pérdida de conectividad no debe interpretarse como lectura recibida.
- **RN-PHY-010.** Valores generados dentro del firmware únicamente sirven como prueba de conectividad y no como evidencia de sensor físico.
- **RN-PHY-011.** La evidencia no puede revelar credenciales Wi-Fi, `X-Device-Token`, `APP_KEY` ni secretos.
- **RN-PHY-012.** HTTP LAN es únicamente un mecanismo de desarrollo; el despliegue productivo requiere HTTPS.
- **RN-PHY-013.** SPEC-009 no cambia thresholds, `AlertEvaluationService`, estados de alertas ni estados de incidencias.
- **RN-PHY-014.** Una alerta puede continuar al flujo humano de incidencia según SPEC-006, pero SPEC-009 no automatiza esa creación.
- **RN-PHY-015.** No se afirma precisión de sensor sin calibración documentada.

## Criterios de aceptación

- **CA-PHY-001.** Un ESP32 físico conectado a la LAN puede enviar una lectura válida y recibir HTTP `201`.
- **CA-PHY-002.** La lectura aparece persistida con `device_id` y `pond_id` correctos.
- **CA-PHY-003.** `last_seen_at` cambia tras una recepción válida.
- **CA-PHY-004.** Una lectura dentro del threshold no genera alerta.
- **CA-PHY-005.** Una lectura fuera de threshold genera la `Alert` esperada si existe `PondThreshold`.
- **CA-PHY-006.** La alerta queda ligada al `Reading`.
- **CA-PHY-007.** `alert_created` produce las notificaciones permitidas por SPEC-007.
- **CA-PHY-008.** Token ausente produce `401` y ninguna lectura.
- **CA-PHY-009.** Token inválido produce `401` y ninguna lectura.
- **CA-PHY-010.** Payload inválido produce `422`.
- **CA-PHY-011.** El dashboard muestra la lectura nueva.
- **CA-PHY-012.** El historial gráfico contiene la lectura recibida.
- **CA-PHY-013.** Ninguna evidencia expone el token.
- **CA-PHY-014.** Una prueba con valores generados por firmware queda identificada como conectividad, no sensor real.
- **CA-PHY-015.** Al menos una prueba final documenta el origen físico de los datos o marca explícitamente qué sensores continúan pendientes.
- **CA-PHY-016.** La integración no modifica el comportamiento funcional de SPEC-003 a SPEC-008.

## Evidencia requerida

La futura validación debe producir evidencia **fechada**. Como mínimo:

1. Identificación del ESP32 utilizado.
2. Cuenta HOME o FARM utilizada.
3. Unidad/`Pond`.
4. Device UID (**sin token**).
5. IP LAN del servidor durante la prueba.
6. Confirmación de red común.
7. Respuesta HTTP `201`.
8. `Reading` visible en Aqualytics.
9. `last_seen_at` actualizado.
10. Historial mostrando la nueva lectura.
11. Prueba normal (E2E-001).
12. Prueba anómala (E2E-002).
13. `Alert` creada (si aplica).
14. `InternalNotification` creada (si aplica).
15. `401` sin token.
16. `401` token inválido.
17. `422` payload inválido.
18. Capturas del dashboard.
19. Resultado de regresión automatizada (PHPUnit / Cypress / Dusk vigentes; SPEC-009 no añade tests en esta tarea).
20. Sensores físicos realmente utilizados.
21. Sensores pendientes.
22. Observaciones/calibración.

No incluir secretos en screenshots.

## Evidencia visual

Se permiten capturas de:

- Serial Monitor sanitizado;
- respuesta HTTP sin token;
- Device en Aqualytics;
- Dashboard;
- historial;
- Alert;
- Notification;
- Incident, si se continúa el flujo humano.

El Serial Monitor puede mostrar HTTP `201`, valores enviados, estado Wi-Fi y timestamp. **No** puede mostrar password Wi-Fi, token ni `APP_KEY`.

## Alineación de calidad

### ISO/IEC 25010:2023

Alineación con el modelo de producto (QUALITY-001). **No** se afirma cumplimiento total ni certificación.

Características especialmente aplicables: adecuación funcional, compatibilidad, fiabilidad, seguridad, eficiencia de desempeño, capacidad de interacción.

**Adecuación funcional.** El ESP32 físico debe completar el flujo ya definido sin introducir un pipeline alternativo. Evidencia futura: `Reading` / `Alert` / `InternalNotification` / dashboard.

**Compatibilidad.** Característica principal de SPEC-009. Interoperabilidad entre ESP32, Wi-Fi, HTTP, JSON, Laravel, MySQL y navegador. Evidencia futura: request/response reales y persistencia.

**Fiabilidad.** Respuesta ante red no disponible, `401`, `422`, ausencia de datos falsamente confirmados y recuperación posterior de comunicación. No se exige cola offline en v0.1.

**Seguridad.** Token requerido e incorrecto rechazado; secreto no visible; tenancy no alterado; HTTP solo en LAN de desarrollo.

**Eficiencia de desempeño.** En pruebas futuras se podrá registrar, de forma informativa, el tiempo aproximado ESP32 → respuesta HTTP y hasta visibilidad en Aqualytics. No se establece SLA inventado ni umbral ISO.

**Capacidad de interacción.** La lectura física debe ser comprensible desde dashboard, historial y alertas sin inspeccionar la base de datos manualmente.

### Consideraciones ISO/IEC 27001:2022

ISO/IEC 27001 es **referencia complementaria** (SECURITY-001). **No** hay certificación ni SGSI.

**Activo:** telemetría IoT y credenciales del dispositivo.

| Riesgo | Descripción | Control | Estado |
| --- | --- | --- | --- |
| **R-PHY-SEC-001** | Suplantación de ESP32. | `X-Device-Token` + HMAC almacenado (`api_token_hash`). | Control existente (SPEC-003). |
| **R-PHY-SEC-002** | Intercepción del token en HTTP LAN. | Red controlada de desarrollo. Tratamiento requerido para producción: HTTPS. | **PENDIENTE** para producción. |
| **R-PHY-SEC-003** | Exposición del token mediante logs/capturas. | No Serial, no logs, no screenshots, no Git. | A aplicar en la evidencia. |
| **R-PHY-SEC-004** | Envío de payload manipulado. | Validación backend (422) + thresholds + autenticación. | Control existente (SPEC-003, SPEC-004, SPEC-005). |

## Trazabilidad

| Documento | Relación |
| --- | --- |
| SPEC-003 | `Device`, `device_uid`, `X-Device-Token`, HMAC |
| SPEC-004 | `Reading`, `ReadingIngestionService`, `last_seen_at`, historial |
| SPEC-005 | `PondThreshold`, `AlertEvaluationService`, `Alert` |
| SPEC-006 | Incident humano posterior (no automático) |
| SPEC-007 | `InternalNotification` (`alert_created`) |
| SPEC-008 | Dashboard contextual HOME/Farm |
| QUALITY-001 | Modelo ISO/IEC 25010:2023 |
| QUALITY-002 | Matriz de trazabilidad (SPEC-009 aún no ejecutada) |
| SECURITY-001 | Referencia ISO/IEC 27001:2022 |
| `docs/ESP32_API.md` | Contrato HTTP actual |

## Fuera de esta tarea

Esta SPEC **no autoriza todavía**:

- firmware nuevo;
- código Laravel;
- tests automatizados nuevos;
- migraciones;
- cambios de endpoints;
- cambios de payload.

Eso se decidirá en PLAN-009.

## Evidencia de implementación

Pendiente. SPEC-009 todavía no está ejecutada físicamente.

Sin commit de producto en esta tarea.
