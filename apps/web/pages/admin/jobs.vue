<script setup lang="ts">
definePageMeta({ layout: 'default' })

interface JobFailure {
  id: number
  uuid?: string
  queue?: string
  failed_at?: string
  exception?: string
}

interface JobStatusResponse {
  data: {
    pending: number
    failed: number
    recent_failures: JobFailure[]
  }
}

const api = useApi()
const { canAccessEnterpriseAdmin } = useEnterpriseAccess()
const jobStatus = ref<JobStatusResponse['data'] | null>(null)
const loading = ref(false)
const retrying = ref<string | null>(null)
const error = ref('')

async function loadJobs() {
  if (!canAccessEnterpriseAdmin.value) {
    jobStatus.value = null
    error.value = ''
    return
  }

  loading.value = true
  error.value = ''
  try {
    const response = await api.request<JobStatusResponse>('/admin/jobs/status')
    jobStatus.value = response.data
  }
  catch (loadError) {
    error.value = loadError instanceof Error ? loadError.message : 'Unable to load job status.'
  }
  finally {
    loading.value = false
  }
}

async function retryJob(job: JobFailure) {
  if (!canAccessEnterpriseAdmin.value)
    return

  const id = job.uuid || String(job.id)
  retrying.value = id
  try {
    await api.request(`/admin/jobs/${id}/retry`, { method: 'POST' })
    await loadJobs()
  }
  finally {
    retrying.value = null
  }
}

onMounted(loadJobs)
</script>

<template>
  <div>
    <SchoolPageHeader eyebrow="Enterprise" title="Admin jobs" subtitle="Watch queue pressure, inspect failures, and retry recent failed jobs." />
    <VAlert
      v-if="!canAccessEnterpriseAdmin"
      type="info"
      variant="tonal"
      class="mb-4"
    >
      Queue retry and failure inspection are limited to enterprise super admins.
    </VAlert>
    <VAlert v-else-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</VAlert>
    <VRow class="mb-2">
      <VCol cols="12" md="6"><SchoolMetricCard title="Pending jobs" :value="String(jobStatus?.pending ?? 0)" delta="" tone="primary" icon="tabler-clock" note="Current queue depth" /></VCol>
      <VCol cols="12" md="6"><SchoolMetricCard title="Failed jobs" :value="String(jobStatus?.failed ?? 0)" delta="" tone="error" icon="tabler-alert-triangle" note="Recent failure volume" /></VCol>
    </VRow>
    <VCard class="school-signal-card">
      <VCardText class="pt-2">
        <VTable class="school-data-table">
          <thead><tr><th>Queue</th><th>Failed at</th><th>Exception</th><th>Action</th></tr></thead>
          <tbody>
            <tr v-for="failure in jobStatus?.recent_failures || []" :key="failure.uuid || failure.id">
              <td>{{ failure.queue || 'default' }}</td>
              <td>{{ failure.failed_at || '-' }}</td>
              <td>{{ failure.exception || '-' }}</td>
              <td><VBtn size="small" color="primary" :loading="retrying === (failure.uuid || String(failure.id))" @click="retryJob(failure)">Retry</VBtn></td>
            </tr>
            <tr v-if="!(jobStatus?.recent_failures?.length) && !loading"><td colspan="4">{{ canAccessEnterpriseAdmin ? 'No recent failures.' : 'Enterprise access required.' }}</td></tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </div>
</template>
