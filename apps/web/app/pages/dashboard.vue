<script setup lang="ts">
import type { DashboardSummary } from '~/composables/useApi'

interface ItemResponse<T> {
  data: T
}

type ModuleLink = {
  label: string
  description: string
  route: string
  permissions: string[]
  tone: 'academic' | 'people' | 'finance' | 'operations'
}

const auth = useAuth()
const api = useApi()
const router = useRouter()
useSchoolLocale()

const loading = ref(false)
const summaryLoading = ref(false)
const creatingSchool = ref(false)
const error = ref('')
const success = ref('')
const dashboardSummary = ref<DashboardSummary | null>(null)

const schoolForm = reactive({
  name: '',
  slug: '',
  timezone: 'Asia/Dhaka',
  locale: 'en',
})

const selectedSchool = computed(() =>
  auth.schools.value.find((school) => school.id === auth.selectedSchoolId.value) ?? null,
)

const modules: ModuleLink[] = [
  { label: 'Academic Years', description: 'Sessions and current year', route: 'academic-years', permissions: ['academic_years.manage'], tone: 'academic' },
  { label: 'Classes', description: 'Class levels and order', route: 'academic-classes', permissions: ['academic_classes.manage'], tone: 'academic' },
  { label: 'Sections', description: 'Rooms, capacity, class mapping', route: 'academic-sections', permissions: ['sections.manage'], tone: 'academic' },
  { label: 'Subjects', description: 'Core and co-curricular subjects', route: 'subjects', permissions: ['subjects.manage'], tone: 'academic' },
  { label: 'Class Subjects', description: 'Marks and subject assignment', route: 'class-subjects', permissions: ['class_subjects.manage'], tone: 'academic' },
  { label: 'Groups', description: 'Science and general tracks', route: 'student-groups', permissions: ['student_groups.manage'], tone: 'academic' },
  { label: 'Shifts', description: 'Morning and day operations', route: 'shifts', permissions: ['shifts.manage'], tone: 'academic' },
  { label: 'Timetable', description: 'Periods, rooms, teachers', route: 'timetable', permissions: ['timetable.manage'], tone: 'academic' },
  { label: 'Assignments', description: 'Class work and submissions', route: 'assignments', permissions: ['assignments.manage'], tone: 'academic' },
  { label: 'Students', description: 'Admissions and profiles', route: 'students', permissions: ['students.manage'], tone: 'people' },
  { label: 'Enrollments', description: 'Year, class, roll, section', route: 'enrollments', permissions: ['enrollments.manage'], tone: 'people' },
  { label: 'Teachers', description: 'Teaching profiles', route: 'teacher-profiles', permissions: ['teachers.manage'], tone: 'people' },
  { label: 'Attendance', description: 'Student daily records', route: 'attendance', permissions: ['attendance.manage'], tone: 'people' },
  { label: 'Designations', description: 'Staff role catalog', route: 'designations', permissions: ['designations.manage'], tone: 'people' },
  { label: 'Employees', description: 'Staff records and salary base', route: 'employees', permissions: ['employees.manage'], tone: 'people' },
  { label: 'Exams', description: 'Schedules and publication', route: 'exams', permissions: ['exams.manage'], tone: 'operations' },
  { label: 'Marks', description: 'Entry and verification', route: 'marks', permissions: ['marks.enter.any', 'marks.enter.own'], tone: 'operations' },
  { label: 'Reports', description: 'Results, PDFs, attendance', route: 'reports', permissions: ['reports.view'], tone: 'operations' },
  { label: 'Promotions', description: 'Year-end progression', route: 'promotions', permissions: ['promotions.manage'], tone: 'operations' },
  { label: 'Calendar', description: 'Events and holidays', route: 'calendar', permissions: ['calendar.manage', 'reports.view'], tone: 'operations' },
  { label: 'Documents', description: 'Circulars and files', route: 'documents', permissions: ['documents.manage'], tone: 'operations' },
  { label: 'Finance', description: 'Fees, invoices, payments', route: 'finance', permissions: ['finance.manage'], tone: 'finance' },
  { label: 'Payment Gateways', description: 'bKash, Nagad, cards', route: 'payment-gateways', permissions: ['payment_gateways.manage'], tone: 'finance' },
  { label: 'Staff Ops', description: 'Payroll, attendance, leave', route: 'staff-operations', permissions: ['payroll.manage', 'employee_attendance.manage', 'leave.manage'], tone: 'finance' },
]

const navGroups = computed(() => [
  { title: 'Academics', items: modules.filter((item) => item.tone === 'academic') },
  { title: 'People', items: modules.filter((item) => item.tone === 'people') },
  { title: 'Operations', items: modules.filter((item) => item.tone === 'operations') },
  { title: 'Finance', items: modules.filter((item) => item.tone === 'finance') },
])

const featuredModules = computed(() =>
  modules.filter((item) => canOpen(item)).slice(0, 8),
)

const currencyFormatter = new Intl.NumberFormat('en-BD', {
  maximumFractionDigits: 0,
  style: 'currency',
  currency: 'BDT',
})

const kpis = computed(() => [
  {
    label: 'Students',
    value: dashboardSummary.value?.admin.student_count ?? 0,
    detail: 'Active and historical records',
  },
  {
    label: 'Employees',
    value: dashboardSummary.value?.admin.employee_count ?? 0,
    detail: 'Teaching and operations team',
  },
  {
    label: 'Attendance',
    value: `${dashboardSummary.value?.admin.today_attendance_rate ?? 0}%`,
    detail: 'Today across recorded students',
  },
  {
    label: 'Collection',
    value: formatMoney(dashboardSummary.value?.admin.fee_collection_this_month ?? 0),
    detail: 'This month',
  },
])

const collectionTrend = computed(() => dashboardSummary.value?.accountant.collection_trend ?? [])
const trendMax = computed(() => {
  const values = collectionTrend.value.map((row) => Number(row.total))

  return Math.max(...values, 1)
})

function formatMoney(value: number | string) {
  return currencyFormatter.format(Number(value))
}

function canOpen(module: ModuleLink) {
  const permissions = selectedSchool.value?.permissions ?? []

  return module.permissions.some((permission) => permissions.includes(permission))
}

async function loadDashboard() {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    await auth.refreshSchools()
    await auth.refreshProfile()
    await loadSummary()
  } catch (dashboardError) {
    error.value = dashboardError instanceof Error ? dashboardError.message : 'Unable to load dashboard.'
  } finally {
    loading.value = false
  }
}

async function loadSummary() {
  if (!selectedSchool.value) {
    dashboardSummary.value = null
    return
  }

  summaryLoading.value = true
  try {
    const response = await api.request<ItemResponse<DashboardSummary>>(
      `/schools/${selectedSchool.value.id}/dashboard/summary`,
    )
    dashboardSummary.value = response.data
  } catch {
    dashboardSummary.value = null
  } finally {
    summaryLoading.value = false
  }
}

function chooseSchool(event: Event) {
  const value = Number((event.target as HTMLSelectElement).value)
  auth.selectSchool(value)
}

async function createSchool() {
  creatingSchool.value = true
  error.value = ''
  success.value = ''

  try {
    const school = await auth.createSchool({
      name: schoolForm.name,
      slug: schoolForm.slug || undefined,
      timezone: schoolForm.timezone || undefined,
      locale: schoolForm.locale || undefined,
    })

    success.value = `${school.name} is ready.`
    schoolForm.name = ''
    schoolForm.slug = ''
    await loadSummary()
  } catch (schoolError) {
    error.value = schoolError instanceof Error ? schoolError.message : 'Unable to create school.'
  } finally {
    creatingSchool.value = false
  }
}

async function openModule(module: ModuleLink) {
  if (!selectedSchool.value) {
    error.value = 'Create or select a school first.'
    return
  }

  if (!canOpen(module)) {
    error.value = `${module.label} is not available for your current role.`
    return
  }

  await router.push(`/schools/${selectedSchool.value.id}/${module.route}`)
}

watch(
  () => auth.selectedSchoolId.value,
  () => {
    void loadSummary()
  },
)

onMounted(loadDashboard)
</script>

<template>
  <main class="dashboard-shell">
    <aside class="dashboard-sidebar">
      <NuxtLink class="brand" to="/dashboard">
        <span>EA</span>
        <strong>School SaaS</strong>
      </NuxtLink>

      <div v-if="selectedSchool" class="school-chip">
        <small>Selected school</small>
        <strong>{{ selectedSchool.name }}</strong>
      </div>

      <nav aria-label="Main navigation" class="module-nav">
        <section v-for="group in navGroups" :key="group.title" class="nav-group">
          <h2>{{ group.title }}</h2>
          <button
            v-for="item in group.items"
            :key="item.label"
            class="nav-link"
            type="button"
            :disabled="!canOpen(item)"
            @click="openModule(item)"
          >
            <span>{{ item.label }}</span>
          </button>
        </section>
      </nav>
    </aside>

    <section class="dashboard-main">
      <header class="dashboard-header">
        <div>
          <p class="eyebrow">Command center</p>
          <h1>{{ selectedSchool?.name || 'No school selected' }}</h1>
          <p class="header-copy">Five years of academics, attendance, finance, staff, exams, documents, and promotions are ready for local QA.</p>
        </div>

        <div class="header-actions">
          <LocaleSwitcher />
          <select
            v-if="auth.schools.value.length"
            class="school-select"
            :value="auth.selectedSchoolId.value || ''"
            aria-label="Select school"
            @change="chooseSchool"
          >
            <option v-for="school in auth.schools.value" :key="school.id" :value="school.id">
              {{ school.name }}
            </option>
          </select>
          <button class="dash-btn ghost" type="button" @click="auth.logout()">{{ $t('actions.signOut') }}</button>
        </div>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading || summaryLoading" class="muted">Loading dashboard</p>

      <section class="kpi-grid" aria-label="School metrics">
        <article v-for="metric in kpis" :key="metric.label" class="kpi-card">
          <span>{{ metric.label }}</span>
          <strong>{{ metric.value }}</strong>
          <small>{{ metric.detail }}</small>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="panel wide-panel">
          <div class="panel-header">
            <div>
              <p class="eyebrow">Operating picture</p>
              <h2>Collections trend</h2>
            </div>
            <strong>{{ dashboardSummary?.accountant.unpaid_invoices ?? 0 }} unpaid</strong>
          </div>

          <div v-if="collectionTrend.length" class="trend-chart" aria-label="Collection trend">
            <div v-for="row in collectionTrend" :key="row.month" class="trend-row">
              <span>{{ row.month }}</span>
              <div>
                <i :style="{ width: `${Math.max((Number(row.total) / trendMax) * 100, 4)}%` }"></i>
              </div>
              <strong>{{ formatMoney(row.total) }}</strong>
            </div>
          </div>
          <p v-else class="empty-copy">No collection trend yet.</p>
        </article>

        <article class="panel">
          <div class="panel-header">
            <div>
              <p class="eyebrow">Today</p>
              <h2>Attention</h2>
            </div>
          </div>

          <ul class="attention-list">
            <li>
              <span>Pending leave</span>
              <strong>{{ dashboardSummary?.admin.pending_leave_applications ?? 0 }}</strong>
            </li>
            <li>
              <span>Pending marks</span>
              <strong>{{ dashboardSummary?.teacher.pending_marks_entries ?? 0 }}</strong>
            </li>
            <li>
              <span>Pending salaries</span>
              <strong>{{ dashboardSummary?.accountant.pending_salaries ?? 0 }}</strong>
            </li>
          </ul>
        </article>
      </section>

      <section class="panel">
        <div class="panel-header">
          <div>
            <p class="eyebrow">Modules</p>
            <h2>Workspaces</h2>
          </div>
        </div>

        <div class="module-grid">
          <button
            v-for="item in featuredModules"
            :key="item.label"
            class="module-card"
            type="button"
            @click="openModule(item)"
          >
            <strong>{{ item.label }}</strong>
            <span>{{ item.description }}</span>
          </button>
        </div>
      </section>

      <section class="dashboard-grid">
        <form class="panel setup-panel" @submit.prevent="createSchool">
          <div>
            <p class="eyebrow">Tenant setup</p>
            <h2>Create a school</h2>
            <p class="muted">Add another tenant and continue from the same dashboard.</p>
          </div>

          <div class="field">
            <label for="school-name">School name</label>
            <input
              id="school-name"
              v-model="schoolForm.name"
              autocomplete="organization"
              required
              type="text"
              placeholder="Example International School"
            />
          </div>

          <div class="field">
            <label for="school-slug">Slug</label>
            <input id="school-slug" v-model="schoolForm.slug" type="text" placeholder="example-international-school" />
          </div>

          <div class="form-row">
            <div class="field">
              <label for="school-timezone">Timezone</label>
              <input id="school-timezone" v-model="schoolForm.timezone" type="text" />
            </div>
            <div class="field">
              <label for="school-locale">Locale</label>
              <input id="school-locale" v-model="schoolForm.locale" type="text" />
            </div>
          </div>

          <button class="dash-btn primary" type="submit" :disabled="creatingSchool">
            {{ creatingSchool ? 'Creating school' : 'Create school' }}
          </button>
        </form>

        <section class="panel">
          <div class="panel-header">
            <div>
              <p class="eyebrow">Tenants</p>
              <h2>Schools</h2>
            </div>
          </div>

          <div v-if="auth.schools.value.length" class="school-list">
            <button
              v-for="school in auth.schools.value"
              :key="school.id"
              class="school-row"
              :class="{ selected: school.id === auth.selectedSchoolId.value }"
              type="button"
              @click="auth.selectSchool(school.id)"
            >
              <span>
                <strong>{{ school.name }}</strong>
                <small>{{ school.slug }} / {{ school.roles?.[0]?.name || 'Member' }}</small>
              </span>
              <em>{{ school.status }}</em>
            </button>
          </div>

          <p v-else class="empty-copy">No schools yet.</p>
        </section>
      </section>
    </section>
  </main>
</template>

<style scoped>
.dashboard-shell {
  display: grid;
  min-height: 100vh;
  grid-template-columns: 288px minmax(0, 1fr);
  background: #f4f7f8;
  color: #18201d;
}

.dashboard-sidebar {
  position: sticky;
  top: 0;
  display: flex;
  height: 100vh;
  flex-direction: column;
  gap: 20px;
  overflow-y: auto;
  border-right: 1px solid #d9e1df;
  padding: 22px;
  background: #ffffff;
}

.brand {
  display: flex;
  gap: 12px;
  align-items: center;
}

.brand span {
  display: grid;
  width: 38px;
  height: 38px;
  place-items: center;
  border-radius: 8px;
  background: #1f6f5b;
  color: #fff;
  font-weight: 900;
}

.brand strong,
.school-chip strong,
.panel h2,
.dashboard-header h1 {
  color: #18201d;
}

.school-chip {
  display: grid;
  gap: 4px;
  border: 1px solid #d9e1df;
  border-radius: 8px;
  padding: 12px;
  background: #f8fbfa;
}

.school-chip small,
.eyebrow {
  color: #a33d4f;
  font-weight: 900;
  text-transform: uppercase;
}

.module-nav {
  display: grid;
  gap: 18px;
}

.nav-group {
  display: grid;
  gap: 6px;
}

.nav-group h2 {
  margin: 0 0 4px;
  color: #687370;
  font-size: 0.78rem;
  letter-spacing: 0;
  text-transform: uppercase;
}

.nav-link {
  display: flex;
  width: 100%;
  min-height: 36px;
  align-items: center;
  border: 0;
  border-radius: 8px;
  padding: 0 10px;
  background: transparent;
  color: #34423e;
  cursor: pointer;
  font-weight: 760;
  text-align: left;
}

.nav-link:hover {
  background: #edf6f2;
  color: #1f6f5b;
}

.nav-link:disabled {
  cursor: not-allowed;
  opacity: 0.42;
}

.dashboard-main {
  display: grid;
  align-content: start;
  gap: 18px;
  padding: 28px;
}

.dashboard-header,
.panel-header {
  display: flex;
  gap: 18px;
  align-items: flex-start;
  justify-content: space-between;
}

.dashboard-header h1 {
  max-width: 860px;
  margin: 0;
  font-size: 3.2rem;
  font-weight: 780;
  letter-spacing: 0;
  line-height: 1;
}

.header-copy {
  max-width: 760px;
  margin: 12px 0 0;
  color: #5e6a66;
  font-size: 1rem;
  line-height: 1.6;
}

.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.school-select {
  min-height: 42px;
  max-width: 260px;
  border: 1px solid #cad6d3;
  border-radius: 8px;
  padding: 0 12px;
  background: #fff;
  color: #18201d;
}

.dash-btn {
  display: inline-flex;
  min-height: 42px;
  align-items: center;
  justify-content: center;
  border: 1px solid #cad6d3;
  border-radius: 8px;
  padding: 0 16px;
  background: #fff;
  color: #18201d;
  cursor: pointer;
  font-weight: 800;
}

.dash-btn:hover {
  border-color: #1f6f5b;
  color: #1f6f5b;
}

.dash-btn.primary {
  border-color: #1f6f5b;
  background: #1f6f5b;
  color: #fff;
}

.dash-btn:disabled {
  cursor: progress;
  opacity: 0.7;
}

.eyebrow {
  margin: 0 0 8px;
  font-size: 0.76rem;
  letter-spacing: 0;
}

.kpi-grid,
.dashboard-grid,
.module-grid {
  display: grid;
  gap: 14px;
}

.kpi-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.dashboard-grid {
  grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.75fr);
}

.panel,
.kpi-card {
  border: 1px solid #dce5e2;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 18px 44px rgba(24, 32, 29, 0.06);
}

.panel {
  padding: 22px;
}

.kpi-card {
  display: grid;
  gap: 8px;
  min-height: 138px;
  padding: 18px;
}

.kpi-card span,
.kpi-card small,
.empty-copy,
.muted {
  color: #65716d;
}

.kpi-card strong {
  color: #18201d;
  font-size: 2rem;
  font-weight: 820;
  letter-spacing: 0;
}

.wide-panel {
  min-height: 340px;
}

.panel-header h2 {
  margin: 0;
  font-size: 1.35rem;
}

.panel-header > strong {
  border-radius: 8px;
  padding: 8px 10px;
  background: #fff2f4;
  color: #a33d4f;
}

.trend-chart {
  display: grid;
  gap: 12px;
  margin-top: 20px;
}

.trend-row {
  display: grid;
  grid-template-columns: 72px minmax(0, 1fr) 120px;
  gap: 12px;
  align-items: center;
}

.trend-row span,
.trend-row strong {
  color: #34423e;
  font-weight: 800;
}

.trend-row div {
  height: 12px;
  overflow: hidden;
  border-radius: 8px;
  background: #e8efed;
}

.trend-row i {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: #1f6f5b;
}

.attention-list {
  display: grid;
  gap: 12px;
  margin: 18px 0 0;
  padding: 0;
  list-style: none;
}

.attention-list li {
  display: flex;
  min-height: 58px;
  align-items: center;
  justify-content: space-between;
  border: 1px solid #e1e8e6;
  border-radius: 8px;
  padding: 12px;
  background: #f8fbfa;
}

.attention-list span {
  color: #5e6a66;
  font-weight: 760;
}

.attention-list strong {
  color: #a33d4f;
  font-size: 1.35rem;
}

.module-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-top: 18px;
}

.module-card {
  display: grid;
  gap: 8px;
  min-height: 104px;
  align-content: start;
  border: 1px solid #dce5e2;
  border-radius: 8px;
  padding: 14px;
  background: #f8fbfa;
  color: #18201d;
  cursor: pointer;
  text-align: left;
}

.module-card:hover {
  border-color: #1f6f5b;
  background: #eef8f4;
}

.module-card span {
  color: #65716d;
  line-height: 1.45;
}

.setup-panel {
  display: grid;
  align-content: start;
  gap: 16px;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.school-list {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.school-row {
  display: flex;
  min-height: 66px;
  align-items: center;
  justify-content: space-between;
  border: 1px solid #dce5e2;
  border-radius: 8px;
  padding: 12px;
  background: #fff;
  color: #18201d;
  cursor: pointer;
  text-align: left;
}

.school-row.selected,
.school-row:hover {
  border-color: #1f6f5b;
  background: #eef8f4;
}

.school-row span {
  display: grid;
  gap: 4px;
}

.school-row small {
  color: #65716d;
}

.school-row em {
  color: #1f6f5b;
  font-style: normal;
  font-weight: 900;
  text-transform: capitalize;
}

@media (max-width: 1100px) {
  .dashboard-shell,
  .dashboard-grid,
  .kpi-grid,
  .module-grid {
    grid-template-columns: 1fr;
  }

  .dashboard-sidebar {
    position: static;
    height: auto;
  }

  .module-nav {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .dashboard-header,
  .header-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .dashboard-header h1 {
    font-size: 2.25rem;
  }
}

@media (max-width: 680px) {
  .dashboard-main,
  .dashboard-sidebar {
    padding: 16px;
  }

  .module-nav,
  .form-row,
  .trend-row {
    grid-template-columns: 1fr;
  }

  .trend-row strong {
    justify-self: start;
  }
}
</style>
