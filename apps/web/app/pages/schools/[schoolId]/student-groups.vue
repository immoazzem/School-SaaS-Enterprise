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
  <main class="operation-shell">
    <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Student groups navigation"
      context-title="People setup"
      :context-links="[
        { label: 'Subjects', to: `/schools/${schoolId}/subjects` },
        { label: 'Shifts', to: `/schools/${schoolId}/shifts` },
        { label: 'Students', to: `/schools/${schoolId}/students` },
        { label: 'Enrollments', to: `/schools/${schoolId}/enrollments` },
      ]"
    />

    <section class="operation-workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">People</p>
          <h1>Student Groups</h1>
          <p>Organize learners into academic tracks used by admissions, class planning, and reporting.</p>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
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

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="saveGroup">
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

        <section class="surface record-list">
          <div class="list-header">
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
                      <button class="text-button" type="button" :disabled="group.status === 'archived' || archivingGroupId === group.id" @click="archiveGroup(group)">Archive</button>
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
    </section>
  </main>
</template>


