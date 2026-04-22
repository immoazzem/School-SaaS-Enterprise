<script setup lang="ts">
import type { StudentAttendanceSummary } from '~/composables/useApi'

definePageMeta({
  layout: 'default',
})

interface AttendanceSummaryResponse {
  data: StudentAttendanceSummary[]
}

type AttendanceTableRow = {
  className: string
  present: number
  absent: number
  late: number
  completedBy: string
}

type EscalationRow = {
  className: string
  issue: string
  action: string
  tone: string
}

const session = useSession()
const loading = ref(false)
const errorMessage = ref('')
const liveRows = ref<AttendanceTableRow[]>([])
const escalations = ref<EscalationRow[]>([])
const totalPresent = computed(() => liveRows.value.reduce((sum, row) => sum + row.present, 0))
const totalAbsent = computed(() => liveRows.value.reduce((sum, row) => sum + row.absent, 0))
const totalLate = computed(() => liveRows.value.reduce((sum, row) => sum + row.late, 0))

const escalationColor = (value: string) => ({
  error: 'error',
  warning: 'warning',
  success: 'success',
}[value] ?? 'default')

async function loadAttendance() {
  if (!session.selectedSchool.value) {
    liveRows.value = []
    escalations.value = []
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const response = await useApiFetch<AttendanceSummaryResponse>(
      `/schools/${session.selectedSchool.value.id}/attendance/summary?month=${new Date().toISOString().slice(0, 7)}`,
    )

    const grouped = new Map<string, AttendanceTableRow>()
    response.data.forEach((record) => {
      const className = record.student_enrollment?.academic_class?.name ?? 'Unassigned class'
      const row = grouped.get(className) ?? { className, present: 0, absent: 0, late: 0, completedBy: 'Live summary' }
      row.present += record.present
      row.absent += record.absent
      row.late += record.late
      grouped.set(className, row)
    })

    liveRows.value = Array.from(grouped.values()).sort((a, b) => a.className.localeCompare(b.className))
    escalations.value = response.data
      .filter(record => Number(record.attendance_percentage) < 85)
      .slice(0, 4)
      .map((record) => {
        const name = record.student_enrollment?.student?.full_name ?? 'Student'
        const className = record.student_enrollment?.academic_class?.name ?? 'Unassigned class'
        const attendance = Number(record.attendance_percentage).toFixed(1)

        return {
          className,
          issue: `${name} is tracking at ${attendance}% attendance this month.`,
          action: Number(record.attendance_percentage) < 75 ? 'Intervene' : 'Follow up',
          tone: Number(record.attendance_percentage) < 75 ? 'error' : 'warning',
        }
      })

    if (!escalations.value.length && liveRows.value.length) {
      escalations.value = [{
        className: liveRows.value[0].className,
        issue: 'Attendance is within target today.',
        action: 'Healthy',
        tone: 'success',
      }]
    }
  }
  catch {
    errorMessage.value = 'Attendance records are unavailable right now.'
    liveRows.value = []
    escalations.value = []
  }
  finally {
    loading.value = false
  }
}

watch(() => session.selectedSchool.value?.id, loadAttendance, { immediate: true })
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Attendance"
      title="Attendance control"
      subtitle="Keep every class accounted for before the operational day gets away from you."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-refresh" @click="loadAttendance">
          Refresh
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-arrow-right" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/attendance` : '/schools'">
          Open workspace
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
      <VCol cols="12" md="4">
        <SchoolMetricCard title="Present" :value="String(totalPresent)" delta="Live" tone="success" icon="tabler-user-check" note="Students marked present this month" />
      </VCol>
      <VCol cols="12" md="4">
        <SchoolMetricCard title="Absent" :value="String(totalAbsent)" delta="Live" tone="error" icon="tabler-user-x" note="Requires guardian follow-up" />
      </VCol>
      <VCol cols="12" md="4">
        <SchoolMetricCard title="Late arrivals" :value="String(totalLate)" delta="Live" tone="warning" icon="tabler-clock" note="Across the active school" />
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" xl="8">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">Daily roster monitor</VCardTitle>
            <VCardSubtitle>Attendance totals by class from the live summary service.</VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <div
              v-if="loading"
              class="text-body-2 text-medium-emphasis mt-3"
            >
              Refreshing live attendance records...
            </div>
            <VTable v-else class="school-data-table">
              <thead>
                <tr>
                  <th>Class</th>
                  <th>Present</th>
                  <th>Absent</th>
                  <th>Late</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in liveRows" :key="row.className">
                  <td class="font-weight-medium">{{ row.className }}</td>
                  <td>{{ row.present }}</td>
                  <td>{{ row.absent }}</td>
                  <td>{{ row.late }}</td>
                  <td>{{ row.completedBy }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" xl="4">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">Escalations</VCardTitle>
            <VCardSubtitle>The attendance issues worth acting on next.</VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <div class="school-alert-list">
              <div
                v-for="item in escalations"
                :key="`${item.className}-${item.issue}`"
                class="school-alert-list__item"
              >
                <div>
                  <div class="font-weight-medium">{{ item.className }}</div>
                  <div class="text-body-2 text-medium-emphasis">{{ item.issue }}</div>
                </div>
                <VChip :color="escalationColor(item.tone)" variant="tonal" size="small">
                  {{ item.action }}
                </VChip>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
