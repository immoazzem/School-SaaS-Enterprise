<script setup lang="ts">
import type { DashboardSummary, MarksEntry } from '~/composables/useApi'

definePageMeta({
  layout: 'default',
})

interface ListResponse<T> {
  data: T[]
}

interface DashboardSummaryResponse {
  data: DashboardSummary
}

type MarksRow = {
  subject: string
  progress: string
  pending: number
  moderated: string
}

type ReadinessRow = {
  title: string
  note: string
  value: string
  tone: string
}

const session = useSession()
const loading = ref(false)
const errorMessage = ref('')
const liveMarksRows = ref<MarksRow[]>([])
const readinessRows = ref<ReadinessRow[]>([])

const moderationColor = (value: string) => ({
  Complete: 'success',
  'In review': 'warning',
  'Needs review': 'error',
}[value] ?? 'default')

async function loadMarks() {
  if (!session.selectedSchool.value) {
    liveMarksRows.value = []
    readinessRows.value = []
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const schoolId = session.selectedSchool.value.id
    const [marksResponse, dashboardResponse] = await Promise.all([
      useApiFetch<ListResponse<MarksEntry>>(`/schools/${schoolId}/marks-entries?per_page=300`),
      useApiFetch<DashboardSummaryResponse>(`/schools/${schoolId}/dashboard/summary`),
    ])

    const grouped = new Map<string, { total: number; pending: number; rejected: number }>()
    marksResponse.data.forEach((entry) => {
      const subject = entry.class_subject?.subject?.name ?? 'Unknown subject'
      const bucket = grouped.get(subject) ?? { total: 0, pending: 0, rejected: 0 }
      bucket.total += 1
      if (entry.verification_status === 'pending')
        bucket.pending += 1
      if (entry.verification_status === 'rejected')
        bucket.rejected += 1
      grouped.set(subject, bucket)
    })

    liveMarksRows.value = Array.from(grouped.entries()).map(([subject, bucket]) => {
      const completed = Math.max(bucket.total - bucket.pending - bucket.rejected, 0)
      const progress = bucket.total > 0 ? `${Math.round((completed / bucket.total) * 100)}%` : '0%'

      return {
        subject,
        progress,
        pending: bucket.pending + bucket.rejected,
        moderated: bucket.rejected > 0 ? 'Needs review' : bucket.pending > 0 ? 'In review' : 'Complete',
      }
    }).sort((a, b) => a.subject.localeCompare(b.subject))

    const dashboard = dashboardResponse.data
    const pendingMarks = dashboard.teacher.pending_marks_entries
    const upcomingExams = dashboard.teacher.upcoming_exams.length
    const blockedSubjects = liveMarksRows.value.filter(item => item.moderated === 'Needs review').length

    readinessRows.value = [
      {
        title: 'Teacher queue',
        note: 'Mark entries still pending verification or completion.',
        value: pendingMarks.toLocaleString(),
        tone: pendingMarks > 0 ? 'warning' : 'success',
      },
      {
        title: 'Upcoming exams',
        note: 'Scheduled exam windows shaping the next release cycle.',
        value: upcomingExams.toLocaleString(),
        tone: upcomingExams > 0 ? 'primary' : 'success',
      },
      {
        title: 'Blocked subjects',
        note: 'Subjects still carrying rejected or disputed entries.',
        value: blockedSubjects.toLocaleString(),
        tone: blockedSubjects > 0 ? 'error' : 'success',
      },
    ]
  }
  catch {
    errorMessage.value = 'Marks data is unavailable right now.'
    liveMarksRows.value = []
    readinessRows.value = []
  }
  finally {
    loading.value = false
  }
}

watch(() => session.selectedSchool.value?.id, loadMarks, { immediate: true })
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Assessment"
      title="Assessment desk"
      subtitle="Run the mark entry cycle with live signals around backlog, moderation, and release readiness."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-adjustments-horizontal" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/marks` : '/schools'">
          Moderation desk
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-send" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/reports` : '/schools'">
          Prepare release
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
        v-for="item in readinessRows"
        :key="item.title"
        cols="12"
        md="4"
      >
        <VCard class="school-signal-card h-100">
          <VCardText class="pa-5">
            <div class="school-kicker mb-3">
              Release readiness
            </div>
            <div class="school-metric-card__value mb-2">
              {{ item.value }}
            </div>
            <div class="text-h6 font-weight-bold mb-2">
              {{ item.title }}
            </div>
            <div class="text-body-2 text-medium-emphasis mb-4">
              {{ item.note }}
            </div>
            <VChip :color="item.tone" variant="tonal" size="small">
              {{ item.tone === 'success' ? 'Healthy' : item.tone === 'warning' ? 'Watch' : item.tone === 'error' ? 'Blocked' : 'Active' }}
            </VChip>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VCard class="school-signal-card">
      <VCardItem>
        <VCardTitle class="font-weight-bold">Subject progress</VCardTitle>
        <VCardSubtitle>Backlog and moderation status by subject desk.</VCardSubtitle>
      </VCardItem>
      <VCardText class="pt-2">
        <div
          v-if="loading"
          class="text-body-2 text-medium-emphasis mt-3"
        >
          Refreshing live marks records...
        </div>
        <VTable v-else class="school-data-table">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Progress</th>
              <th>Pending scripts</th>
              <th>Moderation</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in liveMarksRows" :key="row.subject">
              <td class="font-weight-medium">{{ row.subject }}</td>
              <td>{{ row.progress }}</td>
              <td>{{ row.pending }}</td>
              <td>
                <VChip :color="moderationColor(row.moderated)" variant="tonal" size="small">
                  {{ row.moderated }}
                </VChip>
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </div>
</template>
