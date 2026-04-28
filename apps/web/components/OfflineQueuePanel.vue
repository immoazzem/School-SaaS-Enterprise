<script setup lang="ts">
type QueueEntry = {
  id: string
  label: string
  status: 'pending' | 'syncing' | 'failed' | 'conflict' | 'auth_required'
  attempts: number
  errorMessage: string | null
  updatedAt: string
  lastAttemptAt: string | null
  payload: Record<string, unknown>
  serverSnapshot?: Record<string, unknown> | null
}

const props = defineProps<{
  entries: QueueEntry[]
  syncing: boolean
}>()

const emit = defineEmits<{
  sync: []
  discard: [id: string]
  retry: [id: string]
  signIn: []
}>()

const statusLabels: Record<QueueEntry['status'], string> = {
  pending: 'Ready',
  syncing: 'Syncing',
  failed: 'Failed',
  conflict: 'Needs review',
  auth_required: 'Sign in required',
}

const counts = computed(() => props.entries.reduce(
  (carry, entry) => {
    carry[entry.status] += 1

    return carry
  },
  {
    pending: 0,
    syncing: 0,
    failed: 0,
    conflict: 0,
    auth_required: 0,
  } as Record<QueueEntry['status'], number>,
))

function payloadPreview(payload: Record<string, unknown>) {
  return JSON.stringify(payload, null, 2)
}
</script>

<template>
  <section class="surface record-list">
    <div class="list-header">
      <div>
        <p class="eyebrow">Offline queue</p>
        <h2>Pending sync items</h2>
        <p v-if="entries.length" class="muted queue-summary">
          {{ counts.pending }} ready / {{ counts.failed }} failed / {{ counts.conflict }} need review / {{ counts.auth_required }} need sign in
        </p>
      </div>
      <div class="form-actions">
        <VBtn
          v-if="counts.auth_required"
          color="primary"
          variant="flat"
          size="small"
          @click="emit('signIn')"
        >
          Sign in again
        </VBtn>
        <VBtn color="default" variant="outlined" size="small" :loading="syncing" @click="emit('sync')">
          Sync queue
        </VBtn>
      </div>
    </div>

    <div v-if="entries.length" class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Status</th>
            <th>Updated</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="entry in entries" :key="entry.id">
            <td>
              <strong>{{ entry.label }}</strong>
              <div class="muted">{{ entry.errorMessage || 'Ready to sync.' }}</div>
              <details v-if="entry.status === 'conflict' || entry.status === 'failed' || entry.status === 'auth_required'" class="queue-review">
                <summary>Review local payload</summary>
                <pre>{{ payloadPreview(entry.payload) }}</pre>
              </details>
              <details v-if="entry.serverSnapshot" class="queue-review">
                <summary>Compare server record</summary>
                <pre>{{ payloadPreview(entry.serverSnapshot) }}</pre>
              </details>
            </td>
            <td>
              <span class="status-pill" :class="entry.status">{{ statusLabels[entry.status] }}</span>
              <span class="muted">Attempt {{ entry.attempts }}</span>
            </td>
            <td>
              <span>{{ entry.updatedAt }}</span>
              <span v-if="entry.lastAttemptAt" class="muted">Last attempt {{ entry.lastAttemptAt }}</span>
            </td>
            <td>
              <VBtn
                v-if="entry.status === 'failed' || entry.status === 'conflict'"
                color="default"
                variant="text"
                size="small"
                @click="emit('retry', entry.id)"
              >
                Retry
              </VBtn>
              <VBtn color="default" variant="text" size="small" @click="emit('discard', entry.id)">
                Discard
              </VBtn>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else class="muted">
      No offline queue items for this workspace.
    </div>
  </section>
</template>

<style scoped>
.queue-summary {
  margin-top: 0.35rem;
}

.queue-review {
  margin-top: 0.65rem;
}

.queue-review summary {
  cursor: pointer;
  font-size: 0.78rem;
  font-weight: 700;
}

.queue-review pre {
  max-width: min(36rem, 100%);
  margin: 0.5rem 0 0;
  overflow: auto;
  border: 1px solid rgba(15, 23, 42, 0.1);
  border-radius: 8px;
  padding: 0.75rem;
  background: rgba(15, 23, 42, 0.035);
  font-size: 0.76rem;
  line-height: 1.45;
  white-space: pre-wrap;
}
</style>
