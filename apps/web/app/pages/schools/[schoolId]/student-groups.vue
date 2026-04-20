<script setup lang="ts">
import type { StudentGroup } from '~/composables/useApi'

interface StudentGroupsResponse {
  data: StudentGroup[]
}

interface StudentGroupResponse {
  data: StudentGroup
}

const api = useApi()
const route = useRoute()
const auth = useAuth()

const groups = ref<StudentGroup[]>([])
const loading = ref(false)
const saving = ref(false)
const archivingGroupId = ref<number | null>(null)
const error = ref('')
const success = ref('')
const editingGroupId = ref<number | null>(null)
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
const activeCount = computed(() => groups.value.filter((group) => group.status === 'active').length)

async function loadGroups() {
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
    const response = await api.request<StudentGroupsResponse>(`/schools/${schoolId.value}/student-groups${suffix}`)
    groups.value = response.data
  } catch (groupError) {
    error.value = groupError instanceof Error ? groupError.message : 'Unable to load groups.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingGroupId.value = null
  form.name = ''
  form.code = ''
  form.description = ''
  form.sort_order = 0
  form.status = 'active'
}

function editGroup(group: StudentGroup) {
  editingGroupId.value = group.id
  form.name = group.name
  form.code = group.code
  form.description = group.description || ''
  form.sort_order = group.sort_order
  form.status = group.status
}

async function saveGroup() {
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
    if (editingGroupId.value) {
      await api.request<StudentGroupResponse>(`/schools/${schoolId.value}/student-groups/${editingGroupId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      success.value = 'Group updated.'
    } else {
      await api.request<StudentGroupResponse>(`/schools/${schoolId.value}/student-groups`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Group saved.'
    }

    resetForm()
    await loadGroups()
  } catch (groupError) {
    error.value = groupError instanceof Error ? groupError.message : 'Unable to save group.'
  } finally {
    saving.value = false
  }
}

async function archiveGroup(group: StudentGroup) {
  archivingGroupId.value = group.id
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/student-groups/${group.id}`, {
      method: 'PATCH',
      body: { status: 'archived' },
    })
    success.value = 'Group archived.'
    await loadGroups()
  } catch (groupError) {
    error.value = groupError instanceof Error ? groupError.message : 'Unable to archive group.'
  } finally {
    archivingGroupId.value = null
  }
}

async function chooseStatus(event: Event) {
  statusFilter.value = (event.target as HTMLSelectElement).value
  await loadGroups()
}

async function searchGroups() {
  await loadGroups()
}

onMounted(async () => {
  await auth.refreshProfile()
  await loadGroups()
})
</script>

<template>
  <main class="catalog-page">
    <header class="catalog-header">
      <div>
        <NuxtLink class="back-link" to="/dashboard">Dashboard</NuxtLink>
        <h1>Student Groups</h1>
        <p>Organize learners into academic tracks used by admissions, class planning, and reporting.</p>
      </div>

      <div class="header-actions">
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/subjects`">Subjects</NuxtLink>
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/shifts`">Shifts</NuxtLink>
      </div>
    </header>

    <section class="summary-grid">
      <article class="surface summary-item">
        <span>Visible groups</span>
        <strong>{{ groups.length }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Active groups</span>
        <strong>{{ activeCount }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Filter</span>
        <strong>{{ statusFilter || 'All' }}</strong>
      </article>
    </section>

    <section class="catalog-grid">
      <form class="surface catalog-form" @submit.prevent="saveGroup">
        <div>
          <p class="muted">{{ editingGroupId ? 'Edit group' : 'New group' }}</p>
          <h2>{{ editingGroupId ? 'Update group' : 'Add group' }}</h2>
        </div>

        <div class="field">
          <label for="group-name">Name</label>
          <input id="group-name" v-model="form.name" required placeholder="Science Group" />
        </div>

        <div class="form-row">
          <div class="field">
            <label for="group-code">Code</label>
            <input id="group-code" v-model="form.code" required placeholder="SCI" />
          </div>
          <div class="field">
            <label for="group-order">Order</label>
            <input id="group-order" v-model="form.sort_order" min="0" type="number" />
          </div>
        </div>

        <div class="field">
          <label for="group-description">Description</label>
          <textarea id="group-description" v-model="form.description" rows="4" placeholder="Group notes" />
        </div>

        <div class="field">
          <label for="group-status">Status</label>
          <select id="group-status" v-model="form.status">
            <option value="active">Active</option>
            <option value="archived">Archived</option>
          </select>
        </div>

        <p v-if="error" class="error">{{ error }}</p>
        <p v-if="success" class="success">{{ success }}</p>

        <div class="form-actions">
          <button class="button" type="submit" :disabled="saving">
            {{ saving ? 'Saving' : editingGroupId ? 'Update group' : 'Save group' }}
          </button>
          <button v-if="editingGroupId" class="button secondary" type="button" @click="resetForm">Cancel</button>
        </div>
      </form>

      <section class="surface catalog-list">
        <div class="list-heading">
          <div>
            <p class="muted">Group register</p>
            <h2>Student group catalog</h2>
          </div>

          <form class="filters" @submit.prevent="searchGroups">
            <input v-model="search" aria-label="Search groups" placeholder="Search" />
            <select :value="statusFilter" aria-label="Status filter" @change="chooseStatus">
              <option value="">All status</option>
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
            <button class="button secondary" type="submit">Search</button>
          </form>
        </div>

        <p v-if="loading" class="muted">Loading groups</p>

        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Group</th>
                <th>Status</th>
                <th>Order</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="group in groups" :key="group.id">
                <td>
                  <strong>{{ group.name }}</strong>
                  <small>{{ group.code }}</small>
                </td>
                <td>{{ group.status }}</td>
                <td>{{ group.sort_order }}</td>
                <td>
                  <div class="row-actions">
                    <button class="text-button" type="button" @click="editGroup(group)">Edit</button>
                    <button
                      class="text-button"
                      type="button"
                      :disabled="group.status === 'archived' || archivingGroupId === group.id"
                      @click="archiveGroup(group)"
                    >
                      Archive
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="groups.length === 0">
                <td colspan="4">No groups yet.</td>
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

.summary-item {
  padding: 18px;
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
.catalog-list {
  padding: 22px;
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
