# Phase 7E Offline Support / PWA Plan

**Last updated:** April 20, 2026  
**Active plan:** `docs/enterprise-plan-v3.md`  
**Current status:** Foundation slice implemented; full sync queue remains planned.

## Scope

Phase 7E exists for schools with unreliable connectivity. The first slice keeps the app installable, caches the key SPA workspaces, and prevents data-entry loss on the two most sensitive pages:

- Attendance
- Marks entry

This does not yet implement automatic queued write replay. That needs conflict handling, permission expiry handling, and user-visible sync review before it is safe for production.

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

## Queue Design For Next Slice

The next implementation should move from drafts to a real write queue:

- Use IndexedDB rather than `localStorage` for durable queued writes.
- Store queue entries with:
  - generated client UUID.
  - school id.
  - endpoint path.
  - HTTP method.
  - payload.
  - created timestamp.
  - last attempt timestamp.
  - attempt count.
  - sync status: `draft`, `queued`, `syncing`, `synced`, `conflict`, `failed`.
- Replay only when:
  - browser is online.
  - auth token exists.
  - selected school has the required permission.
- Stop replay and ask for login when the API returns `401`.
- Mark entries as conflict when the API returns `409` or validation errors caused by duplicate attendance/marks records.
- Show a sync review drawer before deleting local failed/conflicted records.

## Conflict Rules

- Attendance: one record per enrollment/date should remain authoritative. If a server record already exists, show local vs server values and let the user update or discard.
- Marks: one marks entry per exam/class subject/enrollment should remain authoritative. If a duplicate exists, show local vs server values and require a reviewer decision.
- Never silently overwrite verified marks.

## Security Notes

- Local drafts currently live in browser storage on the same device and should be treated as convenience recovery, not secure long-term storage.
- Do not store payment secrets, gateway credentials, or full financial exports offline.
- Future IndexedDB queue should avoid storing unnecessary student PII beyond the payload required for the pending write.

## Acceptance Criteria For Full Phase 7E

- Offline attendance and marks submissions enter a visible queue.
- Queue survives reload and browser restart.
- Queue replays when the connection returns.
- Conflicts are visible and resolvable by a permitted user.
- Failed sync records are never silently discarded.
- Service worker update flow is documented for self-hosted deployments.
