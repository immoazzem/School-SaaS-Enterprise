# Known Build Warnings

**Last updated:** April 19, 2026  
**Current checkpoint:** Production Stabilization Checkpoint B  
**Command:** `cd apps/web && npm run build 2>&1 | Tee-Object -FilePath build-output.log`

## Current Result

`npm run build` exits `0`.

Tailwind is now loaded from the project CSS entry:

```text
[nuxt:tailwindcss] Using Tailwind CSS from ~/assets/css/main.css
```

This confirms the Nuxt Tailwind module is no longer injecting its default CSS file.

## Classified Warnings

| Warning | Severity | Source | Decision |
|---|---|---|---|
| Duplicated imports `useAppConfig`; `nitropack/runtime/internal/config` ignored in favor of `@nuxt/nitro-server/dist/runtime/utils/app-config` | Defer | Nuxt/Nitro generated runtime imports | No app source imports `useAppConfig`. Keep classified and revisit when Nuxt/Nitro versions change. |
| `[plugin nuxt:module-preload-polyfill] Sourcemap is likely to be incorrect` | Defer | Nuxt/Vite module preload transform | Production build artifacts complete successfully. This affects sourcemap fidelity, not runtime behavior. Revisit when Nuxt/Vite updates land. |
| Node `[DEP0155]` deprecated trailing slash pattern mapping from `@vue/shared`, imported by `@nuxt/nitro-server` | Environment | Node 25 runtime warning against upstream package exports | Project is locked to Node `20.11.0` through `apps/web/.nvmrc` and package `engines`. Current Codex shell reports Node `v25.0.0`, which is stricter than the project target. Recheck under Node 20.11.0 locally. |

## Install Warnings

`npm install` completed successfully after aligning Pinia with Vue Router 5:

- `pinia`: `^3.0.4`
- `@pinia/nuxt`: `^0.11.3`

Transitive install deprecation warnings observed:

| Warning | Severity | Source | Decision |
|---|---|---|---|
| `inflight@1.0.6` deprecated | Defer | Transitive dependency | Not directly imported by the app. Monitor through dependency updates. |
| `@koa/router@12.0.2` deprecated | Defer | Transitive dependency | Not directly imported by the app. Monitor Nuxt/dev tooling dependency updates. |
| `glob@7.2.3` deprecated | Defer | Transitive dependency | Not directly imported by the app. Monitor through dependency updates. |

## Fixed During Checkpoint B

- Added Tailwind module configuration with `cssPath: '~/assets/css/main.css'`.
- Removed separate Nuxt `css` injection to avoid double-loading the same CSS entry.
- Added Tailwind directives to `apps/web/app/assets/css/main.css`.
- Added `apps/web/tailwind.config.ts`.
- Added Pinia/Nuxt module dependencies and Node engine metadata.
- Added `apps/web/.nvmrc` with `20.11.0`.

