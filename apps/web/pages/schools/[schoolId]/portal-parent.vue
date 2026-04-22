<script setup lang="ts">
interface ChildItem {
  id: number
  full_name?: string
  admission_no?: string
  enrollments?: Array<{
    id?: number
    academic_class?: { name?: string } | null
    academic_year?: { name?: string } | null
  }>
}

interface AttendanceRecord { id: number; attendance_date?: string; status?: string }
interface ResultItem { id: number; percentage?: string; grade?: string | null; exam?: { name?: string } | null }
interface InvoiceItem { id: number; invoice_no?: string; total?: string; paid_amount?: string; status?: string }
interface NotificationItem { id: number; title?: string | null; body?: string | null; type?: string }
interface DataResponse<T> { data: T[] }
interface ListResponse<T> { data: T[] }

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const children = ref<ChildItem[]>([])
const notifications = ref<NotificationItem[]>([])
const selectedEnrollmentId = ref<number | null>(null)
const attendance = ref<AttendanceRecord[]>([])
const results = ref<ResultItem[]>([])
const invoices = ref<InvoiceItem[]>([])
const loading = ref(false)
const error = ref('')

const selectedChild = computed(() =>
  children.value.find(child => child.enrollments?.some(enrollment => enrollment.id === selectedEnrollmentId.value)) ?? null,
)

const enrollmentOptions = computed(() =>
  children.value.flatMap(child =>
    (child.enrollments || []).map(enrollment => ({
      id: enrollment.id,
      label: `${child.full_name || 'Child'} / ${enrollment.academic_class?.name || 'Class'} / ${enrollment.academic_year?.name || 'Year'}`,
    })),
  ),
)

async function loadParentWorkspace() {
  loading.value = true
  error.value = ''
  try {
    const [childrenResponse, notificationResponse] = await Promise.all([
      api.request<DataResponse<ChildItem>>(`/schools/${schoolId.value}/portal/parent/children`),
      api.request<ListResponse<NotificationItem>>(`/schools/${schoolId.value}/portal/parent/notifications?per_page=20`),
    ])
    children.value = childrenResponse.data
    notifications.value = notificationResponse.data
    selectedEnrollmentId.value = selectedEnrollmentId.value ?? children.value[0]?.enrollments?.[0]?.id ?? null
    await loadSelectedChildFeeds()
  }
  catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load parent portal view.'
  }
  finally {
    loading.value = false
  }
}

async function loadSelectedChildFeeds() {
  if (!selectedEnrollmentId.value)
    return

  const [attendanceResponse, resultsResponse, invoicesResponse] = await Promise.all([
    api.request<ListResponse<AttendanceRecord>>(`/schools/${schoolId.value}/portal/parent/children/${selectedEnrollmentId.value}/attendance?per_page=20`),
    api.request<ListResponse<ResultItem>>(`/schools/${schoolId.value}/portal/parent/children/${selectedEnrollmentId.value}/results?per_page=20`),
    api.request<ListResponse<InvoiceItem>>(`/schools/${schoolId.value}/portal/parent/children/${selectedEnrollmentId.value}/invoices?per_page=20`),
  ])
  attendance.value = attendanceResponse.data
  results.value = resultsResponse.data
  invoices.value = invoicesResponse.data
}

watch(selectedEnrollmentId, loadSelectedChildFeeds)
onMounted(loadParentWorkspace)
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
        :school-id="schoolId"
        aria-label="Parent portal navigation"
        context-title="Portal links"
        :context-links="[
          { label: 'Student Portal', to: `/schools/${schoolId}/portal-student` },
          { label: 'School Settings', to: `/schools/${schoolId}/settings` },
          { label: 'Notifications', to: `/schools/${schoolId}/notifications` },
        ]"
      />
    </template>

    <header class="workspace-header">
      <div>
        <p class="eyebrow">Finance</p>
        <h1>Parent portal view</h1>
        <p>Preview the guardian-facing experience for child progress, attendance, invoices, and alerts.</p>
      </div>
    </header>

    <VAlert v-if="error" type="error" variant="tonal">{{ error }}</VAlert>

    <section class="workspace-grid">
      <section class="surface record-form">
        <h2>Selected child</h2>
        <div class="field">
          <label>Enrollment</label>
          <select v-model="selectedEnrollmentId">
            <option v-for="option in enrollmentOptions" :key="option.id" :value="option.id">
              {{ option.label }}
            </option>
          </select>
        </div>
        <div class="muted">Current child: {{ selectedChild?.full_name || 'Not available' }}</div>
      </section>

      <section class="surface record-list">
        <div class="list-header"><h2>Guardian notifications</h2><span class="muted">{{ notifications.length }} items</span></div>
        <div class="table-wrap">
          <table>
            <tbody>
              <tr v-for="item in notifications" :key="item.id">
                <td>
                  <strong>{{ item.title || item.type || 'Parent message' }}</strong>
                  <div class="muted">{{ item.body || '-' }}</div>
                </td>
              </tr>
              <tr v-if="!notifications.length && !loading"><td>No parent notifications.</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>

    <section class="summary-grid">
      <article class="surface summary-item"><span>Attendance records</span><strong>{{ attendance.length }}</strong></article>
      <article class="surface summary-item"><span>Results</span><strong>{{ results.length }}</strong></article>
      <article class="surface summary-item"><span>Invoices</span><strong>{{ invoices.length }}</strong></article>
    </section>
  </SchoolWorkspaceTemplate>
</template>
