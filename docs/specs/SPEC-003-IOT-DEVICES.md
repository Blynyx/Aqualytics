# SPEC-003 — Dispositivos IoT

## Estado

IMPLEMENTADO

## Versión

1.0

## Objetivo

Registrar dispositivos de telemetría por estanque o pecera y autenticarlos en la API de lecturas con un token de dispositivo, sin almacenar el secreto en texto plano.

## Actores

- **Administrador:** registra el dispositivo en una unidad de su cuenta y puede regenerar el token.
- **Supervisor / Especialista:** no registran ni regeneran tokens.
- **Dispositivo ESP32 (o cliente HTTP equivalente):** envía lecturas con `device_uid` y cabecera `X-Device-Token`.
- **Simulador interno:** no usa HTTP ni token; llama al mismo servicio de ingestión. Ver SPEC-004.

## Reglas de negocio

- **RN-001.** Un `Device` pertenece a un `Pond` y, por tenancy, a un `FishFarm`.
- **RN-002.** `device_uid` es único a nivel global.
- **RN-003.** Solo un administrador de la misma cuenta puede registrar un dispositivo o regenerar su token.
- **RN-004.** No se puede registrar un dispositivo en un estanque de otra cuenta.
- **RN-005.** El alta respeta `max_devices` del plan (SPEC-002).
- **RN-006.** Al registrar o regenerar, el sistema genera un token en claro de 64 caracteres hex (`bin2hex(random_bytes(32))`) y lo muestra una sola vez en sesión (`device_token`).
- **RN-007.** El token no se guarda en texto plano. Se persiste `api_token_hash` = HMAC-SHA256(token, `APP_KEY`).
- **RN-008.** `POST /api/readings` exige `X-Device-Token`. Si falta, no coincide o el dispositivo no tiene hash, responde `401` con el mensaje `Dispositivo no autorizado.`
- **RN-009.** El `device_uid` se valida primero; la autorización por token ocurre antes de persistir la lectura.
- **RN-010.** Regenerar el token invalida el anterior. Un administrador no puede regenerar el token de un dispositivo de otra cuenta (`404`).

## Modelo conceptual

### Device

Campos relevantes:

- `pond_id`
- `name`
- `device_uid`
- `status`
- `api_token_hash` (oculto en serialización)
- `last_seen_at` (se actualiza al ingerir una lectura)

Relaciones: `pond()`, `readings()`, `alerts()`.

## Flujo funcional

### Alta administrativa

```
Administrador
    ↓
POST /ponds/{pond}/devices  (o POST /devices con pond_id)
    ↓
Valida tenancy + límite de plan + device_uid único
    ↓
Crea Device (status = active)
    ↓
Device.issueToken()
    ↓
Guarda api_token_hash (HMAC-SHA256)
Devuelve el token en claro una vez (flash de sesión)
```

### Ingesta autenticada

```
ESP32
    ↓
POST /api/readings
    body: device_uid + sensores
    header: X-Device-Token
    ↓
Device Authentication (tokenMatches)
    ↓
Sí → valida sensores → ReadingIngestionService
No → 401 Dispositivo no autorizado.
```

## Criterios de aceptación

- **CA-001.** Un administrador puede registrar un dispositivo en un estanque de su cuenta y recibe un token de un solo uso.
- **CA-002.** El hash persistido no es igual al token en claro.
- **CA-003.** `device_uid` es obligatorio y único.
- **CA-004.** No se puede registrar un dispositivo en un estanque de otra cuenta.
- **CA-005.** Una lectura con token válido responde `201` y persiste el `Reading`.
- **CA-006.** Token ausente, inválido o de otro dispositivo responde `401` y no crea lectura.
- **CA-007.** Un dispositivo sin `api_token_hash` no puede enviar lecturas.
- **CA-008.** Regenerar el token deja inválido el token anterior.
- **CA-009.** Un supervisor no puede regenerar tokens (`403`).
- **CA-010.** MQTT, emparejamiento BLE y drivers de sensor nativos están **fuera de alcance actual**. El contrato HTTP está en `docs/ESP32_API.md`.

## Evidencia de implementación

### Código relacionado

- `app/Models/Device.php` — `hashToken()`, `issueToken()`, `tokenMatches()`.
- `app/Http/Controllers/DeviceController.php` — alta y regeneración.
- `app/Http/Controllers/Api/ReadingController.php` — validación de `X-Device-Token`.
- `routes/api.php` — `POST /api/readings`.
- `routes/web.php` — `POST /ponds/{pond}/devices`, `POST /devices/{device}/regenerate-token`.
- `database/migrations/2026_09_18_220000_add_api_token_hash_to_devices_table.php`
- `docs/ESP32_API.md`
- `docs/examples/esp32_http_example.ino`

### Tests relacionados

- `tests/Feature/DeviceAuthenticationTest.php`
- `tests/Feature/DeviceTest.php`

### Estado actual

Implementado. El canal productivo de dispositivos es HTTP + token HMAC. El simulador interno no sustituye esta autenticación.
