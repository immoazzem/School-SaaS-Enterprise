<script setup lang="ts">
import type { Exam, SalaryRecord, StudentInvoice } from '~/composables/useApi'

definePageMeta({
  layout: 'default',
})

interface ListResponse<T> {
  data: T[]
}

type LibraryRow = {
  label: string
  count: string
  note: string
}

type QueueRow = {
  name: string
  owner: string
  status: string
  updated: string
  to: string
}

const session = useSession()
const loading = ref(false)
const errorMessage = ref('')
const libraryRows = ref<LibraryRow[]>([])
const queueRows = ref<QueueRow[]>([])

async function loadReports() {
  if (!session.selectedSchool.value) {
    libraryRows.value = []
    queueRows.value = []
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const schoolId = session.selectedSchool.value.id
    const [examsResponse, invoiceResponse, salaryResponse] = await Promise.all([
      useApiFetch<ListResponse<Exam>>(`/schools/${schoolId}/exams?per_page=100`),
      useApiFetch<ListResponse<StudentInvoice>>(`/schools/${schoolId}/student-invoices?per_page=100`),
      useApiFetch<ListResponse<SalaryRecord>>(`/schools/${schoolId}/salary-records?per_page=100`),
    ])

    const publishedExams = examsResponse.data.filter(exam => exam.is_published).length
    const unpaidInvoices = invoiceResponse.data.filter(invoice => invoice.status !== 'paid').length
    const pendingSalaries = salaryResponse.data.filter(record => record.status !== 'paid').length

    libraryRows.value = [
      {
        label: 'Academic exports',
        count: `${publishedExams} published exam sets`,
        note: 'Ready for marksheets, result sheets, and result review.',
      },
      {
        label: 'Finance exports',
        count: `${invoiceResponse.data.length} invoice records`,
        note: `${unpaidInvoices} invoices still need collection follow-up.`,
      },
      {
        label: 'Salary exports',
        count: `${salaryResponse.data.length} salary records`,
        note: `${pendingSalaries} payroll records still open.`,
      },
    ]

    queueRows.value = [
      {
        name: 'Marksheet and result sheet queue',
        owner: 'Academic reporting',
        status: publishedExams > 0 ? 'Ready' : 'Needs review',
        updated: `${publishedExams} published exam set${publishedExams === 1 ? '' : 's'}`,
        to: `/schools/${schoolId}/reports`,
      },
      {
        name: 'Invoice export queue',
        owner: 'Finance reporting',
        status: invoiceResponse.data.length > 0 ? 'Ready' : 'Queued',
        updated: `${invoiceResponse.data.length} invoice record${invoiceResponse.data.length === 1 ? '' : 's'}`,
        to: `/schools/${schoolId}/reports`,
      },
      {
        name: 'Salary export queue',
        owner: 'Payroll reporting',
        status: pendingSalaries > 0 ? 'Refreshing' : 'Ready',
        updated: `${salaryResponse.data.length} salary record${salaryResponse.data.length === 1 ? '' : 's'}`,
        to: `/schools/${schoolId}/reports`,
      },
    ]
  }
  catch {
    errorMessage.value = 'Reporting data could not be loaded from the API right now.'
    libraryRows.value = []
    queueRows.value = []
  }
  finally {
    loading.value = false
  }
}

watch(() => session.selectedSchool.value?.id, loadReports, { immediate: true })
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Reports"
      title="Reporting pipeline"
      subtitle="Generate operational, academic, and board-ready output from one managed queue."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-clock" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/reports` : '/schools'">
          Queue status
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-file-export" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/reports` : '/schools'">
          New export
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
        v-for="library in libraryRows"
        :key="library.label"
        cols="12"
        md="4"
      >
        <VCard class="school-signal-card h-100">
          <VCardText class="pa-5">
            <div class="school-kicker mb-3">
              Report library
            </div>
            <div class="text-h6 font-weight-bold mb-2">{{ library.label }}</div>
            <div class="text-body-2 text-medium-emphasis mb-4">{{ library.note }}</div>
            <div class="school-metric-card__value text-h6">{{ library.count }}</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VCard class="school-signal-card">
      <VCardText class="pt-2">
        <div
          v-if="loading"
          class="text-body-2 text-medium-emphasis"
        >
          Refreshing reporting queue...
        </div>
        <div v-else class="school-alert-list">
          <div
            v-for="report in queueRows"
            :key="`${report.name}-${report.updated}`"
            class="school-alert-list__item"
          >
            <div>
              <div class="font-weight-medium">{{ report.name }}</div>
              <div class="text-body-2 text-medium-emphasis">{{ report.owner }} · {{ report.updated }}</div>
            </div>
            <div class="d-flex align-center gap-3">
              <VChip :color="report.status === 'Ready' ? 'success' : report.status === 'Refreshing' ? 'warning' : report.status === 'Failed' ? 'error' : 'default'" variant="tonal" size="small">
                {{ report.status }}
              </VChip>
              <VBtn variant="text" color="primary" append-icon="tabler-arrow-right" :to="report.to">
                Open
              </VBtn>
            </div>
          </div>
        </div>
      </VCardText>
    </VCard>
  </div>
</template>
