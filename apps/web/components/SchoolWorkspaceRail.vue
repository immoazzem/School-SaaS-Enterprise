<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useDisplay } from 'vuetify'
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
const { mdAndUp } = useDisplay()

const mobileMenuOpen = ref(false)

const selectedSchool = computed(() =>
  auth.schools.value.find((school) => school.id === activeSchoolId.value)
  ?? auth.schools.value.find((school) => school.id === auth.selectedSchoolId.value)
  ?? null,
)

const activeSchoolId = computed(() => props.schoolId ?? selectedSchool.value?.id ?? null)

const schoolOptions = computed(() =>
  auth.schools.value.map(school => ({
    title: school.name,
    value: school.id,
  })),
)

const groupedModules = computed(() =>
  schoolWorkspaceGroups.map((group) => ({
    title: group.title,
    icon: group.icon,
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

const drawerModel = computed({
  get: () => mdAndUp.value || mobileMenuOpen.value,
  set: (value: boolean) => {
    if (!mdAndUp.value)
      mobileMenuOpen.value = value
  },
})

function canOpen(module: { permissions: string[] }) {
  const permissions = selectedSchool.value?.permissions ?? []

  return module.permissions.some(permission => permissions.includes(permission))
}

async function selectSchool(value: number | null) {
  if (!value)
    return

  auth.selectSchool(value)

  if (route.path.startsWith('/schools/')) {
    const [, , , ...rest] = route.path.split('/')
    await router.push(`/schools/${value}/${rest.join('/')}`)
  }
}

watch(() => route.path, () => {
  mobileMenuOpen.value = false
})

function iconPath(icon: string) {
  const icons: Record<string, string> = {
    'arrow-up': 'M12 19V5m0 0-5 5m5-5 5 5',
    badge: 'M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v12l-3-2-3 2-3-2-3 2V6a2 2 0 0 1 2-2Z',
    book: 'M4 6.5A2.5 2.5 0 0 1 6.5 4H20v13.5A2.5 2.5 0 0 0 17.5 15H4zM4 6.5V18a2 2 0 0 0 2 2h14',
    briefcase: 'M9 6V4h6v2m5 3H4m0 0v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9M4 9h16',
    calendar: 'M8 2v4m8-4v4M3 10h18M5 5h14a2 2 0 0 1 2 2v12H3V7a2 2 0 0 1 2-2Z',
    chart: 'M4 19h16M7 16V9m5 7V5m5 11v-6',
    'check-circle': 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    clipboard: 'M9 3h6l1 2h3v16H5V5h3l1-2Zm0 6h6m-6 4h6m-6 4h4',
    clock: 'M12 7v5l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    'credit-card': 'M3 7h18M5 5h14a2 2 0 0 1 2 2v10H3V7a2 2 0 0 1 2-2Zm0 8h4',
    edit: 'M4 20h4l10-10-4-4L4 16v4Zm9-13 4 4',
    file: 'M8 3h6l5 5v13H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm5 1v4h4',
    grid: 'M4 4h7v7H4zm9 0h7v7h-7zM4 13h7v7H4zm9 0h7v7h-7z',
    id: 'M4 6h16v12H4zm4 3h5m-5 3h8m3-3h.01M17 12h.01',
    nodes: 'M6 6h4v4H6zm8 0h4v4h-4zM10 14h4v4h-4zM10 8h4m-8 2v2m8-2v2',
    report: 'M7 3h10l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm3 6h6m-6 4h6m-6 4h4',
    schedule: 'M4 5h16v14H4zm4 0V3m8 2V3M8 11h3m2 0h3m-8 4h3',
    settings: 'M12 8.5A3.5 3.5 0 1 1 8.5 12 3.5 3.5 0 0 1 12 8.5Zm7 3.5-2.1.7a6.7 6.7 0 0 1-.5 1.2l1 2-2 2-2-1a6.7 6.7 0 0 1-1.2.5L12 19l-1.2-2.1a6.7 6.7 0 0 1-1.2-.5l-2 1-2-2 1-2a6.7 6.7 0 0 1-.5-1.2L4 12l2.1-1a6.7 6.7 0 0 1 .5-1.2l-1-2 2-2 2 1a6.7 6.7 0 0 1 1.2-.5L12 4l1 2.1a6.7 6.7 0 0 1 1.2.5l2-1 2 2-1 2c.2.4.4.8.5 1.2Z',
    stack: 'M12 4 4 8l8 4 8-4-8-4Zm-8 8 8 4 8-4M4 16l8 4 8-4',
    teacher: 'M12 5 3 9l9 4 7-3.1V17h2V9L12 5Zm-4 9.5V17c0 1.7 2.2 3 4 3s4-1.3 4-3v-2.5',
    users: 'M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2m18 0v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
    wallet: 'M4 7h15a2 2 0 0 1 2 2v8H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 0V5a2 2 0 0 1 2-2h10M21 12h-4v2h4',
  }

  return icons[icon] ?? icons.grid
}
</script>

<template>
  <div>
    <header class="workspace-mobilebar">
      <NuxtLink class="workspace-mobilebar__brand" to="/dashboard">
        <span class="workspace-mobilebar__mark">EA</span>
        <strong>School SaaS</strong>
      </NuxtLink>
      <VBtn
        color="default"
        icon
        size="small"
        variant="text"
        aria-label="Open menu"
        class="md:hidden"
        @click="mobileMenuOpen = true"
      >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </VBtn>
    </header>

    <VNavigationDrawer
      v-model="drawerModel"
      app
      :permanent="mdAndUp"
      :temporary="!mdAndUp"
      :scrim="!mdAndUp"
      width="296"
      class="workspace-admin-drawer"
    >
      <div class="operation-nav operation-nav--vuetify">
        <NuxtLink class="brand" to="/dashboard">
          <span>EA</span>
          <div class="flex flex-col">
            <strong class="text-slate-900 font-semibold tracking-tight">School SaaS</strong>
            <small class="text-[11px] uppercase tracking-[0.14em] text-slate-400">Enterprise Suite</small>
          </div>
        </NuxtLink>

        <div class="workspace-school-card">
          <div class="flex items-center gap-3">
            <span class="workspace-school-card__mark">{{ selectedSchool?.name?.slice(0, 2).toUpperCase() || 'SC' }}</span>
            <div class="min-w-0">
              <small class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Selected school</small>
              <strong class="block truncate text-sm font-semibold text-slate-900">{{ selectedSchool?.name || 'Choose a school' }}</strong>
            </div>
          </div>
        </div>

        <VSelect
          v-if="schoolOptions.length"
          class="workspace-school-select-vuetify"
          :model-value="activeSchoolId || null"
          :items="schoolOptions"
          label="Switch school"
          variant="outlined"
          density="comfortable"
          hide-details
          @update:model-value="selectSchool"
        />

        <nav :aria-label="ariaLabel" class="nav-groups">
          <section v-for="group in groupedModules" :key="group.title" class="nav-group">
            <h2 class="nav-group-title">
              <span class="nav-group-title__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="iconPath(group.icon)" />
                </svg>
              </span>
              {{ group.title }}
            </h2>

            <VList class="workspace-nav-list" density="compact" nav>
              <VListItem
                v-for="item in group.items"
                :key="item.label"
                :active="item.active"
                :disabled="!item.to"
                :to="item.to || undefined"
                rounded="lg"
                class="workspace-nav-item"
              >
                <template #prepend>
                  <span class="nav-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="iconPath(item.icon)" />
                    </svg>
                  </span>
                </template>

                <VListItemTitle class="nav-link__copy">
                  <strong>{{ item.label }}</strong>
                  <small>{{ item.description }}</small>
                </VListItemTitle>
              </VListItem>
            </VList>
          </section>
        </nav>

        <section v-if="contextLinks.length" class="context-links">
          <h2 class="nav-group-title">{{ contextTitle }}</h2>
          <VList class="workspace-nav-list" density="compact" nav>
            <VListItem
              v-for="link in contextLinks"
              :key="link.to"
              :active="route.path === link.to"
              :to="link.to"
              rounded="lg"
              class="workspace-nav-item"
            >
              <VListItemTitle class="nav-link__copy">
                <strong>{{ link.label }}</strong>
              </VListItemTitle>
            </VListItem>
          </VList>
        </section>
      </div>
    </VNavigationDrawer>
  </div>
</template>
