<script setup lang="ts">
import type {
  AcademicClass,
  DashboardSummary,
  Exam,
  ReportDownloadStatus,
  ReportExport,
  ResultSummary,
  StudentEnrollment,
} from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface ItemResponse<T> {
  data: T
}

type AttendanceSummaryRow = {
  student_enrollment?: StudentEnrollment
  counts: {
    present: number
    absent: number
    late: number
    half_day: number
  }
  total_days: number
  attendance_percentage: number
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const exams = ref<Exam[]>([])
const classes = ref<AcademicClass[]>([])
const enrollments = ref<StudentEnrollment[]>([])
const resultSummaries = ref<ResultSummary[]>([])
const attendanceSummaries = ref<AttendanceSummaryRow[]>([])
const dashboardSummary = ref<DashboardSummary | null>(null)
const lastExport = ref<ReportExport | null>(null)
const exportStatus = ref<ReportDownloadStatus | null>(null)
const loading = ref(false)
const publishing = ref(false)
const exporting = ref(false)
const error = ref('')
const success = ref('')

const selectedExamId = ref('')
const selectedEnrollmentId = ref('')
const selectedClassId = ref('')
const selectedMonth = ref(new Date().toISOString().slice(0, 7))
const selectedInvoiceId = ref('')
const selectedSalaryRecordId = ref('')

const publishedCount = computed(() => exams.value.filter((exam) => exam.is_published).length)
const unpublishedCount = computed(() => exams.value.filter((exam) => !exam.is_published).length)
const averageAttendance = computed(() => {
  if (!attendanceSummaries.value.length) {
    return 0
  }

  const total = attendanceSummaries.value.reduce((sum, row) => sum + Number(row.attendance_percentage), 0)

  return Math.round((total / attendanceSummaries.value.length) * 100) / 100
})

function enrollmentLabel(enrollment?: StudentEnrollment) {
  if (!enrollment) {
    return 'Student'
  }

  const studentName = enrollment.student?.full_name || `Student ${enrollment.student_id}`
  const roll = enrollment.roll_no ? ` / Roll ${enrollment.roll_no}` : ''

  return `${studentName}${roll}`
}

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const [examResponse, classResponse, enrollmentResponse, dashboardResponse] = await Promise.all([
      api.request<ListResponse<Exam>>(`/schools/${schoolId.value}/exams?per_page=100`),
      api.request<ListResponse<AcademicClass>>(`/schools/${schoolId.value}/academic-classes?status=active&per_page=100`),
      api.request<ListResponse<StudentEnrollment>>(`/schools/${schoolId.value}/student-enrollments?status=active&per_page=100`),
      api.request<ItemResponse<DashboardSummary>>(`/schools/${schoolId.value}/dashboard/summary`),
    ])

    exams.value = examResponse.data
    classes.value = classResponse.data
    enrollments.value = enrollmentResponse.data
    dashboardSummary.value = dashboardResponse.data

    if (!selectedExamId.value && exams.value.length) {
      selectedExamId.value = String(exams.value[0].id)
    }

    if (!selectedEnrollmentId.value && enrollments.value.length) {
      selectedEnrollmentId.value = String(enrollments.value[0].id)
    }

    await Promise.all([loadResults(), loadAttendanceSummary()])
  } catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load reports workspace.'
  } finally {
    loading.value = false
  }
}

async function loadResults() {
  if (!selectedExamId.value) {
    resultSummaries.value = []
    return
  }

  const response = await api.request<ListResponse<ResultSummary>>(
    `/schools/${schoolId.value}/exams/${selectedExamId.value}/result-summaries?per_page=100`,
  )
  resultSummaries.value = response.data
}

async function loadAttendanceSummary() {
  const params = new URLSearchParams()

  if (selectedMonth.value) {
    params.set('month', selectedMonth.value)
  }

  if (selectedClassId.value) {
    params.set('academic_class_id', selectedClassId.value)
  }

  const response = await api.request<ListResponse<AttendanceSummaryRow>>(
    `/schools/${schoolId.value}/attendance/summary?${params.toString()}`,
  )
  attendanceSummaries.value = response.data
}

async function publishExam() {
  if (!selectedExamId.value) {
    error.value = 'Select an exam first.'
    return
  }

  publishing.value = true
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/exams/${selectedExamId.value}/publish`, { method: 'POST' })
    success.value = 'Exam results published and summaries refreshed.'
    await loadWorkspace()
  } catch (publishError) {
    error.value = publishError instanceof Error ? publishError.message : 'Unable to publish results.'
  } finally {
    publishing.value = false
  }
}

async function queueReport(type: 'marksheet' | 'result-sheet' | 'id-card' | 'invoice' | 'salary') {
  exporting.value = true
  error.value = ''
  success.value = ''
  exportStatus.value = null

  try {
    let path = `/schools/${schoolId.value}/reports/${type}`
    let body: Record<string, unknown> = {}

    if (type === 'marksheet') {
      body = {
        exam_id: Number(selectedExamId.value),
        student_enrollment_id: Number(selectedEnrollmentId.value),
      }
    } else if (type === 'result-sheet') {
      body = {
        exam_id: Number(selectedExamId.value),
        academic_class_id: selectedClassId.value ? Number(selectedClassId.value) : null,
      }
    } else if (type === 'id-card') {
      body = {
        student_enrollment_id: Number(selectedEnrollmentId.value),
      }
    } else if (type === 'invoice') {
      path = `/schools/${schoolId.value}/reports/invoice/${selectedInvoiceId.value}`
    } else {
      path = `/schools/${schoolId.value}/reports/salary/${selectedSalaryRecordId.value}`
    }

    const response = await api.request<ItemResponse<ReportExport>>(path, { method: 'POST', body })
    lastExport.value = response.data
    success.value = `Report queued: ${response.data.job_id}.`
  } catch (exportError) {
    error.value = exportError instanceof Error ? exportError.message : 'Unable to queue report.'
  } finally {
    exporting.value = false
  }
}

async function checkExport() {
  if (!lastExport.value) {
    error.value = 'Queue a report first.'
    return
  }

  error.value = ''

  try {
    const response = await api.request<ItemResponse<ReportDownloadStatus>>(
      `/schools/${schoolId.value}/reports/${lastExport.value.job_id}/download`,
    )
    exportStatus.value = response.data
    success.value = response.data.download_url ? 'Report file is ready.' : `Report status: ${response.data.status}.`
  } catch (statusError) {
    error.value = statusError instanceof Error ? statusError.message : 'Unable to check report status.'
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <main class="operation-shell">
    <aside class="operation-nav">
      <NuxtLink class="brand" to="/dashboard">
        <span>EA</span>
        <strong>School SaaS</strong>
      </NuxtLink>
      <nav aria-label="Reports navigation">
        <NuxtLink :to="`/schools/${schoolId}/calendar`">Calendar</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/documents`">Documents</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/exams`">Exams</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/marks`">Marks</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/finance`">Finance</NuxtLink>
      </nav>
    </aside>

    <section class="operation-workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">Reports</p>
          <h1>Publish results, review summaries, and queue official PDFs.</h1>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading" class="muted">Loading reports workspace</p>

      <section class="summary-grid">
        <article class="summary-item surface">
          <span>Published exams</span>
          <strong>{{ publishedCount }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Unpublished exams</span>
          <strong>{{ unpublishedCount }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Month attendance</span>
          <strong>{{ averageAttendance }}%</strong>
        </article>
      </section>

      <section v-if="dashboardSummary" class="workspace-grid">
        <article class="record-list surface">
          <div class="list-header">
            <div>
              <p class="eyebrow">Executive view</p>
              <h2>Dashboard summary</h2>
            </div>
          </div>
          <div class="insight-grid">
            <span>Students <strong>{{ dashboardSummary.admin.student_count }}</strong></span>
            <span>Employees <strong>{{ dashboardSummary.admin.employee_count }}</strong></span>
            <span>Today attendance <strong>{{ dashboardSummary.admin.today_attendance_rate }}%</strong></span>
            <span>Monthly collection <strong>{{ dashboardSummary.admin.fee_collection_this_month }}</strong></span>
            <span>Unpaid invoices <strong>{{ dashboardSummary.accountant.unpaid_invoices }}</strong></span>
            <span>Pending marks <strong>{{ dashboardSummary.teacher.pending_marks_entries }}</strong></span>
          </div>
        </article>

        <article class="record-list surface">
          <div class="list-header">
            <div>
              <p class="eyebrow">Attendance</p>
              <h2>Monthly student summary</h2>
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="summary-month">Month</label>
              <input id="summary-month" v-model="selectedMonth" type="month" @change="loadAttendanceSummary" />
            </div>
            <div class="field">
              <label for="summary-class">Class</label>
              <select id="summary-class" v-model="selectedClassId" @change="loadAttendanceSummary">
                <option value="">All classes</option>
                <option v-for="academicClass in classes" :key="academicClass.id" :value="academicClass.id">
                  {{ academicClass.name }}
                </option>
              </select>
            </div>
          </div>
          <div class="mini-list">
            <span v-for="row in attendanceSummaries.slice(0, 5)" :key="row.student_enrollment?.id">
              <strong>{{ enrollmentLabel(row.student_enrollment) }}</strong>
              <em>{{ row.attendance_percentage }}% attendance</em>
            </span>
            <p v-if="!attendanceSummaries.length" class="muted">No attendance summaries for this filter.</p>
          </div>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="record-form surface" @submit.prevent="publishExam">
          <div>
            <p class="eyebrow">Publication</p>
            <h2>Publish exam results</h2>
            <p>Recompute class positions, GPA, pass status, and notify the school team.</p>
          </div>

          <div class="field">
            <label for="exam-select">Exam</label>
            <select id="exam-select" v-model="selectedExamId" required @change="loadResults">
              <option v-for="exam in exams" :key="exam.id" :value="exam.id">
                {{ exam.name }} / {{ exam.status }}
              </option>
            </select>
          </div>

          <button class="button" type="submit" :disabled="publishing || !selectedExamId">
            {{ publishing ? 'Publishing' : 'Publish results' }}
          </button>
        </form>

        <form class="record-form surface" @submit.prevent="queueReport('marksheet')">
          <div>
            <p class="eyebrow">PDF queue</p>
            <h2>Generate official reports</h2>
            <p>Queue marksheets, result sheets, ID cards, receipts, and salary slips.</p>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="report-exam">Exam</label>
              <select id="report-exam" v-model="selectedExamId" required>
                <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.name }}</option>
              </select>
            </div>
            <div class="field">
              <label for="report-enrollment">Student</label>
              <select id="report-enrollment" v-model="selectedEnrollmentId" required>
                <option v-for="enrollment in enrollments" :key="enrollment.id" :value="enrollment.id">
                  {{ enrollmentLabel(enrollment) }}
                </option>
              </select>
            </div>
          </div>

          <div class="strip-actions">
            <button class="button" type="submit" :disabled="exporting || !selectedExamId || !selectedEnrollmentId">
              Queue marksheet
            </button>
            <button class="button secondary" type="button" :disabled="exporting || !selectedExamId" @click="queueReport('result-sheet')">
              Result sheet
            </button>
            <button class="button secondary" type="button" :disabled="exporting || !selectedEnrollmentId" @click="queueReport('id-card')">
              ID card
            </button>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="invoice-id">Invoice ID</label>
              <input id="invoice-id" v-model="selectedInvoiceId" inputmode="numeric" placeholder="Invoice record id" />
            </div>
            <div class="field">
              <label for="salary-id">Salary record ID</label>
              <input id="salary-id" v-model="selectedSalaryRecordId" inputmode="numeric" placeholder="Salary record id" />
            </div>
          </div>

          <div class="strip-actions">
            <button class="button secondary" type="button" :disabled="exporting || !selectedInvoiceId" @click="queueReport('invoice')">
              Invoice PDF
            </button>
            <button class="button secondary" type="button" :disabled="exporting || !selectedSalaryRecordId" @click="queueReport('salary')">
              Salary PDF
            </button>
            <button class="button secondary" type="button" :disabled="!lastExport" @click="checkExport">
              Check file
            </button>
          </div>

          <a
            v-if="exportStatus?.download_url"
            class="button compact"
            :href="exportStatus.download_url"
            target="_blank"
            rel="noreferrer"
          >
            Open PDF
          </a>
        </form>
      </section>

      <section class="record-list surface">
        <div class="list-header">
          <div>
            <p class="eyebrow">Result summaries</p>
            <h2>Published marksheet rows</h2>
          </div>
          <button class="button secondary compact" type="button" @click="loadResults">Refresh</button>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>Marks</th>
                <th>Percentage</th>
                <th>Grade</th>
                <th>Position</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="summary in resultSummaries" :key="summary.id">
                <td>{{ enrollmentLabel(summary.student_enrollment) }}</td>
                <td>{{ summary.total_marks_obtained }} / {{ summary.total_full_marks }}</td>
                <td>{{ summary.percentage }}%</td>
                <td>{{ summary.grade || 'N/A' }} / GPA {{ summary.gpa }}</td>
                <td>{{ summary.position_in_class || 'N/A' }}</td>
                <td><span class="status-pill">{{ summary.is_pass ? 'pass' : 'fail' }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!resultSummaries.length" class="muted">No result summaries are available for this exam yet.</p>
      </section>
    </section>
  </main>
</template>

<style scoped>
.insight-grid,
.mini-list {
  display: grid;
  gap: 10px;
}

.insight-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.insight-grid span,
.mini-list span {
  display: flex;
  min-height: 52px;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #e3ebe7;
  color: #607169;
  gap: 12px;
}

.insight-grid strong,
.mini-list strong {
  color: #16201c;
}

.mini-list em {
  color: #0f5f4a;
  font-style: normal;
  font-weight: 800;
}

.strip-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

@media (max-width: 900px) {
  .insight-grid {
    grid-template-columns: 1fr;
  }
}
</style>
