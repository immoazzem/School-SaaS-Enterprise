type OfflineQueueEntry = {
  id: string
  label: string
  schoolId: number
  method: string
  path: string
  payload: Record<string, unknown>
  status: 'pending' | 'syncing' | 'failed' | 'conflict' | 'auth_required'
  attempts: number
  errorMessage: string | null
  createdAt: string
  updatedAt: string
  lastAttemptAt: string | null
}

type OfflineQueueSummary = {
  attempted: number
  synced: number
  failed: number
  conflicts: number
  authRequired: number
}

function normalizeEntry(entry: OfflineQueueEntry): OfflineQueueEntry {
  return {
    ...entry,
    status: entry.status || 'pending',
    attempts: entry.attempts || 0,
    errorMessage: entry.errorMessage ?? null,
    lastAttemptAt: entry.lastAttemptAt ?? null,
  }
}

function isAuthError(message: string) {
  const normalized = message.toLowerCase()

  return normalized.includes('401')
    || normalized.includes('419')
    || normalized.includes('unauth')
    || normalized.includes('csrf')
}

function isConflictError(message: string) {
  const normalized = message.toLowerCase()

  return normalized.includes('409')
    || normalized.includes('422')
    || normalized.includes('conflict')
    || normalized.includes('already')
    || normalized.includes('duplicate')
}

export function useOfflineQueue(namespace: string) {
  const entries = ref<OfflineQueueEntry[]>([])
  const syncing = ref(false)

  const storageKey = `school-saas:offline-queue:${namespace}`

  function persist(nextEntries: OfflineQueueEntry[]) {
    entries.value = nextEntries

    if (!import.meta.client) {
      return
    }

    localStorage.setItem(storageKey, JSON.stringify(nextEntries))
  }

  function refresh() {
    if (!import.meta.client) {
      entries.value = []

      return
    }

    const raw = localStorage.getItem(storageKey)

    if (!raw) {
      entries.value = []

      return
    }

    try {
      entries.value = (JSON.parse(raw) as OfflineQueueEntry[]).map(normalizeEntry)
    } catch {
      entries.value = []
      localStorage.removeItem(storageKey)
    }
  }

  async function enqueue(input: Omit<OfflineQueueEntry, 'id' | 'status' | 'errorMessage' | 'createdAt' | 'updatedAt'>) {
    const timestamp = new Date().toISOString()
    const nextEntry: OfflineQueueEntry = {
      ...input,
      id: `${namespace}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
      status: 'pending',
      attempts: 0,
      errorMessage: null,
      createdAt: timestamp,
      updatedAt: timestamp,
      lastAttemptAt: null,
    }

    persist([nextEntry, ...entries.value])
  }

  function remove(id: string) {
    persist(entries.value.filter(entry => entry.id !== id))
  }

  function markPending(id: string) {
    const now = new Date().toISOString()
    persist(entries.value.map(entry => entry.id === id
      ? {
          ...entry,
          status: 'pending',
          errorMessage: null,
          updatedAt: now,
        }
      : entry,
    ))
  }

  async function syncEntries(
    predicate: (entry: OfflineQueueEntry) => boolean,
    handler: (entry: OfflineQueueEntry) => Promise<void>,
  ): Promise<OfflineQueueSummary> {
    syncing.value = true

    let attempted = 0
    let synced = 0
    let failed = 0
    let conflicts = 0
    let authRequired = 0

    const nextEntries = [...entries.value]

    for (let index = 0; index < nextEntries.length; index += 1) {
      const entry = nextEntries[index]

      if (!predicate(entry) || entry.status === 'conflict' || entry.status === 'auth_required') {
        continue
      }

      const attemptStartedAt = new Date().toISOString()
      const syncingEntry: OfflineQueueEntry = {
        ...entry,
        status: 'syncing',
        attempts: entry.attempts + 1,
        updatedAt: attemptStartedAt,
        lastAttemptAt: attemptStartedAt,
      }

      nextEntries[index] = syncingEntry
      persist([...nextEntries])
      attempted += 1

      try {
        await handler(syncingEntry)
        nextEntries.splice(index, 1)
        index -= 1
        synced += 1
      } catch (error) {
        const message = error instanceof Error ? error.message : 'Offline sync failed.'
        const now = new Date().toISOString()

        if (isAuthError(message)) {
          authRequired += 1
          nextEntries[index] = {
            ...syncingEntry,
            status: 'auth_required',
            errorMessage: message,
            updatedAt: now,
          }

          break
        } else if (isConflictError(message)) {
          nextEntries[index] = {
            ...syncingEntry,
            status: 'conflict',
            errorMessage: message,
            updatedAt: now,
          }
          conflicts += 1
        } else {
          nextEntries[index] = {
            ...syncingEntry,
            status: 'failed',
            errorMessage: message,
            updatedAt: now,
          }
          failed += 1
        }
      }
    }

    persist(nextEntries)
    syncing.value = false

    return {
      attempted,
      synced,
      failed,
      conflicts,
      authRequired,
    }
  }

  if (import.meta.client) {
    refresh()
  }

  return {
    entries,
    syncing,
    refresh,
    enqueue,
    remove,
    markPending,
    syncEntries,
  }
}
