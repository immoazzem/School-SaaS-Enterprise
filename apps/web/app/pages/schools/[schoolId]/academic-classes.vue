<script setup lang="ts">
import type { AcademicClass } from '~/composables/useApi'

interface ClassesResponse {
  data: AcademicClass[]
}

const api = useApi()
const route = useRoute()
const auth = useAuth()

const classes = ref<AcademicClass[]>([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const form = reactive({
  name: '',
  code: '',
  sort_order: 0,
  status: 'active',
})

const schoolId = computed(() => Number(route.params.schoolId))

async function loadClasses() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.request<ClassesResponse>(
      `/schools/${schoolId.value}/academic-classes?status=active&per_page=100`,
    )
    classes.value = response.data
  } catch (classError) {
    error.value = classError instanceof Error ? classError.message : 'Unable to load classes.'
  } finally {
    loading.value = false
  }
}

async function createClass() {
  saving.value = true
  error.value = ''
  success.value = ''

  try {
    await api.request<ClassesResponse>(`/schools/${schoolId.value}/academic-classes`, {
      method: 'POST',
      body: {
        name: form.name,
        code: form.code,
        sort_order: Number(form.sort_order),
        status: form.status,
      },
    })

    form.name = ''
    form.code = ''
    form.sort_order = 0
    form.status = 'active'
    success.value = 'Class saved.'
    await loadClasses()
  } catch (classError) {
    error.value = classError instanceof Error ? classError.message : 'Unable to save class.'
  } finally {
    saving.value = false
  }
}

async function archiveClass(academicClass: AcademicClass) {
  await api.request(`/schools/${schoolId.value}/academic-classes/${academicClass.id}`, {
    method: 'PATCH',
    body: { status: 'archived' },
  })
  await loadClasses()
}

onMounted(async () => {
  await auth.refreshProfile()
  await loadClasses()
})
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
      :school-id="schoolId"
      aria-label="Academic classes navigation"
      context-title="Academics setup"
      :context-links="[
        { label: 'Sections', to: `/schools/${schoolId}/academic-sections` },
        { label: 'Years', to: `/schools/${schoolId}/academic-years` },
        { label: 'Subjects', to: `/schools/${schoolId}/subjects` },
        { label: 'Class Subjects', to: `/schools/${schoolId}/class-subjects` },
      ]"
    />
    </template>

    <header class="workspace-header">
        <div>
          <p class="eyebrow">Academics</p>
          <h1>Academic Classes</h1>
          <p>Manage the class list for the active school.</p>
        </div>
        <NuxtLink class="button secondary" to="/dashboard">Dashboard</NuxtLink>
      </header>

      <section class="workspace-grid">
        <form class="surface record-form" @submit.prevent="createClass">
          <h2>Add class</h2>
          <div class="field">
            <label for="name">Name</label>
            <input id="name" v-model="form.name" required placeholder="Class One" />
          </div>
          <div class="field">
            <label for="code">Code</label>
            <input id="code" v-model="form.code" required placeholder="C1" />
          </div>
          <div class="field">
            <label for="sort">Sort order</label>
            <input id="sort" v-model="form.sort_order" min="0" type="number" />
          </div>
          <div class="field">
            <label for="status">Status</label>
            <select id="status" v-model="form.status">
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
          </div>

          <p v-if="error" class="error">{{ error }}</p>
          <p v-if="success" class="success">{{ success }}</p>

          <button class="button" type="submit" :disabled="saving">
            {{ saving ? 'Saving' : 'Save class' }}
          </button>
        </form>

        <section class="surface record-list">
          <div class="list-header">
            <h2>Class register</h2>
            <span class="muted">{{ classes.length }} records</span>
          </div>

          <p v-if="loading" class="muted">Loading classes</p>

          <div v-else class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Code</th>
                  <th>Status</th>
                  <th>Order</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="academicClass in classes" :key="academicClass.id">
                  <td>{{ academicClass.name }}</td>
                  <td>{{ academicClass.code }}</td>
                  <td>{{ academicClass.status }}</td>
                  <td>{{ academicClass.sort_order }}</td>
                  <td>
                    <button
                      class="text-button"
                      type="button"
                      :disabled="academicClass.status === 'archived'"
                      @click="archiveClass(academicClass)"
                    >
                      Archive
                    </button>
                  </td>
                </tr>
                <tr v-if="classes.length === 0">
                  <td colspan="5">No classes yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>
</SchoolWorkspaceTemplate>
</template>


