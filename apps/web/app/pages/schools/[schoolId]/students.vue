<script setup lang="ts">
import type { Guardian, Student } from '~/composables/useApi'

interface GuardiansResponse {
  data: Guardian[]
}

interface GuardianResponse {
  data: Guardian
}

interface StudentsResponse {
  data: Student[]
}

interface StudentResponse {
  data: Student
}

const api = useApi()
const route = useRoute()
const auth = useAuth()

const guardians = ref<Guardian[]>([])
const students = ref<Student[]>([])
const loading = ref(false)
const savingGuardian = ref(false)
const savingStudent = ref(false)
const error = ref('')
const success = ref('')
const studentSearch = ref('')
const guardianSearch = ref('')
const studentStatus = ref('active')
const guardianStatus = ref('active')
const editingStudentId = ref<number | null>(null)
const editingGuardianId = ref<number | null>(null)

const guardianForm = reactive({
  full_name: '',
  relationship: 'Father',
  phone: '',
  email: '',
  occupation: '',
  address: '',
  status: 'active',
})

const studentForm = reactive({
  guardian_id: '',
  admission_no: '',
  full_name: '',
  father_name: '',
  mother_name: '',
  email: '',
  phone: '',
  gender: '',
  religion: '',
  date_of_birth: '',
  admitted_on: '',
  address: '',
  medical_notes: '',
  status: 'active',
})

const schoolId = computed(() => Number(route.params.schoolId))
const activeGuardians = computed(() => guardians.value.filter((guardian) => guardian.status === 'active'))
const activeStudents = computed(() => students.value.filter((student) => student.status === 'active').length)

function toDateInput(value: string | null) {
  return value ? value.slice(0, 10) : ''
}

async function loadGuardians() {
  const query = new URLSearchParams()

  if (guardianStatus.value) {
    query.set('status', guardianStatus.value)
  }

  if (guardianSearch.value.trim()) {
    query.set('search', guardianSearch.value.trim())
  }

  const suffix = query.toString() ? `?${query.toString()}` : ''
  const response = await api.request<GuardiansResponse>(`/schools/${schoolId.value}/guardians${suffix}`)
  guardians.value = response.data
}

async function loadStudents() {
  const query = new URLSearchParams()

  if (studentStatus.value) {
    query.set('status', studentStatus.value)
  }

  if (studentSearch.value.trim()) {
    query.set('search', studentSearch.value.trim())
  }

  const suffix = query.toString() ? `?${query.toString()}` : ''
  const response = await api.request<StudentsResponse>(`/schools/${schoolId.value}/students${suffix}`)
  students.value = response.data
}

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    await Promise.all([loadGuardians(), loadStudents()])
  } catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load students.'
  } finally {
    loading.value = false
  }
}

function resetGuardianForm() {
  editingGuardianId.value = null
  guardianForm.full_name = ''
  guardianForm.relationship = 'Father'
  guardianForm.phone = ''
  guardianForm.email = ''
  guardianForm.occupation = ''
  guardianForm.address = ''
  guardianForm.status = 'active'
}

function resetStudentForm() {
  editingStudentId.value = null
  studentForm.guardian_id = ''
  studentForm.admission_no = ''
  studentForm.full_name = ''
  studentForm.father_name = ''
  studentForm.mother_name = ''
  studentForm.email = ''
  studentForm.phone = ''
  studentForm.gender = ''
  studentForm.religion = ''
  studentForm.date_of_birth = ''
  studentForm.admitted_on = ''
  studentForm.address = ''
  studentForm.medical_notes = ''
  studentForm.status = 'active'
}

function editGuardian(guardian: Guardian) {
  editingGuardianId.value = guardian.id
  guardianForm.full_name = guardian.full_name
  guardianForm.relationship = guardian.relationship
  guardianForm.phone = guardian.phone || ''
  guardianForm.email = guardian.email || ''
  guardianForm.occupation = guardian.occupation || ''
  guardianForm.address = guardian.address || ''
  guardianForm.status = guardian.status
}

function editStudent(student: Student) {
  editingStudentId.value = student.id
  studentForm.guardian_id = student.guardian_id ? String(student.guardian_id) : ''
  studentForm.admission_no = student.admission_no
  studentForm.full_name = student.full_name
  studentForm.father_name = student.father_name || ''
  studentForm.mother_name = student.mother_name || ''
  studentForm.email = student.email || ''
  studentForm.phone = student.phone || ''
  studentForm.gender = student.gender || ''
  studentForm.religion = student.religion || ''
  studentForm.date_of_birth = toDateInput(student.date_of_birth)
  studentForm.admitted_on = toDateInput(student.admitted_on)
  studentForm.address = student.address || ''
  studentForm.medical_notes = student.medical_notes || ''
  studentForm.status = student.status
}

async function saveGuardian() {
  savingGuardian.value = true
  error.value = ''
  success.value = ''

  const payload = {
    full_name: guardianForm.full_name,
    relationship: guardianForm.relationship,
    phone: guardianForm.phone || null,
    email: guardianForm.email || null,
    occupation: guardianForm.occupation || null,
    address: guardianForm.address || null,
    status: guardianForm.status,
  }

  try {
    if (editingGuardianId.value) {
      await api.request<GuardianResponse>(`/schools/${schoolId.value}/guardians/${editingGuardianId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      success.value = 'Guardian updated.'
    } else {
      await api.request<GuardianResponse>(`/schools/${schoolId.value}/guardians`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Guardian saved.'
    }

    resetGuardianForm()
    await loadGuardians()
  } catch (guardianError) {
    error.value = guardianError instanceof Error ? guardianError.message : 'Unable to save guardian.'
  } finally {
    savingGuardian.value = false
  }
}

async function saveStudent() {
  savingStudent.value = true
  error.value = ''
  success.value = ''

  const payload = {
    guardian_id: studentForm.guardian_id ? Number(studentForm.guardian_id) : null,
    admission_no: studentForm.admission_no,
    full_name: studentForm.full_name,
    father_name: studentForm.father_name || null,
    mother_name: studentForm.mother_name || null,
    email: studentForm.email || null,
    phone: studentForm.phone || null,
    gender: studentForm.gender || null,
    religion: studentForm.religion || null,
    date_of_birth: studentForm.date_of_birth || null,
    admitted_on: studentForm.admitted_on,
    address: studentForm.address || null,
    medical_notes: studentForm.medical_notes || null,
    status: studentForm.status,
  }

  try {
    if (editingStudentId.value) {
      await api.request<StudentResponse>(`/schools/${schoolId.value}/students/${editingStudentId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      success.value = 'Student updated.'
    } else {
      await api.request<StudentResponse>(`/schools/${schoolId.value}/students`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Student saved.'
    }

    resetStudentForm()
    await loadStudents()
    await loadGuardians()
  } catch (studentError) {
    error.value = studentError instanceof Error ? studentError.message : 'Unable to save student.'
  } finally {
    savingStudent.value = false
  }
}

async function archiveGuardian(guardian: Guardian) {
  await api.request(`/schools/${schoolId.value}/guardians/${guardian.id}`, {
    method: 'PATCH',
    body: { status: 'archived' },
  })
  success.value = 'Guardian archived.'
  await loadGuardians()
}

async function archiveStudent(student: Student) {
  await api.request(`/schools/${schoolId.value}/students/${student.id}`, {
    method: 'PATCH',
    body: { status: 'archived' },
  })
  success.value = 'Student archived.'
  await loadStudents()
}

onMounted(async () => {
  await auth.refreshProfile()
  await loadWorkspace()
})
</script>

<template>
  <main class="people-page">
    <header class="page-header">
      <div>
        <NuxtLink class="back-link" to="/dashboard">Dashboard</NuxtLink>
        <h1>Students</h1>
        <p>Register guardians and students before enrollments, attendance, exams, and fees begin.</p>
      </div>
      <div class="header-actions">
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/employees`">Employees</NuxtLink>
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/academic-classes`">Classes</NuxtLink>
      </div>
    </header>

    <section class="summary-grid">
      <article class="surface summary-item">
        <span>Students</span>
        <strong>{{ students.length }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Active</span>
        <strong>{{ activeStudents }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Guardians</span>
        <strong>{{ guardians.length }}</strong>
      </article>
    </section>

    <p v-if="error" class="error">{{ error }}</p>
    <p v-if="success" class="success">{{ success }}</p>
    <p v-if="loading" class="muted">Loading students</p>

    <section class="form-grid">
      <form class="surface record-form" @submit.prevent="saveGuardian">
        <p class="muted">{{ editingGuardianId ? 'Edit guardian' : 'New guardian' }}</p>
        <h2>{{ editingGuardianId ? 'Update guardian' : 'Add guardian' }}</h2>

        <label for="guardian-name">Name</label>
        <input id="guardian-name" v-model="guardianForm.full_name" required placeholder="Karim Rahman" />

        <div class="form-row">
          <div>
            <label for="guardian-relationship">Relationship</label>
            <input id="guardian-relationship" v-model="guardianForm.relationship" />
          </div>
          <div>
            <label for="guardian-phone">Phone</label>
            <input id="guardian-phone" v-model="guardianForm.phone" />
          </div>
        </div>

        <label for="guardian-email">Email</label>
        <input id="guardian-email" v-model="guardianForm.email" type="email" />

        <label for="guardian-occupation">Occupation</label>
        <input id="guardian-occupation" v-model="guardianForm.occupation" />

        <label for="guardian-address">Address</label>
        <textarea id="guardian-address" v-model="guardianForm.address" rows="3" />

        <div class="form-actions">
          <button class="button" type="submit" :disabled="savingGuardian">
            {{ savingGuardian ? 'Saving' : editingGuardianId ? 'Update guardian' : 'Save guardian' }}
          </button>
          <button v-if="editingGuardianId" class="button secondary" type="button" @click="resetGuardianForm">Cancel</button>
        </div>
      </form>

      <form class="surface record-form" @submit.prevent="saveStudent">
        <p class="muted">{{ editingStudentId ? 'Edit student' : 'New student' }}</p>
        <h2>{{ editingStudentId ? 'Update student' : 'Add student' }}</h2>

        <div class="form-row">
          <div>
            <label for="student-admission">Admission no</label>
            <input id="student-admission" v-model="studentForm.admission_no" required placeholder="ADM-2026-0001" />
          </div>
          <div>
            <label for="student-guardian">Guardian</label>
            <select id="student-guardian" v-model="studentForm.guardian_id">
              <option value="">No guardian</option>
              <option v-for="guardian in activeGuardians" :key="guardian.id" :value="guardian.id">
                {{ guardian.full_name }}
              </option>
            </select>
          </div>
        </div>

        <label for="student-name">Name</label>
        <input id="student-name" v-model="studentForm.full_name" required placeholder="Nadia Rahman" />

        <div class="form-row">
          <div>
            <label for="student-father">Father</label>
            <input id="student-father" v-model="studentForm.father_name" />
          </div>
          <div>
            <label for="student-mother">Mother</label>
            <input id="student-mother" v-model="studentForm.mother_name" />
          </div>
        </div>

        <div class="form-row">
          <div>
            <label for="student-admitted">Admitted on</label>
            <input id="student-admitted" v-model="studentForm.admitted_on" required type="date" />
          </div>
          <div>
            <label for="student-dob">Date of birth</label>
            <input id="student-dob" v-model="studentForm.date_of_birth" type="date" />
          </div>
        </div>

        <div class="form-row">
          <div>
            <label for="student-gender">Gender</label>
            <input id="student-gender" v-model="studentForm.gender" />
          </div>
          <div>
            <label for="student-phone">Phone</label>
            <input id="student-phone" v-model="studentForm.phone" />
          </div>
        </div>

        <label for="student-email">Email</label>
        <input id="student-email" v-model="studentForm.email" type="email" />

        <label for="student-address">Address</label>
        <textarea id="student-address" v-model="studentForm.address" rows="3" />

        <label for="student-medical">Medical notes</label>
        <textarea id="student-medical" v-model="studentForm.medical_notes" rows="3" />

        <div class="form-actions">
          <button class="button" type="submit" :disabled="savingStudent">
            {{ savingStudent ? 'Saving' : editingStudentId ? 'Update student' : 'Save student' }}
          </button>
          <button v-if="editingStudentId" class="button secondary" type="button" @click="resetStudentForm">Cancel</button>
        </div>
      </form>
    </section>

    <section class="list-grid">
      <section class="surface record-list">
        <div class="list-heading">
          <div>
            <p class="muted">Family records</p>
            <h2>Guardians</h2>
          </div>
          <form class="filters" @submit.prevent="loadGuardians">
            <input v-model="guardianSearch" aria-label="Search guardians" placeholder="Search" />
            <button class="button secondary" type="submit">Search</button>
          </form>
        </div>

        <table>
          <thead>
            <tr>
              <th>Guardian</th>
              <th>Contact</th>
              <th>Students</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="guardian in guardians" :key="guardian.id">
              <td>
                <strong>{{ guardian.full_name }}</strong>
                <small>{{ guardian.relationship }}</small>
              </td>
              <td>{{ guardian.phone || guardian.email || 'No contact' }}</td>
              <td>{{ guardian.students_count || 0 }}</td>
              <td>
                <button class="text-button" type="button" @click="editGuardian(guardian)">Edit</button>
                <button class="text-button" type="button" @click="archiveGuardian(guardian)">Archive</button>
              </td>
            </tr>
            <tr v-if="guardians.length === 0">
              <td colspan="4">No guardians yet.</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="surface record-list">
        <div class="list-heading">
          <div>
            <p class="muted">Admissions</p>
            <h2>Students</h2>
          </div>
          <form class="filters" @submit.prevent="loadStudents">
            <input v-model="studentSearch" aria-label="Search students" placeholder="Search" />
            <button class="button secondary" type="submit">Search</button>
          </form>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>Guardian</th>
                <th>Admitted</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in students" :key="student.id">
                <td>
                  <strong>{{ student.full_name }}</strong>
                  <small>{{ student.admission_no }}</small>
                </td>
                <td>{{ student.guardian?.full_name || 'Unassigned' }}</td>
                <td>{{ toDateInput(student.admitted_on) }}</td>
                <td>{{ student.status }}</td>
                <td>
                  <button class="text-button" type="button" @click="editStudent(student)">Edit</button>
                  <button class="text-button" type="button" @click="archiveStudent(student)">Archive</button>
                </td>
              </tr>
              <tr v-if="students.length === 0">
                <td colspan="5">No students yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </main>
</template>

<style scoped>
.people-page {
  min-height: 100vh;
  padding: 30px;
  background: #f7f3ef;
}

.page-header,
.header-actions,
.summary-grid,
.form-grid,
.list-grid,
.form-row,
.form-actions,
.list-heading,
.filters {
  display: flex;
  gap: 14px;
}

.page-header,
.list-heading,
.form-actions {
  align-items: center;
  justify-content: space-between;
}

.page-header {
  margin-bottom: 20px;
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

p,
.muted,
small {
  color: #6a7771;
}

.summary-grid,
.form-grid,
.list-grid {
  display: grid;
}

.summary-grid {
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin-bottom: 18px;
}

.form-grid,
.list-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-top: 18px;
}

.surface {
  border: 1px solid #dfe6e2;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 18px 45px rgb(24 44 35 / 8%);
}

.summary-item,
.record-form,
.record-list {
  padding: 22px;
}

.summary-item strong {
  display: block;
  margin-top: 8px;
  color: #111827;
  font-size: 1.8rem;
}

.record-form {
  display: grid;
  gap: 12px;
}

.form-row > * {
  flex: 1;
}

label {
  display: block;
  margin-bottom: 8px;
  color: #26342f;
  font-weight: 800;
}

input,
select,
textarea {
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

.filters {
  flex-wrap: wrap;
  justify-content: flex-end;
}

.filters input {
  width: auto;
  min-width: 150px;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  min-width: 580px;
  border-collapse: collapse;
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
  margin-right: 10px;
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
  .people-page {
    padding: 18px;
  }

  .page-header,
  .header-actions,
  .form-row,
  .list-heading {
    align-items: stretch;
    flex-direction: column;
  }

  .summary-grid,
  .form-grid,
  .list-grid {
    grid-template-columns: 1fr;
  }

  .filters,
  .filters input {
    width: 100%;
  }
}
</style>
