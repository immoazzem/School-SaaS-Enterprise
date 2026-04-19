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
  <main class="catalog-page">
    <header class="catalog-header">
      <div>
        <NuxtLink class="back-link" to="/dashboard">Dashboard</NuxtLink>
        <h1>Designations</h1>
        <p>Maintain the staff titles used by teachers, employees, payroll, and reporting.</p>
      </div>

      <div class="header-actions">
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/student-groups`">Groups</NuxtLink>
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/shifts`">Shifts</NuxtLink>
      </div>
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

    <section class="catalog-grid">
      <form class="surface catalog-form" @submit.prevent="saveDesignation">
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

      <section class="surface catalog-list">
        <div class="list-heading">
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
                    <button
                      class="text-button"
                      type="button"
                      :disabled="designation.status === 'archived' || archivingDesignationId === designation.id"
                      @click="archiveDesignation(designation)"
                    >
                      Archive
                    </button>
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
  </main>
</template>

<style scoped>
.catalog-page {
  min-height: 100vh;
  padding: 30px;
  background: #f7f3ef;
}

.catalog-header,
.list-heading,
.form-row,
.form-actions,
.row-actions,
.filters,
.header-actions {
  display: flex;
  gap: 14px;
}

.catalog-header {
  align-items: end;
  justify-content: space-between;
  margin-bottom: 20px;
}

.list-heading,
.form-actions,
.header-actions {
  align-items: center;
  justify-content: space-between;
}

.back-link,
.text-button {
  color: #be3455;
  font-weight: 800;
}

h1 {
  margin: 12px 0 0;
  color: #111827;
  font-size: clamp(2.1rem, 5.8vw, 4.4rem);
  line-height: 0.95;
}

h2 {
  margin: 0;
  color: #111827;
}

p {
  color: #56635e;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 18px;
}

.surface {
  border: 1px solid #dfe6e2;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 18px 45px rgb(24 44 35 / 8%);
}

.summary-item,
.catalog-form,
.catalog-list {
  padding: 22px;
}

.summary-item span,
.muted,
small {
  color: #6a7771;
}

.summary-item strong {
  display: block;
  margin-top: 8px;
  color: #111827;
  font-size: 1.8rem;
}

.catalog-grid {
  display: grid;
  grid-template-columns: minmax(280px, 380px) minmax(0, 1fr);
  gap: 18px;
  align-items: start;
}

.catalog-form,
.field {
  display: grid;
  gap: 12px;
}

.form-row > * {
  flex: 1;
}

label {
  color: #26342f;
  font-weight: 800;
}

input,
select,
textarea {
  width: 100%;
  border: 1px solid #ccd8d2;
  border-radius: 8px;
  padding: 12px 13px;
  color: #111827;
  background: #ffffff;
}

.button {
  border: 0;
  border-radius: 8px;
  padding: 11px 16px;
  color: #ffffff;
  background: #163f34;
  font-weight: 800;
  cursor: pointer;
}

.button.secondary {
  border: 1px solid #cbd8d2;
  color: #163f34;
  background: #ffffff;
}

.button:disabled,
.text-button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.filters {
  flex-wrap: wrap;
  justify-content: flex-end;
}

.filters input,
.filters select {
  width: auto;
  min-width: 130px;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 620px;
}

th,
td {
  border-bottom: 1px solid #e5ece8;
  padding: 14px 12px;
  text-align: left;
}

th {
  color: #6a7771;
  font-size: 0.76rem;
  text-transform: uppercase;
}

td strong,
td small {
  display: block;
}

.text-button {
  border: 0;
  padding: 0;
  background: transparent;
  cursor: pointer;
}

.error,
.success {
  border-radius: 8px;
  padding: 12px;
}

.error {
  color: #9b1c1c;
  background: #fde8e8;
}

.success {
  color: #be3455;
  background: #e1f3ec;
}

@media (max-width: 900px) {
  .catalog-page {
    padding: 18px;
  }

  .catalog-header,
  .list-heading,
  .form-row,
  .header-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .summary-grid,
  .catalog-grid {
    grid-template-columns: 1fr;
  }

  .filters,
  .filters input,
  .filters select {
    width: 100%;
  }
}
</style>
