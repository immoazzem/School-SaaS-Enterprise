<script setup lang="ts">
import { financePressurePoints, financeRows } from '@/utils/schoolDashboardData'

definePageMeta({
  layout: 'default',
})

const session = useSession()
const loading = ref(false)
const errorMessage = ref('')
const liveFinanceRows = ref(financeRows)

interface LiveFinanceInvoice {
  status: string
  total: number
  paid_amount: number
  fee_month?: string
}

interface LiveFinanceResponse {
  data: LiveFinanceInvoice[]
}

const financeTone = (value: string) => ({
  error: 'error',
  warning: 'warning',
  success: 'success',
}[value] ?? 'default')

async function loadFinance() {
  if (!session.selectedSchool.value)
    return

  loading.value = true
  errorMessage.value = ''

  try {
    const response = await useApiFetch<LiveFinanceResponse>(`/schools/${session.selectedSchool.value.id}/student-invoices`)

    const grouped = new Map<string, { stream: string; collected: string; overdue: string; coverage: string }>()

    response.data.forEach(invoice => {
      const stream = invoice.fee_month ?? 'General'
      const existing = grouped.get(stream) ?? { stream, collected: '৳ 0', overdue: '৳ 0', coverage: '0%' }
      const collected = Number(existing.collected.replace(/[^\d.-]/g, '')) + invoice.paid_amount
      const overdue = Number(existing.overdue.replace(/[^\d.-]/g, '')) + Math.max(invoice.total - invoice.paid_amount, 0)
      const coverage = invoice.total > 0 ? Math.round((invoice.paid_amount / invoice.total) * 100) : 0

      existing.collected = `৳ ${Math.round(collected).toLocaleString()}`
      existing.overdue = `৳ ${Math.round(overdue).toLocaleString()}`
      existing.coverage = `${coverage}%`
      grouped.set(stream, existing)
    })

    liveFinanceRows.value = grouped.size ? Array.from(grouped.values()) : financeRows
  }
  catch {
    errorMessage.value = 'Finance records are unavailable right now. Showing the local operating snapshot instead.'
    liveFinanceRows.value = financeRows
  }
  finally {
    loading.value = false
  }
}

watch(() => session.selectedSchool.value?.id, loadFinance, { immediate: true })
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Finance"
      title="Finance desk"
      subtitle="A tighter view of collection strength, overdue pressure, and the fee streams that matter most."
    >
      <template #actions>
        <VBtn variant="outlined" color="default" prepend-icon="tabler-file-spreadsheet">
          Reconciliation
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-cash-banknote">
          Record payment
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
      <VCol cols="12" xl="8">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">Fee stream performance</VCardTitle>
            <VCardSubtitle>Which collection lines are healthy and which ones are dragging on recovery.</VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <VTable class="school-data-table">
              <thead>
                <tr>
                  <th>Fee stream</th>
                  <th>Collected</th>
                  <th>Overdue</th>
                  <th>Coverage</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in liveFinanceRows" :key="row.stream">
                  <td class="font-weight-medium">{{ row.stream }}</td>
                  <td>{{ row.collected }}</td>
                  <td>{{ row.overdue }}</td>
                  <td>{{ row.coverage }}</td>
                </tr>
              </tbody>
            </VTable>
            <div
              v-if="loading"
              class="text-body-2 text-medium-emphasis mt-3"
            >
              Refreshing live finance records...
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" xl="4">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">Pressure points</VCardTitle>
            <VCardSubtitle>The few finance decisions that change this week fastest.</VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <div class="school-alert-list">
              <div
                v-for="item in financePressurePoints"
                :key="item.title"
                class="school-alert-list__item"
              >
                <div>
                  <div class="font-weight-medium">{{ item.title }}</div>
                  <div class="text-body-2 text-medium-emphasis">{{ item.note }}</div>
                </div>
                <VChip :color="financeTone(item.tone)" variant="tonal" size="small">
                  {{ item.status }}
                </VChip>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
