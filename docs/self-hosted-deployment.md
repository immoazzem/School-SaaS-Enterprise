# Self-Hosted Deployment

This guide covers a single-server deployment for the School SaaS Enterprise stack.

## Runtime

- PHP 8.5 with required Laravel extensions.
- MySQL 8.0 or newer.
- Node.js 22 LTS or newer for building the Nuxt frontend.
- A web server such as Nginx, Caddy, Apache, or Laravel Herd Pro.
- A queue worker for Laravel jobs.
- A scheduler entry for Laravel's scheduler.

## API Deployment

Use `apps/api` as the Laravel application root.

1. Copy `.env.example` to `.env`.
2. Set production values:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://api.yourdomain.com`
   - `DB_*`
   - `QUEUE_CONNECTION=database` or a production queue driver
   - `SANCTUM_STATEFUL_DOMAINS`
   - `CORS_ALLOWED_ORIGINS`
3. Install dependencies:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
```

4. Generate or provide an app key:

```bash
php artisan key:generate --force
```

5. Run database migrations:

```bash
php artisan migrate --force
```

6. Cache the production bootstrap:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

7. Start one or more queue workers:

```bash
php artisan queue:work --tries=3 --timeout=120
```

8. Configure the scheduler:

```bash
* * * * * cd /path/to/School-SaaS-Enterprise/apps/api && php artisan schedule:run >> /dev/null 2>&1
```

## Web Deployment

Use `apps/web` as the Nuxt application root.

1. Configure environment:

```bash
NUXT_PUBLIC_API_BASE=https://api.yourdomain.com/api
```

2. Install and build:

```bash
npm ci
npm run build
```

3. Run with a Node process manager or deploy the built Nuxt server according to your host's Nuxt support.

## Backups

Create a full JSON backup:

```bash
php artisan school:backup
```

Create a single-school backup:

```bash
php artisan school:backup --school=1
```

Restore a backup after confirming the target database:

```bash
php artisan school:restore backups/school-saas-backup-all-YYYYMMDD-HHMMSS.json
```

Use `--force` only for automated restore jobs where confirmation is handled elsewhere:

```bash
php artisan school:restore backups/archive.json --force
```

## Operations Checklist

- Run `php artisan migrate --force` during each release.
- Keep queue workers supervised.
- Store `storage/app` on persistent disk.
- Back up MySQL and `storage/app` together.
- Rotate logs from `storage/logs`.
- Use HTTPS for the API and frontend.
- Keep `APP_KEY`, database credentials, mail credentials, and SMS credentials out of Git.
- Test restore procedures on a non-production database.
