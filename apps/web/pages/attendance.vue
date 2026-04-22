<script setup lang="ts">
import { attendanceEscalations, attendanceRows } from '@/utils/schoolDashboardData'

definePageMeta({
  layout: 'default',
})

interface LiveAttendanceRecord {
  status: string
  student_enrollment?: {
    academic_class?: {
      name?: string
    }
  }
}

interface LiveAttendanceResponse {
  data: LiveAttendanceRecord[]
}

const session = useSession()
const loading = ref(false)
const errorMessage = ref('')
const liveRows = ref(attendanceRows)
const totalPresent = computed(() => liveRows.value.reduce((sum, row) => sum + row.present, 0))
const totalAbsent = computed(() => liveRows.value.reduce((sum, row) => sum + row.absent, 0))

const escalationColor = (value: string) => ({
  error: 'error',
  warning: 'warning',
  success: 'success',
}[value] ?? 'default')

async function loadAttendance() {
  if (!session.selectedSchool.value)
    return

  loading.value = true
  errorMessage.value = ''

  try {
    const response = await useApiFetch<LiveAttendanceResponse>(`/schools/${session.selectedSchool.value.id}/student-attendance-records`)

    const grouped = new Map<string, { className: string; present: number; absent: number; late: number; completedBy: string }>()

    response.data.forEach(record => {
      const className = record.student_enrollment?.academic_class?.name ?? 'Unassigned class'
      const existing = grouped.get(className) ?? { className, present: 0, absent: 0, late: 0, completedBy: 'Live sync' }

      if (record.status === 'present')
        existing.present += 1
      else if (record.status === 'late') {
        existing.present += 1
        existing.late += 1
      }
      else if (record.status === 'absent')
        existing.absent += 1

      grouped.set(className, existing)
    })

    liveRows.value = grouped.size ? Array.from(grouped.values()) : attendanceRows
  }
  catch {
    errorMessage.value = 'Attendance records are unavailable right now. Showing the operating snapshot instead.'
    liveRows.value = attendanceRows
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
        <VBtn variant="outlined" color="default" prepend-icon="tabler-refresh">
          Refresh
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-device-floppy">
          Finalize day
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
        <SchoolMetricCard title="Present" :value="String(totalPresent)" delta="+6" tone="success" icon="tabler-user-check" note="Students checked in" />
      </VCol>
      <VCol cols="12" md="4">
        <SchoolMetricCard title="Absent" :value="String(totalAbsent)" delta="-2" tone="error" icon="tabler-user-x" note="Needs guardian follow-up" />
      </VCol>
      <VCol cols="12" md="4">
        <SchoolMetricCard title="Late arrivals" value="6" delta="-1" tone="warning" icon="tabler-clock" note="Across all campuses" />
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" xl="8">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">Daily roster monitor</VCardTitle>
            <VCardSubtitle>See which classes are complete and where the office needs to chase updates.</VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <VTable class="school-data-table">
              <thead>
                <tr>
                  <th>Class</th>
                  <th>Present</th>
                  <th>Absent</th>
                  <th>Late</th>
                  <th>Completed by</th>
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
            <div
              v-if="loading"
              class="text-body-2 text-medium-emphasis mt-3"
            >
              Refreshing live attendance records...
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" xl="4">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">Escalations</VCardTitle>
            <VCardSubtitle>The classes that need office attention before closeout.</VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <div class="school-alert-list">
              <div
                v-for="item in attendanceEscalations"
                :key="item.className"
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
