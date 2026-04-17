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

export interface AcademicYear {
  id: number
  school_id: number
  name: string
  code: string
  starts_on: string
  ends_on: string
  is_current: boolean
  status: string
}

export interface Subject {
  id: number
  school_id: number
  name: string
  code: string
  type: 'core' | 'elective' | 'co_curricular'
  description: string | null
  credit_hours: number | null
  sort_order: number
  status: string
}

export interface StudentGroup {
  id: number
  school_id: number
  name: string
  code: string
  description: string | null
  sort_order: number
  status: string
}

export interface Shift {
  id: number
  school_id: number
  name: string
  code: string
  starts_at: string | null
  ends_at: string | null
  description: string | null
  sort_order: number
  status: string
}

export interface ClassSubject {
  id: number
  school_id: number
  academic_class_id: number
  subject_id: number
  academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
  subject?: Pick<Subject, 'id' | 'name' | 'code' | 'type'>
  full_marks: number
  pass_marks: number
  subjective_marks: number | null
  sort_order: number
  status: string
}

export interface Designation {
  id: number
  school_id: number
  name: string
  code: string
  description: string | null
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
