<script setup lang="ts">
import type { Designation } from '~/composables/useApi'

interface DesignationsResponse {
  data: Designation[]
}

interface DesignationResponse {
  data: Designation
}

const api = useApi()
const route = useRoute()
const auth = useAuth()

const designations = ref<Designation[]>([])
const loading = ref(false)
const saving = ref(false)
const archivingDesignationId = ref<number | null>(null)
const error = ref('')
const success = ref('')
const editingDesignationId = ref<number | null>(null)
const statusFilter = ref('active')
const search = ref('')
const form = reactive({
  name: '',
  code: '',
  description: '',
  sort_order: 0,
  status: 'active',
})

const schoolId = computed(() => Number(route.params.schoolId))
const activeCount = computed(() => designations.value.filter((designation) => designation.status === 'active').length)

async function loadDesignations() {
  loading.value = true
  error.value = ''

  try {
    const query = new URLSearchParams()
    query.set('per_page', '100')

    if (statusFilter.value) {
      query.set('status', statusFilter.value)
    }

    if (search.value.trim()) {
      query.set('search', search.value.trim())
    }

    const suffix = query.toString() ? `?${query.toString()}` : ''
    const response = await api.request<DesignationsResponse>(`/schools/${schoolId.value}/designations${suffix}`)
    designations.value = response.data
  } catch (designationError) {
    error.value = designationError instanceof Error ? designationError.message : 'Unable to load designations.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingDesignationId.value = null
  form.name = ''
  form.code = ''
  form.description = ''
  form.sort_order = 0
  form.status = 'active'
}

function editDesignation(designation: Designation) {
  editingDesignationId.value = designation.id
  form.name = designation.name
  form.code = designation.code
  form.description = designation.description || ''
  form.sort_order = designation.sort_order
  form.status = designation.status
}

async function saveDesignation() {
  saving.value = true
  error.value = ''
  success.value = ''

  const payload = {
    name: form.name,
    code: form.code,
    description: form.description || null,
    sort_order: Number(form.sort_order),
    status: form.status,
  }

  try {
    if (editingDesignationId.value) {
      await api.request<DesignationResponse>(`/schools/${schoolId.value}/designations/${editingDesignationId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      success.value = 'Designation updated.'
    } else {
      await api.request<DesignationResponse>(`/schools/${schoolId.value}/designations`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Designation saved.'
    }

    resetForm()
    await loadDesignations()
  } catch (designationError) {
    error.value = designationError instanceof Error ? designationError.message : 'Unable to save designation.'
  } finally {
    saving.value = false
  }
}

async function archiveDesignation(designation: Designation) {
  archivingDesignationId.value = designation.id
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/designations/${designation.id}`, {
      method: 'PATCH',
      body: { status: 'archived' },
    })
    success.value = 'Designation archived.'
    await loadDesignations()
  } catch (designationError) {
    error.value = designationError instanceof Error ? designationError.message : 'Unable to archive designation.'
  } finally {
    archivingDesignationId.value = null
  }
}

async function chooseStatus(event: Event) {
  statusFilter.value = (event.target as HTMLSelectElement).value
  await loadDesignations()
}

async function searchDesignations() {
  await loadDesignations()
}

onMounted(async () => {
  await auth.refreshProfile()
  await loadDesignations()
})
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Designations navigation"
      context-title="People setup"
      :context-links="[
        { label: 'Employees', to: `/schools/${schoolId}/employees` },
        { label: 'Student Groups', to: `/schools/${schoolId}/student-groups` },
        { label: 'Shifts', to: `/schools/${schoolId}/shifts` },
      ]"
    />
    </template>

    <header class="workspace-header">
        <div>
          <p class="eyebrow">People</p>
          <h1>Designations</h1>
          <p>Maintain the staff titles used by teachers, employees, payroll, and reporting.</p>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <section class="summary-grid">
        <article class="surface summary-item">
          <span>Visible titles</span>
          <strong>{{ designations.length }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Active titles</span>
          <strong>{{ activeCount }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Filter</span>
          <strong>{{ statusFilter || 'All' }}</strong>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="saveDesignation">
          <div>
            <p class="muted">{{ editingDesignationId ? 'Edit designation' : 'New designation' }}</p>
            <h2>{{ editingDesignationId ? 'Update designation' : 'Add designation' }}</h2>
          </div>

          <div class="field">
            <label for="designation-name">Name</label>
            <input id="designation-name" v-model="form.name" required placeholder="Senior Teacher" />
          </div>

          <div class="form-row">
            <div class="field">
              <label for="designation-code">Code</label>
              <input id="designation-code" v-model="form.code" required placeholder="SNR-TCHR" />
            </div>
            <div class="field">
              <label for="designation-order">Order</label>
              <input id="designation-order" v-model="form.sort_order" min="0" type="number" />
            </div>
          </div>

          <div class="field">
            <label for="designation-description">Description</label>
            <textarea id="designation-description" v-model="form.description" rows="4" placeholder="Role notes" />
          </div>

          <div class="field">
            <label for="designation-status">Status</label>
            <select id="designation-status" v-model="form.status">
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
          </div>

          <p v-if="error" class="error">{{ error }}</p>
          <p v-if="success" class="success">{{ success }}</p>

          <div class="form-actions">
            <button class="button" type="submit" :disabled="saving">
              {{ saving ? 'Saving' : editingDesignationId ? 'Update designation' : 'Save designation' }}
            </button>
            <button v-if="editingDesignationId" class="button secondary" type="button" @click="resetForm">Cancel</button>
          </div>
        </form>

        <section class="surface record-list">
          <div class="list-header">
            <div>
              <p class="muted">People setup</p>
              <h2>Designation catalog</h2>
            </div>
            <form class="filters" @submit.prevent="searchDesignations">
              <input v-model="search" aria-label="Search designations" placeholder="Search" />
              <select :value="statusFilter" aria-label="Status filter" @change="chooseStatus">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
              <button class="button secondary" type="submit">Search</button>
            </form>
          </div>

          <p v-if="loading" class="muted">Loading designations</p>

          <div v-else class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Designation</th>
                  <th>Status</th>
                  <th>Order</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="designation in designations" :key="designation.id">
                  <td>
                    <strong>{{ designation.name }}</strong>
                    <small>{{ designation.code }}</small>
                  </td>
                  <td>{{ designation.status }}</td>
                  <td>{{ designation.sort_order }}</td>
                  <td>
                    <div class="row-actions">
                      <button class="text-button" type="button" @click="editDesignation(designation)">Edit</button>
                      <button class="text-button" type="button" :disabled="designation.status === 'archived' || archivingDesignationId === designation.id" @click="archiveDesignation(designation)">Archive</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="designations.length === 0">
                  <td colspan="4">No designations yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>
</SchoolWorkspaceTemplate>
</template>


