# Contrato HTTP del ESP32

El hardware real envía lecturas a Aqualytics por `POST /api/readings`. El simulador Artisan **no** usa este contrato: llama a `ReadingIngestionService` dentro del servidor.

`device_uid` identifica el dispositivo. La autenticación es un **token independiente** enviado en el header `X-Device-Token`. Quien solo conozca el UID no puede enviar datos.

## Almacenamiento del token

El token en claro **nunca** se guarda.

Se persiste `devices.api_token_hash = HMAC-SHA256(token, APP_KEY)` y se compara con `hash_equals`. Se eligió HMAC-SHA256 (no `Hash::make` / bcrypt) porque la validación IoT debe ser rápida y determinista. El token plano solo se muestra una vez al registrar o regenerar.

## Endpoint

Desarrollo en el mismo PC:

```text
POST http://127.0.0.1:8000/api/readings
```

Desde un ESP32 en la LAN, `127.0.0.1` es el propio microcontrolador, **no** el PC. Laravel debe escuchar en todas las interfaces:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

El firmware usa la **IP LAN del PC**, por ejemplo:

```text
POST http://192.168.1.100:8000/api/readings
```

Esa IP no se hardcodea en la aplicación. Se configura en el sketch (`API_URL`).

## Request

Headers:

```text
Content-Type: application/json
Accept: application/json
X-Device-Token: <TOKEN>
```

Body:

```json
{
    "device_uid": "ESP32-001",
    "temperature": 25.6,
    "ph": 7.2,
    "turbidity": 34.5,
    "water_level": 82.0
}
```

No enviar el token en query string, URL ni JSON. No imprimirlo en Serial ni en logs.

## Respuestas

| HTTP | Significado |
| --- | --- |
| 201 | Lectura aceptada. Se crea `Reading`, se actualiza `last_seen_at` y se evalúan alertas. |
| 401 | `{"message":"Dispositivo no autorizado."}` Token ausente, incorrecto, de otro dispositivo, o device sin token. |
| 422 | Validación: UID inexistente o sensores fuera de rango. |

Un device sin `api_token_hash` no puede usar la API (401). Hay que registrar de nuevo o pulsar **Regenerar clave** como admin de la misma cuenta.

## HTTPS

En LAN de desarrollo, HTTP solo para pruebas controladas.

En despliegue real usar HTTPS. Este contrato no configura certificados en el ESP32 todavía.

## Prueba local con curl

Tras crear o regenerar un token (no commitear ni reportar el valor):

```bash
curl -X POST http://127.0.0.1:8000/api/readings \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-Device-Token: <TOKEN_LOCAL>" \
  -d "{\"device_uid\":\"ESP32-001\",\"temperature\":25.6,\"ph\":7.2,\"turbidity\":34.5,\"water_level\":82.0}"
```

Sin header o con token incorrecto debe responder 401.

Código de referencia: [examples/esp32_http_example.ino](examples/esp32_http_example.ino).
