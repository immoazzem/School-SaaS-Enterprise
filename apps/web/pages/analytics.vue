<script setup lang="ts">
import type { DashboardSummary } from '~/composables/useApi'

definePageMeta({
  layout: 'default',
})

interface DashboardSummaryResponse {
  data: DashboardSummary
}

interface SignalItem {
  label: string
  value: string
  note: string
}

interface CohortItem {
  title: string
  trend: string
  note: string
  tone: string
}

interface TrendItem {
  label: string
  value: number
}

const session = useSession()
const loading = ref(false)
const errorMessage = ref('')
const stats = ref([
  { title: 'Active students', value: '0', delta: '+0.0%', tone: 'primary', icon: 'tabler-users', note: 'Across the active portfolio' },
  { title: 'Attendance today', value: '0%', delta: '+0.0%', tone: 'success', icon: 'tabler-user-check', note: 'Live attendance summary' },
  { title: 'Fee collection', value: '৳ 0', delta: '+0.0%', tone: 'warning', icon: 'tabler-receipt-2', note: 'Month to date' },
  { title: 'Pending marks', value: '0', delta: '+0', tone: 'info', icon: 'tabler-clipboard-check', note: 'Teacher work queue' },
])
const trendRows = ref<TrendItem[]>([])
const signalRows = ref<SignalItem[]>([])
const cohortRows = ref<CohortItem[]>([])

async function loadAnalytics() {
  if (!session.schools.value.length) {
    stats.value = stats.value.map(item => ({ ...item, value: item.title.includes('%') ? '0%' : '0' }))
    trendRows.value = []
    signalRows.value = []
    cohortRows.value = []
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const summaries = await Promise.all(
      session.schools.value.map(school =>
        useApiFetch<DashboardSummaryResponse>(`/schools/${school.id}/dashboard/summary`).then(response => ({
          school,
          summary: response.data,
        })),
      ),
    )

    const totalStudents = summaries.reduce((sum, item) => sum + item.summary.admin.student_count, 0)
    const totalEmployees = summaries.reduce((sum, item) => sum + item.summary.admin.employee_count, 0)
    const feeThisMonth = summaries.reduce((sum, item) => sum + Number(item.summary.admin.fee_collection_this_month), 0)
    const feeLastMonth = summaries.reduce((sum, item) => sum + Number(item.summary.admin.fee_collection_last_month), 0)
    const pendingMarks = summaries.reduce((sum, item) => sum + item.summary.teacher.pending_marks_entries, 0)
    const attendanceAverage = summaries.length
      ? summaries.reduce((sum, item) => sum + Number(item.summary.admin.today_attendance_rate), 0) / summaries.length
      : 0
    const collectionDelta = feeLastMonth > 0 ? ((feeThisMonth - feeLastMonth) / feeLastMonth) * 100 : 0

    stats.value = [
      {
        title: 'Active students',
        value: totalStudents.toLocaleString(),
        delta: `+${session.schools.value.length}`,
        tone: 'primary',
        icon: 'tabler-users',
        note: `Across ${session.schools.value.length} schools`,
      },
      {
        title: 'Attendance today',
        value: `${attendanceAverage.toFixed(1)}%`,
        delta: `${attendanceAverage >= 90 ? '+' : ''}${(attendanceAverage - 90).toFixed(1)} pts`,
        tone: 'success',
        icon: 'tabler-user-check',
        note: `${totalEmployees.toLocaleString()} employees in service`,
      },
      {
        title: 'Fee collection',
        value: `৳ ${Math.round(feeThisMonth).toLocaleString()}`,
        delta: `${collectionDelta >= 0 ? '+' : ''}${collectionDelta.toFixed(1)}%`,
        tone: 'warning',
        icon: 'tabler-receipt-2',
        note: 'Month versus prior month',
      },
      {
        title: 'Pending marks',
        value: pendingMarks.toLocaleString(),
        delta: pendingMarks > 0 ? 'In flight' : 'Clear',
        tone: 'info',
        icon: 'tabler-clipboard-check',
        note: 'Across active teaching desks',
      },
    ]

    const collectionByMonth = new Map<string, number>()
    summaries.forEach(({ summary }) => {
      summary.accountant.collection_trend.forEach((item) => {
        const total = Number(item.total)
        collectionByMonth.set(item.month, (collectionByMonth.get(item.month) ?? 0) + total)
      })
    })

    const trendEntries = Array.from(collectionByMonth.entries()).slice(-6)
    const maxTotal = Math.max(...trendEntries.map(([, total]) => total), 1)
    trendRows.value = trendEntries.map(([label, total]) => ({
      label,
      value: Math.max(8, Math.round((total / maxTotal) * 100)),
    }))

    const unpaidInvoices = summaries.reduce((sum, item) => sum + item.summary.accountant.unpaid_invoices, 0)
    const pendingLeaves = summaries.reduce((sum, item) => sum + item.summary.admin.pending_leave_applications, 0)
    const upcomingExams = summaries.reduce((sum, item) => sum + item.summary.admin.upcoming_exams.length, 0)
    const auditEvents = summaries.reduce((sum, item) => sum + item.summary.auditor.recent_audit_logs.length, 0)

    signalRows.value = [
      {
        label: 'Attendance compliance',
        value: `${attendanceAverage.toFixed(1)}%`,
        note: 'Today across active schools',
      },
      {
        label: 'Collection recovery',
        value: unpaidInvoices.toLocaleString(),
        note: 'Open invoices still unpaid',
      },
      {
        label: 'Exam runway',
        value: upcomingExams.toLocaleString(),
        note: 'Upcoming exam windows scheduled',
      },
      {
        label: 'Audit movement',
        value: auditEvents.toLocaleString(),
        note: 'Recent compliance events recorded',
      },
    ]

    const strongestAttendance = [...summaries].sort((a, b) =>
      Number(b.summary.admin.today_attendance_rate) - Number(a.summary.admin.today_attendance_rate),
    )[0]
    const weakestCollection = [...summaries].sort((a, b) =>
      Number(a.summary.accountant.unpaid_invoices) - Number(b.summary.accountant.unpaid_invoices),
    ).reverse()[0]
    const heaviestReview = [...summaries].sort((a, b) =>
      b.summary.teacher.pending_marks_entries - a.summary.teacher.pending_marks_entries,
    )[0]

    cohortRows.value = [
      {
        title: strongestAttendance ? `${strongestAttendance.school.name} attendance lead` : 'Attendance lead',
        trend: strongestAttendance ? `${Number(strongestAttendance.summary.admin.today_attendance_rate).toFixed(1)}%` : '0%',
        note: 'Best same-day attendance performance in the current portfolio.',
        tone: 'success',
      },
      {
        title: weakestCollection ? `${weakestCollection.school.name} collection pressure` : 'Collection pressure',
        trend: weakestCollection ? `${weakestCollection.summary.accountant.unpaid_invoices} unpaid` : '0 unpaid',
        note: 'Needs the fastest accounts follow-up this week.',
        tone: 'warning',
      },
      {
        title: heaviestReview ? `${heaviestReview.school.name} assessment backlog` : 'Assessment backlog',
        trend: heaviestReview ? `${heaviestReview.summary.teacher.pending_marks_entries} pending` : '0 pending',
        note: `Pending leave approvals across the group: ${pendingLeaves.toLocaleString()}.`,
        tone: 'primary',
      },
    ]
  }
  catch {
    errorMessage.value = 'Analytics could not be loaded from live school data right now.'
    trendRows.value = []
    signalRows.value = []
    cohortRows.value = []
  }
  finally {
    loading.value = false
  }
}

watch(() => session.schools.value.map(school => school.id).join(','), loadAnalytics, { immediate: true })
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Analytics"
      title="Performance analytics"
      subtitle="Institutional health and delivery metrics for academic and operational leadership."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-chart-histogram">
          Live portfolio
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-presentation-analytics" to="/">
          Command center
        </VBtn>
      </template>
    </SchoolPageHeader>

    <VAlert
      v-if="errorMessage"
      type="warning"
      variant="tonal"
      class="mb-4"
    >
      {{ errorMessage }}
    </VAlert>

    <VRow class="mb-2">
      <VCol
        v-for="stat in stats"
        :key="stat.title"
        cols="12"
        md="6"
        xl="3"
      >
        <SchoolMetricCard v-bind="stat" />
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" xl="8">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Trend monitor
            </VCardTitle>
            <VCardSubtitle>Collection movement aggregated from the active school portfolio.</VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <div
              v-if="loading"
              class="text-body-2 text-medium-emphasis"
            >
              Refreshing portfolio analytics...
            </div>
            <div v-else class="school-bar-stack">
              <div
                v-for="row in trendRows"
                :key="row.label"
                class="school-bar-stack__row"
              >
                <div class="school-bar-stack__label">{{ row.label }}</div>
                <div class="school-bar-stack__track">
                  <div class="school-bar-stack__fill" :style="{ width: `${row.value}%` }" />
                </div>
                <div class="school-bar-stack__value">{{ row.value }}%</div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" xl="4">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Signal pack
            </VCardTitle>
            <VCardSubtitle>The ratios that matter most at operator level.</VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <div class="school-alert-list">
              <div
                v-for="channel in signalRows"
                :key="channel.label"
                class="school-alert-list__item"
              >
                <div>
                  <div class="font-weight-medium">{{ channel.label }}</div>
                  <div class="text-body-2 text-medium-emphasis">{{ channel.note }}</div>
                </div>
                <div class="school-metric-card__value text-h6">{{ channel.value }}</div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol
        v-for="cohort in cohortRows"
        :key="cohort.title"
        cols="12"
        md="4"
      >
        <VCard class="school-signal-card h-100">
          <VCardText class="pa-5">
            <div class="school-kicker mb-3">
              Portfolio signal
            </div>
            <div class="text-h6 font-weight-bold mb-2">
              {{ cohort.title }}
            </div>
            <div class="text-body-1 text-medium-emphasis mb-4">
              {{ cohort.note }}
            </div>
            <VChip :color="cohort.tone" variant="tonal" size="small">
              {{ cohort.trend }}
            </VChip>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
