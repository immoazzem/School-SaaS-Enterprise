# School SaaS Enterprise

Enterprise-grade rebuild of the legacy Laravel School Management app.

## Current Phase

Phase 0: legacy audit and implementation documentation.

## Important References

- Full plan: `docs/enterprise-plan.md`
- Current status: `docs/current-status.md`
- Legacy app reference: `legacy-reference/`
- Radiant design source: `D:\Development\tailwindui-radiant\radiant-ts`

## Stack Decisions

- Backend: Laravel 13.x, PHP 8.5, MySQL, Sanctum SPA auth
- Frontend: Nuxt 4.4.x, Vue 3, TypeScript, Tailwind CSS 4
- Local runtime: Laravel Herd for API, Nuxt dev server for web
- Tenancy: multi-school from day one
- Security priority: RBAC, tenant isolation, audit logs

