<script setup lang="ts">
import type {
  AcademicClass,
  AcademicYear,
  PromotionAction,
  PromotionBatch,
  PromotionPreviewRow,
  PromotionRecord,
} from '~/composables/useApi'

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
const previewRows = ref<PromotionPreviewRow[]>([])
const currentBatch = ref<PromotionBatch | null>(null)
const loading = ref(false)
const previewing = ref(false)
const creating = ref(false)
const executing = ref(false)
const rollingBack = ref(false)
const savingRecordId = ref<number | null>(null)
const error = ref('')
const success = ref('')

const form = reactive({
  from_academic_year_id: '',
  to_academic_year_id: '',
  from_academic_class_id: '',
  to_academic_class_id: '',
})

const recordForms = reactive<Record<number, { action: PromotionAction, notes: string }>>({})

const actionOptions: { value: PromotionAction, label: string }[] = [
  { value: 'promoted', label: 'Promote' },
  { value: 'retained', label: 'Retain' },
  { value: 'transferred_out', label: 'Transferred out' },
  { value: 'graduated', label: 'Graduated' },
  { value: 'dropped', label: 'Dropped' },
]

const promotedCount = computed(() => records.value.filter((record) => record.action === 'promoted').length)
const retainedCount = computed(() => records.value.filter((record) => record.action === 'retained').length)
const exitCount = computed(() =>
  records.value.filter((record) => ['transferred_out', 'graduated', 'dropped'].includes(record.action)).length,
)
const records = computed(() => currentBatch.value?.records || [])
const canCreateDraft = computed(() => previewRows.value.length > 0 && currentBatch.value?.status !== 'draft')
const canExecute = computed(() => currentBatch.value?.status === 'draft' && records.value.length > 0)
const canRollback = computed(() => currentBatch.value?.status === 'completed')

function yearLabel(id: string | number) {
  return academicYears.value.find((year) => year.id === Number(id))?.name || 'Academic year'
}

function classLabel(id: string | number) {
  return academicClasses.value.find((academicClass) => academicClass.id === Number(id))?.name || 'Class'
}

function studentLabel(row: PromotionPreviewRow | PromotionRecord) {
  const student = 'student' in row ? row.student : row.student_enrollment?.student
  const admission = student?.admission_no ? ` / ${student.admission_no}` : ''

  return `${student?.full_name || 'Student'}${admission}`
}

function syncRecordForms(batch: PromotionBatch | null) {
  Object.keys(recordForms).forEach((key) => delete recordForms[Number(key)])

  for (const record of batch?.records || []) {
    recordForms[record.id] = {
      action: record.action,
      notes: record.notes || '',
    }
  }
}

async function loadOptions() {
  loading.value = true
  error.value = ''

  try {
    const [yearResponse, classResponse] = await Promise.all([
      api.request<ListResponse<AcademicYear>>(`/schools/${schoolId.value}/academic-years?status=active&per_page=100`),
      api.request<ListResponse<AcademicClass>>(`/schools/${schoolId.value}/academic-classes?status=active&per_page=100`),
    ])

    academicYears.value = yearResponse.data
    academicClasses.value = classResponse.data

    if (!form.from_academic_year_id && academicYears.value[0]) {
      form.from_academic_year_id = String(academicYears.value[0].id)
    }

    if (!form.to_academic_year_id && academicYears.value[1]) {
      form.to_academic_year_id = String(academicYears.value[1].id)
    }

    if (!form.from_academic_class_id && academicClasses.value[0]) {
      form.from_academic_class_id = String(academicClasses.value[0].id)
    }

    if (!form.to_academic_class_id && academicClasses.value[1]) {
      form.to_academic_class_id = String(academicClasses.value[1].id)
    }
  } catch (optionsError) {
    error.value = optionsError instanceof Error ? optionsError.message : 'Unable to load promotion options.'
  } finally {
    loading.value = false
  }
}

function payload() {
  return {
    from_academic_year_id: Number(form.from_academic_year_id),
    to_academic_year_id: Number(form.to_academic_year_id),
    from_academic_class_id: Number(form.from_academic_class_id),
    to_academic_class_id: Number(form.to_academic_class_id),
  }
}

async function previewPromotion() {
  previewing.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api.request<ListResponse<PromotionPreviewRow>>(`/schools/${schoolId.value}/promotions/preview`, {
      method: 'POST',
      body: payload(),
    })
    previewRows.value = response.data
    currentBatch.value = null
    syncRecordForms(null)
    success.value = response.data.length
      ? `${response.data.length} students are ready for review.`
      : 'No active enrollments found for this class and year.'
  } catch (previewError) {
    error.value = previewError instanceof Error ? previewError.message : 'Unable to preview promotion.'
  } finally {
    previewing.value = false
  }
}

async function createDraft() {
  creating.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api.request<ItemResponse<PromotionBatch>>(`/schools/${schoolId.value}/promotions`, {
      method: 'POST',
      body: payload(),
    })
    currentBatch.value = response.data
    syncRecordForms(response.data)
    success.value = `Draft batch ${response.data.id} created.`
  } catch (createError) {
    error.value = createError instanceof Error ? createError.message : 'Unable to create promotion batch.'
  } finally {
    creating.value = false
  }
}

async function saveRecord(record: PromotionRecord) {
  const state = recordForms[record.id]

  if (!currentBatch.value || !state) {
    return
  }

  savingRecordId.value = record.id
  error.value = ''
  success.value = ''

  try {
    const response = await api.request<ItemResponse<PromotionRecord>>(
      `/schools/${schoolId.value}/promotions/${currentBatch.value.id}/records/${record.id}`,
      {
        method: 'PATCH',
        body: {
          action: state.action,
          notes: state.notes || null,
        },
      },
    )

    currentBatch.value.records = records.value.map((batchRecord) =>
      batchRecord.id === record.id ? response.data : batchRecord,
    )
    syncRecordForms(currentBatch.value)
    success.value = 'Promotion action updated.'
  } catch (recordError) {
    error.value = recordError instanceof Error ? recordError.message : 'Unable to update promotion action.'
  } finally {
    savingRecordId.value = null
  }
}

async function executeBatch() {
  if (!currentBatch.value) {
    return
  }

  executing.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api.request<ItemResponse<PromotionBatch>>(
      `/schools/${schoolId.value}/promotions/${currentBatch.value.id}/execute`,
      { method: 'POST' },
    )
    currentBatch.value = response.data
    syncRecordForms(response.data)
    success.value = `${response.data.processed_count} promotion records executed.`
  } catch (executeError) {
    error.value = executeError instanceof Error ? executeError.message : 'Unable to execute promotion batch.'
  } finally {
    executing.value = false
  }
}

async function rollbackBatch() {
  if (!currentBatch.value) {
    return
  }

  rollingBack.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api.request<ItemResponse<PromotionBatch>>(
      `/schools/${schoolId.value}/promotions/${currentBatch.value.id}/rollback`,
      { method: 'POST' },
    )
    currentBatch.value = response.data
    syncRecordForms(response.data)
    success.value = 'Promotion batch rolled back.'
  } catch (rollbackError) {
    error.value = rollbackError instanceof Error ? rollbackError.message : 'Unable to roll back promotion batch.'
  } finally {
    rollingBack.value = false
  }
}

onMounted(loadOptions)
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Promotion navigation"
      context-title="Promotion tools"
      :context-links="[
        { label: 'Academic Years', to: `/schools/${schoolId}/academic-years` },
        { label: 'Classes', to: `/schools/${schoolId}/academic-classes` },
        { label: 'Enrollments', to: `/schools/${schoolId}/enrollments` },
        { label: 'Reports', to: `/schools/${schoolId}/reports` },
      ]"
    />
    </template>

    <header class="workspace-header">
        <div>
          <p class="eyebrow">Promotion</p>
          <h1>Move students into the next academic year.</h1>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading" class="muted">Loading promotion workspace</p>

      <section class="summary-grid">
        <article class="summary-item surface">
          <span>Preview rows</span>
          <strong>{{ previewRows.length }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Promoted</span>
          <strong>{{ promotedCount }}</strong>
        </article>
        <article class="summary-item surface">
          <span>Retained</span>
          <strong>{{ retainedCount }}</strong>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="record-form surface" @submit.prevent="previewPromotion">
          <div>
            <p class="eyebrow">Source and target</p>
            <h2>Build promotion preview</h2>
            <p>Preview is read-only. Create a draft batch after reviewing the suggested actions.</p>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="from-year">From year</label>
              <select id="from-year" v-model="form.from_academic_year_id" required>
                <option value="">Select year</option>
                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
              </select>
            </div>
            <div class="field">
              <label for="to-year">To year</label>
              <select id="to-year" v-model="form.to_academic_year_id" required>
                <option value="">Select year</option>
                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label for="from-class">From class</label>
              <select id="from-class" v-model="form.from_academic_class_id" required>
                <option value="">Select class</option>
                <option v-for="academicClass in academicClasses" :key="academicClass.id" :value="academicClass.id">
                  {{ academicClass.name }}
                </option>
              </select>
            </div>
            <div class="field">
              <label for="to-class">To class</label>
              <select id="to-class" v-model="form.to_academic_class_id" required>
                <option value="">Select class</option>
                <option v-for="academicClass in academicClasses" :key="academicClass.id" :value="academicClass.id">
                  {{ academicClass.name }}
                </option>
              </select>
            </div>
          </div>

          <button class="button" type="submit" :disabled="previewing">
            {{ previewing ? 'Previewing' : 'Preview students' }}
          </button>
        </form>

        <article class="record-list surface">
          <div class="list-header">
            <div>
              <p class="eyebrow">Batch state</p>
              <h2>{{ currentBatch ? `Batch ${currentBatch.id}` : 'No draft batch' }}</h2>
            </div>
            <span v-if="currentBatch" class="status-pill">{{ currentBatch.status }}</span>
          </div>

          <div class="transition-panel">
            <span>
              <strong>{{ yearLabel(form.from_academic_year_id) }}</strong>
              <small>{{ classLabel(form.from_academic_class_id) }}</small>
            </span>
            <b>to</b>
            <span>
              <strong>{{ yearLabel(form.to_academic_year_id) }}</strong>
              <small>{{ classLabel(form.to_academic_class_id) }}</small>
            </span>
          </div>

          <div class="strip-actions">
            <button class="button" type="button" :disabled="!canCreateDraft || creating" @click="createDraft">
              {{ creating ? 'Creating draft' : 'Create draft batch' }}
            </button>
            <button class="button secondary" type="button" :disabled="!canExecute || executing" @click="executeBatch">
              {{ executing ? 'Executing' : 'Execute' }}
            </button>
            <button class="button secondary" type="button" :disabled="!canRollback || rollingBack" @click="rollbackBatch">
              {{ rollingBack ? 'Rolling back' : 'Rollback' }}
            </button>
          </div>

          <div class="batch-metrics">
            <span>Processed <strong>{{ currentBatch?.processed_count || 0 }}</strong></span>
            <span>Exit actions <strong>{{ exitCount }}</strong></span>
          </div>
        </article>
      </section>

      <section class="record-list surface">
        <div class="list-header">
          <div>
            <p class="eyebrow">{{ currentBatch ? 'Draft records' : 'Preview' }}</p>
            <h2>{{ currentBatch ? `${records.length} promotion records` : `${previewRows.length} preview rows` }}</h2>
          </div>
        </div>

        <div v-if="currentBatch" class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>Action</th>
                <th>Notes</th>
                <th>New enrollment</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="record in records" :key="record.id">
                <td>
                  <strong>{{ studentLabel(record) }}</strong>
                  <span class="row-note">Roll {{ record.student_enrollment?.roll_no || '-' }}</span>
                </td>
                <td>
                  <select v-model="recordForms[record.id].action" :disabled="currentBatch.status !== 'draft'">
                    <option v-for="option in actionOptions" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>
                </td>
                <td>
                  <input
                    v-model="recordForms[record.id].notes"
                    :disabled="currentBatch.status !== 'draft'"
                    placeholder="Optional note"
                  />
                </td>
                <td>{{ record.new_enrollment_id || '-' }}</td>
                <td>
                  <button
                    class="button compact secondary"
                    type="button"
                    :disabled="currentBatch.status !== 'draft' || savingRecordId === record.id"
                    @click="saveRecord(record)"
                  >
                    {{ savingRecordId === record.id ? 'Saving' : 'Save' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>Suggested action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in previewRows" :key="row.student_enrollment_id">
                <td>{{ studentLabel(row) }}</td>
                <td><span class="status-pill">{{ row.suggested_action }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <p v-if="!previewRows.length && !currentBatch" class="muted">Select a year and class to preview promotion candidates.</p>
      </section>
</SchoolWorkspaceTemplate>
</template>


