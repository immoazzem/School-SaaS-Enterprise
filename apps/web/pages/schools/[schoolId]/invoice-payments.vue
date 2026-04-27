<script setup lang="ts">
import type { StudentInvoice } from '~/composables/useApi'

interface InvoicePaymentItem {
  id: number
  amount: string
  paid_on: string
  payment_method: string
  transaction_ref?: string | null
  student_invoice?: Pick<StudentInvoice, 'invoice_no' | 'status'> | null
}

interface ListResponse<T> {
  data: T[]
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const payments = ref<InvoicePaymentItem[]>([])
const invoices = ref<StudentInvoice[]>([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

const form = reactive({
  student_invoice_id: '',
  amount: '',
  paid_on: '',
  payment_method: 'cash',
  transaction_ref: '',
  notes: '',
})

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const [paymentResponse, invoiceResponse] = await Promise.all([
      api.request<ListResponse<InvoicePaymentItem>>(`/schools/${schoolId.value}/invoice-payments?per_page=100`),
      api.request<ListResponse<StudentInvoice>>(`/schools/${schoolId.value}/student-invoices?per_page=100`),
    ])
    payments.value = paymentResponse.data
    invoices.value = invoiceResponse.data
  }
  catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load invoice payments.'
  }
  finally {
    loading.value = false
  }
}

async function recordPayment() {
  saving.value = true
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/invoice-payments`, {
      method: 'POST',
      body: {
        student_invoice_id: Number(form.student_invoice_id),
        amount: Number(form.amount),
        paid_on: form.paid_on,
        payment_method: form.payment_method,
        transaction_ref: form.transaction_ref || null,
        notes: form.notes || null,
      },
    })
    success.value = 'Invoice payment recorded.'
    form.student_invoice_id = ''
    form.amount = ''
    form.paid_on = ''
    form.transaction_ref = ''
    form.notes = ''
    await loadWorkspace()
  }
  catch (saveError) {
    error.value = saveError instanceof Error ? saveError.message : 'Unable to record invoice payment.'
  }
  finally {
    saving.value = false
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
        :school-id="schoolId"
        aria-label="Invoice payments navigation"
        context-title="Collections links"
        :context-links="[
          { label: 'Finance', to: `/schools/${schoolId}/finance` },
          { label: 'Discounts', to: `/schools/${schoolId}/discounts` },
          { label: 'Reports', to: `/schools/${schoolId}/reports` },
        ]"
      />
    </template>

    <header class="workspace-header">
      <div>
        <p class="eyebrow">Finance</p>
        <h1>Invoice payments</h1>
        <p>Capture collected payments and keep invoice balances accurate in real time.</p>
      </div>
    </header>

    <section class="workspace-grid">
      <form class="surface record-form" @submit.prevent="recordPayment">
        <h2>Record payment</h2>
        <div class="field">
          <label for="invoice-payment-invoice">Invoice</label>
          <select id="invoice-payment-invoice" v-model="form.student_invoice_id" required>
            <option value="">Select invoice</option>
            <option v-for="invoice in invoices" :key="invoice.id" :value="invoice.id">
              {{ invoice.invoice_no }} / {{ invoice.total }} / {{ invoice.status }}
            </option>
          </select>
        </div>
        <div class="form-row">
          <div class="field"><label for="invoice-payment-amount">Amount</label><input id="invoice-payment-amount" v-model="form.amount" required type="number" min="0.01" step="0.01" /></div>
          <div class="field"><label for="invoice-payment-paid-on">Paid on</label><input id="invoice-payment-paid-on" v-model="form.paid_on" required type="date" /></div>
        </div>
        <div class="form-row">
          <div class="field">
            <label for="invoice-payment-method">Method</label>
            <select id="invoice-payment-method" v-model="form.payment_method">
              <option value="cash">Cash</option>
              <option value="bkash">bKash</option>
              <option value="nagad">Nagad</option>
              <option value="rocket">Rocket</option>
              <option value="bank_transfer">Bank transfer</option>
              <option value="card">Card</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="field"><label for="invoice-payment-transaction-ref">Transaction ref</label><input id="invoice-payment-transaction-ref" v-model="form.transaction_ref" type="text" /></div>
        </div>
        <div class="field"><label for="invoice-payment-notes">Notes</label><input id="invoice-payment-notes" v-model="form.notes" type="text" /></div>
        <VAlert v-if="error" type="error" variant="tonal">{{ error }}</VAlert>
        <VAlert v-if="success" type="success" variant="tonal">{{ success }}</VAlert>
        <VBtn color="primary" :loading="saving" type="submit">Record payment</VBtn>
      </form>

      <section class="surface record-list">
        <div class="list-header">
          <h2>Payment ledger</h2>
          <span class="muted">{{ loading ? 'Refreshing...' : `${payments.length} records` }}</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Invoice</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Paid on</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in payments" :key="payment.id">
                <td>{{ payment.student_invoice?.invoice_no || 'Unknown invoice' }}</td>
                <td>{{ payment.amount }}</td>
                <td>{{ payment.payment_method }}</td>
                <td>{{ payment.paid_on }}</td>
              </tr>
              <tr v-if="!payments.length && !loading">
                <td colspan="4">No payments recorded yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </SchoolWorkspaceTemplate>
</template>
