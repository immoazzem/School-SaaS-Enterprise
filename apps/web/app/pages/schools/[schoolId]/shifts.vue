<script setup lang="ts">
import type { Shift } from '~/composables/useApi'

interface ShiftsResponse {
  data: Shift[]
}

interface ShiftResponse {
  data: Shift
}

const api = useApi()
const route = useRoute()
const auth = useAuth()

const shifts = ref<Shift[]>([])
const loading = ref(false)
const saving = ref(false)
const archivingShiftId = ref<number | null>(null)
const error = ref('')
const success = ref('')
const editingShiftId = ref<number | null>(null)
const statusFilter = ref('active')
const search = ref('')
const form = reactive({
  name: '',
  code: '',
  starts_at: '',
  ends_at: '',
  description: '',
  sort_order: 0,
  status: 'active',
})

const schoolId = computed(() => Number(route.params.schoolId))
const activeCount = computed(() => shifts.value.filter((shift) => shift.status === 'active').length)

function formatTime(value: string | null) {
  return value ? value.slice(0, 5) : 'Unset'
}

async function loadShifts() {
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
    const response = await api.request<ShiftsResponse>(`/schools/${schoolId.value}/shifts${suffix}`)
    shifts.value = response.data
  } catch (shiftError) {
    error.value = shiftError instanceof Error ? shiftError.message : 'Unable to load shifts.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingShiftId.value = null
  form.name = ''
  form.code = ''
  form.starts_at = ''
  form.ends_at = ''
  form.description = ''
  form.sort_order = 0
  form.status = 'active'
}

function editShift(shift: Shift) {
  editingShiftId.value = shift.id
  form.name = shift.name
  form.code = shift.code
  form.starts_at = shift.starts_at ? shift.starts_at.slice(0, 5) : ''
  form.ends_at = shift.ends_at ? shift.ends_at.slice(0, 5) : ''
  form.description = shift.description || ''
  form.sort_order = shift.sort_order
  form.status = shift.status
}

async function saveShift() {
  saving.value = true
  error.value = ''
  success.value = ''

  const payload = {
    name: form.name,
    code: form.code,
    starts_at: form.starts_at || null,
    ends_at: form.ends_at || null,
    description: form.description || null,
    sort_order: Number(form.sort_order),
    status: form.status,
  }

  try {
    if (editingShiftId.value) {
      await api.request<ShiftResponse>(`/schools/${schoolId.value}/shifts/${editingShiftId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      success.value = 'Shift updated.'
    } else {
      await api.request<ShiftResponse>(`/schools/${schoolId.value}/shifts`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Shift saved.'
    }

    resetForm()
    await loadShifts()
  } catch (shiftError) {
    error.value = shiftError instanceof Error ? shiftError.message : 'Unable to save shift.'
  } finally {
    saving.value = false
  }
}

async function archiveShift(shift: Shift) {
  archivingShiftId.value = shift.id
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/shifts/${shift.id}`, {
      method: 'PATCH',
      body: { status: 'archived' },
    })
    success.value = 'Shift archived.'
    await loadShifts()
  } catch (shiftError) {
    error.value = shiftError instanceof Error ? shiftError.message : 'Unable to archive shift.'
  } finally {
    archivingShiftId.value = null
  }
}

async function chooseStatus(event: Event) {
  statusFilter.value = (event.target as HTMLSelectElement).value
  await loadShifts()
}

async function searchShifts() {
  await loadShifts()
}

onMounted(async () => {
  await auth.refreshProfile()
  await loadShifts()
})
</script>

<template>
  <main class="catalog-page">
    <header class="catalog-header">
      <div>
        <NuxtLink class="back-link" to="/dashboard">Dashboard</NuxtLink>
        <h1>Shifts</h1>
        <p>Maintain the daily schedule windows used by admissions, attendance, and timetable planning.</p>
      </div>

      <div class="header-actions">
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/timetable`">Timetable</NuxtLink>
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/student-groups`">Groups</NuxtLink>
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/subjects`">Subjects</NuxtLink>
      </div>
    </header>

    <section class="summary-grid">
      <article class="surface summary-item">
        <span>Visible shifts</span>
        <strong>{{ shifts.length }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Active shifts</span>
        <strong>{{ activeCount }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Filter</span>
        <strong>{{ statusFilter || 'All' }}</strong>
      </article>
    </section>

    <section class="catalog-grid">
      <form class="surface catalog-form" @submit.prevent="saveShift">
        <div>
          <p class="muted">{{ editingShiftId ? 'Edit shift' : 'New shift' }}</p>
          <h2>{{ editingShiftId ? 'Update shift' : 'Add shift' }}</h2>
        </div>

        <div class="field">
          <label for="shift-name">Name</label>
          <input id="shift-name" v-model="form.name" required placeholder="Morning Shift" />
        </div>

        <div class="form-row">
          <div class="field">
            <label for="shift-code">Code</label>
            <input id="shift-code" v-model="form.code" required placeholder="MOR" />
          </div>
          <div class="field">
            <label for="shift-order">Order</label>
            <input id="shift-order" v-model="form.sort_order" min="0" type="number" />
          </div>
        </div>

        <div class="form-row">
          <div class="field">
            <label for="shift-start">Starts</label>
            <input id="shift-start" v-model="form.starts_at" type="time" />
          </div>
          <div class="field">
            <label for="shift-end">Ends</label>
            <input id="shift-end" v-model="form.ends_at" type="time" />
          </div>
        </div>

        <div class="field">
          <label for="shift-description">Description</label>
          <textarea id="shift-description" v-model="form.description" rows="4" placeholder="Schedule notes" />
        </div>

        <div class="field">
          <label for="shift-status">Status</label>
          <select id="shift-status" v-model="form.status">
            <option value="active">Active</option>
            <option value="archived">Archived</option>
          </select>
        </div>

        <p v-if="error" class="error">{{ error }}</p>
        <p v-if="success" class="success">{{ success }}</p>

        <div class="form-actions">
          <button class="button" type="submit" :disabled="saving">
            {{ saving ? 'Saving' : editingShiftId ? 'Update shift' : 'Save shift' }}
          </button>
          <button v-if="editingShiftId" class="button secondary" type="button" @click="resetForm">Cancel</button>
        </div>
      </form>

      <section class="surface catalog-list">
        <div class="list-heading">
          <div>
            <p class="muted">Shift register</p>
            <h2>Shift catalog</h2>
          </div>

          <form class="filters" @submit.prevent="searchShifts">
            <input v-model="search" aria-label="Search shifts" placeholder="Search" />
            <select :value="statusFilter" aria-label="Status filter" @change="chooseStatus">
              <option value="">All status</option>
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
            <button class="button secondary" type="submit">Search</button>
          </form>
        </div>

        <p v-if="loading" class="muted">Loading shifts</p>

        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Shift</th>
                <th>Time</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="shift in shifts" :key="shift.id">
                <td>
                  <strong>{{ shift.name }}</strong>
                  <small>{{ shift.code }}</small>
                </td>
                <td>{{ formatTime(shift.starts_at) }} to {{ formatTime(shift.ends_at) }}</td>
                <td>{{ shift.status }}</td>
                <td>
                  <div class="row-actions">
                    <button class="text-button" type="button" @click="editShift(shift)">Edit</button>
                    <button
                      class="text-button"
                      type="button"
                      :disabled="shift.status === 'archived' || archivingShiftId === shift.id"
                      @click="archiveShift(shift)"
                    >
                      Archive
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="shifts.length === 0">
                <td colspan="4">No shifts yet.</td>
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
