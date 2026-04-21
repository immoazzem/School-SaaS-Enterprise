<script setup lang="ts">
import type { SchoolDocument } from '~/composables/useApi'

interface ListResponse<T> {
  data: T[]
}

interface ItemResponse<T> {
  data: T
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const documents = ref<SchoolDocument[]>([])
const selectedDocument = ref<SchoolDocument | null>(null)
const selectedFile = ref<File | null>(null)
const loading = ref(false)
const uploading = ref(false)
const error = ref('')
const success = ref('')

const filters = reactive({
  category: '',
  is_public: '',
})

const documentForm = reactive({
  title: '',
  category: 'circular' as SchoolDocument['category'],
  is_public: false,
  related_model_type: '',
  related_model_id: '',
})

const publicCount = computed(() => documents.value.filter((document) => document.is_public).length)
const privateCount = computed(() => documents.value.filter((document) => !document.is_public).length)
const totalStorageMb = computed(() => {
  const bytes = documents.value.reduce((sum, document) => sum + Number(document.file_size_bytes), 0)

  return Math.round((bytes / 1024 / 1024) * 100) / 100
})

function buildQuery() {
  const params = new URLSearchParams()

  if (filters.category) {
    params.set('category', filters.category)
  }

  if (filters.is_public !== '') {
    params.set('is_public', filters.is_public)
  }

  params.set('per_page', '100')

  return params.toString()
}

function formatBytes(bytes: number) {
  if (bytes < 1024) {
    return `${bytes} B`
  }

  if (bytes < 1024 * 1024) {
    return `${Math.round((bytes / 1024) * 10) / 10} KB`
  }

  return `${Math.round((bytes / 1024 / 1024) * 10) / 10} MB`
}

function chooseFile(event: Event) {
  selectedFile.value = (event.target as HTMLInputElement).files?.[0] || null
}

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.request<ListResponse<SchoolDocument>>(`/schools/${schoolId.value}/documents?${buildQuery()}`)
    documents.value = response.data
  } catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load documents workspace.'
  } finally {
    loading.value = false
  }
}

async function uploadDocument() {
  if (!selectedFile.value) {
    error.value = 'Choose a file first.'
    return
  }

  uploading.value = true
  error.value = ''
  success.value = ''

  try {
    const body = new FormData()
    body.append('title', documentForm.title)
    body.append('category', documentForm.category)
    body.append('is_public', documentForm.is_public ? '1' : '0')
    body.append('file', selectedFile.value)

    if (documentForm.related_model_type) {
      body.append('related_model_type', documentForm.related_model_type)
    }

    if (documentForm.related_model_id) {
      body.append('related_model_id', documentForm.related_model_id)
    }

    await api.request<ItemResponse<SchoolDocument>>(`/schools/${schoolId.value}/documents`, {
      method: 'POST',
      body,
    })

    success.value = 'Document uploaded.'
    documentForm.title = ''
    documentForm.related_model_type = ''
    documentForm.related_model_id = ''
    selectedFile.value = null
    await loadWorkspace()
  } catch (uploadError) {
    error.value = uploadError instanceof Error ? uploadError.message : 'Unable to upload document.'
  } finally {
    uploading.value = false
  }
}

async function openDocument(document: SchoolDocument) {
  error.value = ''

  try {
    const response = await api.request<ItemResponse<SchoolDocument>>(`/schools/${schoolId.value}/documents/${document.id}`)
    selectedDocument.value = response.data
  } catch (documentError) {
    error.value = documentError instanceof Error ? documentError.message : 'Unable to open document.'
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <main class="operation-shell">
    <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Documents navigation"
      context-title="Document tools"
      :context-links="[
        { label: 'Reports', to: `/schools/${schoolId}/reports` },
        { label: 'Calendar', to: `/schools/${schoolId}/calendar` },
        { label: 'Students', to: `/schools/${schoolId}/students` },
        { label: 'Staff Operations', to: `/schools/${schoolId}/staff-operations` },
      ]"
    />

    <section class="operation-workspace">
      <header class="workspace-header">
        <div>
          <p class="eyebrow">Documents</p>
          <h1>Store circulars, student files, employee records, and finance papers.</h1>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading" class="muted">Loading documents workspace</p>

      <section class="summary-grid">
        <article class="summary-item surface">
          <span>Documents</span>
          <strong>{{ documents.length }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Public files</span>
          <strong>{{ publicCount }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Storage shown</span>
          <strong>{{ totalStorageMb }} MB</strong>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="record-form surface" @submit.prevent="uploadDocument">
          <div>
            <p class="eyebrow">Upload</p>
            <h2>Add document</h2>
            <p>Private by default. Public files can be read by permitted school members.</p>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="document-title">Title</label>
              <input id="document-title" v-model="documentForm.title" required placeholder="Admission circular" />
            </div>
            <div class="field">
              <label for="document-category">Category</label>
              <select id="document-category" v-model="documentForm.category" required>
                <option value="circular">Circular</option>
                <option value="student_document">Student document</option>
                <option value="employee_document">Employee document</option>
                <option value="financial_document">Financial document</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <div class="field">
            <label for="document-file">File</label>
            <input id="document-file" type="file" required @change="chooseFile" />
          </div>

          <div class="form-row">
            <div class="field">
              <label for="related-type">Related model</label>
              <input id="related-type" v-model="documentForm.related_model_type" placeholder="Optional model name" />
            </div>
            <div class="field">
              <label for="related-id">Related ID</label>
              <input id="related-id" v-model="documentForm.related_model_id" inputmode="numeric" placeholder="Optional id" />
            </div>
          </div>

          <label class="check-row">
            <input v-model="documentForm.is_public" type="checkbox" />
            Make this document public
          </label>

          <button class="button" type="submit" :disabled="uploading">
            {{ uploading ? 'Uploading' : 'Upload document' }}
          </button>
        </form>

        <section class="record-list surface">
          <div class="list-header">
            <div>
              <p class="eyebrow">Access</p>
              <h2>Selected document</h2>
            </div>
          </div>

          <div v-if="selectedDocument" class="document-detail">
            <span>Title <strong>{{ selectedDocument.title }}</strong></span>
            <span>File <strong>{{ selectedDocument.file_name }}</strong></span>
            <span>Size <strong>{{ formatBytes(selectedDocument.file_size_bytes) }}</strong></span>
            <span>Visibility <strong>{{ selectedDocument.is_public ? 'Public' : 'Private' }}</strong></span>
            <a
              v-if="selectedDocument.download_url"
              class="button compact"
              :href="selectedDocument.download_url"
              target="_blank"
              rel="noreferrer"
            >
              Open signed file
            </a>
          </div>
          <p v-else class="muted">Select a document from the table to request a signed download link.</p>
        </section>
      </section>

      <section class="record-list surface">
        <div class="list-header">
          <div>
            <p class="eyebrow">Library</p>
            <h2>Documents</h2>
          </div>
          <div class="filter-row">
            <select v-model="filters.category" @change="loadWorkspace">
              <option value="">All categories</option>
              <option value="circular">Circular</option>
              <option value="student_document">Student document</option>
              <option value="employee_document">Employee document</option>
              <option value="financial_document">Financial document</option>
              <option value="other">Other</option>
            </select>
            <select v-model="filters.is_public" @change="loadWorkspace">
              <option value="">Any visibility</option>
              <option value="1">Public</option>
              <option value="0">Private</option>
            </select>
            <button class="button secondary compact" type="button" @click="loadWorkspace">Refresh</button>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Title</th>
                <th>Category</th>
                <th>File</th>
                <th>Size</th>
                <th>Visibility</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="document in documents" :key="document.id">
                <td>{{ document.title }}</td>
                <td>{{ document.category.replace('_', ' ') }}</td>
                <td>{{ document.file_name }}</td>
                <td>{{ formatBytes(document.file_size_bytes) }}</td>
                <td><span class="status-pill">{{ document.is_public ? 'public' : 'private' }}</span></td>
                <td>
                  <button class="button secondary compact" type="button" @click="openDocument(document)">
                    Get link
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!documents.length" class="muted">No documents match the current filters.</p>
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

.document-detail {
  display: grid;
  gap: 12px;
}

.document-detail span {
  display: flex;
  min-height: 44px;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #e3ebe7;
  color: #6b7280;
  gap: 12px;
}

.document-detail strong {
  color: #111827;
  text-align: right;
}

.filter-row {
  display: flex;
  flex-wrap: wrap;
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
</style>
