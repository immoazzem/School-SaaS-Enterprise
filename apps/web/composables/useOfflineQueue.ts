type OfflineQueueStatus = 'pending' | 'syncing' | 'failed' | 'conflict' | 'auth_required'

type OfflineQueueEntry = {
  id: string
  label: string
  schoolId: number
  method: string
  path: string
  payload: Record<string, unknown>
  status: OfflineQueueStatus
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

type QueueStoreRecord = {
  namespace: string
  entries: OfflineQueueEntry[]
  updatedAt: string
}

const DB_NAME = 'school-saas-offline'
const DB_VERSION = 1
const STORE_NAME = 'queues'

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

function requestToPromise<T>(request: IDBRequest<T>): Promise<T> {
  return new Promise((resolve, reject) => {
    request.onsuccess = () => resolve(request.result)
    request.onerror = () => reject(request.error ?? new Error('IndexedDB request failed.'))
  })
}

function transactionDone(transaction: IDBTransaction): Promise<void> {
  return new Promise((resolve, reject) => {
    transaction.oncomplete = () => resolve()
    transaction.onerror = () => reject(transaction.error ?? new Error('IndexedDB transaction failed.'))
    transaction.onabort = () => reject(transaction.error ?? new Error('IndexedDB transaction aborted.'))
  })
}

async function openQueueDb(): Promise<IDBDatabase> {
  if (!import.meta.client || !('indexedDB' in window)) {
    throw new Error('IndexedDB is not available.')
  }

  const request = indexedDB.open(DB_NAME, DB_VERSION)

  request.onupgradeneeded = () => {
    const db = request.result

    if (!db.objectStoreNames.contains(STORE_NAME)) {
      db.createObjectStore(STORE_NAME, { keyPath: 'namespace' })
    }
  }

  return await requestToPromise(request)
}

async function readIndexedQueue(namespace: string): Promise<OfflineQueueEntry[] | null> {
  const db = await openQueueDb()

  try {
    const transaction = db.transaction(STORE_NAME, 'readonly')
    const record = await requestToPromise<QueueStoreRecord | undefined>(
      transaction.objectStore(STORE_NAME).get(namespace),
    )
    await transactionDone(transaction)

    return record?.entries?.map(normalizeEntry) ?? null
  } finally {
    db.close()
  }
}

async function writeIndexedQueue(namespace: string, entries: OfflineQueueEntry[]): Promise<void> {
  const db = await openQueueDb()

  try {
    const transaction = db.transaction(STORE_NAME, 'readwrite')
    transaction.objectStore(STORE_NAME).put({
      namespace,
      entries,
      updatedAt: new Date().toISOString(),
    } satisfies QueueStoreRecord)
    await transactionDone(transaction)
  } finally {
    db.close()
  }
}

function readLegacyQueue(storageKey: string): OfflineQueueEntry[] | null {
  const raw = localStorage.getItem(storageKey)

  if (!raw) {
    return null
  }

  try {
    return (JSON.parse(raw) as OfflineQueueEntry[]).map(normalizeEntry)
  } catch {
    localStorage.removeItem(storageKey)

    return null
  }
}

export function useOfflineQueue(namespace: string) {
  const entries = ref<OfflineQueueEntry[]>([])
  const syncing = ref(false)
  const storageDriver = ref<'indexeddb' | 'localstorage' | 'memory'>('memory')

  const storageKey = `school-saas:offline-queue:${namespace}`

  async function persist(nextEntries: OfflineQueueEntry[]) {
    entries.value = nextEntries

    if (!import.meta.client) {
      return
    }

    try {
      await writeIndexedQueue(namespace, nextEntries)
      storageDriver.value = 'indexeddb'
      localStorage.removeItem(storageKey)
    } catch {
      storageDriver.value = 'localstorage'
      localStorage.setItem(storageKey, JSON.stringify(nextEntries))
    }
  }

  async function refresh() {
    if (!import.meta.client) {
      entries.value = []

      return
    }

    const legacyEntries = readLegacyQueue(storageKey)

    try {
      const indexedEntries = await readIndexedQueue(namespace)
      const nextEntries = indexedEntries ?? legacyEntries ?? []

      entries.value = nextEntries
      storageDriver.value = 'indexeddb'

      if (legacyEntries && !indexedEntries) {
        await persist(legacyEntries)
      } else if (legacyEntries) {
        localStorage.removeItem(storageKey)
      }
    } catch {
      storageDriver.value = 'localstorage'
      entries.value = legacyEntries ?? []
    }
  }

  async function enqueue(input: Omit<OfflineQueueEntry, 'id' | 'status' | 'errorMessage' | 'createdAt' | 'updatedAt' | 'attempts' | 'lastAttemptAt'>) {
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

    await persist([nextEntry, ...entries.value])
  }

  async function remove(id: string) {
    await persist(entries.value.filter(entry => entry.id !== id))
  }

  async function markPending(id: string) {
    const now = new Date().toISOString()
    await persist(entries.value.map(entry => entry.id === id
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

    try {
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
        await persist([...nextEntries])
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

      await persist(nextEntries)
    } finally {
      syncing.value = false
    }

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
    storageDriver,
    refresh,
    enqueue,
    remove,
    markPending,
    syncEntries,
  }
}
