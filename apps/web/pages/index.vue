<script lang="ts" setup>
import type { DashboardSummary } from '~/composables/useApi'

definePageMeta({
  layout: 'default',
})

interface DashboardSummaryResponse {
  data: DashboardSummary
}

interface DashboardStat {
  title: string
  value: string
  delta: string
  tone: string
  icon: string
  note: string
}

type DashboardAlert = {
  title: string
  severity: string
  time: string
}

type CollectionRow = {
  label: string
  value: number
}

type QueueRow = {
  name: string
  owner: string
  status: string
  updated: string
}

type PortfolioRow = {
  id: number
  name: string
  students: string
  staff: string
  attendance: string
  collection: string
}

const dashboardQuickActions = [
  { label: 'Mark attendance', to: '/attendance', icon: 'tabler-user-check' },
  { label: 'Publish marks', to: '/marks', icon: 'tabler-clipboard-check' },
  { label: 'Collect fees', to: '/finance/fees', icon: 'tabler-receipt-2' },
  { label: 'Issue notice', to: '/notices', icon: 'tabler-speakerphone' },
]

const session = useSession()
const dashboardLoading = ref(false)
const dashboardError = ref('')
const activeStats = ref<DashboardStat[]>([])
const activeCollections = ref<CollectionRow[]>([])
const activeAlerts = ref<DashboardAlert[]>([])
const reportQueue = ref<QueueRow[]>([])
const portfolioRows = ref<PortfolioRow[]>([])

function alertTone(severity: string) {
  return {
    Critical: 'error',
    Warning: 'warning',
    Info: 'default',
  }[severity] ?? 'default'
}

async function loadDashboard() {
  if (!session.schools.value.length) {
    activeStats.value = []
    activeCollections.value = []
    activeAlerts.value = []
    reportQueue.value = []
    portfolioRows.value = []
    return
  }

  dashboardLoading.value = true
  dashboardError.value = ''

  try {
    const results = await Promise.all(
      session.schools.value.map(async (school) => {
        const response = await useApiFetch<DashboardSummaryResponse>(`/schools/${school.id}/dashboard/summary`)
        return {
          school,
          summary: response.data,
        }
      }),
    )

    const totalStudents = results.reduce((sum, item) => sum + item.summary.admin.student_count, 0)
    const totalEmployees = results.reduce((sum, item) => sum + item.summary.admin.employee_count, 0)
    const feeThisMonth = results.reduce((sum, item) => sum + Number(item.summary.admin.fee_collection_this_month), 0)
    const feeLastMonth = results.reduce((sum, item) => sum + Number(item.summary.admin.fee_collection_last_month), 0)
    const pendingLeaves = results.reduce((sum, item) => sum + item.summary.admin.pending_leave_applications, 0)
    const attendanceAverage = results.length
      ? results.reduce((sum, item) => sum + Number(item.summary.admin.today_attendance_rate), 0) / results.length
      : 0
    const collectionDelta = feeLastMonth > 0 ? ((feeThisMonth - feeLastMonth) / feeLastMonth) * 100 : 0

    activeStats.value = [
      {
        title: 'Active students',
        value: totalStudents.toLocaleString(),
        delta: `+${results.length}`,
        tone: 'primary',
        icon: 'tabler-users',
        note: `Across ${results.length} schools`,
      },
      {
        title: 'Attendance today',
        value: `${attendanceAverage.toFixed(1)}%`,
        delta: `${attendanceAverage >= 90 ? '+' : ''}${(attendanceAverage - 90).toFixed(1)} pts`,
        tone: 'success',
        icon: 'tabler-user-check',
        note: `${totalEmployees.toLocaleString()} employees in directory`,
      },
      {
        title: 'Fee collection',
        value: `৳ ${Math.round(feeThisMonth).toLocaleString()}`,
        delta: `${collectionDelta >= 0 ? '+' : ''}${collectionDelta.toFixed(1)}%`,
        tone: 'warning',
        icon: 'tabler-receipt-2',
        note: 'Month to date',
      },
      {
        title: 'Pending leave approvals',
        value: pendingLeaves.toLocaleString(),
        delta: pendingLeaves > 0 ? 'Action needed' : 'Clear',
        tone: 'info',
        icon: 'tabler-clipboard-check',
        note: 'Awaiting decision',
      },
    ]

    const collectionByMonth = new Map<string, number>()
    results.forEach(({ summary }) => {
      summary.accountant.collection_trend.forEach((item) => {
        collectionByMonth.set(item.month, (collectionByMonth.get(item.month) ?? 0) + Number(item.total))
      })
    })
    const trendEntries = Array.from(collectionByMonth.entries()).slice(-5)
    const maxTotal = Math.max(...trendEntries.map(([, total]) => total), 1)
    activeCollections.value = trendEntries.map(([label, total]) => ({
      label,
      value: Math.max(8, Math.round((total / maxTotal) * 100)),
    }))

    activeAlerts.value = results.flatMap(({ school, summary }) =>
      summary.admin.attendance_concerns.slice(0, 2).map(item => ({
        title: item.student_enrollment?.student?.full_name
          ? `${item.student_enrollment.student.full_name} at ${school.name} dropped below threshold`
          : `${school.name} has an attendance concern`,
        severity: item.attendance_percentage < 60 ? 'Critical' : 'Warning',
        time: `${Number(item.attendance_percentage).toFixed(1)}% attendance`,
      })),
    ).slice(0, 6)

    reportQueue.value = results.map(({ school, summary }) => ({
      name: `${school.name} export pack`,
      owner: 'Reporting workspace',
      status: summary.teacher.pending_marks_entries > 0 ? 'Needs review' : 'Ready',
      updated: `${summary.admin.upcoming_exams.length} upcoming exams`,
    }))

    portfolioRows.value = results.map(({ school, summary }) => {
      const billed = Number(summary.admin.fee_collection_this_month) + summary.accountant.outstanding_by_class.reduce((sum, item) => sum + Number(item.outstanding), 0)
      const collection = billed > 0
        ? `${Math.round((Number(summary.admin.fee_collection_this_month) / billed) * 100)}%`
        : '0%'

      return {
        id: school.id,
        name: school.name,
        students: summary.admin.student_count.toLocaleString(),
        staff: summary.admin.employee_count.toLocaleString(),
        attendance: `${Number(summary.admin.today_attendance_rate).toFixed(1)}%`,
        collection,
      }
    })
  }
  catch {
    dashboardError.value = 'Live dashboard data is unavailable right now.'
    activeStats.value = []
    activeCollections.value = []
    activeAlerts.value = []
    reportQueue.value = []
    portfolioRows.value = []
  }
  finally {
    dashboardLoading.value = false
  }
}

watch(() => session.schools.value.map(school => school.id).join(','), loadDashboard, { immediate: true })
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
          prepend-icon="tabler-building-community"
          to="/schools"
        >
          Portfolio
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
            <div
              v-if="dashboardLoading"
              class="text-body-2 text-medium-emphasis"
            >
              Refreshing portfolio attention queue...
            </div>
            <div v-else class="school-alert-list">
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
                  :color="report.status === 'Ready' ? 'success' : report.status === 'Needs review' ? 'warning' : 'default'"
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
                <tr v-for="school in portfolioRows" :key="school.id">
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
