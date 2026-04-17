<script setup lang="ts">
import type { StudentAttendanceRecord, StudentEnrollment } from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface AttendanceResponse {
  data: StudentAttendanceRecord
}

const api = useApi()
const route = useRoute()

const records = ref<StudentAttendanceRecord[]>([])
const enrollments = ref<StudentEnrollment[]>([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const search = ref('')
const statusFilter = ref('')
const editingId = ref<number | null>(null)

function dhakaDateInput() {
  const parts = new Intl.DateTimeFormat('en-CA', {
    day: '2-digit',
    month: '2-digit',
    timeZone: 'Asia/Dhaka',
    year: 'numeric',
  })
    .formatToParts(new Date())
    .reduce<Record<string, string>>((carry, part) => {
      carry[part.type] = part.value

      return carry
    }, {})

  return `${parts.year}-${parts.month}-${parts.day}`
}

const today = dhakaDateInput()
const selectedDate = ref(today)

const form = reactive({
  student_enrollment_id: '',
  attendance_date: today,
  status: 'present' as StudentAttendanceRecord['status'],
  remarks: '',
})

const schoolId = computed(() => Number(route.params.schoolId))

const attendanceSummary = computed(() => {
  const counts = {
    present: 0,
    late: 0,
    absent: 0,
    excused: 0,
  }

  for (const record of records.value) {
    counts[record.status] += 1
  }

  return counts
})

function enrollmentLabel(enrollment: StudentEnrollment) {
  const student = enrollment.student
  const className = enrollment.academic_class?.name || 'No class'
  const roll = enrollment.roll_no ? `Roll ${enrollment.roll_no}` : 'No roll'

  return `${student?.full_name || 'Student'} / ${student?.admission_no || 'No admission'} / ${className} / ${roll}`
}

async function loadEnrollments() {
  const response = await api.request<ListResponse<StudentEnrollment>>(
    `/schools/${schoolId.value}/student-enrollments?status=active`,
  )
  enrollments.value = response.data
}

async function loadRecords() {
  const query = new URLSearchParams()

  if (selectedDate.value) {
    query.set('attendance_date', selectedDate.value)
  }

  if (statusFilter.value) {
    query.set('status', statusFilter.value)
  }

  if (search.value.trim()) {
    query.set('search', search.value.trim())
  }

  const suffix = query.toString() ? `?${query.toString()}` : ''
  const response = await api.request<ListResponse<StudentAttendanceRecord>>(
    `/schools/${schoolId.value}/student-attendance-records${suffix}`,
  )
  records.value = response.data
}

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    await Promise.all([loadEnrollments(), loadRecords()])
  } catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load attendance.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingId.value = null
  form.student_enrollment_id = ''
  form.attendance_date = selectedDate.value || today
  form.status = 'present'
  form.remarks = ''
}

function editRecord(record: StudentAttendanceRecord) {
  editingId.value = record.id
  form.student_enrollment_id = String(record.student_enrollment_id)
  form.attendance_date = record.attendance_date.slice(0, 10)
  form.status = record.status
  form.remarks = record.remarks || ''
}

async function saveRecord() {
  saving.value = true
  error.value = ''
  success.value = ''

  const payload = {
    student_enrollment_id: Number(form.student_enrollment_id),
    attendance_date: form.attendance_date,
    status: form.status,
    remarks: form.remarks || null,
  }

  try {
    if (editingId.value) {
      await api.request<AttendanceResponse>(
        `/schools/${schoolId.value}/student-attendance-records/${editingId.value}`,
        {
          method: 'PATCH',
          body: payload,
        },
      )
      success.value = 'Attendance updated.'
    } else {
      await api.request<AttendanceResponse>(`/schools/${schoolId.value}/student-attendance-records`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Attendance saved.'
    }

    selectedDate.value = form.attendance_date
    resetForm()
    await loadRecords()
  } catch (attendanceError) {
    error.value = attendanceError instanceof Error ? attendanceError.message : 'Unable to save attendance.'
  } finally {
    saving.value = false
  }
}

async function deleteRecord(record: StudentAttendanceRecord) {
  await api.request(`/schools/${schoolId.value}/student-attendance-records/${record.id}`, {
    method: 'DELETE',
  })
  success.value = 'Attendance deleted.'
  await loadRecords()
}

watch(selectedDate, (value) => {
  if (!editingId.value) {
    form.attendance_date = value
  }
})

onMounted(loadWorkspace)
</script>

<template>
  <main class="shell">
    <aside class="sidebar">
      <NuxtLink class="brand" to="/dashboard">
        <span>EA</span>
        <strong>School SaaS</strong>
      </NuxtLink>
      <nav aria-label="Attendance navigation">
        <NuxtLink :to="`/schools/${schoolId}/enrollments`">Enrollments</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/students`">Students</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/teacher-profiles`">Teachers</NuxtLink>
      </nav>
    </aside>

    <section class="workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">Daily register</p>
          <h1>Attendance</h1>
          <p class="muted">Record daily attendance from active enrollments and keep the day searchable.</p>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <div v-if="error" class="alert error">{{ error }}</div>
      <div v-if="success" class="alert success">{{ success }}</div>

      <section class="summary-grid">
        <article>
          <span>Present</span>
          <strong>{{ attendanceSummary.present }}</strong>
        </article>
        <article>
          <span>Late</span>
          <strong>{{ attendanceSummary.late }}</strong>
        </article>
        <article>
          <span>Absent</span>
          <strong>{{ attendanceSummary.absent }}</strong>
        </article>
        <article>
          <span>Excused</span>
          <strong>{{ attendanceSummary.excused }}</strong>
        </article>
      </section>

      <section class="grid two-columns">
        <form class="panel" @submit.prevent="saveRecord">
          <p class="muted">{{ editingId ? 'Editing attendance' : 'New attendance' }}</p>
          <h2>{{ editingId ? 'Update record' : 'Add record' }}</h2>

          <label for="enrollment">Student enrollment</label>
          <select id="enrollment" v-model="form.student_enrollment_id" required>
            <option value="">Select student</option>
            <option v-for="enrollment in enrollments" :key="enrollment.id" :value="enrollment.id">
              {{ enrollmentLabel(enrollment) }}
            </option>
          </select>

          <div class="form-row">
            <div>
              <label for="attendance-date">Date</label>
              <input id="attendance-date" v-model="form.attendance_date" type="date" required>
            </div>
            <div>
              <label for="status">Status</label>
              <select id="status" v-model="form.status" required>
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
                <option value="excused">Excused</option>
              </select>
            </div>
          </div>

          <label for="remarks">Remarks</label>
          <textarea id="remarks" v-model="form.remarks" rows="4" placeholder="Morning homeroom." />

          <div class="form-actions">
            <button class="button" type="submit" :disabled="saving">{{ saving ? 'Saving...' : 'Save attendance' }}</button>
            <button v-if="editingId" class="button secondary" type="button" @click="resetForm">Cancel</button>
          </div>
        </form>

        <section class="panel">
          <div class="table-header">
            <div>
              <p class="muted">Attendance list</p>
              <h2>{{ records.length }} records</h2>
            </div>
            <form class="search-form" @submit.prevent="loadRecords">
              <input v-model="selectedDate" type="date">
              <select v-model="statusFilter">
                <option value="">All statuses</option>
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
                <option value="excused">Excused</option>
              </select>
              <input v-model="search" placeholder="Search student">
              <button class="button secondary" type="submit">Search</button>
            </form>
          </div>

          <div v-if="loading" class="muted">Loading attendance...</div>
          <table v-else>
            <thead>
              <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="record in records" :key="record.id">
                <td>
                  <strong>{{ record.student_enrollment?.student?.full_name }}</strong>
                  <span>{{ record.student_enrollment?.student?.admission_no }}</span>
                </td>
                <td>
                  <strong>{{ record.student_enrollment?.academic_class?.name || '-' }}</strong>
                  <span>{{ record.student_enrollment?.roll_no ? `Roll ${record.student_enrollment.roll_no}` : 'No roll' }}</span>
                </td>
                <td>{{ record.attendance_date }}</td>
                <td><span class="status-pill" :class="record.status">{{ record.status }}</span></td>
                <td class="table-actions">
                  <button class="link-button" type="button" @click="editRecord(record)">Edit</button>
                  <button class="link-button danger" type="button" @click="deleteRecord(record)">Delete</button>
                </td>
              </tr>
              <tr v-if="!records.length">
                <td colspan="5">No attendance records for this view.</td>
              </tr>
            </tbody>
          </table>
        </section>
      </section>
    </section>
  </main>
</template>

<style scoped>
.shell {
  display: grid;
  min-height: 100vh;
  grid-template-columns: 260px minmax(0, 1fr);
  background: #f6f8f7;
}

.sidebar {
  display: flex;
  flex-direction: column;
  gap: 30px;
  border-right: 1px solid #dbe5e1;
  padding: 24px;
  background: #fff;
}

.brand {
  display: flex;
  gap: 12px;
  align-items: center;
  color: #16201c;
  text-decoration: none;
}

.brand span {
  display: grid;
  width: 38px;
  height: 38px;
  place-items: center;
  border-radius: 8px;
  background: #0f5f4a;
  color: #fff;
  font-weight: 900;
}

nav {
  display: grid;
  gap: 8px;
}

nav a {
  border-radius: 8px;
  padding: 12px;
  color: #53665e;
  text-decoration: none;
}

nav a:hover {
  background: #eef5f1;
  color: #0f5f4a;
}

.workspace {
  display: grid;
  align-content: start;
  gap: 22px;
  padding: 30px;
}

.workspace-header {
  display: flex;
  gap: 20px;
  align-items: center;
  justify-content: space-between;
}

.eyebrow,
.muted {
  margin: 0;
  color: #607169;
  font-weight: 700;
}

h1,
h2 {
  margin: 6px 0;
  color: #16201c;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.summary-grid article,
.panel {
  border: 1px solid #dbe5e1;
  border-radius: 8px;
  background: #fff;
}

.summary-grid article {
  display: grid;
  gap: 10px;
  padding: 18px;
}

.summary-grid span {
  color: #607169;
  font-weight: 800;
}

.summary-grid strong {
  color: #16201c;
  font-size: 1.9rem;
}

.grid.two-columns {
  display: grid;
  grid-template-columns: minmax(320px, 0.72fr) minmax(0, 1.28fr);
  gap: 16px;
}

.panel {
  display: grid;
  align-content: start;
  gap: 16px;
  padding: 22px;
}

label {
  color: #33413b;
  font-weight: 800;
}

input,
select,
textarea {
  width: 100%;
  min-height: 44px;
  border: 1px solid #cbdad4;
  border-radius: 8px;
  padding: 0 12px;
  background: #fff;
  color: #16201c;
  font: inherit;
}

textarea {
  min-height: 112px;
  padding-top: 12px;
  resize: vertical;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.form-actions,
.table-actions,
.search-form {
  display: flex;
  gap: 10px;
  align-items: center;
}

.table-header {
  display: flex;
  gap: 16px;
  align-items: start;
  justify-content: space-between;
}

.search-form {
  flex-wrap: wrap;
  justify-content: flex-end;
}

.search-form input,
.search-form select {
  width: 160px;
}

.button {
  min-height: 44px;
  border: 0;
  border-radius: 8px;
  padding: 0 16px;
  background: #0f5f4a;
  color: #fff;
  font-weight: 900;
  text-decoration: none;
  cursor: pointer;
}

.button.secondary {
  border: 1px solid #cbdad4;
  background: #fff;
  color: #0f5f4a;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  border-bottom: 1px solid #e3ebe7;
  padding: 12px 8px;
  text-align: left;
  vertical-align: top;
}

th {
  color: #607169;
  font-size: 0.78rem;
  text-transform: uppercase;
}

td span {
  display: block;
  margin-top: 3px;
  color: #607169;
  font-size: 0.9rem;
}

.status-pill {
  display: inline-flex;
  border-radius: 8px;
  padding: 5px 10px;
  background: #eef5f1;
  color: #0f5f4a;
  font-weight: 900;
  text-transform: capitalize;
}

.status-pill.absent {
  background: #fff1f0;
  color: #a83b32;
}

.status-pill.late {
  background: #f3f0df;
  color: #6b5d16;
}

.status-pill.excused {
  background: #eef2f8;
  color: #315271;
}

.link-button {
  border: 0;
  background: transparent;
  color: #0f5f4a;
  font-weight: 900;
  cursor: pointer;
}

.link-button.danger,
.alert.error {
  color: #a83b32;
}

.alert {
  border-radius: 8px;
  padding: 12px 14px;
  font-weight: 800;
}

.alert.error {
  background: #fff1f0;
}

.alert.success {
  background: #edf7f0;
  color: #24703c;
}

@media (max-width: 960px) {
  .shell,
  .grid.two-columns {
    grid-template-columns: 1fr;
  }

  .summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .sidebar {
    border-right: 0;
    border-bottom: 1px solid #dbe5e1;
  }

  .workspace-header,
  .table-header,
  .form-row,
  .form-actions,
  .search-form {
    grid-template-columns: 1fr;
    align-items: stretch;
    flex-direction: column;
  }

  .search-form input,
  .search-form select {
    width: 100%;
  }
}
</style>
