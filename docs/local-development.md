# Local Development

## Prerequisites

- PHP 8.5 through Laravel Herd
- Composer
- MySQL 8.0+
- Node.js 20.11.0
- npm 10.2.0+
- Git

Redis is optional at the current checkpoint. The default local queue, cache, and session drivers use the database.

## Local URLs

```text
API: https://school-api.test
API v1: https://school-api.test/api/v1
Web: http://localhost:3000
```

## API Setup

```bash
cd D:\Development\School-SaaS-Enterprise\apps\api
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` for the local MySQL credentials:

```env
APP_URL=https://school-api.test
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_saas_enterprise
DB_USERNAME=root
DB_PASSWORD=
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000,school-api.test
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

Create the database if it does not already exist:

```sql
CREATE DATABASE school_saas_enterprise
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Run migrations and seeders:

```bash
php artisan migrate:fresh --seed
```

## Herd Setup

The API app folder is:

```text
D:\Development\School-SaaS-Enterprise\apps\api
```

Link it in Herd:

```bash
herd link school-api --secure --isolate=8.5
```

Expected unauthenticated API check:

```bash
curl https://school-api.test/api/v1/me
```

Expected response:

```json
{"message":"Unauthenticated."}
```

If Herd is not running, the fallback local API server is:

```bash
cd D:\Development\School-SaaS-Enterprise\apps\api
php -S 127.0.0.1:8010 -t public public\index.php
```

Then set the web env API base to:

```env
NUXT_PUBLIC_API_BASE=http://127.0.0.1:8010/api
```

## Frontend Setup

```bash
cd D:\Development\School-SaaS-Enterprise\apps\web
nvm use 20.11.0
cp .env.example .env
npm install
npm run dev
```

The Nuxt app reads:

```env
NUXT_PUBLIC_API_BASE=https://school-api.test/api
NUXT_PUBLIC_APP_NAME="School SaaS Enterprise"
```

Keep the base at `/api`. The Nuxt `useApi()` composable appends `/v1` centrally.

## Login Smoke Test

After seeding, the local test login is:

```text
Email: test@example.com
Password: password
```

The login endpoint is:

```text
POST https://school-api.test/api/v1/auth/login
```

The current auth flow uses Sanctum personal access tokens:

- Login returns a bearer token.
- Nuxt stores the token in local storage.
- API requests send `Authorization: Bearer <token>`.

## Quality Gates

Backend:

```bash
cd D:\Development\School-SaaS-Enterprise\apps\api
php artisan test
vendor\bin\pint --test
php artisan route:list --path=api/v1 --except-vendor
php artisan migrate:fresh --seed
```

Frontend:

```bash
cd D:\Development\School-SaaS-Enterprise\apps\web
npm run build
```

