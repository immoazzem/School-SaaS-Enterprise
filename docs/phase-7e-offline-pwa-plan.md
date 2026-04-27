# Phase 7E Offline Support / PWA Plan

**Last updated:** April 20, 2026  
**Active plan:** `docs/enterprise-plan-v3.md`  
**Current status:** PWA foundation and first durable browser queue slice implemented; conflict/auth recovery is now covered by browser QA.

## Scope

Phase 7E exists for schools with unreliable connectivity. The first slice keeps the app installable, caches the key SPA workspaces, and prevents data-entry loss on the two most sensitive pages:

- Attendance
- Marks entry

This now includes visible queued write replay for Attendance and Marks. The next hardening slice should improve conflict resolution, permission expiry handling, and user-visible recovery for failed records.

## Implemented Foundation

- Nuxt PWA module configured through `@vite-pwa/nuxt`.
- App manifest configured with product name, theme colors, start URL, and app icon.
- Service worker generated during production build.
- Network-first runtime cache for `/schools/**/attendance` and `/schools/**/marks`.
- Shared `useNetworkStatus()` composable for online/offline state.
- Shared `useOfflineDraft()` composable for local device drafts.
- `OfflineNotice` component for visible draft/offline state.
- Attendance form can save, restore, and clear a local draft.
- Marks form can save, restore, and clear a local draft.
- Attendance and Marks save actions preserve a draft instead of attempting API writes while offline.
- Shared `useOfflineQueue()` composable backed by durable browser storage.
- Shared `OfflineQueuePanel` component for queued, failed, and conflicted records.
- Attendance offline submissions are queued with endpoint, method, payload, attempts, and status metadata.
- Marks offline submissions are queued with endpoint, method, payload, attempts, and status metadata.
- Queues replay manually through "Sync now" and automatically when the browser returns online.
- Synced records are removed from the queue; duplicate or validation failures stay visible as `conflict`.
- Failed network/API records stay visible as `failed` rather than being silently discarded.
- API `401` responses now mark the current record as `auth_required` and stop replay so stale sessions do not keep sending queued writes.
- Queue sync returns a summary to the page so users see whether records synced, failed, conflicted, or need a fresh login.
- The queue panel shows per-status counts, friendly status labels, attempt counts, and retained error messages.
- The queue panel shows local payload review for failed, conflicted, and auth-required records.
- Conflict/failed records can be marked ready for retry after review.
- Auth-required records expose a one-click sign-in-again path that clears the local session and returns to the current workspace after login.
- `npm run qa:offline-queue` verifies the auth-required/conflict review, retry, and sign-in-again flow in a real browser.

## Implemented Queue Design

The current queue implementation:

- uses namespaced `localStorage` for durable queued writes. IndexedDB remains the preferred future storage when queues expand beyond Attendance and Marks or start carrying larger payloads.
- stores queue entries with:
  - generated client UUID.
  - school id.
  - endpoint path.
  - HTTP method.
  - payload.
  - created timestamp.
  - last attempt timestamp.
  - attempt count.
  - sync status: `pending`, `syncing`, `conflict`, `failed`, `auth_required`.

## Queue Hardening For Next Slice

- Move the queue from localStorage to IndexedDB before large multi-form offline capture is enabled.
- Add server lookup endpoints for richer local-vs-server comparison views for attendance and marks conflicts.
- Add a sync review drawer before deleting local failed/conflicted records.
- Add unit coverage around queue helper classification when the Nuxt test harness is in place.
- Extend browser queue QA to Marks once seeded exam/class-subject combinations are stable across environments.

## Service Worker Deployment Notes

- Local development keeps service-worker registration disabled and emits a self-destroying worker unless `NUXT_ENABLE_PWA=true`.
- Production PWA deployments should set `NUXT_ENABLE_PWA=true` only when the deploy target serves hashed Nuxt assets with correct cache headers.
- After a deploy that changes asset hosting or cache policy, verify `/sw.js`, hard-refresh once in a normal browser, and confirm stale `_nuxt` assets are not being served.
- If users report a white page after a release, temporarily deploy with PWA disabled so the self-destroying worker unregisters stale local workers, then re-enable PWA after the affected clients recover.

## Conflict Rules

- Attendance: one record per enrollment/date should remain authoritative. If a server record already exists, show local vs server values and let the user update or discard.
- Marks: one marks entry per exam/class subject/enrollment should remain authoritative. If a duplicate exists, show local vs server values and require a reviewer decision.
- Never silently overwrite verified marks.

## Security Notes

- Local drafts currently live in browser storage on the same device and should be treated as convenience recovery, not secure long-term storage.
- Do not store payment secrets, gateway credentials, or full financial exports offline.
- Future IndexedDB queue should avoid storing unnecessary student PII beyond the payload required for the pending write.

## Acceptance Criteria For Full Phase 7E

- Offline attendance and marks submissions enter a visible queue. Implemented.
- Queue survives reload and browser restart. Implemented through durable browser storage.
- Queue replays when the connection returns. Implemented for non-conflicting records.
- Conflicts are visible with local payload review and retry/discard controls. Server comparison remains planned.
- Failed sync records are never silently discarded. Implemented.
- Service worker update flow is documented for self-hosted deployments.
