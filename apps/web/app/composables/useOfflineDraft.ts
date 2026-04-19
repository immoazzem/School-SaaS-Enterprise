import type { MaybeRefOrGetter } from 'vue'

export interface OfflineDraftEnvelope<T> {
  data: T
  savedAt: string
}

export function useOfflineDraft<T>(key: MaybeRefOrGetter<string>) {
  const hasDraft = ref(false)
  const savedAt = ref<string | null>(null)

  function storageKey() {
    return toValue(key)
  }

  function readEnvelope(): OfflineDraftEnvelope<T> | null {
    if (!import.meta.client) {
      return null
    }

    const rawDraft = localStorage.getItem(storageKey())

    if (!rawDraft) {
      hasDraft.value = false
      savedAt.value = null

      return null
    }

    try {
      const envelope = JSON.parse(rawDraft) as OfflineDraftEnvelope<T>
      hasDraft.value = true
      savedAt.value = envelope.savedAt

      return envelope
    } catch {
      localStorage.removeItem(storageKey())
      hasDraft.value = false
      savedAt.value = null

      return null
    }
  }

  function load(): T | null {
    return readEnvelope()?.data ?? null
  }

  function save(data: T) {
    if (!import.meta.client) {
      return
    }

    const envelope: OfflineDraftEnvelope<T> = {
      data,
      savedAt: new Date().toISOString(),
    }

    localStorage.setItem(storageKey(), JSON.stringify(envelope))
    hasDraft.value = true
    savedAt.value = envelope.savedAt
  }

  function clear() {
    if (!import.meta.client) {
      return
    }

    localStorage.removeItem(storageKey())
    hasDraft.value = false
    savedAt.value = null
  }

  onMounted(() => {
    readEnvelope()
  })

  return {
    clear,
    hasDraft,
    load,
    save,
    savedAt,
  }
}
