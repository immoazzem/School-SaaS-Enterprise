<script setup lang="ts">
interface StudentProfile {
  full_name?: string
  admission_no?: string
  email?: string | null
  guardian?: { full_name?: string; relationship?: string; phone?: string | null } | null
  enrollments?: Array<{
    academic_year?: { name?: string }
    academic_class?: { name?: string }
    academic_section?: { name?: string } | null
  }>
}

interface AttendanceRecord {
  id: number
  attendance_date?: string
  status?: string
}

interface ResultItem {
  id: number
  percentage?: string
  grade?: string | null
  exam?: { name?: string; code?: string } | null
}

interface InvoiceItem {
  id: number
  invoice_no?: string
  total?: string
  paid_amount?: string
  status?: string
}

interface NotificationItem {
  id: number
  title?: string | null
  body?: string | null
  type?: string
  created_at?: string | null
}

interface DataResponse<T> { data: T }
interface ListResponse<T> { data: T[] }

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const profile = ref<StudentProfile | null>(null)
const attendance = ref<AttendanceRecord[]>([])
const results = ref<ResultItem[]>([])
const invoices = ref<InvoiceItem[]>([])
const notifications = ref<NotificationItem[]>([])
const loading = ref(false)
const error = ref('')

async function loadWorkspace() {
  loading.value = true
  error.value = ''
  try {
    const [profileResponse, attendanceResponse, resultsResponse, invoicesResponse, notificationResponse] = await Promise.all([
      api.request<DataResponse<StudentProfile>>(`/schools/${schoolId.value}/portal/student/profile`),
      api.request<ListResponse<AttendanceRecord>>(`/schools/${schoolId.value}/portal/student/attendance?per_page=20`),
      api.request<ListResponse<ResultItem>>(`/schools/${schoolId.value}/portal/student/results?per_page=20`),
      api.request<ListResponse<InvoiceItem>>(`/schools/${schoolId.value}/portal/student/invoices?per_page=20`),
      api.request<ListResponse<NotificationItem>>(`/schools/${schoolId.value}/portal/student/notifications?per_page=20`),
    ])
    profile.value = profileResponse.data
    attendance.value = attendanceResponse.data
    results.value = resultsResponse.data
    invoices.value = invoicesResponse.data
    notifications.value = notificationResponse.data
  }
  catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load student portal view.'
  }
  finally {
    loading.value = false
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
        :school-id="schoolId"
        aria-label="Student portal navigation"
        context-title="Portal links"
        :context-links="[
          { label: 'Parent Portal', to: `/schools/${schoolId}/portal-parent` },
          { label: 'School Settings', to: `/schools/${schoolId}/settings` },
          { label: 'Notifications', to: `/schools/${schoolId}/notifications` },
        ]"
      />
    </template>

    <header class="workspace-header">
      <div>
        <p class="eyebrow">Finance</p>
        <h1>Student portal view</h1>
        <p>Preview the live student-facing experience for profile, attendance, results, invoices, and notifications.</p>
      </div>
    </header>

    <VAlert v-if="error" type="error" variant="tonal">{{ error }}</VAlert>

    <section class="summary-grid" v-if="profile">
      <article class="surface summary-item">
        <span>Student</span>
        <strong>{{ profile.full_name }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Attendance records</span>
        <strong>{{ attendance.length }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Invoices</span>
        <strong>{{ invoices.length }}</strong>
      </article>
    </section>

    <section class="workspace-grid">
      <section class="surface record-list">
        <div class="list-header"><h2>Profile</h2><span class="muted">{{ loading ? 'Refreshing...' : 'Live view' }}</span></div>
        <div class="table-wrap">
          <table>
            <tbody>
              <tr><th>Admission no</th><td>{{ profile?.admission_no || '-' }}</td></tr>
              <tr><th>Email</th><td>{{ profile?.email || '-' }}</td></tr>
              <tr><th>Guardian</th><td>{{ profile?.guardian?.full_name || '-' }}</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="surface record-list">
        <div class="list-header"><h2>Notifications</h2><span class="muted">{{ notifications.length }} items</span></div>
        <div class="table-wrap">
          <table>
            <tbody>
              <tr v-for="item in notifications" :key="item.id">
                <td>
                  <strong>{{ item.title || item.type || 'Portal message' }}</strong>
                  <div class="muted">{{ item.body || '-' }}</div>
                </td>
              </tr>
              <tr v-if="!notifications.length && !loading"><td>No notifications.</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </SchoolWorkspaceTemplate>
</template>
