<script setup lang="ts">
definePageMeta({ layout: 'default' })

interface AuditLogItem {
  id: number
  event: string
  created_at?: string
  school?: { name?: string; slug?: string } | null
  actor?: { name?: string; email?: string } | null
}

interface ListResponse<T> { data: T[] }

const api = useApi()
const { canAccessEnterpriseAdmin } = useEnterpriseAccess()
const logs = ref<AuditLogItem[]>([])
const loading = ref(false)
const error = ref('')

async function loadLogs() {
  if (!canAccessEnterpriseAdmin.value) {
    logs.value = []
    error.value = ''
    return
  }

  loading.value = true
  error.value = ''
  try {
    const response = await api.request<ListResponse<AuditLogItem>>('/admin/audit-logs?per_page=100')
    logs.value = response.data
  }
  catch (loadError) {
    error.value = loadError instanceof Error ? loadError.message : 'Unable to load audit logs.'
  }
  finally {
    loading.value = false
  }
}

onMounted(loadLogs)
</script>

<template>
  <div>
    <SchoolPageHeader eyebrow="Enterprise" title="Admin audit logs" subtitle="Track portfolio-wide change activity across schools and operators." />
    <VAlert
      v-if="!canAccessEnterpriseAdmin"
      type="info"
      variant="tonal"
      class="mb-4"
    >
      Portfolio audit review is limited to enterprise super admins.
    </VAlert>
    <VAlert v-else-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</VAlert>
    <VCard class="school-signal-card">
      <VCardText class="pt-2">
        <VTable class="school-data-table">
          <thead><tr><th>Event</th><th>School</th><th>Actor</th><th>Created</th></tr></thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td class="font-weight-medium">{{ log.event }}</td>
              <td>{{ log.school?.name || 'System' }}</td>
              <td>{{ log.actor?.name || log.actor?.email || 'System' }}</td>
              <td>{{ log.created_at || '-' }}</td>
            </tr>
            <tr v-if="!logs.length && !loading"><td colspan="4">{{ canAccessEnterpriseAdmin ? 'No audit logs returned.' : 'Enterprise access required.' }}</td></tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </div>
</template>
