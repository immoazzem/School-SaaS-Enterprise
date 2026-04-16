import type { ApiSchool, ApiUser } from './useApi'

interface LoginResponse {
  token: string
  token_type: string
  user: ApiUser
}

interface MeResponse {
  user: ApiUser
}

interface SchoolsResponse {
  data: ApiSchool[]
}

export function useAuth() {
  const api = useApi()
  const token = useState<string | null>('auth.token', () => null)
  const user = useState<ApiUser | null>('auth.user', () => null)
  const schools = useState<ApiSchool[]>('auth.schools', () => [])
  const selectedSchoolId = useState<number | null>('auth.selectedSchoolId', () => null)

  if (import.meta.client && token.value === null) {
    token.value = localStorage.getItem('school_saas_token')
    const storedSchool = localStorage.getItem('school_saas_school_id')
    selectedSchoolId.value = storedSchool ? Number(storedSchool) : null
  }

  async function login(email: string, password: string) {
    const response = await api.request<LoginResponse>('/auth/login', {
      method: 'POST',
      body: {
        email,
        password,
        device_name: 'nuxt-web',
      },
    })

    token.value = response.token
    user.value = response.user
    schools.value = response.user.schools
    selectedSchoolId.value = response.user.schools[0]?.id ?? null

    if (import.meta.client) {
      localStorage.setItem('school_saas_token', response.token)
      if (selectedSchoolId.value) {
        localStorage.setItem('school_saas_school_id', String(selectedSchoolId.value))
      }
    }

    return response
  }

  async function refreshProfile() {
    if (!token.value) {
      return null
    }

    const response = await api.request<MeResponse>('/me')
    user.value = response.user
    schools.value = response.user.schools
    selectedSchoolId.value ??= response.user.schools[0]?.id ?? null

    return response.user
  }

  async function refreshSchools() {
    const response = await api.request<SchoolsResponse>('/schools')
    schools.value = response.data
    selectedSchoolId.value ??= response.data[0]?.id ?? null

    return response.data
  }

  function selectSchool(schoolId: number) {
    selectedSchoolId.value = schoolId

    if (import.meta.client) {
      localStorage.setItem('school_saas_school_id', String(schoolId))
    }
  }

  function logout() {
    token.value = null
    user.value = null
    schools.value = []
    selectedSchoolId.value = null

    if (import.meta.client) {
      localStorage.removeItem('school_saas_token')
      localStorage.removeItem('school_saas_school_id')
    }
  }

  return {
    token,
    user,
    schools,
    selectedSchoolId,
    login,
    refreshProfile,
    refreshSchools,
    selectSchool,
    logout,
  }
}
