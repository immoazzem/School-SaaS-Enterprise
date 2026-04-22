export function useAuth() {
  const session = useSession()
  const api = useApi()
  const token = useState<string | null>('auth.token', () => session.token.value ?? null)

  watch(
    () => session.token.value,
    (value) => {
      token.value = value ?? null
    },
    { immediate: true },
  )

  const permissions = computed(() => session.selectedSchool.value?.permissions ?? [])

  function can(permission: string) {
    return permissions.value.includes(permission)
  }

  async function refreshProfile() {
    const user = await session.fetchMe()

    token.value = session.token.value ?? null

    return user
  }

  async function refreshSchools() {
    const response = await api.request<{ data: AuthSchool[] }>('/schools')

    const currentUser = session.user.value

    if (currentUser) {
      session.user.value = {
        ...currentUser,
        schools: response.data.map((school) => {
          const existing = currentUser.schools.find(item => item.id === school.id)

          return {
            ...school,
            roles: existing?.roles ?? school.roles,
            permissions: existing?.permissions ?? school.permissions,
          }
        }),
      }
    }

    return response.data
  }

  async function createSchool(input: { name: string; slug?: string; timezone?: string; locale?: string }) {
    const response = await api.request<{ data: AuthSchool }>('/schools', {
      method: 'POST',
      body: input,
    })

    await refreshProfile()
    selectSchool(response.data.id)

    return response.data
  }

  function selectSchool(schoolId: number) {
    session.applySelectedSchool(schoolId)
  }

  return {
    token,
    user: session.user,
    schools: computed(() => session.schools.value),
    selectedSchool: computed(() => session.selectedSchool.value),
    selectedSchoolId: session.selectedSchoolId,
    permissions,
    can,
    login: session.login,
    refreshProfile,
    refreshSchools,
    createSchool,
    selectSchool,
    logout: session.logout,
  }
}
