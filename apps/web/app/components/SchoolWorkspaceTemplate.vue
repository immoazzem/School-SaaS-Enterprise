<script setup lang="ts">
const props = withDefaults(defineProps<{
  contentClass?: string
}>(), {
  contentClass: '',
})

const contentClasses = computed(() => ['workspace-stage', props.contentClass])
</script>

<template>
  <main class="workspace-shell">
    <slot name="navigation" />
    <section :class="contentClasses">
      <slot />
    </section>
  </main>
</template>
