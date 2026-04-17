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

export interface Employee {
  id: number
  school_id: number
  designation_id: number | null
  employee_no: string
  full_name: string
  father_name: string | null
  mother_name: string | null
  email: string | null
  phone: string | null
  gender: string | null
  religion: string | null
  date_of_birth: string | null
  joined_on: string
  salary: string
  employee_type: string
  address: string | null
  notes: string | null
  status: string
  designation?: Pick<Designation, 'id' | 'name' | 'code'> | null
}

export interface Guardian {
  id: number
  school_id: number
  full_name: string
  relationship: string
  phone: string | null
  email: string | null
  occupation: string | null
  address: string | null
  status: string
  students_count?: number
}

export interface Student {
  id: number
  school_id: number
  guardian_id: number | null
  admission_no: string
  full_name: string
  father_name: string | null
  mother_name: string | null
  email: string | null
  phone: string | null
  gender: string | null
  religion: string | null
  date_of_birth: string | null
  admitted_on: string
  address: string | null
  medical_notes: string | null
  status: string
  guardian?: Pick<Guardian, 'id' | 'full_name' | 'relationship' | 'phone'> | null
}

export interface StudentEnrollment {
  id: number
  school_id: number
  student_id: number
  academic_year_id: number
  academic_class_id: number
  academic_section_id: number | null
  student_group_id: number | null
  shift_id: number | null
  roll_no: string | null
  enrolled_on: string
  status: 'active' | 'completed' | 'transferred' | 'archived'
  notes: string | null
  student?: Pick<Student, 'id' | 'admission_no' | 'full_name'>
  academic_year?: Pick<AcademicYear, 'id' | 'name' | 'code'>
  academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
  academic_section?: Pick<AcademicSection, 'id' | 'name' | 'code'> | null
  student_group?: Pick<StudentGroup, 'id' | 'name' | 'code'> | null
  shift?: Pick<Shift, 'id' | 'name' | 'code'> | null
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
