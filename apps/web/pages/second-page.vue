<script setup lang="ts">
onMounted(async () => {
  await navigateTo('/dashboard', { replace: true })
})
</script>

<template>
  <div class="pa-6 text-body-2 text-medium-emphasis">
    Opening dashboard...
  </div>
</template>
