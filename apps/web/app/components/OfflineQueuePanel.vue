<script setup lang="ts">
import type { OfflineQueueEntry } from '~/composables/useOfflineQueue'

defineProps<{
  entries: OfflineQueueEntry[]
  syncing: boolean
}>()

const emit = defineEmits<{
  sync: []
  discard: [id: string]
}>()
</script>

<template>
  <section v-if="entries.length" class="queue-panel">
    <div class="queue-header">
      <div>
        <p class="eyebrow">Offline queue</p>
        <h2>{{ entries.length }} pending item{{ entries.length === 1 ? '' : 's' }}</h2>
      </div>
      <button class="button secondary compact" type="button" :disabled="syncing" @click="emit('sync')">
        {{ syncing ? 'Syncing' : 'Sync now' }}
      </button>
    </div>

    <div class="queue-list">
      <article v-for="entry in entries" :key="entry.id" class="queue-item" :class="entry.status">
        <div>
          <strong>{{ entry.label }}</strong>
          <span>{{ entry.status }} / {{ entry.method }} / {{ entry.createdAt }}</span>
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
