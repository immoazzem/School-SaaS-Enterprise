<script setup lang="ts">
import type { AcademicYear, Employee, EmployeeAttendanceRecord, LeaveApplication, LeaveBalance, LeaveType, SalaryRecord } from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface ItemResponse<T> {
  data: T
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const employees = ref<Employee[]>([])
const academicYears = ref<AcademicYear[]>([])
const salaryRecords = ref<SalaryRecord[]>([])
const attendanceRecords = ref<EmployeeAttendanceRecord[]>([])
const leaveTypes = ref<LeaveType[]>([])
const leaveBalances = ref<LeaveBalance[]>([])
const leaveApplications = ref<LeaveApplication[]>([])
const loading = ref(false)
const savingSalary = ref(false)
const savingAttendance = ref(false)
const savingLeaveType = ref(false)
const savingLeaveBalance = ref(false)
const savingLeaveApplication = ref(false)
const reviewingId = ref<number | null>(null)
const error = ref('')
const success = ref('')

const salaryForm = reactive({
  employee_id: '',
  academic_year_id: '',
  month: '',
  basic_amount: '',
  hra: '',
  medical: '',
  transport: '',
  provident_fund: '',
  income_tax: '',
})

const attendanceForm = reactive({
  employee_id: '',
  date: '',
  status: 'present',
  check_in_time: '',
  check_out_time: '',
})

const leaveTypeForm = reactive({
  name: '',
  code: '',
  max_days_per_year: 10,
})

const leaveBalanceForm = reactive({
  employee_id: '',
  leave_type_id: '',
  academic_year_id: '',
  total_days: 10,
})

const leaveApplicationForm = reactive({
  employee_id: '',
  leave_type_id: '',
  from_date: '',
  to_date: '',
  reason: '',
})

const pendingLeaveCount = computed(() => leaveApplications.value.filter((leave) => leave.status === 'pending').length)
const payrollTotal = computed(() => salaryRecords.value.reduce((sum, salary) => sum + Number(salary.net_amount), 0))
const onLeaveCount = computed(() => attendanceRecords.value.filter((record) => record.status === 'on_leave').length)

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const [employeeResponse, yearResponse, salaryResponse, attendanceResponse, typeResponse, balanceResponse, applicationResponse] = await Promise.all([
      api.request<ListResponse<Employee>>(`/schools/${schoolId.value}/employees?status=active&per_page=100`),
      api.request<ListResponse<AcademicYear>>(`/schools/${schoolId.value}/academic-years?status=active&per_page=100`),
      api.request<ListResponse<SalaryRecord>>(`/schools/${schoolId.value}/salary-records?per_page=100`),
      api.request<ListResponse<EmployeeAttendanceRecord>>(`/schools/${schoolId.value}/employee-attendance-records?per_page=100`),
      api.request<ListResponse<LeaveType>>(`/schools/${schoolId.value}/leave-types?per_page=100`),
      api.request<ListResponse<LeaveBalance>>(`/schools/${schoolId.value}/leave-balances?per_page=100`),
      api.request<ListResponse<LeaveApplication>>(`/schools/${schoolId.value}/leave-applications?per_page=100`),
    ])
    employees.value = employeeResponse.data
    academicYears.value = yearResponse.data
    salaryRecords.value = salaryResponse.data
    attendanceRecords.value = attendanceResponse.data
    leaveTypes.value = typeResponse.data
    leaveBalances.value = balanceResponse.data
    leaveApplications.value = applicationResponse.data
  } catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load staff operations.'
  } finally {
    loading.value = false
  }
}

async function saveSalary() {
  savingSalary.value = true
  error.value = ''
  success.value = ''
  try {
    await api.request<ItemResponse<SalaryRecord>>(`/schools/${schoolId.value}/salary-records`, {
      method: 'POST',
      body: {
        employee_id: Number(salaryForm.employee_id),
        academic_year_id: Number(salaryForm.academic_year_id),
        month: salaryForm.month,
        basic_amount: Number(salaryForm.basic_amount),
        allowances: {
          hra: Number(salaryForm.hra || 0),
          medical: Number(salaryForm.medical || 0),
          transport: Number(salaryForm.transport || 0),
        },
        deductions: {
          provident_fund: Number(salaryForm.provident_fund || 0),
          income_tax: Number(salaryForm.income_tax || 0),
        },
      },
    })
    success.value = 'Salary record saved.'
    salaryForm.basic_amount = ''
    await loadWorkspace()
  } catch (salaryError) {
    error.value = salaryError instanceof Error ? salaryError.message : 'Unable to save salary record.'
  } finally {
    savingSalary.value = false
  }
}

async function saveAttendance() {
  savingAttendance.value = true
  error.value = ''
  success.value = ''
  try {
    await api.request<ItemResponse<EmployeeAttendanceRecord>>(`/schools/${schoolId.value}/employee-attendance-records`, {
      method: 'POST',
      body: {
        employee_id: Number(attendanceForm.employee_id),
        date: attendanceForm.date,
        status: attendanceForm.status,
        check_in_time: attendanceForm.check_in_time || null,
        check_out_time: attendanceForm.check_out_time || null,
      },
    })
    success.value = 'Employee attendance saved.'
    await loadWorkspace()
  } catch (attendanceError) {
    error.value = attendanceError instanceof Error ? attendanceError.message : 'Unable to save employee attendance.'
  } finally {
    savingAttendance.value = false
  }
}

async function saveLeaveType() {
  savingLeaveType.value = true
  error.value = ''
  success.value = ''
  try {
    await api.request<ItemResponse<LeaveType>>(`/schools/${schoolId.value}/leave-types`, {
      method: 'POST',
      body: { ...leaveTypeForm, max_days_per_year: Number(leaveTypeForm.max_days_per_year) },
    })
    success.value = 'Leave type saved.'
    leaveTypeForm.name = ''
    leaveTypeForm.code = ''
    await loadWorkspace()
  } catch (leaveTypeError) {
    error.value = leaveTypeError instanceof Error ? leaveTypeError.message : 'Unable to save leave type.'
  } finally {
    savingLeaveType.value = false
  }
}

async function saveLeaveBalance() {
  savingLeaveBalance.value = true
  error.value = ''
  success.value = ''
  try {
    await api.request<ItemResponse<LeaveBalance>>(`/schools/${schoolId.value}/leave-balances`, {
      method: 'POST',
      body: {
        employee_id: Number(leaveBalanceForm.employee_id),
        leave_type_id: Number(leaveBalanceForm.leave_type_id),
        academic_year_id: Number(leaveBalanceForm.academic_year_id),
        total_days: Number(leaveBalanceForm.total_days),
      },
    })
    success.value = 'Leave balance saved.'
    await loadWorkspace()
  } catch (balanceError) {
    error.value = balanceError instanceof Error ? balanceError.message : 'Unable to save leave balance.'
  } finally {
    savingLeaveBalance.value = false
  }
}

async function applyLeave() {
  savingLeaveApplication.value = true
  error.value = ''
  success.value = ''
  try {
    await api.request<ItemResponse<LeaveApplication>>(`/schools/${schoolId.value}/leave-applications`, {
      method: 'POST',
      body: {
        employee_id: Number(leaveApplicationForm.employee_id),
        leave_type_id: Number(leaveApplicationForm.leave_type_id),
        from_date: leaveApplicationForm.from_date,
        to_date: leaveApplicationForm.to_date,
        reason: leaveApplicationForm.reason,
      },
    })
    success.value = 'Leave application submitted.'
    leaveApplicationForm.reason = ''
    await loadWorkspace()
  } catch (leaveError) {
    error.value = leaveError instanceof Error ? leaveError.message : 'Unable to submit leave application.'
  } finally {
    savingLeaveApplication.value = false
  }
}

async function reviewLeave(application: LeaveApplication, action: 'approve' | 'reject' | 'cancel') {
  reviewingId.value = application.id
  error.value = ''
  success.value = ''
  try {
    await api.request<ItemResponse<LeaveApplication>>(`/schools/${schoolId.value}/leave-applications/${application.id}/${action}`, {
      method: 'PATCH',
      body: {},
    })
    success.value = `Leave ${action}d.`
    await loadWorkspace()
  } catch (reviewError) {
    error.value = reviewError instanceof Error ? reviewError.message : 'Unable to update leave application.'
  } finally {
    reviewingId.value = null
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Staff operations navigation"
      context-title="Staff tools"
      :context-links="[
        { label: 'Finance', to: `/schools/${schoolId}/finance` },
        { label: 'Employees', to: `/schools/${schoolId}/employees` },
        { label: 'Student Attendance', to: `/schools/${schoolId}/attendance` },
      ]"
    />
    </template>

    <header class="workspace-header">
        <div>
          <p class="eyebrow">Staff operations</p>
          <h1>Run payroll, record attendance, and approve leave.</h1>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading" class="muted">Loading staff operations</p>

      <section class="summary-grid">
        <article class="surface summary-item">
          <span>Payroll total</span>
          <strong>{{ payrollTotal.toFixed(0) }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Pending leave</span>
          <strong>{{ pendingLeaveCount }}</strong>
        </article>
        <article class="surface summary-item">
          <span>On leave</span>
          <strong>{{ onLeaveCount }}</strong>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="saveSalary">
          <div>
            <p class="eyebrow">Payroll</p>
            <h2>Create salary record</h2>
          </div>
          <div class="form-row">
            <div class="field">
              <label>Employee</label>
              <select v-model="salaryForm.employee_id" required>
                <option value="">Select employee</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.full_name }}</option>
              </select>
            </div>
            <div class="field">
              <label>Academic year</label>
              <select v-model="salaryForm.academic_year_id" required>
                <option value="">Select year</option>
                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label>Month</label>
              <input v-model="salaryForm.month" required type="month" />
            </div>
            <div class="field">
              <label>Basic amount</label>
              <input v-model="salaryForm.basic_amount" required min="0" type="number" />
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label>HRA</label>
              <input v-model="salaryForm.hra" min="0" type="number" />
            </div>
            <div class="field">
              <label>Medical</label>
              <input v-model="salaryForm.medical" min="0" type="number" />
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label>Provident fund</label>
              <input v-model="salaryForm.provident_fund" min="0" type="number" />
            </div>
            <div class="field">
              <label>Income tax</label>
              <input v-model="salaryForm.income_tax" min="0" type="number" />
            </div>
          </div>
          <button class="button" type="submit" :disabled="savingSalary">{{ savingSalary ? 'Saving salary' : 'Save salary' }}</button>
        </form>

        <form class="surface record-form" @submit.prevent="saveAttendance">
          <div>
            <p class="eyebrow">Employee attendance</p>
            <h2>Record day status</h2>
          </div>
          <div class="field">
            <label>Employee</label>
            <select v-model="attendanceForm.employee_id" required>
              <option value="">Select employee</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.full_name }}</option>
            </select>
          </div>
          <div class="form-row">
            <div class="field">
              <label>Date</label>
              <input v-model="attendanceForm.date" required type="date" />
            </div>
            <div class="field">
              <label>Status</label>
              <select v-model="attendanceForm.status">
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
                <option value="half_day">Half day</option>
                <option value="on_leave">On leave</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label>Check in</label>
              <input v-model="attendanceForm.check_in_time" type="time" />
            </div>
            <div class="field">
              <label>Check out</label>
              <input v-model="attendanceForm.check_out_time" type="time" />
            </div>
          </div>
          <button class="button" type="submit" :disabled="savingAttendance">{{ savingAttendance ? 'Saving attendance' : 'Save attendance' }}</button>
        </form>
      </section>

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="saveLeaveType">
          <div>
            <p class="eyebrow">Leave setup</p>
            <h2>Create leave type</h2>
          </div>
          <div class="form-row">
            <div class="field">
              <label>Name</label>
              <input v-model="leaveTypeForm.name" required type="text" placeholder="Casual Leave" />
            </div>
            <div class="field">
              <label>Code</label>
              <input v-model="leaveTypeForm.code" required type="text" placeholder="CL" />
            </div>
          </div>
          <div class="field">
            <label>Max days</label>
            <input v-model="leaveTypeForm.max_days_per_year" min="0" max="255" type="number" />
          </div>
          <button class="button" type="submit" :disabled="savingLeaveType">{{ savingLeaveType ? 'Saving type' : 'Save leave type' }}</button>
        </form>

        <form class="surface record-form" @submit.prevent="saveLeaveBalance">
          <div>
            <p class="eyebrow">Leave balance</p>
            <h2>Assign annual balance</h2>
          </div>
          <div class="field">
            <label>Employee</label>
            <select v-model="leaveBalanceForm.employee_id" required>
              <option value="">Select employee</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.full_name }}</option>
            </select>
          </div>
          <div class="form-row">
            <div class="field">
              <label>Leave type</label>
              <select v-model="leaveBalanceForm.leave_type_id" required>
                <option value="">Select leave type</option>
                <option v-for="leaveType in leaveTypes" :key="leaveType.id" :value="leaveType.id">{{ leaveType.name }}</option>
              </select>
            </div>
            <div class="field">
              <label>Academic year</label>
              <select v-model="leaveBalanceForm.academic_year_id" required>
                <option value="">Select year</option>
                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label>Total days</label>
            <input v-model="leaveBalanceForm.total_days" min="0" max="255" type="number" />
          </div>
          <button class="button" type="submit" :disabled="savingLeaveBalance">{{ savingLeaveBalance ? 'Saving balance' : 'Save balance' }}</button>
        </form>
      </section>

      <form class="surface record-form" @submit.prevent="applyLeave">
        <div>
          <p class="eyebrow">Leave application</p>
          <h2>Submit leave</h2>
        </div>
        <div class="form-row">
          <div class="field">
            <label>Employee</label>
            <select v-model="leaveApplicationForm.employee_id" required>
              <option value="">Select employee</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.full_name }}</option>
            </select>
          </div>
          <div class="field">
            <label>Leave type</label>
            <select v-model="leaveApplicationForm.leave_type_id" required>
              <option value="">Select leave type</option>
              <option v-for="leaveType in leaveTypes" :key="leaveType.id" :value="leaveType.id">{{ leaveType.name }}</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="field">
            <label>From</label>
            <input v-model="leaveApplicationForm.from_date" required type="date" />
          </div>
          <div class="field">
            <label>To</label>
            <input v-model="leaveApplicationForm.to_date" required type="date" />
          </div>
        </div>
        <div class="field">
          <label>Reason</label>
          <input v-model="leaveApplicationForm.reason" required type="text" />
        </div>
        <button class="button" type="submit" :disabled="savingLeaveApplication">{{ savingLeaveApplication ? 'Submitting leave' : 'Submit leave' }}</button>
      </form>

      <section class="surface record-list">
        <div class="list-header">
          <div>
            <p class="eyebrow">Leave queue</p>
            <h2>Applications</h2>
          </div>
          <button class="button secondary" type="button" @click="loadWorkspace">Refresh</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Employee</th>
                <th>Type</th>
                <th>Dates</th>
                <th>Days</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="application in leaveApplications" :key="application.id">
                <td>{{ application.employee?.full_name || application.employee_id }}</td>
                <td>{{ application.leave_type?.name || application.leave_type_id }}</td>
                <td>{{ application.from_date }} to {{ application.to_date }}</td>
                <td>{{ application.total_days }}</td>
                <td><span class="status-pill">{{ application.status }}</span></td>
                <td>
                  <button class="button secondary compact" type="button" :disabled="reviewingId === application.id || application.status !== 'pending'" @click="reviewLeave(application, 'approve')">Approve</button>
                  <button class="button secondary compact" type="button" :disabled="reviewingId === application.id || application.status === 'cancelled'" @click="reviewLeave(application, 'cancel')">Cancel</button>
                </td>
              </tr>
              <tr v-if="!leaveApplications.length">
                <td colspan="6">No leave applications yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
</SchoolWorkspaceTemplate>
</template>


