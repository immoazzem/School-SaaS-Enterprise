<script setup lang="ts">
import type { AcademicClass, ClassSubject, Subject } from '~/composables/useApi'

interface ClassSubjectsResponse { data: ClassSubject[] }
interface ClassSubjectResponse { data: ClassSubject }
interface ClassesResponse { data: AcademicClass[] }
interface SubjectsResponse { data: Subject[] }

const api = useApi()
const route = useRoute()
const auth = useAuth()

const assignments = ref<ClassSubject[]>([])
const classes = ref<AcademicClass[]>([])
const subjects = ref<Subject[]>([])
const loading = ref(false)
const saving = ref(false)
const archivingAssignmentId = ref<number | null>(null)
const error = ref('')
const success = ref('')
const editingAssignmentId = ref<number | null>(null)
const classFilter = ref('')
const subjectFilter = ref('')
const statusFilter = ref('active')
const search = ref('')
const form = reactive({
  academic_class_id: '',
  subject_id: '',
  full_marks: 100,
  pass_marks: 33,
  subjective_marks: '',
  sort_order: 0,
  status: 'active',
})

const schoolId = computed(() => Number(route.params.schoolId))
const activeCount = computed(() => assignments.value.filter((assignment) => assignment.status === 'active').length)

async function loadOptions() {
  const [classResponse, subjectResponse] = await Promise.all([
    api.request<ClassesResponse>(`/schools/${schoolId.value}/academic-classes?status=active`),
    api.request<SubjectsResponse>(`/schools/${schoolId.value}/subjects?status=active`),
  ])
  classes.value = classResponse.data
  subjects.value = subjectResponse.data
}

async function loadAssignments() {
  loading.value = true
  error.value = ''

  try {
    const query = new URLSearchParams()

    if (classFilter.value) query.set('academic_class_id', classFilter.value)
    if (subjectFilter.value) query.set('subject_id', subjectFilter.value)
    if (statusFilter.value) query.set('status', statusFilter.value)
    if (search.value.trim()) query.set('search', search.value.trim())

    const suffix = query.toString() ? `?${query.toString()}` : ''
    const response = await api.request<ClassSubjectsResponse>(`/schools/${schoolId.value}/class-subjects${suffix}`)
    assignments.value = response.data
  } catch (assignmentError) {
    error.value = assignmentError instanceof Error ? assignmentError.message : 'Unable to load assignments.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingAssignmentId.value = null
  form.academic_class_id = ''
  form.subject_id = ''
  form.full_marks = 100
  form.pass_marks = 33
  form.subjective_marks = ''
  form.sort_order = 0
  form.status = 'active'
}

function editAssignment(assignment: ClassSubject) {
  editingAssignmentId.value = assignment.id
  form.academic_class_id = String(assignment.academic_class_id)
  form.subject_id = String(assignment.subject_id)
  form.full_marks = assignment.full_marks
  form.pass_marks = assignment.pass_marks
  form.subjective_marks = assignment.subjective_marks === null ? '' : String(assignment.subjective_marks)
  form.sort_order = assignment.sort_order
  form.status = assignment.status
}

async function saveAssignment() {
  saving.value = true
  error.value = ''
  success.value = ''

  const payload = {
    academic_class_id: Number(form.academic_class_id),
    subject_id: Number(form.subject_id),
    full_marks: Number(form.full_marks),
    pass_marks: Number(form.pass_marks),
    subjective_marks: form.subjective_marks === '' ? null : Number(form.subjective_marks),
    sort_order: Number(form.sort_order),
    status: form.status,
  }

  try {
    if (editingAssignmentId.value) {
      await api.request<ClassSubjectResponse>(`/schools/${schoolId.value}/class-subjects/${editingAssignmentId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      success.value = 'Assignment updated.'
    } else {
      await api.request<ClassSubjectResponse>(`/schools/${schoolId.value}/class-subjects`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Assignment saved.'
    }

    resetForm()
    await loadAssignments()
  } catch (assignmentError) {
    error.value = assignmentError instanceof Error ? assignmentError.message : 'Unable to save assignment.'
  } finally {
    saving.value = false
  }
}

async function archiveAssignment(assignment: ClassSubject) {
  archivingAssignmentId.value = assignment.id
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/class-subjects/${assignment.id}`, {
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

async function refreshFilters() {
  await loadAssignments()
}

onMounted(async () => {
  await auth.refreshProfile()
  await loadOptions()
  await loadAssignments()
})
</script>

<template>
  <main class="catalog-page">
    <header class="catalog-header">
      <div>
        <NuxtLink class="back-link" to="/dashboard">Dashboard</NuxtLink>
        <h1>Class Subjects</h1>
        <p>Assign subjects to classes with the mark rules used later by exams and reports.</p>
      </div>

      <div class="header-actions">
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/academic-classes`">Classes</NuxtLink>
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/subjects`">Subjects</NuxtLink>
      </div>
    </header>

    <section class="summary-grid">
      <article class="surface summary-item">
        <span>Visible assignments</span>
        <strong>{{ assignments.length }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Active assignments</span>
        <strong>{{ activeCount }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Catalog options</span>
        <strong>{{ classes.length }} / {{ subjects.length }}</strong>
      </article>
    </section>

    <section class="catalog-grid">
      <form class="surface catalog-form" @submit.prevent="saveAssignment">
        <div>
          <p class="muted">{{ editingAssignmentId ? 'Edit assignment' : 'New assignment' }}</p>
          <h2>{{ editingAssignmentId ? 'Update class subject' : 'Assign subject' }}</h2>
        </div>

        <div class="field">
          <label for="assignment-class">Class</label>
          <select id="assignment-class" v-model="form.academic_class_id" required>
            <option value="">Select class</option>
            <option v-for="academicClass in classes" :key="academicClass.id" :value="academicClass.id">
              {{ academicClass.name }} ({{ academicClass.code }})
            </option>
          </select>
        </div>

        <div class="field">
          <label for="assignment-subject">Subject</label>
          <select id="assignment-subject" v-model="form.subject_id" required>
            <option value="">Select subject</option>
            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
              {{ subject.name }} ({{ subject.code }})
            </option>
          </select>
        </div>

        <div class="form-row">
          <div class="field">
            <label for="full-marks">Full marks</label>
            <input id="full-marks" v-model="form.full_marks" min="1" type="number" />
          </div>
          <div class="field">
            <label for="pass-marks">Pass marks</label>
            <input id="pass-marks" v-model="form.pass_marks" min="1" type="number" />
          </div>
        </div>

        <div class="form-row">
          <div class="field">
            <label for="subjective-marks">Subjective marks</label>
            <input id="subjective-marks" v-model="form.subjective_marks" min="0" type="number" placeholder="60" />
          </div>
          <div class="field">
            <label for="assignment-order">Order</label>
            <input id="assignment-order" v-model="form.sort_order" min="0" type="number" />
          </div>
        </div>

        <div class="field">
          <label for="assignment-status">Status</label>
          <select id="assignment-status" v-model="form.status">
            <option value="active">Active</option>
            <option value="archived">Archived</option>
          </select>
        </div>

        <p v-if="error" class="error">{{ error }}</p>
        <p v-if="success" class="success">{{ success }}</p>

        <div class="form-actions">
          <button class="button" type="submit" :disabled="saving">
            {{ saving ? 'Saving' : editingAssignmentId ? 'Update assignment' : 'Save assignment' }}
          </button>
          <button v-if="editingAssignmentId" class="button secondary" type="button" @click="resetForm">Cancel</button>
        </div>
      </form>

      <section class="surface catalog-list">
        <div class="list-heading">
          <div>
            <p class="muted">Assignment register</p>
            <h2>Class subject catalog</h2>
          </div>

          <form class="filters" @submit.prevent="refreshFilters">
            <input v-model="search" aria-label="Search assignments" placeholder="Search" />
            <select v-model="classFilter" aria-label="Class filter" @change="refreshFilters">
              <option value="">All classes</option>
              <option v-for="academicClass in classes" :key="academicClass.id" :value="academicClass.id">
                {{ academicClass.name }}
              </option>
            </select>
            <select v-model="subjectFilter" aria-label="Subject filter" @change="refreshFilters">
              <option value="">All subjects</option>
              <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
            </select>
            <select v-model="statusFilter" aria-label="Status filter" @change="refreshFilters">
              <option value="">All status</option>
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
            <button class="button secondary" type="submit">Search</button>
          </form>
        </div>

        <p v-if="loading" class="muted">Loading assignments</p>

        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Class</th>
                <th>Subject</th>
                <th>Marks</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="assignment in assignments" :key="assignment.id">
                <td>{{ assignment.academic_class?.name || assignment.academic_class_id }}</td>
                <td>
                  <strong>{{ assignment.subject?.name || assignment.subject_id }}</strong>
                  <small>{{ assignment.subject?.code }}</small>
                </td>
                <td>{{ assignment.pass_marks }} / {{ assignment.full_marks }}</td>
                <td>{{ assignment.status }}</td>
                <td>
                  <div class="row-actions">
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
                </td>
              </tr>
              <tr v-if="assignments.length === 0">
                <td colspan="5">No class subjects yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </main>
</template>

<style scoped>
.catalog-page {
  min-height: 100vh;
  padding: 30px;
  background: #f7f3ef;
}

.catalog-header,
.list-heading,
.form-row,
.form-actions,
.row-actions,
.filters,
.header-actions {
  display: flex;
  gap: 14px;
}

.catalog-header {
  align-items: end;
  justify-content: space-between;
  margin-bottom: 20px;
}

.list-heading,
.form-actions,
.header-actions {
  align-items: center;
  justify-content: space-between;
}

.back-link,
.text-button {
  color: #be3455;
  font-weight: 800;
}

h1 {
  margin: 12px 0 0;
  color: #111827;
  font-size: clamp(2.1rem, 5.8vw, 4.4rem);
  line-height: 0.95;
}

h2 {
  margin: 0;
  color: #111827;
}

p {
  color: #56635e;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 18px;
}

.surface {
  border: 1px solid #dfe6e2;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 18px 45px rgb(24 44 35 / 8%);
}

.summary-item,
.catalog-form,
.catalog-list {
  padding: 22px;
}

.summary-item span,
.muted,
small {
  color: #6a7771;
}

.summary-item strong {
  display: block;
  margin-top: 8px;
  color: #111827;
  font-size: 1.8rem;
}

.catalog-grid {
  display: grid;
  grid-template-columns: minmax(280px, 390px) minmax(0, 1fr);
  gap: 18px;
  align-items: start;
}

.catalog-form,
.field {
  display: grid;
  gap: 12px;
}

.form-row > * {
  flex: 1;
}

label {
  color: #26342f;
  font-weight: 800;
}

input,
select {
  width: 100%;
  border: 1px solid #ccd8d2;
  border-radius: 8px;
  padding: 12px 13px;
  color: #111827;
  background: #ffffff;
}

.button {
  border: 0;
  border-radius: 8px;
  padding: 11px 16px;
  color: #ffffff;
  background: #163f34;
  font-weight: 800;
  cursor: pointer;
}

.button.secondary {
  border: 1px solid #cbd8d2;
  color: #163f34;
  background: #ffffff;
}

.button:disabled,
.text-button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.filters {
  flex-wrap: wrap;
  justify-content: flex-end;
}

.filters input,
.filters select {
  width: auto;
  min-width: 130px;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 720px;
}

th,
td {
  border-bottom: 1px solid #e5ece8;
  padding: 14px 12px;
  text-align: left;
}

th {
  color: #6a7771;
  font-size: 0.76rem;
  text-transform: uppercase;
}

td strong,
td small {
  display: block;
}

.text-button {
  border: 0;
  padding: 0;
  background: transparent;
  cursor: pointer;
}

.error,
.success {
  border-radius: 8px;
  padding: 12px;
}

.error {
  color: #9b1c1c;
  background: #fde8e8;
}

.success {
  color: #be3455;
  background: #e1f3ec;
}

@media (max-width: 900px) {
  .catalog-page {
    padding: 18px;
  }

  .catalog-header,
  .list-heading,
  .form-row,
  .header-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .summary-grid,
  .catalog-grid {
    grid-template-columns: 1fr;
  }

  .filters,
  .filters input,
  .filters select {
    width: 100%;
  }
}
</style>
