<script setup lang="ts">
import type { AcademicClass, AcademicYear, FeeCategory, FeeStructure, StudentEnrollment, StudentInvoice } from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface ItemResponse<T> {
  data: T
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const categories = ref<FeeCategory[]>([])
const structures = ref<FeeStructure[]>([])
const invoices = ref<StudentInvoice[]>([])
const academicYears = ref<AcademicYear[]>([])
const academicClasses = ref<AcademicClass[]>([])
const enrollments = ref<StudentEnrollment[]>([])
const loading = ref(false)
const savingCategory = ref(false)
const savingStructure = ref(false)
const savingInvoice = ref(false)
const queueingBulk = ref(false)
const error = ref('')
const success = ref('')

const categoryForm = reactive({
  name: '',
  code: '',
  billing_type: 'monthly',
})

const structureForm = reactive({
  fee_category_id: '',
  academic_year_id: '',
  academic_class_id: '',
  amount: '',
  due_day_of_month: 10,
  is_recurring: true,
})

const invoiceForm = reactive({
  student_enrollment_id: '',
  academic_year_id: '',
  fee_month: '',
  fee_structure_id: '',
  due_date: '',
})

const bulkForm = reactive({
  academic_class_id: '',
  academic_year_id: '',
  month: '',
  fee_structure_id: '',
})

const outstandingTotal = computed(() =>
  invoices.value.reduce((sum, invoice) => sum + Math.max(0, Number(invoice.total) - Number(invoice.paid_amount)), 0),
)
const paidCount = computed(() => invoices.value.filter((invoice) => invoice.status === 'paid').length)
const activeStructureCount = computed(() => structures.value.filter((structure) => structure.status === 'active').length)

function enrollmentLabel(enrollment: StudentEnrollment) {
  const name = enrollment.student?.full_name || `Student ${enrollment.student_id}`
  const roll = enrollment.roll_no ? ` / Roll ${enrollment.roll_no}` : ''

  return `${name}${roll}`
}

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const [categoryResponse, structureResponse, invoiceResponse, yearResponse, classResponse, enrollmentResponse] = await Promise.all([
      api.request<ListResponse<FeeCategory>>(`/schools/${schoolId.value}/fee-categories?per_page=100`),
      api.request<ListResponse<FeeStructure>>(`/schools/${schoolId.value}/fee-structures?per_page=100`),
      api.request<ListResponse<StudentInvoice>>(`/schools/${schoolId.value}/student-invoices?per_page=100`),
      api.request<ListResponse<AcademicYear>>(`/schools/${schoolId.value}/academic-years?status=active&per_page=100`),
      api.request<ListResponse<AcademicClass>>(`/schools/${schoolId.value}/academic-classes?status=active&per_page=100`),
      api.request<ListResponse<StudentEnrollment>>(`/schools/${schoolId.value}/student-enrollments?status=active&per_page=100`),
    ])
    categories.value = categoryResponse.data
    structures.value = structureResponse.data
    invoices.value = invoiceResponse.data
    academicYears.value = yearResponse.data
    academicClasses.value = classResponse.data
    enrollments.value = enrollmentResponse.data
  } catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load finance workspace.'
  } finally {
    loading.value = false
  }
}

async function saveCategory() {
  savingCategory.value = true
  error.value = ''
  success.value = ''
  try {
    await api.request<ItemResponse<FeeCategory>>(`/schools/${schoolId.value}/fee-categories`, {
      method: 'POST',
      body: { ...categoryForm },
    })
    success.value = 'Fee category saved.'
    categoryForm.name = ''
    categoryForm.code = ''
    await loadWorkspace()
  } catch (categoryError) {
    error.value = categoryError instanceof Error ? categoryError.message : 'Unable to save category.'
  } finally {
    savingCategory.value = false
  }
}

async function saveStructure() {
  savingStructure.value = true
  error.value = ''
  success.value = ''
  try {
    await api.request<ItemResponse<FeeStructure>>(`/schools/${schoolId.value}/fee-structures`, {
      method: 'POST',
      body: {
        fee_category_id: Number(structureForm.fee_category_id),
        academic_year_id: Number(structureForm.academic_year_id),
        academic_class_id: structureForm.academic_class_id ? Number(structureForm.academic_class_id) : null,
        amount: Number(structureForm.amount),
        due_day_of_month: Number(structureForm.due_day_of_month),
        is_recurring: structureForm.is_recurring,
      },
    })
    success.value = 'Fee structure saved.'
    structureForm.amount = ''
    await loadWorkspace()
  } catch (structureError) {
    error.value = structureError instanceof Error ? structureError.message : 'Unable to save fee structure.'
  } finally {
    savingStructure.value = false
  }
}

async function createInvoice() {
  savingInvoice.value = true
  error.value = ''
  success.value = ''
  try {
    await api.request<ItemResponse<StudentInvoice>>(`/schools/${schoolId.value}/student-invoices`, {
      method: 'POST',
      body: {
        student_enrollment_id: Number(invoiceForm.student_enrollment_id),
        academic_year_id: Number(invoiceForm.academic_year_id),
        fee_month: invoiceForm.fee_month || null,
        fee_structure_ids: [Number(invoiceForm.fee_structure_id)],
        due_date: invoiceForm.due_date || null,
      },
    })
    success.value = 'Invoice created.'
    invoiceForm.student_enrollment_id = ''
    await loadWorkspace()
  } catch (invoiceError) {
    error.value = invoiceError instanceof Error ? invoiceError.message : 'Unable to create invoice.'
  } finally {
    savingInvoice.value = false
  }
}

async function queueBulkInvoices() {
  queueingBulk.value = true
  error.value = ''
  success.value = ''
  try {
    const response = await api.request<{ data: { job_id: string } }>(`/schools/${schoolId.value}/student-invoices/bulk-generate`, {
      method: 'POST',
      body: {
        academic_class_id: Number(bulkForm.academic_class_id),
        academic_year_id: Number(bulkForm.academic_year_id),
        month: bulkForm.month,
        fee_structure_ids: [Number(bulkForm.fee_structure_id)],
      },
    })
    success.value = `Bulk invoices queued: ${response.data.job_id}.`
  } catch (bulkError) {
    error.value = bulkError instanceof Error ? bulkError.message : 'Unable to queue bulk invoices.'
  } finally {
    queueingBulk.value = false
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <main class="operation-shell">
    <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Finance navigation"
      context-title="Finance tools"
      :context-links="[
        { label: 'Payment Gateways', to: `/schools/${schoolId}/payment-gateways` },
        { label: 'Marks', to: `/schools/${schoolId}/marks` },
        { label: 'Staff Operations', to: `/schools/${schoolId}/staff-operations` },
        { label: 'Students', to: `/schools/${schoolId}/students` },
      ]"
    />

    <section class="operation-workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">Finance</p>
          <h1>Configure fees, create invoices, and queue class billing.</h1>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading" class="muted">Loading finance workspace</p>

      <section class="summary-grid">
        <article class="surface summary-item">
          <span>Structures</span>
          <strong>{{ activeStructureCount }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Paid invoices</span>
          <strong>{{ paidCount }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Outstanding</span>
          <strong>{{ outstandingTotal.toFixed(0) }}</strong>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="saveCategory">
          <div>
            <p class="eyebrow">Fee category</p>
            <h2>Add fee head</h2>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="fee-category-name">Name</label>
              <input id="fee-category-name" v-model="categoryForm.name" required type="text" placeholder="Monthly Tuition" />
            </div>
            <div class="field">
              <label for="fee-category-code">Code</label>
              <input id="fee-category-code" v-model="categoryForm.code" required type="text" placeholder="TUITION" />
            </div>
          </div>
          <div class="field">
            <label for="fee-category-billing-type">Billing type</label>
            <select id="fee-category-billing-type" v-model="categoryForm.billing_type">
              <option value="monthly">Monthly</option>
              <option value="one_time">One time</option>
              <option value="per_exam">Per exam</option>
              <option value="optional">Optional</option>
            </select>
          </div>
          <button class="button" type="submit" :disabled="savingCategory">{{ savingCategory ? 'Saving category' : 'Save category' }}</button>
        </form>

        <form class="surface record-form" @submit.prevent="saveStructure">
          <div>
            <p class="eyebrow">Fee structure</p>
            <h2>Set amount</h2>
          </div>
          <div class="field">
            <label for="fee-structure-category">Fee category</label>
            <select id="fee-structure-category" v-model="structureForm.fee_category_id" required>
              <option value="">Select category</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="fee-structure-year">Academic year</label>
              <select id="fee-structure-year" v-model="structureForm.academic_year_id" required>
                <option value="">Select year</option>
                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
              </select>
            </div>
            <div class="field">
              <label for="fee-structure-class">Class</label>
              <select id="fee-structure-class" v-model="structureForm.academic_class_id">
                <option value="">All classes</option>
                <option v-for="schoolClass in academicClasses" :key="schoolClass.id" :value="schoolClass.id">{{ schoolClass.name }}</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="fee-structure-amount">Amount</label>
              <input id="fee-structure-amount" v-model="structureForm.amount" required min="0" step="0.01" type="number" />
            </div>
            <div class="field">
              <label for="fee-structure-due-day">Due day</label>
              <input id="fee-structure-due-day" v-model="structureForm.due_day_of_month" min="1" max="31" type="number" />
            </div>
          </div>
          <button class="button" type="submit" :disabled="savingStructure">{{ savingStructure ? 'Saving structure' : 'Save structure' }}</button>
        </form>
      </section>

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="createInvoice">
          <div>
            <p class="eyebrow">Manual invoice</p>
            <h2>Create invoice</h2>
          </div>
          <div class="field">
            <label for="manual-invoice-student">Student</label>
            <select id="manual-invoice-student" v-model="invoiceForm.student_enrollment_id" required>
              <option value="">Select student</option>
              <option v-for="enrollment in enrollments" :key="enrollment.id" :value="enrollment.id">{{ enrollmentLabel(enrollment) }}</option>
            </select>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="manual-invoice-year">Academic year</label>
              <select id="manual-invoice-year" v-model="invoiceForm.academic_year_id" required>
                <option value="">Select year</option>
                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
              </select>
            </div>
            <div class="field">
              <label for="manual-invoice-month">Fee month</label>
              <input id="manual-invoice-month" v-model="invoiceForm.fee_month" type="month" />
            </div>
          </div>
          <div class="field">
            <label for="manual-invoice-structure">Fee structure</label>
            <select id="manual-invoice-structure" v-model="invoiceForm.fee_structure_id" required>
              <option value="">Select structure</option>
              <option v-for="structure in structures" :key="structure.id" :value="structure.id">
                {{ structure.fee_category?.name || structure.fee_category_id }} / {{ structure.amount }}
              </option>
            </select>
          </div>
          <button class="button" type="submit" :disabled="savingInvoice">{{ savingInvoice ? 'Creating invoice' : 'Create invoice' }}</button>
        </form>

        <form class="surface record-form" @submit.prevent="queueBulkInvoices">
          <div>
            <p class="eyebrow">Bulk billing</p>
            <h2>Queue class invoices</h2>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="bulk-invoice-class">Class</label>
              <select id="bulk-invoice-class" v-model="bulkForm.academic_class_id" required>
                <option value="">Select class</option>
                <option v-for="schoolClass in academicClasses" :key="schoolClass.id" :value="schoolClass.id">{{ schoolClass.name }}</option>
              </select>
            </div>
            <div class="field">
              <label for="bulk-invoice-month">Month</label>
              <input id="bulk-invoice-month" v-model="bulkForm.month" required type="month" />
            </div>
          </div>
          <div class="field">
            <label for="bulk-invoice-year">Academic year</label>
            <select id="bulk-invoice-year" v-model="bulkForm.academic_year_id" required>
              <option value="">Select year</option>
              <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
            </select>
          </div>
          <div class="field">
            <label for="bulk-invoice-structure">Fee structure</label>
            <select id="bulk-invoice-structure" v-model="bulkForm.fee_structure_id" required>
              <option value="">Select structure</option>
              <option v-for="structure in structures" :key="structure.id" :value="structure.id">
                {{ structure.fee_category?.name || structure.fee_category_id }} / {{ structure.amount }}
              </option>
            </select>
          </div>
          <button class="button" type="submit" :disabled="queueingBulk">{{ queueingBulk ? 'Queueing invoices' : 'Queue invoices' }}</button>
        </form>
      </section>

      <section class="surface record-list">
        <div class="list-header">
          <div>
            <p class="eyebrow">Invoice ledger</p>
            <h2>Student invoices</h2>
          </div>
          <button class="button secondary" type="button" @click="loadWorkspace">Refresh</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Invoice</th>
                <th>Student</th>
                <th>Month</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="invoice in invoices" :key="invoice.id">
                <td>{{ invoice.invoice_no }}</td>
                <td>{{ invoice.student_enrollment?.student?.full_name || invoice.student_enrollment_id }}</td>
                <td>{{ invoice.fee_month || 'Manual' }}</td>
                <td>{{ invoice.total }}</td>
                <td>{{ invoice.paid_amount }}</td>
                <td><span class="status-pill">{{ invoice.status }}</span></td>
              </tr>
              <tr v-if="!invoices.length">
                <td colspan="6">No invoices yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </main>
</template>
