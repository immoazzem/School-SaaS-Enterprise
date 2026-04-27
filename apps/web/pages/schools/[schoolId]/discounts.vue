<script setup lang="ts">
import type { AcademicYear, DiscountPolicy, FeeCategory, StudentEnrollment } from '~/composables/useApi'

interface StudentDiscountItem {
  id: number
  notes?: string | null
  student_enrollment?: {
    student?: { full_name?: string; admission_no?: string }
    roll_no?: string | null
  } | null
  discount_policy?: {
    name?: string
    code?: string
    discount_type?: string
    amount?: string
  } | null
  academic_year?: {
    name?: string
  } | null
}

interface ListResponse<T> {
  data: T[]
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const policies = ref<DiscountPolicy[]>([])
const studentDiscounts = ref<StudentDiscountItem[]>([])
const categories = ref<FeeCategory[]>([])
const academicYears = ref<AcademicYear[]>([])
const enrollments = ref<StudentEnrollment[]>([])
const loading = ref(false)
const savingPolicy = ref(false)
const savingDiscount = ref(false)
const error = ref('')
const success = ref('')

const policyForm = reactive({
  name: '',
  code: '',
  discount_type: 'flat',
  amount: '',
  applies_to_category_ids: [] as number[],
  is_stackable: false,
  status: 'active',
})

const studentDiscountForm = reactive({
  student_enrollment_id: '',
  discount_policy_id: '',
  academic_year_id: '',
  notes: '',
})

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const [policyResponse, discountResponse, categoryResponse, yearResponse, enrollmentResponse] = await Promise.all([
      api.request<ListResponse<DiscountPolicy>>(`/schools/${schoolId.value}/discount-policies?per_page=100`),
      api.request<ListResponse<StudentDiscountItem>>(`/schools/${schoolId.value}/student-discounts?per_page=100`),
      api.request<ListResponse<FeeCategory>>(`/schools/${schoolId.value}/fee-categories?per_page=100`),
      api.request<ListResponse<AcademicYear>>(`/schools/${schoolId.value}/academic-years?per_page=100`),
      api.request<ListResponse<StudentEnrollment>>(`/schools/${schoolId.value}/student-enrollments?status=active&per_page=100`),
    ])
    policies.value = policyResponse.data
    studentDiscounts.value = discountResponse.data
    categories.value = categoryResponse.data
    academicYears.value = yearResponse.data
    enrollments.value = enrollmentResponse.data
  }
  catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load discounts workspace.'
  }
  finally {
    loading.value = false
  }
}

async function savePolicy() {
  savingPolicy.value = true
  error.value = ''
  success.value = ''
  try {
    await api.request(`/schools/${schoolId.value}/discount-policies`, {
      method: 'POST',
      body: {
        ...policyForm,
        amount: Number(policyForm.amount),
      },
    })
    success.value = 'Discount policy saved.'
    policyForm.name = ''
    policyForm.code = ''
    policyForm.amount = ''
    await loadWorkspace()
  }
  catch (saveError) {
    error.value = saveError instanceof Error ? saveError.message : 'Unable to save discount policy.'
  }
  finally {
    savingPolicy.value = false
  }
}

async function saveStudentDiscount() {
  savingDiscount.value = true
  error.value = ''
  success.value = ''
  try {
    await api.request(`/schools/${schoolId.value}/student-discounts`, {
      method: 'POST',
      body: {
        student_enrollment_id: Number(studentDiscountForm.student_enrollment_id),
        discount_policy_id: Number(studentDiscountForm.discount_policy_id),
        academic_year_id: Number(studentDiscountForm.academic_year_id),
        notes: studentDiscountForm.notes || null,
      },
    })
    success.value = 'Student discount assigned.'
    studentDiscountForm.student_enrollment_id = ''
    studentDiscountForm.discount_policy_id = ''
    studentDiscountForm.notes = ''
    await loadWorkspace()
  }
  catch (saveError) {
    error.value = saveError instanceof Error ? saveError.message : 'Unable to assign student discount.'
  }
  finally {
    savingDiscount.value = false
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
        :school-id="schoolId"
        aria-label="Discounts navigation"
        context-title="Finance links"
        :context-links="[
          { label: 'Finance', to: `/schools/${schoolId}/finance` },
          { label: 'Invoice Payments', to: `/schools/${schoolId}/invoice-payments` },
          { label: 'Reports', to: `/schools/${schoolId}/reports` },
        ]"
      />
    </template>

    <header class="workspace-header">
      <div>
        <p class="eyebrow">Finance</p>
        <h1>Discounts</h1>
        <p>Define scholarship rules and apply approved discounts to enrolled students.</p>
      </div>
    </header>

    <VAlert v-if="error" type="error" variant="tonal">{{ error }}</VAlert>
    <VAlert v-if="success" type="success" variant="tonal">{{ success }}</VAlert>

    <section class="workspace-grid">
      <form class="surface record-form" @submit.prevent="savePolicy">
        <h2>Discount policy</h2>
        <div class="form-row">
          <div class="field"><label for="discount-policy-name">Name</label><input id="discount-policy-name" v-model="policyForm.name" required type="text" /></div>
          <div class="field"><label for="discount-policy-code">Code</label><input id="discount-policy-code" v-model="policyForm.code" required type="text" /></div>
        </div>
        <div class="form-row">
          <div class="field">
            <label for="discount-policy-type">Type</label>
            <select id="discount-policy-type" v-model="policyForm.discount_type">
              <option value="flat">Flat</option>
              <option value="percent">Percent</option>
            </select>
          </div>
          <div class="field"><label for="discount-policy-amount">Amount</label><input id="discount-policy-amount" v-model="policyForm.amount" required type="number" min="0" step="0.01" /></div>
        </div>
        <div class="field">
          <label for="discount-policy-category-scope">Category scope</label>
          <select id="discount-policy-category-scope" v-model="policyForm.applies_to_category_ids" multiple>
            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
          </select>
        </div>
        <label><input v-model="policyForm.is_stackable" type="checkbox" /> Stackable policy</label>
        <VBtn color="primary" :loading="savingPolicy" type="submit">Save policy</VBtn>
      </form>

      <form class="surface record-form" @submit.prevent="saveStudentDiscount">
        <h2>Assign student discount</h2>
        <div class="field">
          <label for="student-discount-enrollment">Student enrollment</label>
          <select id="student-discount-enrollment" v-model="studentDiscountForm.student_enrollment_id" required>
            <option value="">Select enrollment</option>
            <option v-for="enrollment in enrollments" :key="enrollment.id" :value="enrollment.id">
              {{ enrollment.student?.full_name || enrollment.student_id }} / {{ enrollment.roll_no || 'No roll' }}
            </option>
          </select>
        </div>
        <div class="field">
          <label for="student-discount-policy">Policy</label>
          <select id="student-discount-policy" v-model="studentDiscountForm.discount_policy_id" required>
            <option value="">Select policy</option>
            <option v-for="policy in policies" :key="policy.id" :value="policy.id">{{ policy.name }}</option>
          </select>
        </div>
        <div class="field">
          <label for="student-discount-academic-year">Academic year</label>
          <select id="student-discount-academic-year" v-model="studentDiscountForm.academic_year_id" required>
            <option value="">Select year</option>
            <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
          </select>
        </div>
        <div class="field"><label for="student-discount-notes">Notes</label><input id="student-discount-notes" v-model="studentDiscountForm.notes" type="text" /></div>
        <VBtn color="primary" :loading="savingDiscount" type="submit">Assign discount</VBtn>
      </form>
    </section>

    <section class="surface record-list">
      <div class="list-header">
        <h2>Assigned discounts</h2>
        <span class="muted">{{ loading ? 'Refreshing...' : `${studentDiscounts.length} records` }}</span>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Student</th>
              <th>Policy</th>
              <th>Year</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="discount in studentDiscounts" :key="discount.id">
              <td>{{ discount.student_enrollment?.student?.full_name || 'Unknown student' }}</td>
              <td>{{ discount.discount_policy?.name || 'Unknown policy' }}</td>
              <td>{{ discount.academic_year?.name || 'Unknown year' }}</td>
              <td>{{ discount.notes || '-' }}</td>
            </tr>
            <tr v-if="!studentDiscounts.length && !loading">
              <td colspan="4">No student discounts yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </SchoolWorkspaceTemplate>
</template>
