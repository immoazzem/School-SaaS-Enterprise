<script setup lang="ts">
import type { DashboardSummary } from '~/composables/useApi'
import { hasAnyPermission } from '~/lib/accessControl'
import { schoolWorkspaceGroups, schoolWorkspaceModules } from '~/utils/schoolWorkspaceNav'

definePageMeta({
  layout: 'blank',
})

interface DashboardSummaryResponse {
  data: DashboardSummary
}

const route = useRoute()
const auth = useAuth()

const schoolId = computed(() => Number(route.params.schoolId))
const activeSchool = computed(() =>
  auth.schools.value.find(school => school.id === schoolId.value)
  ?? auth.selectedSchool.value
  ?? null,
)

const loading = ref(false)
const error = ref('')
const summary = ref<DashboardSummary | null>(null)

const metrics = computed(() => {
  const data = summary.value

  return [
    {
      title: 'Active students',
      value: data ? data.admin.student_count.toLocaleString() : '...',
      note: 'Current enrolled learner base',
      icon: 'tabler-users',
      tone: 'primary',
    },
    {
      title: 'Staff',
      value: data ? data.admin.employee_count.toLocaleString() : '...',
      note: 'Employees and academic team',
      icon: 'tabler-briefcase',
      tone: 'info',
    },
    {
      title: 'Attendance today',
      value: data ? `${Number(data.admin.today_attendance_rate).toFixed(1)}%` : '...',
      note: 'Daily operating signal',
      icon: 'tabler-user-check',
      tone: 'success',
    },
    {
      title: 'Collections',
      value: data ? `৳ ${Math.round(Number(data.admin.fee_collection_this_month)).toLocaleString()}` : '...',
      note: 'Month-to-date receipts',
      icon: 'tabler-receipt-2',
      tone: 'warning',
    },
  ]
})

const groupedQuickLinks = computed(() =>
  schoolWorkspaceGroups.map(group => ({
    ...group,
    items: schoolWorkspaceModules
      .filter(module => module.route && module.tone === group.tone)
      .filter(module => hasAnyPermission(activeSchool.value, module.permissions))
      .map(module => ({
        ...module,
        to: `/schools/${schoolId.value}/${module.route}`,
      })),
  })).filter(group => group.items.length),
)

const riskItems = computed(() => {
  const data = summary.value

  if (!data)
    return []

  return [
    {
      label: 'Unpaid invoices',
      value: data.accountant.unpaid_invoices.toLocaleString(),
      to: `/schools/${schoolId.value}/invoice-payments`,
    },
    {
      label: 'Pending salaries',
      value: data.accountant.pending_salaries.toLocaleString(),
      to: `/schools/${schoolId.value}/staff-operations`,
    },
    {
      label: 'Pending marks',
      value: data.teacher.pending_marks_entries.toLocaleString(),
      to: `/schools/${schoolId.value}/marks`,
    },
    {
      label: 'Upcoming exams',
      value: data.admin.upcoming_exams.length.toLocaleString(),
      to: `/schools/${schoolId.value}/exams`,
    },
  ]
})

async function loadDashboard() {
  if (!schoolId.value)
    return

  loading.value = true
  error.value = ''

  try {
    auth.selectSchool(schoolId.value)
    const response = await useApiFetch<DashboardSummaryResponse>(`/schools/${schoolId.value}/dashboard/summary`)
    summary.value = response.data
  }
  catch {
    error.value = 'School dashboard data is unavailable right now.'
    summary.value = null
  }
  finally {
    loading.value = false
  }
}

watch(schoolId, loadDashboard, { immediate: true })
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail :school-id="schoolId" />
    </template>

    <section class="workspace-dashboard">
      <div class="workspace-header">
        <div>
          <p class="workspace-kicker">School dashboard</p>
          <h1>{{ activeSchool?.name || 'School workspace' }}</h1>
          <p class="muted">
            Role-aware launchpad for academics, people, finance, reports, and daily operations.
          </p>
        </div>

        <div class="header-actions">
          <VBtn variant="outlined" color="default" to="/dashboard">
            Role dashboard
          </VBtn>
          <VBtn color="primary" :to="`/schools/${schoolId}/students`">
            Open students
          </VBtn>
        </div>
      </div>

      <VAlert v-if="error" type="warning" variant="tonal">
        {{ error }}
      </VAlert>

      <VProgressLinear v-if="loading" color="primary" indeterminate rounded />

      <VRow>
        <VCol v-for="metric in metrics" :key="metric.title" cols="12" md="6" xl="3">
          <SchoolMetricCard v-bind="metric" />
        </VCol>
      </VRow>

      <VRow>
        <VCol cols="12" xl="8">
          <VCard class="school-signal-card h-100">
            <VCardItem>
              <VCardTitle class="font-weight-bold">Authorized workspaces</VCardTitle>
              <VCardSubtitle>Only modules available to this user are listed.</VCardSubtitle>
            </VCardItem>
            <VCardText>
              <div class="school-dashboard-groups">
                <section v-for="group in groupedQuickLinks" :key="group.title" class="school-dashboard-group">
                  <h2>{{ group.title }}</h2>
                  <div class="school-action-grid">
                    <NuxtLink
                      v-for="item in group.items"
                      :key="item.route"
                      :to="item.to"
                      class="school-action-grid__item"
                    >
                      <span class="school-action-grid__icon">
                        <VIcon icon="tabler-arrow-up-right" />
                      </span>
                      <span class="school-action-grid__label">{{ item.label }}</span>
                    </NuxtLink>
                  </div>
                </section>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" xl="4">
          <VCard class="school-signal-card h-100">
            <VCardItem>
              <VCardTitle class="font-weight-bold">Operating queue</VCardTitle>
              <VCardSubtitle>Items that usually need leadership attention.</VCardSubtitle>
            </VCardItem>
            <VCardText>
              <div class="school-alert-list">
                <NuxtLink
                  v-for="item in riskItems"
                  :key="item.label"
                  :to="item.to"
                  class="school-alert-list__item text-decoration-none"
                >
                  <div>
                    <div class="font-weight-medium">{{ item.label }}</div>
                    <div class="text-body-2 text-medium-emphasis">Open the related workspace</div>
                  </div>
                  <strong>{{ item.value }}</strong>
                </NuxtLink>

                <div v-if="!riskItems.length && !loading" class="text-body-2 text-medium-emphasis">
                  No dashboard signals available yet.
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </section>
  </SchoolWorkspaceTemplate>
</template>
