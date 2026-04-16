# Local Development

## Environment

- Windows workspace: `D:\Development`
- Project folder: `D:\Development\School-SaaS-Enterprise`
- Laravel Herd is installed and running.
- MySQL Workbench is installed.
- Docker is not required for Phase 1.

## Planned Local URLs

```text
API: https://school-api.test
Web: http://localhost:3000
```

## Database

Create a local MySQL database:

```sql
CREATE DATABASE school_saas_enterprise
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

The Laravel `.env` in `apps/api` should use:

```text
APP_URL=https://school-api.test
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_saas_enterprise
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000,school-api.test
```

Fill in local `DB_USERNAME` and `DB_PASSWORD` from the machine's MySQL setup. Do not commit `.env`.

The Nuxt `.env` in `apps/web` should use:

```text
NUXT_PUBLIC_API_BASE=https://school-api.test/api
```

## Herd Setup

The API app exists at:

```text
D:\Development\School-SaaS-Enterprise\apps\api
```

In Herd:

1. Add or link the API app folder.
2. Set the local site domain to `school-api.test`.
3. Confirm PHP is 8.5.
4. Confirm the site serves Laravel over HTTPS.

After the site is available:

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
```

## Nuxt Setup

After `apps/web` exists:

```bash
npm install
npm run dev
```

Nuxt should run at:

```text
http://localhost:3000
```

## Sanctum Local Notes

The current Phase 1 implementation uses Sanctum personal access tokens for the API:

- `POST /api/auth/login` returns a bearer token.
- Nuxt stores the token in local storage.
- API requests send `Authorization: Bearer <token>`.

The env template already includes stateful domains so cookie-based Sanctum SPA auth can be introduced later without changing the local hostnames.

## Quality Gates

Backend:

```bash
php artisan test
vendor/bin/pint --test
php artisan route:list
php artisan migrate:fresh --seed
```

Frontend:

```bash
npm run build
npm run typecheck
npm run lint
```
