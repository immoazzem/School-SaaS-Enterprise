<script setup lang="ts">
import type { Student, StudentInvoice } from '~/composables/useApi'

definePageMeta({
  layout: 'default',
})

interface StudentsResponse {
  data: Student[]
}

interface InvoicesResponse {
  data: StudentInvoice[]
}

type LiveStudentRow = {
  id: number
  name: string
  className: string
  guardian: string
  risk: string
  balance: string
}

const session = useSession()
const loading = ref(false)
const errorMessage = ref('')
const search = ref('')
const liveStudents = ref<LiveStudentRow[]>([])

const riskColor = (value: string) => ({
  Low: 'success',
  Watch: 'warning',
  Intervention: 'error',
}[value] ?? 'default')

async function loadStudents() {
  if (!session.selectedSchool.value) {
    liveStudents.value = []
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const schoolId = session.selectedSchool.value.id
    const query = new URLSearchParams({ status: 'active' })
    if (search.value.trim())
      query.set('search', search.value.trim())

    const [studentResponse, invoiceResponse] = await Promise.all([
      useApiFetch<StudentsResponse>(`/schools/${schoolId}/students?${query.toString()}`),
      useApiFetch<InvoicesResponse>(`/schools/${schoolId}/student-invoices?per_page=300`),
    ])

    const balances = new Map<number, number>()
    invoiceResponse.data.forEach((invoice) => {
      const studentId = invoice.student_enrollment?.student_id
      if (!studentId)
        return
      balances.set(studentId, (balances.get(studentId) ?? 0) + Math.max(Number(invoice.total) - Number(invoice.paid_amount), 0))
    })

    liveStudents.value = studentResponse.data.map((student) => {
      const balance = balances.get(student.id) ?? 0
      const risk = balance >= 10000 ? 'Intervention' : balance > 0 ? 'Watch' : 'Low'

      return {
        id: student.id,
        name: student.full_name,
        className: student.admission_no,
        guardian: student.guardian?.full_name ?? 'No guardian linked',
        risk,
        balance: balance > 0 ? `৳ ${Math.round(balance).toLocaleString()}` : 'Cleared',
      }
    })
  }
  catch {
    errorMessage.value = 'Student records could not be loaded from the API.'
    liveStudents.value = []
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
      subtitle="Track learner status, financial follow-up, and guardian accountability from one live list."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-filter" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/enrollments` : '/schools'">
          Enrollments
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-user-plus" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/students` : '/schools'">
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
            placeholder="Search by student, admission number, guardian"
            prepend-inner-icon="tabler-search"
            density="compact"
            hide-details
            variant="solo-filled"
            rounded="lg"
            class="school-toolbar__search"
          />
          <VBtn variant="tonal" color="default" prepend-icon="tabler-download" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/students` : '/schools'">
            Open workspace
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
        <VTable v-else class="school-data-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Admission</th>
              <th>Guardian</th>
              <th>Risk</th>
              <th>Balance</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="student in liveStudents" :key="student.id">
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
