<script setup lang="ts">
import { schoolWorkspaceModules } from '~/utils/schoolWorkspaceNav'

const props = withDefaults(defineProps<{
  contentClass?: string
}>(), {
  contentClass: '',
})

const contentClasses = computed(() => ['workspace-stage', props.contentClass])
const route = useRoute()
const auth = useAuth()

const activeSchool = computed(() =>
  auth.schools.value.find((school) => school.id === auth.selectedSchoolId.value) ?? null,
)

const activeModule = computed(() => {
  if (route.path === '/dashboard')
    return {
      label: 'Dashboard',
      description: 'School-wide command center and launchpad',
    }

  const match = schoolWorkspaceModules.find(module => route.path.includes(`/${module.route}`))

  return match
    ? { label: match.label, description: match.description }
    : {
        label: 'Workspace',
        description: 'Shared operating shell',
      }
})

const breadcrumbItems = computed(() => {
  const items = ['Workspace']

  if (activeSchool.value?.name)
    items.push(activeSchool.value.name)

  items.push(activeModule.value.label)

  return items
})
</script>

<template>
  <main class="workspace-shell">
    <slot name="navigation" />
    <section :class="contentClasses">
      <div class="workspace-content-inner workspace-content-inner--framed">
        <VSheet class="workspace-topbar" color="surface" rounded="lg">
          <div class="workspace-topbar__meta">
            <VBreadcrumbs :items="breadcrumbItems" class="workspace-breadcrumbs" density="compact" />
            <div class="workspace-topbar__titles">
              <div>
                <p class="workspace-topbar__eyebrow">Enterprise workspace</p>
                <h1>{{ activeModule.label }}</h1>
              </div>
              <p>{{ activeModule.description }}</p>
            </div>
          </div>

          <div class="workspace-topbar__actions">
            <VChip
              v-if="activeSchool"
              class="workspace-topbar__school"
              color="success"
              variant="tonal"
              rounded="lg"
            >
              <span class="workspace-topbar__school-dot" />
              <div>
                <strong>{{ activeSchool.name }}</strong>
                <small>{{ activeSchool.slug }}</small>
              </div>
            </VChip>

            <VChip
              v-if="auth.user?.email"
              class="workspace-topbar__user"
              color="default"
              variant="outlined"
              rounded="lg"
            >
              <span>{{ auth.user.email }}</span>
            </VChip>

            <slot name="topbar-actions" />
          </div>
        </VSheet>

        <slot />
      </div>
    </section>
  </main>
</template>
