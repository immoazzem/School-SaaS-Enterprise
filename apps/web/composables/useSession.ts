import type { AuthSchool, AuthUser, LoginResponse } from '~/composables/useApi'

export function useSession() {
  const token = useCookie<string | null>('accessToken', {
    sameSite: 'lax',
    secure: false,
    default: () => null,
  })
  const selectedSchoolId = useCookie<number | null>('selectedSchoolId', {
    sameSite: 'lax',
    secure: false,
    default: () => null,
  })

  const user = useState<AuthUser | null>('session.user', () => null)
  const loading = useState<boolean>('session.loading', () => false)
  const hydrated = useState<boolean>('session.hydrated', () => false)

  const schools = computed(() => user.value?.schools ?? [])
  const selectedSchool = computed<AuthSchool | null>(() => {
    if (!schools.value.length)
      return null

    const preferred = selectedSchoolId.value
      ? schools.value.find(school => school.id === Number(selectedSchoolId.value))
      : null

    return preferred ?? schools.value[0] ?? null
  })

  function applySelectedSchool(id: number | null) {
    selectedSchoolId.value = id
  }

  function syncSelectedSchool() {
    if (!schools.value.length) {
      selectedSchoolId.value = null
      return
    }

    if (!selectedSchool.value)
      selectedSchoolId.value = schools.value[0].id
  }

  async function fetchMe() {
    if (!token.value) {
      user.value = null
      hydrated.value = true
      return null
    }

    loading.value = true

    try {
      const response = await useApiFetch<{ user: AuthUser }>('/me')
      user.value = response.user
      syncSelectedSchool()
      hydrated.value = true

      return response.user
    }
    catch (error) {
      token.value = null
      user.value = null
      selectedSchoolId.value = null
      hydrated.value = true
      throw error
    }
    finally {
      loading.value = false
    }
  }

  async function login(email: string, password: string) {
    loading.value = true

    try {
      const response = await useApiFetch<LoginResponse>('/auth/login', {
        method: 'POST',
        body: {
          email,
          password,
          device_name: 'school-saas-web',
        },
      })

      token.value = response.token
      user.value = response.user
      hydrated.value = true
      syncSelectedSchool()

      return response
    }
    finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      if (token.value)
        await useApiFetch('/auth/logout', { method: 'POST' })
    }
    catch {
      // Ignore logout failures and clear the local session anyway.
    }
    finally {
      token.value = null
      user.value = null
      selectedSchoolId.value = null
      hydrated.value = true
    }
  }

  return {
    token,
    user,
    schools,
    selectedSchool,
    selectedSchoolId,
    loading,
    hydrated,
    applySelectedSchool,
    fetchMe,
    login,
    logout,
  }
}
