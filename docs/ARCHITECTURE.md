# Architecture

**Last updated:** April 19, 2026  
**Active plan:** `docs/enterprise-plan-v3.md`

## Application Shape

School SaaS Enterprise is a local-first rebuild of the legacy Laravel school management app into a multi-school SaaS.

- `apps/api`: Laravel 13 API, PHP 8.5 target, Sanctum token auth, MySQL, tenant-scoped school modules.
- `apps/web`: Nuxt 4 SPA, Vue 3, TypeScript, Radiant-inspired UI, centralized API composable.
- `legacy-reference`: read-only legacy app reference.

## API Version Strategy

All current application API routes are versioned under `/api/v1`.

Versioning is route-prefix based:

```php
Route::prefix('v1')->group(function (): void {
    // auth, admin, and school-scoped routes
});
```

The frontend keeps `NUXT_PUBLIC_API_BASE` at the `/api` level and appends `/v1` inside `apps/web/app/composables/useApi.ts`. Individual pages and composables should continue calling paths such as `/auth/login`, `/me`, and `/schools`.

## Compatibility Policy

- v1 allows additive changes that do not break existing clients.
- Breaking changes require a future `/api/v2` route group.
- After a v2 release, v1 should remain supported for at least six months.
- Deprecations must be documented in `docs/api-contract.md` and the engineering log before removal.

