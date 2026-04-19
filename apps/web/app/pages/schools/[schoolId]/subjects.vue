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
  <main class="subjects-page">
    <header class="subjects-header">
      <div>
        <NuxtLink class="back-link" to="/dashboard">Dashboard</NuxtLink>
        <h1>Subjects</h1>
        <p>Maintain the subject catalog used by classes, timetables, exams, and reports.</p>
      </div>

      <div class="header-actions">
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/academic-classes`">
          Classes
        </NuxtLink>
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/academic-years`">
          Years
        </NuxtLink>
      </div>
    </header>

    <section class="subject-summary">
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

    <section class="subjects-grid">
      <form class="surface subject-form" @submit.prevent="saveSubject">
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
          <button v-if="editingSubjectId" class="button secondary" type="button" @click="resetForm">
            Cancel
          </button>
        </div>
      </form>

      <section class="surface subject-list">
        <div class="list-heading">
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
                    <button
                      class="text-button"
                      type="button"
                      :disabled="subject.status === 'archived' || archivingSubjectId === subject.id"
                      @click="archiveSubject(subject)"
                    >
                      Archive
                    </button>
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
  </main>
</template>

<style scoped>
.subjects-page {
  min-height: 100vh;
  padding: 30px;
  background: #f7f3ef;
}

.subjects-header {
  display: flex;
  gap: 20px;
  align-items: end;
  justify-content: space-between;
  margin-bottom: 20px;
}

.back-link {
  color: #be3455;
  font-weight: 800;
}

h1 {
  margin: 12px 0 0;
  color: #111827;
  font-size: clamp(2.1rem, 5.8vw, 4.4rem);
  line-height: 0.96;
}

.subjects-header p {
  max-width: 720px;
  margin: 16px 0 0;
  color: #6b7280;
}

.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.subject-summary {
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
  color: #6b7280;
  font-weight: 700;
}

.summary-item strong {
  color: #111827;
  font-size: 1.55rem;
  text-transform: capitalize;
}

.subjects-grid {
  display: grid;
  grid-template-columns: minmax(300px, 400px) minmax(0, 1fr);
  gap: 18px;
}

.subject-form,
.subject-list {
  padding: 22px;
}

.subject-form {
  display: grid;
  align-content: start;
  gap: 16px;
}

.subject-form h2,
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

.filters {
  display: flex;
  gap: 10px;
  align-items: center;
}

.filters input,
.filters select {
  min-height: 42px;
  border: 1px solid rgba(17, 24, 39, 0.1);
  border-radius: 8px;
  padding: 0 12px;
  background: #fff;
  color: #111827;
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

@media (max-width: 960px) {
  .subjects-page {
    padding: 22px;
  }

  .subjects-header,
  .header-actions,
  .form-actions,
  .filters {
    align-items: stretch;
    flex-direction: column;
  }

  .subject-summary,
  .subjects-grid,
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
