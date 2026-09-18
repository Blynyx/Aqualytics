# Simulador de telemetría IoT

El comando `php artisan aqualytics:simulate` genera lecturas realistas para un dispositivo **ya registrado**. Sustituye temporalmente al ESP32 físico, pero **no duplica** la lógica de negocio: API y simulador pasan por `ReadingIngestionService`.

```
ESP32 real  →  POST /api/readings  →  ReadingIngestionService  →  Reading + Alert
Simulador   →  aqualytics:simulate →  ReadingIngestionService  →  Reading + Alert
```

El simulador **no crea dispositivos**. Si el UID no existe, el comando termina con código distinto de 0.

Sirve igual para cuentas **HOME** (pecera) y **FARM** (estanque): el núcleo IoT es el mismo.

## Uso normal

```bash
php artisan aqualytics:simulate --device=ESP32-001
```

Windows (una sola línea):

```powershell
php artisan aqualytics:simulate --device=ESP32-001
```

Por defecto genera **10** lecturas, espera **2** segundos entre ellas y usa el modo `normal`.

## Demo rápida

```bash
php artisan aqualytics:simulate \
  --device=ESP32-001 \
  --count=30 \
  --interval=1 \
  --mode=mixed \
  --anomaly-rate=15
```

Windows:

```powershell
php artisan aqualytics:simulate --device=ESP32-001 --count=30 --interval=1 --mode=mixed --anomaly-rate=15
```

Datos de demostración locales (no usar en producción):

```bash
php artisan db:seed --class=DemoSeeder
php artisan aqualytics:simulate --device=DEMO-FARM-ESP32-001 --count=10 --interval=0 --mode=mixed --anomaly-rate=30
php artisan aqualytics:simulate --device=DEMO-HOME-ESP32-001 --count=10 --interval=0 --mode=mixed --anomaly-rate=30
```

## Opciones

| Opción | Default | Descripción |
| --- | --- | --- |
| `--device=` | (obligatorio) | `device_uid` existente. Ejemplo: `CYP-ESP32-001`. |
| `--count=` | `10` | Entero positivo. Cantidad de lecturas. |
| `--interval=` | `2` | Segundos entre lecturas. `0` no duerme (útil en tests y desarrollo). |
| `--mode=` | `normal` | `normal`, `anomaly` o `mixed`. |
| `--anomaly-rate=` | `10` | Porcentaje 0–100. Solo aplica en `mixed`. |

## Modos

### `--mode=normal`

Valores dentro de los umbrales del estanque/pecera (o de los fallback de simulación si no hay umbral). No debería generar alertas si los thresholds están configurados.

### `--mode=anomaly`

Cada lectura saca **al menos un** parámetro de rango, de forma moderada. Las alertas las crea `AlertEvaluationService` a través de la ingestión, no el comando.

### `--mode=mixed`

La mayoría de lecturas son sanas. Aproximadamente `--anomaly-rate`% pueden contener una anomalía (no se exige exactitud estadística).

### Desarrollo rápido

```bash
php artisan aqualytics:simulate --device=ESP32-001 --count=20 --interval=0 --mode=mixed
```

## Rangos

El generador lee los umbrales reales del `PondThreshold` asociado:

- temperatura: `temperature_min` / `temperature_max`
- pH: `ph_min` / `ph_max`
- turbidez: `turbidity_max` (mínimo de simulación: fallback)
- nivel de agua: `water_level_min` / `water_level_max`

Si un umbral no está configurado, **solo para simular valores** se usan fallbacks seguros (no se usan para evaluar alertas):

| Parámetro | Fallback min | Fallback max |
| --- | --- | --- |
| temperature | 22.0 | 28.0 |
| ph | 6.5 | 8.0 |
| turbidity | 10.0 | 40.0 |
| water_level | 60.0 | 90.0 |

Las alertas siguen las reglas actuales: sin threshold en el estanque, no hay alerta.

Las lecturas respetan los límites físicos del API:

- temperatura 0–60
- pH 0–14
- turbidez ≥ 0
- nivel de agua ≥ 0

## Random walk

Los valores no saltan al azar. Cada parámetro arranca en el **centro del rango** y se mueve con un paso pequeño (p. ej. 25.1 → 25.2 → 25.0). El estado del walk vive **en memoria durante el comando**; no se persiste nada extra fuera de `readings`.

Una anomalía es un pico puntual: el walk interno permanece en zona sana para que el modo `mixed` pueda recuperarse.

## Alertas

El comando **no** inserta filas en `alerts`. Si una lectura queda fuera de rango, la alerta sale del mismo pipeline que `POST /api/readings`. No se cambia la consolidación de duplicados en esta tarea.
