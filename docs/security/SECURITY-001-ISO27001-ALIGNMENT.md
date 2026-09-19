# SECURITY-001 — Alineación con ISO/IEC 27001:2022

## Estado

DOCUMENTADO

## Versión

1.0

## Papel de la norma

ISO/IEC 27001:2022 **no** es la norma principal de Aqualytics. La norma principal de calidad de producto es ISO/IEC 25010:2023 (`docs/quality/QUALITY-001-ISO25010-QUALITY-MODEL.md`).

ISO/IEC 27001:2022 se toma **como referencia complementaria** para razonar sobre seguridad de la información: activos, riesgos y controles. Este documento describe **alineación** y **controles aplicados o pendientes**.

**No afirma** que Aqualytics esté certificado, ni que exista un SGSI completo, ni **cumplimiento total** de ISO/IEC 27001:2022.

---

## Confidencialidad, integridad y disponibilidad

| Dimensión | Aplicación en Aqualytics | Controles de referencia | Estado |
| --- | --- | --- | --- |
| Confidencialidad | Datos de una `FishFarm` no deben ser visibles por otra cuenta; tokens de dispositivo no se almacenan en claro. | Tenancy + `404`; HMAC-SHA256; `.env` fuera de Git | PARCIAL |
| Integridad | Lecturas y alertas no deben aceptarse de un dispositivo sin token válido; umbrales y estados no se alteran al notificar. | `X-Device-Token`; validación 422; tests de no mutación de status | PARCIAL |
| Disponibilidad | El servicio debe poder instalarse y comprobarse; no hay aún HA ni backup formal. | `GET /health`; documentación de entorno; pruebas de regresión | PARCIAL |

---

## Gestión de riesgos (alcance actual)

No hay un proceso formal de gestión de riesgos 27001 (activos valorados, tratamiento aprobado por dirección, declaración de aplicabilidad). Lo que sigue es un **registro inicial** de riesgos reales del producto, para priorizar controles.

Activos relevantes (no exhaustivo): cuentas `FishFarm`, usuarios, lecturas, alertas, incidencias, `APP_KEY`, tokens de dispositivo, base de datos, `.env`.

---

## Registro de riesgos

### R-SEC-001 — Acceso de usuario a otra FishFarm

| | |
| --- | --- |
| Impacto CIA | Confidencialidad |
| Control aplicado | `fish_farm_id` en consultas; autorización por rol; recurso de otra cuenta → `404` (no `403`) |
| Evidencia | SPEC-001, SPEC-006, SPEC-007; `FishFarmTenancyTest`, `IncidentManagementTest`, `NotificationManagementTest` |
| Estado | IMPLEMENTADO (en la capa de aplicación web documentada) |

### R-SEC-002 — ESP32 no autorizado enviando telemetría

| | |
| --- | --- |
| Impacto CIA | Integridad |
| Control aplicado | Cabecera `X-Device-Token`; comparación `hash_equals` sobre HMAC-SHA256 |
| Evidencia | SPEC-003; `DeviceAuthenticationTest` (`401` si falta o no coincide) |
| Estado | IMPLEMENTADO |

### R-SEC-003 — Robo de secretos almacenados

| | |
| --- | --- |
| Impacto CIA | Confidencialidad |
| Control aplicado | `api_token_hash`; token en claro solo en flash de sesión una vez; `api_token_hash` hidden; contraseñas de usuario con hash Laravel |
| Evidencia | SPEC-003; `Device::issueToken()` / `hashToken()` |
| Limitación | Quien tenga `APP_KEY` y un hash puede verificar tokens; el secreto de aplicación sigue siendo crítico |
| Estado | PARCIAL |

### R-SEC-004 — Intercepción del token durante transmisión

| | |
| --- | --- |
| Impacto CIA | Confidencialidad |
| Situación actual | Desarrollo en LAN usa HTTP (`APP_URL` local) |
| Control requerido en producción | HTTPS (TLS) en el canal ESP32 → API y en la web |
| Estado | PENDIENTE |

### R-SEC-005 — Pérdida de base de datos

| | |
| --- | --- |
| Impacto CIA | Disponibilidad, integridad |
| Situación actual | No hay procedimiento documentado de backup/restore de producción |
| Estado | PENDIENTE |

### R-SEC-006 — Exposición de credenciales de entorno

| | |
| --- | --- |
| Impacto CIA | Confidencialidad |
| Control aplicado | `.env` y `.env.*` en `.gitignore` (con excepciones `!.env.example` y `!.env.dusk.local.example`); no versionar secretos |
| Limitación | Depende de disciplina humana y de no forzar add de secretos; no hay escaneo automatizado de secretos en CI |
| Estado | PARCIAL |

### R-SEC-007 — Dependencias vulnerables

| | |
| --- | --- |
| Impacto CIA | Integridad, confidencialidad |
| Situación actual | No hay evidencia en el repositorio de `composer audit`, `npm audit`, Dependabot u otro análisis automatizado en CI |
| Control pendiente | Definir y ejecutar análisis periódico de dependencias PHP y Node |
| Estado | PENDIENTE |

---

## Resumen de controles

### Implementados (aplicación)

- Autenticación web (sesión Laravel).
- Autorización por rol.
- Tenancy y `404` entre cuentas.
- Autenticación de dispositivo IoT (`X-Device-Token` + HMAC-SHA256).
- Validación de payload de lecturas.
- Token de dispositivo no persistido en texto plano.

### Parciales

- Protección de secretos de entorno (Git ignore; sin escaneo CI).
- Almacenamiento de token IoT (hash sí; `APP_KEY` y canal en tránsito aún abiertos).
- Confidencialidad / integridad / disponibilidad como conjunto (faltan HTTPS, backup y SGSI).

### Pendientes

- HTTPS en producción (R-SEC-004).
- Backup/restore formal (R-SEC-005).
- Análisis automatizado de dependencias (R-SEC-007).
- SGSI, declaración de aplicabilidad, auditorías internas y cualquier pretensión de certificación 27001.

---

## SPEC nuevas (desde SPEC-008)

Cuando una SPEC tenga impacto de seguridad, incluir bajo **Alineación de calidad**:

```
### Consideraciones ISO/IEC 27001:2022

- activo
- riesgo
- control
- evidencia
```

No reescribir SPEC-001 a SPEC-007 en esta tarea.

## Relación con calidad de producto

La característica **Seguridad** de ISO/IEC 25010:2023 cubre el producto. ISO/IEC 27001:2022 cubre, como referencia, el sistema de gestión de la información. Aqualytics aplica la primera como marco principal y la segunda solo como guía de riesgos y controles.
