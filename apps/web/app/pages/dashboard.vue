<script setup lang="ts">
import type { DashboardSummary } from '~/composables/useApi'
import { schoolWorkspaceModules } from '~/utils/schoolWorkspaceNav'

interface ItemResponse<T> {
  data: T
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

const featuredModules = computed(() =>
  schoolWorkspaceModules.filter((item) => canOpen(item)).slice(0, 8),
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

function canOpen(module: { permissions: string[] }) {
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

async function openModule(module: { label: string, route: string, permissions: string[] }) {
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
    <SchoolWorkspaceRail :school-id="selectedSchool?.id ?? null" aria-label="Main navigation" />

    <section class="dashboard-main">
      <header class="dashboard-header">
        <div>
          <p class="eyebrow">Command center</p>
          <h1>{{ selectedSchool?.name || 'No school selected' }}</h1>
          <p class="header-copy">Five years of academics, attendance, finance, staff, exams, documents, and promotions are ready for local QA.</p>
        </div>

        <div class="header-actions">
          <LocaleSwitcher />
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

.panel h2,
.dashboard-header h1 {
  color: #18201d;
}

.eyebrow {
  color: #a33d4f;
  font-weight: 900;
  text-transform: uppercase;
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
  .dashboard-main {
    padding: 16px;
  }

  .form-row,
  .trend-row {
    grid-template-columns: 1fr;
  }

  .trend-row strong {
    justify-self: start;
  }
}
</style>
