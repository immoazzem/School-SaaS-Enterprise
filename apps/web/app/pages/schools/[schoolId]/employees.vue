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
      success.value = 'Employee updated.'
    } else {
      await api.request<EmployeeResponse>(`/schools/${schoolId.value}/employees`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Employee saved.'
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
</script>

<template>
  <main class="catalog-page">
    <header class="catalog-header">
      <div>
        <NuxtLink class="back-link" to="/dashboard">Dashboard</NuxtLink>
        <h1>Employees</h1>
        <p>Keep staff profiles, teacher records, contact details, salary baselines, and designation links ready.</p>
      </div>

      <div class="header-actions">
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/designations`">Designations</NuxtLink>
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/shifts`">Shifts</NuxtLink>
      </div>
    </header>

    <section class="summary-grid">
      <article class="surface summary-item">
        <span>Visible staff</span>
        <strong>{{ employees.length }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Active staff</span>
        <strong>{{ activeCount }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Teachers</span>
        <strong>{{ teacherCount }}</strong>
      </article>
    </section>

    <section class="catalog-grid">
      <form class="surface catalog-form" @submit.prevent="saveEmployee">
        <div>
          <p class="muted">{{ editingEmployeeId ? 'Edit employee' : 'New employee' }}</p>
          <h2>{{ editingEmployeeId ? 'Update employee' : 'Add employee' }}</h2>
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
          <label for="employee-name">Full name</label>
          <input id="employee-name" v-model="form.full_name" required placeholder="Amina Rahman" />
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

      <section class="surface catalog-list">
        <div class="list-heading">
          <div>
            <p class="muted">People records</p>
            <h2>Employee roster</h2>
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
              <option v-for="designation in activeDesignations" :key="designation.id" :value="designation.id">
                {{ designation.name }}
              </option>
            </select>
            <button class="button secondary" type="submit">Search</button>
          </form>
        </div>

        <p v-if="loading" class="muted">Loading employees</p>

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
                  <strong>{{ employee.full_name }}</strong>
                  <small>{{ employee.employee_no }} · {{ employee.phone || employee.email || 'No contact' }}</small>
                </td>
                <td>{{ employee.designation?.name || 'Unassigned' }}</td>
                <td>{{ employee.employee_type }}</td>
                <td>{{ toDateInput(employee.joined_on) }}</td>
                <td>{{ employee.status }}</td>
                <td>
                  <div class="row-actions">
                    <button class="text-button" type="button" @click="editEmployee(employee)">Edit</button>
                    <button
                      class="text-button"
                      type="button"
                      :disabled="employee.status === 'archived' || archivingEmployeeId === employee.id"
                      @click="archiveEmployee(employee)"
                    >
                      Archive
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="employees.length === 0">
                <td colspan="6">No employees yet.</td>
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
  background: #f6f8f7;
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
  color: #0f5f4a;
  font-weight: 800;
}

h1 {
  margin: 12px 0 0;
  color: #16201c;
  font-size: clamp(2.1rem, 5.8vw, 4.4rem);
  line-height: 0.95;
}

h2 {
  margin: 0;
  color: #16201c;
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
  color: #16201c;
  font-size: 1.8rem;
}

.catalog-grid {
  display: grid;
  grid-template-columns: minmax(320px, 460px) minmax(0, 1fr);
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
select,
textarea {
  width: 100%;
  border: 1px solid #ccd8d2;
  border-radius: 8px;
  padding: 12px 13px;
  color: #16201c;
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
  min-width: 860px;
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
  color: #0f5f4a;
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
