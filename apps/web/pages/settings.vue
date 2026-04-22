<script setup lang="ts">
definePageMeta({
  layout: 'default',
})

interface SchoolSettingsPayload {
  timezone: string
  locale: string
  currency: string
  academic_year_start_month: number
  date_format: string
  sms_enabled: boolean
  sms_provider: string | null
  sms_api_key: string | null
  attendance_warning_threshold_percent: number
  fee_invoice_prefix: string
  result_grade_scale_id: number | null
  allow_parent_portal: boolean
  allow_student_portal: boolean
  pdf_header_logo: string | null
  pdf_footer_text: string | null
}

interface SchoolSettingsResponse {
  data: SchoolSettingsPayload
}

interface SystemHealthResponse {
  data: {
    status: string
    database: string
    checked_at: string
  }
}

interface SystemStatsResponse {
  data: {
    schools: number
    active_schools: number
    users: number
    audit_logs: number
  }
}

interface JobFailure {
  id: number
  uuid?: string
  connection?: string
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

interface AuditLogItem {
  id: number
  event: string
  created_at?: string
  actor?: {
    name?: string
    email?: string
  } | null
  school?: {
    name?: string
    slug?: string
  } | null
}

interface PaginatedResponse<T> {
  data: T[]
  meta?: {
    total?: number
  }
}

const auth = useAuth()
const api = useApi()

const loading = ref(false)
const saving = ref(false)
const retrying = ref<string | null>(null)
const errorMessage = ref('')
const successMessage = ref('')

const schoolSettings = reactive<SchoolSettingsPayload>({
  timezone: 'Asia/Dhaka',
  locale: 'en',
  currency: 'BDT',
  academic_year_start_month: 1,
  date_format: 'Y-m-d',
  sms_enabled: false,
  sms_provider: null,
  sms_api_key: null,
  attendance_warning_threshold_percent: 75,
  fee_invoice_prefix: 'INV',
  result_grade_scale_id: null,
  allow_parent_portal: true,
  allow_student_portal: true,
  pdf_header_logo: null,
  pdf_footer_text: null,
})

const systemHealth = ref<SystemHealthResponse['data'] | null>(null)
const systemStats = ref<SystemStatsResponse['data'] | null>(null)
const jobStatus = ref<JobStatusResponse['data'] | null>(null)
const auditLogs = ref<AuditLogItem[]>([])

const selectedSchool = computed(() => auth.selectedSchool.value)
const canManageSchool = computed(() => auth.can('schools.manage'))
const hasEnterpriseAdminAccess = computed(() =>
  auth.schools.value.some(school => school.roles?.some(role => role.key === 'super-admin')),
)
const hasAdminAccess = computed(() => systemHealth.value !== null || systemStats.value !== null || jobStatus.value !== null || auditLogs.value.length > 0)

function messageFromError(error: unknown, fallback: string) {
  if (error && typeof error === 'object') {
    const maybeData = (error as { data?: { message?: string } }).data
    if (maybeData?.message) {
      return maybeData.message
    }
  }

  return error instanceof Error ? error.message : fallback
}

async function tryLoadAdminSurface() {
  if (!hasEnterpriseAdminAccess.value) {
    systemHealth.value = null
    systemStats.value = null
    jobStatus.value = null
    auditLogs.value = []
    return
  }

  try {
    const [healthResponse, statsResponse, jobsResponse, auditResponse] = await Promise.all([
      api.request<SystemHealthResponse>('/admin/system/health'),
      api.request<SystemStatsResponse>('/admin/system/stats'),
      api.request<JobStatusResponse>('/admin/jobs/status'),
      api.request<PaginatedResponse<AuditLogItem>>('/admin/audit-logs?per_page=8'),
    ])

    systemHealth.value = healthResponse.data
    systemStats.value = statsResponse.data
    jobStatus.value = jobsResponse.data
    auditLogs.value = auditResponse.data
  }
  catch {
    systemHealth.value = null
    systemStats.value = null
    jobStatus.value = null
    auditLogs.value = []
  }
}

async function loadSettings() {
  loading.value = true
  errorMessage.value = ''

  try {
    await auth.refreshProfile()

    if (selectedSchool.value && canManageSchool.value) {
      const response = await api.request<SchoolSettingsResponse>(`/schools/${selectedSchool.value.id}/settings`)
      Object.assign(schoolSettings, response.data)
    }

    await tryLoadAdminSurface()
  }
  catch (error) {
    errorMessage.value = messageFromError(error, 'Unable to load settings right now.')
  }
  finally {
    loading.value = false
  }
}

async function saveSettings() {
  if (!selectedSchool.value) {
    return
  }

  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.request<SchoolSettingsResponse>(`/schools/${selectedSchool.value.id}/settings`, {
      method: 'PATCH',
      body: {
        ...schoolSettings,
      },
    })

    Object.assign(schoolSettings, response.data)
    successMessage.value = 'School settings saved.'
  }
  catch (error) {
    errorMessage.value = messageFromError(error, 'Unable to save school settings.')
  }
  finally {
    saving.value = false
  }
}

async function retryFailedJob(job: JobFailure) {
  const identifier = job.uuid || String(job.id)
  retrying.value = identifier
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await api.request(`/admin/jobs/${identifier}/retry`, { method: 'POST' })
    successMessage.value = 'Failed job queued for retry.'
    await tryLoadAdminSurface()
  }
  catch (error) {
    errorMessage.value = messageFromError(error, 'Unable to retry the failed job.')
  }
  finally {
    retrying.value = null
  }
}

const settingsCards = computed(() => [
  {
    title: 'Timezone',
    value: schoolSettings.timezone,
    note: 'Used for attendance, payroll, and due-date calculations.',
  },
  {
    title: 'Currency',
    value: schoolSettings.currency,
    note: 'Applied across fee structures, invoices, and finance reports.',
  },
  {
    title: 'Attendance warning',
    value: `${schoolSettings.attendance_warning_threshold_percent}%`,
    note: 'Threshold that drives low-attendance alerts and operator follow-up.',
  },
])

onMounted(loadSettings)
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="System"
      title="Platform settings"
      subtitle="Use the active school for operational settings and expose enterprise controls whenever the current account is allowed to reach them."
    >
      <template #actions>
        <VBtn
          v-if="selectedSchool"
          variant="outlined"
          color="default"
          prepend-icon="tabler-building"
          :to="`/schools/${selectedSchool.id}/reports`"
        >
          Open school reports
        </VBtn>
        <VBtn
          v-if="selectedSchool && canManageSchool"
          color="primary"
          prepend-icon="tabler-device-floppy"
          :loading="saving"
          @click="saveSettings"
        >
          Save settings
        </VBtn>
      </template>
    </SchoolPageHeader>

    <VAlert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      class="mb-4"
    >
      {{ errorMessage }}
    </VAlert>

    <VAlert
      v-if="successMessage"
      type="success"
      variant="tonal"
      class="mb-4"
    >
      {{ successMessage }}
    </VAlert>

    <VAlert
      v-if="!selectedSchool"
      type="info"
      variant="tonal"
      class="mb-4"
    >
      Select a school from the portfolio before trying to manage school settings.
    </VAlert>

    <VRow class="mb-2">
      <VCol
        v-for="card in settingsCards"
        :key="card.title"
        cols="12"
        md="4"
      >
        <VCard class="school-signal-card h-100">
          <VCardText class="pa-5">
            <div class="school-kicker mb-3">
              School setting
            </div>
            <div class="school-metric-card__value text-h6 mb-2">{{ card.value }}</div>
            <div class="text-h6 font-weight-bold mb-2">{{ card.title }}</div>
            <div class="text-body-2 text-medium-emphasis">{{ card.note }}</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" xl="7">
        <VCard class="school-signal-card">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Active school settings
            </VCardTitle>
            <VCardSubtitle>
              Operational defaults for the current school context.
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <VAlert
              v-if="selectedSchool && !canManageSchool"
              type="info"
              variant="tonal"
              class="mb-4"
            >
              This account can see the school context but does not currently have school management permission.
            </VAlert>

            <VForm>
              <VRow>
                <VCol cols="12" md="6">
                  <AppTextField v-model="schoolSettings.timezone" label="Timezone" :disabled="!canManageSchool" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="schoolSettings.locale" label="Locale" :disabled="!canManageSchool" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="schoolSettings.currency" label="Currency" :disabled="!canManageSchool" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="schoolSettings.fee_invoice_prefix" label="Invoice prefix" :disabled="!canManageSchool" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="schoolSettings.academic_year_start_month" type="number" label="Academic year start month" :disabled="!canManageSchool" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="schoolSettings.attendance_warning_threshold_percent" type="number" label="Attendance warning threshold" :disabled="!canManageSchool" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="schoolSettings.sms_provider" label="SMS provider" :disabled="!canManageSchool" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="schoolSettings.sms_api_key" label="SMS API key" :disabled="!canManageSchool" />
                </VCol>
                <VCol cols="12">
                  <AppTextField v-model="schoolSettings.pdf_footer_text" label="PDF footer text" :disabled="!canManageSchool" />
                </VCol>
                <VCol cols="12" md="6">
                  <VSwitch v-model="schoolSettings.sms_enabled" label="SMS enabled" :disabled="!canManageSchool" />
                </VCol>
                <VCol cols="12" md="6">
                  <VSwitch v-model="schoolSettings.allow_parent_portal" label="Parent portal enabled" :disabled="!canManageSchool" />
                </VCol>
                <VCol cols="12" md="6">
                  <VSwitch v-model="schoolSettings.allow_student_portal" label="Student portal enabled" :disabled="!canManageSchool" />
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" xl="5">
        <VCard class="school-signal-card mb-4">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Enterprise status
            </VCardTitle>
            <VCardSubtitle>
              Live system visibility when the signed-in account can reach the admin surface.
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <VAlert
              v-if="!hasAdminAccess"
              type="info"
              variant="tonal"
            >
              Admin-level health, jobs, and audit visibility will appear here for accounts that can access the enterprise surface.
            </VAlert>

            <div v-else class="school-alert-list">
              <div class="school-alert-list__item">
                <div>
                  <div class="font-weight-medium">System health</div>
                  <div class="text-body-2 text-medium-emphasis">Database: {{ systemHealth?.database }} · checked {{ systemHealth?.checked_at }}</div>
                </div>
                <VChip size="small" color="success" variant="tonal">
                  {{ systemHealth?.status }}
                </VChip>
              </div>
              <div class="school-alert-list__item">
                <div>
                  <div class="font-weight-medium">Active schools</div>
                  <div class="text-body-2 text-medium-emphasis">{{ systemStats?.active_schools }} of {{ systemStats?.schools }} total</div>
                </div>
              </div>
              <div class="school-alert-list__item">
                <div>
                  <div class="font-weight-medium">Users and audits</div>
                  <div class="text-body-2 text-medium-emphasis">{{ systemStats?.users }} users · {{ systemStats?.audit_logs }} audit logs</div>
                </div>
              </div>
              <div class="school-alert-list__item">
                <div>
                  <div class="font-weight-medium">Queue pressure</div>
                  <div class="text-body-2 text-medium-emphasis">{{ jobStatus?.pending }} pending · {{ jobStatus?.failed }} failed</div>
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>

        <VCard
          v-if="jobStatus?.recent_failures?.length"
          class="school-signal-card"
        >
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Failed jobs
            </VCardTitle>
            <VCardSubtitle>
              The most recent failures visible from the queue surface.
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <div class="school-alert-list">
              <div
                v-for="failure in jobStatus.recent_failures"
                :key="failure.uuid || failure.id"
                class="school-alert-list__item"
              >
                <div>
                  <div class="font-weight-medium">{{ failure.queue || 'default' }} queue</div>
                  <div class="text-body-2 text-medium-emphasis">{{ failure.failed_at }}</div>
                </div>
                <VBtn
                  size="small"
                  variant="text"
                  color="primary"
                  :loading="retrying === (failure.uuid || String(failure.id))"
                  @click="retryFailedJob(failure)"
                >
                  Retry
                </VBtn>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow v-if="auditLogs.length" class="mt-0">
      <VCol cols="12">
        <VCard class="school-signal-card">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Audit trail
            </VCardTitle>
            <VCardSubtitle>
              Latest enterprise-level changes visible to the current account.
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <VTable class="school-data-table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th>School</th>
                  <th>Actor</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in auditLogs" :key="log.id">
                  <td class="font-weight-medium">{{ log.event }}</td>
                  <td>{{ log.school?.name || 'System' }}</td>
                  <td>{{ log.actor?.name || log.actor?.email || 'System' }}</td>
                  <td>{{ log.created_at || 'Just now' }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
