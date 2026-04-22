<script setup lang="ts">
import { marksReleaseReadiness, marksRows } from '@/utils/schoolDashboardData'

definePageMeta({
  layout: 'default',
})

interface LiveMarksRecord {
  verification_status: string
  class_subject?: {
    subject?: {
      name?: string
    }
  }
}

interface LiveMarksResponse {
  data: LiveMarksRecord[]
}

const session = useSession()
const loading = ref(false)
const errorMessage = ref('')
const liveMarksRows = ref(marksRows)

const moderationColor = (value: string) => ({
  Complete: 'success',
  'In review': 'warning',
  'Needs review': 'error',
}[value] ?? 'default')

async function loadMarks() {
  if (!session.selectedSchool.value)
    return

  loading.value = true
  errorMessage.value = ''

  try {
    const response = await useApiFetch<LiveMarksResponse>(`/schools/${session.selectedSchool.value.id}/marks-entries`)

    const grouped = new Map<string, { subject: string; progress: string; pending: number; moderated: string }>()

    response.data.forEach(entry => {
      const subject = entry.class_subject?.subject?.name ?? 'Unknown subject'
      const existing = grouped.get(subject) ?? { subject, progress: '0%', pending: 0, moderated: 'Complete' }
      existing.pending += entry.verification_status === 'pending' ? 1 : 0
      existing.moderated = entry.verification_status === 'rejected'
        ? 'Needs review'
        : entry.verification_status === 'pending'
          ? 'In review'
          : existing.moderated
      grouped.set(subject, existing)
    })

    liveMarksRows.value = (grouped.size ? Array.from(grouped.values()) : marksRows).map(row => ({
      ...row,
      progress: row.pending > 0 ? `${Math.max(100 - row.pending * 2, 35)}%` : '100%',
    }))
  }
  catch {
    errorMessage.value = 'Marks data is unavailable right now. Showing the local operating snapshot instead.'
    liveMarksRows.value = marksRows
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
      subtitle="Run the mark entry cycle with cleaner signals around backlog, moderation, and release readiness."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-adjustments-horizontal">
          Moderation
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-send">
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
        v-for="item in marksReleaseReadiness"
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
              {{ item.tone === 'success' ? 'On track' : item.tone === 'warning' ? 'Watch' : 'Blocked' }}
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
        <VTable class="school-data-table">
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
        <div
          v-if="loading"
          class="text-body-2 text-medium-emphasis mt-3"
        >
          Refreshing live marks records...
        </div>
      </VCardText>
    </VCard>
  </div>
</template>
