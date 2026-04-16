<script setup lang="ts">
const auth = useAuth()
const router = useRouter()

const loading = ref(false)
const error = ref('')

const selectedSchool = computed(() =>
  auth.schools.value.find((school) => school.id === auth.selectedSchoolId.value),
)

async function loadDashboard() {
  loading.value = true
  error.value = ''

  try {
    await auth.refreshProfile()
    await auth.refreshSchools()
  } catch (dashboardError) {
    error.value = dashboardError instanceof Error ? dashboardError.message : 'Unable to load dashboard.'
  } finally {
    loading.value = false
  }
}

function chooseSchool(event: Event) {
  const value = Number((event.target as HTMLSelectElement).value)
  auth.selectSchool(value)
}

async function openClasses() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/academic-classes`)
}

onMounted(loadDashboard)
</script>

<template>
  <main class="shell">
    <aside class="sidebar">
      <NuxtLink class="brand" to="/dashboard">
        <span>EA</span>
        <strong>School SaaS</strong>
      </NuxtLink>
      <nav aria-label="Main navigation">
        <button class="nav-item active" type="button">Overview</button>
        <button class="nav-item" type="button" @click="openClasses">Academic Classes</button>
        <button class="nav-item" type="button">People</button>
        <button class="nav-item" type="button">Finance</button>
      </nav>
    </aside>

    <section class="workspace">
      <header class="topbar">
        <div>
          <p class="muted">Selected school</p>
          <h1>{{ selectedSchool?.name || 'No school selected' }}</h1>
        </div>

        <div class="topbar-actions">
          <select
            v-if="auth.schools.value.length"
            class="school-select"
            :value="auth.selectedSchoolId.value || ''"
            @change="chooseSchool"
          >
            <option v-for="school in auth.schools.value" :key="school.id" :value="school.id">
              {{ school.name }}
            </option>
          </select>
          <button class="button secondary" type="button" @click="auth.logout()">Sign out</button>
        </div>
      </header>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="loading" class="muted">Loading workspace</p>

      <section class="metrics">
        <article class="metric surface">
          <span>Schools</span>
          <strong>{{ auth.schools.value.length }}</strong>
        </article>
        <article class="metric surface">
          <span>Access</span>
          <strong>{{ auth.token.value ? 'Token' : 'Guest' }}</strong>
        </article>
        <article class="metric surface">
          <span>Module</span>
          <strong>Academics</strong>
        </article>
      </section>

      <section class="surface action-strip">
        <div>
          <p class="muted">Next task</p>
          <h2>Set up Academic Classes</h2>
          <p>Manage class names, codes, ordering, and status inside the active school.</p>
        </div>
        <button class="button" type="button" @click="openClasses">Open classes</button>
      </section>
    </section>
  </main>
</template>

<style scoped>
.shell {
  display: grid;
  min-height: 100vh;
  grid-template-columns: 260px minmax(0, 1fr);
  background: #f6f8f7;
}

.sidebar {
  display: flex;
  flex-direction: column;
  gap: 30px;
  border-right: 1px solid #dbe5e1;
  padding: 24px;
  background: #fff;
}

.brand {
  display: flex;
  gap: 12px;
  align-items: center;
}

.brand span {
  display: grid;
  width: 38px;
  height: 38px;
  place-items: center;
  border-radius: 8px;
  background: #0f5f4a;
  color: #fff;
  font-weight: 900;
}

.brand strong {
  color: #16201c;
}

nav {
  display: grid;
  gap: 8px;
}

.nav-item {
  min-height: 42px;
  border: 0;
  border-radius: 8px;
  padding: 0 12px;
  background: transparent;
  color: #53665e;
  cursor: pointer;
  text-align: left;
}

.nav-item:hover,
.nav-item.active {
  background: #eef5f1;
  color: #0f5f4a;
}

.workspace {
  display: grid;
  align-content: start;
  gap: 24px;
  padding: 30px;
}

.topbar {
  display: flex;
  gap: 20px;
  align-items: center;
  justify-content: space-between;
}

.topbar h1,
.action-strip h2 {
  margin: 0;
  color: #16201c;
}

.topbar-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.school-select {
  min-height: 44px;
  border: 1px solid #cbdad4;
  border-radius: 8px;
  padding: 0 12px;
  background: #fff;
}

.metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.metric {
  display: grid;
  gap: 12px;
  padding: 20px;
}

.metric span {
  color: #607169;
  font-weight: 700;
}

.metric strong {
  color: #16201c;
  font-size: 2rem;
}

.action-strip {
  display: flex;
  gap: 20px;
  align-items: center;
  justify-content: space-between;
  padding: 24px;
}

.action-strip p {
  max-width: 620px;
  margin: 10px 0 0;
  color: #607169;
}

@media (max-width: 860px) {
  .shell {
    grid-template-columns: 1fr;
  }

  .sidebar {
    border-right: 0;
    border-bottom: 1px solid #dbe5e1;
  }

  .metrics,
  .topbar,
  .action-strip {
    grid-template-columns: 1fr;
  }

  .topbar,
  .topbar-actions,
  .action-strip {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
