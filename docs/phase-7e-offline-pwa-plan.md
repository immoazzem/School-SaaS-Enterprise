# Phase 7E Offline Support / PWA Plan

**Last updated:** April 20, 2026  
**Active plan:** `docs/enterprise-plan-v3.md`  
**Current status:** PWA foundation and first IndexedDB queue slice implemented; richer conflict review remains planned.

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
- Shared `useOfflineQueue()` composable backed by IndexedDB.
- Shared `OfflineQueuePanel` component for queued, failed, and conflicted records.
- Attendance offline submissions are queued with endpoint, method, payload, attempts, and status metadata.
- Marks offline submissions are queued with endpoint, method, payload, attempts, and status metadata.
- Queues replay manually through "Sync now" and automatically when the browser returns online.
- Synced records are removed from IndexedDB; duplicate or validation failures stay visible as `conflict`.
- Failed network/API records stay visible as `failed` rather than being silently discarded.

## Implemented Queue Design

The current queue implementation:

- uses IndexedDB rather than `localStorage` for durable queued writes.
- stores queue entries with:
  - generated client UUID.
  - school id.
  - endpoint path.
  - HTTP method.
  - payload.
  - created timestamp.
  - last attempt timestamp.
  - attempt count.
  - sync status: `pending`, `syncing`, `conflict`, `failed`.

## Queue Hardening For Next Slice

- Stop replay and ask for login when the API returns `401`.
- Mark entries as conflict when the API returns `409` or validation errors caused by duplicate attendance/marks records.
- Show a sync review drawer before deleting local failed/conflicted records.
- Add richer local-vs-server comparison views for attendance and marks conflicts.
- Add tests around IndexedDB queue helpers where the Nuxt test harness is in place.

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
- Queue survives reload and browser restart. Implemented through IndexedDB.
- Queue replays when the connection returns. Implemented for non-conflicting records.
- Conflicts are visible and resolvable by a permitted user.
- Failed sync records are never silently discarded. Implemented.
- Service worker update flow is documented for self-hosted deployments.
