<script setup lang="ts">
import { dashboardPathForSchool } from '~/lib/accessControl'

const session = useSession()
const targetPath = computed(() => dashboardPathForSchool(session.selectedSchool.value))

onMounted(async () => {
  await navigateTo(targetPath.value, { replace: true })
})
</script>

<template>
  <div class="pa-6 text-body-2 text-medium-emphasis">
    Opening your role dashboard...
  </div>
</template>
