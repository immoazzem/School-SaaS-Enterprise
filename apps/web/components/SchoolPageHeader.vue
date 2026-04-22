<script setup lang="ts">
defineProps<{
  eyebrow: string
  title: string
  subtitle: string
}>()
</script>

<template>
  <div class="school-page-header d-flex flex-wrap align-end justify-space-between gap-4 mb-6">
    <div class="school-page-header__content">
      <div class="school-kicker mb-2">
        {{ eyebrow }}
      </div>
      <h1 class="text-h4 font-weight-bold mb-1">
        {{ title }}
      </h1>
      <p class="text-body-1 text-medium-emphasis mb-0">
        {{ subtitle }}
      </p>
    </div>

    <div class="school-page-header__actions d-flex flex-wrap align-center gap-3">
      <slot name="actions" />
    </div>
  </div>
</template>
