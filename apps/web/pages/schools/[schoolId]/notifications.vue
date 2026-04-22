<script setup lang="ts">
interface NotificationItem {
  id: number
  type: string
  title?: string | null
  body?: string | null
  data?: Record<string, unknown> | null
  read_at?: string | null
  created_at?: string | null
}

interface ListResponse<T> {
  data: T[]
}

interface NotificationUnreadResponse {
  data: {
    unread_count: number
  }
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const notifications = ref<NotificationItem[]>([])
const unreadCount = ref(0)
const loading = ref(false)
const marking = ref(false)
const error = ref('')
const success = ref('')

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const [notificationResponse, unreadResponse] = await Promise.all([
      api.request<ListResponse<NotificationItem>>(`/schools/${schoolId.value}/notifications?per_page=50`),
      api.request<NotificationUnreadResponse>(`/schools/${schoolId.value}/notifications/unread-count`),
    ])
    notifications.value = notificationResponse.data
    unreadCount.value = unreadResponse.data.unread_count
  }
  catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load notifications.'
  }
  finally {
    loading.value = false
  }
}

async function markAllRead() {
  marking.value = true
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/notifications/mark-read`, {
      method: 'POST',
      body: { all: true },
    })
    success.value = 'Notifications marked as read.'
    await loadWorkspace()
  }
  catch (markError) {
    error.value = markError instanceof Error ? markError.message : 'Unable to mark notifications as read.'
  }
  finally {
    marking.value = false
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
        :school-id="schoolId"
        aria-label="Notifications navigation"
        context-title="Operations links"
        :context-links="[
          { label: 'Reports', to: `/schools/${schoolId}/reports` },
          { label: 'Documents', to: `/schools/${schoolId}/documents` },
          { label: 'Invitations', to: `/schools/${schoolId}/invitations` },
        ]"
      />
    </template>

    <header class="workspace-header">
      <div>
        <p class="eyebrow">Operations</p>
        <h1>Notifications</h1>
        <p>Track the live delivery queue and operator-facing alerts for this school.</p>
      </div>
      <div class="header-actions">
        <VBtn color="default" variant="outlined" :loading="marking" :disabled="!unreadCount" @click="markAllRead">Mark all read</VBtn>
      </div>
    </header>

    <section class="summary-grid">
      <article class="surface summary-item">
        <span>Total notifications</span>
        <strong>{{ notifications.length }}</strong>
      </article>
      <article class="surface summary-item">
        <span>Unread</span>
        <strong>{{ unreadCount }}</strong>
      </article>
    </section>

    <VAlert v-if="error" type="error" variant="tonal">{{ error }}</VAlert>
    <VAlert v-if="success" type="success" variant="tonal">{{ success }}</VAlert>

    <section class="surface record-list">
      <div class="list-header">
        <h2>Notification register</h2>
        <span class="muted">{{ loading ? 'Refreshing...' : `${notifications.length} records` }}</span>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Message</th>
              <th>Status</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="notification in notifications" :key="notification.id">
              <td>
                <strong>{{ notification.title || notification.type || 'School notification' }}</strong>
                <div class="muted">{{ notification.body || notification.data?.message || 'No extra details provided.' }}</div>
              </td>
              <td>{{ notification.read_at ? 'Read' : 'Unread' }}</td>
              <td>{{ notification.created_at || 'Just now' }}</td>
            </tr>
            <tr v-if="!notifications.length && !loading">
              <td colspan="3">No notifications found for this school.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </SchoolWorkspaceTemplate>
</template>
