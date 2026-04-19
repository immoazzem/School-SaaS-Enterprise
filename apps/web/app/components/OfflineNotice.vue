<script setup lang="ts">
const props = withDefaults(defineProps<{
  context: string
  hasDraft?: boolean
  savedAt?: string | null
}>(), {
  hasDraft: false,
  savedAt: null,
})

const { isOnline, statusLabel } = useNetworkStatus()

const shouldShow = computed(() => !isOnline.value || props.hasDraft)

const savedAtLabel = computed(() => {
  if (!props.savedAt) {
    return ''
  }

  return new Intl.DateTimeFormat('en', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(props.savedAt))
})
</script>

<template>
  <section v-if="shouldShow" class="offline-notice" :class="{ offline: !isOnline }">
    <div>
      <p class="eyebrow">{{ statusLabel }}</p>
      <h2>{{ context }}</h2>
      <p>
        <span v-if="!isOnline">Writes are saved as local drafts until the API is reachable again.</span>
        <span v-else-if="hasDraft">A saved local draft is available on this device.</span>
      </p>
      <p v-if="savedAtLabel" class="saved-at">Last local draft: {{ savedAtLabel }}</p>
    </div>
    <div class="notice-actions">
      <slot />
    </div>
  </section>
</template>

<style scoped>
.offline-notice {
  display: flex;
  gap: 16px;
  align-items: center;
  justify-content: space-between;
  border: 1px solid #d8e0dc;
  border-radius: 8px;
  padding: 16px;
  background: #edf7f0;
  color: #111827;
}

.offline-notice.offline {
  border-color: #f0d2ca;
  background: #fff1f0;
}

.offline-notice h2,
.offline-notice p {
  margin: 0;
}

.offline-notice h2 {
  margin-bottom: 5px;
  font-size: 1.05rem;
}

.eyebrow {
  margin-bottom: 6px;
  color: #be3455;
  font-size: 0.76rem;
  font-weight: 900;
  letter-spacing: 0;
  text-transform: uppercase;
}

.saved-at {
  margin-top: 6px;
  color: #6b7280;
  font-size: 0.9rem;
  font-weight: 700;
}

.notice-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

@media (max-width: 760px) {
  .offline-notice {
    align-items: stretch;
    flex-direction: column;
  }

  .notice-actions {
    justify-content: flex-start;
  }
}
</style>
