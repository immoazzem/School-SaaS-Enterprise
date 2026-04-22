<script setup lang="ts">
definePageMeta({
  layout: 'default',
})

interface ListResponse<T> {
  data: T[]
  meta?: {
    total?: number
  }
}

interface NotificationItem {
  id: number
  type: string
  title?: string | null
  body?: string | null
  data?: Record<string, unknown> | null
  read_at?: string | null
  created_at?: string | null
}

interface NotificationUnreadResponse {
  data: {
    unread_count: number
  }
}

interface InvitationItem {
  id: number
  email: string
  name?: string | null
  status: string
  expires_at?: string | null
  role?: {
    id?: number
    name?: string
    key?: string
  } | null
  inviter?: {
    id?: number
    name?: string
    email?: string
  } | null
}

interface InvitationResponse {
  data: InvitationItem
}

const auth = useAuth()
const api = useApi()

const loading = ref(false)
const savingInvitation = ref(false)
const markingRead = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const notifications = ref<NotificationItem[]>([])
const invitations = ref<InvitationItem[]>([])
const unreadCount = ref(0)

const invitationForm = reactive({
  email: '',
  name: '',
})

const selectedSchool = computed(() => auth.selectedSchool.value)
const selectedSchoolId = computed(() => selectedSchool.value?.id ?? null)
const canManageUsers = computed(() => auth.can('users.manage'))

const communicationSignals = computed(() => [
  {
    title: 'Unread notifications',
    value: unreadCount.value.toLocaleString(),
    note: selectedSchool.value ? `${selectedSchool.value.name} operator queue` : 'Select a school first.',
  },
  {
    title: 'Pending invitations',
    value: invitations.value.filter(invitation => invitation.status === 'pending').length.toLocaleString(),
    note: canManageUsers.value ? 'Outstanding join requests for the school team.' : 'Invitation tools unlock when user management is available.',
  },
  {
    title: 'Recent activity items',
    value: notifications.value.length.toLocaleString(),
    note: 'The most recent school-facing updates delivered to this account.',
  },
])

function messageFromError(error: unknown, fallback: string) {
  if (error && typeof error === 'object') {
    const maybeData = (error as { data?: { message?: string } }).data
    if (maybeData?.message) {
      return maybeData.message
    }
  }

  return error instanceof Error ? error.message : fallback
}

async function loadCommunicationHub() {
  if (!selectedSchoolId.value) {
    notifications.value = []
    invitations.value = []
    unreadCount.value = 0
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const requests: Promise<unknown>[] = [
      api.request<ListResponse<NotificationItem>>(`/schools/${selectedSchoolId.value}/notifications?per_page=20`),
      api.request<NotificationUnreadResponse>(`/schools/${selectedSchoolId.value}/notifications/unread-count`),
    ]

    if (canManageUsers.value) {
      requests.push(api.request<ListResponse<InvitationItem>>(`/schools/${selectedSchoolId.value}/invitations?per_page=20`))
    }

    const [notificationResponse, unreadResponse, invitationResponse] = await Promise.all(requests)

    notifications.value = (notificationResponse as ListResponse<NotificationItem>).data ?? []
    unreadCount.value = (unreadResponse as NotificationUnreadResponse).data.unread_count ?? 0
    invitations.value = invitationResponse ? (invitationResponse as ListResponse<InvitationItem>).data ?? [] : []
  }
  catch (error) {
    errorMessage.value = messageFromError(error, 'Unable to load the communication center right now.')
  }
  finally {
    loading.value = false
  }
}

async function markAllRead() {
  if (!selectedSchoolId.value || !unreadCount.value) {
    return
  }

  markingRead.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await api.request(`/schools/${selectedSchoolId.value}/notifications/mark-read`, {
      method: 'POST',
      body: { all: true },
    })
    successMessage.value = 'Notifications marked as read.'
    await loadCommunicationHub()
  }
  catch (error) {
    errorMessage.value = messageFromError(error, 'Unable to mark notifications as read.')
  }
  finally {
    markingRead.value = false
  }
}

async function createInvitation() {
  if (!selectedSchoolId.value) {
    return
  }

  savingInvitation.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await api.request<InvitationResponse>(`/schools/${selectedSchoolId.value}/invitations`, {
      method: 'POST',
      body: {
        email: invitationForm.email,
        name: invitationForm.name || null,
      },
    })

    invitationForm.email = ''
    invitationForm.name = ''
    successMessage.value = 'Invitation created.'
    await loadCommunicationHub()
  }
  catch (error) {
    errorMessage.value = messageFromError(error, 'Unable to create the invitation.')
  }
  finally {
    savingInvitation.value = false
  }
}

async function revokeInvitation(invitation: InvitationItem) {
  if (!selectedSchoolId.value) {
    return
  }

  errorMessage.value = ''
  successMessage.value = ''

  try {
    await api.request(`/schools/${selectedSchoolId.value}/invitations/${invitation.id}`, {
      method: 'DELETE',
    })
    successMessage.value = 'Invitation revoked.'
    await loadCommunicationHub()
  }
  catch (error) {
    errorMessage.value = messageFromError(error, 'Unable to revoke the invitation.')
  }
}

watch(selectedSchoolId, loadCommunicationHub, { immediate: true })
</script>

<template>
  <div>
    <SchoolPageHeader
      eyebrow="Communications"
      title="Notice center"
      subtitle="Run operator notifications and school invitations from the active campus instead of a disconnected placeholder."
    >
      <template #actions>
        <VBtn
          variant="outlined"
          color="default"
          prepend-icon="tabler-mail-opened"
          :loading="markingRead"
          :disabled="!selectedSchoolId || !unreadCount"
          @click="markAllRead"
        >
          Mark all read
        </VBtn>
        <VBtn
          v-if="selectedSchoolId"
          color="primary"
          prepend-icon="tabler-building-bank"
          :to="`/schools/${selectedSchoolId}/reports`"
        >
          Open school reports
        </VBtn>
      </template>
    </SchoolPageHeader>

    <VAlert
      v-if="!selectedSchoolId"
      type="info"
      variant="tonal"
      class="mb-4"
    >
      Select a school from the portfolio first to load notifications and invitations.
    </VAlert>

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
        v-for="item in communicationSignals"
        :key="item.title"
        cols="12"
        md="4"
      >
        <VCard class="school-signal-card h-100">
          <VCardText class="pa-5">
            <div class="school-kicker mb-3">
              Communications signal
            </div>
            <div class="school-metric-card__value text-h6 mb-2">{{ item.value }}</div>
            <div class="text-h6 font-weight-bold mb-2">{{ item.title }}</div>
            <div class="text-body-2 text-medium-emphasis">{{ item.note }}</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" xl="7">
        <VCard class="school-signal-card h-100">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Operator notifications
            </VCardTitle>
            <VCardSubtitle>
              The live account queue from the active school.
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <VTable class="school-data-table">
              <thead>
                <tr>
                  <th>Message</th>
                  <th>State</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="notification in notifications" :key="notification.id">
                  <td class="font-weight-medium">
                    {{ notification.title || notification.type || 'School notification' }}
                    <div class="text-body-2 text-medium-emphasis mt-1">
                      {{ notification.body || notification.data?.message || 'No additional details attached.' }}
                    </div>
                  </td>
                  <td>
                    <VChip :color="notification.read_at ? 'default' : 'warning'" size="small" variant="tonal">
                      {{ notification.read_at ? 'Read' : 'Unread' }}
                    </VChip>
                  </td>
                  <td>{{ notification.created_at || 'Just now' }}</td>
                </tr>
                <tr v-if="!notifications.length && !loading">
                  <td colspan="3" class="text-medium-emphasis">
                    No notifications are waiting for this operator.
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" xl="5">
        <VCard class="school-signal-card mb-4">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Team invitations
            </VCardTitle>
            <VCardSubtitle>
              Bring new operators into the active school workspace.
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <VAlert
              v-if="!canManageUsers"
              type="info"
              variant="tonal"
              class="mb-4"
            >
              Invitation controls are available when the active school grants user management.
            </VAlert>

            <VForm v-else @submit.prevent="createInvitation">
              <VRow>
                <VCol cols="12">
                  <AppTextField
                    v-model="invitationForm.email"
                    label="Invite email"
                    placeholder="teacher@northcampus.edu"
                    required
                  />
                </VCol>
                <VCol cols="12">
                  <AppTextField
                    v-model="invitationForm.name"
                    label="Display name"
                    placeholder="North Campus Teacher"
                  />
                </VCol>
                <VCol cols="12">
                  <VBtn
                    block
                    color="primary"
                    type="submit"
                    :loading="savingInvitation"
                    :disabled="!selectedSchoolId"
                  >
                    Send invitation
                  </VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>

        <VCard class="school-signal-card">
          <VCardItem>
            <VCardTitle class="font-weight-bold">
              Invitation register
            </VCardTitle>
            <VCardSubtitle>
              Pending, accepted, and revoked access records.
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="pt-2">
            <div class="school-alert-list">
              <div
                v-for="invitation in invitations"
                :key="invitation.id"
                class="school-alert-list__item"
              >
                <div>
                  <div class="font-weight-medium">{{ invitation.name || invitation.email }}</div>
                  <div class="text-body-2 text-medium-emphasis">
                    {{ invitation.email }}<span v-if="invitation.role?.name"> · {{ invitation.role.name }}</span>
                  </div>
                </div>

                <div class="d-flex align-center gap-2">
                  <VChip size="small" variant="tonal" :color="invitation.status === 'pending' ? 'warning' : invitation.status === 'accepted' ? 'success' : 'default'">
                    {{ invitation.status }}
                  </VChip>
                  <VBtn
                    v-if="invitation.status === 'pending' && canManageUsers"
                    size="small"
                    variant="text"
                    color="error"
                    @click="revokeInvitation(invitation)"
                  >
                    Revoke
                  </VBtn>
                </div>
              </div>
              <div
                v-if="!invitations.length && !loading"
                class="text-body-2 text-medium-emphasis"
              >
                No invitations yet for the selected school.
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
