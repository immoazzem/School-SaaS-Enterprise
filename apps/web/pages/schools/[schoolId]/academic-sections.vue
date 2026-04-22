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
  const response = await api.request<ClassesResponse>(`/schools/${schoolId.value}/academic-classes?status=active&per_page=100`)
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
    const query = new URLSearchParams({
      per_page: '100',
      status: 'active',
    })

    if (selectedClassId.value) {
      query.set('academic_class_id', String(selectedClassId.value))
    }

    const response = await api.request<SectionsResponse>(
      `/schools/${schoolId.value}/academic-sections?${query.toString()}`,
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
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Academic sections navigation"
      context-title="Academics setup"
      :context-links="[
        { label: 'Classes', to: `/schools/${schoolId}/academic-classes` },
        { label: 'Years', to: `/schools/${schoolId}/academic-years` },
        { label: 'Class Subjects', to: `/schools/${schoolId}/class-subjects` },
      ]"
    />
    </template>

    <header class="workspace-header">
        <div>
          <p class="eyebrow">Academics</p>
          <h1>Academic Sections</h1>
          <p>Organize class groups, rooms, capacity, and status for the selected school.</p>
        </div>
        <div class="header-actions">
          <NuxtLink class="button secondary" :to="`/schools/${schoolId}/academic-years`">Manage years</NuxtLink>
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

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="saveSection">
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
            <button v-if="editingSectionId" class="button secondary" type="button" @click="resetForm">Cancel</button>
          </div>
        </form>

        <section class="surface record-list">
          <div class="list-header">
            <div>
              <p class="muted">{{ selectedClassName }}</p>
              <h2>Section register</h2>
            </div>
            <span class="muted">{{ sections.length }} records</span>
          </div>

          <p v-if="classes.length === 0" class="muted">Create an Academic Class before adding sections.</p>
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
                      <button class="text-button" type="button" :disabled="section.status === 'archived'" @click="archiveSection(section)">Archive</button>
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
</SchoolWorkspaceTemplate>
</template>


