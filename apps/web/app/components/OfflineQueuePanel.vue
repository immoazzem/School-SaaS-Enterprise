<script setup lang="ts">
import type { OfflineQueueEntry } from '~/composables/useOfflineQueue'

const props = defineProps<{
  entries: OfflineQueueEntry[]
  syncing: boolean
}>()

const emit = defineEmits<{
  sync: []
  discard: [id: string]
}>()

const queueCounts = computed(() => ({
  pending: props.entries.filter((entry) => entry.status === 'pending').length,
  failed: props.entries.filter((entry) => entry.status === 'failed').length,
  conflict: props.entries.filter((entry) => entry.status === 'conflict').length,
  authRequired: props.entries.filter((entry) => entry.status === 'auth_required').length,
}))

function statusLabel(entry: OfflineQueueEntry) {
  const labels: Record<OfflineQueueEntry['status'], string> = {
    pending: 'Ready to sync',
    syncing: 'Syncing',
    synced: 'Synced',
    conflict: 'Needs review',
    failed: 'Sync failed',
    auth_required: 'Sign in required',
  }

  return labels[entry.status]
}

function formattedDate(value: string) {
  return new Intl.DateTimeFormat('en', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}
</script>

<template>
  <section v-if="entries.length" class="queue-panel">
    <div class="queue-header">
      <div>
        <p class="eyebrow">Offline queue</p>
        <h2>{{ entries.length }} local item{{ entries.length === 1 ? '' : 's' }}</h2>
        <p class="queue-summary">
          {{ queueCounts.pending }} ready
          <span v-if="queueCounts.failed">/ {{ queueCounts.failed }} failed</span>
          <span v-if="queueCounts.conflict">/ {{ queueCounts.conflict }} needs review</span>
          <span v-if="queueCounts.authRequired">/ sign in required</span>
        </p>
      </div>
      <button class="button secondary compact" type="button" :disabled="syncing" @click="emit('sync')">
        {{ syncing ? 'Syncing' : 'Sync now' }}
      </button>
    </div>

    <div class="queue-list">
      <article v-for="entry in entries" :key="entry.id" class="queue-item" :class="entry.status">
        <div>
          <strong>{{ entry.label }}</strong>
          <span>{{ statusLabel(entry) }} / {{ entry.method }} / {{ formattedDate(entry.createdAt) }}</span>
          <span v-if="entry.attempts">Attempts: {{ entry.attempts }}</span>
          <span v-if="entry.error">{{ entry.error }}</span>
        </div>
        <button class="link-button danger" type="button" @click="emit('discard', entry.id)">Discard</button>
      </article>
    </div>
  </section>
</template>

<style scoped>
.queue-panel {
  display: grid;
  gap: 12px;
  border: 1px solid #d8e0dc;
  border-radius: 8px;
  padding: 16px;
  background: #fff;
}

.queue-header {
  display: flex;
  gap: 14px;
  align-items: center;
  justify-content: space-between;
}

.queue-header h2 {
  margin: 0;
  color: #111827;
  font-size: 1.05rem;
}

.queue-summary {
  margin: 6px 0 0;
  color: #5f6f68;
  font-size: 0.86rem;
  font-weight: 800;
}

.eyebrow {
  margin: 0 0 6px;
  color: #be3455;
  font-size: 0.76rem;
  font-weight: 900;
  letter-spacing: 0;
  text-transform: uppercase;
}

.queue-list {
  display: grid;
  gap: 8px;
}

.queue-item {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid #e3ebe7;
  padding-top: 10px;
}

.queue-item strong,
.queue-item span {
  display: block;
}

.queue-item span {
  margin-top: 4px;
  color: #6b7280;
  font-size: 0.88rem;
  font-weight: 700;
}

.queue-item.auth_required,
.queue-item.conflict,
.queue-item.failed {
  border-left: 3px solid #a83b32;
  padding-left: 10px;
}

.queue-item.auth_required span:last-child,
.queue-item.conflict span:last-child,
.queue-item.failed span:last-child {
  color: #a83b32;
}

.button.compact {
  min-height: 36px;
  padding: 0 12px;
}

.link-button {
  border: 0;
  background: transparent;
  color: #be3455;
  font-weight: 900;
  cursor: pointer;
}

.link-button.danger {
  color: #a83b32;
}

@media (max-width: 760px) {
  .queue-header,
  .queue-item {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
