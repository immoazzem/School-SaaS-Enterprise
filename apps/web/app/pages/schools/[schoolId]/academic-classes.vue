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
      `/schools/${schoolId.value}/academic-classes`,
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
  <main class="classes-page">
    <header class="classes-header">
      <div>
        <NuxtLink class="back-link" to="/dashboard">Dashboard</NuxtLink>
        <h1>Academic Classes</h1>
        <p>Manage the class list for the active school.</p>
      </div>
      <div class="header-actions">
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/academic-sections`">
          Manage sections
        </NuxtLink>
        <NuxtLink class="button secondary" :to="`/schools/${schoolId}/academic-years`">
          Manage years
        </NuxtLink>
      </div>
    </header>

    <section class="classes-grid">
      <form class="surface class-form" @submit.prevent="createClass">
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

      <section class="surface class-list">
        <div class="list-heading">
          <h2>Class register</h2>
          <span>{{ classes.length }} records</span>
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
  </main>
</template>

<style scoped>
.classes-page {
  min-height: 100vh;
  padding: 30px;
  background: #f6f8f7;
}

.classes-header {
  display: flex;
  gap: 20px;
  align-items: end;
  justify-content: space-between;
  margin-bottom: 24px;
}

.back-link {
  color: #0f5f4a;
  font-weight: 800;
}

h1 {
  margin: 12px 0 0;
  color: #16201c;
  font-size: clamp(2.2rem, 6vw, 4.5rem);
  line-height: 0.95;
}

.classes-header p {
  margin: 18px 0 0;
  color: #607169;
}

.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.classes-grid {
  display: grid;
  grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
  gap: 18px;
}

.class-form,
.class-list {
  padding: 22px;
}

.class-form {
  display: grid;
  gap: 16px;
  align-content: start;
}

.class-form h2,
.list-heading h2 {
  margin: 0;
  color: #16201c;
}

.list-heading {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  align-items: center;
  margin-bottom: 18px;
}

.list-heading span {
  color: #607169;
  font-weight: 800;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  min-width: 640px;
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
  color: #607169;
  font-size: 0.84rem;
  text-transform: uppercase;
}

.text-button {
  border: 0;
  background: transparent;
  color: #0f5f4a;
  cursor: pointer;
  font-weight: 800;
}

.text-button:disabled {
  color: #91a29b;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .classes-page {
    padding: 22px;
  }

  .classes-grid {
    grid-template-columns: 1fr;
  }

  .classes-header,
  .header-actions {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
