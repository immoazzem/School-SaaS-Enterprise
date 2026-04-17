<script setup lang="ts">
import type {
  AcademicClass,
  AcademicSection,
  AcademicYear,
  Shift,
  Student,
  StudentEnrollment,
  StudentGroup,
} from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface EnrollmentResponse {
  data: StudentEnrollment
}

const api = useApi()
const route = useRoute()
const auth = useAuth()

const enrollments = ref<StudentEnrollment[]>([])
const students = ref<Student[]>([])
const years = ref<AcademicYear[]>([])
const classes = ref<AcademicClass[]>([])
const sections = ref<AcademicSection[]>([])
const groups = ref<StudentGroup[]>([])
const shifts = ref<Shift[]>([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const search = ref('')
const status = ref('active')
const editingId = ref<number | null>(null)

const form = reactive({
  student_id: '',
  academic_year_id: '',
  academic_class_id: '',
  academic_section_id: '',
  student_group_id: '',
  shift_id: '',
  roll_no: '',
  enrolled_on: '',
  status: 'active',
  notes: '',
})

const schoolId = computed(() => Number(route.params.schoolId))
const selectedClassId = computed(() => Number(form.academic_class_id || 0))
const classSections = computed(() =>
  sections.value.filter((section) => section.academic_class_id === selectedClassId.value),
)

function toDateInput(value: string | null) {
  return value ? value.slice(0, 10) : ''
}

async function loadEnrollments() {
  const query = new URLSearchParams()

  if (status.value) {
    query.set('status', status.value)
  }

  if (search.value.trim()) {
    query.set('search', search.value.trim())
  }

  const suffix = query.toString() ? `?${query.toString()}` : ''
  const response = await api.request<ListResponse<StudentEnrollment>>(
    `/schools/${schoolId.value}/student-enrollments${suffix}`,
  )
  enrollments.value = response.data
}

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const [studentList, yearList, classList, sectionList, groupList, shiftList] = await Promise.all([
      api.request<ListResponse<Student>>(`/schools/${schoolId.value}/students?status=active`),
      api.request<ListResponse<AcademicYear>>(`/schools/${schoolId.value}/academic-years?status=active`),
      api.request<ListResponse<AcademicClass>>(`/schools/${schoolId.value}/academic-classes`),
      api.request<ListResponse<AcademicSection>>(`/schools/${schoolId.value}/academic-sections?status=active`),
      api.request<ListResponse<StudentGroup>>(`/schools/${schoolId.value}/student-groups?status=active`),
      api.request<ListResponse<Shift>>(`/schools/${schoolId.value}/shifts?status=active`),
    ])

    students.value = studentList.data
    years.value = yearList.data
    classes.value = classList.data.filter((item) => item.status === 'active')
    sections.value = sectionList.data
    groups.value = groupList.data
    shifts.value = shiftList.data

    if (!form.academic_year_id) {
      const currentYear = years.value.find((year) => year.is_current) || years.value[0]
      form.academic_year_id = currentYear ? String(currentYear.id) : ''
    }

    await loadEnrollments()
  } catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load enrollments.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingId.value = null
  form.student_id = ''
  form.academic_year_id = years.value.find((year) => year.is_current)?.id.toString() || ''
  form.academic_class_id = ''
  form.academic_section_id = ''
  form.student_group_id = ''
  form.shift_id = ''
  form.roll_no = ''
  form.enrolled_on = ''
  form.status = 'active'
  form.notes = ''
}

function editEnrollment(enrollment: StudentEnrollment) {
  editingId.value = enrollment.id
  form.student_id = String(enrollment.student_id)
  form.academic_year_id = String(enrollment.academic_year_id)
  form.academic_class_id = String(enrollment.academic_class_id)
  form.academic_section_id = enrollment.academic_section_id ? String(enrollment.academic_section_id) : ''
  form.student_group_id = enrollment.student_group_id ? String(enrollment.student_group_id) : ''
  form.shift_id = enrollment.shift_id ? String(enrollment.shift_id) : ''
  form.roll_no = enrollment.roll_no || ''
  form.enrolled_on = toDateInput(enrollment.enrolled_on)
  form.status = enrollment.status
  form.notes = enrollment.notes || ''
}

async function saveEnrollment() {
  saving.value = true
  error.value = ''
  success.value = ''

  const payload = {
    student_id: Number(form.student_id),
    academic_year_id: Number(form.academic_year_id),
    academic_class_id: Number(form.academic_class_id),
    academic_section_id: form.academic_section_id ? Number(form.academic_section_id) : null,
    student_group_id: form.student_group_id ? Number(form.student_group_id) : null,
    shift_id: form.shift_id ? Number(form.shift_id) : null,
    roll_no: form.roll_no || null,
    enrolled_on: form.enrolled_on,
    status: form.status,
    notes: form.notes || null,
  }

  try {
    if (editingId.value) {
      await api.request<EnrollmentResponse>(`/schools/${schoolId.value}/student-enrollments/${editingId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      success.value = 'Enrollment updated.'
    } else {
      await api.request<EnrollmentResponse>(`/schools/${schoolId.value}/student-enrollments`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Enrollment saved.'
    }

    resetForm()
    await loadEnrollments()
  } catch (enrollmentError) {
    error.value = enrollmentError instanceof Error ? enrollmentError.message : 'Unable to save enrollment.'
  } finally {
    saving.value = false
  }
}

async function archiveEnrollment(enrollment: StudentEnrollment) {
  await api.request<EnrollmentResponse>(`/schools/${schoolId.value}/student-enrollments/${enrollment.id}`, {
    method: 'PATCH',
    body: { status: 'archived' },
  })
  success.value = 'Enrollment archived.'
  await loadEnrollments()
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
      <nav aria-label="Enrollment navigation">
        <NuxtLink :to="`/schools/${schoolId}/students`">Students</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/academic-classes`">Classes</NuxtLink>
        <NuxtLink :to="`/schools/${schoolId}/academic-years`">Years</NuxtLink>
      </nav>
    </aside>

    <section class="workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">People records</p>
          <h1>Enrollments</h1>
          <p class="muted">Place students into the right year, class, section, group, and shift.</p>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <div v-if="error" class="alert error">{{ error }}</div>
      <div v-if="success" class="alert success">{{ success }}</div>

      <section class="grid two-columns">
        <form class="panel" @submit.prevent="saveEnrollment">
          <p class="muted">{{ editingId ? 'Editing enrollment' : 'New enrollment' }}</p>
          <h2>{{ editingId ? 'Update enrollment' : 'Add enrollment' }}</h2>

          <label for="student">Student</label>
          <select id="student" v-model="form.student_id" required>
            <option value="">Select student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.full_name }} / {{ student.admission_no }}
            </option>
          </select>

          <div class="form-row">
            <div>
              <label for="year">Academic year</label>
              <select id="year" v-model="form.academic_year_id" required>
                <option value="">Select year</option>
                <option v-for="year in years" :key="year.id" :value="year.id">
                  {{ year.name }}
                </option>
              </select>
            </div>
            <div>
              <label for="class">Class</label>
              <select id="class" v-model="form.academic_class_id" required>
                <option value="">Select class</option>
                <option v-for="item in classes" :key="item.id" :value="item.id">
                  {{ item.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div>
              <label for="section">Section</label>
              <select id="section" v-model="form.academic_section_id">
                <option value="">No section</option>
                <option v-for="section in classSections" :key="section.id" :value="section.id">
                  {{ section.name }}
                </option>
              </select>
            </div>
            <div>
              <label for="roll">Roll no</label>
              <input id="roll" v-model="form.roll_no" placeholder="12">
            </div>
          </div>

          <div class="form-row">
            <div>
              <label for="group">Group</label>
              <select id="group" v-model="form.student_group_id">
                <option value="">No group</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">
                  {{ group.name }}
                </option>
              </select>
            </div>
            <div>
              <label for="shift">Shift</label>
              <select id="shift" v-model="form.shift_id">
                <option value="">No shift</option>
                <option v-for="shift in shifts" :key="shift.id" :value="shift.id">
                  {{ shift.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div>
              <label for="enrolled-on">Enrolled on</label>
              <input id="enrolled-on" v-model="form.enrolled_on" type="date" required>
            </div>
            <div>
              <label for="status">Status</label>
              <select id="status" v-model="form.status">
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="transferred">Transferred</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>

          <label for="notes">Notes</label>
          <textarea id="notes" v-model="form.notes" rows="3" />

          <div class="form-actions">
            <button class="button" type="submit" :disabled="saving">{{ saving ? 'Saving...' : 'Save enrollment' }}</button>
            <button v-if="editingId" class="button secondary" type="button" @click="resetForm">Cancel</button>
          </div>
        </form>

        <section class="panel">
          <div class="table-header">
            <div>
              <p class="muted">Enrollment list</p>
              <h2>{{ enrollments.length }} records</h2>
            </div>
            <form class="search-form" @submit.prevent="loadEnrollments">
              <select v-model="status">
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="transferred">Transferred</option>
                <option value="archived">Archived</option>
              </select>
              <input v-model="search" placeholder="Search student or roll">
              <button class="button secondary" type="submit">Search</button>
            </form>
          </div>

          <div v-if="loading" class="muted">Loading enrollments...</div>
          <table v-else>
            <thead>
              <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Roll</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="enrollment in enrollments" :key="enrollment.id">
                <td>
                  <strong>{{ enrollment.student?.full_name }}</strong>
                  <span>{{ enrollment.student?.admission_no }}</span>
                </td>
                <td>
                  <strong>{{ enrollment.academic_class?.name }}</strong>
                  <span>{{ enrollment.academic_section?.name || 'No section' }}</span>
                </td>
                <td>{{ enrollment.roll_no || '-' }}</td>
                <td><span class="status-pill">{{ enrollment.status }}</span></td>
                <td class="table-actions">
                  <button class="link-button" type="button" @click="editEnrollment(enrollment)">Edit</button>
                  <button class="link-button danger" type="button" @click="archiveEnrollment(enrollment)">Archive</button>
                </td>
              </tr>
              <tr v-if="!enrollments.length">
                <td colspan="5">No enrollments yet.</td>
              </tr>
            </tbody>
          </table>
        </section>
      </section>
    </section>
  </main>
</template>
