export interface ApiUser {
  id: number
  name: string
  email: string
  schools: ApiSchool[]
}

export interface ApiSchool {
  id: number
  public_id?: string
  name: string
  slug: string
  status: string
  roles?: {
    key: string
    name: string
  }[]
  permissions?: string[]
}

export interface AcademicClass {
  id: number
  school_id: number
  name: string
  code: string
  description: string | null
  sort_order: number
  status: string
}

export interface AcademicSection {
  id: number
  school_id: number
  academic_class_id: number
  academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
  name: string
  code: string
  capacity: number | null
  room: string | null
  sort_order: number
  status: string
}

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE'
  body?: Record<string, unknown>
}

export function useApi() {
  const config = useRuntimeConfig()
  const token = useState<string | null>('auth.token', () => null)

  async function request<T>(path: string, options: RequestOptions = {}) {
    const headers: Record<string, string> = {
      Accept: 'application/json',
    }

    if (token.value) {
      headers.Authorization = `Bearer ${token.value}`
    }

    return await $fetch<T>(`${config.public.apiBase}${path}`, {
      method: options.method || 'GET',
      body: options.body,
      headers,
    })
  }

  return { request }
}
