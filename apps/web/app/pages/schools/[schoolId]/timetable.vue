<script setup lang="ts">
import type { AcademicClass, AcademicYear, Shift, Subject, TimetablePeriod } from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface ItemResponse<T> {
  data: T
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const academicYears = ref<AcademicYear[]>([])
const academicClasses = ref<AcademicClass[]>([])
const shifts = ref<Shift[]>([])
const subjects = ref<Subject[]>([])
const periods = ref<TimetablePeriod[]>([])
const loading = ref(false)
const saving = ref(false)
const archivingPeriodId = ref<number | null>(null)
const editingPeriodId = ref<number | null>(null)
const error = ref('')
const success = ref('')

const filters = reactive({
  academic_year_id: '',
  academic_class_id: '',
  shift_id: '',
  day_of_week: '',
  status: 'active',
})

const form = reactive({
  academic_year_id: '',
  academic_class_id: '',
  shift_id: '',
  day_of_week: '0',
  period_number: 1,
  start_time: '08:00',
  end_time: '08:45',
  subject_id: '',
  room: '',
  status: 'active',
})

const days = [
  { value: 0, label: 'Sunday' },
  { value: 1, label: 'Monday' },
  { value: 2, label: 'Tuesday' },
  { value: 3, label: 'Wednesday' },
  { value: 4, label: 'Thursday' },
  { value: 5, label: 'Friday' },
  { value: 6, label: 'Saturday' },
]

const activeCount = computed(() => periods.value.filter((period) => period.status === 'active').length)
const visibleDayCount = computed(() => new Set(periods.value.map((period) => period.day_of_week)).size)
const scheduledMinutes = computed(() =>
  periods.value.reduce((total, period) => total + minutesBetween(period.start_time, period.end_time), 0),
)
const groupedPeriods = computed(() =>
  days.map((day) => ({
    ...day,
    periods: periods.value
      .filter((period) => period.day_of_week === day.value)
      .sort((a, b) => a.period_number - b.period_number || a.start_time.localeCompare(b.start_time)),
  })),
)

function minutesBetween(start: string, end: string) {
  const [startHour, startMinute] = start.slice(0, 5).split(':').map(Number)
  const [endHour, endMinute] = end.slice(0, 5).split(':').map(Number)

  return Math.max(0, endHour * 60 + endMinute - (startHour * 60 + startMinute))
}

function formatTime(value: string | null) {
  return value ? value.slice(0, 5) : 'Unset'
}

function resetForm() {
  editingPeriodId.value = null
  form.academic_year_id = filters.academic_year_id || (academicYears.value[0] ? String(academicYears.value[0].id) : '')
  form.academic_class_id = filters.academic_class_id || (academicClasses.value[0] ? String(academicClasses.value[0].id) : '')
  form.shift_id = filters.shift_id || ''
  form.day_of_week = filters.day_of_week || '0'
  form.period_number = 1
  form.start_time = '08:00'
  form.end_time = '08:45'
  form.subject_id = subjects.value[0] ? String(subjects.value[0].id) : ''
  form.room = ''
  form.status = 'active'
}

function periodPayload() {
  return {
    academic_year_id: Number(form.academic_year_id),
    academic_class_id: Number(form.academic_class_id),
    shift_id: form.shift_id ? Number(form.shift_id) : null,
    day_of_week: Number(form.day_of_week),
    period_number: Number(form.period_number),
    start_time: form.start_time,
    end_time: form.end_time,
    subject_id: form.subject_id ? Number(form.subject_id) : null,
    room: form.room || null,
    status: form.status,
  }
}

function editPeriod(period: TimetablePeriod) {
  editingPeriodId.value = period.id
  form.academic_year_id = String(period.academic_year_id)
  form.academic_class_id = String(period.academic_class_id)
  form.shift_id = period.shift_id ? String(period.shift_id) : ''
  form.day_of_week = String(period.day_of_week)
  form.period_number = period.period_number
  form.start_time = formatTime(period.start_time)
  form.end_time = formatTime(period.end_time)
  form.subject_id = period.subject_id ? String(period.subject_id) : ''
  form.room = period.room || ''
  form.status = period.status
}

function buildPeriodQuery() {
  const query = new URLSearchParams()

  for (const [key, value] of Object.entries(filters)) {
    if (value !== '') {
      query.set(key, value)
    }
  }

  query.set('per_page', '100')

  return query.toString()
}

async function loadOptions() {
  loading.value = true
  error.value = ''

  try {
    const [yearResponse, classResponse, shiftResponse, subjectResponse] = await Promise.all([
      api.request<ListResponse<AcademicYear>>(`/schools/${schoolId.value}/academic-years?status=active&per_page=100`),
      api.request<ListResponse<AcademicClass>>(`/schools/${schoolId.value}/academic-classes?status=active&per_page=100`),
      api.request<ListResponse<Shift>>(`/schools/${schoolId.value}/shifts?status=active&per_page=100`),
      api.request<ListResponse<Subject>>(`/schools/${schoolId.value}/subjects?status=active&per_page=100`),
    ])

    academicYears.value = yearResponse.data
    academicClasses.value = classResponse.data
    shifts.value = shiftResponse.data
    subjects.value = subjectResponse.data

    if (!filters.academic_year_id && academicYears.value[0]) {
      filters.academic_year_id = String(academicYears.value[0].id)
    }

    if (!filters.academic_class_id && academicClasses.value[0]) {
      filters.academic_class_id = String(academicClasses.value[0].id)
    }

    resetForm()
    await loadPeriods()
  } catch (optionsError) {
    error.value = optionsError instanceof Error ? optionsError.message : 'Unable to load timetable options.'
  } finally {
    loading.value = false
  }
}

async function loadPeriods() {
  error.value = ''

  try {
    const query = buildPeriodQuery()
    const response = await api.request<ListResponse<TimetablePeriod>>(
      `/schools/${schoolId.value}/timetable-periods?${query}`,
    )
    periods.value = response.data
  } catch (periodError) {
    error.value = periodError instanceof Error ? periodError.message : 'Unable to load timetable.'
  }
}

async function applyFilters() {
  await loadPeriods()
  resetForm()
}

async function savePeriod() {
  saving.value = true
  error.value = ''
  success.value = ''

  try {
    if (editingPeriodId.value) {
      await api.request<ItemResponse<TimetablePeriod>>(
        `/schools/${schoolId.value}/timetable-periods/${editingPeriodId.value}`,
        {
          method: 'PATCH',
          body: periodPayload(),
        },
      )
      success.value = 'Timetable period updated.'
    } else {
      await api.request<ItemResponse<TimetablePeriod>>(`/schools/${schoolId.value}/timetable-periods`, {
        method: 'POST',
        body: periodPayload(),
      })
      success.value = 'Timetable period saved.'
    }

    await loadPeriods()
    resetForm()
  } catch (periodError) {
    error.value = periodError instanceof Error ? periodError.message : 'Unable to save timetable period.'
  } finally {
    saving.value = false
  }
}

async function archivePeriod(period: TimetablePeriod) {
  archivingPeriodId.value = period.id
  error.value = ''
  success.value = ''

  try {
    await api.request<ItemResponse<TimetablePeriod>>(`/schools/${schoolId.value}/timetable-periods/${period.id}`, {
      method: 'PATCH',
      body: { status: 'archived' },
    })
    success.value = 'Timetable period archived.'
    await loadPeriods()
  } catch (periodError) {
    error.value = periodError instanceof Error ? periodError.message : 'Unable to archive timetable period.'
  } finally {
    archivingPeriodId.value = null
  }
}

onMounted(loadOptions)
</script>

<template>
  <main class="operation-shell">
    <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Timetable navigation"
      context-title="Timetable tools"
      :context-links="[
        { label: 'Academic Years', to: `/schools/${schoolId}/academic-years` },
        { label: 'Classes', to: `/schools/${schoolId}/academic-classes` },
        { label: 'Subjects', to: `/schools/${schoolId}/subjects` },
        { label: 'Shifts', to: `/schools/${schoolId}/shifts` },
        { label: 'Assignments', to: `/schools/${schoolId}/assignments` },
        { label: 'Promotions', to: `/schools/${schoolId}/promotions` },
      ]"
    />

    <section class="operation-workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">Timetable</p>
          <h1>Plan the weekly class routine.</h1>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading" class="muted">Loading timetable workspace</p>

      <section class="summary-grid">
        <article class="summary-item surface">
          <span>Visible periods</span>
          <strong>{{ periods.length }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Active periods</span>
          <strong>{{ activeCount }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Scheduled hours</span>
          <strong>{{ (scheduledMinutes / 60).toFixed(1) }}</strong>
        </article>
      </section>

      <section class="workspace-grid timetable-controls">
        <form class="record-form surface" @submit.prevent="savePeriod">
          <div>
            <p class="eyebrow">{{ editingPeriodId ? 'Edit period' : 'New period' }}</p>
            <h2>{{ editingPeriodId ? 'Update routine slot' : 'Add routine slot' }}</h2>
            <p>Class and teacher conflicts are checked before saving.</p>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="period-year">Academic year</label>
              <select id="period-year" v-model="form.academic_year_id" required>
                <option value="">Select year</option>
                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
              </select>
            </div>
            <div class="field">
              <label for="period-class">Class</label>
              <select id="period-class" v-model="form.academic_class_id" required>
                <option value="">Select class</option>
                <option v-for="academicClass in academicClasses" :key="academicClass.id" :value="academicClass.id">
                  {{ academicClass.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="period-shift">Shift</label>
              <select id="period-shift" v-model="form.shift_id">
                <option value="">No shift</option>
                <option v-for="shift in shifts" :key="shift.id" :value="shift.id">{{ shift.name }}</option>
              </select>
            </div>
            <div class="field">
              <label for="period-subject">Subject</label>
              <select id="period-subject" v-model="form.subject_id">
                <option value="">Free period</option>
                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                  {{ subject.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="period-day">Day</label>
              <select id="period-day" v-model="form.day_of_week" required>
                <option v-for="day in days" :key="day.value" :value="day.value">{{ day.label }}</option>
              </select>
            </div>
            <div class="field">
              <label for="period-number">Period number</label>
              <input id="period-number" v-model="form.period_number" min="1" max="20" required type="number" />
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="period-start">Starts</label>
              <input id="period-start" v-model="form.start_time" required type="time" />
            </div>
            <div class="field">
              <label for="period-end">Ends</label>
              <input id="period-end" v-model="form.end_time" required type="time" />
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="period-room">Room</label>
              <input id="period-room" v-model="form.room" placeholder="Room 204" />
            </div>
            <div class="field">
              <label for="period-status">Status</label>
              <select id="period-status" v-model="form.status">
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>

          <div class="strip-actions">
            <button class="button" type="submit" :disabled="saving">
              {{ saving ? 'Saving' : editingPeriodId ? 'Update period' : 'Save period' }}
            </button>
            <button v-if="editingPeriodId" class="button secondary" type="button" @click="resetForm">Cancel</button>
          </div>
        </form>

        <section class="record-list surface">
          <div class="list-header">
            <div>
              <p class="eyebrow">Filters</p>
              <h2>Routine view</h2>
            </div>
            <span class="status-pill">{{ visibleDayCount }} days</span>
          </div>

          <form class="filter-grid" @submit.prevent="applyFilters">
            <div class="field">
              <label for="filter-year">Academic year</label>
              <select id="filter-year" v-model="filters.academic_year_id">
                <option value="">All years</option>
                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
              </select>
            </div>
            <div class="field">
              <label for="filter-class">Class</label>
              <select id="filter-class" v-model="filters.academic_class_id">
                <option value="">All classes</option>
                <option v-for="academicClass in academicClasses" :key="academicClass.id" :value="academicClass.id">
                  {{ academicClass.name }}
                </option>
              </select>
            </div>
            <div class="field">
              <label for="filter-shift">Shift</label>
              <select id="filter-shift" v-model="filters.shift_id">
                <option value="">All shifts</option>
                <option v-for="shift in shifts" :key="shift.id" :value="shift.id">{{ shift.name }}</option>
              </select>
            </div>
            <div class="field">
              <label for="filter-day">Day</label>
              <select id="filter-day" v-model="filters.day_of_week">
                <option value="">All days</option>
                <option v-for="day in days" :key="day.value" :value="day.value">{{ day.label }}</option>
              </select>
            </div>
            <div class="field">
              <label for="filter-status">Status</label>
              <select id="filter-status" v-model="filters.status">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <button class="button secondary" type="submit">Apply filters</button>
          </form>
        </section>
      </section>

      <section class="routine-board">
        <article v-for="day in groupedPeriods" :key="day.value" class="day-column surface">
          <div class="day-heading">
            <h2>{{ day.label }}</h2>
            <span>{{ day.periods.length }}</span>
          </div>

          <div v-if="day.periods.length" class="period-stack">
            <article v-for="period in day.periods" :key="period.id" class="period-card">
              <div>
                <strong>Period {{ period.period_number }}</strong>
                <span>{{ formatTime(period.start_time) }} to {{ formatTime(period.end_time) }}</span>
              </div>
              <p>{{ period.subject?.name || 'Free period' }}</p>
              <small>
                {{ period.academic_class?.name || 'Class' }}
                <template v-if="period.shift"> · {{ period.shift.name }}</template>
                <template v-if="period.room"> · {{ period.room }}</template>
              </small>
              <div class="period-actions">
                <button class="text-button" type="button" @click="editPeriod(period)">Edit</button>
                <button
                  class="text-button"
                  type="button"
                  :disabled="period.status === 'archived' || archivingPeriodId === period.id"
                  @click="archivePeriod(period)"
                >
                  Archive
                </button>
              </div>
            </article>
          </div>

          <p v-else class="muted">No periods scheduled.</p>
        </article>
      </section>

      <section class="record-list surface">
        <div class="list-header">
          <div>
            <p class="eyebrow">Register</p>
            <h2>{{ periods.length }} timetable periods</h2>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Day</th>
                <th>Period</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Time</th>
                <th>Room</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="period in periods" :key="period.id">
                <td>{{ days.find((day) => day.value === period.day_of_week)?.label }}</td>
                <td>{{ period.period_number }}</td>
                <td>{{ period.academic_class?.name || '-' }}</td>
                <td>{{ period.subject?.name || 'Free period' }}</td>
                <td>{{ formatTime(period.start_time) }} to {{ formatTime(period.end_time) }}</td>
                <td>{{ period.room || '-' }}</td>
                <td><span class="status-pill">{{ period.status }}</span></td>
              </tr>
              <tr v-if="periods.length === 0">
                <td colspan="7">No timetable periods match the current filters.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </main>
</template>

<style scoped>
.timetable-controls {
  grid-template-columns: minmax(320px, 0.95fr) minmax(0, 1.05fr);
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  align-items: end;
}

.filter-grid .button {
  align-self: end;
}

.routine-board {
  display: grid;
  grid-template-columns: repeat(7, minmax(180px, 1fr));
  gap: 12px;
  overflow-x: auto;
  padding-bottom: 4px;
}

.day-column {
  min-width: 180px;
  padding: 16px;
}

.day-heading,
.period-actions,
.strip-actions {
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: space-between;
}

.day-heading h2 {
  margin: 0;
  color: #111827;
  font-size: 1rem;
}

.day-heading span {
  border-radius: 999px;
  padding: 4px 8px;
  background: rgba(255, 255, 255, 0.72);
  color: #be3455;
  font-weight: 900;
}

.period-stack {
  display: grid;
  gap: 10px;
  margin-top: 14px;
}

.period-card {
  display: grid;
  gap: 8px;
  border: 1px solid rgba(17, 24, 39, 0.08);
  border-radius: 8px;
  padding: 12px;
  background: rgba(255, 255, 255, 0.64);
}

.period-card div:first-child {
  display: flex;
  gap: 8px;
  align-items: start;
  justify-content: space-between;
}

.period-card strong {
  color: #111827;
}

.period-card span,
.period-card small {
  color: #6b7280;
}

.period-card p {
  margin: 0;
  color: #111827;
  font-weight: 850;
}

.text-button {
  border: 0;
  padding: 0;
  background: transparent;
  color: #be3455;
  cursor: pointer;
  font-weight: 850;
}

.text-button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

@media (max-width: 1100px) {
  .timetable-controls,
  .filter-grid {
    grid-template-columns: 1fr;
  }
}
</style>
