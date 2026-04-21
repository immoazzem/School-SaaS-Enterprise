<script setup lang="ts">
import type { PaymentGatewayConfig } from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface ItemResponse<T> {
  data: T
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const configs = ref<PaymentGatewayConfig[]>([])
const loading = ref(false)
const saving = ref(false)
const deletingId = ref<number | null>(null)
const editingId = ref<number | null>(null)
const error = ref('')
const success = ref('')

const gatewayOptions = [
  { value: 'bkash', label: 'bKash' },
  { value: 'nagad', label: 'Nagad' },
  { value: 'sslcommerz', label: 'SSLCommerz' },
  { value: 'stripe', label: 'Stripe' },
] as const

const gatewayForm = reactive({
  gateway: 'bkash',
  merchant_id: '',
  public_key: '',
  secret_key: '',
  is_active: true,
  test_mode: true,
})

const activeCount = computed(() => configs.value.filter((config) => config.is_active).length)
const testModeCount = computed(() => configs.value.filter((config) => config.test_mode).length)
const liveModeCount = computed(() => configs.value.filter((config) => !config.test_mode).length)

function gatewayLabel(gateway: PaymentGatewayConfig['gateway'] | string) {
  return gatewayOptions.find((option) => option.value === gateway)?.label ?? gateway
}

function resetForm() {
  editingId.value = null
  gatewayForm.gateway = 'bkash'
  gatewayForm.merchant_id = ''
  gatewayForm.public_key = ''
  gatewayForm.secret_key = ''
  gatewayForm.is_active = true
  gatewayForm.test_mode = true
}

function editConfig(config: PaymentGatewayConfig) {
  editingId.value = config.id
  gatewayForm.gateway = config.gateway
  gatewayForm.merchant_id = ''
  gatewayForm.public_key = ''
  gatewayForm.secret_key = ''
  gatewayForm.is_active = config.is_active
  gatewayForm.test_mode = config.test_mode
}

function credentialPayload() {
  const credentials: Record<string, string> = {}

  if (gatewayForm.merchant_id.trim()) {
    credentials.merchant_id = gatewayForm.merchant_id.trim()
  }

  if (gatewayForm.public_key.trim()) {
    credentials.public_key = gatewayForm.public_key.trim()
  }

  if (gatewayForm.secret_key.trim()) {
    credentials.secret_key = gatewayForm.secret_key.trim()
  }

  return credentials
}

function gatewayPayload() {
  const credentials = credentialPayload()
  const payload: Record<string, unknown> = {
    gateway: gatewayForm.gateway,
    is_active: gatewayForm.is_active,
    test_mode: gatewayForm.test_mode,
  }

  if (Object.keys(credentials).length > 0) {
    payload.credentials = credentials
  }

  return payload
}

async function loadConfigs() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.request<ListResponse<PaymentGatewayConfig>>(
      `/schools/${schoolId.value}/payment-gateway-configs?per_page=100`,
    )
    configs.value = response.data
  } catch (configError) {
    error.value = configError instanceof Error ? configError.message : 'Unable to load payment gateway configs.'
  } finally {
    loading.value = false
  }
}

async function saveConfig() {
  saving.value = true
  error.value = ''
  success.value = ''

  try {
    if (editingId.value) {
      await api.request<ItemResponse<PaymentGatewayConfig>>(
        `/schools/${schoolId.value}/payment-gateway-configs/${editingId.value}`,
        {
          method: 'PATCH',
          body: gatewayPayload(),
        },
      )
      success.value = 'Gateway config updated.'
    } else {
      await api.request<ItemResponse<PaymentGatewayConfig>>(`/schools/${schoolId.value}/payment-gateway-configs`, {
        method: 'POST',
        body: {
          ...gatewayPayload(),
          credentials: credentialPayload(),
        },
      })
      success.value = 'Gateway config saved.'
    }

    resetForm()
    await loadConfigs()
  } catch (saveError) {
    error.value = saveError instanceof Error ? saveError.message : 'Unable to save gateway config.'
  } finally {
    saving.value = false
  }
}

async function deleteConfig(config: PaymentGatewayConfig) {
  deletingId.value = config.id
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/payment-gateway-configs/${config.id}`, {
      method: 'DELETE',
    })
    success.value = `${gatewayLabel(config.gateway)} config removed.`
    await loadConfigs()
  } catch (deleteError) {
    error.value = deleteError instanceof Error ? deleteError.message : 'Unable to remove gateway config.'
  } finally {
    deletingId.value = null
  }
}

onMounted(loadConfigs)
</script>

<template>
  <main class="operation-shell">
    <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Payment gateway navigation"
      context-title="Payment tools"
      :context-links="[
        { label: 'Finance', to: `/schools/${schoolId}/finance` },
        { label: 'Reports', to: `/schools/${schoolId}/reports` },
        { label: 'Documents', to: `/schools/${schoolId}/documents` },
      ]"
    />

    <section class="operation-workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">Payment gateways</p>
          <h1>Connect collection channels without exposing secrets.</h1>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading" class="muted">Loading payment gateway configs</p>

      <section class="summary-grid">
        <article class="summary-item surface">
          <span>Configured</span>
          <strong>{{ configs.length }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Active</span>
          <strong>{{ activeCount }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Live mode</span>
          <strong>{{ liveModeCount }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Test mode</span>
          <strong>{{ testModeCount }}</strong>
        </article>
      </section>

      <section class="workspace-grid gateway-grid">
        <form class="record-form surface" @submit.prevent="saveConfig">
          <div>
            <p class="eyebrow">{{ editingId ? 'Rotate config' : 'New config' }}</p>
            <h2>{{ editingId ? 'Update gateway' : 'Add gateway' }}</h2>
            <p>Credentials are write-only. Saved values stay encrypted and hidden.</p>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="gateway-name">Gateway</label>
              <select id="gateway-name" v-model="gatewayForm.gateway" :disabled="Boolean(editingId)" required>
                <option v-for="gateway in gatewayOptions" :key="gateway.value" :value="gateway.value">
                  {{ gateway.label }}
                </option>
              </select>
            </div>
            <div class="field">
              <label for="merchant-id">Merchant / store ID</label>
              <input id="merchant-id" v-model="gatewayForm.merchant_id" :required="!editingId" placeholder="Merchant or store identifier" />
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="public-key">App / public key</label>
              <input id="public-key" v-model="gatewayForm.public_key" placeholder="Optional public key" />
            </div>
            <div class="field">
              <label for="secret-key">Secret / password</label>
              <input id="secret-key" v-model="gatewayForm.secret_key" :required="!editingId" type="password" placeholder="Never displayed after save" />
            </div>
          </div>

          <div class="form-row compact-row">
            <label class="check-field">
              <input v-model="gatewayForm.is_active" type="checkbox" />
              Active
            </label>
            <label class="check-field">
              <input v-model="gatewayForm.test_mode" type="checkbox" />
              Test mode
            </label>
          </div>

          <div class="strip-actions">
            <button class="button" type="submit" :disabled="saving">
              {{ saving ? 'Saving' : editingId ? 'Update config' : 'Save config' }}
            </button>
            <button v-if="editingId" class="button secondary" type="button" @click="resetForm">Cancel</button>
          </div>
        </form>

        <section class="record-list surface">
          <div class="list-header">
            <div>
              <p class="eyebrow">Gateway register</p>
              <h2>{{ configs.length }} configured channels</h2>
            </div>
          </div>

          <div class="gateway-stack">
            <article v-for="config in configs" :key="config.id" class="gateway-row">
              <div>
                <strong>{{ gatewayLabel(config.gateway) }}</strong>
                <span>{{ config.is_active ? 'Active' : 'Inactive' }} / {{ config.test_mode ? 'Test mode' : 'Live mode' }}</span>
                <small>
                  Keys:
                  {{ config.credential_keys.length ? config.credential_keys.join(', ') : 'none' }}
                </small>
              </div>
              <div class="row-actions">
                <span class="status-pill">{{ config.credentials_configured ? 'Encrypted' : 'Missing credentials' }}</span>
                <button class="text-button" type="button" @click="editConfig(config)">Edit</button>
                <button class="text-button" type="button" :disabled="deletingId === config.id" @click="deleteConfig(config)">
                  Remove
                </button>
              </div>
            </article>
            <p v-if="configs.length === 0" class="muted">No gateway configs yet.</p>
          </div>
        </section>
      </section>
    </section>
  </main>
</template>


