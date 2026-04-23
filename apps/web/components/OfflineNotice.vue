<script setup lang="ts">
defineProps<{
  context: string
  hasDraft: boolean
  savedAt?: string | null
}>()
</script>

<template>
  <section class="surface record-list">
    <div class="list-header">
      <div>
        <p class="eyebrow">{{ context }}</p>
        <h2>Offline draft</h2>
      </div>
      <span class="muted">
        {{ hasDraft ? `Saved ${savedAt || 'recently'}` : 'No draft saved' }}
      </span>
    </div>

    <div class="mini-list">
      <span>
        <strong>{{ hasDraft ? 'Draft available' : 'Nothing stored locally' }}</strong>
        <em>{{ hasDraft ? 'You can restore or clear the saved device draft.' : 'A local draft appears here once saved.' }}</em>
      </span>
    </div>

    <div class="form-actions">
      <slot />
    </div>
  </section>
</template>
