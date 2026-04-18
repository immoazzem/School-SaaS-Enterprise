<script setup lang="ts">
import type { AcademicYear, ClassSubject, Exam, ExamSchedule, ExamType } from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface ItemResponse<T> {
  data: T
}

const api = useApi()
const route = useRoute()

const schoolId = computed(() => Number(route.params.schoolId))

const examTypes = ref<ExamType[]>([])
const exams = ref<Exam[]>([])
const schedules = ref<ExamSchedule[]>([])
const academicYears = ref<AcademicYear[]>([])
const classSubjects = ref<ClassSubject[]>([])
const loading = ref(false)
const savingType = ref(false)
const savingExam = ref(false)
const savingSchedule = ref(false)
const error = ref('')
const success = ref('')
const typeStatusFilter = ref('active')
const examStatusFilter = ref('')
const scheduleExamFilter = ref('')

const typeForm = reactive({
  name: '',
  code: '',
  weightage_percent: '',
  description: '',
  sort_order: 0,
  status: 'active',
})

const examForm = reactive({
  exam_type_id: '',
  academic_year_id: '',
  name: '',
  code: '',
  starts_on: '',
  ends_on: '',
  status: 'scheduled',
  notes: '',
})

const scheduleForm = reactive({
  exam_id: '',
  class_subject_id: '',
  exam_date: '',
  starts_at: '',
  ends_at: '',
  room: '',
  instructions: '',
  status: 'scheduled',
})

const publishedCount = computed(() => exams.value.filter((exam) => exam.is_published).length)
const scheduledCount = computed(() => schedules.value.filter((schedule) => schedule.status === 'scheduled').length)
const activeTypeCount = computed(() => examTypes.value.filter((type) => type.status === 'active').length)

function classSubjectLabel(assignment: ClassSubject) {
  const className = assignment.academic_class?.name || `Class ${assignment.academic_class_id}`
  const subjectName = assignment.subject?.name || `Subject ${assignment.subject_id}`

  return `${className} / ${subjectName} / ${assignment.pass_marks}-${assignment.full_marks}`
}

async function loadOptions() {
  const [yearResponse, assignmentResponse] = await Promise.all([
    api.request<ListResponse<AcademicYear>>(`/schools/${schoolId.value}/academic-years?status=active`),
    api.request<ListResponse<ClassSubject>>(`/schools/${schoolId.value}/class-subjects?status=active&per_page=100`),
  ])

  academicYears.value = yearResponse.data
  classSubjects.value = assignmentResponse.data
}

async function loadExamTypes() {
  const query = new URLSearchParams()
  if (typeStatusFilter.value) query.set('status', typeStatusFilter.value)

  const suffix = query.toString() ? `?${query.toString()}` : ''
  const response = await api.request<ListResponse<ExamType>>(`/schools/${schoolId.value}/exam-types${suffix}`)
  examTypes.value = response.data
}

async function loadExams() {
  const query = new URLSearchParams()
  if (examStatusFilter.value) query.set('status', examStatusFilter.value)

  const suffix = query.toString() ? `?${query.toString()}` : ''
  const response = await api.request<ListResponse<Exam>>(`/schools/${schoolId.value}/exams${suffix}`)
  exams.value = response.data
}

async function loadSchedules() {
  const query = new URLSearchParams()
  if (scheduleExamFilter.value) query.set('exam_id', scheduleExamFilter.value)

  const suffix = query.toString() ? `?${query.toString()}` : ''
  const response = await api.request<ListResponse<ExamSchedule>>(`/schools/${schoolId.value}/exam-schedules${suffix}`)
  schedules.value = response.data
}

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    await Promise.all([loadOptions(), loadExamTypes(), loadExams(), loadSchedules()])
  } catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load exams.'
  } finally {
    loading.value = false
  }
}

function resetTypeForm() {
  typeForm.name = ''
  typeForm.code = ''
  typeForm.weightage_percent = ''
  typeForm.description = ''
  typeForm.sort_order = 0
  typeForm.status = 'active'
}

function resetExamForm() {
  examForm.exam_type_id = ''
  examForm.academic_year_id = ''
  examForm.name = ''
  examForm.code = ''
  examForm.starts_on = ''
  examForm.ends_on = ''
  examForm.status = 'scheduled'
  examForm.notes = ''
}

function resetScheduleForm() {
  scheduleForm.exam_id = ''
  scheduleForm.class_subject_id = ''
  scheduleForm.exam_date = ''
  scheduleForm.starts_at = ''
  scheduleForm.ends_at = ''
  scheduleForm.room = ''
  scheduleForm.instructions = ''
  scheduleForm.status = 'scheduled'
}

async function saveExamType() {
  savingType.value = true
  error.value = ''
  success.value = ''

  try {
    await api.request<ItemResponse<ExamType>>(`/schools/${schoolId.value}/exam-types`, {
      method: 'POST',
      body: {
        name: typeForm.name,
        code: typeForm.code,
        weightage_percent: typeForm.weightage_percent === '' ? null : Number(typeForm.weightage_percent),
        description: typeForm.description || null,
        sort_order: Number(typeForm.sort_order),
        status: typeForm.status,
      },
    })
    success.value = 'Exam type saved.'
    resetTypeForm()
    await loadExamTypes()
  } catch (typeError) {
    error.value = typeError instanceof Error ? typeError.message : 'Unable to save exam type.'
  } finally {
    savingType.value = false
  }
}

async function saveExam() {
  savingExam.value = true
  error.value = ''
  success.value = ''

  try {
    await api.request<ItemResponse<Exam>>(`/schools/${schoolId.value}/exams`, {
      method: 'POST',
      body: {
        exam_type_id: Number(examForm.exam_type_id),
        academic_year_id: Number(examForm.academic_year_id),
        name: examForm.name,
        code: examForm.code,
        starts_on: examForm.starts_on,
        ends_on: examForm.ends_on,
        status: examForm.status,
        notes: examForm.notes || null,
      },
    })
    success.value = 'Exam saved.'
    resetExamForm()
    await loadExams()
  } catch (examError) {
    error.value = examError instanceof Error ? examError.message : 'Unable to save exam.'
  } finally {
    savingExam.value = false
  }
}

async function saveSchedule() {
  savingSchedule.value = true
  error.value = ''
  success.value = ''

  try {
    await api.request<ItemResponse<ExamSchedule>>(`/schools/${schoolId.value}/exam-schedules`, {
      method: 'POST',
      body: {
        exam_id: Number(scheduleForm.exam_id),
        class_subject_id: Number(scheduleForm.class_subject_id),
        exam_date: scheduleForm.exam_date,
        starts_at: scheduleForm.starts_at || null,
        ends_at: scheduleForm.ends_at || null,
        room: scheduleForm.room || null,
        instructions: scheduleForm.instructions || null,
        status: scheduleForm.status,
      },
    })
    success.value = 'Schedule saved.'
    resetScheduleForm()
    await loadSchedules()
  } catch (scheduleError) {
    error.value = scheduleError instanceof Error ? scheduleError.message : 'Unable to save schedule.'
  } finally {
    savingSchedule.value = false
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <main class="shell">
    <aside class="sidebar">
      <NuxtLink class="brand" to="/dashboard">
        <span>EA</span>
        <strong>School SaaS</strong>
      </NuxtLink>
      <nav aria-label="Exam navigation">
        <NuxtLink :to="`/schools/${schoolId}/academic-years`">Academic Years</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/class-subjects`">Class Subjects</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/attendance`">Attendance</NuxtLink>
      </nav>
    </aside>

    <section class="workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">Exam operations</p>
          <h1>Exams</h1>
          <p class="muted">Define weighted terms, open exam windows, and schedule each class subject.</p>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <div v-if="error" class="alert error">{{ error }}</div>
      <div v-if="success" class="alert success">{{ success }}</div>

      <section class="summary-grid">
        <article>
          <span>Exam types</span>
          <strong>{{ activeTypeCount }}</strong>
        </article>
        <article>
          <span>Exam windows</span>
          <strong>{{ exams.length }}</strong>
        </article>
        <article>
          <span>Published</span>
          <strong>{{ publishedCount }}</strong>
        </article>
        <article>
          <span>Schedules</span>
          <strong>{{ scheduledCount }}</strong>
        </article>
      </section>

      <p v-if="loading" class="muted">Loading exams...</p>

      <section class="grid three-columns">
        <form class="panel" @submit.prevent="saveExamType">
          <p class="muted">Weighted terms</p>
          <h2>Exam Type</h2>

          <label for="type-name">Name</label>
          <input id="type-name" v-model="typeForm.name" required placeholder="Midterm">

          <label for="type-code">Code</label>
          <input id="type-code" v-model="typeForm.code" required placeholder="MID">

          <label for="type-weight">Weightage percent</label>
          <input id="type-weight" v-model="typeForm.weightage_percent" max="100" min="0" step="0.01" type="number" placeholder="40">

          <label for="type-description">Description</label>
          <textarea id="type-description" v-model="typeForm.description" rows="3" placeholder="First term exam." />

          <button class="button" type="submit" :disabled="savingType">{{ savingType ? 'Saving...' : 'Save type' }}</button>
        </form>

        <form class="panel" @submit.prevent="saveExam">
          <p class="muted">Exam window</p>
          <h2>Exam</h2>

          <label for="exam-type">Exam type</label>
          <select id="exam-type" v-model="examForm.exam_type_id" required>
            <option value="">Select type</option>
            <option v-for="type in examTypes" :key="type.id" :value="type.id">
              {{ type.name }} / {{ type.weightage_percent || '0.00' }}%
            </option>
          </select>

          <label for="exam-year">Academic year</label>
          <select id="exam-year" v-model="examForm.academic_year_id" required>
            <option value="">Select year</option>
            <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
          </select>

          <label for="exam-name">Name</label>
          <input id="exam-name" v-model="examForm.name" required placeholder="Midterm 2026">

          <label for="exam-code">Code</label>
          <input id="exam-code" v-model="examForm.code" required placeholder="MID-2026">

          <div class="form-row">
            <div>
              <label for="starts-on">Starts</label>
              <input id="starts-on" v-model="examForm.starts_on" required type="date">
            </div>
            <div>
              <label for="ends-on">Ends</label>
              <input id="ends-on" v-model="examForm.ends_on" required type="date">
            </div>
          </div>

          <button class="button" type="submit" :disabled="savingExam">{{ savingExam ? 'Saving...' : 'Save exam' }}</button>
        </form>

        <form class="panel" @submit.prevent="saveSchedule">
          <p class="muted">Class subject schedule</p>
          <h2>Schedule</h2>

          <label for="schedule-exam">Exam</label>
          <select id="schedule-exam" v-model="scheduleForm.exam_id" required>
            <option value="">Select exam</option>
            <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.name }}</option>
          </select>

          <label for="schedule-subject">Class subject</label>
          <select id="schedule-subject" v-model="scheduleForm.class_subject_id" required>
            <option value="">Select class subject</option>
            <option v-for="assignment in classSubjects" :key="assignment.id" :value="assignment.id">
              {{ classSubjectLabel(assignment) }}
            </option>
          </select>

          <label for="exam-date">Date</label>
          <input id="exam-date" v-model="scheduleForm.exam_date" required type="date">

          <div class="form-row">
            <div>
              <label for="starts-at">Start</label>
              <input id="starts-at" v-model="scheduleForm.starts_at" type="time">
            </div>
            <div>
              <label for="ends-at">End</label>
              <input id="ends-at" v-model="scheduleForm.ends_at" type="time">
            </div>
          </div>

          <label for="room">Room</label>
          <input id="room" v-model="scheduleForm.room" placeholder="Room 101">

          <button class="button" type="submit" :disabled="savingSchedule">
            {{ savingSchedule ? 'Saving...' : 'Save schedule' }}
          </button>
        </form>
      </section>

      <section class="grid two-columns">
        <section class="panel">
          <div class="table-header">
            <div>
              <p class="muted">Exam register</p>
              <h2>{{ exams.length }} exams</h2>
            </div>
            <form class="search-form" @submit.prevent="loadExams">
              <select v-model="examStatusFilter" aria-label="Exam status">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="scheduled">Scheduled</option>
                <option value="completed">Completed</option>
                <option value="archived">Archived</option>
              </select>
              <button class="button secondary" type="submit">Filter</button>
            </form>
          </div>

          <table>
            <thead>
              <tr>
                <th>Exam</th>
                <th>Type</th>
                <th>Window</th>
                <th>Publication</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="exam in exams" :key="exam.id">
                <td>
                  <strong>{{ exam.name }}</strong>
                  <span>{{ exam.code }} / {{ exam.status }}</span>
                </td>
                <td>{{ exam.exam_type?.name || exam.exam_type_id }}</td>
                <td>{{ exam.starts_on }} to {{ exam.ends_on }}</td>
                <td>
                  <span class="status-pill" :class="{ published: exam.is_published }">
                    {{ exam.is_published ? 'Published' : 'Unpublished' }}
                  </span>
                </td>
              </tr>
              <tr v-if="!exams.length">
                <td colspan="4">No exams yet.</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section class="panel">
          <div class="table-header">
            <div>
              <p class="muted">Schedule board</p>
              <h2>{{ schedules.length }} schedules</h2>
            </div>
            <form class="search-form" @submit.prevent="loadSchedules">
              <select v-model="scheduleExamFilter" aria-label="Schedule exam">
                <option value="">All exams</option>
                <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.name }}</option>
              </select>
              <button class="button secondary" type="submit">Filter</button>
            </form>
          </div>

          <table>
            <thead>
              <tr>
                <th>Subject</th>
                <th>Exam</th>
                <th>Date</th>
                <th>Room</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="schedule in schedules" :key="schedule.id">
                <td>
                  <strong>{{ schedule.class_subject?.subject?.name || schedule.class_subject_id }}</strong>
                  <span>{{ schedule.class_subject?.academic_class?.name }} / {{ schedule.class_subject?.pass_marks }}-{{ schedule.class_subject?.full_marks }}</span>
                </td>
                <td>{{ schedule.exam?.name || schedule.exam_id }}</td>
                <td>{{ schedule.exam_date }} {{ schedule.starts_at || '' }}</td>
                <td>{{ schedule.room || '-' }}</td>
              </tr>
              <tr v-if="!schedules.length">
                <td colspan="4">No schedules yet.</td>
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

.summary-grid,
.grid.three-columns,
.grid.two-columns {
  display: grid;
  gap: 14px;
}

.summary-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.grid.three-columns {
  grid-template-columns: repeat(3, minmax(260px, 1fr));
}

.grid.two-columns {
  grid-template-columns: repeat(2, minmax(0, 1fr));
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

.panel {
  display: grid;
  align-content: start;
  gap: 14px;
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
  min-height: 96px;
  padding-top: 12px;
  resize: vertical;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.table-header,
.search-form {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
}

.search-form {
  flex-wrap: wrap;
  justify-content: flex-end;
}

.search-form select {
  width: 170px;
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

.button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
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
  background: #fff1f0;
  color: #a83b32;
  font-weight: 900;
}

.status-pill.published {
  background: #edf7f0;
  color: #24703c;
}

.alert {
  border-radius: 8px;
  padding: 12px 14px;
  font-weight: 800;
}

.alert.error {
  color: #a83b32;
  background: #fff1f0;
}

.alert.success {
  background: #edf7f0;
  color: #24703c;
}

@media (max-width: 1100px) {
  .grid.three-columns,
  .grid.two-columns {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 860px) {
  .shell {
    grid-template-columns: 1fr;
  }

  .sidebar {
    border-right: 0;
    border-bottom: 1px solid #dbe5e1;
  }

  .workspace {
    padding: 18px;
  }

  .workspace-header,
  .table-header,
  .search-form {
    align-items: stretch;
    flex-direction: column;
  }

  .summary-grid,
  .form-row {
    grid-template-columns: 1fr;
  }

  .search-form select {
    width: 100%;
  }
}
</style>
