<script setup lang="ts">
import type { Employee, TeacherProfile } from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface ProfileResponse {
  data: TeacherProfile
}

const api = useApi()
const route = useRoute()

const employees = ref<Employee[]>([])
const profiles = ref<TeacherProfile[]>([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const search = ref('')
const status = ref('active')
const editingId = ref<number | null>(null)

const form = reactive({
  employee_id: '',
  teacher_no: '',
  specialization: '',
  qualification: '',
  experience_years: '',
  joined_teaching_on: '',
  bio: '',
  status: 'active',
})

const schoolId = computed(() => Number(route.params.schoolId))

function toDateInput(value: string | null) {
  return value ? value.slice(0, 10) : ''
}

async function loadProfiles() {
  const query = new URLSearchParams()

  if (status.value) {
    query.set('status', status.value)
  }

  if (search.value.trim()) {
    query.set('search', search.value.trim())
  }

  const suffix = query.toString() ? `?${query.toString()}` : ''
  const response = await api.request<ListResponse<TeacherProfile>>(
    `/schools/${schoolId.value}/teacher-profiles${suffix}`,
  )
  profiles.value = response.data
}

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const employeeList = await api.request<ListResponse<Employee>>(`/schools/${schoolId.value}/employees?status=active`)
    employees.value = employeeList.data
    await loadProfiles()
  } catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load teacher profiles.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingId.value = null
  form.employee_id = ''
  form.teacher_no = ''
  form.specialization = ''
  form.qualification = ''
  form.experience_years = ''
  form.joined_teaching_on = ''
  form.bio = ''
  form.status = 'active'
}

function editProfile(profile: TeacherProfile) {
  editingId.value = profile.id
  form.employee_id = String(profile.employee_id)
  form.teacher_no = profile.teacher_no
  form.specialization = profile.specialization || ''
  form.qualification = profile.qualification || ''
  form.experience_years = profile.experience_years === null ? '' : String(profile.experience_years)
  form.joined_teaching_on = toDateInput(profile.joined_teaching_on)
  form.bio = profile.bio || ''
  form.status = profile.status
}

async function saveProfile() {
  saving.value = true
  error.value = ''
  success.value = ''

  const payload = {
    employee_id: Number(form.employee_id),
    teacher_no: form.teacher_no,
    specialization: form.specialization || null,
    qualification: form.qualification || null,
    experience_years: form.experience_years ? Number(form.experience_years) : null,
    joined_teaching_on: form.joined_teaching_on || null,
    bio: form.bio || null,
    status: form.status,
  }

  try {
    if (editingId.value) {
      await api.request<ProfileResponse>(`/schools/${schoolId.value}/teacher-profiles/${editingId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      success.value = 'Teacher profile updated.'
    } else {
      await api.request<ProfileResponse>(`/schools/${schoolId.value}/teacher-profiles`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Teacher profile saved.'
    }

    resetForm()
    await loadProfiles()
  } catch (profileError) {
    error.value = profileError instanceof Error ? profileError.message : 'Unable to save teacher profile.'
  } finally {
    saving.value = false
  }
}

async function archiveProfile(profile: TeacherProfile) {
  await api.request<ProfileResponse>(`/schools/${schoolId.value}/teacher-profiles/${profile.id}`, {
    method: 'PATCH',
    body: { status: 'archived' },
  })
  success.value = 'Teacher profile archived.'
  await loadProfiles()
}

onMounted(loadWorkspace)
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Teacher navigation"
      context-title="Teacher tools"
      :context-links="[
        { label: 'Employees', to: `/schools/${schoolId}/employees` },
        { label: 'Class Subjects', to: `/schools/${schoolId}/class-subjects` },
        { label: 'Enrollments', to: `/schools/${schoolId}/enrollments` },
      ]"
    />
    </template>

    <header class="workspace-header">
        <div>
          <p class="eyebrow">People records</p>
          <h1>Teachers</h1>
          <p class="muted">Attach teaching profile details to employee records.</p>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <div v-if="error" class="alert error">{{ error }}</div>
      <div v-if="success" class="alert success">{{ success }}</div>

      <section class="grid two-columns">
        <form class="panel" @submit.prevent="saveProfile">
          <p class="muted">{{ editingId ? 'Editing teacher' : 'New teacher' }}</p>
          <h2>{{ editingId ? 'Update teacher' : 'Add teacher' }}</h2>

          <label for="employee">Employee</label>
          <select id="employee" v-model="form.employee_id" required>
            <option value="">Select employee</option>
            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
              {{ employee.full_name }} / {{ employee.employee_no }}
            </option>
          </select>

          <div class="form-row">
            <div>
              <label for="teacher-no">Teacher no</label>
              <input id="teacher-no" v-model="form.teacher_no" required placeholder="TCHR-2026-0001">
            </div>
            <div>
              <label for="experience">Experience</label>
              <input id="experience" v-model="form.experience_years" min="0" type="number" placeholder="8">
            </div>
          </div>

          <label for="specialization">Specialization</label>
          <input id="specialization" v-model="form.specialization" placeholder="Mathematics">

          <label for="qualification">Qualification</label>
          <input id="qualification" v-model="form.qualification" placeholder="M.Ed">

          <div class="form-row">
            <div>
              <label for="joined-teaching-on">Teaching start</label>
              <input id="joined-teaching-on" v-model="form.joined_teaching_on" type="date">
            </div>
            <div>
              <label for="status">Status</label>
              <select id="status" v-model="form.status">
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>

          <label for="bio">Bio</label>
          <textarea id="bio" v-model="form.bio" rows="3" />

          <div class="form-actions">
            <button class="button" type="submit" :disabled="saving">{{ saving ? 'Saving...' : 'Save teacher' }}</button>
            <button v-if="editingId" class="button secondary" type="button" @click="resetForm">Cancel</button>
          </div>
        </form>

        <section class="panel">
          <div class="table-header">
            <div>
              <p class="muted">Teacher list</p>
              <h2>{{ profiles.length }} profiles</h2>
            </div>
            <form class="search-form" @submit.prevent="loadProfiles">
              <select v-model="status">
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
              <input v-model="search" placeholder="Search teacher">
              <button class="button secondary" type="submit">Search</button>
            </form>
          </div>

          <div v-if="loading" class="muted">Loading teachers...</div>
          <table v-else>
            <thead>
              <tr>
                <th>Teacher</th>
                <th>Specialization</th>
                <th>Experience</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="profile in profiles" :key="profile.id">
                <td>
                  <strong>{{ profile.employee?.full_name }}</strong>
                  <span>{{ profile.teacher_no }}</span>
                </td>
                <td>{{ profile.specialization || '-' }}</td>
                <td>{{ profile.experience_years ?? '-' }}</td>
                <td><span class="status-pill">{{ profile.status }}</span></td>
                <td class="table-actions">
                  <button class="link-button" type="button" @click="editProfile(profile)">Edit</button>
                  <button class="link-button danger" type="button" @click="archiveProfile(profile)">Archive</button>
                </td>
              </tr>
              <tr v-if="!profiles.length">
                <td colspan="5">No teacher profiles yet.</td>
              </tr>
            </tbody>
          </table>
        </section>
      </section>
</SchoolWorkspaceTemplate>
</template>


