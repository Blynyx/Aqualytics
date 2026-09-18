# Preparación de Aqualytics para Docker

Este documento deja listo lo que hará falta para containerizar Aqualytics.
Todavía no hay `Dockerfile`, `docker-compose.yml` ni configuración de Nginx.

## Versión de PHP

- PHP 8.2 o superior
- Recomendado: imagen oficial `php:8.2-fpm` o `php:8.2-cli`

## Extensiones PHP

Instalar en la imagen:

- `ctype`
- `curl`
- `fileinfo`
- `json`
- `mbstring`
- `openssl`
- `pdo`
- `pdo_mysql`
- `tokenizer`
- `xml`

Para ejecutar PHPUnit o Dusk dentro del contenedor, añadir `pdo_sqlite`.

## Servicios requeridos

- Aplicación PHP (php-fpm o `php artisan serve` solo en desarrollo)
- Servidor HTTP (Nginx o equivalente) delante de PHP
- MySQL 8
- Node.js solo en la etapa de build del frontend (no hace falta en runtime)

Redis y Memcached no son necesarios con la configuración actual
(`SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`).

## Puerto HTTP esperado

- Contenedor de aplicación / Nginx: `80` (o el que publique el orquestador)
- Desarrollo local con `php artisan serve`: `8000`
- Dusk: el puerto de `APP_URL` en `.env.dusk.local` (por defecto `8000`)
- MySQL: `3306` (interno de la red Docker; no publicarlo en producción)

La aplicación no hardcodea estos puertos en lógica PHP. Se configuran con
`APP_URL`, `DB_HOST` y `DB_PORT`.

## Variables de entorno

Usar `.env.example` como plantilla. Mínimo:

```env
APP_NAME=Aqualytics
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=aqualytics
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

- Generar `APP_KEY` en el arranque (`php artisan key:generate --force` solo si está vacía).
- `DB_HOST` en Docker será el nombre del servicio MySQL, no `127.0.0.1`.
- No copiar credenciales reales a la imagen.

## Directorios que necesitan permisos de escritura

El usuario del contenedor debe poder escribir en:

- `storage/`
- `storage/app/`
- `storage/framework/`
- `storage/framework/cache/`
- `storage/framework/sessions/`
- `storage/framework/views/`
- `storage/logs/`
- `bootstrap/cache/`

En el arranque:

```bash
php artisan storage:link
```

`public/storage` no se versiona; el enlace se crea en cada despliegue.

## Estrategia frontend

El navegador consume los assets compilados en `public/build`.

En la imagen de build:

```bash
npm ci
npm run build
```

Copiar `public/build` a la imagen final. No ejecutar Vite en producción.
`vite.config.js` no depende de rutas de Windows.

## Estrategia de base de datos

- Un servicio MySQL aparte.
- La aplicación solo lee `DB_CONNECTION`, `DB_HOST`, `DB_PORT`,
  `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`.
- Las tablas de sesión, cache y cola ya existen en las migraciones
  (`sessions`, `cache`, `jobs`).
- No usar SQLite en producción.
- No ejecutar `migrate:fresh` contra datos reales.

## Healthcheck

Endpoint de la aplicación:

```text
GET /health
```

Respuesta esperada: HTTP 200 y `{"status":"ok","service":"aqualytics"}`.

Sirve como `HEALTHCHECK` de Docker y como probe de orquestación.
No consulta secretos ni la versión de PHP.

Laravel también expone `GET /up`; para contenedores usar `/health`.

## Comandos de migración

En un contenedor de release o en un job de arranque, después de que MySQL
acepte conexiones:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

No sembrar `E2ETestSeeder` ni `DemoSeeder` en producción.

## Pruebas en CI

- PHPUnit: SQLite `:memory:` vía `phpunit.xml` (no usa MySQL).
- Cypress: base URL inyectada con `CYPRESS_BASE_URL` y `PHP_BINARY=php`.
- Dusk: `.env.dusk.local` con `DB_DATABASE=database/dusk.sqlite`.

## Pendiente (fuera de este documento)

- `Dockerfile` multi-stage (PHP + build de Vite)
- `docker-compose.yml` (app + mysql)
- Configuración de Nginx
- Usuario no root en el contenedor
- Secrets de orquestación para `APP_KEY` y la base de datos
