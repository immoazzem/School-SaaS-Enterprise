<script setup lang="ts">
interface InvitationItem {
  id: number
  email: string
  name?: string | null
  status: string
  expires_at?: string | null
  role?: {
    name?: string
    key?: string
  } | null
  inviter?: {
    name?: string
    email?: string
  } | null
}

interface ListResponse<T> {
  data: T[]
}

const api = useApi()
const route = useRoute()
const schoolId = computed(() => Number(route.params.schoolId))

const invitations = ref<InvitationItem[]>([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

const form = reactive({
  email: '',
  name: '',
})

async function loadWorkspace() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.request<ListResponse<InvitationItem>>(`/schools/${schoolId.value}/invitations?per_page=50`)
    invitations.value = response.data
  }
  catch (workspaceError) {
    error.value = workspaceError instanceof Error ? workspaceError.message : 'Unable to load invitations.'
  }
  finally {
    loading.value = false
  }
}

async function createInvitation() {
  saving.value = true
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/invitations`, {
      method: 'POST',
      body: {
        email: form.email,
        name: form.name || null,
      },
    })
    form.email = ''
    form.name = ''
    success.value = 'Invitation created.'
    await loadWorkspace()
  }
  catch (inviteError) {
    error.value = inviteError instanceof Error ? inviteError.message : 'Unable to create invitation.'
  }
  finally {
    saving.value = false
  }
}

async function revokeInvitation(id: number) {
  error.value = ''
  success.value = ''

  try {
    await api.request(`/schools/${schoolId.value}/invitations/${id}`, {
      method: 'DELETE',
    })
    success.value = 'Invitation revoked.'
    await loadWorkspace()
  }
  catch (revokeError) {
    error.value = revokeError instanceof Error ? revokeError.message : 'Unable to revoke invitation.'
  }
}

onMounted(loadWorkspace)
</script>

<template>
  <SchoolWorkspaceTemplate>
    <template #navigation>
      <SchoolWorkspaceRail
        :school-id="schoolId"
        aria-label="Invitations navigation"
        context-title="Access control"
        :context-links="[
          { label: 'Notifications', to: `/schools/${schoolId}/notifications` },
          { label: 'Employees', to: `/schools/${schoolId}/employees` },
          { label: 'Settings', to: `/schools/${schoolId}/settings` },
        ]"
      />
    </template>

    <header class="workspace-header">
      <div>
        <p class="eyebrow">Operations</p>
        <h1>Invitations</h1>
        <p>Invite staff into the school workspace and monitor access onboarding.</p>
      </div>
    </header>

    <section class="workspace-grid">
      <form class="surface record-form" @submit.prevent="createInvitation">
        <h2>Invite team member</h2>
        <div class="field">
          <label for="invitation-email">Email</label>
          <input id="invitation-email" v-model="form.email" required type="email" placeholder="teacher@school.test" />
        </div>
        <div class="field">
          <label for="invitation-name">Name</label>
          <input id="invitation-name" v-model="form.name" type="text" placeholder="Teacher name" />
        </div>
        <VAlert v-if="error" type="error" variant="tonal">{{ error }}</VAlert>
        <VAlert v-if="success" type="success" variant="tonal">{{ success }}</VAlert>
        <VBtn color="primary" :loading="saving" type="submit">Send invitation</VBtn>
      </form>

      <section class="surface record-list">
        <div class="list-header">
          <h2>Invitation register</h2>
          <span class="muted">{{ loading ? 'Refreshing...' : `${invitations.length} records` }}</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Invitee</th>
                <th>Status</th>
                <th>Expires</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="invitation in invitations" :key="invitation.id">
                <td>
                  <strong>{{ invitation.name || invitation.email }}</strong>
                  <div class="muted">{{ invitation.email }}</div>
                </td>
                <td>{{ invitation.status }}</td>
                <td>{{ invitation.expires_at || 'Default expiry' }}</td>
                <td>
                  <VBtn v-if="invitation.status === 'pending'" size="small" variant="text" color="error" @click="revokeInvitation(invitation.id)">Revoke</VBtn>
                </td>
              </tr>
              <tr v-if="!invitations.length && !loading">
                <td colspan="4">No invitations created yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </SchoolWorkspaceTemplate>
</template>
