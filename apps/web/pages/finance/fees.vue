<script setup lang="ts">
import type { StudentDiscount, StudentInvoice } from '~/composables/useApi'

definePageMeta({
  layout: 'default',
})

interface ListResponse<T> {
  data: T[]
}

type FinanceRow = {
  stream: string
  collected: string
  overdue: string
  coverage: string
}

type PressurePoint = {
  title: string
  note: string
  status: string
  tone: string
}

const session = useSession()
const loading = ref(false)
const errorMessage = ref('')
const liveFinanceRows = ref<FinanceRow[]>([])
const pressurePoints = ref<PressurePoint[]>([])

const financeTone = (value: string) => ({
  error: 'error',
  warning: 'warning',
  success: 'success',
}[value] ?? 'default')

async function loadFinance() {
  if (!session.selectedSchool.value) {
    liveFinanceRows.value = []
    pressurePoints.value = []
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const schoolId = session.selectedSchool.value.id
    const [invoiceResponse, discountResponse] = await Promise.all([
      useApiFetch<ListResponse<StudentInvoice>>(`/schools/${schoolId}/student-invoices?per_page=200`),
      useApiFetch<ListResponse<StudentDiscount>>(`/schools/${schoolId}/student-discounts?per_page=200`),
    ])

    const grouped = new Map<string, { collected: number; overdue: number; total: number }>()
    invoiceResponse.data.forEach((invoice) => {
      const stream = invoice.fee_month ?? 'General'
      const bucket = grouped.get(stream) ?? { collected: 0, overdue: 0, total: 0 }
      const total = Number(invoice.total)
      const paid = Number(invoice.paid_amount)
      bucket.collected += paid
      bucket.overdue += Math.max(total - paid, 0)
      bucket.total += total
      grouped.set(stream, bucket)
    })

    liveFinanceRows.value = Array.from(grouped.entries()).map(([stream, bucket]) => ({
      stream,
      collected: `৳ ${Math.round(bucket.collected).toLocaleString()}`,
      overdue: `৳ ${Math.round(bucket.overdue).toLocaleString()}`,
      coverage: `${bucket.total > 0 ? Math.round((bucket.collected / bucket.total) * 100) : 0}%`,
    }))

    const highBalanceInvoices = invoiceResponse.data.filter(invoice => Number(invoice.total) - Number(invoice.paid_amount) >= 10000).length
    const activeDiscounts = discountResponse.data.filter(discount => discount.status === 'active').length
    const clearedMonths = liveFinanceRows.value.filter(row => Number.parseInt(row.coverage, 10) >= 95).length

    pressurePoints.value = [
      {
        title: 'High-balance defaulters',
        note: `${highBalanceInvoices} invoices are carrying balances above ৳ 10,000.`,
        status: highBalanceInvoices > 0 ? 'Escalate' : 'Clear',
        tone: highBalanceInvoices > 0 ? 'error' : 'success',
      },
      {
        title: 'Active discounts',
        note: `${activeDiscounts} discount records are currently affecting billed totals.`,
        status: activeDiscounts > 0 ? 'Review' : 'Clear',
        tone: activeDiscounts > 0 ? 'warning' : 'success',
      },
      {
        title: 'Healthy fee months',
        note: `${clearedMonths} fee streams are already above 95% coverage.`,
        status: 'Monitor',
        tone: 'success',
      },
    ]
  }
  catch {
    errorMessage.value = 'Finance records are unavailable right now.'
    liveFinanceRows.value = []
    pressurePoints.value = []
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
        <VBtn variant="outlined" color="default" prepend-icon="tabler-file-spreadsheet" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/finance` : '/schools'">
          Reconciliation
        </VBtn>
        <VBtn color="primary" prepend-icon="tabler-cash-banknote" :to="session.selectedSchool ? `/schools/${session.selectedSchool.id}/invoice-payments` : '/schools'">
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
            <div
              v-if="loading"
              class="text-body-2 text-medium-emphasis mt-3"
            >
              Refreshing live finance records...
            </div>
            <VTable v-else class="school-data-table">
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
                v-for="item in pressurePoints"
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
