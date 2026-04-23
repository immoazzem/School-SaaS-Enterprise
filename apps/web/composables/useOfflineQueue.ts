type OfflineQueueEntry = {
  id: string
  label: string
  schoolId: number
  method: string
  path: string
  payload: Record<string, unknown>
  status: 'pending' | 'failed' | 'conflict'
  errorMessage: string | null
  createdAt: string
  updatedAt: string
}

type OfflineQueueSummary = {
  synced: number
  failed: number
  conflicts: number
  authRequired: boolean
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
      entries.value = JSON.parse(raw) as OfflineQueueEntry[]
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
      errorMessage: null,
      createdAt: timestamp,
      updatedAt: timestamp,
    }

    persist([nextEntry, ...entries.value])
  }

  function remove(id: string) {
    persist(entries.value.filter(entry => entry.id !== id))
  }

  async function syncEntries(
    predicate: (entry: OfflineQueueEntry) => boolean,
    handler: (entry: OfflineQueueEntry) => Promise<void>,
  ): Promise<OfflineQueueSummary> {
    syncing.value = true

    let synced = 0
    let failed = 0
    let conflicts = 0
    let authRequired = false

    const nextEntries = [...entries.value]

    for (let index = 0; index < nextEntries.length; index += 1) {
      const entry = nextEntries[index]

      if (!predicate(entry)) {
        continue
      }

      try {
        await handler(entry)
        nextEntries.splice(index, 1)
        index -= 1
        synced += 1
      } catch (error) {
        const message = error instanceof Error ? error.message : 'Offline sync failed.'
        const normalized = message.toLowerCase()
        const now = new Date().toISOString()

        if (normalized.includes('401') || normalized.includes('419') || normalized.includes('unauth')) {
          authRequired = true
          nextEntries[index] = {
            ...entry,
            status: 'failed',
            errorMessage: message,
            updatedAt: now,
          }
          failed += 1
        } else if (normalized.includes('409') || normalized.includes('422') || normalized.includes('conflict')) {
          nextEntries[index] = {
            ...entry,
            status: 'conflict',
            errorMessage: message,
            updatedAt: now,
          }
          conflicts += 1
        } else {
          nextEntries[index] = {
            ...entry,
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
    syncEntries,
  }
}
