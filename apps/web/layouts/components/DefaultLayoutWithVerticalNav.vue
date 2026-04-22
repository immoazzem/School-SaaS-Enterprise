<script lang="ts" setup>
import navItems from '@/navigation/vertical'
import { themeConfig } from '@themeConfig'
import Footer from '@/layouts/components/Footer.vue'
import UserProfile from '@/layouts/components/UserProfile.vue'
import NavBarI18n from '@core/components/I18n.vue'
import { VerticalNavLayout } from '@layouts'

const session = useSession()

const schoolOptions = computed(() => session.schools.value.map(school => ({
  title: school.name,
  value: school.id,
})))

const activeSchool = computed({
  get: () => session.selectedSchool.value?.id ?? null,
  set: value => session.applySelectedSchool(value ? Number(value) : null),
})

const workspaceSummary = computed(() => {
  const campusCount = session.schools.value.length
  const activeName = session.selectedSchool.value?.name ?? 'No school selected'

  if (!campusCount)
    return 'No active campus membership found'

  return `${activeName} · ${campusCount} ${campusCount === 1 ? 'campus' : 'campuses'} in portfolio`
})
</script>

<template>
  <VerticalNavLayout :nav-items="navItems">
    <!-- 👉 navbar -->
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="signal-navbar">
        <IconBtn
          id="vertical-nav-toggle-btn"
          class="d-lg-none"
          @click="toggleVerticalOverlayNavActive(true)"
        >
          <VIcon
            size="26"
            icon="tabler-menu-2"
          />
        </IconBtn>

        <div class="signal-navbar__meta">
          <div class="signal-navbar__eyebrow">
            Enterprise workspace
          </div>
          <div class="signal-navbar__summary">
            {{ workspaceSummary }}
          </div>
        </div>

        <div class="signal-navbar__actions">
          <VTextField
            model-value=""
            density="compact"
            hide-details
            variant="solo-filled"
            rounded="lg"
            prepend-inner-icon="tabler-search"
            placeholder="Search students, invoices, notices"
            class="signal-navbar__search"
          />

          <VSelect
            v-model="activeSchool"
            :items="schoolOptions"
            density="compact"
            hide-details
            variant="outlined"
            rounded="lg"
            class="signal-navbar__school"
            :disabled="!schoolOptions.length"
          />

          <NavBarI18n
            v-if="themeConfig.app.i18n.enable && themeConfig.app.i18n.langConfig?.length"
            :languages="themeConfig.app.i18n.langConfig"
          />
          <UserProfile />
        </div>
      </div>
    </template>

    <!-- 👉 Pages -->
    <slot />

    <!-- 👉 Footer -->
    <template #footer>
      <Footer />
    </template>

    <!-- 👉 Customizer -->
    <!-- <TheCustomizer /> -->
  </VerticalNavLayout>
</template>
