# Entorno de Aqualytics

Esta guía describe cómo instalar y ejecutar Aqualytics en cualquier máquina
con PHP, Composer, Node.js y MySQL. No depende de Windows ni de XAMPP.

## Requisitos

- PHP 8.2 o superior (CLI y extensiones listadas abajo)
- Composer 2
- Node.js 20 o superior
- npm 10 o superior
- MySQL 8 (o compatible)

### Extensiones PHP requeridas

Obligatorias para la aplicación:

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

Adicionales para pruebas:

- `pdo_sqlite` (PHPUnit y Laravel Dusk)

## Instalación genérica

Desde la raíz del proyecto:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
npm ci
npm run build
```

Completa en `.env` al menos:

- `APP_URL`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

No dejes credenciales vacías en un entorno real. El archivo `.env.example`
no incluye usuarios ni contraseñas.

## Servidor de desarrollo

```bash
php artisan serve
```

Por defecto Laravel escucha en `http://127.0.0.1:8000`.

Para assets en caliente:

```bash
npm run dev
```

## Datos iniciales

El esquema se construye solo con migraciones. No hay datos de producción
obligatorios.

| Comando | Uso |
| --- | --- |
| `php artisan migrate` | Crea las tablas de la aplicación, incluidas `sessions`, `cache` y `jobs`. |
| `php artisan db:seed` | No inserta datos de negocio. |
| `php artisan db:seed --class=DemoSeeder` | Datos opcionales de demostración. No usar en producción. |
| `php artisan db:seed --class=E2ETestSeeder` | Exclusivo para Cypress/Dusk. No usar en producción. |

## Pruebas

PHPUnit usa SQLite en memoria y no toca la base MySQL principal:

```bash
php artisan test
```

Cypress espera la aplicación en `http://127.0.0.1:8000` (configurable con
`CYPRESS_BASE_URL`) y siembra datos con `E2ETestSeeder`:

```bash
php artisan serve
npm run cy:run
```

Si `php` no está en el `PATH`, define el ejecutable:

```bash
PHP_BINARY=/usr/bin/php npm run cy:run
```

Laravel Dusk usa `.env.dusk.local` y SQLite en `database/dusk.sqlite`.
Copia el ejemplo, genera una clave y deja la app escuchando en `APP_URL`
(por defecto el mismo `http://127.0.0.1:8000` de `php artisan serve`):

```bash
cp .env.dusk.local.example .env.dusk.local
php artisan key:generate --env=dusk.local
php artisan serve --host=127.0.0.1 --port=8000
php artisan dusk
```

No ejecutes Cypress y Dusk a la vez: Cypress usa MySQL con `E2ETestSeeder`
y Dusk usa SQLite. `APP_URL` debe apuntar a un puerto libre que sirva la app.

Una ruta absoluta a `dusk.sqlite` también funciona (por ejemplo en un
entorno Windows local). La ruta relativa del ejemplo se resuelve sola
contra la raíz del proyecto.

## Health check

`GET /health` responde HTTP 200:

```json
{
    "status": "ok",
    "service": "aqualytics"
}
```

No expone secretos, versión de PHP ni credenciales.

## Nota específica para Windows/XAMPP

El entorno local actual puede usar XAMPP. Eso es solo una característica
de esa máquina, no de la aplicación.

Si `php` no está en el `PATH`, sustituye `php` por el binario de XAMPP:

```text
D:\xampp\php\php.exe artisan key:generate
D:\xampp\php\php.exe artisan migrate
D:\xampp\php\php.exe artisan serve
D:\xampp\php\php.exe artisan test
D:\xampp\php\php.exe artisan dusk
```

Cypress, en Windows, usa `D:\xampp\php\php.exe` automáticamente si ese
archivo existe. En Linux o Docker usa `php` del `PATH`, o `PHP_BINARY`.
