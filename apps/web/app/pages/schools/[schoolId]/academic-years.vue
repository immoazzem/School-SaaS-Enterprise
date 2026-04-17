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
  <main class="years-page">
    <header class="years-header">
      <div>
        <NuxtLink class="back-link" to="/dashboard">Dashboard</NuxtLink>
        <h1>Academic Years</h1>
        <p>Define school-year calendars, set the active year, and keep archived years available for records.</p>
      </div>

      <div class="header-actions">
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/academic-classes`">
          Classes
        </NuxtLink>
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/academic-sections`">
          Sections
        </NuxtLink>
      </div>
    </header>

    <section class="year-summary">
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

    <section class="years-grid">
      <form class="surface year-form" @submit.prevent="saveYear">
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
          <button v-if="editingYearId" class="button secondary" type="button" @click="resetForm">
            Cancel
          </button>
        </div>
      </form>

      <section class="surface year-list">
        <div class="list-heading">
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
                    <button
                      class="text-button"
                      type="button"
                      :disabled="year.is_current"
                      @click="makeCurrent(year)"
                    >
                      Set current
                    </button>
                    <button
                      class="text-button"
                      type="button"
                      :disabled="year.status === 'archived' || deletingYearId === year.id"
                      @click="archiveYear(year)"
                    >
                      Archive
                    </button>
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
  </main>
</template>

<style scoped>
.years-page {
  min-height: 100vh;
  padding: 30px;
  background: #f6f8f7;
}

.years-header {
  display: flex;
  gap: 20px;
  align-items: end;
  justify-content: space-between;
  margin-bottom: 20px;
}

.back-link {
  color: #0f5f4a;
  font-weight: 800;
}

h1 {
  margin: 12px 0 0;
  color: #16201c;
  font-size: clamp(2.1rem, 5.8vw, 4.4rem);
  line-height: 0.96;
}

.years-header p {
  max-width: 720px;
  margin: 16px 0 0;
  color: #607169;
}

.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.year-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 18px;
}

.summary-item {
  display: grid;
  gap: 10px;
  padding: 18px;
}

.summary-item span {
  color: #607169;
  font-weight: 700;
}

.summary-item strong {
  color: #16201c;
  font-size: 1.55rem;
}

.years-grid {
  display: grid;
  grid-template-columns: minmax(300px, 390px) minmax(0, 1fr);
  gap: 18px;
}

.year-form,
.year-list {
  padding: 22px;
}

.year-form {
  display: grid;
  align-content: start;
  gap: 16px;
}

.year-form h2,
.list-heading h2 {
  margin: 0;
  color: #16201c;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.check-field {
  display: flex;
  min-height: 46px;
  align-items: center;
  gap: 10px;
  color: #40524a;
  font-weight: 800;
}

.check-field input {
  width: 18px;
  height: 18px;
  accent-color: #0f5f4a;
}

.check-field.compact {
  min-height: 40px;
  font-size: 0.88rem;
  white-space: nowrap;
}

.form-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.list-heading {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  align-items: center;
  margin-bottom: 18px;
}

.filters {
  display: flex;
  gap: 12px;
  align-items: center;
}

.filters select {
  min-height: 42px;
  border: 1px solid #cbdad4;
  border-radius: 8px;
  padding: 0 12px;
  background: #fff;
  color: #17231e;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  min-width: 820px;
  border-collapse: collapse;
}

th,
td {
  border-bottom: 1px solid #e1e9e5;
  padding: 14px 10px;
  color: #26332e;
  text-align: left;
}

th {
  color: #607169;
  font-size: 0.84rem;
  text-transform: uppercase;
}

td strong,
td small {
  display: block;
}

td small {
  margin-top: 4px;
  color: #607169;
}

.status-pill {
  display: inline-flex;
  min-height: 30px;
  align-items: center;
  border: 1px solid #d6e1dc;
  border-radius: 8px;
  padding: 0 10px;
  color: #607169;
  font-weight: 800;
}

.status-pill.current {
  border-color: #0f5f4a;
  background: #eef7f3;
  color: #0f5f4a;
}

.row-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.text-button {
  border: 0;
  background: transparent;
  color: #0f5f4a;
  cursor: pointer;
  font-weight: 800;
}

.text-button:disabled {
  color: #91a29b;
  cursor: not-allowed;
}

@media (max-width: 960px) {
  .years-page {
    padding: 22px;
  }

  .years-header,
  .header-actions,
  .form-actions,
  .filters {
    align-items: stretch;
    flex-direction: column;
  }

  .year-summary,
  .years-grid,
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
