<script setup lang="ts">
import type { AcademicYear } from '~/composables/useApi'

interface YearsResponse {
  data: AcademicYear[]
}

interface YearResponse {
  data: AcademicYear
}

const api = useApi()
const route = useRoute()
const auth = useAuth()

const years = ref<AcademicYear[]>([])
const loading = ref(false)
const saving = ref(false)
const deletingYearId = ref<number | null>(null)
const error = ref('')
const success = ref('')
const editingYearId = ref<number | null>(null)
const statusFilter = ref('active')
const currentOnly = ref(false)
const form = reactive({
  name: '',
  code: '',
  starts_on: '',
  ends_on: '',
  is_current: false,
  status: 'active',
})

const schoolId = computed(() => Number(route.params.schoolId))
const currentYear = computed(() => years.value.find((year) => year.is_current))

async function loadYears() {
  loading.value = true
  error.value = ''

  try {
    const query = new URLSearchParams()
    query.set('per_page', '100')

    if (statusFilter.value) {
      query.set('status', statusFilter.value)
    }

    if (currentOnly.value) {
      query.set('is_current', '1')
    }

    const suffix = query.toString() ? `?${query.toString()}` : ''
    const response = await api.request<YearsResponse>(`/schools/${schoolId.value}/academic-years${suffix}`)
    years.value = response.data
  } catch (yearError) {
    error.value = yearError instanceof Error ? yearError.message : 'Unable to load academic years.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingYearId.value = null
  form.name = ''
  form.code = ''
  form.starts_on = ''
  form.ends_on = ''
  form.is_current = false
  form.status = 'active'
}

function editYear(year: AcademicYear) {
  editingYearId.value = year.id
  form.name = year.name
  form.code = year.code
  form.starts_on = year.starts_on
  form.ends_on = year.ends_on
  form.is_current = year.is_current
  form.status = year.status
}

async function saveYear() {
  saving.value = true
  error.value = ''
  success.value = ''

  const payload = {
    name: form.name,
    code: form.code,
    starts_on: form.starts_on,
    ends_on: form.ends_on,
    is_current: form.is_current,
    status: form.status,
  }

  try {
    if (editingYearId.value) {
      await api.request<YearResponse>(`/schools/${schoolId.value}/academic-years/${editingYearId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      success.value = 'Academic year updated.'
    } else {
      await api.request<YearResponse>(`/schools/${schoolId.value}/academic-years`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Academic year saved.'
    }

    resetForm()
    await loadYears()
  } catch (yearError) {
    error.value = yearError instanceof Error ? yearError.message : 'Unable to save academic year.'
  } finally {
    saving.value = false
  }
}

async function makeCurrent(year: AcademicYear) {
  error.value = ''
  success.value = ''

  try {
    await api.request<YearResponse>(`/schools/${schoolId.value}/academic-years/${year.id}`, {
      method: 'PATCH',
      body: { is_current: true },
    })
    success.value = `${year.name} is now current.`
    await loadYears()
  } catch (yearError) {
    error.value = yearError instanceof Error ? yearError.message : 'Unable to update current year.'
  }
}

async function archiveYear(year: AcademicYear) {
  deletingYearId.value = year.id
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/academic-years/${year.id}`, {
      method: 'PATCH',
      body: { status: 'archived' },
    })
    success.value = 'Academic year archived.'
    await loadYears()
  } catch (yearError) {
    error.value = yearError instanceof Error ? yearError.message : 'Unable to archive academic year.'
  } finally {
    deletingYearId.value = null
  }
}

async function chooseStatus(event: Event) {
  statusFilter.value = (event.target as HTMLSelectElement).value
  await loadYears()
}

async function toggleCurrentOnly(event: Event) {
  currentOnly.value = (event.target as HTMLInputElement).checked
  await loadYears()
}

onMounted(async () => {
  await auth.refreshProfile()
  await loadYears()
})
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Academic years navigation"
      context-title="Academics setup"
      :context-links="[
        { label: 'Classes', to: `/schools/${schoolId}/academic-classes` },
        { label: 'Sections', to: `/schools/${schoolId}/academic-sections` },
        { label: 'Subjects', to: `/schools/${schoolId}/subjects` },
      ]"
    />
    </template>

    <header class="workspace-header">
        <div>
          <p class="eyebrow">Academics</p>
          <h1>Academic Years</h1>
          <p>Define school-year calendars, set the active year, and keep archived years available for records.</p>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <section class="summary-grid">
        <article class="surface summary-item">
          <span>Visible years</span>
          <strong>{{ years.length }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Current year</span>
          <strong>{{ currentYear?.code || 'Unset' }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Filter</span>
          <strong>{{ currentOnly ? 'Current' : statusFilter || 'All' }}</strong>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="saveYear">
          <div>
            <p class="muted">{{ editingYearId ? 'Edit calendar' : 'New calendar' }}</p>
            <h2>{{ editingYearId ? 'Update academic year' : 'Add academic year' }}</h2>
          </div>

          <div class="field">
            <label for="year-name">Name</label>
            <input id="year-name" v-model="form.name" required placeholder="2026 Academic Year" />
          </div>

          <div class="field">
            <label for="year-code">Code</label>
            <input id="year-code" v-model="form.code" required placeholder="2026" />
          </div>

          <div class="form-row">
            <div class="field">
              <label for="starts-on">Starts on</label>
              <input id="starts-on" v-model="form.starts_on" required type="date" />
            </div>
            <div class="field">
              <label for="ends-on">Ends on</label>
              <input id="ends-on" v-model="form.ends_on" required type="date" />
            </div>
          </div>

          <div class="form-row">
            <label class="check-field" for="is-current">
              <input id="is-current" v-model="form.is_current" type="checkbox" />
              <span>Set as current</span>
            </label>
            <div class="field">
              <label for="year-status">Status</label>
              <select id="year-status" v-model="form.status">
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>

          <p v-if="error" class="error">{{ error }}</p>
          <p v-if="success" class="success">{{ success }}</p>

          <div class="form-actions">
            <button class="button" type="submit" :disabled="saving">
              {{ saving ? 'Saving' : editingYearId ? 'Update year' : 'Save year' }}
            </button>
            <button v-if="editingYearId" class="button secondary" type="button" @click="resetForm">Cancel</button>
          </div>
        </form>

        <section class="surface record-list">
          <div class="list-header">
            <div>
              <p class="muted">Calendar register</p>
              <h2>Academic years</h2>
            </div>
            <div class="filters">
              <label class="check-field compact" for="current-only">
                <input id="current-only" :checked="currentOnly" type="checkbox" @change="toggleCurrentOnly" />
                <span>Current only</span>
              </label>
              <select :value="statusFilter" aria-label="Status filter" @change="chooseStatus">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>

          <p v-if="loading" class="muted">Loading academic years</p>

          <div v-else class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Year</th>
                  <th>Dates</th>
                  <th>Current</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="year in years" :key="year.id">
                  <td>
                    <strong>{{ year.name }}</strong>
                    <small>{{ year.code }}</small>
                  </td>
                  <td>
                    <strong>{{ year.starts_on }}</strong>
                    <small>to {{ year.ends_on }}</small>
                  </td>
                  <td>
                    <span class="status-pill" :class="{ current: year.is_current }">
                      {{ year.is_current ? 'Current' : 'Available' }}
                    </span>
                  </td>
                  <td>{{ year.status }}</td>
                  <td>
                    <div class="row-actions">
                      <button class="text-button" type="button" @click="editYear(year)">Edit</button>
                      <button class="text-button" type="button" :disabled="year.is_current" @click="makeCurrent(year)">Set current</button>
                      <button class="text-button" type="button" :disabled="year.status === 'archived' || deletingYearId === year.id" @click="archiveYear(year)">Archive</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="years.length === 0">
                  <td colspan="5">No academic years yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>
</SchoolWorkspaceTemplate>
</template>


