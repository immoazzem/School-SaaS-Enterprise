<script setup lang="ts">
import { schoolWorkspaceGroups, schoolWorkspaceModules } from '~/utils/schoolWorkspaceNav'

type ContextLink = {
  label: string
  to: string
}

const props = withDefaults(defineProps<{
  schoolId?: number | null
  ariaLabel?: string
  contextTitle?: string
  contextLinks?: ContextLink[]
}>(), {
  schoolId: null,
  ariaLabel: 'School workspace navigation',
  contextTitle: 'This workspace',
  contextLinks: () => [],
})

const auth = useAuth()
const route = useRoute()
const router = useRouter()

const selectedSchool = computed(() =>
  auth.schools.value.find((school) => school.id === activeSchoolId.value)
  ?? auth.schools.value.find((school) => school.id === auth.selectedSchoolId.value)
  ?? null,
)

const activeSchoolId = computed(() => props.schoolId ?? selectedSchool.value?.id ?? null)

const groupedModules = computed(() =>
  schoolWorkspaceGroups.map((group) => ({
    title: group.title,
    items: schoolWorkspaceModules
      .filter((item) => item.tone === group.tone)
      .map((item) => {
        const enabled = canOpen(item)
        const to = activeSchoolId.value && enabled ? `/schools/${activeSchoolId.value}/${item.route}` : null

        return {
          ...item,
          enabled,
          to,
          active: to ? route.path === to : false,
        }
      }),
  })),
)

function canOpen(module: { permissions: string[] }) {
  const permissions = selectedSchool.value?.permissions ?? []

  return module.permissions.some((permission) => permissions.includes(permission))
}

function selectSchool(event: Event) {
  const value = Number((event.target as HTMLSelectElement).value)

  if (value) {
    auth.selectSchool(value)

    if (route.path.startsWith('/schools/')) {
      const [, , , ...rest] = route.path.split('/')
      void router.push(`/schools/${value}/${rest.join('/')}`)
    }
  }
}
</script>

<template>
  <aside class="workspace-rail">
    <NuxtLink class="brand" to="/dashboard">
      <span>EA</span>
      <strong>School SaaS</strong>
    </NuxtLink>

    <div class="school-chip">
      <small>Selected school</small>
      <strong>{{ selectedSchool?.name || 'Choose a school' }}</strong>
    </div>

    <select
      v-if="auth.schools.value.length"
      class="school-select"
      :value="activeSchoolId || ''"
      aria-label="Select school"
      @change="selectSchool"
    >
      <option v-for="school in auth.schools.value" :key="school.id" :value="school.id">
        {{ school.name }}
      </option>
    </select>

    <nav :aria-label="ariaLabel" class="module-nav">
      <section v-for="group in groupedModules" :key="group.title" class="nav-group">
        <h2>{{ group.title }}</h2>
        <template v-for="item in group.items" :key="item.label">
          <NuxtLink
            v-if="item.to"
            class="nav-link"
            :class="{ active: item.active }"
            :to="item.to"
          >
            <span>{{ item.label }}</span>
          </NuxtLink>
          <span v-else class="nav-link disabled">
            <span>{{ item.label }}</span>
          </span>
        </template>
      </section>
    </nav>

    <section v-if="contextLinks.length" class="context-nav">
      <h2>{{ contextTitle }}</h2>
      <NuxtLink
        v-for="link in contextLinks"
        :key="link.to"
        class="context-link"
        :class="{ active: route.path === link.to }"
        :to="link.to"
      >
        {{ link.label }}
      </NuxtLink>
    </section>
  </aside>
</template>

<style scoped>
.workspace-rail {
  position: sticky;
  top: 0;
  display: flex;
  height: 100vh;
  width: 288px;
  flex-direction: column;
  gap: 18px;
  overflow-y: auto;
  border-right: 1px solid #d9e1df;
  padding: 22px 18px 24px;
  background: #ffffff;
}

.brand {
  display: flex;
  gap: 12px;
  align-items: center;
  color: #18201d;
  text-decoration: none;
}

.brand span {
  display: grid;
  width: 38px;
  height: 38px;
  place-items: center;
  border-radius: 8px;
  background: #1f6f5b;
  color: #fff;
  font-weight: 900;
}

.brand strong,
.school-chip strong {
  color: #18201d;
}

.school-chip {
  display: grid;
  gap: 4px;
  border: 1px solid #d9e1df;
  border-radius: 8px;
  padding: 12px;
  background: #f8fbfa;
}

.school-chip small,
.nav-group h2,
.context-nav h2 {
  color: #687370;
  font-size: 0.78rem;
  font-weight: 900;
  text-transform: uppercase;
}

.school-select {
  min-height: 42px;
  border: 1px solid #cad6d3;
  border-radius: 8px;
  padding: 0 12px;
  background: #fff;
  color: #18201d;
}

.module-nav,
.context-nav {
  display: grid;
  gap: 16px;
}

.nav-group {
  display: grid;
  gap: 6px;
}

.nav-group h2,
.context-nav h2 {
  margin: 0 0 2px;
}

.nav-link,
.context-link {
  display: flex;
  min-height: 38px;
  align-items: center;
  border-radius: 8px;
  padding: 0 12px;
  color: #34423e;
  font-weight: 760;
  text-decoration: none;
}

.nav-link:hover,
.context-link:hover {
  background: #edf6f2;
  color: #1f6f5b;
}

.nav-link.active,
.context-link.active {
  background: #1f6f5b;
  color: #fff;
}

.nav-link.disabled {
  cursor: not-allowed;
  opacity: 0.42;
}

@media (max-width: 980px) {
  .workspace-rail {
    position: static;
    height: auto;
    width: 100%;
    border-right: 0;
    border-bottom: 1px solid #d9e1df;
  }
}
</style>
