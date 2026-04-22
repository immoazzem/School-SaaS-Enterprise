<script setup lang="ts">
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

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

const settings = reactive<SchoolSettingsPayload>({
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

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.request<SchoolSettingsResponse>(`/schools/${schoolId.value}/settings`)
    Object.assign(settings, response.data)
  }
  catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load school settings.'
  }
  finally {
    loading.value = false
  }
}

async function saveSettings() {
  saving.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api.request<SchoolSettingsResponse>(`/schools/${schoolId.value}/settings`, {
      method: 'PATCH',
      body: { ...settings },
    })
    Object.assign(settings, response.data)
    success.value = 'School settings saved.'
  }
  catch (saveError) {
    error.value = saveError instanceof Error ? saveError.message : 'Unable to save school settings.'
  }
  finally {
    saving.value = false
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
        :school-id="schoolId"
        aria-label="School settings navigation"
        context-title="Configuration links"
        :context-links="[
          { label: 'Notifications', to: `/schools/${schoolId}/notifications` },
          { label: 'Reports', to: `/schools/${schoolId}/reports` },
          { label: 'Finance', to: `/schools/${schoolId}/finance` },
        ]"
      />
    </template>

    <header class="workspace-header">
      <div>
        <p class="eyebrow">Finance</p>
        <h1>School settings</h1>
        <p>Configure portal access, billing defaults, attendance thresholds, and reporting metadata.</p>
      </div>
      <div class="header-actions">
        <VBtn color="primary" :loading="saving" @click="saveSettings">Save settings</VBtn>
      </div>
    </header>

    <VAlert v-if="error" type="error" variant="tonal">{{ error }}</VAlert>
    <VAlert v-if="success" type="success" variant="tonal">{{ success }}</VAlert>

    <section class="workspace-grid">
      <section class="surface record-form">
        <h2>Core rules</h2>
        <div class="form-row">
          <div class="field">
            <label>Timezone</label>
            <input v-model="settings.timezone" type="text" />
          </div>
          <div class="field">
            <label>Locale</label>
            <input v-model="settings.locale" type="text" />
          </div>
        </div>
        <div class="form-row">
          <div class="field">
            <label>Currency</label>
            <input v-model="settings.currency" type="text" />
          </div>
          <div class="field">
            <label>Invoice prefix</label>
            <input v-model="settings.fee_invoice_prefix" type="text" />
          </div>
        </div>
        <div class="form-row">
          <div class="field">
            <label>Academic year start month</label>
            <input v-model="settings.academic_year_start_month" type="number" min="1" max="12" />
          </div>
          <div class="field">
            <label>Attendance warning threshold</label>
            <input v-model="settings.attendance_warning_threshold_percent" type="number" min="1" max="100" />
          </div>
        </div>
      </section>

      <section class="surface record-form">
        <h2>Portals and communication</h2>
        <div class="field">
          <label>SMS provider</label>
          <input v-model="settings.sms_provider" type="text" />
        </div>
        <div class="field">
          <label>SMS API key</label>
          <input v-model="settings.sms_api_key" type="text" />
        </div>
        <div class="field">
          <label>PDF footer text</label>
          <input v-model="settings.pdf_footer_text" type="text" />
        </div>
        <div class="form-row">
          <label><input v-model="settings.sms_enabled" type="checkbox" /> SMS enabled</label>
          <label><input v-model="settings.allow_parent_portal" type="checkbox" /> Parent portal enabled</label>
          <label><input v-model="settings.allow_student_portal" type="checkbox" /> Student portal enabled</label>
        </div>
      </section>
    </section>
  </SchoolWorkspaceTemplate>
</template>
