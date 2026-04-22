<script setup lang="ts">
definePageMeta({
  layout: 'default',
})

interface HealthResponse {
  data: {
    status: string
    database: string
    checked_at: string
  }
}

interface StatsResponse {
  data: {
    schools: number
    active_schools: number
    users: number
    audit_logs: number
  }
}

interface JobResponse {
  data: {
    pending: number
    failed: number
  }
}

const api = useApi()
const { canAccessEnterpriseAdmin } = useEnterpriseAccess()
const health = ref<HealthResponse['data'] | null>(null)
const stats = ref<StatsResponse['data'] | null>(null)
const jobs = ref<JobResponse['data'] | null>(null)
const loading = ref(false)
const error = ref('')

async function loadAdmin() {
  if (!canAccessEnterpriseAdmin.value) {
    health.value = null
    stats.value = null
    jobs.value = null
    error.value = ''
    return
  }

  loading.value = true
  error.value = ''
  try {
    const [healthResponse, statsResponse, jobsResponse] = await Promise.all([
      api.request<HealthResponse>('/admin/system/health'),
      api.request<StatsResponse>('/admin/system/stats'),
      api.request<JobResponse>('/admin/jobs/status'),
    ])
    health.value = healthResponse.data
    stats.value = statsResponse.data
    jobs.value = jobsResponse.data
  }
  catch (adminError) {
    error.value = adminError instanceof Error ? adminError.message : 'Unable to load enterprise admin overview.'
  }
  finally {
    loading.value = false
  }
}

onMounted(loadAdmin)
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Enterprise"
      title="Admin control"
      subtitle="Portfolio-wide health, queue status, and the management routes that sit above a single school."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-building-school" to="/admin/schools">Schools</VBtn>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-users" to="/admin/users">Users</VBtn>
        <VBtn color="primary" prepend-icon="tabler-clock" to="/admin/jobs">Jobs</VBtn>
      </template>
    </SchoolPageHeader>

    <VAlert
      v-if="!canAccessEnterpriseAdmin"
      type="info"
      variant="tonal"
      class="mb-4"
    >
      Enterprise administration is available only to portfolio super admins. This account can keep working inside school workspaces.
    </VAlert>
    <VAlert v-else-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</VAlert>

    <VRow class="mb-2">
      <VCol cols="12" md="3">
        <SchoolMetricCard title="System status" :value="health?.status || (loading ? 'Loading' : 'Unavailable')" delta="" tone="success" icon="tabler-shield-check" note="Database {{ health?.database || 'unknown' }}" />
      </VCol>
      <VCol cols="12" md="3">
        <SchoolMetricCard title="Schools" :value="String(stats?.schools ?? 0)" delta="" tone="primary" icon="tabler-building-community" note="Active {{ stats?.active_schools ?? 0 }}" />
      </VCol>
      <VCol cols="12" md="3">
        <SchoolMetricCard title="Users" :value="String(stats?.users ?? 0)" delta="" tone="warning" icon="tabler-users" note="Portfolio accounts" />
      </VCol>
      <VCol cols="12" md="3">
        <SchoolMetricCard title="Queue" :value="String(jobs?.pending ?? 0)" delta="" tone="info" icon="tabler-clock" note="Failed {{ jobs?.failed ?? 0 }}" />
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" md="6">
        <VCard class="school-signal-card h-100">
          <VCardText class="pa-5">
            <div class="school-kicker mb-3">Enterprise routes</div>
            <div class="school-alert-list">
              <div class="school-alert-list__item"><div><div class="font-weight-medium">School administration</div><div class="text-body-2 text-medium-emphasis">Review plans, status, onboarding, and live tenant footprint.</div></div><VBtn size="small" variant="text" to="/admin/schools">Open</VBtn></div>
              <div class="school-alert-list__item"><div><div class="font-weight-medium">User administration</div><div class="text-body-2 text-medium-emphasis">Inspect the account base and multi-school role spread.</div></div><VBtn size="small" variant="text" to="/admin/users">Open</VBtn></div>
              <div class="school-alert-list__item"><div><div class="font-weight-medium">Audit and jobs</div><div class="text-body-2 text-medium-emphasis">Trace failures and portfolio-level change activity.</div></div><VBtn size="small" variant="text" to="/admin/audit-logs">Open</VBtn></div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard class="school-signal-card h-100">
          <VCardText class="pa-5">
            <div class="school-kicker mb-3">Current state</div>
            <div class="text-body-2 text-medium-emphasis mb-3">Checked at {{ health?.checked_at || 'not available' }}</div>
              <div class="text-body-2 text-medium-emphasis">
                <template v-if="canAccessEnterpriseAdmin">
                  This surface is ready for enterprise operators and reflects portfolio-wide controls.
                </template>
                <template v-else>
                  This account is intentionally scoped below enterprise administration, so portfolio controls stay read-blocked.
                </template>
              </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
