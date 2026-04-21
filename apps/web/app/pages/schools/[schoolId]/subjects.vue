<script setup lang="ts">
import type { Subject } from '~/composables/useApi'

interface SubjectsResponse {
  data: Subject[]
}

interface SubjectResponse {
  data: Subject
}

const api = useApi()
const route = useRoute()
const auth = useAuth()

const subjects = ref<Subject[]>([])
const loading = ref(false)
const saving = ref(false)
const archivingSubjectId = ref<number | null>(null)
const error = ref('')
const success = ref('')
const editingSubjectId = ref<number | null>(null)
const statusFilter = ref('active')
const typeFilter = ref('')
const search = ref('')
const form = reactive({
  name: '',
  code: '',
  type: 'core',
  description: '',
  credit_hours: '',
  sort_order: 0,
  status: 'active',
})

const schoolId = computed(() => Number(route.params.schoolId))
const coreCount = computed(() => subjects.value.filter((subject) => subject.type === 'core').length)

async function loadSubjects() {
  loading.value = true
  error.value = ''

  try {
    const query = new URLSearchParams()
    query.set('per_page', '100')

    if (statusFilter.value) {
      query.set('status', statusFilter.value)
    }

    if (typeFilter.value) {
      query.set('type', typeFilter.value)
    }

    if (search.value.trim()) {
      query.set('search', search.value.trim())
    }

    const suffix = query.toString() ? `?${query.toString()}` : ''
    const response = await api.request<SubjectsResponse>(`/schools/${schoolId.value}/subjects${suffix}`)
    subjects.value = response.data
  } catch (subjectError) {
    error.value = subjectError instanceof Error ? subjectError.message : 'Unable to load subjects.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingSubjectId.value = null
  form.name = ''
  form.code = ''
  form.type = 'core'
  form.description = ''
  form.credit_hours = ''
  form.sort_order = 0
  form.status = 'active'
}

function editSubject(subject: Subject) {
  editingSubjectId.value = subject.id
  form.name = subject.name
  form.code = subject.code
  form.type = subject.type
  form.description = subject.description || ''
  form.credit_hours = subject.credit_hours ? String(subject.credit_hours) : ''
  form.sort_order = subject.sort_order
  form.status = subject.status
}

async function saveSubject() {
  saving.value = true
  error.value = ''
  success.value = ''

  const payload = {
    name: form.name,
    code: form.code,
    type: form.type,
    description: form.description || null,
    credit_hours: form.credit_hours ? Number(form.credit_hours) : null,
    sort_order: Number(form.sort_order),
    status: form.status,
  }

  try {
    if (editingSubjectId.value) {
      await api.request<SubjectResponse>(`/schools/${schoolId.value}/subjects/${editingSubjectId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      success.value = 'Subject updated.'
    } else {
      await api.request<SubjectResponse>(`/schools/${schoolId.value}/subjects`, {
        method: 'POST',
        body: payload,
      })
      success.value = 'Subject saved.'
    }

    resetForm()
    await loadSubjects()
  } catch (subjectError) {
    error.value = subjectError instanceof Error ? subjectError.message : 'Unable to save subject.'
  } finally {
    saving.value = false
  }
}

async function archiveSubject(subject: Subject) {
  archivingSubjectId.value = subject.id
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/subjects/${subject.id}`, {
      method: 'PATCH',
      body: { status: 'archived' },
    })
    success.value = 'Subject archived.'
    await loadSubjects()
  } catch (subjectError) {
    error.value = subjectError instanceof Error ? subjectError.message : 'Unable to archive subject.'
  } finally {
    archivingSubjectId.value = null
  }
}

async function chooseStatus(event: Event) {
  statusFilter.value = (event.target as HTMLSelectElement).value
  await loadSubjects()
}

async function chooseType(event: Event) {
  typeFilter.value = (event.target as HTMLSelectElement).value
  await loadSubjects()
}

async function searchSubjects() {
  await loadSubjects()
}

onMounted(async () => {
  await auth.refreshProfile()
  await loadSubjects()
})
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Subjects navigation"
      context-title="Academics setup"
      :context-links="[
        { label: 'Classes', to: `/schools/${schoolId}/academic-classes` },
        { label: 'Years', to: `/schools/${schoolId}/academic-years` },
        { label: 'Class Subjects', to: `/schools/${schoolId}/class-subjects` },
        { label: 'Marks', to: `/schools/${schoolId}/marks` },
      ]"
    />
    </template>

    <header class="workspace-header">
        <div>
          <p class="eyebrow">Academics</p>
          <h1>Subjects</h1>
          <p>Maintain the subject catalog used by classes, timetables, exams, and reports.</p>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <section class="summary-grid">
        <article class="surface summary-item">
          <span>Visible subjects</span>
          <strong>{{ subjects.length }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Core subjects</span>
          <strong>{{ coreCount }}</strong>
        </article>
        <article class="surface summary-item">
          <span>Filter</span>
          <strong>{{ typeFilter || statusFilter || 'All' }}</strong>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="saveSubject">
          <div>
            <p class="muted">{{ editingSubjectId ? 'Edit subject' : 'New subject' }}</p>
            <h2>{{ editingSubjectId ? 'Update subject' : 'Add subject' }}</h2>
          </div>

          <div class="field">
            <label for="subject-name">Name</label>
            <input id="subject-name" v-model="form.name" required placeholder="Mathematics" />
          </div>

          <div class="form-row">
            <div class="field">
              <label for="subject-code">Code</label>
              <input id="subject-code" v-model="form.code" required placeholder="MATH" />
            </div>
            <div class="field">
              <label for="subject-type">Type</label>
              <select id="subject-type" v-model="form.type">
                <option value="core">Core</option>
                <option value="elective">Elective</option>
                <option value="co_curricular">Co-curricular</option>
              </select>
            </div>
          </div>

          <div class="field">
            <label for="subject-description">Description</label>
            <textarea id="subject-description" v-model="form.description" rows="4" placeholder="Curriculum notes" />
          </div>

          <div class="form-row">
            <div class="field">
              <label for="credit-hours">Credit hours</label>
              <input id="credit-hours" v-model="form.credit_hours" min="1" type="number" placeholder="4" />
            </div>
            <div class="field">
              <label for="subject-order">Order</label>
              <input id="subject-order" v-model="form.sort_order" min="0" type="number" />
            </div>
          </div>

          <div class="field">
            <label for="subject-status">Status</label>
            <select id="subject-status" v-model="form.status">
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
          </div>

          <p v-if="error" class="error">{{ error }}</p>
          <p v-if="success" class="success">{{ success }}</p>

          <div class="form-actions">
            <button class="button" type="submit" :disabled="saving">
              {{ saving ? 'Saving' : editingSubjectId ? 'Update subject' : 'Save subject' }}
            </button>
            <button v-if="editingSubjectId" class="button secondary" type="button" @click="resetForm">Cancel</button>
          </div>
        </form>

        <section class="surface record-list">
          <div class="list-header">
            <div>
              <p class="muted">Catalog register</p>
              <h2>Subject catalog</h2>
            </div>
            <form class="filters" @submit.prevent="searchSubjects">
              <input v-model="search" aria-label="Search subjects" placeholder="Search" />
              <select :value="typeFilter" aria-label="Type filter" @change="chooseType">
                <option value="">All types</option>
                <option value="core">Core</option>
                <option value="elective">Elective</option>
                <option value="co_curricular">Co-curricular</option>
              </select>
              <select :value="statusFilter" aria-label="Status filter" @change="chooseStatus">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
              <button class="button secondary" type="submit">Search</button>
            </form>
          </div>

          <p v-if="loading" class="muted">Loading subjects</p>

          <div v-else class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Subject</th>
                  <th>Type</th>
                  <th>Hours</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="subject in subjects" :key="subject.id">
                  <td>
                    <strong>{{ subject.name }}</strong>
                    <small>{{ subject.code }}</small>
                  </td>
                  <td>{{ subject.type.replace('_', '-') }}</td>
                  <td>{{ subject.credit_hours || 'Unset' }}</td>
                  <td>{{ subject.status }}</td>
                  <td>
                    <div class="row-actions">
                      <button class="text-button" type="button" @click="editSubject(subject)">Edit</button>
                      <button class="text-button" type="button" :disabled="subject.status === 'archived' || archivingSubjectId === subject.id" @click="archiveSubject(subject)">Archive</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="subjects.length === 0">
                  <td colspan="5">No subjects yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>
</SchoolWorkspaceTemplate>
</template>


