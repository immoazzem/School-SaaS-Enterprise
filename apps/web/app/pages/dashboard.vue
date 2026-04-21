<script setup lang="ts">
import type { DashboardSummary } from '~/composables/useApi'
import { schoolWorkspaceGroups, schoolWorkspaceModules } from '~/utils/schoolWorkspaceNav'

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

const groupedModules = computed(() =>
  schoolWorkspaceGroups.map((group) => ({
    title: group.title,
    tone: group.tone,
    items: schoolWorkspaceModules
      .filter((item) => item.tone === group.tone && canOpen(item)),
  })),
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
    eyebrow: 'Enrolment',
    trend: '+2.4%',
  },
  {
    label: 'Employees',
    value: dashboardSummary.value?.admin.employee_count ?? 0,
    detail: 'Teaching and operations team',
    eyebrow: 'Staff',
    trend: '+1.1%',
  },
  {
    label: `${dashboardSummary.value?.admin.today_attendance_rate ?? 0}%`,
    value: null,
    detail: 'Today across recorded students',
    eyebrow: 'Attendance',
    isRate: true,
    trend: '+0.5%',
  },
  {
    label: formatMoney(dashboardSummary.value?.admin.fee_collection_this_month ?? 0),
    value: null,
    detail: 'This month',
    eyebrow: 'Collection',
    isMoney: true,
    trend: '+12.5%',
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
  <SchoolWorkspaceTemplate content-class="pb-24">
    <template #navigation>
      <SchoolWorkspaceRail :school-id="selectedSchool?.id ?? null" aria-label="Main navigation" />
    </template>

      <div class="workspace-content-inner">
        <header class="workspace-header">
          <div class="flex flex-col">
            <p class="eyebrow">Command Center</p>
            <h1>{{ selectedSchool?.name || 'No tenant selected' }}</h1>
            <p v-if="selectedSchool" class="mt-1.5 flex items-center gap-2 font-medium text-slate-500">
              <span class="inline-block h-2 w-2 rounded-full bg-emerald-400" />
              {{ selectedSchool.slug }} · {{ selectedSchool.roles?.[0]?.name || 'Member' }}
            </p>
          </div>

          <div class="header-actions">
            <LocaleSwitcher />
            <span v-if="auth.user?.email" class="hidden cursor-default items-center rounded-md bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 sm:inline-flex">
              <svg class="mr-1.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
              {{ auth.user?.email }}
            </span>
            <button class="button secondary compact" type="button" @click="auth.logout()">
              {{ $t('actions.signOut') }}
            </button>
          </div>
        </header>

        <p v-if="error" class="error fade-in">{{ error }}</p>
        <p v-if="success" class="success fade-in">{{ success }}</p>
        
        <div v-if="loading || summaryLoading" class="flex items-center gap-3 p-4 bg-slate-100 rounded-xl max-w-sm text-slate-500 font-medium text-sm animate-pulse">
          <svg class="animate-spin h-5 w-5 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          Loading workspace data…
        </div>

        <section v-if="selectedSchool" class="summary-grid fade-in" aria-label="School metrics">
          <article v-for="metric in kpis" :key="metric.eyebrow" class="summary-item">
            <span>{{ metric.eyebrow }}</span>
            <div class="mt-1 flex items-baseline gap-2">
              <strong>
                {{ metric.isRate || metric.isMoney ? metric.label : metric.value }}
              </strong>
              <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                <svg class="mr-0.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                {{ metric.trend }}
              </span>
            </div>
            <p class="m-0 mt-2 text-sm text-slate-500">{{ metric.detail }}</p>
          </article>
        </section>

        <section v-if="selectedSchool" class="grid grid-cols-1 lg:grid-cols-[1.5fr_1fr] gap-5 fade-in" style="animation-delay: 50ms;">
          <article class="surface flex flex-col gap-4 p-5 lg:p-6">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="eyebrow">Operating picture</p>
                <h2 class="text-xl font-display font-semibold text-slate-900 tracking-tight m-0">Collections trend</h2>
              </div>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-rose-50 text-rose-700 text-xs font-bold">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                {{ dashboardSummary?.accountant.unpaid_invoices ?? 0 }} unpaid
              </span>
            </div>

            <div v-if="collectionTrend.length" class="flex flex-col gap-3 mt-2" aria-label="Collection trend">
              <div v-for="row in collectionTrend" :key="row.month" class="grid grid-cols-[60px_1fr_90px] md:grid-cols-[80px_1fr_110px] gap-3 items-center">
                <span class="text-sm font-semibold text-slate-700">{{ row.month }}</span>
                <div class="h-8 md:h-10 w-full overflow-hidden rounded-r-lg flex items-center">
                  <div class="h-full bg-brand-500 rounded-r-lg transition-all duration-500 hover:bg-brand-400" :style="{ width: `${Math.max((Number(row.total) / trendMax) * 100, 2)}%` }"></div>
                </div>
                <strong class="text-sm font-bold text-slate-900 text-right">{{ formatMoney(row.total) }}</strong>
              </div>
            </div>
            <p v-else class="text-sm text-slate-500 font-medium py-8 text-center border-2 border-dashed border-slate-200 rounded-xl mt-2">No collection trend data available yet.</p>
          </article>

          <article class="surface flex flex-col gap-4 p-5 lg:p-6">
            <div>
              <p class="eyebrow">Today</p>
              <h2 class="text-xl font-display font-semibold text-slate-900 tracking-tight m-0">Attention Required</h2>
            </div>

            <ul class="flex flex-col gap-3 m-0 p-0 list-none mt-2">
              <li class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-brand-200 transition-colors shadow-sm">
                <span class="text-sm font-semibold text-slate-700">Pending leave</span>
                <strong class="text-2xl font-display font-bold text-brand-600">{{ dashboardSummary?.admin.pending_leave_applications ?? 0 }}</strong>
              </li>
              <li class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-brand-200 transition-colors shadow-sm">
                <span class="text-sm font-semibold text-slate-700">Pending marks</span>
                <strong class="text-2xl font-display font-bold text-brand-600">{{ dashboardSummary?.teacher.pending_marks_entries ?? 0 }}</strong>
              </li>
              <li class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-brand-200 transition-colors shadow-sm">
                <span class="text-sm font-semibold text-slate-700">Pending salaries</span>
                <strong class="text-2xl font-display font-bold text-brand-600">{{ dashboardSummary?.accountant.pending_salaries ?? 0 }}</strong>
              </li>
            </ul>
          </article>
        </section>

        <section v-if="selectedSchool" class="flex flex-col gap-8 fade-in mt-2" style="animation-delay: 100ms;">
          <div v-for="group in groupedModules.filter(g => g.items.length)" :key="group.title" class="flex flex-col gap-3">
            <h3 class="border-b border-slate-200 pb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ group.title }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              <button
                v-for="item in group.items"
                :key="item.label"
                class="module-tile"
                type="button"
                @click="openModule(item)"
              >
                <div class="mb-1 flex items-center gap-3">
                  <div class="flex h-8 w-8 items-center justify-center rounded-md bg-brand-50 text-brand-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                  </div>
                  <strong>{{ item.label }}</strong>
                </div>
                <span class="pl-11">{{ item.description }}</span>
              </button>
            </div>
          </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-[1.5fr_1fr] gap-5 mt-8 border-t border-slate-200 pt-8">
          <form class="surface flex flex-col gap-5 p-5 lg:p-6" @submit.prevent="createSchool">
            <div>
              <p class="eyebrow">Tenants</p>
              <h2 class="text-xl font-display font-semibold text-slate-900 tracking-tight m-0">Create a New School</h2>
              <p class="text-sm text-slate-500 mt-1">Deploy a new tenant instantly and manage it from this workspace.</p>
            </div>

            <div class="field mt-2">
              <label for="school-name">School name</label>
              <input id="school-name" v-model="schoolForm.name" autocomplete="organization" required type="text" placeholder="Example International School" />
            </div>

            <div class="field">
              <label for="school-slug">Slug Identifier</label>
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

            <div class="mt-2 text-right">
              <button class="button md:w-auto w-full" type="submit" :disabled="creatingSchool">
                <span v-if="creatingSchool" class="flex items-center gap-2">
                  <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                  Deploying...
                </span>
                <span v-else>Deploy School tenant</span>
              </button>
            </div>
          </form>

          <article class="surface flex flex-col gap-5 p-5 lg:p-6">
            <div>
              <p class="eyebrow">Tenants</p>
              <h2 class="text-xl font-display font-semibold text-slate-900 tracking-tight m-0">Your Access</h2>
            </div>

            <div v-if="auth.schools.value.length" class="flex flex-col gap-3 mt-2">
              <button
                v-for="school in auth.schools.value"
                :key="school.id"
                class="flex items-center justify-between p-4 border border-slate-200 rounded-xl text-left transition-all duration-150"
                :class="school.id === auth.selectedSchoolId.value ? 'bg-brand-50 border-brand-200 shadow-sm' : 'bg-white hover:bg-slate-50 hover:border-slate-300'"
                type="button"
                @click="auth.selectSchool(school.id)"
              >
                <span class="flex flex-col gap-1">
                  <strong class="text-sm font-bold text-slate-900 leading-none">{{ school.name }}</strong>
                  <small class="text-xs text-slate-500">{{ school.slug }} / {{ school.roles?.[0]?.name || 'Member' }}</small>
                </span>
                <span class="status-pill" :class="school.status">{{ school.status }}</span>
              </button>
            </div>

            <p v-else class="text-sm text-slate-500 font-medium py-10 text-center border-2 border-dashed border-slate-200 rounded-xl mt-2">No active access. Create a tenant.</p>
          </article>
        </section>
      </div>
  </SchoolWorkspaceTemplate>
</template>

