<script setup lang="ts">
import { schoolWorkspaceGroups, schoolWorkspaceModules } from '~/utils/schoolWorkspaceNav'
import { ref, computed } from 'vue'

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

const mobileMenuOpen = ref(false)

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

// Close mobile menu on route change
watch(() => route.path, () => {
  mobileMenuOpen.value = false
})
</script>

<template>
  <div>
    <header class="workspace-mobilebar">
      <NuxtLink class="workspace-mobilebar__brand" to="/dashboard">
        <span class="workspace-mobilebar__mark">EA</span>
        <strong>School SaaS</strong>
      </NuxtLink>
      <button
        class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900"
        aria-label="Open menu"
        @click="mobileMenuOpen = true"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </header>

    <div
      v-if="mobileMenuOpen"
      class="workspace-sidebar-drawer"
      @click="mobileMenuOpen = false"
    />

    <aside
      :class="[
        'operation-nav fixed left-0 top-0 z-50 flex h-screen w-[272px] overflow-y-auto transition-transform duration-300 ease-out md:sticky md:left-auto md:z-30 md:translate-x-0',
        mobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
      ]"
    >
      <button
        class="absolute right-4 top-4 rounded-md bg-slate-100 p-1.5 text-slate-400 hover:text-slate-700 md:hidden"
        aria-label="Close menu"
        @click="mobileMenuOpen = false"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <NuxtLink class="brand" to="/dashboard">
        <span>EA</span>
        <strong class="text-slate-900 font-semibold tracking-tight">School SaaS</strong>
      </NuxtLink>

      <div class="workspace-school-card">
        <small class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Selected school</small>
        <strong class="truncate text-sm font-semibold text-slate-900">{{ selectedSchool?.name || 'Choose a school' }}</strong>
      </div>

      <select
        v-if="auth.schools.value.length"
        class="workspace-school-select"
        :value="activeSchoolId || ''"
        aria-label="Select school"
        @change="selectSchool"
      >
        <option v-for="school in auth.schools.value" :key="school.id" :value="school.id">
          {{ school.name }}
        </option>
      </select>

      <nav :aria-label="ariaLabel" class="nav-groups">
        <section v-for="group in groupedModules" :key="group.title" class="nav-group">
          <h2 class="nav-group-title">{{ group.title }}</h2>
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

      <section v-if="contextLinks.length" class="context-links">
        <h2 class="nav-group-title">{{ contextTitle }}</h2>
        <NuxtLink
          v-for="link in contextLinks"
          :key="link.to"
          class="nav-link"
          :class="{ active: route.path === link.to }"
          :to="link.to"
        >
          {{ link.label }}
        </NuxtLink>
      </section>
    </aside>
  </div>
</template>
