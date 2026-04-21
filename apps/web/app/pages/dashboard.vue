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

const quickActions = computed(() => [
  schoolWorkspaceModules.find((module) => module.route === 'students'),
  schoolWorkspaceModules.find((module) => module.route === 'attendance'),
  schoolWorkspaceModules.find((module) => module.route === 'finance'),
  schoolWorkspaceModules.find((module) => module.route === 'reports'),
  schoolWorkspaceModules.find((module) => module.route === 'timetable'),
  schoolWorkspaceModules.find((module) => module.route === 'employees'),
].filter((module): module is NonNullable<typeof module> => Boolean(module) && canOpen(module)))

const attentionItems = computed(() => [
  {
    label: 'Pending leave',
    value: dashboardSummary.value?.admin.pending_leave_applications ?? 0,
    tone: 'amber',
    detail: 'Needs school review',
  },
  {
    label: 'Pending marks',
    value: dashboardSummary.value?.teacher.pending_marks_entries ?? 0,
    tone: 'blue',
    detail: 'Teacher work queue',
  },
  {
    label: 'Pending salaries',
    value: dashboardSummary.value?.accountant.pending_salaries ?? 0,
    tone: 'rose',
    detail: 'Finance follow-up',
  },
])

const accessSummary = computed(() => [
  {
    label: 'Schools you can operate',
    value: auth.schools.value.length,
  },
  {
    label: 'Workspace modules available',
    value: groupedModules.value.reduce((count, group) => count + group.items.length, 0),
  },
])

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

      <div v-if="loading || summaryLoading" class="flex max-w-sm items-center gap-3 rounded-lg bg-slate-100 p-4 text-sm font-medium text-slate-500 animate-pulse">
        <svg class="h-5 w-5 animate-spin text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
        Loading workspace data…
      </div>

      <section class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,0.9fr)]">
        <article class="surface flex flex-col gap-5 p-5 lg:p-6">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-2xl">
              <p class="eyebrow">Workspace overview</p>
              <h2 class="m-0 text-2xl font-display font-semibold tracking-tight text-slate-900">Run the school from one compact control surface.</h2>
              <p class="mt-2 text-sm text-slate-500">
                Use the launchpad for high-frequency tasks, then move through the full workspace map as needed.
              </p>
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:min-w-[320px]">
              <div v-for="item in accessSummary" :key="item.label" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <span class="block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ item.label }}</span>
                <strong class="mt-1 block text-2xl font-display font-semibold tracking-tight text-slate-900">{{ item.value }}</strong>
              </div>
            </div>
          </div>

          <div v-if="selectedSchool" class="summary-grid fade-in" aria-label="School metrics">
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
          </div>
        </article>

        <article class="surface flex flex-col gap-4 p-5 lg:p-6">
          <div>
            <p class="eyebrow">Launchpad</p>
            <h2 class="m-0 text-xl font-display font-semibold tracking-tight text-slate-900">Most-used workflows</h2>
          </div>

          <div v-if="selectedSchool && quickActions.length" class="grid grid-cols-1 gap-2">
            <button
              v-for="module in quickActions"
              :key="module.route"
              class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3 text-left transition-colors hover:bg-slate-50 hover:border-slate-300"
              type="button"
              @click="openModule(module)"
            >
              <span class="flex flex-col gap-1">
                <strong class="text-sm font-semibold text-slate-900">{{ module.label }}</strong>
                <span class="text-sm text-slate-500">{{ module.description }}</span>
              </span>
              <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </button>
          </div>

          <div v-else class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
            Select or create a school to unlock the operational modules.
          </div>
        </article>
      </section>

      <section v-if="selectedSchool" class="grid grid-cols-1 gap-5 fade-in lg:grid-cols-[1.5fr_1fr]" style="animation-delay: 50ms;">
        <article class="surface flex flex-col gap-4 p-5 lg:p-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="eyebrow">Operating picture</p>
              <h2 class="m-0 text-xl font-display font-semibold tracking-tight text-slate-900">Collections trend</h2>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-rose-700">
              <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
              {{ dashboardSummary?.accountant.unpaid_invoices ?? 0 }} unpaid
            </span>
          </div>

          <div v-if="collectionTrend.length" class="mt-2 flex flex-col gap-3" aria-label="Collection trend">
            <div v-for="row in collectionTrend" :key="row.month" class="grid grid-cols-[60px_1fr_90px] items-center gap-3 md:grid-cols-[80px_1fr_110px]">
              <span class="text-sm font-semibold text-slate-700">{{ row.month }}</span>
              <div class="flex h-8 w-full items-center overflow-hidden rounded-r-lg md:h-10">
                <div class="h-full rounded-r-lg bg-brand-500 transition-all duration-500 hover:bg-brand-400" :style="{ width: `${Math.max((Number(row.total) / trendMax) * 100, 2)}%` }"></div>
              </div>
              <strong class="text-right text-sm font-bold text-slate-900">{{ formatMoney(row.total) }}</strong>
            </div>
          </div>
          <p v-else class="mt-2 rounded-lg border-2 border-dashed border-slate-200 py-8 text-center text-sm font-medium text-slate-500">No collection trend data available yet.</p>
        </article>

        <article class="surface flex flex-col gap-4 p-5 lg:p-6">
          <div>
            <p class="eyebrow">Today</p>
            <h2 class="m-0 text-xl font-display font-semibold tracking-tight text-slate-900">Attention required</h2>
          </div>

          <ul class="mt-2 m-0 flex list-none flex-col gap-3 p-0">
            <li
              v-for="item in attentionItems"
              :key="item.label"
              class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3"
            >
              <span class="flex flex-col gap-1">
                <span class="text-sm font-semibold text-slate-700">{{ item.label }}</span>
                <span class="text-xs text-slate-500">{{ item.detail }}</span>
              </span>
              <strong
                class="text-2xl font-display font-semibold"
                :class="{
                  'text-amber-600': item.tone === 'amber',
                  'text-brand-600': item.tone === 'blue',
                  'text-rose-600': item.tone === 'rose',
                }"
              >
                {{ item.value }}
              </strong>
            </li>
          </ul>
        </article>
      </section>

      <section v-if="selectedSchool" class="fade-in" style="animation-delay: 100ms;">
        <div class="surface flex flex-col gap-6 p-5 lg:p-6">
          <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="eyebrow">Workspace map</p>
                <h2 class="m-0 text-xl font-display font-semibold tracking-tight text-slate-900">Operate by domain</h2>
                <p class="mt-1 text-sm text-slate-500">Each area uses the same compact shell, forms, and records layout.</p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div v-for="group in groupedModules.filter(g => g.items.length)" :key="group.title" class="flex flex-col gap-3">
              <h3 class="border-b border-slate-200 pb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ group.title }}</h3>
              <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
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
          </div>
        </div>
      </section>

      <section class="mt-8 grid grid-cols-1 gap-5 border-t border-slate-200 pt-8 lg:grid-cols-[1.5fr_1fr]">
        <form class="surface flex flex-col gap-5 p-5 lg:p-6" @submit.prevent="createSchool">
          <div>
            <p class="eyebrow">Tenants</p>
            <h2 class="m-0 text-xl font-display font-semibold tracking-tight text-slate-900">Create a new school</h2>
            <p class="mt-1 text-sm text-slate-500">Provision a new tenant and hand it straight to the shared workspace shell.</p>
          </div>

          <div class="field mt-2">
            <label for="school-name">School name</label>
            <input id="school-name" v-model="schoolForm.name" autocomplete="organization" required type="text" placeholder="Example International School" />
          </div>

          <div class="field">
            <label for="school-slug">Slug identifier</label>
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
            <button class="button w-full md:w-auto" type="submit" :disabled="creatingSchool">
              <span v-if="creatingSchool" class="flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                Provisioning…
              </span>
              <span v-else>Deploy school tenant</span>
            </button>
          </div>
        </form>

        <article class="surface flex flex-col gap-5 p-5 lg:p-6">
          <div>
            <p class="eyebrow">Tenants</p>
            <h2 class="m-0 text-xl font-display font-semibold tracking-tight text-slate-900">Your access</h2>
          </div>

          <div v-if="auth.schools.value.length" class="mt-2 flex flex-col gap-3">
            <button
              v-for="school in auth.schools.value"
              :key="school.id"
              class="flex items-center justify-between rounded-lg border px-4 py-3 text-left transition-colors duration-150"
              :class="school.id === auth.selectedSchoolId.value ? 'border-brand-200 bg-brand-50' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
              type="button"
              @click="auth.selectSchool(school.id)"
            >
              <span class="flex flex-col gap-1">
                <strong class="text-sm font-semibold leading-none text-slate-900">{{ school.name }}</strong>
                <small class="text-xs text-slate-500">{{ school.slug }} / {{ school.roles?.[0]?.name || 'Member' }}</small>
              </span>
              <span class="status-pill" :class="school.status">{{ school.status }}</span>
            </button>
          </div>

          <p v-else class="mt-2 rounded-lg border-2 border-dashed border-slate-200 py-10 text-center text-sm font-medium text-slate-500">No active access. Create a tenant.</p>
        </article>
      </section>

      <section v-if="!selectedSchool && !loading" class="surface flex flex-col gap-4 p-5 lg:p-6">
        <div>
          <p class="eyebrow">Getting started</p>
          <h2 class="m-0 text-xl font-display font-semibold tracking-tight text-slate-900">No school selected yet</h2>
          <p class="mt-1 text-sm text-slate-500">Create a school or choose one from your access list to unlock the operational modules.</p>
        </div>
      </section>
    </div>
  </SchoolWorkspaceTemplate>
</template>

