<script setup lang="ts">
import type { AcademicClass, AcademicSection } from '~/composables/useApi'

interface ClassesResponse {
  data: AcademicClass[]
}

interface SectionsResponse {
  data: AcademicSection[]
}

interface SectionResponse {
  data: AcademicSection
}

const api = useApi()
const route = useRoute()
const auth = useAuth()

const classes = ref<AcademicClass[]>([])
const sections = ref<AcademicSection[]>([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const editingSectionId = ref<number | null>(null)
const selectedClassId = ref<number | null>(null)
const form = reactive({
  academic_class_id: '',
  name: '',
  code: '',
  capacity: '',
  room: '',
  sort_order: 0,
  status: 'active',
})

const schoolId = computed(() => Number(route.params.schoolId))

const selectedClassName = computed(() => {
  const selectedClass = classes.value.find((academicClass) => academicClass.id === selectedClassId.value)

  return selectedClass ? `${selectedClass.name} (${selectedClass.code})` : 'All classes'
})

async function loadClasses() {
  const response = await api.request<ClassesResponse>(`/schools/${schoolId.value}/academic-classes`)
  classes.value = response.data

  if (!selectedClassId.value && response.data[0]) {
    selectedClassId.value = response.data[0].id
  }

  if (!form.academic_class_id && response.data[0]) {
    form.academic_class_id = String(response.data[0].id)
  }
}

async function loadSections() {
  loading.value = true
  error.value = ''

  try {
    const query = selectedClassId.value ? `?academic_class_id=${selectedClassId.value}` : ''
    const response = await api.request<SectionsResponse>(
      `/schools/${schoolId.value}/academic-sections${query}`,
    )
    sections.value = response.data
  } catch (sectionError) {
    error.value = sectionError instanceof Error ? sectionError.message : 'Unable to load sections.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingSectionId.value = null
  form.academic_class_id = selectedClassId.value ? String(selectedClassId.value) : String(classes.value[0]?.id ?? '')
  form.name = ''
  form.code = ''
  form.capacity = ''
  form.room = ''
  form.sort_order = 0
  form.status = 'active'
}

function editSection(section: AcademicSection) {
  editingSectionId.value = section.id
  form.academic_class_id = String(section.academic_class_id)
  form.name = section.name
  form.code = section.code
  form.capacity = section.capacity ? String(section.capacity) : ''
  form.room = section.room || ''
  form.sort_order = section.sort_order
  form.status = section.status
}

async function saveSection() {
  saving.value = true
  error.value = ''
  success.value = ''

  const payload = {
    academic_class_id: Number(form.academic_class_id),
    name: form.name,
    code: form.code,
    capacity: form.capacity ? Number(form.capacity) : null,
    room: form.room || null,
    sort_order: Number(form.sort_order),
    status: form.status,
  }

  try {
    if (editingSectionId.value) {
      await api.request<SectionResponse>(
        `/schools/${schoolId.value}/academic-sections/${editingSectionId.value}`,
        {
          method: 'PATCH',
          body: payload,
        },
      )
      success.value = 'Section updated.'
    } else {
      await api.request<SectionResponse>(`/schools/${schoolId.value}/academic-sections`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Section saved.'
    }

    resetForm()
    await loadSections()
  } catch (sectionError) {
    error.value = sectionError instanceof Error ? sectionError.message : 'Unable to save section.'
  } finally {
    saving.value = false
  }
}

async function archiveSection(section: AcademicSection) {
  await api.request(`/schools/${schoolId.value}/academic-sections/${section.id}`, {
    method: 'PATCH',
    body: { status: 'archived' },
  })
  await loadSections()
}

async function chooseClass(event: Event) {
  const value = Number((event.target as HTMLSelectElement).value)
  selectedClassId.value = value || null
  form.academic_class_id = value ? String(value) : form.academic_class_id
  await loadSections()
}

onMounted(async () => {
  await auth.refreshProfile()
  await loadClasses()
  await loadSections()
})
</script>

<template>
  <main class="sections-page">
    <header class="sections-header">
      <div>
        <NuxtLink class="back-link" :to="`/schools/${schoolId}/academic-classes`">Academic Classes</NuxtLink>
        <h1>Academic Sections</h1>
        <p>Organize class groups, rooms, capacity, and status for the selected school.</p>
      </div>

      <div class="header-tools">
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/academic-years`">
          Manage years
        </NuxtLink>

        <div class="filter-panel">
          <label for="class-filter">Class filter</label>
          <select id="class-filter" :value="selectedClassId || ''" @change="chooseClass">
            <option value="">All classes</option>
            <option v-for="academicClass in classes" :key="academicClass.id" :value="academicClass.id">
              {{ academicClass.name }} · {{ academicClass.code }}
            </option>
          </select>
        </div>
      </div>
    </header>

    <section class="sections-grid">
      <form class="surface section-form" @submit.prevent="saveSection">
        <div>
          <p class="muted">{{ editingSectionId ? 'Edit section' : 'New section' }}</p>
          <h2>{{ editingSectionId ? 'Update section' : 'Add section' }}</h2>
        </div>

        <div class="field">
          <label for="section-class">Class</label>
          <select id="section-class" v-model="form.academic_class_id" required>
            <option disabled value="">Select class</option>
            <option v-for="academicClass in classes" :key="academicClass.id" :value="academicClass.id">
              {{ academicClass.name }} · {{ academicClass.code }}
            </option>
          </select>
        </div>

        <div class="field">
          <label for="section-name">Name</label>
          <input id="section-name" v-model="form.name" required placeholder="Section A" />
        </div>

        <div class="form-row">
          <div class="field">
            <label for="section-code">Code</label>
            <input id="section-code" v-model="form.code" required placeholder="A" />
          </div>
          <div class="field">
            <label for="section-order">Order</label>
            <input id="section-order" v-model="form.sort_order" min="0" type="number" />
          </div>
        </div>

        <div class="form-row">
          <div class="field">
            <label for="section-capacity">Capacity</label>
            <input id="section-capacity" v-model="form.capacity" min="1" type="number" placeholder="35" />
          </div>
          <div class="field">
            <label for="section-room">Room</label>
            <input id="section-room" v-model="form.room" placeholder="101" />
          </div>
        </div>

        <div class="field">
          <label for="section-status">Status</label>
          <select id="section-status" v-model="form.status">
            <option value="active">Active</option>
            <option value="archived">Archived</option>
          </select>
        </div>

        <p v-if="error" class="error">{{ error }}</p>
        <p v-if="success" class="success">{{ success }}</p>

        <div class="form-actions">
          <button class="button" type="submit" :disabled="saving || classes.length === 0">
            {{ saving ? 'Saving' : editingSectionId ? 'Update section' : 'Save section' }}
          </button>
          <button v-if="editingSectionId" class="button secondary" type="button" @click="resetForm">
            Cancel
          </button>
        </div>
      </form>

      <section class="surface section-list">
        <div class="list-heading">
          <div>
            <p class="muted">{{ selectedClassName }}</p>
            <h2>Section register</h2>
          </div>
          <span>{{ sections.length }} records</span>
        </div>

        <p v-if="classes.length === 0" class="empty-copy">Create an Academic Class before adding sections.</p>
        <p v-else-if="loading" class="muted">Loading sections</p>

        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Section</th>
                <th>Class</th>
                <th>Capacity</th>
                <th>Room</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="section in sections" :key="section.id">
                <td>
                  <strong>{{ section.name }}</strong>
                  <small>{{ section.code }}</small>
                </td>
                <td>{{ section.academic_class?.name || 'Class' }}</td>
                <td>{{ section.capacity || 'Open' }}</td>
                <td>{{ section.room || 'Unassigned' }}</td>
                <td>{{ section.status }}</td>
                <td>
                  <div class="row-actions">
                    <button class="text-button" type="button" @click="editSection(section)">Edit</button>
                    <button
                      class="text-button"
                      type="button"
                      :disabled="section.status === 'archived'"
                      @click="archiveSection(section)"
                    >
                      Archive
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="sections.length === 0">
                <td colspan="6">No sections yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </main>
</template>

<style scoped>
.sections-page {
  min-height: 100vh;
  padding: 30px;
  background: #f7f3ef;
}

.sections-header {
  display: flex;
  gap: 20px;
  align-items: end;
  justify-content: space-between;
  margin-bottom: 24px;
}

.back-link {
  color: #be3455;
  font-weight: 800;
}

h1 {
  margin: 12px 0 0;
  color: #111827;
  font-size: clamp(2.1rem, 3.8rem, 4rem);
  line-height: 0.98;
}

.sections-header p {
  max-width: 680px;
  margin: 16px 0 0;
  color: #6b7280;
}

.filter-panel {
  display: grid;
  gap: 8px;
  min-width: 250px;
}

.header-tools {
  display: flex;
  gap: 12px;
  align-items: end;
}

.filter-panel label {
  color: #4b5563;
  font-size: 0.88rem;
  font-weight: 700;
}

.filter-panel select {
  min-height: 46px;
  border: 1px solid rgba(17, 24, 39, 0.1);
  border-radius: 8px;
  padding: 0 14px;
  background: #fff;
  color: #111827;
}

.sections-grid {
  display: grid;
  grid-template-columns: minmax(300px, 390px) minmax(0, 1fr);
  gap: 18px;
}

.section-form,
.section-list {
  padding: 22px;
}

.section-form {
  display: grid;
  align-content: start;
  gap: 16px;
}

.section-form h2,
.list-heading h2 {
  margin: 0;
  color: #111827;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
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

.list-heading span {
  color: #6b7280;
  font-weight: 800;
}

.empty-copy {
  color: #6b7280;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  min-width: 760px;
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
  color: #6b7280;
  font-size: 0.84rem;
  text-transform: uppercase;
}

td strong,
td small {
  display: block;
}

td small {
  margin-top: 4px;
  color: #6b7280;
}

.row-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.text-button {
  border: 0;
  background: transparent;
  color: #be3455;
  cursor: pointer;
  font-weight: 800;
}

.text-button:disabled {
  color: #91a29b;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .sections-page {
    padding: 22px;
  }

  .sections-header,
  .header-tools,
  .form-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .sections-grid,
  .form-row {
    grid-template-columns: 1fr;
  }

  .filter-panel {
    min-width: 0;
  }
}
</style>
