<script setup lang="ts">
definePageMeta({ layout: 'default' })

interface AdminUser {
  id: number
  name: string
  email: string
  school_memberships_count?: number
  role_assignments_count?: number
}

interface ListResponse<T> { data: T[] }

const api = useApi()
const { canAccessEnterpriseAdmin } = useEnterpriseAccess()
const users = ref<AdminUser[]>([])
const search = ref('')
const loading = ref(false)
const error = ref('')

async function loadUsers() {
  if (!canAccessEnterpriseAdmin.value) {
    users.value = []
    error.value = ''
    return
  }

  loading.value = true
  error.value = ''
  try {
    const suffix = search.value.trim() ? `?search=${encodeURIComponent(search.value.trim())}&per_page=100` : '?per_page=100'
    const response = await api.request<ListResponse<AdminUser>>(`/admin/users${suffix}`)
    users.value = response.data
  }
  catch (loadError) {
    error.value = loadError instanceof Error ? loadError.message : 'Unable to load enterprise users.'
  }
  finally {
    loading.value = false
  }
}

onMounted(loadUsers)
</script>

<template>
  <div>
    <SchoolPageHeader eyebrow="Enterprise" title="Admin users" subtitle="Inspect the account base and the spread of memberships and role assignments." />
    <VAlert
      v-if="!canAccessEnterpriseAdmin"
      type="info"
      variant="tonal"
      class="mb-4"
    >
      Portfolio user administration is limited to enterprise super admins.
    </VAlert>
    <VAlert v-else-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</VAlert>
    <VRow class="mb-2"><VCol cols="12" md="5"><AppTextField v-model="search" label="Search users" placeholder="Name or email" :disabled="!canAccessEnterpriseAdmin" /></VCol><VCol cols="12" md="2"><VBtn color="primary" :disabled="!canAccessEnterpriseAdmin" @click="loadUsers">Search</VBtn></VCol></VRow>
    <VCard class="school-signal-card">
      <VCardText class="pt-2">
        <VTable class="school-data-table">
          <thead><tr><th>User</th><th>Email</th><th>School memberships</th><th>Role assignments</th></tr></thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td class="font-weight-medium">{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.school_memberships_count ?? 0 }}</td>
              <td>{{ user.role_assignments_count ?? 0 }}</td>
            </tr>
            <tr v-if="!users.length && !loading"><td colspan="4">{{ canAccessEnterpriseAdmin ? 'No users returned.' : 'Enterprise access required.' }}</td></tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </div>
</template>
