<script setup lang="ts">
import type { ClassSubject, Exam, GradeScale, MarksEntry, StudentEnrollment } from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface ItemResponse<T> {
  data: T
}

interface MarksDraft {
  exam_id: string | number
  class_subject_id: string | number
  student_enrollment_id: string | number
  marks_obtained: string | number
  is_absent: boolean
  absent_reason: string
  remarks: string
}

const api = useApi()
const route = useRoute()
const { isOnline } = useNetworkStatus()
const offlineQueue = useOfflineQueue('marks')
const schoolId = computed(() => Number(route.params.schoolId))

const exams = ref<Exam[]>([])
const classSubjects = ref<ClassSubject[]>([])
const enrollments = ref<StudentEnrollment[]>([])
const marks = ref<MarksEntry[]>([])
const gradeScales = ref<GradeScale[]>([])
const loading = ref(false)
const savingMark = ref(false)
const savingGrade = ref(false)
const verifyingId = ref<number | null>(null)
const error = ref('')
const success = ref('')
const marksDraft = useOfflineDraft<MarksDraft>(
  computed(() => `school-saas:offline:marks:${schoolId.value}`),
)
const marksQueueEntries = computed(() =>
  offlineQueue.entries.value.filter((entry) => entry.schoolId === schoolId.value),
)

const markForm = reactive({
  exam_id: '',
  class_subject_id: '',
  student_enrollment_id: '',
  marks_obtained: '',
  is_absent: false,
  absent_reason: '',
  remarks: '',
})

const gradeForm = reactive({
  name: '',
  code: '',
  min_percent: 80,
  max_percent: 100,
  grade_point: 5,
  fail_below_percent: 33,
  gpa_calculation_method: 'weighted',
})

const pendingCount = computed(() => marks.value.filter((entry) => entry.verification_status === 'pending').length)
const absentCount = computed(() => marks.value.filter((entry) => entry.is_absent).length)
const verifiedCount = computed(() => marks.value.filter((entry) => entry.verification_status === 'verified').length)

function classSubjectLabel(assignment: ClassSubject) {
  const className = assignment.academic_class?.name || `Class ${assignment.academic_class_id}`
  const subjectName = assignment.subject?.name || `Subject ${assignment.subject_id}`

  return `${className} / ${subjectName} / ${assignment.pass_marks}-${assignment.full_marks}`
}

function enrollmentLabel(enrollment: StudentEnrollment) {
  const name = enrollment.student?.full_name || `Student ${enrollment.student_id}`
  const roll = enrollment.roll_no ? ` / Roll ${enrollment.roll_no}` : ''

  return `${name}${roll}`
}

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const [examResponse, assignmentResponse, enrollmentResponse, markResponse, gradeResponse] = await Promise.all([
      api.request<ListResponse<Exam>>(`/schools/${schoolId.value}/exams?per_page=100`),
      api.request<ListResponse<ClassSubject>>(`/schools/${schoolId.value}/class-subjects?status=active&per_page=100`),
      api.request<ListResponse<StudentEnrollment>>(`/schools/${schoolId.value}/student-enrollments?status=active&per_page=100`),
      api.request<ListResponse<MarksEntry>>(`/schools/${schoolId.value}/marks-entries?per_page=100`),
      api.request<ListResponse<GradeScale>>(`/schools/${schoolId.value}/grade-scales?per_page=100`),
    ])
    exams.value = examResponse.data
    classSubjects.value = assignmentResponse.data
    enrollments.value = enrollmentResponse.data
    marks.value = markResponse.data
    gradeScales.value = gradeResponse.data
  } catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load marks workspace.'
  } finally {
    loading.value = false
  }
}

function resetMarkForm() {
  markForm.student_enrollment_id = ''
  markForm.marks_obtained = ''
  markForm.is_absent = false
  markForm.absent_reason = ''
  markForm.remarks = ''
}

function marksDraftPayload(): MarksDraft {
  return {
    exam_id: markForm.exam_id,
    class_subject_id: markForm.class_subject_id,
    student_enrollment_id: markForm.student_enrollment_id,
    marks_obtained: markForm.marks_obtained,
    is_absent: markForm.is_absent,
    absent_reason: markForm.absent_reason,
    remarks: markForm.remarks,
  }
}

function restoreMarksDraft() {
  const draft = marksDraft.load()

  if (!draft) {
    return
  }

  markForm.exam_id = String(draft.exam_id)
  markForm.class_subject_id = String(draft.class_subject_id)
  markForm.student_enrollment_id = String(draft.student_enrollment_id)
  markForm.marks_obtained = String(draft.marks_obtained)
  markForm.is_absent = draft.is_absent
  markForm.absent_reason = draft.absent_reason
  markForm.remarks = draft.remarks
  success.value = 'Local marks draft restored.'
}

function saveMarksDraft() {
  marksDraft.save(marksDraftPayload())
  success.value = isOnline.value
    ? 'Marks draft saved on this device.'
    : 'Offline marks draft saved on this device.'
}

function clearMarksDraft() {
  marksDraft.clear()
  success.value = 'Local marks draft cleared.'
}

function markQueueLabel(payload: { exam_id: number, class_subject_id: number, student_enrollment_id: number }) {
  const enrollment = enrollments.value.find((item) => item.id === payload.student_enrollment_id)
  const exam = exams.value.find((item) => item.id === payload.exam_id)
  const classSubject = classSubjects.value.find((item) => item.id === payload.class_subject_id)
  const subjectName = classSubject?.subject?.name || `Subject ${payload.class_subject_id}`

  return `Marks / ${enrollment?.student?.full_name || `Enrollment ${payload.student_enrollment_id}`} / ${exam?.name || `Exam ${payload.exam_id}`} / ${subjectName}`
}

async function queueMark(payload: {
  exam_id: number
  class_subject_id: number
  student_enrollment_id: number
  marks_obtained: number | null
  is_absent: boolean
  absent_reason: string | null
  remarks: string | null
}) {
  await offlineQueue.enqueue({
    schoolId: schoolId.value,
    label: markQueueLabel(payload),
    method: 'POST',
    path: `/schools/${schoolId.value}/marks-entries`,
    payload,
  })
  marksDraft.clear()
}

async function syncMarksQueue() {
  error.value = ''
  success.value = ''

  if (!isOnline.value) {
    success.value = 'The marks queue will sync when the connection returns.'

    return
  }

  const summary = await offlineQueue.syncEntries(
    (entry) => entry.schoolId === schoolId.value,
    async (entry) => {
      await api.request(entry.path, {
        method: entry.method,
        body: entry.payload,
      })
    },
  )
  const retainedConflicts = marksQueueEntries.value.filter((entry) => entry.status === 'conflict').length
  const retainedFailures = marksQueueEntries.value.filter((entry) => entry.status === 'failed').length

  if (summary.authRequired) {
    error.value = 'Marks sync paused because your session expired. Sign in again, then sync the remaining queue.'
  } else if (summary.conflicts || retainedConflicts) {
    const count = summary.conflicts || retainedConflicts
    error.value = `${count} marks record${count === 1 ? '' : 's'} need review before they can sync.`
  } else if (summary.failed || retainedFailures) {
    const count = summary.failed || retainedFailures
    error.value = `${count} marks record${count === 1 ? '' : 's'} could not sync. Keep them in the queue and try again.`
  } else if (summary.synced) {
    success.value = `${summary.synced} marks record${summary.synced === 1 ? '' : 's'} synced.`
  } else {
    success.value = 'No marks queue records were ready to sync.'
  }

  await loadWorkspace()
}

async function saveMark() {
  savingMark.value = true
  error.value = ''
  success.value = ''

  const payload = {
    exam_id: Number(markForm.exam_id),
    class_subject_id: Number(markForm.class_subject_id),
    student_enrollment_id: Number(markForm.student_enrollment_id),
    marks_obtained: markForm.is_absent || markForm.marks_obtained === '' ? null : Number(markForm.marks_obtained),
    is_absent: markForm.is_absent,
    absent_reason: markForm.is_absent ? markForm.absent_reason || null : null,
    remarks: markForm.remarks || null,
  }

  try {
    if (!isOnline.value) {
      await queueMark(payload)
      success.value = 'Offline marks entry queued. It will sync when the connection returns.'
      resetMarkForm()

      return
    }

    await api.request<ItemResponse<MarksEntry>>(`/schools/${schoolId.value}/marks-entries`, {
      method: 'POST',
      body: payload,
    })
    success.value = 'Marks entry saved.'
    marksDraft.clear()
    resetMarkForm()
    await loadWorkspace()
  } catch (markError) {
    error.value = markError instanceof Error ? markError.message : 'Unable to save marks entry.'
  } finally {
    savingMark.value = false
  }
}

async function saveGradeScale() {
  savingGrade.value = true
  error.value = ''
  success.value = ''

  try {
    await api.request<ItemResponse<GradeScale>>(`/schools/${schoolId.value}/grade-scales`, {
      method: 'POST',
      body: {
        name: gradeForm.name,
        code: gradeForm.code,
        min_percent: Number(gradeForm.min_percent),
        max_percent: Number(gradeForm.max_percent),
        grade_point: Number(gradeForm.grade_point),
        fail_below_percent: Number(gradeForm.fail_below_percent),
        gpa_calculation_method: gradeForm.gpa_calculation_method,
      },
    })
    success.value = 'Grade scale saved.'
    gradeForm.name = ''
    gradeForm.code = ''
    await loadWorkspace()
  } catch (gradeError) {
    error.value = gradeError instanceof Error ? gradeError.message : 'Unable to save grade scale.'
  } finally {
    savingGrade.value = false
  }
}

async function verifyEntry(entry: MarksEntry, status: 'verified' | 'rejected') {
  verifyingId.value = entry.id
  error.value = ''
  success.value = ''

  try {
    await api.request<ItemResponse<MarksEntry>>(`/schools/${schoolId.value}/marks-entries/${entry.id}/verify`, {
      method: 'PATCH',
      body: { verification_status: status },
    })
    success.value = status === 'verified' ? 'Marks entry verified.' : 'Marks entry rejected.'
    await loadWorkspace()
  } catch (verifyError) {
    error.value = verifyError instanceof Error ? verifyError.message : 'Unable to update verification.'
  } finally {
    verifyingId.value = null
  }
}

watch(isOnline, (online) => {
  if (online && marksQueueEntries.value.length) {
    syncMarksQueue()
  }
})

onMounted(() => {
  restoreMarksDraft()
  offlineQueue.refresh()
  loadWorkspace()
})
</script>

<template>
  <main class="operation-shell">
    <aside class="operation-nav">
      <NuxtLink class="brand" to="/dashboard">
        <span>EA</span>
        <strong>School SaaS</strong>
      </NuxtLink>
      <nav aria-label="Marks navigation">
        <NuxtLink :to="`/schools/${schoolId}/exams`">Exams</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/class-subjects`">Class Subjects</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/finance`">Finance</NuxtLink>
      </nav>
    </aside>

    <section class="operation-workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">Marks and grades</p>
          <h1>Enter marks, verify results, and maintain grading rules.</h1>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading" class="muted">Loading marks workspace</p>

      <OfflineNotice
        context="Marks offline draft"
        :has-draft="marksDraft.hasDraft.value"
        :saved-at="marksDraft.savedAt.value"
      >
        <button v-if="marksDraft.hasDraft.value" class="button secondary compact" type="button" @click="restoreMarksDraft">
          Restore draft
        </button>
        <button v-if="marksDraft.hasDraft.value" class="button secondary compact" type="button" @click="clearMarksDraft">
          Clear draft
        </button>
      </OfflineNotice>

      <OfflineQueuePanel
        :entries="marksQueueEntries"
        :syncing="offlineQueue.syncing.value"
        @discard="offlineQueue.remove"
        @sync="syncMarksQueue"
      />

      <section class="summary-grid">
        <article class="surface summary-item">
          <span>Pending</span>
          <strong>{{ pendingCount }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Verified</span>
          <strong>{{ verifiedCount }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Absent entries</span>
          <strong>{{ absentCount }}</strong>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="saveMark">
          <div>
            <p class="eyebrow">Marks entry</p>
            <h2>Record student marks</h2>
            <p>Full and pass marks come from the selected class subject.</p>
          </div>
          <div class="field">
            <label for="mark-exam">Exam</label>
            <select id="mark-exam" v-model="markForm.exam_id" required>
              <option value="">Select exam</option>
              <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.name }}</option>
            </select>
          </div>
          <div class="field">
            <label for="mark-subject">Class subject</label>
            <select id="mark-subject" v-model="markForm.class_subject_id" required>
              <option value="">Select class subject</option>
              <option v-for="assignment in classSubjects" :key="assignment.id" :value="assignment.id">
                {{ classSubjectLabel(assignment) }}
              </option>
            </select>
          </div>
          <div class="field">
            <label for="mark-student">Student</label>
            <select id="mark-student" v-model="markForm.student_enrollment_id" required>
              <option value="">Select student</option>
              <option v-for="enrollment in enrollments" :key="enrollment.id" :value="enrollment.id">
                {{ enrollmentLabel(enrollment) }}
              </option>
            </select>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="marks-obtained">Marks obtained</label>
              <input id="marks-obtained" v-model="markForm.marks_obtained" :disabled="markForm.is_absent" min="0" step="0.01" type="number" />
            </div>
            <label class="check-field">
              <input v-model="markForm.is_absent" type="checkbox" />
              Absent
            </label>
          </div>
          <div class="field">
            <label for="absent-reason">Absent reason</label>
            <input id="absent-reason" v-model="markForm.absent_reason" :disabled="!markForm.is_absent" type="text" />
          </div>
          <div class="form-actions">
            <button class="button" type="submit" :disabled="savingMark">{{ savingMark ? 'Saving marks' : 'Save marks' }}</button>
            <button class="button secondary" type="button" @click="saveMarksDraft">Save offline draft</button>
          </div>
        </form>

        <form class="surface record-form" @submit.prevent="saveGradeScale">
          <div>
            <p class="eyebrow">Grade configuration</p>
            <h2>Add grade scale</h2>
            <p>Use weighted GPA rules when exam types carry term weightage.</p>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="grade-name">Name</label>
              <input id="grade-name" v-model="gradeForm.name" required type="text" placeholder="A Plus" />
            </div>
            <div class="field">
              <label for="grade-code">Code</label>
              <input id="grade-code" v-model="gradeForm.code" required type="text" placeholder="A+" />
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="min-percent">Min percent</label>
              <input id="min-percent" v-model="gradeForm.min_percent" min="0" max="100" step="0.01" type="number" />
            </div>
            <div class="field">
              <label for="max-percent">Max percent</label>
              <input id="max-percent" v-model="gradeForm.max_percent" min="0" max="100" step="0.01" type="number" />
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="grade-point">Grade point</label>
              <input id="grade-point" v-model="gradeForm.grade_point" min="0" max="5" step="0.01" type="number" />
            </div>
            <div class="field">
              <label for="fail-below">Fail below</label>
              <input id="fail-below" v-model="gradeForm.fail_below_percent" min="0" max="100" step="0.01" type="number" />
            </div>
          </div>
          <div class="field">
            <label for="gpa-method">GPA method</label>
            <select id="gpa-method" v-model="gradeForm.gpa_calculation_method">
              <option value="weighted">Weighted</option>
              <option value="simple_average">Simple average</option>
            </select>
          </div>
          <button class="button" type="submit" :disabled="savingGrade">{{ savingGrade ? 'Saving grade' : 'Save grade scale' }}</button>
        </form>
      </section>

      <section class="surface record-list">
        <div class="list-header">
          <div>
            <p class="eyebrow">Verification queue</p>
            <h2>Marks entries</h2>
          </div>
          <button class="button secondary" type="button" @click="loadWorkspace">Refresh</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>Exam</th>
                <th>Subject</th>
                <th>Marks</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in marks" :key="entry.id">
                <td>{{ entry.student_enrollment?.student?.full_name || 'Student' }}</td>
                <td>{{ entry.exam?.name || entry.exam_id }}</td>
                <td>{{ entry.class_subject?.subject?.name || entry.class_subject_id }}</td>
                <td>{{ entry.is_absent ? `Absent${entry.absent_reason ? ` / ${entry.absent_reason}` : ''}` : `${entry.marks_obtained} / ${entry.full_marks}` }}</td>
                <td><span class="status-pill">{{ entry.verification_status }}</span></td>
                <td>
                  <button class="button secondary compact" type="button" :disabled="verifyingId === entry.id" @click="verifyEntry(entry, 'verified')">Verify</button>
                </td>
              </tr>
              <tr v-if="!marks.length">
                <td colspan="6">No marks entries yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="surface record-list">
        <div class="list-header">
          <div>
            <p class="eyebrow">Grade rules</p>
            <h2>Grade scales</h2>
          </div>
        </div>
        <div class="grade-grid">
          <article v-for="grade in gradeScales" :key="grade.id" class="grade-row">
            <strong>{{ grade.code }}</strong>
            <span>{{ grade.min_percent }} - {{ grade.max_percent }}%</span>
            <span>GPA {{ grade.grade_point }}</span>
            <span>{{ grade.gpa_calculation_method }}</span>
          </article>
        </div>
      </section>
    </section>
  </main>
</template>

<style scoped>
.operation-shell {
  display: grid;
  min-height: 100vh;
  grid-template-columns: 250px minmax(0, 1fr);
  background: #f7f3ef;
}

.operation-nav {
  display: flex;
  flex-direction: column;
  gap: 28px;
  border-right: 1px solid rgba(17, 24, 39, 0.08);
  padding: 24px;
  background: #fff;
}

.brand {
  display: flex;
  gap: 12px;
  align-items: center;
}

.brand span {
  display: grid;
  width: 38px;
  height: 38px;
  place-items: center;
  border-radius: 8px;
  background: #be3455;
  color: #fff;
  font-weight: 900;
}

nav {
  display: grid;
  gap: 10px;
}

nav a {
  border-radius: 8px;
  padding: 10px 12px;
  color: #4b5563;
  font-weight: 700;
}

nav a:hover {
  background: rgba(255, 255, 255, 0.62);
  color: #be3455;
}

.operation-workspace {
  display: grid;
  align-content: start;
  gap: 20px;
  padding: 30px;
}

.workspace-header,
.list-header {
  display: flex;
  gap: 16px;
  align-items: center;
  justify-content: space-between;
}

.workspace-header h1 {
  max-width: 780px;
  margin: 0;
  color: #111827;
  font-size: clamp(1.8rem, 4vw, 3rem);
  line-height: 1.04;
}

.eyebrow {
  margin: 0 0 8px;
  color: #be3455;
  font-size: 0.76rem;
  font-weight: 900;
  letter-spacing: 0;
  text-transform: uppercase;
}

.summary-grid,
.workspace-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.workspace-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.summary-item,
.record-form,
.record-list {
  padding: 22px;
}

.summary-item {
  display: grid;
  gap: 10px;
}

.summary-item span {
  color: #6b7280;
  font-weight: 800;
}

.summary-item strong {
  color: #111827;
  font-size: 2rem;
}

.record-form {
  display: grid;
  align-content: start;
  gap: 16px;
}

.record-form h2,
.record-list h2 {
  margin: 0;
  color: #111827;
}

.record-form p {
  margin: 8px 0 0;
  color: #6b7280;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.form-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.check-field {
  display: flex;
  min-height: 46px;
  align-items: center;
  gap: 10px;
  color: #4b5563;
  font-weight: 800;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 780px;
}

th,
td {
  border-bottom: 1px solid #e3ebe7;
  padding: 14px 10px;
  color: #4b5563;
  text-align: left;
}

th {
  color: #111827;
  font-size: 0.82rem;
  text-transform: uppercase;
}

.status-pill {
  border-radius: 8px;
  padding: 5px 8px;
  background: rgba(255, 255, 255, 0.62);
  color: #be3455;
  font-weight: 800;
  text-transform: capitalize;
}

.button.compact {
  min-height: 34px;
  padding: 0 10px;
}

.grade-grid {
  display: grid;
  gap: 10px;
}

.grade-row {
  display: grid;
  grid-template-columns: 0.6fr 1fr 1fr 1fr;
  gap: 12px;
  border-top: 1px solid #e3ebe7;
  padding: 12px 0;
  color: #4b5563;
}

@media (max-width: 900px) {
  .operation-shell,
  .summary-grid,
  .workspace-grid,
  .form-row,
  .grade-row {
    grid-template-columns: 1fr;
  }

  .operation-nav {
    border-right: 0;
    border-bottom: 1px solid rgba(17, 24, 39, 0.08);
  }

  .workspace-header,
  .list-header {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
