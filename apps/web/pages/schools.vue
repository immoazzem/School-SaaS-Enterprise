<script setup lang="ts">
definePageMeta({
  layout: 'default',
})

interface SchoolDetailResponse {
  data: AuthSchool & {
    timezone?: string
    locale?: string
    status?: string
  }
}

const auth = useAuth()
const router = useRouter()

const loading = ref(false)
const creating = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const selectedSchoolProfile = ref<SchoolDetailResponse['data'] | null>(null)

const createForm = reactive({
  name: '',
  timezone: 'Asia/Dhaka',
  locale: 'en',
})

const selectedSchool = computed(() => auth.selectedSchool.value)
const schools = computed(() => auth.schools.value)

const portfolioSignals = computed(() => [
  {
    title: 'Campuses in portfolio',
    value: schools.value.length.toLocaleString(),
    note: 'Schools currently available in this operator workspace.',
  },
  {
    title: 'Permissions in focus',
    value: (selectedSchool.value?.permissions?.length ?? 0).toLocaleString(),
    note: selectedSchool.value ? `${selectedSchool.value.name} access map` : 'Pick a school to inspect access depth.',
  },
  {
    title: 'Current operating school',
    value: selectedSchool.value?.name ?? 'Not selected',
    note: 'This is the context used by the school workspaces and settings surfaces.',
  },
])

async function loadPortfolio() {
  loading.value = true
  errorMessage.value = ''

  try {
    await auth.refreshProfile()

    if (selectedSchool.value) {
      const api = useApi()
      const response = await api.request<SchoolDetailResponse>(`/schools/${selectedSchool.value.id}`)
      selectedSchoolProfile.value = response.data
    }
    else {
      selectedSchoolProfile.value = null
    }
  }
  catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to load the school portfolio right now.'
  }
  finally {
    loading.value = false
  }
}

async function createSchool() {
  creating.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const school = await auth.createSchool({
      name: createForm.name,
      timezone: createForm.timezone,
      locale: createForm.locale,
    })

    successMessage.value = `${school.name} is ready for onboarding.`
    createForm.name = ''
    await loadPortfolio()
  }
  catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to create the school right now.'
  }
  finally {
    creating.value = false
  }
}

function switchSchool(id: number) {
  auth.selectSchool(id)
  successMessage.value = 'School context updated.'
  loadPortfolio()
}

function openSchool(id: number) {
  auth.selectSchool(id)
  router.push(`/schools/${id}/students`)
}

onMounted(loadPortfolio)
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Portfolio"
      title="School portfolio"
      subtitle="Create campuses, switch operating context, and move into the live school workspaces from one place."
    >
      <template #actions>
        <VBtn
          v-if="selectedSchool"
          variant="outlined"
          color="default"
          prepend-icon="tabler-settings"
          to="/settings"
        >
          Manage selected school
        </VBtn>
        <VBtn
          v-if="selectedSchool"
          color="primary"
          prepend-icon="tabler-arrow-right"
          :to="`/schools/${selectedSchool.id}/students`"
        >
          Open school workspace
        </VBtn>
      </template>
    </SchoolPageHeader>

    <VAlert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      class="mb-4"
    >
      {{ errorMessage }}
    </VAlert>

    <VAlert
      v-if="successMessage"
      type="success"
      variant="tonal"
      class="mb-4"
    >
      {{ successMessage }}
    </VAlert>

    <VRow class="mb-2">
      <VCol
        v-for="signal in portfolioSignals"
        :key="signal.title"
        cols="12"
        md="4"
      >
        <VCard class="school-signal-card h-100">
          <VCardText class="pa-5">
            <div class="school-kicker mb-3">
              Portfolio signal
            </div>
            <div class="school-metric-card__value text-h6 mb-2">
              {{ signal.value }}
            </div>
            <div class="text-h6 font-weight-bold mb-2">
              {{ signal.title }}
            </div>
            <div class="text-body-2 text-medium-emphasis">
              {{ signal.note }}
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" xl="8">
        <VCard class="school-signal-card">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Portfolio directory
            </VCardTitle>
            <VCardSubtitle>
              Choose the school you want the rest of the platform to operate against.
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <VTable class="school-data-table">
              <thead>
                <tr>
                  <th>School</th>
                  <th>Status</th>
                  <th>Roles</th>
                  <th>Permissions</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="school in schools" :key="school.id">
                  <td class="font-weight-medium">
                    {{ school.name }}
                  </td>
                  <td>
                    <VChip size="small" variant="tonal" color="success">
                      {{ school.status }}
                    </VChip>
                  </td>
                  <td>
                    {{ school.roles?.map(role => role.name).join(', ') || 'Member' }}
                  </td>
                  <td>
                    {{ school.permissions?.length ?? 0 }}
                  </td>
                  <td class="d-flex flex-wrap gap-2">
                    <VBtn size="small" variant="outlined" color="default" @click="switchSchool(school.id)">
                      Select
                    </VBtn>
                    <VBtn size="small" color="primary" @click="openSchool(school.id)">
                      Open
                    </VBtn>
                  </td>
                </tr>
                <tr v-if="!schools.length && !loading">
                  <td colspan="5" class="text-medium-emphasis">
                    No schools are attached to this account yet.
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" xl="4">
        <VCard class="school-signal-card mb-4">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Create school
            </VCardTitle>
            <VCardSubtitle>
              Start a fresh campus with the default enterprise setup.
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <VForm @submit.prevent="createSchool">
              <VRow>
                <VCol cols="12">
                  <AppTextField
                    v-model="createForm.name"
                    label="School name"
                    placeholder="North Campus"
                    required
                  />
                </VCol>
                <VCol cols="12">
                  <AppTextField
                    v-model="createForm.timezone"
                    label="Timezone"
                    placeholder="Asia/Dhaka"
                    required
                  />
                </VCol>
                <VCol cols="12">
                  <AppTextField
                    v-model="createForm.locale"
                    label="Locale"
                    placeholder="en"
                    required
                  />
                </VCol>
                <VCol cols="12">
                  <VBtn block color="primary" type="submit" :loading="creating">
                    Create school
                  </VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>

        <VCard
          v-if="selectedSchoolProfile"
          class="school-signal-card"
        >
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Selected school
            </VCardTitle>
            <VCardSubtitle>
              The active operating context for settings, notifications, and school workspace routes.
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <div class="school-alert-list">
              <div class="school-alert-list__item">
                <div>
                  <div class="font-weight-medium">{{ selectedSchoolProfile.name }}</div>
                  <div class="text-body-2 text-medium-emphasis">Slug: {{ selectedSchoolProfile.slug }}</div>
                </div>
                <VChip size="small" color="primary" variant="tonal">
                  Selected
                </VChip>
              </div>
              <div class="school-alert-list__item">
                <div>
                  <div class="font-weight-medium">Timezone</div>
                  <div class="text-body-2 text-medium-emphasis">{{ selectedSchoolProfile.timezone || 'Not set' }}</div>
                </div>
              </div>
              <div class="school-alert-list__item">
                <div>
                  <div class="font-weight-medium">Locale</div>
                  <div class="text-body-2 text-medium-emphasis">{{ selectedSchoolProfile.locale || 'Not set' }}</div>
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
