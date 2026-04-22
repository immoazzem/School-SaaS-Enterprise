<script setup lang="ts">
import { studentRoster } from '@/utils/schoolDashboardData'

definePageMeta({
  layout: 'default',
})

interface LiveStudentRecord {
  id: number
  full_name: string
  admission_no: string
  guardian?: {
    full_name?: string
  }
}

interface LiveStudentsResponse {
  data: LiveStudentRecord[]
}

const session = useSession()
const loading = ref(false)
const errorMessage = ref('')
const search = ref('')
const liveStudents = ref(studentRoster)

const riskColor = (value: string) => ({
  Low: 'success',
  Watch: 'warning',
  Intervention: 'error',
}[value] ?? 'default')

async function loadStudents() {
  if (!session.selectedSchool.value)
    return

  loading.value = true
  errorMessage.value = ''

  try {
    const response = await useApiFetch<LiveStudentsResponse>(`/schools/${session.selectedSchool.value.id}/students?status=active&search=${encodeURIComponent(search.value)}`)

    liveStudents.value = response.data.map(student => ({
      name: student.full_name,
      className: student.admission_no,
      guardian: student.guardian?.full_name ?? 'No guardian linked',
      risk: 'Low',
      balance: 'Live record',
    }))
  }
  catch {
    errorMessage.value = 'Student records could not be loaded from the API. Showing the local snapshot instead.'
    liveStudents.value = studentRoster
  }
  finally {
    loading.value = false
  }
}

watch([() => session.selectedSchool.value?.id, search], loadStudents, { immediate: true })
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Students"
      title="Student operations"
      subtitle="Track learner status, financial follow-up, and guardian accountability from one list."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-filter">
          Filters
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-user-plus">
          Add student
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

    <VCard class="school-signal-card">
      <VCardText class="pa-4">
        <div class="school-toolbar">
          <VTextField
            v-model="search"
            placeholder="Search by student, class, guardian"
            prepend-inner-icon="tabler-search"
            density="compact"
            hide-details
            variant="solo-filled"
            rounded="lg"
            class="school-toolbar__search"
          />
          <VBtn variant="tonal" color="default" prepend-icon="tabler-download">
            Export
          </VBtn>
        </div>
      </VCardText>

      <VCardText class="pt-0">
        <div
          v-if="loading"
          class="text-body-2 text-medium-emphasis px-4 pb-4"
        >
          Loading student records...
        </div>
        <VTable class="school-data-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Class</th>
              <th>Guardian</th>
              <th>Risk</th>
              <th>Balance</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="student in liveStudents" :key="student.name">
              <td class="font-weight-medium">{{ student.name }}</td>
              <td>{{ student.className }}</td>
              <td>{{ student.guardian }}</td>
              <td>
                <VChip :color="riskColor(student.risk)" variant="tonal" size="small">
                  {{ student.risk }}
                </VChip>
              </td>
              <td>{{ student.balance }}</td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </div>
</template>
