<script setup lang="ts">
definePageMeta({ layout: 'default' })

interface AdminSchool {
  id: number
  name: string
  slug: string
  status: string
  plan?: string
  subscription_status?: string
  memberships_count?: number
  students_count?: number
  employees_count?: number
}

interface ListResponse<T> { data: T[] }

const api = useApi()
const { canAccessEnterpriseAdmin } = useEnterpriseAccess()
const schools = ref<AdminSchool[]>([])
const loading = ref(false)
const onboardingId = ref<number | null>(null)
const error = ref('')
const success = ref('')

async function loadSchools() {
  if (!canAccessEnterpriseAdmin.value) {
    schools.value = []
    error.value = ''
    return
  }

  loading.value = true
  error.value = ''
  try {
    const response = await api.request<ListResponse<AdminSchool>>('/admin/schools?per_page=100')
    schools.value = response.data
  }
  catch (loadError) {
    error.value = loadError instanceof Error ? loadError.message : 'Unable to load enterprise schools.'
  }
  finally {
    loading.value = false
  }
}

async function onboardSchool(id: number) {
  if (!canAccessEnterpriseAdmin.value)
    return

  onboardingId.value = id
  error.value = ''
  success.value = ''
  try {
    await api.request(`/admin/schools/${id}/onboard`, { method: 'POST' })
    success.value = 'School onboarding completed.'
    await loadSchools()
  }
  catch (onboardError) {
    error.value = onboardError instanceof Error ? onboardError.message : 'Unable to onboard school.'
  }
  finally {
    onboardingId.value = null
  }
}

onMounted(loadSchools)
</script>

<template>
  <div>
    <SchoolPageHeader eyebrow="Enterprise" title="Admin schools" subtitle="Manage portfolio schools, onboarding, and tenant-wide status." />
    <VAlert
      v-if="!canAccessEnterpriseAdmin"
      type="info"
      variant="tonal"
      class="mb-4"
    >
      Portfolio school administration is limited to enterprise super admins.
    </VAlert>
    <VAlert v-else-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</VAlert>
    <VAlert v-else-if="success" type="success" variant="tonal" class="mb-4">{{ success }}</VAlert>
    <VCard class="school-signal-card">
      <VCardText class="pt-2">
        <VTable class="school-data-table">
          <thead><tr><th>School</th><th>Status</th><th>Plan</th><th>Users</th><th>Students</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="school in schools" :key="school.id">
              <td class="font-weight-medium">{{ school.name }}<div class="text-body-2 text-medium-emphasis">{{ school.slug }}</div></td>
              <td>{{ school.status }}</td>
              <td>{{ school.plan || school.subscription_status || '-' }}</td>
              <td>{{ school.memberships_count ?? 0 }}</td>
              <td>{{ school.students_count ?? 0 }}</td>
              <td class="d-flex gap-2">
                <VBtn size="small" variant="outlined" color="default" :to="`/schools/${school.id}/students`">Open</VBtn>
                <VBtn size="small" color="primary" :loading="onboardingId === school.id" @click="onboardSchool(school.id)">Onboard</VBtn>
              </td>
            </tr>
            <tr v-if="!schools.length && !loading"><td colspan="6">{{ canAccessEnterpriseAdmin ? 'No schools returned.' : 'Enterprise access required.' }}</td></tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </div>
</template>
