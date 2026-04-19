<script setup lang="ts">
const auth = useAuth()
const router = useRouter()

const loading = ref(false)
const creatingSchool = ref(false)
const error = ref('')
const success = ref('')
const schoolForm = reactive({
  name: '',
  slug: '',
  timezone: 'Asia/Dhaka',
  locale: 'en',
})

const selectedSchool = computed(() =>
  auth.schools.value.find((school) => school.id === auth.selectedSchoolId.value),
)

const navItems = computed(() => [
  { label: 'Overview', active: true, enabled: true },
  {
    label: 'Academic Classes',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('academic_classes.manage') ?? false,
  },
  {
    label: 'Sections',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('sections.manage') ?? false,
  },
  {
    label: 'Academic Years',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('academic_years.manage') ?? false,
  },
  {
    label: 'Subjects',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('subjects.manage') ?? false,
  },
  {
    label: 'Class Subjects',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('class_subjects.manage') ?? false,
  },
  {
    label: 'Groups',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('student_groups.manage') ?? false,
  },
  {
    label: 'Shifts',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('shifts.manage') ?? false,
  },
  {
    label: 'Timetable',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('timetable.manage') ?? false,
  },
  {
    label: 'Assignments',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('assignments.manage') ?? false,
  },
  {
    label: 'People',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('students.manage') ?? false,
  },
  {
    label: 'Enrollments',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('enrollments.manage') ?? false,
  },
  {
    label: 'Teachers',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('teachers.manage') ?? false,
  },
  {
    label: 'Attendance',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('attendance.manage') ?? false,
  },
  {
    label: 'Exams',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('exams.manage') ?? false,
  },
  {
    label: 'Marks',
    active: false,
    enabled:
      selectedSchool.value?.permissions?.includes('marks.enter.any')
      || selectedSchool.value?.permissions?.includes('marks.enter.own')
      || false,
  },
  {
    label: 'Designations',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('designations.manage') ?? false,
  },
  {
    label: 'Finance',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('finance.manage') ?? false,
  },
  {
    label: 'Staff Ops',
    active: false,
    enabled:
      selectedSchool.value?.permissions?.includes('payroll.manage')
      || selectedSchool.value?.permissions?.includes('employee_attendance.manage')
      || selectedSchool.value?.permissions?.includes('leave.manage')
      || false,
  },
  {
    label: 'Reports',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('reports.view') ?? false,
  },
  {
    label: 'Promotions',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('promotions.manage') ?? false,
  },
  {
    label: 'Calendar',
    active: false,
    enabled:
      selectedSchool.value?.permissions?.includes('calendar.manage')
      || selectedSchool.value?.permissions?.includes('reports.view')
      || false,
  },
  {
    label: 'Documents',
    active: false,
    enabled: selectedSchool.value?.permissions?.includes('documents.manage') ?? false,
  },
])

async function loadDashboard() {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    await auth.refreshSchools()
    await auth.refreshProfile()
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

async function createSchool() {
  creatingSchool.value = true
  error.value = ''
  success.value = ''

  try {
    const school = await auth.createSchool({
      name: schoolForm.name,
      slug: schoolForm.slug || undefined,
      timezone: schoolForm.timezone || undefined,
      locale: schoolForm.locale || undefined,
    })

    success.value = `${school.name} is ready.`
    schoolForm.name = ''
    schoolForm.slug = ''
  } catch (schoolError) {
    error.value = schoolError instanceof Error ? schoolError.message : 'Unable to create school.'
  } finally {
    creatingSchool.value = false
  }
}

async function openClasses() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/academic-classes`)
}

async function openSections() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/academic-sections`)
}

async function openAcademicYears() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/academic-years`)
}

async function openSubjects() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/subjects`)
}

async function openClassSubjects() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/class-subjects`)
}

async function openStudentGroups() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/student-groups`)
}

async function openShifts() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/shifts`)
}

async function openTimetable() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/timetable`)
}

async function openAssignments() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/assignments`)
}

async function openDesignations() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/designations`)
}

async function openEmployees() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/employees`)
}

async function openStudents() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/students`)
}

async function openEnrollments() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/enrollments`)
}

async function openTeachers() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/teacher-profiles`)
}

async function openAttendance() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/attendance`)
}

async function openExams() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/exams`)
}

async function openMarks() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/marks`)
}

async function openFinance() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/finance`)
}

async function openStaffOperations() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/staff-operations`)
}

async function openReports() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/reports`)
}

async function openPromotions() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/promotions`)
}

async function openCalendar() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/calendar`)
}

async function openDocuments() {
  if (!auth.selectedSchoolId.value) {
    error.value = 'Create or select a school first.'
    return
  }

  await router.push(`/schools/${auth.selectedSchoolId.value}/documents`)
}

const navActions: Record<string, () => Promise<void>> = {
  'Academic Classes': openClasses,
  Sections: openSections,
  'Academic Years': openAcademicYears,
  Subjects: openSubjects,
  'Class Subjects': openClassSubjects,
  Groups: openStudentGroups,
  Shifts: openShifts,
  Timetable: openTimetable,
  Assignments: openAssignments,
  Designations: openDesignations,
  People: openStudents,
  Enrollments: openEnrollments,
  Teachers: openTeachers,
  Attendance: openAttendance,
  Exams: openExams,
  Marks: openMarks,
  Finance: openFinance,
  'Staff Ops': openStaffOperations,
  Reports: openReports,
  Promotions: openPromotions,
  Calendar: openCalendar,
  Documents: openDocuments,
}

async function openNavItem(label: string) {
  await navActions[label]?.()
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
        <button
          v-for="item in navItems"
          :key="item.label"
          class="nav-item"
          :class="{ active: item.active }"
          type="button"
          :disabled="!item.enabled"
          @click="openNavItem(item.label)"
        >
          <span>{{ item.label }}</span>
          <small v-if="!item.enabled">Locked</small>
        </button>
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
      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="loading" class="muted">Loading workspace</p>

      <section class="metrics">
        <article class="metric surface">
          <span>Schools</span>
          <strong>{{ auth.schools.value.length }}</strong>
        </article>
        <article class="metric surface">
          <span>Access</span>
          <strong>{{ selectedSchool?.roles?.[0]?.name || (auth.token.value ? 'Token' : 'Guest') }}</strong>
        </article>
        <article class="metric surface">
          <span>Module</span>
          <strong>Academics</strong>
        </article>
      </section>

      <section class="workspace-grid">
        <form class="surface create-school" @submit.prevent="createSchool">
          <div>
            <p class="muted">School setup</p>
            <h2>Create a school</h2>
            <p>Start a tenant, assign yourself as owner, and continue into Academic Classes.</p>
          </div>

          <div class="field">
            <label for="school-name">School name</label>
            <input
              id="school-name"
              v-model="schoolForm.name"
              autocomplete="organization"
              required
              type="text"
              placeholder="Example International School"
            />
          </div>

          <div class="field">
            <label for="school-slug">Slug</label>
            <input
              id="school-slug"
              v-model="schoolForm.slug"
              type="text"
              placeholder="example-international-school"
            />
          </div>

          <div class="form-row">
            <div class="field">
              <label for="school-timezone">Timezone</label>
              <input id="school-timezone" v-model="schoolForm.timezone" type="text" />
            </div>
            <div class="field">
              <label for="school-locale">Locale</label>
              <input id="school-locale" v-model="schoolForm.locale" type="text" />
            </div>
          </div>

          <button class="button" type="submit" :disabled="creatingSchool">
            {{ creatingSchool ? 'Creating school' : 'Create school' }}
          </button>
        </form>

        <section class="surface tenant-list">
          <div>
            <p class="muted">Active tenants</p>
            <h2>Schools</h2>
          </div>

          <div v-if="auth.schools.value.length" class="school-list">
            <button
              v-for="school in auth.schools.value"
              :key="school.id"
              class="school-row"
              :class="{ selected: school.id === auth.selectedSchoolId.value }"
              type="button"
              @click="auth.selectSchool(school.id)"
            >
              <span>
                <strong>{{ school.name }}</strong>
                <small>{{ school.slug }} · {{ school.roles?.[0]?.name || 'Member' }}</small>
              </span>
              <em>{{ school.status }}</em>
            </button>
          </div>

          <p v-else class="empty-copy">No schools yet. Create the first tenant to unlock setup modules.</p>
        </section>
      </section>

      <section class="surface action-strip">
        <div>
          <p class="muted">Next task</p>
          <h2>Set up Academic Classes</h2>
          <p>Manage class names, sections, academic years, and subjects inside the active school.</p>
        </div>
        <div class="strip-actions">
          <button class="button" type="button" @click="openClasses">Open classes</button>
          <button class="button secondary" type="button" @click="openAcademicYears">Open years</button>
          <button class="button secondary" type="button" @click="openSubjects">Open subjects</button>
          <button class="button secondary" type="button" @click="openClassSubjects">Assign subjects</button>
          <button class="button secondary" type="button" @click="openStudentGroups">Open groups</button>
          <button class="button secondary" type="button" @click="openShifts">Open shifts</button>
          <button class="button secondary" type="button" @click="openTimetable">Open timetable</button>
          <button class="button secondary" type="button" @click="openAssignments">Open assignments</button>
          <button class="button secondary" type="button" @click="openDesignations">Open designations</button>
          <button class="button secondary" type="button" @click="openEmployees">Open employees</button>
          <button class="button secondary" type="button" @click="openStudents">Open students</button>
          <button class="button secondary" type="button" @click="openEnrollments">Open enrollments</button>
          <button class="button secondary" type="button" @click="openTeachers">Open teachers</button>
          <button class="button secondary" type="button" @click="openAttendance">Open attendance</button>
          <button class="button secondary" type="button" @click="openExams">Open exams</button>
          <button class="button secondary" type="button" @click="openMarks">Open marks</button>
          <button class="button secondary" type="button" @click="openFinance">Open finance</button>
          <button class="button secondary" type="button" @click="openStaffOperations">Open staff ops</button>
          <button class="button secondary" type="button" @click="openReports">Open reports</button>
          <button class="button secondary" type="button" @click="openPromotions">Open promotions</button>
          <button class="button secondary" type="button" @click="openCalendar">Open calendar</button>
          <button class="button secondary" type="button" @click="openDocuments">Open documents</button>
        </div>
      </section>
    </section>
  </main>
</template>

<style scoped>
.shell {
  position: relative;
  isolation: isolate;
  display: grid;
  min-height: 100vh;
  grid-template-columns: 284px minmax(0, 1fr);
  background:
    radial-gradient(circle at top right, rgba(238, 135, 203, 0.26), transparent 28rem),
    radial-gradient(circle at 8% 6%, rgba(255, 241, 190, 0.75), transparent 25rem),
    linear-gradient(180deg, #fffaf4 0%, #f5f0ed 55%, #eceff3 100%);
  overflow: hidden;
}

.shell::before {
  position: fixed;
  inset: 16px;
  z-index: -1;
  border-radius: 8px;
  background:
    linear-gradient(rgba(17, 24, 39, 0.045) 1px, transparent 1px),
    linear-gradient(90deg, rgba(17, 24, 39, 0.045) 1px, transparent 1px);
  background-size: 64px 64px;
  content: "";
  pointer-events: none;
}

.sidebar {
  position: sticky;
  top: 0;
  display: flex;
  min-height: 100vh;
  flex-direction: column;
  gap: 30px;
  border-right: 1px solid rgba(17, 24, 39, 0.08);
  padding: 24px;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.74), rgba(255, 255, 255, 0.48)),
    linear-gradient(115deg, rgba(255, 241, 190, 0.42), rgba(238, 135, 203, 0.12) 70%, rgba(176, 96, 255, 0.12));
  backdrop-filter: blur(18px);
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
  border-radius: 999px;
  background: #111827;
  box-shadow: 0 14px 28px rgba(17, 24, 39, 0.18);
  color: #fff;
  font-weight: 900;
}

.brand strong {
  color: #111827;
}

nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-item {
  display: flex;
  min-height: 42px;
  align-items: center;
  justify-content: space-between;
  border: 0;
  border-radius: 999px;
  padding: 0 14px;
  background: transparent;
  color: #4b5563;
  cursor: pointer;
  font-weight: 740;
  text-align: left;
  transition:
    background 160ms ease,
    color 160ms ease,
    transform 160ms ease;
}

.nav-item:hover,
.nav-item.active {
  background: rgba(255, 255, 255, 0.62);
  color: #111827;
  transform: translateX(2px);
}

.nav-item:disabled {
  cursor: not-allowed;
  opacity: 0.58;
}

.nav-item small {
  font-size: 0.72rem;
  font-weight: 800;
}

.workspace {
  position: relative;
  display: grid;
  align-content: start;
  gap: 26px;
  padding: 34px;
}

.workspace::before {
  position: absolute;
  inset: 0 34px auto;
  height: 10px;
  border-top: 1px solid rgba(17, 24, 39, 0.06);
  border-bottom: 1px solid rgba(17, 24, 39, 0.06);
  content: "";
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
  color: #111827;
  letter-spacing: -0.04em;
}

.topbar h1 {
  font-size: clamp(2.4rem, 5vw, 4.8rem);
  font-weight: 760;
  line-height: 0.9;
}

.topbar-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.school-select {
  min-height: 44px;
  border: 1px solid rgba(17, 24, 39, 0.1);
  border-radius: 999px;
  padding: 0 12px;
  background: rgba(255, 255, 255, 0.7);
  color: #111827;
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
  color: #6b7280;
  font-weight: 760;
}

.metric strong {
  color: #111827;
  font-size: 2rem;
  letter-spacing: -0.04em;
}

.action-strip {
  display: flex;
  gap: 20px;
  align-items: center;
  justify-content: space-between;
  padding: 24px;
}

.workspace-grid {
  display: grid;
  grid-template-columns: minmax(320px, 0.9fr) minmax(0, 1.1fr);
  gap: 16px;
}

.create-school,
.tenant-list {
  display: grid;
  align-content: start;
  gap: 18px;
  padding: 24px;
}

.create-school h2,
.tenant-list h2 {
  margin: 0;
  color: #111827;
  letter-spacing: -0.025em;
}

.create-school p,
.empty-copy {
  margin: 8px 0 0;
  color: #6b7280;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.school-list {
  display: grid;
  gap: 10px;
}

.school-row {
  display: flex;
  min-height: 68px;
  align-items: center;
  justify-content: space-between;
  border: 1px solid rgba(17, 24, 39, 0.08);
  border-radius: 8px;
  padding: 12px 14px;
  background: rgba(255, 255, 255, 0.66);
  color: #111827;
  cursor: pointer;
  text-align: left;
  transition:
    border-color 160ms ease,
    background 160ms ease,
    transform 160ms ease;
}

.school-row:hover,
.school-row.selected {
  border-color: rgba(209, 80, 82, 0.38);
  background: rgba(255, 255, 255, 0.86);
  transform: translateY(-1px);
}

.school-row span {
  display: grid;
  gap: 4px;
}

.school-row small {
  color: #6b7280;
}

.school-row em {
  color: #be3455;
  font-style: normal;
  font-weight: 800;
  text-transform: capitalize;
}

.action-strip p {
  max-width: 620px;
  margin: 10px 0 0;
  color: #6b7280;
}

.strip-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

@media (max-width: 860px) {
  .shell {
    grid-template-columns: 1fr;
  }

  .sidebar {
    border-right: 0;
    border-bottom: 1px solid rgba(17, 24, 39, 0.08);
  }

  .metrics,
  .workspace-grid,
  .topbar,
  .form-row,
  .action-strip {
    grid-template-columns: 1fr;
  }

  .topbar,
  .topbar-actions,
  .action-strip,
  .strip-actions {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
