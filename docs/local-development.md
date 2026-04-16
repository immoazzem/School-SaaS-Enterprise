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
DB_CONNECTION=mysql
DB_DATABASE=school_saas_enterprise
```

Fill in local `DB_USERNAME` and `DB_PASSWORD` from the machine's MySQL setup. Do not commit `.env`.

## Herd Setup

After `apps/api` exists:

1. Link or park the API app in Herd.
2. Configure the local site domain as `school-api.test`.
3. Confirm PHP is 8.5.
4. Confirm the site serves Laravel over HTTPS.

Exact Herd commands depend on the installed Herd CLI and will be finalized after the Laravel app is scaffolded.

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

When the Laravel and Nuxt apps exist, configure:

- Laravel session domain/stateful domains for `localhost:3000` and `school-api.test`.
- Nuxt API base URL to `https://school-api.test`.
- CSRF cookie request before login.

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

