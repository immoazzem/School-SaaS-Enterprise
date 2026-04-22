<script lang="ts" setup>
import {
  dashboardAlerts,
  dashboardCollections,
  dashboardQuickActions,
  dashboardStats,
  reportQueue,
  schoolRows,
} from '@/utils/schoolDashboardData'

definePageMeta({
  layout: 'default',
})

const session = useSession()
const dashboardLoading = ref(false)
const dashboardError = ref('')
const liveCollections = ref<typeof dashboardCollections | null>(null)
const liveAlerts = ref<typeof dashboardAlerts | null>(null)

interface DashboardStat {
  title: string
  value: string
  delta: string
  tone: string
  icon: string
  note: string
}

interface DashboardAttendanceConcern {
  attendance_percentage: number
  student_enrollment?: {
    student?: {
      full_name?: string
    }
  }
}

interface DashboardCollectionTrendItem {
  month: string
  total: number
}

interface DashboardSummaryResponse {
  data: {
    admin: {
      student_count: number
      employee_count: number
      today_attendance_rate: number
      fee_collection_this_month: number
      fee_collection_last_month: number
      pending_leave_applications: number
      attendance_concerns: DashboardAttendanceConcern[]
    }
    accountant: {
      collection_trend: DashboardCollectionTrendItem[]
    }
  }
}

const liveStats = ref<DashboardStat[] | null>(null)

const activeStats = computed(() => liveStats.value ?? dashboardStats)
const activeCollections = computed(() => liveCollections.value ?? dashboardCollections)
const activeAlerts = computed(() => liveAlerts.value ?? dashboardAlerts)

async function loadDashboard() {
  if (!session.selectedSchool.value)
    return

  dashboardLoading.value = true
  dashboardError.value = ''

  try {
    const response = await useApiFetch<DashboardSummaryResponse>(`/schools/${session.selectedSchool.value.id}/dashboard/summary`)

    const admin = response.data.admin
    const accountant = response.data.accountant
    const collectionDelta = admin.fee_collection_last_month > 0
      ? ((admin.fee_collection_this_month - admin.fee_collection_last_month) / admin.fee_collection_last_month) * 100
      : 0

    liveStats.value = [
      {
        title: 'Active students',
        value: admin.student_count.toLocaleString(),
        delta: '+0.0%',
        tone: 'primary',
        icon: 'tabler-users',
        note: `Across ${session.schools.value.length} campuses`,
      },
      {
        title: 'Attendance today',
        value: `${admin.today_attendance_rate}%`,
        delta: '+0.0%',
        tone: 'success',
        icon: 'tabler-user-check',
        note: `${admin.employee_count.toLocaleString()} employees in directory`,
      },
      {
        title: 'Fee collection',
        value: `৳ ${Math.round(admin.fee_collection_this_month).toLocaleString()}`,
        delta: `${collectionDelta >= 0 ? '+' : ''}${collectionDelta.toFixed(1)}%`,
        tone: 'warning',
        icon: 'tabler-receipt-2',
        note: 'Month to date',
      },
      {
        title: 'Pending leave approvals',
        value: admin.pending_leave_applications.toLocaleString(),
        delta: '+0',
        tone: 'info',
        icon: 'tabler-clipboard-check',
        note: 'Awaiting decision',
      },
    ]

    const recentTrend = accountant.collection_trend.slice(-5)
    const maxTotal = Math.max(...recentTrend.map(item => item.total), 1)
    liveCollections.value = recentTrend.map(item => ({
      label: item.month,
      value: Math.round((item.total / maxTotal) * 100),
    }))

    liveAlerts.value = admin.attendance_concerns.slice(0, 4).map(item => ({
      title: item.student_enrollment?.student?.full_name
        ? `${item.student_enrollment.student.full_name} attendance dropped below threshold`
        : 'Attendance concern flagged',
      severity: item.attendance_percentage < 60 ? 'Critical' : 'Warning',
      time: `${item.attendance_percentage}% attendance`,
    }))
  }
  catch {
    dashboardError.value = 'Live dashboard data is unavailable right now. Showing the local operating snapshot instead.'
    liveStats.value = null
    liveCollections.value = null
    liveAlerts.value = null
  }
  finally {
    dashboardLoading.value = false
  }
}

watch(() => session.selectedSchool.value?.id, loadDashboard, { immediate: true })

const alertTone = (severity: string) => ({
  Critical: 'error',
  Warning: 'warning',
  Info: 'default',
}[severity] ?? 'default')
</script>

<template>
  <div class="school-dashboard">
    <SchoolPageHeader
      eyebrow="Operations"
      title="School command center"
      subtitle="A cleaner daily view of enrollment, academics, collection health, and leadership priorities."
    >
      <template #actions>
        <VBtn
          variant="outlined"
          color="default"
          prepend-icon="tabler-calendar"
        >
          April 2026
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          to="/notices"
        >
          Create notice
        </VBtn>
      </template>
    </SchoolPageHeader>

    <VAlert
      v-if="dashboardError"
      type="warning"
      variant="tonal"
      class="mb-4"
    >
      {{ dashboardError }}
    </VAlert>

    <VRow class="mb-2">
      <VCol
        v-for="stat in activeStats"
        :key="stat.title"
        cols="12"
        md="6"
        xl="3"
      >
        <SchoolMetricCard v-bind="stat" />
      </VCol>
    </VRow>

    <VRow class="mb-2">
      <VCol cols="12" xl="8">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <template #prepend>
              <VAvatar color="primary" variant="tonal" rounded="lg">
                <VIcon icon="tabler-bolt" />
              </VAvatar>
            </template>
            <VCardTitle class="font-weight-bold">
              Most-used workflows
            </VCardTitle>
            <VCardSubtitle>Jump straight into the tasks that run the school day.</VCardSubtitle>
          </VCardItem>

          <VCardText class="pt-2">
            <div class="school-action-grid">
              <NuxtLink
                v-for="action in dashboardQuickActions"
                :key="action.label"
                :to="action.to"
                class="school-action-grid__item"
              >
                <span class="school-action-grid__icon">
                  <VIcon :icon="action.icon" />
                </span>
                <span class="school-action-grid__label">{{ action.label }}</span>
                <VIcon icon="tabler-arrow-up-right" size="16" />
              </NuxtLink>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" xl="4">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Attention queue
            </VCardTitle>
            <VCardSubtitle>Issues that need leadership action today.</VCardSubtitle>
          </VCardItem>

          <VCardText class="pt-2">
            <div class="school-alert-list">
              <div
                v-for="alert in activeAlerts"
                :key="alert.title"
                class="school-alert-list__item"
              >
                <div>
                  <div class="font-weight-medium">
                    {{ alert.title }}
                  </div>
                  <div class="text-body-2 text-medium-emphasis">
                    {{ alert.time }}
                  </div>
                </div>
                <VChip
                  :color="alertTone(alert.severity)"
                  variant="tonal"
                  size="small"
                >
                  {{ alert.severity }}
                </VChip>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow class="mb-2">
      <VCol cols="12" xl="7">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Collections trend
            </VCardTitle>
            <VCardSubtitle>Monthly collection strength across the group.</VCardSubtitle>
          </VCardItem>

          <VCardText class="pt-2">
            <div class="school-bar-stack">
              <div
                v-for="row in activeCollections"
                :key="row.label"
                class="school-bar-stack__row"
              >
                <div class="school-bar-stack__label">
                  {{ row.label }}
                </div>
                <div class="school-bar-stack__track">
                  <div class="school-bar-stack__fill" :style="{ width: `${row.value}%` }" />
                </div>
                <div class="school-bar-stack__value">
                  {{ row.value }}%
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" xl="5">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Report queue
            </VCardTitle>
            <VCardSubtitle>What is ready for export and what still needs review.</VCardSubtitle>
          </VCardItem>

          <VCardText class="pt-2">
            <div class="school-alert-list">
              <div
                v-for="report in reportQueue"
                :key="report.name"
                class="school-alert-list__item"
              >
                <div>
                  <div class="font-weight-medium">
                    {{ report.name }}
                  </div>
                  <div class="text-body-2 text-medium-emphasis">
                    {{ report.owner }} · {{ report.updated }}
                  </div>
                </div>
                <VChip
                  :color="report.status === 'Ready' ? 'success' : report.status === 'Refreshing' ? 'warning' : 'default'"
                  variant="tonal"
                  size="small"
                >
                  {{ report.status }}
                </VChip>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12">
        <VCard class="school-signal-card">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Campus portfolio
            </VCardTitle>
            <VCardSubtitle>Compare school health at the portfolio level without leaving the dashboard.</VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <VTable class="school-data-table">
              <thead>
                <tr>
                  <th>School</th>
                  <th>Students</th>
                  <th>Staff</th>
                  <th>Attendance</th>
                  <th>Collection</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="school in schoolRows" :key="school.name">
                  <td class="font-weight-medium">{{ school.name }}</td>
                  <td>{{ school.students }}</td>
                  <td>{{ school.staff }}</td>
                  <td>{{ school.attendance }}</td>
                  <td>{{ school.collection }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
