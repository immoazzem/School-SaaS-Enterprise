<script setup lang="ts">
type QueueEntry = {
  id: string
  label: string
  status: 'pending' | 'failed' | 'conflict'
  errorMessage: string | null
  updatedAt: string
}

defineProps<{
  entries: QueueEntry[]
  syncing: boolean
}>()

const emit = defineEmits<{
  sync: []
  discard: [id: string]
}>()
</script>

<template>
  <section class="surface record-list">
    <div class="list-header">
      <div>
        <p class="eyebrow">Offline queue</p>
        <h2>Pending sync items</h2>
      </div>
      <div class="form-actions">
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
            </td>
            <td>{{ entry.status }}</td>
            <td>{{ entry.updatedAt }}</td>
            <td>
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
