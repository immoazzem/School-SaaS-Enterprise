<script setup lang="ts">
import type { Designation, Employee } from '~/composables/useApi'

interface EmployeesResponse {
  data: Employee[]
}

interface EmployeeResponse {
  data: Employee
}

interface DesignationsResponse {
  data: Designation[]
}

const api = useApi()
const route = useRoute()
const auth = useAuth()
const { t, locale } = useI18n()
useSchoolLocale()

const employees = ref<Employee[]>([])
const designations = ref<Designation[]>([])
const loading = ref(false)
const saving = ref(false)
const archivingEmployeeId = ref<number | null>(null)
const error = ref('')
const success = ref('')
const editingEmployeeId = ref<number | null>(null)
const statusFilter = ref('active')
const typeFilter = ref('')
const designationFilter = ref('')
const search = ref('')
const form = reactive({
  designation_id: '',
  employee_no: '',
  full_name: '',
  name_bn: '',
  father_name: '',
  mother_name: '',
  email: '',
  phone: '',
  gender: '',
  religion: '',
  date_of_birth: '',
  joined_on: '',
  salary: 0,
  employee_type: 'staff',
  address: '',
  notes: '',
  status: 'active',
})

const schoolId = computed(() => Number(route.params.schoolId))
const activeCount = computed(() => employees.value.filter((employee) => employee.status === 'active').length)
const teacherCount = computed(() => employees.value.filter((employee) => employee.employee_type === 'teacher').length)
const activeDesignations = computed(() => designations.value.filter((designation) => designation.status === 'active'))

function toDateInput(value: string | null) {
  return value ? value.slice(0, 10) : ''
}

async function loadDesignations() {
  const response = await api.request<DesignationsResponse>(`/schools/${schoolId.value}/designations?status=active`)
  designations.value = response.data
}

async function loadEmployees() {
  loading.value = true
  error.value = ''

  try {
    const query = new URLSearchParams()

    if (statusFilter.value) {
      query.set('status', statusFilter.value)
    }

    if (typeFilter.value) {
      query.set('employee_type', typeFilter.value)
    }

    if (designationFilter.value) {
      query.set('designation_id', designationFilter.value)
    }

    if (search.value.trim()) {
      query.set('search', search.value.trim())
    }

    const suffix = query.toString() ? `?${query.toString()}` : ''
    const response = await api.request<EmployeesResponse>(`/schools/${schoolId.value}/employees${suffix}`)
    employees.value = response.data
  } catch (employeeError) {
    error.value = employeeError instanceof Error ? employeeError.message : 'Unable to load employees.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingEmployeeId.value = null
  form.designation_id = ''
  form.employee_no = ''
  form.full_name = ''
  form.name_bn = ''
  form.father_name = ''
  form.mother_name = ''
  form.email = ''
  form.phone = ''
  form.gender = ''
  form.religion = ''
  form.date_of_birth = ''
  form.joined_on = ''
  form.salary = 0
  form.employee_type = 'staff'
  form.address = ''
  form.notes = ''
  form.status = 'active'
}

function editEmployee(employee: Employee) {
  editingEmployeeId.value = employee.id
  form.designation_id = employee.designation_id ? String(employee.designation_id) : ''
  form.employee_no = employee.employee_no
  form.full_name = employee.full_name
  form.name_bn = employee.name_bn || ''
  form.father_name = employee.father_name || ''
  form.mother_name = employee.mother_name || ''
  form.email = employee.email || ''
  form.phone = employee.phone || ''
  form.gender = employee.gender || ''
  form.religion = employee.religion || ''
  form.date_of_birth = toDateInput(employee.date_of_birth)
  form.joined_on = toDateInput(employee.joined_on)
  form.salary = Number(employee.salary)
  form.employee_type = employee.employee_type
  form.address = employee.address || ''
  form.notes = employee.notes || ''
  form.status = employee.status
}

async function saveEmployee() {
  saving.value = true
  error.value = ''
  success.value = ''

  const payload = {
    designation_id: form.designation_id ? Number(form.designation_id) : null,
    employee_no: form.employee_no,
    full_name: form.full_name,
    name_bn: form.name_bn || null,
    father_name: form.father_name || null,
    mother_name: form.mother_name || null,
    email: form.email || null,
    phone: form.phone || null,
    gender: form.gender || null,
    religion: form.religion || null,
    date_of_birth: form.date_of_birth || null,
    joined_on: form.joined_on,
    salary: Number(form.salary),
    employee_type: form.employee_type,
    address: form.address || null,
    notes: form.notes || null,
    status: form.status,
  }

  try {
    if (editingEmployeeId.value) {
      await api.request<EmployeeResponse>(`/schools/${schoolId.value}/employees/${editingEmployeeId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      success.value = t('employees.updated')
    } else {
      await api.request<EmployeeResponse>(`/schools/${schoolId.value}/employees`, {
        method: 'POST',
        body: payload,
      })
      success.value = t('employees.saved')
    }

    resetForm()
    await loadEmployees()
  } catch (employeeError) {
    error.value = employeeError instanceof Error ? employeeError.message : 'Unable to save employee.'
  } finally {
    saving.value = false
  }
}

async function archiveEmployee(employee: Employee) {
  archivingEmployeeId.value = employee.id
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/employees/${employee.id}`, {
      method: 'PATCH',
      body: { status: 'archived' },
    })
    success.value = 'Employee archived.'
    await loadEmployees()
  } catch (employeeError) {
    error.value = employeeError instanceof Error ? employeeError.message : 'Unable to archive employee.'
  } finally {
    archivingEmployeeId.value = null
  }
}

async function chooseFilter(event: Event, target: 'status' | 'type' | 'designation') {
  const value = (event.target as HTMLSelectElement).value

  if (target === 'status') {
    statusFilter.value = value
  }

  if (target === 'type') {
    typeFilter.value = value
  }

  if (target === 'designation') {
    designationFilter.value = value
  }

  await loadEmployees()
}

async function searchEmployees() {
  await loadEmployees()
}

onMounted(async () => {
  await auth.refreshProfile()
  await loadDesignations()
  await loadEmployees()
})

watch(locale, async () => {
  await loadEmployees()
})
</script>

<template>
  <main class="operation-shell">
    <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Employees navigation"
      context-title="People setup"
      :context-links="[
        { label: 'Designations', to: `/schools/${schoolId}/designations` },
        { label: 'Shifts', to: `/schools/${schoolId}/shifts` },
        { label: 'Staff Operations', to: `/schools/${schoolId}/staff-operations` },
        { label: 'Teacher Profiles', to: `/schools/${schoolId}/teacher-profiles` },
      ]"
    />

    <section class="operation-workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">People</p>
          <h1>{{ $t('employees.title') }}</h1>
          <p>Keep staff profiles, teacher records, contact details, salary baselines, and designation links ready.</p>
        </div>
        <div class="header-actions">
          <LocaleSwitcher />
          <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
        </div>
      </header>

      <section class="summary-grid">
        <article class="surface summary-item">
          <span>Visible staff</span>
          <strong>{{ employees.length }}</strong>
        </article>
        <article class="surface summary-item">
          <span>{{ $t('common.active') }} staff</span>
          <strong>{{ activeCount }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Teachers</span>
          <strong>{{ teacherCount }}</strong>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="saveEmployee">
          <div>
            <p class="muted">{{ editingEmployeeId ? 'Edit employee' : 'New employee' }}</p>
            <h2>{{ editingEmployeeId ? 'Update employee' : $t('employees.add') }}</h2>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="employee-no">Employee no</label>
              <input id="employee-no" v-model="form.employee_no" required placeholder="EMP-2026-0001" />
            </div>
            <div class="field">
              <label for="employee-type">Type</label>
              <select id="employee-type" v-model="form.employee_type">
                <option value="teacher">Teacher</option>
                <option value="administrative">Administrative</option>
                <option value="support">Support</option>
                <option value="staff">Staff</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <div class="field">
            <label for="employee-name">{{ $t('employees.fullName') }}</label>
            <input id="employee-name" v-model="form.full_name" required placeholder="Amina Rahman" />
          </div>

          <div class="field">
            <label for="employee-name-bn">{{ $t('employees.bengaliName') }}</label>
            <input id="employee-name-bn" v-model="form.name_bn" placeholder="আমিনা রহমান" />
          </div>

          <div class="form-row">
            <div class="field">
              <label for="employee-father">Father name</label>
              <input id="employee-father" v-model="form.father_name" />
            </div>
            <div class="field">
              <label for="employee-mother">Mother name</label>
              <input id="employee-mother" v-model="form.mother_name" />
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="employee-email">Email</label>
              <input id="employee-email" v-model="form.email" type="email" placeholder="amina@example.com" />
            </div>
            <div class="field">
              <label for="employee-phone">Phone</label>
              <input id="employee-phone" v-model="form.phone" placeholder="+8801700000001" />
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="employee-designation">Designation</label>
              <select id="employee-designation" v-model="form.designation_id">
                <option value="">No designation</option>
                <option v-for="designation in activeDesignations" :key="designation.id" :value="designation.id">
                  {{ designation.name }}
                </option>
              </select>
            </div>
            <div class="field">
              <label for="employee-salary">Salary</label>
              <input id="employee-salary" v-model="form.salary" min="0" step="0.01" type="number" />
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="employee-joined">Joined on</label>
              <input id="employee-joined" v-model="form.joined_on" required type="date" />
            </div>
            <div class="field">
              <label for="employee-dob">Date of birth</label>
              <input id="employee-dob" v-model="form.date_of_birth" type="date" />
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="employee-gender">Gender</label>
              <input id="employee-gender" v-model="form.gender" />
            </div>
            <div class="field">
              <label for="employee-religion">Religion</label>
              <input id="employee-religion" v-model="form.religion" />
            </div>
          </div>

          <div class="field">
            <label for="employee-address">Address</label>
            <textarea id="employee-address" v-model="form.address" rows="3" />
          </div>

          <div class="field">
            <label for="employee-notes">Notes</label>
            <textarea id="employee-notes" v-model="form.notes" rows="3" />
          </div>

          <div class="field">
            <label for="employee-status">Status</label>
            <select id="employee-status" v-model="form.status">
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
          </div>

          <p v-if="error" class="error">{{ error }}</p>
          <p v-if="success" class="success">{{ success }}</p>

          <div class="form-actions">
            <button class="button" type="submit" :disabled="saving">
              {{ saving ? 'Saving' : editingEmployeeId ? 'Update employee' : 'Save employee' }}
            </button>
            <button v-if="editingEmployeeId" class="button secondary" type="button" @click="resetForm">Cancel</button>
          </div>
        </form>

        <section class="surface record-list">
          <div class="list-header">
            <div>
              <p class="muted">People records</p>
              <h2>{{ $t('employees.roster') }}</h2>
            </div>
            <form class="filters" @submit.prevent="searchEmployees">
              <input v-model="search" aria-label="Search employees" placeholder="Search" />
              <select :value="statusFilter" aria-label="Status filter" @change="chooseFilter($event, 'status')">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
              <select :value="typeFilter" aria-label="Type filter" @change="chooseFilter($event, 'type')">
                <option value="">All types</option>
                <option value="teacher">Teacher</option>
                <option value="administrative">Administrative</option>
                <option value="support">Support</option>
                <option value="staff">Staff</option>
                <option value="other">Other</option>
              </select>
              <select :value="designationFilter" aria-label="Designation filter" @change="chooseFilter($event, 'designation')">
                <option value="">All designations</option>
                <option v-for="designation in activeDesignations" :key="designation.id" :value="designation.id">{{ designation.name }}</option>
              </select>
              <button class="button secondary" type="submit">{{ $t('actions.search') }}</button>
            </form>
          </div>

          <p v-if="loading" class="muted">{{ $t('common.loading') }}</p>

          <div v-else class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Designation</th>
                  <th>Type</th>
                  <th>Joined</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="employee in employees" :key="employee.id">
                  <td>
                    <strong>{{ employee.display_name }}</strong>
                    <small v-if="employee.display_name !== employee.full_name">{{ employee.full_name }}</small>
                    <small>{{ employee.employee_no }} · {{ employee.phone || employee.email || 'No contact' }}</small>
                  </td>
                  <td>{{ employee.designation?.name || 'Unassigned' }}</td>
                  <td>{{ employee.employee_type }}</td>
                  <td>{{ toDateInput(employee.joined_on) }}</td>
                  <td>{{ employee.status }}</td>
                  <td>
                    <div class="row-actions">
                      <button class="text-button" type="button" @click="editEmployee(employee)">{{ $t('actions.edit') }}</button>
                      <button class="text-button" type="button" :disabled="employee.status === 'archived' || archivingEmployeeId === employee.id" @click="archiveEmployee(employee)">{{ $t('actions.archive') }}</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="employees.length === 0">
                  <td colspan="6">{{ $t('employees.empty') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>
    </section>
  </main>
</template>


