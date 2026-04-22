<script setup lang="ts">
const props = defineProps<{
  title: string
  value: string
  delta: string
  tone?: string
  icon: string
  note: string
}>()

const tone = computed(() => props.tone ?? 'primary')
</script>

<template>
  <VCard class="school-signal-card school-metric-card h-100">
    <VCardText class="pa-5">
      <div class="d-flex align-start justify-space-between mb-5">
        <div class="pe-3">
          <div class="school-kicker mb-2">
            {{ title }}
          </div>
          <div class="school-metric-card__value">
            {{ value }}
          </div>
        </div>

        <VAvatar
          :color="tone"
          variant="tonal"
          rounded="lg"
          size="42"
        >
          <VIcon :icon="icon" size="20" />
        </VAvatar>
      </div>

      <div class="d-flex align-center justify-space-between gap-3">
        <VChip
          :color="delta.startsWith('-') ? 'error' : 'success'"
          variant="tonal"
          size="small"
        >
          {{ delta }}
        </VChip>
        <span class="text-body-2 text-medium-emphasis">{{ note }}</span>
      </div>
    </VCardText>
  </VCard>
</template>
