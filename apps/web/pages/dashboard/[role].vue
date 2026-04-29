<script setup lang="ts">
import type { DashboardSummary } from '~/composables/useApi'
import {
  dashboardPathForSchool,
  hasAnyPermission,
  resolveRoleDashboard,
  roleDashboardProfiles,
  type RoleDashboardKey,
} from '~/lib/accessControl'

definePageMeta({
  layout: 'default',
})

interface DashboardSummaryResponse {
  data: DashboardSummary
}

type DashboardStat = {
  title: string
  value: string
  note: string
  icon: string
  tone: string
}

const route = useRoute()
const session = useSession()
const loading = ref(false)
const error = ref('')
const summary = ref<DashboardSummary | null>(null)

const activeSchool = computed(() => session.selectedSchool.value)
const resolvedRole = computed(() => resolveRoleDashboard(activeSchool.value))
const requestedRole = computed(() => String(route.params.role || '') as RoleDashboardKey)
const profile = computed(() => roleDashboardProfiles[resolvedRole.value])

const userEmail = computed(() => session.user.value?.email || '')
const roleNames = computed(() => activeSchool.value?.roles?.map(role => role.name).join(', ') || 'No role assigned')
const visibleQuickLinks = computed(() =>
  profile.value.quickLinks.filter(link => !link.permission || hasAnyPermission(activeSchool.value, [link.permission])),
)

const stats = computed<DashboardStat[]>(() => {
  const data = summary.value
  if (!data) {
    return [
      { title: 'School context', value: activeSchool.value?.name || 'No school', note: roleNames.value, icon: 'tabler-building-school', tone: 'primary' },
      { title: 'Dashboard profile', value: profile.value.label, note: profile.value.badge, icon: 'tabler-id-badge-2', tone: 'info' },
    ]
  }

  if (resolvedRole.value === 'accountant') {
    return [
      { title: 'Collected this month', value: `৳ ${Math.round(Number(data.admin.fee_collection_this_month)).toLocaleString()}`, note: 'Month to date', icon: 'tabler-cash-banknote', tone: 'success' },
      { title: 'Unpaid invoices', value: data.accountant.unpaid_invoices.toLocaleString(), note: 'Needs collection follow-up', icon: 'tabler-receipt-off', tone: 'warning' },
      { title: 'Pending salaries', value: data.accountant.pending_salaries.toLocaleString(), note: 'Payroll queue', icon: 'tabler-wallet', tone: 'info' },
      { title: 'Students billed', value: data.admin.student_count.toLocaleString(), note: activeSchool.value?.name || '', icon: 'tabler-users', tone: 'primary' },
    ]
  }

  if (resolvedRole.value === 'teacher') {
    return [
      { title: 'Pending marks', value: data.teacher.pending_marks_entries.toLocaleString(), note: 'Marks awaiting entry or review', icon: 'tabler-clipboard-check', tone: 'warning' },
      { title: 'Upcoming exams', value: data.teacher.upcoming_exams.length.toLocaleString(), note: 'Scheduled assessment work', icon: 'tabler-calendar-event', tone: 'info' },
      { title: 'Attendance today', value: `${Number(data.admin.today_attendance_rate).toFixed(1)}%`, note: 'Classroom attendance signal', icon: 'tabler-user-check', tone: 'success' },
      { title: 'Active students', value: data.admin.student_count.toLocaleString(), note: activeSchool.value?.name || '', icon: 'tabler-users', tone: 'primary' },
    ]
  }

  if (resolvedRole.value === 'student' || resolvedRole.value === 'parent') {
    return [
      { title: 'Attendance today', value: `${Number(data.admin.today_attendance_rate).toFixed(1)}%`, note: 'School attendance signal', icon: 'tabler-user-check', tone: 'success' },
      { title: 'Upcoming exams', value: data.teacher.upcoming_exams.length.toLocaleString(), note: 'Published school schedule', icon: 'tabler-calendar-event', tone: 'info' },
      { title: 'Invoices', value: data.accountant.unpaid_invoices.toLocaleString(), note: 'Portal-visible billing signal', icon: 'tabler-receipt', tone: 'warning' },
      { title: 'Reports', value: 'Open', note: 'Result and attendance view', icon: 'tabler-file-analytics', tone: 'primary' },
    ]
  }

  if (resolvedRole.value === 'auditor') {
    return [
      { title: 'Audit events', value: data.auditor.recent_audit_logs.length.toLocaleString(), note: 'Latest operational evidence', icon: 'tabler-shield-check', tone: 'primary' },
      { title: 'Reports', value: 'Read-only', note: 'No mutation controls', icon: 'tabler-file-analytics', tone: 'info' },
      { title: 'Schools', value: activeSchool.value ? '1' : '0', note: activeSchool.value?.name || '', icon: 'tabler-building-school', tone: 'success' },
    ]
  }

  return [
    { title: 'Active students', value: data.admin.student_count.toLocaleString(), note: 'Enrollment footprint', icon: 'tabler-users', tone: 'primary' },
    { title: 'Staff', value: data.admin.employee_count.toLocaleString(), note: 'Employees and teachers', icon: 'tabler-briefcase', tone: 'info' },
    { title: 'Attendance today', value: `${Number(data.admin.today_attendance_rate).toFixed(1)}%`, note: 'Operational health', icon: 'tabler-user-check', tone: 'success' },
    { title: 'Collections', value: `৳ ${Math.round(Number(data.admin.fee_collection_this_month)).toLocaleString()}`, note: 'Month to date', icon: 'tabler-receipt-2', tone: 'warning' },
  ]
})

async function loadDashboard() {
  if (!activeSchool.value)
    return

  loading.value = true
  error.value = ''

  try {
    const response = await useApiFetch<DashboardSummaryResponse>(`/schools/${activeSchool.value.id}/dashboard/summary`)
    summary.value = response.data
  }
  catch {
    error.value = 'Dashboard data is unavailable right now.'
    summary.value = null
  }
  finally {
    loading.value = false
  }
}

watch(
  () => [activeSchool.value?.id, route.params.role],
  async () => {
    if (requestedRole.value !== resolvedRole.value) {
      await navigateTo(dashboardPathForSchool(activeSchool.value), { replace: true })
      return
    }

    await loadDashboard()
  },
  { immediate: true },
)
</script>

<template>
  <section class="role-dashboard">
    <SchoolPageHeader
      eyebrow="Role dashboard"
      :title="profile.title"
      :subtitle="profile.subtitle"
    >
      <template #actions>
        <VChip color="primary" variant="tonal" rounded="lg">
          {{ profile.badge }}
        </VChip>
      </template>
    </SchoolPageHeader>

    <VAlert v-if="error" type="warning" variant="tonal" class="mb-4">
      {{ error }}
    </VAlert>

    <VRow class="mb-2">
      <VCol v-for="stat in stats" :key="stat.title" cols="12" md="6" xl="3">
        <SchoolMetricCard v-bind="stat" />
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" xl="7">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Authorized shortcuts
            </VCardTitle>
            <VCardSubtitle>Only workflows available to this role are listed here.</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <div v-if="loading" class="text-body-2 text-medium-emphasis">
              Refreshing role dashboard...
            </div>
            <div v-else class="school-action-grid">
              <NuxtLink
                v-for="link in visibleQuickLinks"
                :key="link.label"
                :to="link.to"
                class="school-action-grid__item"
              >
                <span class="school-action-grid__icon">
                  <VIcon icon="tabler-arrow-up-right" />
                </span>
                <span class="school-action-grid__label">{{ link.label }}</span>
              </NuxtLink>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" xl="5">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Access profile
            </VCardTitle>
            <VCardSubtitle>{{ activeSchool?.name || 'No school selected' }}</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <div class="school-alert-list">
              <div class="school-alert-list__item">
                <div>
                  <div class="font-weight-medium">Signed in as</div>
                  <div class="text-body-2 text-medium-emphasis">{{ userEmail }}</div>
                </div>
              </div>
              <div class="school-alert-list__item">
                <div>
                  <div class="font-weight-medium">Role</div>
                  <div class="text-body-2 text-medium-emphasis">{{ roleNames }}</div>
                </div>
              </div>
              <div class="school-alert-list__item">
                <div>
                  <div class="font-weight-medium">Permission count</div>
                  <div class="text-body-2 text-medium-emphasis">{{ activeSchool?.permissions?.length || 0 }} active permissions</div>
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>
