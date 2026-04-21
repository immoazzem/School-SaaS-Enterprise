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
    <!-- Mobile Top Bar -->
    <header class="md:hidden flex items-center justify-between bg-white/80 backdrop-blur-md border-b border-slate-200 p-4 sticky top-0 z-30">
      <NuxtLink class="flex items-center gap-2.5" to="/dashboard">
        <span class="grid place-items-center w-8 h-8 rounded-lg bg-brand-600 text-white font-bold text-xs shadow-sm">EA</span>
        <strong class="text-slate-900 font-bold tracking-tight">School SaaS</strong>
      </NuxtLink>
      <button @click="mobileMenuOpen = true" class="p-2 -mr-2 text-slate-500 hover:text-slate-900 focus:outline-none rounded-lg" aria-label="Open menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>
    </header>

    <!-- Mobile Menu Backdrop -->
    <div 
      v-if="mobileMenuOpen" 
      class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 md:hidden transition-opacity" 
      @click="mobileMenuOpen = false"
    ></div>

    <!-- Sidebar / Drawer -->
    <aside 
      :class="[
        'operation-nav fixed md:sticky top-0 left-0 h-screen transition-transform duration-300 ease-out z-50 md:translate-x-0 w-[272px] overflow-y-auto',
        mobileMenuOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'
      ]"
    >
      <button @click="mobileMenuOpen = false" class="md:hidden absolute top-5 right-4 p-1.5 text-slate-400 hover:text-slate-700 bg-slate-100 rounded-full" aria-label="Close menu">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>

      <NuxtLink class="brand" to="/dashboard">
        <span>EA</span>
        <strong class="text-slate-900 font-bold tracking-tight">School SaaS</strong>
      </NuxtLink>

      <div class="flex flex-col gap-1 px-4 py-3 bg-slate-100/50 border border-slate-200/60 rounded-xl mt-2">
        <small class="text-xs font-bold text-slate-500 uppercase tracking-widest">Selected school</small>
        <strong class="text-base font-semibold text-slate-900 truncate">{{ selectedSchool?.name || 'Choose a school' }}</strong>
      </div>

      <select
        v-if="auth.schools.value.length"
        class="w-full min-h-[40px] px-3 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all cursor-pointer shadow-sm mt-1"
        :value="activeSchoolId || ''"
        aria-label="Select school"
        @change="selectSchool"
      >
        <option v-for="school in auth.schools.value" :key="school.id" :value="school.id">
          {{ school.name }}
        </option>
      </select>

      <nav :aria-label="ariaLabel" class="flex flex-col gap-6 mt-4">
        <section v-for="group in groupedModules" :key="group.title" class="flex flex-col gap-1.5">
          <h2 class="px-3 mb-1 text-xs font-bold text-slate-400 uppercase tracking-widest">{{ group.title }}</h2>
          <template v-for="item in group.items" :key="item.label">
            <NuxtLink
              v-if="item.to"
              class="flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150"
              :class="item.active ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
              :to="item.to"
            >
              <span>{{ item.label }}</span>
            </NuxtLink>
            <span v-else class="flex items-center px-4 py-2.5 text-base md:text-[0.92rem] font-medium text-slate-400 cursor-not-allowed opacity-60">
              <span>{{ item.label }}</span>
            </span>
          </template>
        </section>
      </nav>

      <section v-if="contextLinks.length" class="flex flex-col gap-1.5 mt-6 pt-6 border-t border-slate-200">
        <h2 class="px-3 mb-1 text-xs font-bold text-slate-400 uppercase tracking-widest">{{ contextTitle }}</h2>
        <NuxtLink
          v-for="link in contextLinks"
          :key="link.to"
          class="flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150"
          :class="route.path === link.to ? 'bg-slate-800 text-white font-semibold shadow-md' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
          :to="link.to"
        >
          {{ link.label }}
        </NuxtLink>
      </section>
    </aside>
  </div>
</template>
