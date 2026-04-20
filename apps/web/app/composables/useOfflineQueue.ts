export type OfflineQueueStatus = 'pending' | 'syncing' | 'synced' | 'conflict' | 'failed'

export interface OfflineQueueEntry<TPayload = Record<string, unknown>> {
  id: string
  schoolId: number
  queue: string
  label: string
  method: 'POST' | 'PATCH' | 'DELETE'
  path: string
  payload: TPayload
  status: OfflineQueueStatus
  attempts: number
  createdAt: string
  updatedAt: string
  lastAttemptAt: string | null
  error: string | null
}

interface QueueInput<TPayload> {
  schoolId: number
  label: string
  method: OfflineQueueEntry<TPayload>['method']
  path: string
  payload: TPayload
}

const dbName = 'school-saas-offline'
const storeName = 'queue'
const dbVersion = 1
let dbPromise: Promise<IDBDatabase> | null = null

function createId() {
  if (import.meta.client && 'crypto' in window && 'randomUUID' in window.crypto) {
    return window.crypto.randomUUID()
  }

  return `offline-${Date.now()}-${Math.random().toString(16).slice(2)}`
}

function openQueueDb(): Promise<IDBDatabase> {
  if (!import.meta.client) {
    return Promise.reject(new Error('Offline queue is only available in the browser.'))
  }

  if (dbPromise) {
    return dbPromise
  }

  dbPromise = new Promise((resolve, reject) => {
    const request = indexedDB.open(dbName, dbVersion)

    request.onupgradeneeded = () => {
      const db = request.result

      if (!db.objectStoreNames.contains(storeName)) {
        db.createObjectStore(storeName, { keyPath: 'id' })
      }
    }

    request.onsuccess = () => resolve(request.result)
    request.onerror = () => reject(request.error ?? new Error('Unable to open offline queue.'))
  })

  return dbPromise
}

async function withStore<T>(mode: IDBTransactionMode, callback: (store: IDBObjectStore) => IDBRequest<T>): Promise<T> {
  const db = await openQueueDb()

  return await new Promise((resolve, reject) => {
    const transaction = db.transaction(storeName, mode)
    const request = callback(transaction.objectStore(storeName))

    request.onsuccess = () => resolve(request.result)
    request.onerror = () => reject(request.error ?? new Error('Offline queue request failed.'))
    transaction.onerror = () => reject(transaction.error ?? new Error('Offline queue transaction failed.'))
  })
}

function errorMessage(error: unknown) {
  if (error instanceof Error) {
    return error.message
  }

  if (typeof error === 'object' && error && 'statusMessage' in error) {
    return String(error.statusMessage)
  }

  return 'Unable to sync queued record.'
}

function classifyFailure(error: unknown): OfflineQueueStatus {
  if (typeof error === 'object' && error && 'status' in error) {
    const status = Number(error.status)

    if (status === 409 || status === 422) {
      return 'conflict'
    }
  }

  return 'failed'
}

function plainEntry(entry: OfflineQueueEntry): OfflineQueueEntry {
  return JSON.parse(JSON.stringify(entry)) as OfflineQueueEntry
}

export function useOfflineQueue(queue: string) {
  const entries = ref<OfflineQueueEntry[]>([])
  const syncing = ref(false)
  const lastSyncedAt = ref<string | null>(null)

  const pendingCount = computed(() =>
    entries.value.filter((entry) => entry.queue === queue && ['pending', 'failed', 'conflict'].includes(entry.status)).length,
  )

  async function refresh() {
    if (!import.meta.client) {
      return
    }

    const rows = await withStore<OfflineQueueEntry[]>('readonly', (store) => store.getAll())
    entries.value = rows
      .filter((entry) => entry.queue === queue)
      .sort((a, b) => a.createdAt.localeCompare(b.createdAt))
  }

  async function put(entry: OfflineQueueEntry) {
    await withStore<IDBValidKey>('readwrite', (store) => store.put(plainEntry(entry)))
    await refresh()
  }

  async function enqueue<TPayload extends Record<string, unknown>>(input: QueueInput<TPayload>) {
    const now = new Date().toISOString()
    const entry: OfflineQueueEntry<TPayload> = {
      id: createId(),
      schoolId: input.schoolId,
      queue,
      label: input.label,
      method: input.method,
      path: input.path,
      payload: input.payload,
      status: 'pending',
      attempts: 0,
      createdAt: now,
      updatedAt: now,
      lastAttemptAt: null,
      error: null,
    }

    await put(entry)

    return entry
  }

  async function remove(id: string) {
    await withStore<undefined>('readwrite', (store) => store.delete(id))
    await refresh()
  }

  async function clearSynced() {
    await refresh()

    const syncedIds = entries.value
      .filter((entry) => entry.queue === queue && entry.status === 'synced')
      .map((entry) => entry.id)

    await Promise.all(syncedIds.map((id) => remove(id)))
  }

  async function syncEntries(
    predicate: (entry: OfflineQueueEntry) => boolean,
    handler: (entry: OfflineQueueEntry) => Promise<void>,
  ) {
    if (syncing.value) {
      return
    }

    syncing.value = true

    try {
      await refresh()

      const candidates = entries.value.filter((entry) =>
        entry.queue === queue
        && ['pending', 'failed'].includes(entry.status)
        && predicate(entry),
      )

      for (const entry of candidates) {
        const now = new Date().toISOString()

        await put({
          ...entry,
          status: 'syncing',
          attempts: entry.attempts + 1,
          lastAttemptAt: now,
          updatedAt: now,
          error: null,
        })

        try {
          await handler(entry)
          await remove(entry.id)
        } catch (syncError) {
          await put({
            ...entry,
            status: classifyFailure(syncError),
            attempts: entry.attempts + 1,
            lastAttemptAt: now,
            updatedAt: new Date().toISOString(),
            error: errorMessage(syncError),
          })
        }
      }

      lastSyncedAt.value = new Date().toISOString()
    } finally {
      syncing.value = false
      await refresh()
    }
  }

  onMounted(refresh)

  return {
    clearSynced,
    enqueue,
    entries,
    pendingCount,
    refresh,
    remove,
    syncEntries,
    syncing,
    lastSyncedAt,
  }
}
