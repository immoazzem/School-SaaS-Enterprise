<script setup lang="ts">
import type { AcademicClass, AcademicYear, CalendarEvent } from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface ItemResponse<T> {
  data: T
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const events = ref<CalendarEvent[]>([])
const academicYears = ref<AcademicYear[]>([])
const classes = ref<AcademicClass[]>([])
const loading = ref(false)
const saving = ref(false)
const importing = ref(false)
const error = ref('')
const success = ref('')

const filters = reactive({
  academic_year_id: '',
  academic_class_id: '',
  is_holiday: '',
  from: '',
  to: '',
})

const eventForm = reactive({
  academic_year_id: '',
  academic_class_id: '',
  title: '',
  description: '',
  starts_on: '',
  ends_on: '',
  starts_at: '',
  ends_at: '',
  location: '',
  is_holiday: false,
  status: 'active',
})

const holidayImport = reactive({
  academic_year_id: '',
  rows: 'Eid-ul-Fitr,2026-03-20\nVictory Day,2026-12-16',
})

const holidayCount = computed(() => events.value.filter((event) => event.is_holiday).length)
const upcomingCount = computed(() => events.value.filter((event) => event.status === 'active').length)
const classScopedCount = computed(() => events.value.filter((event) => event.academic_class_id).length)

function eventWindow(event: CalendarEvent) {
  const date = event.ends_on && event.ends_on !== event.starts_on ? `${event.starts_on} to ${event.ends_on}` : event.starts_on
  const time = event.starts_at ? ` / ${event.starts_at}${event.ends_at ? `-${event.ends_at}` : ''}` : ''

  return `${date}${time}`
}

function buildQuery() {
  const params = new URLSearchParams()

  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '') {
      params.set(key, value)
    }
  })

  params.set('per_page', '100')

  return params.toString()
}

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const [eventResponse, yearResponse, classResponse] = await Promise.all([
      api.request<ListResponse<CalendarEvent>>(`/schools/${schoolId.value}/calendar-events?${buildQuery()}`),
      api.request<ListResponse<AcademicYear>>(`/schools/${schoolId.value}/academic-years?status=active&per_page=100`),
      api.request<ListResponse<AcademicClass>>(`/schools/${schoolId.value}/academic-classes?status=active&per_page=100`),
    ])

    events.value = eventResponse.data
    academicYears.value = yearResponse.data
    classes.value = classResponse.data

    if (!eventForm.academic_year_id && academicYears.value.length) {
      eventForm.academic_year_id = String(academicYears.value[0].id)
      holidayImport.academic_year_id = String(academicYears.value[0].id)
    }
  } catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load calendar workspace.'
  } finally {
    loading.value = false
  }
}

async function saveEvent() {
  saving.value = true
  error.value = ''
  success.value = ''

  try {
    await api.request<ItemResponse<CalendarEvent>>(`/schools/${schoolId.value}/calendar-events`, {
      method: 'POST',
      body: {
        academic_year_id: eventForm.academic_year_id ? Number(eventForm.academic_year_id) : null,
        academic_class_id: eventForm.academic_class_id ? Number(eventForm.academic_class_id) : null,
        title: eventForm.title,
        description: eventForm.description || null,
        starts_on: eventForm.starts_on,
        ends_on: eventForm.ends_on || null,
        starts_at: eventForm.starts_at || null,
        ends_at: eventForm.ends_at || null,
        location: eventForm.location || null,
        is_holiday: eventForm.is_holiday,
        status: eventForm.status,
      },
    })

    success.value = 'Calendar event saved.'
    eventForm.title = ''
    eventForm.description = ''
    eventForm.starts_on = ''
    eventForm.ends_on = ''
    eventForm.starts_at = ''
    eventForm.ends_at = ''
    eventForm.location = ''
    eventForm.is_holiday = false
    await loadWorkspace()
  } catch (eventError) {
    error.value = eventError instanceof Error ? eventError.message : 'Unable to save calendar event.'
  } finally {
    saving.value = false
  }
}

async function importHolidays() {
  importing.value = true
  error.value = ''
  success.value = ''

  try {
    const holidays = holidayImport.rows
      .split('\n')
      .map((row) => row.trim())
      .filter(Boolean)
      .map((row) => {
        const [title, date, description] = row.split(',').map((value) => value?.trim())

        return { title, date, description: description || null }
      })

    const response = await api.request<ListResponse<CalendarEvent>>(`/schools/${schoolId.value}/calendar-events/bulk-import-holidays`, {
      method: 'POST',
      body: {
        academic_year_id: Number(holidayImport.academic_year_id),
        holidays,
      },
    })

    success.value = `${response.data.length} holidays imported.`
    await loadWorkspace()
  } catch (holidayError) {
    error.value = holidayError instanceof Error ? holidayError.message : 'Unable to import holidays.'
  } finally {
    importing.value = false
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <main class="operation-shell">
    <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Calendar navigation"
      context-title="Calendar tools"
      :context-links="[
        { label: 'Reports', to: `/schools/${schoolId}/reports` },
        { label: 'Documents', to: `/schools/${schoolId}/documents` },
        { label: 'Attendance', to: `/schools/${schoolId}/attendance` },
        { label: 'Exams', to: `/schools/${schoolId}/exams` },
      ]"
    />

    <section class="operation-workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">Calendar</p>
          <h1>Plan holidays, exams, class events, and school operations.</h1>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading" class="muted">Loading calendar workspace</p>

      <section class="summary-grid">
        <article class="summary-item surface">
          <span>Active events</span>
          <strong>{{ upcomingCount }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Holidays</span>
          <strong>{{ holidayCount }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Class scoped</span>
          <strong>{{ classScopedCount }}</strong>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="record-form surface" @submit.prevent="saveEvent">
          <div>
            <p class="eyebrow">Event entry</p>
            <h2>Add calendar event</h2>
            <p>Use school-wide events by default, or scope an event to one class.</p>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="event-title">Title</label>
              <input id="event-title" v-model="eventForm.title" required placeholder="Annual sports day" />
            </div>
            <div class="field">
              <label for="event-location">Location</label>
              <input id="event-location" v-model="eventForm.location" placeholder="Main campus" />
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="event-year">Academic year</label>
              <select id="event-year" v-model="eventForm.academic_year_id">
                <option value="">No year scope</option>
                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
              </select>
            </div>
            <div class="field">
              <label for="event-class">Class</label>
              <select id="event-class" v-model="eventForm.academic_class_id">
                <option value="">Whole school</option>
                <option v-for="academicClass in classes" :key="academicClass.id" :value="academicClass.id">
                  {{ academicClass.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="event-start">Starts on</label>
              <input id="event-start" v-model="eventForm.starts_on" required type="date" />
            </div>
            <div class="field">
              <label for="event-end">Ends on</label>
              <input id="event-end" v-model="eventForm.ends_on" type="date" />
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="event-start-time">Starts at</label>
              <input id="event-start-time" v-model="eventForm.starts_at" type="time" />
            </div>
            <div class="field">
              <label for="event-end-time">Ends at</label>
              <input id="event-end-time" v-model="eventForm.ends_at" type="time" />
            </div>
          </div>

          <div class="field">
            <label for="event-description">Description</label>
            <textarea id="event-description" v-model="eventForm.description" placeholder="Optional notes"></textarea>
          </div>

          <label class="check-row">
            <input v-model="eventForm.is_holiday" type="checkbox" />
            Mark as holiday
          </label>

          <button class="button" type="submit" :disabled="saving">{{ saving ? 'Saving' : 'Save event' }}</button>
        </form>

        <form class="record-form surface" @submit.prevent="importHolidays">
          <div>
            <p class="eyebrow">Bulk holidays</p>
            <h2>Import holiday list</h2>
            <p>One holiday per line. Use title, date, and optional description.</p>
          </div>

          <div class="field">
            <label for="holiday-year">Academic year</label>
            <select id="holiday-year" v-model="holidayImport.academic_year_id" required>
              <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
            </select>
          </div>

          <div class="field">
            <label for="holiday-rows">Rows</label>
            <textarea id="holiday-rows" v-model="holidayImport.rows" required></textarea>
          </div>

          <button class="button" type="submit" :disabled="importing || !holidayImport.academic_year_id">
            {{ importing ? 'Importing' : 'Import holidays' }}
          </button>
        </form>
      </section>

      <section class="record-list surface">
        <div class="list-header">
          <div>
            <p class="eyebrow">Calendar list</p>
            <h2>Events and holidays</h2>
          </div>
          <div class="filter-row">
            <select v-model="filters.is_holiday" @change="loadWorkspace">
              <option value="">All records</option>
              <option value="1">Holidays only</option>
              <option value="0">Events only</option>
            </select>
            <button class="button secondary compact" type="button" @click="loadWorkspace">Refresh</button>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Title</th>
                <th>Window</th>
                <th>Scope</th>
                <th>Location</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="event in events" :key="event.id">
                <td>
                  <strong>{{ event.title }}</strong>
                  <small>{{ event.is_holiday ? 'Holiday' : 'Event' }}</small>
                </td>
                <td>{{ eventWindow(event) }}</td>
                <td>{{ event.academic_class?.name || event.academic_year?.name || 'Whole school' }}</td>
                <td>{{ event.location || 'N/A' }}</td>
                <td><span class="status-pill">{{ event.status }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!events.length" class="muted">No calendar events match the current filters.</p>
      </section>
    </section>
  </main>
</template>

<style scoped>
.check-row {
  display: flex;
  gap: 10px;
  align-items: center;
  color: #4b5563;
  font-weight: 800;
}

.filter-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.filter-row select {
  min-height: 38px;
  border: 1px solid rgba(17, 24, 39, 0.1);
  border-radius: 8px;
  padding: 0 10px;
  background: #fff;
}

td strong,
td small {
  display: block;
}

td small {
  margin-top: 4px;
  color: #6b7280;
}

@media (max-width: 900px) {
  .filter-row {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
