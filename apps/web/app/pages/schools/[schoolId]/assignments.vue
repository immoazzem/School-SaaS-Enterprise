<script setup lang="ts">
import type {
  AcademicClass,
  Assignment,
  AssignmentSubmission,
  StudentEnrollment,
  Subject,
} from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface ItemResponse<T> {
  data: T
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const academicClasses = ref<AcademicClass[]>([])
const subjects = ref<Subject[]>([])
const enrollments = ref<StudentEnrollment[]>([])
const assignments = ref<Assignment[]>([])
const submissions = ref<AssignmentSubmission[]>([])
const loading = ref(false)
const savingAssignment = ref(false)
const savingSubmission = ref(false)
const editingAssignmentId = ref<number | null>(null)
const editingSubmissionId = ref<number | null>(null)
const archivingAssignmentId = ref<number | null>(null)
const error = ref('')
const success = ref('')

const assignmentFilters = reactive({
  academic_class_id: '',
  subject_id: '',
  is_published: '',
  status: 'active',
})

const submissionFilters = reactive({
  assignment_id: '',
  status: '',
})

const assignmentForm = reactive({
  academic_class_id: '',
  subject_id: '',
  title: '',
  description: '',
  due_date: '',
  attachment_path: '',
  is_published: false,
  status: 'active',
})

const submissionForm = reactive({
  assignment_id: '',
  student_enrollment_id: '',
  submitted_at: '',
  attachment_path: '',
  marks_awarded: '',
  feedback: '',
  status: 'submitted',
})

const publishedCount = computed(() => assignments.value.filter((assignment) => assignment.is_published).length)
const gradedCount = computed(() => submissions.value.filter((submission) => submission.status === 'graded').length)
const openAssignments = computed(() =>
  assignments.value.filter((assignment) => assignment.status === 'active' && assignment.is_published).length,
)

function enrollmentLabel(enrollment: StudentEnrollment) {
  const student = enrollment.student
  const admission = student?.admission_no ? ` / ${student.admission_no}` : ''
  const roll = enrollment.roll_no ? ` / Roll ${enrollment.roll_no}` : ''

  return `${student?.full_name || 'Student'}${admission}${roll}`
}

function resetAssignmentForm() {
  editingAssignmentId.value = null
  assignmentForm.academic_class_id = assignmentFilters.academic_class_id || (academicClasses.value[0] ? String(academicClasses.value[0].id) : '')
  assignmentForm.subject_id = assignmentFilters.subject_id || (subjects.value[0] ? String(subjects.value[0].id) : '')
  assignmentForm.title = ''
  assignmentForm.description = ''
  assignmentForm.due_date = new Date().toISOString().slice(0, 10)
  assignmentForm.attachment_path = ''
  assignmentForm.is_published = false
  assignmentForm.status = 'active'
}

function resetSubmissionForm() {
  editingSubmissionId.value = null
  submissionForm.assignment_id = submissionFilters.assignment_id || (assignments.value[0] ? String(assignments.value[0].id) : '')
  submissionForm.student_enrollment_id = enrollments.value[0] ? String(enrollments.value[0].id) : ''
  submissionForm.submitted_at = ''
  submissionForm.attachment_path = ''
  submissionForm.marks_awarded = ''
  submissionForm.feedback = ''
  submissionForm.status = 'submitted'
}

function editAssignment(assignment: Assignment) {
  editingAssignmentId.value = assignment.id
  assignmentForm.academic_class_id = String(assignment.academic_class_id)
  assignmentForm.subject_id = String(assignment.subject_id)
  assignmentForm.title = assignment.title
  assignmentForm.description = assignment.description || ''
  assignmentForm.due_date = assignment.due_date.slice(0, 10)
  assignmentForm.attachment_path = assignment.attachment_path || ''
  assignmentForm.is_published = assignment.is_published
  assignmentForm.status = assignment.status
}

function editSubmission(submission: AssignmentSubmission) {
  editingSubmissionId.value = submission.id
  submissionForm.assignment_id = String(submission.assignment_id)
  submissionForm.student_enrollment_id = String(submission.student_enrollment_id)
  submissionForm.submitted_at = submission.submitted_at ? submission.submitted_at.slice(0, 16) : ''
  submissionForm.attachment_path = submission.attachment_path || ''
  submissionForm.marks_awarded = submission.marks_awarded || ''
  submissionForm.feedback = submission.feedback || ''
  submissionForm.status = submission.status
}

function assignmentPayload() {
  return {
    academic_class_id: Number(assignmentForm.academic_class_id),
    subject_id: Number(assignmentForm.subject_id),
    title: assignmentForm.title,
    description: assignmentForm.description || null,
    due_date: assignmentForm.due_date,
    attachment_path: assignmentForm.attachment_path || null,
    is_published: assignmentForm.is_published,
    status: assignmentForm.status,
  }
}

function submissionPayload() {
  return {
    assignment_id: Number(submissionForm.assignment_id),
    student_enrollment_id: Number(submissionForm.student_enrollment_id),
    submitted_at: submissionForm.submitted_at || null,
    attachment_path: submissionForm.attachment_path || null,
    marks_awarded: submissionForm.marks_awarded ? Number(submissionForm.marks_awarded) : null,
    feedback: submissionForm.feedback || null,
    status: submissionForm.status,
  }
}

function queryFrom(filters: Record<string, string>) {
  const query = new URLSearchParams()

  for (const [key, value] of Object.entries(filters)) {
    if (value !== '') {
      query.set(key, value)
    }
  }

  query.set('per_page', '100')

  return query.toString()
}

async function loadOptions() {
  loading.value = true
  error.value = ''

  try {
    const [classResponse, subjectResponse, enrollmentResponse] = await Promise.all([
      api.request<ListResponse<AcademicClass>>(`/schools/${schoolId.value}/academic-classes?status=active&per_page=100`),
      api.request<ListResponse<Subject>>(`/schools/${schoolId.value}/subjects?status=active&per_page=100`),
      api.request<ListResponse<StudentEnrollment>>(`/schools/${schoolId.value}/student-enrollments?status=active&per_page=100`),
    ])

    academicClasses.value = classResponse.data
    subjects.value = subjectResponse.data
    enrollments.value = enrollmentResponse.data

    if (!assignmentFilters.academic_class_id && academicClasses.value[0]) {
      assignmentFilters.academic_class_id = String(academicClasses.value[0].id)
    }

    resetAssignmentForm()
    await loadAssignments()
    resetSubmissionForm()
    await loadSubmissions()
  } catch (optionsError) {
    error.value = optionsError instanceof Error ? optionsError.message : 'Unable to load assignment workspace.'
  } finally {
    loading.value = false
  }
}

async function loadAssignments() {
  const query = queryFrom(assignmentFilters)
  const response = await api.request<ListResponse<Assignment>>(`/schools/${schoolId.value}/assignments?${query}`)
  assignments.value = response.data
}

async function loadSubmissions() {
  const query = queryFrom(submissionFilters)
  const response = await api.request<ListResponse<AssignmentSubmission>>(
    `/schools/${schoolId.value}/assignment-submissions?${query}`,
  )
  submissions.value = response.data
}

async function applyAssignmentFilters() {
  error.value = ''

  try {
    await loadAssignments()
    resetAssignmentForm()
    resetSubmissionForm()
  } catch (filterError) {
    error.value = filterError instanceof Error ? filterError.message : 'Unable to filter assignments.'
  }
}

async function applySubmissionFilters() {
  error.value = ''

  try {
    await loadSubmissions()
    resetSubmissionForm()
  } catch (filterError) {
    error.value = filterError instanceof Error ? filterError.message : 'Unable to filter submissions.'
  }
}

async function saveAssignment() {
  savingAssignment.value = true
  error.value = ''
  success.value = ''

  try {
    if (editingAssignmentId.value) {
      await api.request<ItemResponse<Assignment>>(`/schools/${schoolId.value}/assignments/${editingAssignmentId.value}`, {
        method: 'PATCH',
        body: assignmentPayload(),
      })
      success.value = 'Assignment updated.'
    } else {
      await api.request<ItemResponse<Assignment>>(`/schools/${schoolId.value}/assignments`, {
        method: 'POST',
        body: assignmentPayload(),
      })
      success.value = 'Assignment saved.'
    }

    await loadAssignments()
    resetAssignmentForm()
    resetSubmissionForm()
  } catch (assignmentError) {
    error.value = assignmentError instanceof Error ? assignmentError.message : 'Unable to save assignment.'
  } finally {
    savingAssignment.value = false
  }
}

async function saveSubmission() {
  savingSubmission.value = true
  error.value = ''
  success.value = ''

  try {
    if (editingSubmissionId.value) {
      await api.request<ItemResponse<AssignmentSubmission>>(
        `/schools/${schoolId.value}/assignment-submissions/${editingSubmissionId.value}`,
        {
          method: 'PATCH',
          body: submissionPayload(),
        },
      )
      success.value = 'Submission updated.'
    } else {
      await api.request<ItemResponse<AssignmentSubmission>>(`/schools/${schoolId.value}/assignment-submissions`, {
        method: 'POST',
        body: submissionPayload(),
      })
      success.value = 'Submission saved.'
    }

    await Promise.all([loadAssignments(), loadSubmissions()])
    resetSubmissionForm()
  } catch (submissionError) {
    error.value = submissionError instanceof Error ? submissionError.message : 'Unable to save submission.'
  } finally {
    savingSubmission.value = false
  }
}

async function archiveAssignment(assignment: Assignment) {
  archivingAssignmentId.value = assignment.id
  error.value = ''
  success.value = ''

  try {
    await api.request<ItemResponse<Assignment>>(`/schools/${schoolId.value}/assignments/${assignment.id}`, {
      method: 'PATCH',
      body: { status: 'archived' },
    })
    success.value = 'Assignment archived.'
    await loadAssignments()
  } catch (assignmentError) {
    error.value = assignmentError instanceof Error ? assignmentError.message : 'Unable to archive assignment.'
  } finally {
    archivingAssignmentId.value = null
  }
}

onMounted(loadOptions)
</script>

<template>
  <main class="operation-shell">
    <aside class="operation-nav">
      <NuxtLink class="brand" to="/dashboard">
        <span>EA</span>
        <strong>School SaaS</strong>
      </NuxtLink>
      <nav aria-label="Assignments navigation">
        <NuxtLink :to="`/schools/${schoolId}/academic-classes`">Classes</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/subjects`">Subjects</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/enrollments`">Enrollments</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/timetable`">Timetable</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/reports`">Reports</NuxtLink>
      </nav>
    </aside>

    <section class="operation-workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">Assignments</p>
          <h1>Track homework from issue to grading.</h1>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading" class="muted">Loading assignment workspace</p>

      <section class="summary-grid">
        <article class="summary-item surface">
          <span>Assignments</span>
          <strong>{{ assignments.length }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Published</span>
          <strong>{{ publishedCount }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Graded</span>
          <strong>{{ gradedCount }}</strong>
        </article>
      </section>

      <section class="workspace-grid assignment-panels">
        <form class="record-form surface" @submit.prevent="saveAssignment">
          <div>
            <p class="eyebrow">{{ editingAssignmentId ? 'Edit assignment' : 'New assignment' }}</p>
            <h2>{{ editingAssignmentId ? 'Update assignment' : 'Issue homework' }}</h2>
            <p>Assignments are class and subject scoped.</p>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="assignment-class">Class</label>
              <select id="assignment-class" v-model="assignmentForm.academic_class_id" required>
                <option value="">Select class</option>
                <option v-for="academicClass in academicClasses" :key="academicClass.id" :value="academicClass.id">
                  {{ academicClass.name }}
                </option>
              </select>
            </div>
            <div class="field">
              <label for="assignment-subject">Subject</label>
              <select id="assignment-subject" v-model="assignmentForm.subject_id" required>
                <option value="">Select subject</option>
                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
              </select>
            </div>
          </div>

          <div class="field">
            <label for="assignment-title">Title</label>
            <input id="assignment-title" v-model="assignmentForm.title" required placeholder="Algebra practice" />
          </div>

          <div class="field">
            <label for="assignment-description">Description</label>
            <textarea
              id="assignment-description"
              v-model="assignmentForm.description"
              rows="4"
              placeholder="Instructions for students"
            />
          </div>

          <div class="form-row">
            <div class="field">
              <label for="assignment-due">Due date</label>
              <input id="assignment-due" v-model="assignmentForm.due_date" required type="date" />
            </div>
            <div class="field">
              <label for="assignment-attachment">Attachment path</label>
              <input id="assignment-attachment" v-model="assignmentForm.attachment_path" placeholder="Optional path" />
            </div>
          </div>

          <div class="form-row compact-row">
            <label class="check-field">
              <input v-model="assignmentForm.is_published" type="checkbox" />
              Published
            </label>
            <div class="field">
              <label for="assignment-status">Status</label>
              <select id="assignment-status" v-model="assignmentForm.status">
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>

          <div class="strip-actions">
            <button class="button" type="submit" :disabled="savingAssignment">
              {{ savingAssignment ? 'Saving' : editingAssignmentId ? 'Update assignment' : 'Save assignment' }}
            </button>
            <button v-if="editingAssignmentId" class="button secondary" type="button" @click="resetAssignmentForm">
              Cancel
            </button>
          </div>
        </form>

        <form class="record-form surface" @submit.prevent="saveSubmission">
          <div>
            <p class="eyebrow">{{ editingSubmissionId ? 'Edit submission' : 'Submission' }}</p>
            <h2>{{ editingSubmissionId ? 'Update grading' : 'Record submission' }}</h2>
            <p>Submissions are unique per student enrollment.</p>
          </div>

          <div class="field">
            <label for="submission-assignment">Assignment</label>
            <select id="submission-assignment" v-model="submissionForm.assignment_id" required>
              <option value="">Select assignment</option>
              <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                {{ assignment.title }}
              </option>
            </select>
          </div>

          <div class="field">
            <label for="submission-enrollment">Student</label>
            <select id="submission-enrollment" v-model="submissionForm.student_enrollment_id" required>
              <option value="">Select student</option>
              <option v-for="enrollment in enrollments" :key="enrollment.id" :value="enrollment.id">
                {{ enrollmentLabel(enrollment) }}
              </option>
            </select>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="submission-time">Submitted at</label>
              <input id="submission-time" v-model="submissionForm.submitted_at" type="datetime-local" />
            </div>
            <div class="field">
              <label for="submission-status">Status</label>
              <select id="submission-status" v-model="submissionForm.status">
                <option value="submitted">Submitted</option>
                <option value="late">Late</option>
                <option value="graded">Graded</option>
                <option value="returned">Returned</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="submission-marks">Marks</label>
              <input id="submission-marks" v-model="submissionForm.marks_awarded" min="0" step="0.01" type="number" />
            </div>
            <div class="field">
              <label for="submission-attachment">Attachment path</label>
              <input id="submission-attachment" v-model="submissionForm.attachment_path" placeholder="Optional path" />
            </div>
          </div>

          <div class="field">
            <label for="submission-feedback">Feedback</label>
            <textarea id="submission-feedback" v-model="submissionForm.feedback" rows="3" placeholder="Teacher feedback" />
          </div>

          <div class="strip-actions">
            <button class="button" type="submit" :disabled="savingSubmission || !assignments.length">
              {{ savingSubmission ? 'Saving' : editingSubmissionId ? 'Update submission' : 'Save submission' }}
            </button>
            <button v-if="editingSubmissionId" class="button secondary" type="button" @click="resetSubmissionForm">
              Cancel
            </button>
          </div>
        </form>
      </section>

      <section class="workspace-grid">
        <section class="record-list surface">
          <div class="list-header">
            <div>
              <p class="eyebrow">Assignment register</p>
              <h2>{{ openAssignments }} open assignments</h2>
            </div>
          </div>

          <form class="filter-grid" @submit.prevent="applyAssignmentFilters">
            <select v-model="assignmentFilters.academic_class_id" aria-label="Class filter">
              <option value="">All classes</option>
              <option v-for="academicClass in academicClasses" :key="academicClass.id" :value="academicClass.id">
                {{ academicClass.name }}
              </option>
            </select>
            <select v-model="assignmentFilters.subject_id" aria-label="Subject filter">
              <option value="">All subjects</option>
              <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
            </select>
            <select v-model="assignmentFilters.is_published" aria-label="Published filter">
              <option value="">All publish states</option>
              <option value="1">Published</option>
              <option value="0">Draft</option>
            </select>
            <select v-model="assignmentFilters.status" aria-label="Assignment status filter">
              <option value="">All status</option>
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
            <button class="button secondary" type="submit">Apply filters</button>
          </form>

          <div class="assignment-stack">
            <article v-for="assignment in assignments" :key="assignment.id" class="assignment-row">
              <div>
                <strong>{{ assignment.title }}</strong>
                <span>
                  {{ assignment.academic_class?.name || 'Class' }} / {{ assignment.subject?.name || 'Subject' }}
                </span>
                <small>Due {{ assignment.due_date }} / {{ assignment.submissions_count || 0 }} submissions</small>
              </div>
              <div class="row-actions">
                <span class="status-pill">{{ assignment.is_published ? 'Published' : 'Draft' }}</span>
                <button class="text-button" type="button" @click="editAssignment(assignment)">Edit</button>
                <button
                  class="text-button"
                  type="button"
                  :disabled="assignment.status === 'archived' || archivingAssignmentId === assignment.id"
                  @click="archiveAssignment(assignment)"
                >
                  Archive
                </button>
              </div>
            </article>
            <p v-if="assignments.length === 0" class="muted">No assignments match the current filters.</p>
          </div>
        </section>

        <section class="record-list surface">
          <div class="list-header">
            <div>
              <p class="eyebrow">Submission register</p>
              <h2>{{ submissions.length }} submissions</h2>
            </div>
          </div>

          <form class="filter-grid" @submit.prevent="applySubmissionFilters">
            <select v-model="submissionFilters.assignment_id" aria-label="Assignment filter">
              <option value="">All assignments</option>
              <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                {{ assignment.title }}
              </option>
            </select>
            <select v-model="submissionFilters.status" aria-label="Submission status filter">
              <option value="">All status</option>
              <option value="submitted">Submitted</option>
              <option value="late">Late</option>
              <option value="graded">Graded</option>
              <option value="returned">Returned</option>
            </select>
            <button class="button secondary" type="submit">Apply filters</button>
          </form>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Assignment</th>
                  <th>Status</th>
                  <th>Marks</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="submission in submissions" :key="submission.id">
                  <td>{{ submission.student_enrollment?.student?.full_name || 'Student' }}</td>
                  <td>{{ submission.assignment?.title || 'Assignment' }}</td>
                  <td><span class="status-pill">{{ submission.status }}</span></td>
                  <td>{{ submission.marks_awarded || '-' }}</td>
                  <td><button class="text-button" type="button" @click="editSubmission(submission)">Edit</button></td>
                </tr>
                <tr v-if="submissions.length === 0">
                  <td colspan="5">No submissions yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>
    </section>
  </main>
</template>

<style scoped>
.assignment-panels {
  grid-template-columns: minmax(320px, 1fr) minmax(320px, 1fr);
}

.compact-row {
  align-items: end;
}

.check-field {
  display: inline-flex;
  min-height: 46px;
  align-items: center;
  gap: 10px;
  color: #4b5563;
  font-weight: 850;
}

.check-field input {
  width: 18px;
  height: 18px;
}

.filter-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin: 16px 0;
}

.filter-grid select {
  min-height: 42px;
  border: 1px solid rgba(17, 24, 39, 0.1);
  border-radius: 8px;
  padding: 0 12px;
  background: rgba(255, 255, 255, 0.78);
  color: #111827;
}

.assignment-stack {
  display: grid;
  gap: 10px;
}

.assignment-row {
  display: flex;
  gap: 16px;
  align-items: center;
  justify-content: space-between;
  border: 1px solid rgba(17, 24, 39, 0.08);
  border-radius: 8px;
  padding: 14px;
  background: rgba(255, 255, 255, 0.62);
}

.assignment-row div:first-child {
  display: grid;
  gap: 4px;
}

.assignment-row strong {
  color: #111827;
}

.assignment-row span,
.assignment-row small {
  color: #6b7280;
}

.row-actions,
.strip-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.text-button {
  border: 0;
  padding: 0;
  background: transparent;
  color: #be3455;
  cursor: pointer;
  font-weight: 850;
}

.text-button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

td .text-button {
  white-space: nowrap;
}

@media (max-width: 1000px) {
  .assignment-panels,
  .workspace-grid {
    grid-template-columns: 1fr;
  }

  .assignment-row {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
