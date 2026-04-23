export function useOfflineDraft<T>(storageKey: MaybeRefOrGetter<string>) {
  const hasDraft = ref(false)
  const savedAt = ref<string | null>(null)

  const key = () => toValue(storageKey)

  function refresh() {
    if (!import.meta.client) {
      hasDraft.value = false
      savedAt.value = null

      return
    }

    const raw = localStorage.getItem(key())

    if (!raw) {
      hasDraft.value = false
      savedAt.value = null

      return
    }

    try {
      const parsed = JSON.parse(raw) as { payload?: T; savedAt?: string }
      hasDraft.value = !!parsed.payload
      savedAt.value = parsed.savedAt || null
    } catch {
      hasDraft.value = false
      savedAt.value = null
    }
  }

  function load(): T | null {
    if (!import.meta.client) {
      return null
    }

    const raw = localStorage.getItem(key())

    if (!raw) {
      refresh()

      return null
    }

    try {
      const parsed = JSON.parse(raw) as { payload?: T; savedAt?: string }
      hasDraft.value = !!parsed.payload
      savedAt.value = parsed.savedAt || null

      return parsed.payload ?? null
    } catch {
      clear()

      return null
    }
  }

  function save(payload: T) {
    if (!import.meta.client) {
      return
    }

    const snapshot = {
      payload,
      savedAt: new Date().toISOString(),
    }

    localStorage.setItem(key(), JSON.stringify(snapshot))
    hasDraft.value = true
    savedAt.value = snapshot.savedAt
  }

  function clear() {
    if (!import.meta.client) {
      return
    }

    localStorage.removeItem(key())
    hasDraft.value = false
    savedAt.value = null
  }

  if (import.meta.client) {
    refresh()
  }

  return {
    hasDraft,
    savedAt,
    refresh,
    load,
    save,
    clear,
  }
}
